<?php

/**
 * Dashboard Administrateur - SIGES
 * Vue d'ensemble, statistiques et gestion globale
 */
session_start();

// 1. Sécurité : Vérifier le rôle Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: ../../index.php");
    exit();
}

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// 2. Récupération des statistiques globales (Comptage simple)
$stats = [];
$stats['etudiants'] = $db->query("SELECT COUNT(*) FROM etudiant")->fetchColumn();
$stats['professeurs'] = $db->query("SELECT COUNT(*) FROM professeur")->fetchColumn();
$stats['classes'] = $db->query("SELECT COUNT(*) FROM classe")->fetchColumn();

// 3. Calcul de la moyenne par classe (Performance globale)
$queryClasses = "SELECT c.Id_Classe, c.libelle, c.niveau,
                 (SELECT AVG(eff.note) 
                  FROM effectue eff 
                  JOIN etudiant e ON eff.id_Etudiant = e.id_Etudiant 
                  WHERE e.Id_Classe = c.Id_Classe) as moyenne_classe
                 FROM classe c";
$stmtClasses = $db->prepare($queryClasses);
$stmtClasses->execute();
$classesPerformance = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Administration - SIGES</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 20px;
        }

        .admin-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .stat-card h2 {
            margin: 0;
            color: #007bff;
            font-size: 32px;
        }

        .stat-card p {
            margin: 5px 0 0;
            color: #666;
            text-transform: uppercase;
            font-size: 12px;
        }

        .main-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #edf2f7;
        }

        th {
            background: #f8f9fa;
            color: #4a5568;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-success {
            background: #c6f6d5;
            color: #22543d;
        }

        .badge-warning {
            background: #feebc8;
            color: #744210;
        }

        .logout-btn {
            background: #e53e3e;
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 14px;
        }
    </style>
</head>

<body>

    <div class="header-flex">
        <h1>Tableau de Bord Administrateur</h1>
        <a href="../../controllers/Logout.php" class="logout-btn">Déconnexion</a>
    </div>

    <nav style="background: #007bff; color: white; padding: 10px; border-radius: 8px; margin-bottom: 20px;">
        <a href="dashboard.php" style="color: white; margin-right: 20px; text-decoration: none;">Dashboard</a>
        <a href="users.php" style="color: white; margin-right: 20px; text-decoration: none;">Gestion Utilisateurs</a>
        <a href="grades_view.php" style="color: white; margin-right: 20px; text-decoration: none;">PV Délibération</a>
        <a href="schedule.php" style="color: white; text-decoration: none;">Emploi du Temps</a>
    </nav>

    <div class="admin-grid">
        <div class="stat-card">
            <h2><?= $stats['etudiants'] ?></h2>
            <p>Étudiants inscrits</p>
        </div>
        <div class="stat-card">
            <h2><?= $stats['professeurs'] ?></h2>
            <p>Enseignants</p>
        </div>
        <div class="stat-card">
            <h2><?= $stats['classes'] ?></h2>
            <p>Classes actives</p>
        </div>
    </div>

    <div class="main-section">
        <h3>Performance par Classe</h3>
        <table>
            <thead>
                <tr>
                    <th>Classe</th>
                    <th>Niveau</th>
                    <th>Moyenne Globale</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classesPerformance as $cp): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($cp['libelle']) ?></strong></td>
                        <td><?= $cp['niveau'] ?></td>
                        <td><?= $cp['moyenne_classe'] ? round($cp['moyenne_classe'], 2) : 'N/A' ?> / 20</td>
                        <td>
                            <?php if (!$cp['moyenne_classe']): ?>
                                <span class="badge">Pas de notes</span>
                            <?php elseif ($cp['moyenne_classe'] >= 10): ?>
                                <span class="badge badge-success">Satisfaisant</span>
                            <?php else: ?>
                                <span class="badge badge-warning">À surveiller</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</body>

</html>