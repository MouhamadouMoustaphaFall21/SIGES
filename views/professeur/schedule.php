<?php
/**
 * Emploi du temps enseignant - SIGES (vue calendrier hebdomadaire)
 */
require_once '../../config/auth.php';
requireRole('Professeur');
require_once '../../config/database.php';
require_once '../../models/Teacher.php';
require_once '../../models/Schedule.php';

$database     = new Database();
$db           = $database->getConnection();
$teacherModel = new Teacher($db);
$scheduleModel = new Schedule($db);

$profData   = $teacherModel->getProfileByLogin($_SESSION['user_login']);
$mySchedule = $scheduleModel->getByProfessor($profData['Id_Professeur'])->fetchAll(PDO::FETCH_ASSOC);

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

// Trouver classe par défaut pour sidebar
$selected_classe = $mySchedule[0]['Id_Classe'] ?? '';
$active_page = 'schedule';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Emploi du temps - SIGES</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <style>
        /* ── Toolbar ── */
        .sched-toolbar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:24px}
        .sched-toolbar h2{margin:0;font-size:1.15rem;color:#1A3C5A}
        .toolbar-right{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
        .btn-today{background:#1A3C5A;color:white;border:none;padding:9px 18px;border-radius:10px;font-size:.9rem;font-weight:600;cursor:pointer;transition:background .2s}
        .btn-today:hover{background:#122b40}
        .toolbar-select{padding:8px 14px;border:1px solid #e2e8f0;border-radius:10px;font-size:.9rem;color:#334155;background:#fff;cursor:pointer}

        /* ── Calendar grid ── */
        .cal-wrap{overflow-x:auto;border:1px solid #e2e8f0;border-radius:16px;background:#fff}
        .cal-grid{display:grid;grid-template-columns:64px repeat(6,1fr);min-width:780px}

        /* Header row */
        .cal-head{display:contents}
        .cal-head-cell{padding:14px 8px 12px;font-size:.82rem;font-weight:700;text-align:center;text-transform:uppercase;letter-spacing:.08em;color:#64748b;border-bottom:2px solid #f1f5f9;background:#fafafa;position:sticky;top:0;z-index:2}
        .cal-head-cell.today-col{color:#1A3C5A;border-bottom-color:#2E86AB}
        .cal-head-cell.corner{background:#fafafa;border-right:1px solid #f1f5f9}

        /* Time column */
        .cal-time-col{display:contents}
        .time-label{padding:0 12px 0 8px;font-size:.75rem;color:#94a3b8;text-align:right;border-right:1px solid #f1f5f9;position:relative;height:60px;display:flex;align-items:flex-start;padding-top:6px;box-sizing:border-box}

        /* Day column */
        .day-col{position:relative;border-right:1px solid #f1f5f9;border-bottom:1px solid #f1f5f9}
        .day-col:last-child{border-right:none}
        .day-col.today-col{background:#f0f7fb}

        /* Hour dividers */
        .hour-line{position:absolute;left:0;right:0;border-top:1px solid #f1f5f9;height:0}
        .hour-line.half{border-top-style:dashed;border-color:#f8fafc}

        /* Slot card */
        .ev-card{position:absolute;left:4px;right:4px;border-radius:10px;border-left:4px solid;padding:8px 10px;box-sizing:border-box;overflow:hidden;cursor:default;transition:transform .15s,box-shadow .15s}
        .ev-card:hover{transform:translateY(-1px);box-shadow:0 4px 18px rgba(0,0,0,.12)}
        .ev-card .ev-time{font-size:.72rem;font-weight:600;opacity:.7;margin-bottom:4px}
        .ev-card .ev-subject{font-size:.85rem;font-weight:700;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .ev-card .ev-class{font-size:.78rem;opacity:.75;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

        /* Empty state */
        .cal-empty{text-align:center;padding:48px 24px;color:#94a3b8}
        .cal-empty i{font-size:2.5rem;display:block;margin-bottom:12px}

        /* Legend */
        .legend{display:flex;flex-wrap:wrap;gap:10px;margin-top:20px}
        .legend-item{display:flex;align-items:center;gap:7px;font-size:.82rem;color:#475569;background:#f8fafc;padding:6px 12px;border-radius:8px}
        .legend-dot{width:12px;height:12px;border-radius:3px;flex-shrink:0}

        @media(max-width:640px){.cal-grid{grid-template-columns:48px repeat(6,minmax(80px,1fr))}}
    </style>
</head>
<body>
<div class="student-shell">
    <?php include '_sidebar.php'; ?>

    <main class="student-main">
        <section class="page-header page-header-schedule">
            <div>
                <p class="eyebrow">Emploi du temps</p>
                <h1>Planning de vos cours</h1>
                <p>Vue hebdomadaire · <?= htmlspecialchars($profData['nom_matiere']) ?></p>
            </div>
            <div class="header-user-card">
                <strong>Prof. <?= htmlspecialchars($profData['prenom'].' '.$profData['nom']) ?></strong>
                <span><?= htmlspecialchars($profData['nom_matiere']) ?></span>
            </div>
        </section>

        <section class="section-block">
            <!-- Toolbar -->
            <div class="sched-toolbar">
                <h2 id="weekLabel"></h2>
                <div class="toolbar-right">
                    <button class="btn-today" onclick="goToday()"><i class='bx bx-calendar-check' style="margin-right:5px;"></i>Aujourd'hui</button>
                    <select class="toolbar-select" id="filterMatiere" onchange="applyFilter()">
                        <option value="">Toutes les matières</option>
                        <?php
                        $seen = [];
                        foreach ($mySchedule as $s) {
                            if (!in_array($s['matiere_nom'], $seen)) {
                                $seen[] = $s['matiere_nom'];
                                echo '<option value="'.htmlspecialchars($s['matiere_nom']).'">'.htmlspecialchars($s['matiere_nom']).'</option>';
                            }
                        }
                        ?>
                    </select>
                    <a href="export_schedule.php" target="_blank"
                       style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;background:#1A3C5A;color:white;border-radius:10px;text-decoration:none;font-weight:700;font-size:.88rem;">
                        <i class='bx bx-download'></i>Exporter PDF
                    </a>
                    <select class="toolbar-select" id="filterClasse" onchange="applyFilter()">
                        <option value="">Toutes les classes</option>
                        <?php
                        $seenC = [];
                        foreach ($mySchedule as $s) {
                            if (!in_array($s['classe_nom'], $seenC)) {
                                $seenC[] = $s['classe_nom'];
                                echo '<option value="'.htmlspecialchars($s['classe_nom']).'">'.htmlspecialchars($s['classe_nom']).'</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
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
                            <div class="day-col <?= $day===$todayName?'today-col':'' ?>" style="height:60px;">
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
                                        <div class="ev-class"><?= htmlspecialchars($slot['classe_nom']) ?></div>
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
                    <p>Aucun créneau trouvé pour votre profil.</p>
                    <p style="font-size:.9rem;">Contactez l'administrateur pour configurer votre emploi du temps.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>

<script>
// Label semaine courante
function goToday() {
    const now = new Date();
    document.getElementById('weekLabel').textContent = 'Semaine du ' + now.toLocaleDateString('fr-FR', {weekday:'long',day:'numeric',month:'long',year:'numeric'});
}
goToday();

// Filtre matière / classe
function applyFilter() {
    const mat = document.getElementById('filterMatiere').value.toLowerCase();
    const cls = document.getElementById('filterClasse').value.toLowerCase();
    document.querySelectorAll('.ev-card').forEach(card => {
        const m = card.dataset.matiere.toLowerCase();
        const c = card.dataset.classe.toLowerCase();
        const show = (!mat || m === mat) && (!cls || c === cls);
        card.style.opacity = show ? '1' : '0.15';
        card.style.pointerEvents = show ? '' : 'none';
    });
}
</script>
</body>
</html>
