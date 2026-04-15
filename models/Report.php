<?php

/**
 * Modèle Report - Préparation des données pour PDF
 */
class Report
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Récupérer les données pour le PV de délibération d'une classe
    public function getPVData($id_classe)
    {
        $query = "SELECT e.nom, e.prenom, 
                  (SELECT AVG(eff.note) FROM effectue eff WHERE eff.id_Etudiant = e.id_Etudiant) as moyenne_gen
                  FROM etudiant e
                  WHERE e.Id_Classe = :id_c
                  ORDER BY moyenne_gen DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute(['id_c' => $id_classe]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
