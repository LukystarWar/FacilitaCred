<?php

class ClientService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAllClients($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    c.*,
                    COUNT(DISTINCT l.id) as loan_count,
                    COALESCE(SUM(CASE WHEN l.status = 'active' THEN l.total_amount ELSE 0 END), 0) as active_debt
                FROM clients c
                LEFT JOIN loans l ON c.id = l.client_id
                WHERE c.user_id = :user_id
                GROUP BY c.id
                ORDER BY c.created_at DESC
            ");
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar clientes: " . $e->getMessage());
            return [];
        }
    }

    public function getClientById($id, $userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM clients
                WHERE id = :id AND user_id = :user_id
            ");
            $stmt->execute(['id' => $id, 'user_id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar cliente: " . $e->getMessage());
            return null;
        }
    }

    public function createClient($userId, $name, $cpf = null, $phone = null, $address = null) {
        try {
            // Validar CPF se fornecido
            if ($cpf && !$this->validateCPF($cpf)) {
                return ['success' => false, 'error' => 'CPF inválido'];
            }

            // Verificar CPF duplicado
            if ($cpf) {
                $stmt = $this->db->prepare("SELECT id FROM clients WHERE cpf = :cpf AND user_id = :user_id");
                $stmt->execute(['cpf' => $cpf, 'user_id' => $userId]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'error' => 'CPF já cadastrado'];
                }
            }

            $stmt = $this->db->prepare("
                INSERT INTO clients (user_id, name, cpf, phone, address, created_at)
                VALUES (:user_id, :name, :cpf, :phone, :address, NOW())
            ");
            $stmt->execute([
                'user_id' => $userId,
                'name' => $name,
                'cpf' => $cpf,
                'phone' => $phone,
                'address' => $address
            ]);

            return ['success' => true, 'id' => $this->db->lastInsertId()];
        } catch (PDOException $e) {
            error_log("Erro ao criar cliente: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erro ao criar cliente'];
        }
    }

    public function updateClient($id, $userId, $name, $cpf = null, $phone = null, $address = null) {
        try {
            // Validar CPF se fornecido
            if ($cpf && !$this->validateCPF($cpf)) {
                return ['success' => false, 'error' => 'CPF inválido'];
            }

            // Verificar CPF duplicado (exceto o próprio cliente)
            if ($cpf) {
                $stmt = $this->db->prepare("
                    SELECT id FROM clients
                    WHERE cpf = :cpf AND user_id = :user_id AND id != :id
                ");
                $stmt->execute(['cpf' => $cpf, 'user_id' => $userId, 'id' => $id]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'error' => 'CPF já cadastrado para outro cliente'];
                }
            }

            $stmt = $this->db->prepare("
                UPDATE clients
                SET name = :name, cpf = :cpf, phone = :phone, address = :address, updated_at = NOW()
                WHERE id = :id AND user_id = :user_id
            ");
            $result = $stmt->execute([
                'id' => $id,
                'user_id' => $userId,
                'name' => $name,
                'cpf' => $cpf,
                'phone' => $phone,
                'address' => $address
            ]);

            return ['success' => $result];
        } catch (PDOException $e) {
            error_log("Erro ao atualizar cliente: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erro ao atualizar cliente'];
        }
    }

    public function deleteClient($id, $userId) {
        try {
            // Verificar se há empréstimos
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count FROM loans
                WHERE client_id = :client_id
            ");
            $stmt->execute(['client_id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                return ['success' => false, 'error' => 'Não é possível excluir cliente com empréstimos'];
            }

            $stmt = $this->db->prepare("
                DELETE FROM clients
                WHERE id = :id AND user_id = :user_id
            ");
            $result = $stmt->execute(['id' => $id, 'user_id' => $userId]);

            return ['success' => $result];
        } catch (PDOException $e) {
            error_log("Erro ao excluir cliente: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erro ao excluir cliente'];
        }
    }

    public function getClientLoans($clientId, $userId) {
        try {
            // Verificar se o cliente pertence ao usuário
            $client = $this->getClientById($clientId, $userId);
            if (!$client) {
                return [];
            }

            $stmt = $this->db->prepare("
                SELECT
                    l.*,
                    w.name as wallet_name,
                    COUNT(i.id) as total_installments,
                    COUNT(CASE WHEN i.status = 'paid' THEN 1 END) as paid_installments,
                    COUNT(CASE WHEN i.status = 'overdue' THEN 1 END) as overdue_installments
                FROM loans l
                INNER JOIN wallets w ON l.wallet_id = w.id
                LEFT JOIN installments i ON l.id = i.loan_id
                WHERE l.client_id = :client_id
                GROUP BY l.id
                ORDER BY l.created_at DESC
            ");
            $stmt->execute(['client_id' => $clientId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar empréstimos: " . $e->getMessage());
            return [];
        }
    }

    public function searchClients($userId, $query) {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    c.*,
                    COUNT(DISTINCT l.id) as loan_count
                FROM clients c
                LEFT JOIN loans l ON c.id = l.client_id
                WHERE c.user_id = :user_id
                  AND (c.name LIKE :query OR c.cpf LIKE :query OR c.phone LIKE :query)
                GROUP BY c.id
                ORDER BY c.name ASC
                LIMIT 50
            ");
            $stmt->execute([
                'user_id' => $userId,
                'query' => '%' . $query . '%'
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar clientes: " . $e->getMessage());
            return [];
        }
    }

    private function validateCPF($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($cpf) != 11) {
            return false;
        }

        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }

    public function formatCPF($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) == 11) {
            return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
        }
        return $cpf;
    }

    public function formatPhone($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) == 11) {
            return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 5) . '-' . substr($phone, 7, 4);
        } elseif (strlen($phone) == 10) {
            return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 4) . '-' . substr($phone, 6, 4);
        }
        return $phone;
    }
}
