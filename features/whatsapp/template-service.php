<?php

class WhatsAppTemplateService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAllTemplates($filters = []) {
        try {
            $where = ["1=1"];
            $params = [];

            if (!empty($filters['category'])) {
                $where[] = "category = :category";
                $params['category'] = $filters['category'];
            }

            if (isset($filters['is_active']) && $filters['is_active'] !== '') {
                $where[] = "is_active = :is_active";
                $params['is_active'] = $filters['is_active'];
            }

            $whereClause = implode(" AND ", $where);

            $stmt = $this->db->prepare("
                SELECT * FROM whatsapp_templates
                WHERE $whereClause
                ORDER BY category, name
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar templates: " . $e->getMessage());
            return [];
        }
    }

    public function getTemplateById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM whatsapp_templates WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar template: " . $e->getMessage());
            return null;
        }
    }

    public function createTemplate($name, $description, $message, $category, $isActive = 1) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO whatsapp_templates (name, description, message, category, is_active)
                VALUES (:name, :description, :message, :category, :is_active)
            ");
            return $stmt->execute([
                'name' => $name,
                'description' => $description,
                'message' => $message,
                'category' => $category,
                'is_active' => $isActive
            ]);
        } catch (PDOException $e) {
            error_log("Erro ao criar template: " . $e->getMessage());
            return false;
        }
    }

    public function updateTemplate($id, $name, $description, $message, $category, $isActive) {
        try {
            $stmt = $this->db->prepare("
                UPDATE whatsapp_templates
                SET name = :name,
                    description = :description,
                    message = :message,
                    category = :category,
                    is_active = :is_active,
                    updated_at = NOW()
                WHERE id = :id
            ");
            return $stmt->execute([
                'id' => $id,
                'name' => $name,
                'description' => $description,
                'message' => $message,
                'category' => $category,
                'is_active' => $isActive
            ]);
        } catch (PDOException $e) {
            error_log("Erro ao atualizar template: " . $e->getMessage());
            return false;
        }
    }

    public function deleteTemplate($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM whatsapp_templates WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Erro ao deletar template: " . $e->getMessage());
            return false;
        }
    }

    public function replaceVariables($message, $data) {
        $replacements = [
            '{cliente}' => $data['cliente'] ?? '',
            '{numero_parcela}' => $data['numero_parcela'] ?? '',
            '{total_parcelas}' => $data['total_parcelas'] ?? '',
            '{valor}' => isset($data['valor']) ? number_format($data['valor'], 2, ',', '.') : '',
            '{vencimento}' => $data['vencimento'] ?? '',
            '{data_pagamento}' => $data['data_pagamento'] ?? '',
            '{total_pago}' => isset($data['total_pago']) ? number_format($data['total_pago'], 2, ',', '.') : ''
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }
}
