<?php
/**
 * Session Class
 * Gerenciamento de sessões do usuário
 */

class Session
{
    /**
     * Inicia a sessão
     */
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();

            // Regenera o ID da sessão periodicamente para segurança
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } elseif (time() - $_SESSION['created'] > SESSION_LIFETIME) {
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }

    /**
     * Define um valor na sessão
     */
    public static function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Retorna um valor da sessão
     */
    public static function get($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Verifica se uma chave existe na sessão
     */
    public static function has($key)
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove um valor da sessão
     */
    public static function remove($key)
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Destrói toda a sessão
     */
    public static function destroy()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }

    /**
     * Verifica se o usuário está autenticado
     */
    public static function isAuthenticated()
    {
        return self::has('user_id') && self::get('user_id') !== null;
    }

    /**
     * Retorna o ID do usuário logado
     */
    public static function getUserId()
    {
        return self::get('user_id');
    }

    /**
     * Retorna o username do usuário logado
     */
    public static function getUsername()
    {
        return self::get('username');
    }

    /**
     * Define os dados do usuário na sessão
     */
    public static function setUser($userId, $username)
    {
        self::set('user_id', $userId);
        self::set('username', $username);
    }

    /**
     * Remove os dados do usuário da sessão
     */
    public static function clearUser()
    {
        self::remove('user_id');
        self::remove('username');
    }

    /**
     * Define uma mensagem flash (exibida uma única vez)
     */
    public static function flash($key, $message)
    {
        self::set('flash_' . $key, $message);
    }

    /**
     * Alias para flash() - para compatibilidade
     */
    public static function setFlash($key, $message)
    {
        self::flash($key, $message);
    }

    /**
     * Retorna e remove uma mensagem flash
     */
    public static function getFlash($key)
    {
        $message = self::get('flash_' . $key);
        self::remove('flash_' . $key);
        return $message;
    }

    /**
     * Verifica se existe uma mensagem flash
     */
    public static function hasFlash($key)
    {
        return self::has('flash_' . $key);
    }

    /**
     * Requer autenticação - redireciona se não autenticado
     */
    public static function requireAuth()
    {
        if (!self::isAuthenticated()) {
            self::flash('error', 'Você precisa estar autenticado para acessar esta página.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }
}
