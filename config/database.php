<?php
// Configuración de la base de datos para XAMPP
class Database {
    private $host = 'localhost';
    private $db_name = 'banda_marcial';
    private $username = 'root';
    private $password = '1108';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}

// Función helper para obtener conexión
function getDBConnection() {
    $database = new Database();
    return $database->getConnection();
}
?>
