<?php
/**
 * Fichier de configuration de la connexion à la base de données SIGES.
 * Basé sur le dump SQL fourni le 15 avril 2026.
 */
class Database {

    private $host = "localhost";
    private $db_name = "siges";
    private $username = "root";
    private $password = "";

    public $conn;

    /**
     * Méthode pour obtenir la connexion PDO
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;

        try {

            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );

            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $this->conn->exec("set names utf8mb4");

        } catch(PDOException $exception) {

            die("Erreur de connexion à la base de données SIGES : " . $exception->getMessage());
        }

        return $this->conn;
    }
}
?>