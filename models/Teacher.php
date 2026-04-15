<?php
/**
 * Modèle Teacher - Gestion des données enseignants
 * Basé sur les tables 'professeur', 'matiere' et 'affecter'
 */
class Teacher {
    private $conn;
    private $table_name = "professeur"; // Nom exact dans ton SQL

    // Propriétés correspondant aux colonnes de ton SQL
    public $Id_Professeur;
    public $nom;
    public $prenom;
    public $Id_Matiere;
    public $login;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Récupérer le profil du professeur et sa matière principale
     */
    public function getProfileByLogin($login) {
        $query = "SELECT p.*, m.libelle as nom_matiere, m.coefficient 
                  FROM " . $this->table_name . " p
                  JOIN matiere m ON p.Id_Matiere = m.Id_Matiere
                  WHERE p.login = :login";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':login', $login);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lister les classes affectées à ce professeur (via la table 'affecter')
     * Très important pour que le prof ne voie que ses propres élèves
     */
    public function getAssignedClasses($id_prof) {
        $query = "SELECT c.* FROM classe c
                  JOIN affecter a ON c.Id_Classe = a.Id_Classe
                  WHERE a.Id_Professeur = :id_prof";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_prof', $id_prof);
        $stmt->execute();

        return $stmt;
    }

    /**
     * Récupérer tous les professeurs (pour l'admin)
     */
    public function readAll() {
        $query = "SELECT p.*, m.libelle as matiere_nom 
                  FROM " . $this->table_name . " p 
                  JOIN matiere m ON p.Id_Matiere = m.Id_Matiere 
                  ORDER BY p.nom ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Récupérer les professeurs affectés à une classe spécifique
     */
    public function getProfessorsByClasse($id_classe) {
        $query = "SELECT p.Id_Professeur, p.nom, p.prenom, m.libelle as matiere_nom
                  FROM professeur p
                  JOIN affecter a ON p.Id_Professeur = a.Id_Professeur
                  JOIN matiere m ON p.Id_Matiere = m.Id_Matiere
                  WHERE a.Id_Classe = :id_classe
                  ORDER BY p.nom ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_classe', $id_classe);
        $stmt->execute();

        return $stmt;
    }
}
?>