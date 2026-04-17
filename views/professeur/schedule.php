<?php
require_once '../../config/auth.php';
requireRole('Professeur');
require_once '../../config/database.php';
require_once '../../models/Teacher.php';
require_once '../../models/Schedule.php';

$database = new Database();
$db = $database->getConnection();
$teacherModel = new Teacher($db);
$scheduleModel = new Schedule($db);

$subjectPalette = [
    ['bg' => 'rgba(56, 189, 248, 0.18)', 'border' => 'rgba(56, 189, 248, 0.34)', 'text' => '#0f172a'],
    ['bg' => 'rgba(34, 197, 94, 0.16)', 'border' => 'rgba(34, 197, 94, 0.32)', 'text' => '#0f172a'],
    ['bg' => 'rgba(251, 191, 36, 0.16)', 'border' => 'rgba(251, 191, 36, 0.30)', 'text' => '#0f172a'],
    ['bg' => 'rgba(249, 115, 22, 0.14)', 'border' => 'rgba(249, 115, 22, 0.28)', 'text' => '#0f172a'],
    ['bg' => 'rgba(168, 85, 247, 0.15)', 'border' => 'rgba(168, 85, 247, 0.28)', 'text' => '#0f172a'],
    ['bg' => 'rgba(129, 140, 248, 0.14)', 'border' => 'rgba(129, 140, 248, 0.28)', 'text' => '#0f172a']
];

function getScheduleColor($id)
{
    global $subjectPalette;
    $key = intval($id) % count($subjectPalette);
    return $subjectPalette[$key];
}

$profData = $teacherModel->getProfileByLogin($_SESSION['user_login']);
$mySchedule = $scheduleModel->getByProfessor($profData['Id_Professeur'])->fetchAll(PDO::FETCH_ASSOC);

$days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
$scheduleGrid = [];
$timeSlots = [];
foreach ($mySchedule as $slot) {
    $timeKey = substr($slot['heure_debut'], 0, 5) . ' - ' . substr($slot['heure_fin'], 0, 5);
    if (!in_array($timeKey, $timeSlots, true)) {
        $timeSlots[] = $timeKey;
    }
    $scheduleGrid[$timeKey][$slot['jour']][] = $slot;
}

usort($timeSlots, function ($a, $b) {
    return strtotime(explode(' - ', $a)[0]) - strtotime(explode(' - ', $b)[0]);
});
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Emploi du Temps - SIGES</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
</head>

<body>
    <div class="student-shell">
        <aside class="student-sidebar">
            <div class="sidebar-brand">
                <img src="../../assets/img/logo_simple-SAP.png" alt="SIGES logo">
                <div class="brand-title">
                    <strong>SIGES</strong>
                    <span>Espace Enseignant</span>
                </div>
            </div>

            <div class="profile-box">
                <div class="profile-avatar"><?= htmlspecialchars(strtoupper(substr($profData['prenom'], 0, 1) . substr($profData['nom'], 0, 1))) ?></div>
                <div class="profile-info">
                    <h2><?= htmlspecialchars($profData['prenom'] . ' ' . $profData['nom']) ?></h2>
                    <p><?= htmlspecialchars($profData['nom_matiere']) ?></p>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php"><i class='bx bx-grid-alt'></i>Dashboard</a>
                <a href="schedule.php" class="active"><i class='bx bx-calendar'></i>Emploi du temps</a>
                <a href="grades_entry.php?id_classe=<?= $mySchedule[0]['Id_Classe'] ?? '' ?>"><i class='bx bx-edit'></i>Saisir notes</a>
                <a href="view_students.php?id_classe=<?= $mySchedule[0]['Id_Classe'] ?? '' ?>"><i class='bx bx-group'></i>Mes élèves</a>
                <a href="view_grades.php?id_classe=<?= $mySchedule[0]['Id_Classe'] ?? '' ?>"><i class='bx bx-bar-chart-alt-2'></i>Classement</a>
            </nav>

            <a href="../../controllers/Logout.php" class="logout-btn"><i class='bx bx-log-out'></i>Déconnexion</a>
        </aside>

        <main class="student-main">
            <section class="page-header page-header-schedule">
                <div>
                    <p class="eyebrow">Emploi du Temps</p>
                    <h1>Planning de vos cours</h1>
                    <p>Consultez votre emploi du temps par jour et par horaire, adapté à l’ensemble de vos classes.</p>
                </div>
                <div class="header-user-card">
                    <strong>Prof. <?= htmlspecialchars($profData['prenom'] . ' ' . $profData['nom']) ?></strong>
                    <span><?= htmlspecialchars($profData['nom_matiere']) ?></span>
                </div>
            </section>

            <section class="section-block">
                <?php if (count($mySchedule) > 0): ?>
                    <div class="schedule-grid">
                        <div class="grid-header"></div>
                        <?php foreach ($days as $day): ?>
                            <div class="grid-header"><?= $day ?></div>
                        <?php endforeach; ?>

                        <?php foreach ($timeSlots as $time): ?>
                            <div class="grid-time"><?= $time ?></div>
                            <?php foreach ($days as $day): ?>
                                <div class="schedule-cell">
                                    <?php if (!empty($scheduleGrid[$time][$day])): ?>
                                        <?php foreach ($scheduleGrid[$time][$day] as $slot): ?>
                                            <?php $slotColor = getScheduleColor($slot['Id_Matiere']); ?>
                                            <div class="schedule-slot" style="background: <?= $slotColor['bg'] ?>; border-color: <?= $slotColor['border'] ?>; color: <?= $slotColor['text'] ?>;">
                                                <strong><?= htmlspecialchars($slot['matiere_nom']) ?></strong>
                                                <span><?= htmlspecialchars($slot['classe_nom']) ?> (<?= htmlspecialchars($slot['niveau']) ?>)</span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="cell-empty">Libre</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="form-hint">Aucun créneau trouvé pour vos classes.</div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>

</html>
