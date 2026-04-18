<?php

class Grade {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Récupérer les notes détaillées d'un étudiant
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
     * Récupérer le classement des étudiants d'une classe
     */
    public function getRankingByClasse($id_classe) {
        $query = "SELECT e.id_Etudiant, e.nom, e.prenom,
                         COALESCE(SUM(eff.note * m.coefficient) / NULLIF(SUM(m.coefficient), 0), 0) as moyenne_gen
                  FROM etudiant e
                  LEFT JOIN effectue eff ON e.id_Etudiant = eff.id_Etudiant
                  LEFT JOIN evaluation ev ON eff.Id_Evaluation = ev.Id_Evaluation
                  LEFT JOIN matiere m ON ev.Id_Matiere = m.Id_Matiere
                  WHERE e.Id_Classe = :id_classe
                  GROUP BY e.id_Etudiant
                  ORDER BY moyenne_gen DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute(['id_classe' => $id_classe]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
     * Récupérer la liste des évaluations d'un professeur
     */
    public function getTeacherEvaluations($id_prof) {
        $query = "SELECT ev.*, m.libelle as matiere, m.coefficient
                  FROM evaluation ev
                  JOIN matiere m ON ev.Id_Matiere = m.Id_Matiere
                  WHERE ev.Id_Professeur = :id_prof
                  ORDER BY ev.date_eval DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute(['id_prof' => $id_prof]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Créer une nouvelle évaluation pour un professeur
     */
    public function createEvaluation($date_eval, $semestre, $id_matiere, $id_professeur, $id_classe = null) {
        // Id_Classe est optionnel (la colonne peut ne pas exister sur les anciennes installations)
        try {
            $query = "INSERT INTO evaluation (date_eval, semestre, Id_Matiere, Id_Professeur, Id_Classe)
                      VALUES (:date_eval, :semestre, :id_m, :id_prof, :id_c)";
            $stmt = $this->conn->prepare($query);
            $ok = $stmt->execute([
                'date_eval' => $date_eval,
                'semestre'  => $semestre,
                'id_m'      => $id_matiere,
                'id_prof'   => $id_professeur,
                'id_c'      => $id_classe ?: null
            ]);
        } catch (\PDOException $e) {
            // Colonne Id_Classe absente : fallback sans elle
            $query = "INSERT INTO evaluation (date_eval, semestre, Id_Matiere, Id_Professeur)
                      VALUES (:date_eval, :semestre, :id_m, :id_prof)";
            $stmt = $this->conn->prepare($query);
            $ok = $stmt->execute([
                'date_eval' => $date_eval,
                'semestre'  => $semestre,
                'id_m'      => $id_matiere,
                'id_prof'   => $id_professeur
            ]);
        }
        return $ok ? $this->conn->lastInsertId() : false;
    }

    /**
     * Statistiques de réussite et répartition par classe
     */
    public function getClassSuccessStats($id_classe) {
        $query = "SELECT
                    SUM(CASE WHEN sub.moyenne >= 10 THEN 1 ELSE 0 END) AS passes,
                    SUM(CASE WHEN sub.moyenne < 10 THEN 1 ELSE 0 END) AS fails,
                    COUNT(*) AS total,
                    AVG(sub.moyenne) AS moyenne_generale
                  FROM (
                    SELECT e.id_Etudiant,
                           COALESCE(SUM(eff.note * m.coefficient) / NULLIF(SUM(m.coefficient), 0), 0) as moyenne
                    FROM etudiant e
                    LEFT JOIN effectue eff ON e.id_Etudiant = eff.id_Etudiant
                    LEFT JOIN evaluation ev ON eff.Id_Evaluation = ev.Id_Evaluation
                    LEFT JOIN matiere m ON ev.Id_Matiere = m.Id_Matiere
                    WHERE e.Id_Classe = :id_classe
                    GROUP BY e.id_Etudiant
                  ) sub";

        $stmt = $this->conn->prepare($query);
        $stmt->execute(['id_classe' => $id_classe]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Distribution des notes pour une classe
     */
    public function getClassDistribution($id_classe) {
        $query = "SELECT
                    SUM(CASE WHEN sub.moyenne < 8 THEN 1 ELSE 0 END) AS '0-7',
                    SUM(CASE WHEN sub.moyenne BETWEEN 8 AND 9.99 THEN 1 ELSE 0 END) AS '8-9.99',
                    SUM(CASE WHEN sub.moyenne BETWEEN 10 AND 12.99 THEN 1 ELSE 0 END) AS '10-12.99',
                    SUM(CASE WHEN sub.moyenne BETWEEN 13 AND 15.99 THEN 1 ELSE 0 END) AS '13-15.99',
                    SUM(CASE WHEN sub.moyenne >= 16 THEN 1 ELSE 0 END) AS '16-20'
                  FROM (
                    SELECT e.id_Etudiant,
                           COALESCE(SUM(eff.note * m.coefficient) / NULLIF(SUM(m.coefficient), 0), 0) as moyenne
                    FROM etudiant e
                    LEFT JOIN effectue eff ON e.id_Etudiant = eff.id_Etudiant
                    LEFT JOIN evaluation ev ON eff.Id_Evaluation = ev.Id_Evaluation
                    LEFT JOIN matiere m ON ev.Id_Matiere = m.Id_Matiere
                    WHERE e.Id_Classe = :id_classe
                    GROUP BY e.id_Etudiant
                  ) sub";

        $stmt = $this->conn->prepare($query);
        $stmt->execute(['id_classe' => $id_classe]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Moyennes par classe pour les classes affectées au professeur
     */
    public function getTeacherClassSummary($id_prof) {
        $query = "SELECT c.Id_Classe, c.libelle, c.niveau,
                    COUNT(DISTINCT e.id_Etudiant) AS etudiants,
                    AVG(sub.moyenne) AS moyenne_classe,
                    SUM(CASE WHEN sub.moyenne >= 10 THEN 1 ELSE 0 END) / NULLIF(COUNT(*),0) * 100 AS taux_reussite
                  FROM classe c
                  JOIN affecter a ON c.Id_Classe = a.Id_Classe
                  JOIN etudiant e ON e.Id_Classe = c.Id_Classe
                  LEFT JOIN (
                    SELECT e.id_Etudiant,
                           COALESCE(SUM(eff.note * m.coefficient) / NULLIF(SUM(m.coefficient), 0), 0) as moyenne
                    FROM etudiant e
                    LEFT JOIN effectue eff ON e.id_Etudiant = eff.id_Etudiant
                    LEFT JOIN evaluation ev ON eff.Id_Evaluation = ev.Id_Evaluation
                    LEFT JOIN matiere m ON ev.Id_Matiere = m.Id_Matiere
                    GROUP BY e.id_Etudiant
                  ) sub ON sub.id_Etudiant = e.id_Etudiant
                  WHERE a.Id_Professeur = :id_prof
                  GROUP BY c.Id_Classe, c.libelle, c.niveau";

        $stmt = $this->conn->prepare($query);
        $stmt->execute(['id_prof' => $id_prof]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les notes d'une évaluation pour une classe
     */
    public function getNotesForEvaluation($id_classe, $id_evaluation) {
        $query = "SELECT e.id_Etudiant, e.nom, e.prenom, COALESCE(eff.note, '') as note
                  FROM etudiant e
                  LEFT JOIN effectue eff ON e.id_Etudiant = eff.id_Etudiant AND eff.Id_Evaluation = :id_evaluation
                  WHERE e.Id_Classe = :id_classe
                  ORDER BY e.nom ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute(['id_evaluation' => $id_evaluation, 'id_classe' => $id_classe]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère le nombre d'étudiants par classe
     */
    public function getStudentsByClassCount() {
        $query = "SELECT c.libelle, c.niveau, COUNT(e.id_Etudiant) AS total_etudiants
                  FROM classe c
                  LEFT JOIN etudiant e ON c.Id_Classe = e.Id_Classe
                  GROUP BY c.Id_Classe
                  ORDER BY total_etudiants DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer la liste des étudiants d'une classe
     */
    public function getStudentsByClass($id_classe) {
        $query = "SELECT id_Etudiant, nom, prenom, login
                  FROM etudiant
                  WHERE Id_Classe = :id_classe
                  ORDER BY nom ASC, prenom ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute(['id_classe' => $id_classe]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les matières disponibles pour une classe
     */
    public function getSubjectsByClass($id_classe) {
        $query = "SELECT DISTINCT m.Id_Matiere, m.libelle, m.coefficient
                  FROM evaluation ev
                  JOIN matiere m ON ev.Id_Matiere = m.Id_Matiere
                  JOIN effectue eff ON ev.Id_Evaluation = eff.Id_Evaluation
                  JOIN etudiant e ON eff.id_Etudiant = e.id_Etudiant
                  WHERE e.Id_Classe = :id_classe
                  ORDER BY m.libelle ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute(['id_classe' => $id_classe]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les évaluations pour une classe et une matière
     */
    public function getEvaluationsByClassAndSubject($id_classe, $id_matiere) {
        $query = "SELECT DISTINCT ev.Id_Evaluation, ev.date_eval, ev.semestre,
                         CONCAT(p.nom, ' ', p.prenom) as professeur,
                         m.libelle as matiere
                  FROM evaluation ev
                  JOIN matiere m ON ev.Id_Matiere = m.Id_Matiere
                  JOIN professeur p ON ev.Id_Professeur = p.Id_Professeur
                  JOIN effectue eff ON ev.Id_Evaluation = eff.Id_Evaluation
                  JOIN etudiant e ON eff.id_Etudiant = e.id_Etudiant
                  WHERE e.Id_Classe = :id_classe AND ev.Id_Matiere = :id_matiere
                  ORDER BY ev.date_eval ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute(['id_classe' => $id_classe, 'id_matiere' => $id_matiere]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les notes des étudiants pour une classe et une matière
     */
    public function getNotesByClassAndSubject($id_classe, $id_matiere) {
        $query = "SELECT e.id_Etudiant, eff.Id_Evaluation, eff.note
                  FROM etudiant e
                  LEFT JOIN effectue eff ON e.id_Etudiant = eff.id_Etudiant
                  LEFT JOIN evaluation ev ON eff.Id_Evaluation = ev.Id_Evaluation
                  WHERE e.Id_Classe = :id_classe
                    AND ev.Id_Matiere = :id_matiere
                  ORDER BY e.nom ASC, e.prenom ASC, ev.date_eval ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute(['id_classe' => $id_classe, 'id_matiere' => $id_matiere]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les matières enseignées par le professeur
     */
    public function getSubjectsByTeacher($id_prof) {
        $query = "SELECT DISTINCT m.Id_Matiere, m.libelle, m.coefficient
                  FROM matiere m
                  JOIN professeur p ON m.Id_Matiere = p.Id_Matiere
                  WHERE p.Id_Professeur = :id_prof";

        $stmt = $this->conn->prepare($query);
        $stmt->execute(['id_prof' => $id_prof]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer la moyenne générale et la distribution par matière pour une classe
     */
    public function getSubjectAveragesForClass($id_classe) {
        $query = "SELECT m.libelle as matiere, m.coefficient,
                         AVG(eff.note) as moyenne_mat,
                         SUM(CASE WHEN eff.note >= 10 THEN 1 ELSE 0 END) AS admis,
                         SUM(CASE WHEN eff.note < 10 THEN 1 ELSE 0 END) AS ajourne
                  FROM etudiant e
                  JOIN effectue eff ON e.id_Etudiant = eff.id_Etudiant
                  JOIN evaluation ev ON eff.Id_Evaluation = ev.Id_Evaluation
                  JOIN matiere m ON ev.Id_Matiere = m.Id_Matiere
                  WHERE e.Id_Classe = :id_classe
                  GROUP BY m.Id_Matiere, m.libelle, m.coefficient
                  ORDER BY moyenne_mat DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute(['id_classe' => $id_classe]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les moyennes et bornes de notes par matière pour une classe
     */
    public function getClassSubjectStats($id_classe) {
        $query = "SELECT m.libelle as matiere, m.coefficient,
                         ROUND(AVG(eff.note), 2) as moyenne_classe,
                         MIN(eff.note) as note_min,
                         MAX(eff.note) as note_max
                  FROM etudiant e
                  JOIN effectue eff ON e.id_Etudiant = eff.id_Etudiant
                  JOIN evaluation ev ON eff.Id_Evaluation = ev.Id_Evaluation
                  JOIN matiere m ON ev.Id_Matiere = m.Id_Matiere
                  WHERE e.Id_Classe = :id_classe
                  GROUP BY m.Id_Matiere, m.libelle, m.coefficient
                  ORDER BY m.libelle ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute(['id_classe' => $id_classe]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les notes moyennes par matière pour un étudiant
     */
    public function getStudentSubjectGrades($id_etudiant) {
        $query = "SELECT m.Id_Matiere, m.libelle as matiere, m.coefficient,
                         ROUND(AVG(eff.note), 2) as note
                  FROM effectue eff
                  JOIN evaluation ev ON eff.Id_Evaluation = ev.Id_Evaluation
                  JOIN matiere m ON ev.Id_Matiere = m.Id_Matiere
                  WHERE eff.id_Etudiant = :id
                  GROUP BY m.Id_Matiere, m.libelle, m.coefficient
                  ORDER BY m.libelle ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_etudiant);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer l'évaluation active la plus récente pour une classe
     */
    public function getLatestEvaluation($id_prof) {
        $query = "SELECT ev.*, m.libelle as matiere
                  FROM evaluation ev
                  JOIN matiere m ON ev.Id_Matiere = m.Id_Matiere
                  WHERE ev.Id_Professeur = :id_prof
                  ORDER BY ev.date_eval DESC
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute(['id_prof' => $id_prof]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les étudiants par classe et leurs moyennes pour l'enseignant
     */
    public function getStudentsWithAveragesByClass($id_classe) {
        $query = "SELECT e.id_Etudiant, e.nom, e.prenom,
                         COALESCE(SUM(eff.note * m.coefficient) / NULLIF(SUM(m.coefficient), 0), 0) as moyenne
                  FROM etudiant e
                  LEFT JOIN effectue eff ON e.id_Etudiant = eff.id_Etudiant
                  LEFT JOIN evaluation ev ON eff.Id_Evaluation = ev.Id_Evaluation
                  LEFT JOIN matiere m ON ev.Id_Matiere = m.Id_Matiere
                  WHERE e.Id_Classe = :id_classe
                  GROUP BY e.id_Etudiant
                  ORDER BY moyenne DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute(['id_classe' => $id_classe]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

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

    public function getReclamationsForTeacher($id_prof) {
        $query = "SELECT r.id_reclamation, r.motif, r.statut,
                         e.nom AS etu_nom, e.prenom AS etu_prenom,
                         c.libelle AS classe, c.niveau,
                         m.libelle AS matiere, ev.semestre, ev.date_eval
                  FROM reclamation r
                  LEFT JOIN etudiant e ON e.id_Etudiant = r.id_Etudiant
                  LEFT JOIN evaluation ev ON ev.Id_Evaluation = r.Id_Evaluation
                  LEFT JOIN matiere m ON m.Id_Matiere = ev.Id_Matiere
                  LEFT JOIN classe c ON e.Id_Classe = c.Id_Classe
                  WHERE ev.Id_Professeur = :id_prof
                  ORDER BY r.statut ASC, r.id_reclamation DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_prof', $id_prof);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateReclamationStatus($id_reclamation, $statut) {
        $query = "UPDATE reclamation SET statut = :statut WHERE id_reclamation = :id_reclamation";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':statut', $statut);
        $stmt->bindParam(':id_reclamation', $id_reclamation, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Génère le PV de délibération pour une classe entière
     */
    public function getClassPV($id_classe) {
        $query = "SELECT * FROM (
                    SELECT 
                        e.id_Etudiant, 
                        e.nom, 
                        e.prenom,
                        CASE WHEN SUM(eff.note IS NOT NULL) = 0 THEN NULL
                             ELSE ROUND(SUM(eff.note * m.coefficient) / NULLIF(SUM(CASE WHEN eff.note IS NOT NULL THEN m.coefficient ELSE 0 END), 0), 2)
                        END as moyenne_generale
                    FROM etudiant e
                    LEFT JOIN effectue eff ON e.id_Etudiant = eff.id_Etudiant
                    LEFT JOIN evaluation ev ON eff.Id_Evaluation = ev.Id_Evaluation
                    LEFT JOIN matiere m ON ev.Id_Matiere = m.Id_Matiere
                    WHERE e.Id_Classe = :id_c
                    GROUP BY e.id_Etudiant
                ) t
                ORDER BY (moyenne_generale IS NULL), moyenne_generale DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['id_c' => $id_classe]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les notes détaillées pour le PV d'une classe
     */
    public function getDetailedGradesByClass($id_classe) {
        $query = "SELECT e.id_Etudiant, e.nom, e.prenom, m.libelle as matiere, m.coefficient, eff.note
                  FROM etudiant e
                  LEFT JOIN effectue eff ON e.id_Etudiant = eff.id_Etudiant
                  LEFT JOIN evaluation ev ON eff.Id_Evaluation = ev.Id_Evaluation
                  LEFT JOIN matiere m ON ev.Id_Matiere = m.Id_Matiere
                  WHERE e.Id_Classe = :id_c
                  ORDER BY e.nom ASC, e.prenom ASC, m.libelle ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute(['id_c' => $id_classe]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>