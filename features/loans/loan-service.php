<?php

class LoanService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAllLoans($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    l.*,
                    c.name as client_name,
                    c.cpf as client_cpf,
                    w.name as wallet_name,
                    COUNT(DISTINCT i.id) as total_installments,
                    COUNT(DISTINCT CASE WHEN i.status = 'paid' THEN i.id END) as paid_installments,
                    COUNT(DISTINCT CASE WHEN i.status = 'overdue' THEN i.id END) as overdue_installments
                FROM loans l
                INNER JOIN clients c ON l.client_id = c.id
                INNER JOIN wallets w ON l.wallet_id = w.id
                LEFT JOIN installments i ON l.id = i.loan_id
                WHERE l.user_id = :user_id
                GROUP BY l.id
                ORDER BY l.created_at DESC
            ");
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar empréstimos: " . $e->getMessage());
            return [];
        }
    }

    public function getLoanById($id, $userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    l.*,
                    c.name as client_name,
                    c.cpf as client_cpf,
                    c.phone as client_phone,
                    w.name as wallet_name
                FROM loans l
                INNER JOIN clients c ON l.client_id = c.id
                INNER JOIN wallets w ON l.wallet_id = w.id
                WHERE l.id = :id AND l.user_id = :user_id
            ");
            $stmt->execute(['id' => $id, 'user_id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar empréstimo: " . $e->getMessage());
            return null;
        }
    }

    public function createLoan($userId, $clientId, $walletId, $amount, $installmentsCount) {
        try {
            $this->db->beginTransaction();

            // Verificar se carteira tem saldo
            $stmt = $this->db->prepare("SELECT balance FROM wallets WHERE id = :id AND user_id = :user_id");
            $stmt->execute(['id' => $walletId, 'user_id' => $userId]);
            $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$wallet || $wallet['balance'] < $amount) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Saldo insuficiente na carteira'];
            }

            // Calcular juros
            if ($installmentsCount == 1) {
                $interestRate = 20; // 20% à vista
            } else {
                $interestRate = $installmentsCount * 15; // 15% ao mês
            }

            $interestAmount = ($amount * $interestRate) / 100;
            $totalAmount = $amount + $interestAmount;
            $installmentAmount = $totalAmount / $installmentsCount;

            // Criar empréstimo
            $stmt = $this->db->prepare("
                INSERT INTO loans (user_id, client_id, wallet_id, amount, interest_rate, interest_amount, total_amount, installments_count, status, created_at)
                VALUES (:user_id, :client_id, :wallet_id, :amount, :interest_rate, :interest_amount, :total_amount, :installments_count, 'active', NOW())
            ");
            $stmt->execute([
                'user_id' => $userId,
                'client_id' => $clientId,
                'wallet_id' => $walletId,
                'amount' => $amount,
                'interest_rate' => $interestRate,
                'interest_amount' => $interestAmount,
                'total_amount' => $totalAmount,
                'installments_count' => $installmentsCount
            ]);

            $loanId = $this->db->lastInsertId();

            // Criar parcelas
            $dueDate = new DateTime();
            for ($i = 1; $i <= $installmentsCount; $i++) {
                $dueDate->modify('+1 month');
                $stmt = $this->db->prepare("
                    INSERT INTO installments (loan_id, installment_number, amount, due_date, status, created_at)
                    VALUES (:loan_id, :installment_number, :amount, :due_date, 'pending', NOW())
                ");
                $stmt->execute([
                    'loan_id' => $loanId,
                    'installment_number' => $i,
                    'amount' => $installmentAmount,
                    'due_date' => $dueDate->format('Y-m-d')
                ]);
            }

            // Debitar da carteira
            $stmt = $this->db->prepare("
                UPDATE wallets SET balance = balance - :amount, updated_at = NOW()
                WHERE id = :id AND user_id = :user_id
            ");
            $stmt->execute([
                'id' => $walletId,
                'user_id' => $userId,
                'amount' => $amount
            ]);

            // Registrar transação
            $stmt = $this->db->prepare("
                INSERT INTO transactions (wallet_id, type, amount, description, reference_type, reference_id, created_at)
                VALUES (:wallet_id, 'loan_disbursement', :amount, :description, 'loan', :loan_id, NOW())
            ");
            $stmt->execute([
                'wallet_id' => $walletId,
                'amount' => $amount,
                'description' => "Empréstimo #$loanId concedido",
                'loan_id' => $loanId
            ]);

            $this->db->commit();
            return ['success' => true, 'id' => $loanId];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erro ao criar empréstimo: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erro ao criar empréstimo'];
        }
    }

    public function getInstallments($loanId, $userId) {
        try {
            $loan = $this->getLoanById($loanId, $userId);
            if (!$loan) {
                return [];
            }

            $stmt = $this->db->prepare("
                SELECT * FROM installments
                WHERE loan_id = :loan_id
                ORDER BY installment_number ASC
            ");
            $stmt->execute(['loan_id' => $loanId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar parcelas: " . $e->getMessage());
            return [];
        }
    }

    public function payInstallment($installmentId, $userId) {
        try {
            $this->db->beginTransaction();

            // Buscar parcela e empréstimo
            $stmt = $this->db->prepare("
                SELECT i.*, l.wallet_id, l.user_id
                FROM installments i
                INNER JOIN loans l ON i.loan_id = l.id
                WHERE i.id = :id
            ");
            $stmt->execute(['id' => $installmentId]);
            $installment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$installment) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Parcela não encontrada'];
            }

            if ($installment['user_id'] != $userId) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Sem permissão'];
            }

            if ($installment['status'] === 'paid') {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Parcela já foi paga'];
            }

            // Marcar parcela como paga
            $stmt = $this->db->prepare("
                UPDATE installments
                SET status = 'paid', paid_date = NOW(), updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute(['id' => $installmentId]);

            // Creditar na carteira
            $stmt = $this->db->prepare("
                UPDATE wallets
                SET balance = balance + :amount, updated_at = NOW()
                WHERE id = :wallet_id
            ");
            $stmt->execute([
                'wallet_id' => $installment['wallet_id'],
                'amount' => $installment['amount']
            ]);

            // Registrar transação
            $stmt = $this->db->prepare("
                INSERT INTO transactions (wallet_id, type, amount, description, reference_type, reference_id, created_at)
                VALUES (:wallet_id, 'payment_received', :amount, :description, 'installment', :installment_id, NOW())
            ");
            $stmt->execute([
                'wallet_id' => $installment['wallet_id'],
                'amount' => $installment['amount'],
                'description' => "Pagamento parcela #{$installment['installment_number']} - Empréstimo #{$installment['loan_id']}",
                'installment_id' => $installmentId
            ]);

            // Verificar se todas parcelas foram pagas
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total, COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid
                FROM installments WHERE loan_id = :loan_id
            ");
            $stmt->execute(['loan_id' => $installment['loan_id']]);
            $counts = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($counts['total'] == $counts['paid']) {
                $stmt = $this->db->prepare("
                    UPDATE loans SET status = 'paid', updated_at = NOW()
                    WHERE id = :id
                ");
                $stmt->execute(['id' => $installment['loan_id']]);
            }

            $this->db->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erro ao pagar parcela: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erro ao processar pagamento'];
        }
    }

    public function updateOverdueInstallments() {
        try {
            $stmt = $this->db->prepare("
                UPDATE installments
                SET status = 'overdue'
                WHERE status = 'pending' AND due_date < CURDATE()
            ");
            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Erro ao atualizar parcelas atrasadas: " . $e->getMessage());
            return 0;
        }
    }

    public function calculateLoanDetails($amount, $installmentsCount) {
        if ($installmentsCount == 1) {
            $interestRate = 20;
        } else {
            $interestRate = $installmentsCount * 15;
        }

        $interestAmount = ($amount * $interestRate) / 100;
        $totalAmount = $amount + $interestAmount;
        $installmentAmount = $totalAmount / $installmentsCount;

        return [
            'amount' => $amount,
            'interest_rate' => $interestRate,
            'interest_amount' => $interestAmount,
            'total_amount' => $totalAmount,
            'installment_amount' => $installmentAmount,
            'installments_count' => $installmentsCount
        ];
    }
}
