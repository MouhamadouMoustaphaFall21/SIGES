<?php

/**
 * Interface de saisie des notes - SIGES
 * Permet au professeur de saisir les notes pour une classe et une évaluation précise
 */
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Professeur') {
    header("Location: ../../index.php");
    exit();
}

require_once '../../config/database.php';
require_once '../../models/Teacher.php';
require_once '../../models/Student.php';

$database = new Database();
$db = $database->getConnection();

$teacherModel = new Teacher($db);
$studentModel = new Student($db);

// 1. Récupérer les infos du prof et l'ID de la classe depuis l'URL
$profData = $teacherModel->getProfileByLogin($_SESSION['user_login']);
$id_classe = isset($_GET['id_classe']) ? intval($_GET['id_classe']) : 0;

// 2. Trouver l'ID de l'évaluation correspondant à ce prof et cette matière (Semestre 1 par défaut ici)
// Selon ton SQL, une évaluation lie un Professeur et une Matière
$queryEval = "SELECT Id_Evaluation FROM evaluation 
              WHERE Id_Professeur = :id_p AND Id_Matiere = :id_m AND semestre = 1 LIMIT 1";
$stmtEval = $db->prepare($queryEval);
$stmtEval->execute(['id_p' => $profData['Id_Professeur'], 'id_m' => $profData['Id_Matiere']]);
$eval = $stmtEval->fetch(PDO::FETCH_ASSOC);
$id_evaluation = $eval['Id_Evaluation'];

// 3. Récupérer les étudiants de la classe et leurs notes actuelles pour cette évaluation
$queryStudents = "SELECT e.id_Etudiant, e.nom, e.prenom, eff.note 
                  FROM etudiant e
                  LEFT JOIN effectue eff ON e.id_Etudiant = eff.id_Etudiant AND eff.Id_Evaluation = :id_ev
                  WHERE e.Id_Classe = :id_c
                  ORDER BY e.nom ASC";
$stmtStudents = $db->prepare($queryStudents);
$stmtStudents->execute(['id_ev' => $id_evaluation, 'id_c' => $id_classe]);
$students = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Saisie des notes - SIGES</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 20px;
        }

        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #007bff;
            color: white;
        }

        input[type="number"] {
            width: 80px;
            padding: 5px;
        }

        .btn-save {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            float: right;
            margin-top: 20px;
        }

        .back-link {
            text-decoration: none;
            color: #007bff;
            font-size: 14px;
        }
    </style>
</head>

<body>

    <div class="container">
        <a href="dashboard.php" class="back-link">← Retour au dashboard</a>
        <h2>Saisie des notes : <?= htmlspecialchars($profData['nom_matiere']) ?></h2>
        <p>Classe ID : <?= $id_classe ?> | Évaluation ID : <?= $id_evaluation ?></p>

        <form action="../../controllers/GradeController.php" method="POST">
            <input type="hidden" name="id_evaluation" value="<?= $id_evaluation ?>">
            <input type="hidden" name="id_classe" value="<?= $id_classe ?>">

            <table>
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Note / 20</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['nom'] . " " . $s['prenom']) ?></td>
                            <td>
                                <input type="number"
                                    name="notes[<?= $s['id_Etudiant'] ?>]"
                                    value="<?= $s['note'] ?>"
                                    step="0.25" min="0" max="20" required>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <button type="submit" class="btn-save">Enregistrer toutes les notes</button>
        </form>
    </div>

</body>

</html>