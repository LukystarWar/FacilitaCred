<?php
/**
 * Database Class
 * Gerenciamento de conexão com banco de dados usando PDO
 */

class Database
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        try {
            $this->connection = new PDO(DB_DSN, DB_USER, DB_PASS, DB_OPTIONS);
        } catch (PDOException $e) {
            die("Erro de conexão com o banco de dados: " . $e->getMessage());
        }
    }

    /**
     * Singleton: retorna a instância única da conexão
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retorna a conexão PDO
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Executa uma query preparada e retorna todos os resultados
     */
    public function query($sql, $params = [])
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Executa uma query preparada e retorna um único resultado
     */
    public function queryOne($sql, $params = [])
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    /**
     * Executa uma query de inserção/atualização/deleção
     * Retorna o número de linhas afetadas
     */
    public function execute($sql, $params = [])
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Retorna o último ID inserido
     */
    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Inicia uma transação
     */
    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Confirma uma transação
     */
    public function commit()
    {
        return $this->connection->commit();
    }

    /**
     * Reverte uma transação
     */
    public function rollback()
    {
        return $this->connection->rollback();
    }

    /**
     * Previne clonagem do objeto
     */
    private function __clone() {}

    /**
     * Previne desserialização do objeto
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
