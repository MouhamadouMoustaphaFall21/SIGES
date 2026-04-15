<?php

/**
 * Modèle Schedule - Gestion de la table 'Creneau'
 */
class Schedule
{
    private $conn;
    private $table = "Creneau";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Récupérer l'emploi du temps d'une classe (pour l'élève)
    public function getByClasse($id_classe)
    {
        $query = "SELECT c.*, p.nom as prof_nom, m.libelle as matiere_nom 
                  FROM " . $this->table . " c
                  JOIN Professeur p ON c.Id_Professeur = p.Id_Professeur
                  JOIN Matiere m ON c.Id_Matiere = m.Id_Matiere
                  WHERE c.Id_Classe = :id_c
                  ORDER BY FIELD(c.jour, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'), c.heure_debut";

        $stmt = $this->conn->prepare($query);
        $stmt->execute(['id_c' => $id_classe]);
        return $stmt;
    }

    // Ajouter un créneau (pour l'Admin)
    public function create($data)
    {
        $query = "INSERT INTO " . $this->table . " 
                  (jour, heure_debut, heure_fin, Id_Classe, Id_Professeur, Id_Matiere) 
                  VALUES (:jour, :h_debut, :h_fin, :id_c, :id_p, :id_m)";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute($data);
    }

    /**
     * Récupère l'emploi du temps complet avec les noms des profs et matières
     */
    public function getAllSchedules() {
        $query = "SELECT c.*, cl.libelle as classe_nom, cl.niveau, p.nom as prof_nom, m.libelle as matiere_nom 
                  FROM creneau c
                  JOIN classe cl ON c.Id_Classe = cl.Id_Classe
                  JOIN professeur p ON c.Id_Professeur = p.Id_Professeur
                  JOIN matiere m ON c.Id_Matiere = m.Id_Matiere
                  ORDER BY FIELD(c.jour, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'), c.heure_debut";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
