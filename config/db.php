<?php
// config/db.php
class Database {
    private static $instance = null;
    private $connection;
    
    private $host = 'localhost';
    private $username = 'root';     // Change this to your MySQL username
    private $password = 'Narutostorm4';         // Change this to your MySQL password
    private $database = 'gymnastics_academy';
    
    private function __construct() {
        $this->connection = new mysqli($this->host, $this->username, $this->password, $this->database);
        
        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
        
        $this->connection->set_charset("utf8mb4");
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function executeQuery($sql, $types = "", $params = []) {
        $stmt = $this->connection->prepare($sql);
        if ($stmt === false) {
            error_log("Prepare failed: " . $this->connection->error);
            return false;
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt;
    }
    
    public function escapeString($string) {
        return $this->connection->real_escape_string($string);
    }
}
?>