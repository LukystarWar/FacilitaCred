<?php

class WalletService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Busca todas as carteiras do usuário
     */
    public function getAllWallets($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    w.*,
                    (SELECT COUNT(*) FROM wallet_transactions WHERE wallet_id = w.id) as transaction_count
                FROM wallets w
                WHERE w.is_active = 1
                ORDER BY w.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar carteiras: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca uma carteira específica por ID
     */
    public function getWalletById($id, $userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM wallets
                WHERE id = :id
            ");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar carteira: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Cria uma nova carteira
     */
    public function createWallet($userId, $name, $initialBalance = 0) {
        try {
            $this->db->beginTransaction();

            // Inserir a carteira
            $stmt = $this->db->prepare("
                INSERT INTO wallets (name, balance, created_at)
                VALUES (:name, :balance, NOW())
            ");
            $stmt->execute([
                'name' => $name,
                'balance' => $initialBalance
            ]);

            $walletId = $this->db->lastInsertId();

            // Se há saldo inicial, registrar transação
            if ($initialBalance > 0) {
                $this->recordTransaction(
                    $walletId,
                    'deposit',
                    $initialBalance,
                    'Saldo inicial'
                );
            }

            $this->db->commit();
            return ['success' => true, 'id' => $walletId];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erro ao criar carteira: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erro ao criar carteira'];
        }
    }

    /**
     * Atualiza uma carteira existente
     */
    public function updateWallet($id, $userId, $name) {
        try {
            $stmt = $this->db->prepare("
                UPDATE wallets
                SET name = :name, updated_at = NOW()
                WHERE id = :id
            ");
            $result = $stmt->execute([
                'id' => $id,
                'name' => $name
            ]);

            return ['success' => $result];
        } catch (PDOException $e) {
            error_log("Erro ao atualizar carteira: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erro ao atualizar carteira'];
        }
    }

    /**
     * Exclui uma carteira (apenas se não tiver transações)
     */
    public function deleteWallet($id, $userId) {
        try {
            // Verificar se há transações
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count FROM wallet_transactions
                WHERE wallet_id = :wallet_id
            ");
            $stmt->execute(['wallet_id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                return ['success' => false, 'error' => 'Não é possível excluir uma carteira com transações'];
            }

            // Excluir a carteira
            $stmt = $this->db->prepare("
                DELETE FROM wallets
                WHERE id = :id
            ");
            $result = $stmt->execute(['id' => $id]);

            return ['success' => $result];
        } catch (PDOException $e) {
            error_log("Erro ao excluir carteira: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erro ao excluir carteira'];
        }
    }

    /**
     * Adiciona saldo à carteira
     */
    public function addBalance($walletId, $userId, $amount, $description = '') {
        try {
            $this->db->beginTransaction();

            // Atualizar saldo
            $stmt = $this->db->prepare("
                UPDATE wallets
                SET balance = balance + :amount, updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                'id' => $walletId,
                'amount' => $amount
            ]);

            // Registrar transação
            $this->recordTransaction(
                $walletId,
                'deposit',
                $amount,
                $description ?: 'Adição de saldo'
            );

            $this->db->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erro ao adicionar saldo: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erro ao adicionar saldo'];
        }
    }

    /**
     * Remove saldo da carteira
     */
    public function removeBalance($walletId, $userId, $amount, $description = '') {
        try {
            // Verificar se há saldo suficiente
            $wallet = $this->getWalletById($walletId, $userId);
            if (!$wallet || $wallet['balance'] < $amount) {
                return ['success' => false, 'error' => 'Saldo insuficiente'];
            }

            $this->db->beginTransaction();

            // Atualizar saldo
            $stmt = $this->db->prepare("
                UPDATE wallets
                SET balance = balance - :amount, updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                'id' => $walletId,
                'amount' => $amount
            ]);

            // Registrar transação
            $this->recordTransaction(
                $walletId,
                'withdrawal',
                $amount,
                $description ?: 'Retirada de saldo'
            );

            $this->db->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erro ao remover saldo: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erro ao remover saldo'];
        }
    }

    /**
     * Busca transações de uma carteira com filtros e paginação
     */
    public function getTransactions($walletId, $userId, $limit = 50, $filters = [], $page = 1, $perPage = 20) {
        try {
            // Verificar se a carteira pertence ao usuário
            $wallet = $this->getWalletById($walletId, $userId);
            if (!$wallet) {
                return ['data' => [], 'pagination' => ['total' => 0, 'per_page' => $perPage, 'current_page' => 1, 'total_pages' => 0]];
            }

            // Construir WHERE clause
            $where = ["wt.wallet_id = :wallet_id"];
            $params = ['wallet_id' => $walletId];

            if (!empty($filters['type'])) {
                $where[] = "wt.type = :type";
                $params['type'] = $filters['type'];
            }

            if (!empty($filters['search'])) {
                $where[] = "(wt.description LIKE :search OR c.name LIKE :search)";
                $params['search'] = '%' . $filters['search'] . '%';
            }

            if (!empty($filters['start_date'])) {
                $where[] = "DATE(wt.created_at) >= :start_date";
                $params['start_date'] = $filters['start_date'];
            }

            if (!empty($filters['end_date'])) {
                $where[] = "DATE(wt.created_at) <= :end_date";
                $params['end_date'] = $filters['end_date'];
            }

            $whereClause = implode(" AND ", $where);

            // Contar total de transações
            $countStmt = $this->db->prepare("
                SELECT COUNT(*) as total
                FROM wallet_transactions wt
                LEFT JOIN loans l ON (
                    (wt.type IN ('loan_out', 'loan_payment')) AND
                    (wt.description LIKE CONCAT('%#', l.id, '%'))
                )
                LEFT JOIN clients c ON l.client_id = c.id
                WHERE $whereClause
            ");
            $countStmt->execute($params);
            $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Calcular offset
            $offset = ($page - 1) * $perPage;

            // Buscar transações com paginação
            $stmt = $this->db->prepare("
                SELECT
                    wt.*,
                    c.name as client_name
                FROM wallet_transactions wt
                LEFT JOIN loans l ON (
                    (wt.type IN ('loan_out', 'loan_payment')) AND
                    (wt.description LIKE CONCAT('%#', l.id, '%'))
                )
                LEFT JOIN clients c ON l.client_id = c.id
                WHERE $whereClause
                ORDER BY wt.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return [
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'pagination' => [
                    'total' => $totalRecords,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => ceil($totalRecords / $perPage)
                ]
            ];
        } catch (PDOException $e) {
            error_log("Erro ao buscar transações: " . $e->getMessage());
            return ['data' => [], 'pagination' => ['total' => 0, 'per_page' => $perPage, 'current_page' => 1, 'total_pages' => 0]];
        }
    }

    /**
     * Registra uma transação no histórico
     */
    private function recordTransaction($walletId, $type, $amount, $description) {
        $stmt = $this->db->prepare("
            INSERT INTO wallet_transactions (wallet_id, type, amount, description, created_at)
            VALUES (:wallet_id, :type, :amount, :description, NOW())
        ");
        $stmt->execute([
            'wallet_id' => $walletId,
            'type' => $type,
            'amount' => $amount,
            'description' => $description
        ]);
    }

    /**
     * Calcula o total em todas as carteiras do usuário
     */
    public function getTotalBalance($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(balance), 0) as total
                FROM wallets
                WHERE is_active = 1
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        } catch (PDOException $e) {
            error_log("Erro ao calcular total: " . $e->getMessage());
            return 0;
        }
    }
}
