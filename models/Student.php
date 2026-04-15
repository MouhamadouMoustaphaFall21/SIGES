<?php
/**
 * Modèle Student - Gestion des données élèves
 * Basé sur la table 'etudiant' et ses relations avec 'classe'
 */
class Student {
    private $conn;
    private $table_name = "etudiant"; // Nom exact dans ton SQL

    // Propriétés correspondant aux colonnes de ton SQL
    public $id_Etudiant;
    public $nom;
    public $prenom;
    public $Id_Classe;
    public $login;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Récupérer les informations d'un étudiant par son login (pour le dashboard)
     */
    public function getProfileByLogin($login) {
        $query = "SELECT e.*, c.libelle as nom_classe, c.niveau 
                  FROM " . $this->table_name . " e
                  JOIN classe c ON e.Id_Classe = c.Id_Classe
                  WHERE e.login = :login";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':login', $login);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lister les étudiants d'une classe spécifique (pour le prof ou l'admin)
     */
    public function getByClasse($id_classe) {
        $query = "SELECT id_Etudiant, nom, prenom, login 
                  FROM " . $this->table_name . " 
                  WHERE Id_Classe = :id_classe 
                  ORDER BY nom ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_classe', $id_classe);
        $stmt->execute();

        return $stmt;
    }

    /**
     * Créer un nouvel étudiant (pour l'admin)
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (nom, prenom, Id_Classe, login) 
                  VALUES (:nom, :prenom, :id_classe, :login)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':nom', $this->nom);
        $stmt->bindParam(':prenom', $this->prenom);
        $stmt->bindParam(':id_classe', $this->Id_Classe);
        $stmt->bindParam(':login', $this->login);

        return $stmt->execute();
    }
}
?>