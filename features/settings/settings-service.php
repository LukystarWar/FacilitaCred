<?php
/**
 * Settings Service
 * Gerencia configurações do sistema com cache em memória
 */

class SettingsService {
    private $db;
    private static $cache = [];

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtém valor de uma configuração
     * @param string $key Chave da configuração
     * @param mixed $default Valor padrão se não encontrado
     * @return mixed Valor da configuração
     */
    public function getSetting($key, $default = null) {
        // Verificar cache primeiro
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $stmt = $this->db->prepare("
            SELECT setting_value, setting_type
            FROM system_settings
            WHERE setting_key = ?
        ");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return $default;
        }

        // Converter valor de acordo com o tipo
        $value = $this->castValue($result['setting_value'], $result['setting_type']);

        // Armazenar em cache
        self::$cache[$key] = $value;

        return $value;
    }

    /**
     * Obtém todas as configurações de uma categoria
     * @param string $category Categoria (interest, penalty, etc)
     * @return array Array associativo [key => value]
     */
    public function getSettingsByCategory($category) {
        $stmt = $this->db->prepare("
            SELECT setting_key, setting_value, setting_type, description
            FROM system_settings
            WHERE category = ?
            ORDER BY setting_key
        ");
        $stmt->execute([$category]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $settings = [];
        foreach ($results as $row) {
            $settings[$row['setting_key']] = [
                'value' => $this->castValue($row['setting_value'], $row['setting_type']),
                'description' => $row['description'],
                'type' => $row['setting_type']
            ];
        }

        return $settings;
    }

    /**
     * Atualiza uma configuração
     * @param string $key Chave da configuração
     * @param mixed $value Novo valor
     * @param int $userId ID do usuário que está atualizando
     * @return bool Sucesso da operação
     */
    public function updateSetting($key, $value, $userId) {
        $stmt = $this->db->prepare("
            UPDATE system_settings
            SET setting_value = ?, updated_by = ?, updated_at = NOW()
            WHERE setting_key = ?
        ");

        $result = $stmt->execute([$value, $userId, $key]);

        // Limpar cache
        if ($result) {
            unset(self::$cache[$key]);
        }

        return $result;
    }

    /**
     * Atualiza múltiplas configurações de uma vez
     * @param array $settings Array [key => value]
     * @param int $userId ID do usuário que está atualizando
     * @return bool Sucesso da operação
     */
    public function updateMultiple($settings, $userId) {
        try {
            $this->db->beginTransaction();

            foreach ($settings as $key => $value) {
                $this->updateSetting($key, $value, $userId);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Converte valor de acordo com o tipo
     * @param mixed $value Valor bruto
     * @param string $type Tipo da configuração
     * @return mixed Valor convertido
     */
    private function castValue($value, $type) {
        switch ($type) {
            case 'number':
                return (float) $value;
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * Limpa todo o cache
     */
    public static function clearCache() {
        self::$cache = [];
    }

    /**
     * Obtém histórico de alterações de uma configuração
     * @param string $key Chave da configuração
     * @param int $limit Número de registros
     * @return array Histórico de alterações
     */
    public function getSettingHistory($key, $limit = 10) {
        // TODO: Implementar tabela de auditoria se necessário
        return [];
    }
}
