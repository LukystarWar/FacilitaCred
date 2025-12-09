<?php
/**
 * Auth Service
 * Serviço de autenticação
 */

class AuthService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Tenta fazer login com username e senha
     *
     * @return array ['success' => bool, 'message' => string, 'user' => array|null]
     */
    public function login($username, $password)
    {
        // Validações básicas
        if (empty($username) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Usuário e senha são obrigatórios.',
                'user' => null
            ];
        }

        // Busca o usuário no banco
        $sql = "SELECT id, username, password_hash FROM users WHERE username = :username LIMIT 1";
        $user = $this->db->queryOne($sql, ['username' => $username]);

        // Verifica se o usuário existe
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Usuário ou senha incorretos.',
                'user' => null
            ];
        }

        // Verifica a senha
        if (!password_verify($password, $user['password_hash'])) {
            return [
                'success' => false,
                'message' => 'Usuário ou senha incorretos.',
                'user' => null
            ];
        }

        // Login bem-sucedido
        return [
            'success' => true,
            'message' => 'Login realizado com sucesso!',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username']
            ]
        ];
    }

    /**
     * Cria um novo usuário (para uso administrativo)
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function createUser($username, $password)
    {
        // Validações
        if (empty($username) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Usuário e senha são obrigatórios.'
            ];
        }

        if (strlen($password) < 6) {
            return [
                'success' => false,
                'message' => 'A senha deve ter no mínimo 6 caracteres.'
            ];
        }

        // Verifica se o usuário já existe
        $existing = $this->db->queryOne(
            "SELECT id FROM users WHERE username = :username",
            ['username' => $username]
        );

        if ($existing) {
            return [
                'success' => false,
                'message' => 'Este nome de usuário já está em uso.'
            ];
        }

        // Hash da senha
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Insere o usuário
        $sql = "INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)";
        $this->db->execute($sql, [
            'username' => $username,
            'password_hash' => $passwordHash
        ]);

        return [
            'success' => true,
            'message' => 'Usuário criado com sucesso!'
        ];
    }

    /**
     * Altera a senha de um usuário
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function changePassword($userId, $currentPassword, $newPassword)
    {
        // Validações
        if (empty($currentPassword) || empty($newPassword)) {
            return [
                'success' => false,
                'message' => 'Senhas são obrigatórias.'
            ];
        }

        if (strlen($newPassword) < 6) {
            return [
                'success' => false,
                'message' => 'A nova senha deve ter no mínimo 6 caracteres.'
            ];
        }

        // Busca o usuário
        $user = $this->db->queryOne(
            "SELECT password_hash FROM users WHERE id = :id",
            ['id' => $userId]
        );

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Usuário não encontrado.'
            ];
        }

        // Verifica a senha atual
        if (!password_verify($currentPassword, $user['password_hash'])) {
            return [
                'success' => false,
                'message' => 'Senha atual incorreta.'
            ];
        }

        // Hash da nova senha
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // Atualiza a senha
        $sql = "UPDATE users SET password_hash = :password_hash WHERE id = :id";
        $this->db->execute($sql, [
            'password_hash' => $newPasswordHash,
            'id' => $userId
        ]);

        return [
            'success' => true,
            'message' => 'Senha alterada com sucesso!'
        ];
    }
}
