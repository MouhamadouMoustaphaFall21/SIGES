<?php

/**
 * Dashboard Enseignant - SIGES
 * Gestion des classes affectées et emploi du temps
 */
session_start();

// 1. Sécurité : Vérifier le rôle
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Professeur') {
    header("Location: ../../index.php");
    exit();
}

require_once '../../config/database.php';
require_once '../../models/Teacher.php';

$database = new Database();
$db = $database->getConnection();
$teacherModel = new Teacher($db);

// 2. Récupérer les infos du professeur via son login
$profData = $teacherModel->getProfileByLogin($_SESSION['user_login']);
$id_prof = $profData['Id_Professeur'];

// 3. Récupérer les classes affectées
$classesStmt = $teacherModel->getAssignedClasses($id_prof);
$classes = $classesStmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Récupérer l'emploi du temps du professeur (table 'creneau')
$querySchedule = "SELECT cr.*, cl.libelle as classe_nom 
                  FROM creneau cr
                  JOIN classe cl ON cr.Id_Classe = cl.Id_Classe
                  WHERE cr.Id_Professeur = :id_prof
                  ORDER BY FIELD(cr.jour, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'), cr.heure_debut";
$stmtSchedule = $db->prepare($querySchedule);
$stmtSchedule->execute(['id_prof' => $id_prof]);
$schedule = $stmtSchedule->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Espace Enseignant - SIGES</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: auto;
        }

        .welcome-box {
            background: #007bff;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            position: relative;
        }

        .logout {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            text-decoration: none;
            border: 1px solid white;
            padding: 5px 10px;
            border-radius: 4px;
        }

        .section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        h3 {
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            color: #333;
        }

        .class-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        .class-card {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            transition: 0.3s;
        }

        .class-card:hover {
            border-color: #007bff;
            background: #f0f7ff;
        }

        .btn-notes {
            display: inline-block;
            margin: 5px;
            padding: 8px 15px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

        .btn-students {
            display: inline-block;
            margin: 5px;
            padding: 8px 15px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

        .btn-grades {
            display: inline-block;
            margin: 5px;
            padding: 8px 15px;
            background: #ffc107;
            color: black;
            text-decoration: none;
            border-radius: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        th {
            background: #f8f9fa;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="welcome-box">
            <a href="../../controllers/Logout.php" class="logout">Déconnexion</a>
            <h1>Prof. <?= htmlspecialchars($profData['prenom'] . " " . $profData['nom']) ?></h1>
            <p>Discipline : <strong><?= htmlspecialchars($profData['nom_matiere']) ?></strong></p>
        </div>

        <nav style="background: #007bff; color: white; padding: 10px; border-radius: 8px; margin-bottom: 20px;">
            <a href="dashboard.php" style="color: white; margin-right: 20px; text-decoration: none;">Dashboard</a>
        </nav>

        <div class="section">
            <h3>Mes Classes Affectées</h3>
            <div class="class-grid">
                <?php foreach ($classes as $c): ?>
                    <div class="class-card">
                        <strong><?= htmlspecialchars($c['libelle']) ?></strong><br>
                        <span>Niveau : <?= $c['niveau'] ?></span><br>
                        <a href="grades_entry.php?id_classe=<?= $c['Id_Classe'] ?>" class="btn-notes">Saisir les Notes</a>
                        <a href="view_students.php?id_classe=<?= $c['Id_Classe'] ?>" class="btn-students">Voir les Élèves</a>
                        <a href="view_grades.php?id_classe=<?= $c['Id_Classe'] ?>" class="btn-grades">Voir les Notes</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="section">
            <h3>Mon Emploi du Temps</h3>
            <table>
                <thead>
                    <tr>
                        <th>Jour</th>
                        <th>Heure</th>
                        <th>Classe</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($schedule) > 0): ?>
                        <?php foreach ($schedule as $s): ?>
                            <tr>
                                <td><?= $s['jour'] ?></td>
                                <td><?= substr($s['heure_debut'], 0, 5) ?> - <?= substr($s['heure_fin'], 0, 5) ?></td>
                                <td><?= htmlspecialchars($s['classe_nom']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3">Aucun créneau programmé.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>