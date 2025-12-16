<?php

class ClientService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAllClients($userId, $search = '', $page = 1, $perPage = 20) {
        try {
            $offset = ($page - 1) * $perPage;
            $where = ["c.is_active = 1"];
            $params = [];

            // Filtro por busca
            if (!empty($search)) {
                // Remover caracteres especiais para busca em CPF e telefone
                $searchClean = preg_replace('/[^0-9]/', '', $search);

                // Busca por nome (accent-insensitive), CPF ou telefone
                if (!empty($searchClean)) {
                    // Tem números: buscar em nome, CPF e telefone
                    $where[] = "(c.name COLLATE utf8mb4_general_ci LIKE :search OR REPLACE(REPLACE(REPLACE(c.cpf, '.', ''), '-', ''), '/', '') LIKE :search_clean OR REPLACE(REPLACE(REPLACE(REPLACE(c.phone, '(', ''), ')', ''), ' ', ''), '-', '') LIKE :search_clean)";
                    $params['search'] = '%' . $search . '%';
                    $params['search_clean'] = '%' . $searchClean . '%';
                } else {
                    // Só letras: buscar apenas no nome
                    $where[] = "c.name COLLATE utf8mb4_general_ci LIKE :search";
                    $params['search'] = '%' . $search . '%';
                }
            }

            $whereClause = implode(" AND ", $where);

            // Query para total de registros
            $countStmt = $this->db->prepare("
                SELECT COUNT(DISTINCT c.id) as total
                FROM clients c
                WHERE $whereClause
            ");
            $countStmt->execute($params);
            $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Query principal com paginação
            $stmt = $this->db->prepare("
                SELECT
                    c.*,
                    COUNT(DISTINCT l.id) as loan_count,
                    COALESCE(SUM(CASE WHEN i.status IN ('pending', 'overdue') THEN i.amount ELSE 0 END), 0) as active_debt
                FROM clients c
                LEFT JOIN loans l ON c.id = l.client_id AND l.status = 'active'
                LEFT JOIN loan_installments i ON l.id = i.loan_id
                WHERE $whereClause
                GROUP BY c.id
                ORDER BY active_debt DESC, c.created_at DESC
                LIMIT :limit OFFSET :offset
            ");

            $params['limit'] = $perPage;
            $params['offset'] = $offset;

            $stmt->execute($params);
            $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'data' => $clients,
                'pagination' => [
                    'total' => $totalRecords,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => ceil($totalRecords / $perPage)
                ]
            ];
        } catch (PDOException $e) {
            error_log("Erro ao buscar clientes: " . $e->getMessage());
            error_log("Search params: " . print_r($params, true));
            error_log("Where clause: " . $whereClause);
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

    public function getClientsStats($userId, $search = '') {
        try {
            $where = ["c.is_active = 1"];
            $params = [];

            // Filtro por busca
            if (!empty($search)) {
                // Remover caracteres especiais para busca em CPF e telefone
                $searchClean = preg_replace('/[^0-9]/', '', $search);

                if (!empty($searchClean)) {
                    // Tem números: buscar em nome, CPF e telefone
                    $where[] = "(c.name COLLATE utf8mb4_general_ci LIKE :search OR REPLACE(REPLACE(REPLACE(c.cpf, '.', ''), '-', ''), '/', '') LIKE :search_clean OR REPLACE(REPLACE(REPLACE(REPLACE(c.phone, '(', ''), ')', ''), ' ', ''), '-', '') LIKE :search_clean)";
                    $params['search'] = '%' . $search . '%';
                    $params['search_clean'] = '%' . $searchClean . '%';
                } else {
                    // Só letras: buscar apenas no nome
                    $where[] = "c.name COLLATE utf8mb4_general_ci LIKE :search";
                    $params['search'] = '%' . $search . '%';
                }
            }

            $whereClause = implode(" AND ", $where);

            $stmt = $this->db->prepare("
                SELECT
                    COUNT(DISTINCT c.id) as total_clients,
                    COUNT(DISTINCT CASE WHEN l.id IS NOT NULL THEN c.id END) as clients_with_loans,
                    COALESCE(SUM(CASE WHEN l.status = 'active' THEN l.total_amount ELSE 0 END), 0) as total_active_debt
                FROM clients c
                LEFT JOIN loans l ON c.id = l.client_id
                WHERE $whereClause
            ");

            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar estatísticas de clientes: " . $e->getMessage());
            return [
                'total_clients' => 0,
                'clients_with_loans' => 0,
                'total_active_debt' => 0
            ];
        }
    }

    public function getClientById($id, $userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM clients
                WHERE id = :id
            ");
            $stmt->execute(['id' => $id]);
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
                $stmt = $this->db->prepare("SELECT id FROM clients WHERE cpf = :cpf");
                $stmt->execute(['cpf' => $cpf]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'error' => 'CPF já cadastrado'];
                }
            }

            $stmt = $this->db->prepare("
                INSERT INTO clients (name, cpf, phone, address, created_at)
                VALUES (:name, :cpf, :phone, :address, NOW())
            ");
            $stmt->execute([
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
                    WHERE cpf = :cpf AND id != :id
                ");
                $stmt->execute(['cpf' => $cpf, 'id' => $id]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'error' => 'CPF já cadastrado para outro cliente'];
                }
            }

            $stmt = $this->db->prepare("
                UPDATE clients
                SET name = :name, cpf = :cpf, phone = :phone, address = :address, updated_at = NOW()
                WHERE id = :id
            ");
            $result = $stmt->execute([
                'id' => $id,
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
                WHERE id = :id
            ");
            $result = $stmt->execute(['id' => $id]);

            return ['success' => $result];
        } catch (PDOException $e) {
            error_log("Erro ao excluir cliente: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erro ao excluir cliente'];
        }
    }

    public function getClientLoans($clientId, $userId) {
        try {
            // Verificar se o cliente existe
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
                LEFT JOIN loan_installments i ON l.id = i.loan_id
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
                WHERE c.is_active = 1
                  AND (c.name LIKE :query OR c.cpf LIKE :query OR c.phone LIKE :query)
                GROUP BY c.id
                ORDER BY c.name ASC
                LIMIT 50
            ");
            $stmt->execute(['query' => '%' . $query . '%']);
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
