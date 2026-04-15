<?php
require_once '../../config/auth.php';
requireRole('Professeur');
require_once '../../config/database.php';
require_once '../../models/Grade.php';
require_once '../../models/Teacher.php';

$database = new Database();
$db = $database->getConnection();

$gradeModel = new Grade($db);
$teacherModel = new Teacher($db);

// Vérifier que la classe est affectée au prof
$profData = $teacherModel->getProfileByLogin($_SESSION['user_login']);
$id_classe = isset($_GET['id_classe']) ? intval($_GET['id_classe']) : 0;

$assignedClasses = $teacherModel->getAssignedClasses($profData['Id_Professeur'])->fetchAll(PDO::FETCH_ASSOC);
$allowed = false;
foreach ($assignedClasses as $ac) {
    if ($ac['Id_Classe'] == $id_classe) {
        $allowed = true;
        break;
    }
}

if (!$allowed) {
    header("Location: dashboard.php");
    exit();
}

$ranking = $gradeModel->getRankingByClasse($id_classe);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Classement des Élèves - SIGES</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 800px; margin: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #007bff; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php">← Retour au dashboard</a>
        <h2>Classement des Élèves</h2>
        <table>
            <thead>
                <tr>
                    <th>Rang</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Moyenne Générale</th>
                </tr>
            </thead>
            <tbody>
                <?php $rank = 1; foreach ($ranking as $r): ?>
                    <tr>
                        <td><?= $rank++ ?></td>
                        <td><?= htmlspecialchars($r['nom']) ?></td>
                        <td><?= htmlspecialchars($r['prenom']) ?></td>
                        <td><?= number_format($r['moyenne_gen'], 2) ?> / 20</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>