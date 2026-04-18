<?php
require_once '../../config/auth.php';
requireRole('Etudiant');
require_once '../../config/database.php';
require_once '../../models/Student.php';
require_once '../../models/Schedule.php';

$database     = new Database();
$db           = $database->getConnection();
$studentModel = new Student($db);
$scheduleModel = new Schedule($db);

$profile = $studentModel->getProfileByLogin($_SESSION['user_login']);
$mySchedule = $scheduleModel->getByClasse($profile['Id_Classe'])->fetchAll(PDO::FETCH_ASSOC);
$initials = strtoupper(substr($profile['prenom'], 0, 1) . substr($profile['nom'], 0, 1));

// Palette couleurs matières
$palette = [
    ['bg' => '#dbeafe', 'border' => '#3b82f6', 'text' => '#1e3a8a'],
    ['bg' => '#dcfce7', 'border' => '#22c55e', 'text' => '#14532d'],
    ['bg' => '#fef3c7', 'border' => '#f59e0b', 'text' => '#78350f'],
    ['bg' => '#ffe4e6', 'border' => '#f43f5e', 'text' => '#881337'],
    ['bg' => '#f3e8ff', 'border' => '#a855f7', 'text' => '#581c87'],
    ['bg' => '#e0f2fe', 'border' => '#0ea5e9', 'text' => '#0c4a6e'],
];
function slotColor($id) {
    global $palette;
    return $palette[intval($id) % count($palette)];
}

$days = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
// Heure de début/fin de la grille (8h–18h)
$gridStart = 8;
$gridEnd   = 18;
$totalMin  = ($gridEnd - $gridStart) * 60;

// Indexer les créneaux par jour
$byDay = [];
foreach ($days as $d) $byDay[$d] = [];
foreach ($mySchedule as $slot) {
    $byDay[$slot['jour']][] = $slot;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Emploi du Temps - SIGES</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Calendrier hebdomadaire */
        .cal-wrap{margin:0 auto;max-width:1200px}
        .cal-grid{display:grid;grid-template-columns:48px repeat(6,1fr);border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;background:#fff}
        .cal-head{display:contents}
        .cal-head-cell{background:#f8fafc;border-bottom:1px solid #e2e8f0;padding:12px 8px;text-align:center;font-weight:600;color:#475569;font-size:.9rem}
        .corner{background:#f1f5f9}
        .today-col{background:#dbeafe !important}
        .time-label{background:#f8fafc;border-bottom:1px solid #e2e8f0;padding:8px;text-align:center;font-size:.8rem;color:#64748b;font-weight:500}
        .day-col{position:relative;border-bottom:1px solid #e2e8f0;border-right:1px solid #e2e8f0;padding:0;min-height:60px}
        .day-col:last-child{border-right:0}
        .day-col.day-highlight{background:rgba(56,189,248,0.08)}
        .cal-head-cell.day-highlight{background:#dbeafe;color:#1A3C5A;border-color:#bfdbfe}
        .hour-line{position:absolute;left:0;right:0;height:1px;background:#e2e8f0}
        .ev-card{position:absolute;left:4px;right:4px;border-radius:6px;border-left:3px solid;padding:6px 8px;font-size:.75rem;z-index:10;cursor:pointer;transition:transform .15s ease}
        .ev-card:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,.1)}
        .ev-time{font-weight:600;margin-bottom:2px}
        .ev-subject{font-weight:500}
        .ev-class{opacity:.8}
        .cal-empty{text-align:center;padding:60px 20px;color:#64748b}
        .cal-empty i{font-size:3rem;margin-bottom:16px;opacity:.5}
        /* Legend */
        .legend{display:flex;flex-wrap:wrap;gap:10px;margin-top:20px}
        .legend-item{display:flex;align-items:center;gap:7px;font-size:.82rem;color:#475569;background:#f8fafc;padding:6px 12px;border-radius:8px}
        .legend-dot{width:12px;height:12px;border-radius:3px;flex-shrink:0}

        @media(max-width:640px){.cal-grid{grid-template-columns:48px repeat(6,minmax(80px,1fr))}}
    </style>
</head>

<body>
    <div class="student-shell">
        <aside class="student-sidebar">
            <div class="sidebar-brand">
                <img src="../../assets/img/logo_simple-SAP.png" alt="SIGES logo">
                <div class="brand-title">
                    <strong>SIGES</strong>
                    <span>Espace Étudiant</span>
                </div>
            </div>

            <div class="profile-box">
                <div class="profile-avatar"><i class='bx bxs-user'></i></div>
                <div class="profile-info">
                    <h2><?= htmlspecialchars($profile['prenom'] . ' ' . $profile['nom']) ?></h2>
                    <p><?= htmlspecialchars($profile['nom_classe']) ?> • <?= htmlspecialchars($profile['niveau']) ?></p>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php"><i class='bx bx-grid-alt'></i>Dashboard</a>
                <a href="performances.php"><i class='bx bx-bar-chart-alt-2'></i>Mes performances</a>
                <a href="schedule.php" class="active"><i class='bx bx-calendar'></i>Emploi du Temps</a>
                <a href="reclamation.php"><i class='bx bx-message-square-detail'></i>Réclamation</a>
                <a href="bulletin.php"><i class='bx bx-file'></i>Bulletin</a>
            </nav>

            <a href="../../controllers/Logout.php" class="logout-btn"><i class='bx bx-log-out'></i>Déconnexion</a>
        </aside>

        <main class="student-main">
            <section class="page-header page-header-schedule">
                <div>
                    <p class="eyebrow">Emploi du Temps</p>
                    <h1>Planning de la semaine</h1>
                    <p>Retrouvez tous vos créneaux de cours, les heures et les professeurs de votre classe dans un seul espace.</p>
                    <div class="room-banner">
                        <span class="badge room-badge" style="color: #F29100; background: rgba(242, 145, 0, 0.12);">Salle fixe</span>
                        <strong>Salle S-12 • Bâtiment principal</strong>
                    </div>
                </div>
                <div class="header-user-card">
                    <strong>Bonjour, <?= htmlspecialchars($profile['prenom']) ?></strong>
                    <span>Bonne consultation</span>
                </div>
            </section>

            <section class="section-block">
                <div style="display:flex;justify-content:flex-end;margin-bottom:18px;gap:10px;flex-wrap:wrap;">
                    <button onclick="downloadSchedulePDF('mon-emploi-du-temps.pdf')" class="button-primary button-small" style="border:none; cursor:pointer;">Télécharger PDF</button>
                </div>
                <?php if (count($mySchedule) > 0): ?>
                <!-- Calendrier -->
                <div class="cal-wrap">
                    <?php
                    $todayName = ['Sunday'=>'Dimanche','Monday'=>'Lundi','Tuesday'=>'Mardi','Wednesday'=>'Mercredi','Thursday'=>'Jeudi','Friday'=>'Vendredi','Saturday'=>'Samedi'][date('l')] ?? '';
                    ?>
                    <div class="cal-grid" id="calGrid">
                        <!-- Header -->
                        <div class="cal-head">
                            <div class="cal-head-cell corner"></div>
                            <?php foreach ($days as $day): ?>
                                <div class="cal-head-cell <?= $day===$todayName?'today-col':'' ?>">
                                    <?= $day ?>
                                    <?php if ($day === $todayName): ?>
                                        <span style="display:block;width:6px;height:6px;background:#2E86AB;border-radius:50%;margin:4px auto 0;"></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Body : lignes horaires -->
                        <?php for ($h = $gridStart; $h < $gridEnd; $h++): ?>
                            <!-- Heure entière -->
                            <div class="time-label"><?= str_pad($h, 2, '0', STR_PAD_LEFT) ?>h</div>
                            <?php foreach ($days as $day): ?>
                                <div class="day-col <?= $day===$todayName ? 'today-col' : '' ?> <?= in_array($day, ['Lundi','Mardi','Mercredi','Jeudi','Samedi']) ? 'day-highlight' : '' ?>" style="height:60px;">
                                    <div class="hour-line" style="top:30px;"></div>
                                    <?php foreach ($byDay[$day] as $slot):
                                        $hDeb = intval(substr($slot['heure_debut'],0,2));
                                        $mDeb = intval(substr($slot['heure_debut'],3,2));
                                        $hFin = intval(substr($slot['heure_fin'],0,2));
                                        $mFin = intval(substr($slot['heure_fin'],3,2));
                                        // n'afficher la carte que dans la cellule de l'heure de début
                                        if ($hDeb !== $h) continue;
                                        $topMin    = 0; // aligné au haut de cette cellule
                                        $durationMin = ($hFin*60+$mFin) - ($hDeb*60+$mDeb);
                                        $cardHeight = ($durationMin / 60) * 60; // px (60px/h)
                                        $col = slotColor($slot['Id_Matiere']);
                                    ?>
                                        <div class="ev-card"
                                             style="top:<?= $topMin + ($mDeb>0?30:0) ?>px;height:<?= $cardHeight-4 ?>px;background:<?= $col['bg'] ?>;border-color:<?= $col['border'] ?>;color:<?= $col['text'] ?>;"
                                             data-matiere="<?= htmlspecialchars($slot['matiere_nom']) ?>"
                                             data-classe="<?= htmlspecialchars($slot['classe_nom']) ?>">
                                            <div class="ev-time"><?= substr($slot['heure_debut'],0,5) ?> – <?= substr($slot['heure_fin'],0,5) ?></div>
                                            <div class="ev-subject"><?= htmlspecialchars($slot['matiere_nom']) ?></div>
                                            <div class="ev-class">Prof. <?= htmlspecialchars($slot['prof_nom']) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Légende -->
                <?php
                $legendItems = [];
                foreach ($mySchedule as $s) {
                    $key = $s['Id_Matiere'];
                    if (!isset($legendItems[$key])) {
                        $legendItems[$key] = ['label' => $s['matiere_nom'], 'color' => slotColor($s['Id_Matiere'])];
                    }
                }
                ?>
                <div class="legend">
                    <?php foreach ($legendItems as $item): ?>
                        <div class="legend-item">
                            <span class="legend-dot" style="background:<?= $item['color']['border'] ?>;"></span>
                            <?= htmlspecialchars($item['label']) ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php else: ?>
                    <div class="cal-empty">
                        <i class='bx bx-calendar-x'></i>
                        <p>Aucun créneau trouvé pour votre classe.</p>
                        <p style="font-size:.9rem;">Contactez l'administrateur pour configurer votre emploi du temps.</p>
                    </div>
                <?php endif; ?>
            </section>

        </main>
    </div>
</body>

</html>
