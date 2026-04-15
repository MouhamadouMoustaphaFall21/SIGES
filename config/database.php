<?php
/**
 * Fichier de configuration de la connexion à la base de données SIGES.
 * Basé sur le dump SQL fourni le 15 avril 2026.
 */
class Database {
    // Paramètres de connexion
    private $host = "localhost";
    private $db_name = "siges"; // Nom exact de la base dans ton script
    private $username = "root";
    private $password = ""; // À modifier selon ton environnement local (ex: 'root' sur Mac)
    public $conn;

    /**
     * Méthode pour obtenir la connexion PDO
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;

        try {
            // Création de la connexion avec le jeu de caractères utf8mb4 défini dans ton SQL
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                $this->username, 
                $this->password
            );
            
            // Activation du mode d'erreur exception pour faciliter le debug
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Forcer l'encodage en UTF-8 pour correspondre au COLLATE utf8mb4_general_ci du dump
            $this->conn->exec("set names utf8mb4");
            
        } catch(PDOException $exception) {
            // En cas d'erreur, on affiche un message clair pour le debug de connectivité
            die("Erreur de connexion à la base de données SIGES : " . $exception->getMessage());
        }

        return $this->conn;
    }
}
?>