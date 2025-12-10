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
                    (SELECT COUNT(*) FROM transactions WHERE wallet_id = w.id) as transaction_count
                FROM wallets w
                WHERE w.user_id = :user_id
                ORDER BY w.created_at DESC
            ");
            $stmt->execute(['user_id' => $userId]);
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
                WHERE id = :id AND user_id = :user_id
            ");
            $stmt->execute(['id' => $id, 'user_id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar carteira: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Cria uma nova carteira
     */
    public function createWallet($userId, $name, $initialBalance = 0, $description = '') {
        try {
            $this->db->beginTransaction();

            // Inserir a carteira
            $stmt = $this->db->prepare("
                INSERT INTO wallets (user_id, name, balance, description, created_at)
                VALUES (:user_id, :name, :balance, :description, NOW())
            ");
            $stmt->execute([
                'user_id' => $userId,
                'name' => $name,
                'balance' => $initialBalance,
                'description' => $description
            ]);

            $walletId = $this->db->lastInsertId();

            // Se há saldo inicial, registrar transação
            if ($initialBalance > 0) {
                $this->recordTransaction(
                    $walletId,
                    'credit',
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
    public function updateWallet($id, $userId, $name, $description = '') {
        try {
            $stmt = $this->db->prepare("
                UPDATE wallets
                SET name = :name, description = :description, updated_at = NOW()
                WHERE id = :id AND user_id = :user_id
            ");
            $result = $stmt->execute([
                'id' => $id,
                'user_id' => $userId,
                'name' => $name,
                'description' => $description
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
                SELECT COUNT(*) as count FROM transactions
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
                WHERE id = :id AND user_id = :user_id
            ");
            $result = $stmt->execute(['id' => $id, 'user_id' => $userId]);

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
                WHERE id = :id AND user_id = :user_id
            ");
            $stmt->execute([
                'id' => $walletId,
                'user_id' => $userId,
                'amount' => $amount
            ]);

            // Registrar transação
            $this->recordTransaction(
                $walletId,
                'credit',
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
                WHERE id = :id AND user_id = :user_id
            ");
            $stmt->execute([
                'id' => $walletId,
                'user_id' => $userId,
                'amount' => $amount
            ]);

            // Registrar transação
            $this->recordTransaction(
                $walletId,
                'debit',
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
     * Busca transações de uma carteira
     */
    public function getTransactions($walletId, $userId, $limit = 50) {
        try {
            // Verificar se a carteira pertence ao usuário
            $wallet = $this->getWalletById($walletId, $userId);
            if (!$wallet) {
                return [];
            }

            $stmt = $this->db->prepare("
                SELECT * FROM transactions
                WHERE wallet_id = :wallet_id
                ORDER BY created_at DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':wallet_id', $walletId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar transações: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Registra uma transação no histórico
     */
    private function recordTransaction($walletId, $type, $amount, $description) {
        $stmt = $this->db->prepare("
            INSERT INTO transactions (wallet_id, type, amount, description, created_at)
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
                WHERE user_id = :user_id
            ");
            $stmt->execute(['user_id' => $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        } catch (PDOException $e) {
            error_log("Erro ao calcular total: " . $e->getMessage());
            return 0;
        }
    }
}
