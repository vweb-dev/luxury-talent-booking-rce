<?php

/**
 * Database Connection Singleton Class
 * Provides PDO database connection with error handling
 */
class DB {
    private static $instance = null;
    private $connection = null;
    private $config = null;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->loadConfig();
        $this->connect();
    }

    /**
     * Get singleton instance
     * @return DB
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load database configuration
     * @throws Exception
     */
    private function loadConfig() {
        $configPath = __DIR__ . '/../config/database.php';
        
        if (!file_exists($configPath)) {
            throw new Exception('Database configuration file not found. Please run setup first.');
        }

        $this->config = require $configPath;

        // Validate required configuration
        $required = ['host', 'database', 'username', 'password'];
        foreach ($required as $key) {
            if (!isset($this->config[$key])) {
                throw new Exception("Database configuration missing: {$key}");
            }
        }
    }

    /**
     * Establish database connection
     * @throws Exception
     */
    private function connect() {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $this->config['host'],
                $this->config['port'] ?? 3306,
                $this->config['database'],
                $this->config['charset'] ?? 'utf8mb4'
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . ($this->config['charset'] ?? 'utf8mb4')
            ];

            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $options
            );

            // Set timezone if specified
            if (isset($this->config['timezone'])) {
                $this->connection->exec("SET time_zone = '{$this->config['timezone']}'");
            }

        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception('Database connection failed. Please check your configuration.');
        }
    }

    /**
     * Get PDO connection
     * @return PDO
     */
    public function getConnection() {
        // Check if connection is still alive
        if ($this->connection === null) {
            $this->connect();
        }

        try {
            $this->connection->query('SELECT 1');
        } catch (PDOException $e) {
            // Reconnect if connection is lost
            $this->connect();
        }

        return $this->connection;
    }

    /**
     * Execute a query and return results
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception('Database query failed');
        }
    }

    /**
     * Execute a query and return single row
     * @param string $sql
     * @param array $params
     * @return array|null
     */
    public function queryOne($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception('Database query failed');
        }
    }

    /**
     * Execute an insert/update/delete query
     * @param string $sql
     * @param array $params
     * @return int Number of affected rows
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Execute failed: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception('Database execute failed');
        }
    }

    /**
     * Get last insert ID
     * @return string
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        $this->connection->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        $this->connection->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        $this->connection->rollBack();
    }

    /**
     * Check if currently in transaction
     * @return bool
     */
    public function inTransaction() {
        return $this->connection->inTransaction();
    }

    /**
     * Escape string for LIKE queries
     * @param string $string
     * @return string
     */
    public function escapeLike($string) {
        return str_replace(['%', '_'], ['\%', '\_'], $string);
    }

    /**
     * Test database connection
     * @return bool
     */
    public function testConnection() {
        try {
            $this->connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get database configuration (without sensitive data)
     * @return array
     */
    public function getConfig() {
        $config = $this->config;
        unset($config['password']); // Remove password for security
        return $config;
    }

    /**
     * Import SQL file
     * @param string $filePath
     * @return bool
     * @throws Exception
     */
    public function importSqlFile($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("SQL file not found: {$filePath}");
        }

        $sql = file_get_contents($filePath);
        if ($sql === false) {
            throw new Exception("Failed to read SQL file: {$filePath}");
        }

        try {
            // Split SQL into individual statements
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function($stmt) {
                    return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
                }
            );

            $this->beginTransaction();

            foreach ($statements as $statement) {
                if (!empty(trim($statement))) {
                    $this->connection->exec($statement);
                }
            }

            $this->commit();
            return true;

        } catch (PDOException $e) {
            if ($this->inTransaction()) {
                $this->rollback();
            }
            error_log("SQL import failed: " . $e->getMessage());
            throw new Exception("Failed to import SQL file: " . $e->getMessage());
        }
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
?>
