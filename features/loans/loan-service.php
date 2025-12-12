<?php

class LoanService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAllLoans($userId, $filters = [], $page = 1, $perPage = 20) {
        try {
            $offset = ($page - 1) * $perPage;
            $where = ["1=1"];
            $params = [];

            // Filtro por status
            if (!empty($filters['status'])) {
                if ($filters['status'] === 'overdue') {
                    // Empréstimos ativos com parcelas atrasadas
                    $where[] = "l.status = 'active' AND EXISTS (SELECT 1 FROM loan_installments WHERE loan_id = l.id AND status = 'overdue')";
                } else {
                    $where[] = "l.status = :status";
                    $params['status'] = $filters['status'];
                }
            }

            // Filtro por cliente
            if (!empty($filters['client_id'])) {
                $where[] = "l.client_id = :client_id";
                $params['client_id'] = $filters['client_id'];
            }

            // Filtro por carteira
            if (!empty($filters['wallet_id'])) {
                $where[] = "l.wallet_id = :wallet_id";
                $params['wallet_id'] = $filters['wallet_id'];
            }

            // Filtro por busca (nome do cliente)
            if (!empty($filters['search'])) {
                $where[] = "(c.name LIKE :search OR c.cpf LIKE :search)";
                $params['search'] = '%' . $filters['search'] . '%';
            }

            // Filtro por data inicial
            if (!empty($filters['start_date'])) {
                $where[] = "DATE(l.created_at) >= :start_date";
                $params['start_date'] = $filters['start_date'];
            }

            // Filtro por data final
            if (!empty($filters['end_date'])) {
                $where[] = "DATE(l.created_at) <= :end_date";
                $params['end_date'] = $filters['end_date'];
            }

            $whereClause = implode(" AND ", $where);

            // Query para total de registros
            $countStmt = $this->db->prepare("
                SELECT COUNT(DISTINCT l.id) as total
                FROM loans l
                INNER JOIN clients c ON l.client_id = c.id
                INNER JOIN wallets w ON l.wallet_id = w.id
                WHERE $whereClause
            ");
            $countStmt->execute($params);
            $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Query principal com paginação
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
                LEFT JOIN loan_installments i ON l.id = i.loan_id
                WHERE $whereClause
                GROUP BY l.id
                ORDER BY l.created_at DESC
                LIMIT :limit OFFSET :offset
            ");

            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            $stmt->execute();
            $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'data' => $loans,
                'pagination' => [
                    'total' => $totalRecords,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => ceil($totalRecords / $perPage)
                ]
            ];
        } catch (PDOException $e) {
            error_log("Erro ao buscar empréstimos: " . $e->getMessage());
            return [
                'data' => [],
                'pagination' => [
                    'total' => 0,
                    'per_page' => $perPage,
                    'current_page' => 1,
                    'total_pages' => 0
                ]
            ];
        }
    }

    /**
     * Calcula estatísticas dos empréstimos com filtros aplicados
     */
    public function getLoansStats($userId, $filters = []) {
        try {
            $where = ["l.user_id = :user_id"];
            $params = ['user_id' => $userId];

            // Aplicar os mesmos filtros do getAllLoans
            if (!empty($filters['status'])) {
                if ($filters['status'] === 'overdue') {
                    $where[] = "l.status = 'active' AND EXISTS (SELECT 1 FROM loan_installments WHERE loan_id = l.id AND status = 'overdue')";
                } else {
                    $where[] = "l.status = :status";
                    $params['status'] = $filters['status'];
                }
            }

            if (!empty($filters['client_id'])) {
                $where[] = "l.client_id = :client_id";
                $params['client_id'] = $filters['client_id'];
            }

            if (!empty($filters['wallet_id'])) {
                $where[] = "l.wallet_id = :wallet_id";
                $params['wallet_id'] = $filters['wallet_id'];
            }

            if (!empty($filters['search'])) {
                $where[] = "(c.name LIKE :search OR c.cpf LIKE :search)";
                $params['search'] = '%' . $filters['search'] . '%';
            }

            if (!empty($filters['start_date'])) {
                $where[] = "DATE(l.created_at) >= :start_date";
                $params['start_date'] = $filters['start_date'];
            }

            if (!empty($filters['end_date'])) {
                $where[] = "DATE(l.created_at) <= :end_date";
                $params['end_date'] = $filters['end_date'];
            }

            $whereClause = implode(" AND ", $where);

            // Query para calcular estatísticas
            $stmt = $this->db->prepare("
                SELECT
                    COUNT(*) as total_loans,
                    COUNT(CASE WHEN l.status = 'active' THEN 1 END) as active_loans,
                    SUM(l.amount) as total_emprestado,
                    SUM(l.interest_amount) as total_juros,
                    SUM(CASE
                        WHEN l.status = 'active'
                        THEN l.total_amount * ((l.total_installments - l.paid_installments) / l.total_installments)
                        ELSE 0
                    END) as total_a_receber
                FROM loans l
                INNER JOIN clients c ON l.client_id = c.id
                WHERE {$whereClause}
            ");
            $stmt->execute($params);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'total_loans' => (int)$stats['total_loans'],
                'active_loans' => (int)$stats['active_loans'],
                'total_emprestado' => (float)$stats['total_emprestado'] ?? 0,
                'total_juros' => (float)$stats['total_juros'] ?? 0,
                'total_a_receber' => (float)$stats['total_a_receber'] ?? 0
            ];
        } catch (PDOException $e) {
            error_log("Erro ao calcular estatísticas: " . $e->getMessage());
            return [
                'total_loans' => 0,
                'active_loans' => 0,
                'total_emprestado' => 0,
                'total_juros' => 0,
                'total_a_receber' => 0
            ];
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
                WHERE l.id = :id
            ");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar empréstimo: " . $e->getMessage());
            return null;
        }
    }

    public function createLoan($userId, $clientId, $walletId, $amount, $installmentsCount, $totalAmount = null, $installmentAmount = null, $firstDueDate = null) {
        try {
            $this->db->beginTransaction();

            // Verificar se carteira tem saldo
            $stmt = $this->db->prepare("SELECT balance FROM wallets WHERE id = :id");
            $stmt->execute(['id' => $walletId]);
            $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$wallet || $wallet['balance'] < $amount) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Saldo insuficiente na carteira'];
            }

            // Se totalAmount ou installmentAmount foram fornecidos, usar esses valores
            // Caso contrário, calcular usando a taxa padrão
            if ($totalAmount !== null && $totalAmount > 0) {
                // Usar o total fornecido
                $interestAmount = $totalAmount - $amount;
                $interestRate = ($interestAmount / $amount) * 100;

                if ($installmentAmount === null || $installmentAmount <= 0) {
                    $installmentAmount = $totalAmount / $installmentsCount;
                }
            } elseif ($installmentAmount !== null && $installmentAmount > 0) {
                // Calcular do valor da parcela
                $totalAmount = $installmentAmount * $installmentsCount;
                $interestAmount = $totalAmount - $amount;
                $interestRate = ($interestAmount / $amount) * 100;
            } else {
                // Calcular usando taxa padrão
                if ($installmentsCount == 1) {
                    $interestRate = 20; // 20% à vista
                } else {
                    $interestRate = $installmentsCount * 15; // 15% ao mês
                }

                $interestAmount = ($amount * $interestRate) / 100;
                $totalAmount = $amount + $interestAmount;
                $installmentAmount = $totalAmount / $installmentsCount;
            }

            // Criar empréstimo
            $stmt = $this->db->prepare("
                INSERT INTO loans (client_id, wallet_id, amount, interest_rate, interest_amount, total_amount, installments_count, status, created_at)
                VALUES (:client_id, :wallet_id, :amount, :interest_rate, :interest_amount, :total_amount, :installments_count, 'active', NOW())
            ");
            $stmt->execute([
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
            // Usar a data fornecida ou +30 dias como padrão
            if ($firstDueDate) {
                $dueDate = new DateTime($firstDueDate);
            } else {
                $dueDate = new DateTime();
                $dueDate->modify('+30 days');
            }

            for ($i = 1; $i <= $installmentsCount; $i++) {
                $stmt = $this->db->prepare("
                    INSERT INTO loan_installments (loan_id, installment_number, amount, due_date, status, created_at)
                    VALUES (:loan_id, :installment_number, :amount, :due_date, 'pending', NOW())
                ");
                $stmt->execute([
                    'loan_id' => $loanId,
                    'installment_number' => $i,
                    'amount' => $installmentAmount,
                    'due_date' => $dueDate->format('Y-m-d')
                ]);

                // Próxima parcela vence 1 mês depois
                $dueDate->modify('+1 month');
            }

            // Debitar da carteira
            $stmt = $this->db->prepare("
                UPDATE wallets SET balance = balance - :amount, updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                'id' => $walletId,
                'amount' => $amount
            ]);

            // Registrar transação
            $stmt = $this->db->prepare("
                INSERT INTO wallet_transactions (wallet_id, type, amount, description, reference_type, reference_id, created_at)
                VALUES (:wallet_id, 'loan_out', :amount, :description, 'loan', :loan_id, NOW())
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
                SELECT * FROM loan_installments
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

    public function payInstallment($installmentId, $userId, $adjustmentAmount = 0, $adjustmentReason = '', $paymentMethod = 'Não especificado') {
        try {
            $this->db->beginTransaction();

            // Buscar parcela e empréstimo
            $stmt = $this->db->prepare("
                SELECT i.*, l.wallet_id
                FROM loan_installments i
                INNER JOIN loans l ON i.loan_id = l.id
                WHERE i.id = :id
            ");
            $stmt->execute(['id' => $installmentId]);
            $installment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$installment) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Parcela não encontrada'];
            }

            if ($installment['status'] === 'paid') {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Parcela já foi paga'];
            }

            // Calcular valor final (com ajuste se houver)
            $finalAmount = $installment['amount'] + $adjustmentAmount;

            // Marcar parcela como paga
            $stmt = $this->db->prepare("
                UPDATE loan_installments
                SET status = 'paid',
                    paid_date = NOW(),
                    amount_paid = :amount_paid,
                    adjustment_amount = :adjustment_amount,
                    adjustment_reason = :adjustment_reason,
                    paid_by = :paid_by,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                'id' => $installmentId,
                'amount_paid' => $finalAmount,
                'adjustment_amount' => $adjustmentAmount,
                'adjustment_reason' => $adjustmentReason,
                'paid_by' => $paymentMethod
            ]);

            // Creditar na carteira (valor final)
            $stmt = $this->db->prepare("
                UPDATE wallets
                SET balance = balance + :amount, updated_at = NOW()
                WHERE id = :wallet_id
            ");
            $stmt->execute([
                'wallet_id' => $installment['wallet_id'],
                'amount' => $finalAmount
            ]);

            // Registrar transação
            $stmt = $this->db->prepare("
                INSERT INTO wallet_transactions (wallet_id, type, amount, description, reference_type, reference_id, created_at)
                VALUES (:wallet_id, 'loan_payment', :amount, :description, 'installment', :installment_id, NOW())
            ");
            $description = "Pagamento parcela #{$installment['installment_number']} - Empréstimo #{$installment['loan_id']}";
            if ($adjustmentAmount != 0) {
                $description .= " (Ajuste: " . ($adjustmentAmount > 0 ? '+' : '') . "R$ " . number_format(abs($adjustmentAmount), 2, ',', '.') . ")";
            }
            $stmt->execute([
                'wallet_id' => $installment['wallet_id'],
                'amount' => $finalAmount,
                'description' => $description,
                'installment_id' => $installmentId
            ]);

            // Verificar se todas parcelas foram pagas
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total, COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid
                FROM loan_installments WHERE loan_id = :loan_id
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
                UPDATE loan_installments
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

    public function payoffLoan($loanId, $walletId, $finalAmount, $adjustmentAmount, $adjustmentReason, $paymentMethod, $userId) {
        try {
            $this->db->beginTransaction();

            // Buscar empréstimo
            $loan = $this->getLoanById($loanId, $userId);
            if (!$loan || $loan['status'] === 'paid') {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Empréstimo não encontrado ou já quitado'];
            }

            // Buscar parcelas pendentes
            $stmt = $this->db->prepare("
                SELECT * FROM loan_installments
                WHERE loan_id = :loan_id AND status != 'paid'
                ORDER BY installment_number ASC
            ");
            $stmt->execute(['loan_id' => $loanId]);
            $pendingInstallments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($pendingInstallments)) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Não há parcelas pendentes'];
            }

            $totalPending = array_sum(array_map(fn($i) => $i['amount'], $pendingInstallments));
            $countPending = count($pendingInstallments);

            // Distribuir o ajuste proporcionalmente entre as parcelas
            $adjustmentPerInstallment = $countPending > 0 ? $adjustmentAmount / $countPending : 0;

            // Marcar todas as parcelas pendentes como pagas
            foreach ($pendingInstallments as $installment) {
                $amountPaid = $installment['amount'] + $adjustmentPerInstallment;

                $stmt = $this->db->prepare("
                    UPDATE loan_installments
                    SET status = 'paid',
                        paid_date = NOW(),
                        amount_paid = :amount_paid,
                        adjustment_amount = :adjustment_amount,
                        adjustment_reason = :adjustment_reason,
                        paid_by = :paid_by,
                        updated_at = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([
                    'id' => $installment['id'],
                    'amount_paid' => $amountPaid,
                    'adjustment_amount' => $adjustmentPerInstallment,
                    'adjustment_reason' => $adjustmentReason,
                    'paid_by' => $paymentMethod
                ]);

                // Registrar transação na carteira para cada parcela
                $stmt = $this->db->prepare("
                    INSERT INTO wallet_transactions (wallet_id, type, amount, description, reference_type, reference_id, created_at)
                    VALUES (:wallet_id, 'loan_payment', :amount, :description, 'installment', :installment_id, NOW())
                ");
                $stmt->execute([
                    'wallet_id' => $walletId,
                    'amount' => $amountPaid,
                    'description' => "Quitação - Empréstimo #{$loanId} - Parcela {$installment['installment_number']}",
                    'installment_id' => $installment['id']
                ]);
            }

            // Adicionar valor total à carteira
            $stmt = $this->db->prepare("
                UPDATE wallets SET balance = balance + :amount, updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                'id' => $walletId,
                'amount' => $finalAmount
            ]);

            // Marcar empréstimo como pago
            $stmt = $this->db->prepare("
                UPDATE loans SET status = 'paid', updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute(['id' => $loanId]);

            $this->db->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erro ao quitar empréstimo: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erro ao processar quitação'];
        }
    }

    public function calculateLateFee($installmentAmount, $dueDate) {
        require_once __DIR__ . '/../settings/settings-service.php';
        $settingsService = new SettingsService();

        // Buscar configurações
        $gracePeriodDays = $settingsService->getSetting('grace_period_days', 3);
        $lateFeePercentage = $settingsService->getSetting('late_fee_percentage', 2);

        // Calcular dias de atraso
        $dueDateObj = new DateTime($dueDate);
        $today = new DateTime();
        $diff = $today->diff($dueDateObj);
        $daysLate = $today > $dueDateObj ? $diff->days : 0;

        // Se ainda está no período de carência, não há multa
        if ($daysLate <= $gracePeriodDays) {
            return [
                'days_late' => $daysLate,
                'days_after_grace' => 0,
                'late_fee_percentage' => 0,
                'late_fee_amount' => 0,
                'total_amount' => $installmentAmount,
                'in_grace_period' => true,
                'grace_period_days' => $gracePeriodDays
            ];
        }

        // Calcular dias após a carência
        $daysAfterGrace = $daysLate - $gracePeriodDays;

        // Calcular multa (percentual por dia)
        $lateFeeAmount = $installmentAmount * ($lateFeePercentage / 100) * $daysAfterGrace;
        $totalAmount = $installmentAmount + $lateFeeAmount;

        return [
            'days_late' => $daysLate,
            'days_after_grace' => $daysAfterGrace,
            'late_fee_percentage' => $lateFeePercentage,
            'late_fee_amount' => $lateFeeAmount,
            'total_amount' => $totalAmount,
            'in_grace_period' => false,
            'grace_period_days' => $gracePeriodDays
        ];
    }

    public function getInstallmentWithLateFee($installmentId, $userId) {
        $stmt = $this->db->prepare("
            SELECT i.*, l.wallet_id, l.client_id
            FROM loan_installments i
            INNER JOIN loans l ON i.loan_id = l.id
            WHERE i.id = :id
        ");
        $stmt->execute(['id' => $installmentId]);
        $installment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$installment) {
            return null;
        }

        // Se a parcela está atrasada, calcular multa
        if ($installment['status'] === 'overdue') {
            $lateFeeInfo = $this->calculateLateFee($installment['amount'], $installment['due_date']);
            $installment['late_fee_info'] = $lateFeeInfo;
            $installment['amount_with_late_fee'] = $lateFeeInfo['total_amount'];
        } else {
            $installment['late_fee_info'] = null;
            $installment['amount_with_late_fee'] = $installment['amount'];
        }

        return $installment;
    }
}
