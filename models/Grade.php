<?php
/**
 * Modèle Grade - Gestion des notes et calculs des moyennes
 * Basé sur les tables 'effectue', 'evaluation' et 'matiere'
 */
class Grade {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Récupérer les notes détaillées d'un étudiant
     * Jointure : effectue -> evaluation -> matiere
     */
    public function getStudentGrades($id_etudiant) {
        $query = "SELECT ev.Id_Evaluation, m.libelle as matiere, m.coefficient, eff.note, ev.semestre
                  FROM effectue eff
                  JOIN evaluation ev ON eff.Id_Evaluation = ev.Id_Evaluation
                  JOIN matiere m ON ev.Id_Matiere = m.Id_Matiere
                  WHERE eff.id_Etudiant = :id
                  ORDER BY ev.semestre ASC, m.libelle ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_etudiant);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calcul de la moyenne générale pondérée d'un étudiant
     * Formule : SOMME(note * coefficient) / SOMME(coefficient)
     */
    public function calculateAverage($id_etudiant) {
        $query = "SELECT SUM(eff.note * m.coefficient) as total_points, 
                         SUM(m.coefficient) as total_coeffs
                  FROM effectue eff
                  JOIN evaluation ev ON eff.Id_Evaluation = ev.Id_Evaluation
                  JOIN matiere m ON ev.Id_Matiere = m.Id_Matiere
                  WHERE eff.id_Etudiant = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_etudiant);
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($res['total_coeffs'] > 0) {
            return round($res['total_points'] / $res['total_coeffs'], 2);
        }
        return 0;
    }

    /**
     * Saisir ou mettre à jour une note (pour le professeur)
     */
    public function saveNote($id_etudiant, $id_evaluation, $note) {
        // ON DUPLICATE KEY UPDATE permet de modifier la note si elle existe déjà
        $query = "INSERT INTO effectue (id_Etudiant, Id_Evaluation, note) 
                  VALUES (:id_e, :id_v, :note)
                  ON DUPLICATE KEY UPDATE note = :note";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_e', $id_etudiant);
        $stmt->bindParam(':id_v', $id_evaluation);
        $stmt->bindParam(':note', $note);

        return $stmt->execute();
    }

    /**
     * Récupérer le classement des étudiants d'une classe
     */
    public function getRankingByClasse($id_classe) {
        // Cette requête calcule la moyenne de chaque étudiant de la classe et les trie
        $query = "SELECT e.id_Etudiant, e.nom, e.prenom,
                  (SELECT SUM(eff.note * m.coefficient) / SUM(m.coefficient)
                   FROM effectue eff
                   JOIN evaluation ev ON eff.Id_Evaluation = ev.Id_Evaluation
                   JOIN matiere m ON ev.Id_Matiere = m.Id_Matiere
                   WHERE eff.id_Etudiant = e.id_Etudiant) as moyenne_gen
                  FROM etudiant e
                  WHERE e.Id_Classe = :id_classe
                  ORDER BY moyenne_gen DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_classe', $id_classe);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // À ajouter dans la classe Grade dans /models/Grade.php

    /**
     * Créer une réclamation pour une note spécifique
     */
    public function createReclamation($id_etudiant, $id_evaluation, $motif) {
        $query = "INSERT INTO reclamation (motif, statut, id_Etudiant, Id_Evaluation) 
                VALUES (:motif, 'En attente', :id_e, :id_ev)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            'motif' => $motif,
            'id_e'  => $id_etudiant,
            'id_ev' => $id_evaluation
        ]);
    }

    /**
     * Génère le PV de délibération pour une classe entière
     */
    public function getClassPV($id_classe) {
        $query = "SELECT 
                    e.id_Etudiant, 
                    e.nom, 
                    e.prenom,
                    SUM(eff.note * m.coefficient) / SUM(m.coefficient) as moyenne_generale
                FROM etudiant e
                JOIN effectue eff ON e.id_Etudiant = eff.id_Etudiant
                JOIN evaluation ev ON eff.Id_Evaluation = ev.Id_Evaluation
                JOIN matiere m ON ev.Id_Matiere = m.Id_Matiere
                WHERE e.Id_Classe = :id_c
                GROUP BY e.id_Etudiant
                ORDER BY moyenne_generale DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['id_c' => $id_classe]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>