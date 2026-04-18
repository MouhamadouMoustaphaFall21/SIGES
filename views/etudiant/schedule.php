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
        /* Nouveau design emploi du temps */
        .schedule-sheet{max-width:1180px;margin:0 auto;background:#fff;border:1px solid #dbeafe;border-radius:28px;box-shadow:0 24px 80px rgba(15,23,42,.08);padding:26px 24px}
        .schedule-top{display:flex;align-items:flex-start;gap:18px;flex-wrap:wrap;margin-bottom:18px}
        .schedule-box{flex:1 1 220px;min-width:180px;padding:16px 18px;border:1px solid #dbeafe;border-radius:18px;background:#f8fbff}
        .schedule-box label{display:block;font-size:.78rem;color:#64748b;margin-bottom:6px;text-transform:uppercase;letter-spacing:.08em}
        .schedule-box strong{display:block;font-size:1.2rem;color:#0f172a;line-height:1.2}
        .schedule-title{flex:1 1 100%;text-align:center;margin:0 auto 0;padding:16px 20px;background:#1A3C5A;color:#fff;border-radius:24px;font-size:1.75rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;box-shadow:inset 0 -4px 0 rgba(15,23,42,.12)}
        .schedule-meta{display:flex;justify-content:center;gap:10px;flex-wrap:wrap;margin:20px 0 24px}
        .schedule-meta span{background:#eff6ff;color:#1e3a8a;padding:11px 14px;border-radius:14px;font-size:.92rem;font-weight:700;box-shadow:0 8px 24px rgba(56,189,248,.08)}
        .schedule-actions{display:flex;justify-content:flex-end;margin-bottom:12px}
        .schedule-footer{margin-top:20px;padding:16px 18px;border-radius:18px;background:#f8fafc;border:1px solid #dbeafe;color:#475569;font-size:.92rem;display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px}
        .schedule-footer strong{color:#1A3C5A}

        .cal-wrap{overflow-x:auto;border:1px solid #e2e8f0;border-radius:20px;background:#fff}
        .cal-grid{display:grid;grid-template-columns:84px repeat(6,minmax(140px,1fr));min-width:980px;grid-auto-rows:60px}
        .cal-head{display:contents}
        .cal-head-cell{padding:16px 12px;font-size:.86rem;font-weight:700;text-align:center;text-transform:uppercase;letter-spacing:.08em;color:#fff;border-right:1px solid rgba(255,255,255,.08);background:#1A3C5A;position:sticky;top:0;z-index:2}
        .cal-head-cell.corner{background:#f8fafc;color:#64748b;border-right:1px solid #e2e8f0;box-shadow:inset 0 -1px 0 rgba(15,23,42,.04)}
        .cal-head-cell.today-col{background:#2563eb}
        .time-label{padding:0 12px;font-size:.78rem;color:#475569;font-weight:700;text-align:right;border-right:1px solid #e2e8f0;background:#f8fafc;display:flex;align-items:center;justify-content:flex-end}
        .day-col{position:relative;border-bottom:1px solid #e2e8f0;border-right:1px solid #e2e8f0;background:#fff;overflow:visible}
        .day-col:last-child{border-right:none}
        .day-col.today-col{background:#f7fbff}
        .day-col.day-highlight{background:rgba(56,189,248,0.06)}
        .hour-line{position:absolute;left:12px;right:12px;border-top:1px solid #f1f5f9;top:30px}
        .ev-card{position:absolute;left:10px;right:10px;border-radius:16px;border-left:6px solid #1e40af;padding:12px 12px;background:#eff6ff;color:#0f172a;box-shadow:0 12px 30px rgba(15,23,42,.06);transition:transform .15s ease}
        .ev-card:hover{transform:translateY(-1px)}
        .ev-time{font-size:.75rem;font-weight:700;opacity:.82;margin-bottom:6px}
        .ev-subject{font-size:.96rem;font-weight:800;margin-bottom:4px}
        .ev-class{font-size:.78rem;opacity:.78}
        .cal-empty{text-align:center;padding:60px 20px;color:#64748b}
        .cal-empty i{font-size:3rem;margin-bottom:16px;opacity:.5}
        .legend{display:flex;flex-wrap:wrap;gap:10px;margin-top:22px}
        .legend-item{display:flex;align-items:center;gap:8px;font-size:.88rem;color:#475569;background:#f8fafc;padding:8px 14px;border-radius:14px}
        .legend-dot{width:12px;height:12px;border-radius:4px;flex-shrink:0}

        @media(max-width:768px){.cal-grid{grid-template-columns:72px repeat(6,minmax(120px,1fr))}}
        @media(max-width:640px){.schedule-top,.schedule-meta{flex-direction:column;align-items:stretch}.cal-grid{grid-template-columns:56px repeat(6,minmax(90px,1fr))}}

        @media print {
            body { background: #fff; margin: 0; color: #1a202c; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
            .student-shell, .student-main { margin: 0; padding: 0; }
            .student-sidebar, .sidebar-nav, .page-header, .logout-btn, .header-user-card, .button-primary, .profile-box { display: none !important; }
            
            .page-header { display: block !important; margin: 0 0 16px; padding: 12px 0; border-bottom: 3px solid #2d3748; }
            .page-header .eyebrow { display: block; font-size: .9rem; color: #4a5568; margin-bottom: 6px; font-weight: 600; }
            .page-header h1 { font-size: 1.5rem; margin: 0; color: #2d3748; font-weight: 700; }
            .page-header p { font-size: .9rem; margin: 6px 0 0; color: #4a5568; }
            
            .cal-wrap, .section-block { border: none !important; box-shadow: none !important; background: transparent !important; padding: 0 !important; margin: 0 !important; }
            
            .cal-grid { width: 100% !important; border: 2px solid #2d3748 !important; grid-gap: 0; border-radius: 12px; overflow: visible !important; grid-auto-rows: 60px !important; }
            .cal-head-cell { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; color: #fff !important; border: 1px solid #2d3748 !important; font-size: .8rem; padding: .7rem .5rem; font-weight: 700 !important; text-transform: uppercase; letter-spacing: 0.5px; }
            .time-label { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important; color: #fff !important; border: 1px solid #2d3748 !important; font-size: .75rem; padding: .5rem .4rem; font-weight: 600; text-align: center; }
            .day-col { border: 1px solid #2d3748 !important; background: #f7fafc; overflow: visible !important; min-height: 60px !important; height: auto !important; position: relative !important; }
            .ev-card { position: absolute !important; z-index: 2 !important; background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%) !important; border: 2px solid #2d3748 !important; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important; padding: .5rem .6rem; margin: 0 !important; font-size: .82rem; border-radius: 10px; page-break-inside: avoid !important; display: block !important; left: 10px !important; right: 10px !important; }
            .ev-time { font-size: .75rem; font-weight: 700; margin-bottom: 6px; color: #2d3748; }
            .ev-subject, .ev-class { font-size: .82rem; color: #1a202c; white-space: normal !important; overflow-wrap: anywhere !important; word-break: break-word !important; }
            
            .schedule-sheet { margin: 20px auto; border: 3px solid #2d3748; border-radius: 16px; padding: 24px; background: #fff; }
            .schedule-title { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; color: #fff; padding: 20px; border-radius: 12px; font-size: 2rem; font-weight: 800; text-align: center; margin-bottom: 20px; box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2); }
            .schedule-box { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important; border: 2px solid #2d3748; border-radius: 12px; color: #fff; }
            .schedule-meta span { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%) !important; color: #2d3748; border: 1px solid #2d3748; }
        }
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
                <div class="schedule-sheet">
                    <div class="schedule-top">
                        <div class="schedule-box">
                            <label>Classe</label>
                            <strong><?= htmlspecialchars($profile['nom_classe'] . ' ' . $profile['niveau']) ?></strong>
                        </div>
                        <div class="schedule-title">Mon emploi du temps</div>
                        <div class="schedule-box">
                            <label>Semaine n°</label>
                            <strong><?= date('W') ?></strong>
                        </div>
                    </div>
                    <div class="schedule-meta">
                        <span>Année scolaire <?= htmlspecialchars(date('Y') . '-' . (date('Y') + 1)) ?></span>
                        <span>Élève : <?= htmlspecialchars($profile['prenom'] . ' ' . $profile['nom']) ?></span>
                    </div>
                    <div class="schedule-actions">
                        <button onclick="downloadSchedulePDF('mon-emploi-du-temps.pdf')" class="button-primary button-small" style="border:none;cursor:pointer;">Télécharger PDF</button>
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
                                             data-classe="<?= htmlspecialchars($slot['classe_nom'] ?? $profile['nom_classe']) ?>">
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
                <div class="schedule-footer">
                    <strong>Dates de stage :</strong> ____________________________________________
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
    <script>
        if (new URLSearchParams(window.location.search).get('auto') === '1') {
            window.addEventListener('load', function() {
                setTimeout(function() { window.print(); }, 500);
            });
        }
    </script>
    <script>
        window.downloadSchedulePDF = function(filename = 'emploi-du-temps.pdf') {
            window.print();
        };

        window.downloadNotesPDF = function(filename = 'notes.pdf') {
            window.print();
        };
    </script>
</body>

</html>
