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
    <script>
        window.downloadSchedulePDF = function(filename = 'emploi-du-temps.pdf') {
            window.print();
        };

        window.downloadNotesPDF = function(filename = 'notes.pdf') {
            window.print();
        };
    </script>
    <style>
        @media print {
            body { background: #fff; margin: 0; color: #1a202c; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
            .student-shell, .student-main { margin: 0; padding: 0; }
            .student-sidebar, .sidebar-nav, .logout-btn, .button-primary, .toolbar-right, .toolbar-select, .btn-today, .header-user-card, .sidebar-section, .section-title-row > form, .page-header, .schedule-actions, .sched-toolbar { display: none !important; }
            
            .section-block, .table-card, .cal-wrap, .schedule-sheet { border: none !important; box-shadow: none !important; background: transparent !important; padding: 0 !important; margin: 0 !important; }
            
            .cal-grid { width: 100% !important; border: 2px solid #2d3748 !important; grid-gap: 0; border-radius: 12px; overflow: visible !important; grid-auto-rows: 60px !important; }
            .cal-head-cell { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; color: #fff !important; border: 1px solid #2d3748 !important; font-size: .85rem; padding: .75rem .5rem; font-weight: 700 !important; text-transform: uppercase; letter-spacing: 0.5px; }
            .time-label { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important; color: #fff !important; border: 1px solid #2d3748 !important; font-size: .75rem; padding: .5rem .4rem; font-weight: 600; text-align: center; }
            .day-col { border: 1px solid #2d3748 !important; page-break-inside: avoid; background: #f7fafc; overflow: visible !important; min-height: 60px !important; height: auto !important; position: relative !important; }
            .ev-card { position: absolute !important; z-index: 2 !important; background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%) !important; border: 2px solid #2d3748 !important; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important; padding: .5rem .6rem; margin: 0 !important; font-size: .82rem; border-radius: 10px; page-break-inside: avoid !important; display: block !important; left: 10px !important; right: 10px !important; }
            .ev-card .ev-time { font-size: .75rem; font-weight: 700; margin-bottom: 6px; color: #2d3748; }
            .ev-card .ev-subject, .ev-card .ev-class { font-size: .82rem; color: #1a202c; white-space: normal !important; overflow-wrap: anywhere !important; word-break: break-word !important; }
            
            table { width: 100% !important; border-collapse: collapse !important; margin: 16px 0; }
            th { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; color: #fff !important; border: 2px solid #2d3748 !important; padding: 12px 10px !important; font-weight: 700 !important; font-size: .9rem !important; text-transform: uppercase; letter-spacing: 0.5px; }
            td { border: 1px solid #4a5568 !important; padding: 10px !important; text-align: center !important; font-size: .88rem !important; background: #f7fafc; }
            
            .schedule-sheet { margin: 20px auto; border: 3px solid #2d3748; border-radius: 16px; padding: 24px; background: #fff; }
            .schedule-title { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; color: #fff; padding: 20px; border-radius: 12px; font-size: 2rem; font-weight: 800; text-align: center; margin-bottom: 20px; box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2); }
            .schedule-box { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important; border: 2px solid #2d3748; border-radius: 12px; color: #fff; }
            .schedule-meta span { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%) !important; color: #2d3748; border: 1px solid #2d3748; }
        }

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

        .sched-toolbar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:24px}
        .sched-toolbar h2{margin:0;font-size:1.15rem;color:#1A3C5A}
        .toolbar-right{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
        .btn-today{background:#1A3C5A;color:white;border:none;padding:9px 18px;border-radius:10px;font-size:.9rem;font-weight:600;cursor:pointer;transition:background .2s}
        .btn-today:hover{background:#122b40}
        .toolbar-select{padding:8px 14px;border:1px solid #e2e8f0;border-radius:10px;font-size:.9rem;color:#334155;background:#fff;cursor:pointer}

        .cal-wrap{overflow-x:auto;border:1px solid #e2e8f0;border-radius:20px;background:#fff}
        .cal-grid{display:grid;grid-template-columns:84px repeat(6,minmax(140px,1fr));min-width:980px;grid-auto-rows:60px}

        .cal-head{display:contents}
        .cal-head-cell{padding:16px 12px;font-size:.86rem;font-weight:700;text-align:center;text-transform:uppercase;letter-spacing:.08em;color:#fff;border-right:1px solid rgba(255,255,255,.08);background:#1A3C5A;position:sticky;top:0;z-index:2}
        .cal-head-cell.corner{background:#f8fafc;color:#64748b;border-right:1px solid #e2e8f0;box-shadow:inset 0 -1px 0 rgba(15,23,42,.04)}
        .cal-head-cell.today-col{background:#2563eb}

        .cal-time-col{display:contents}
        .time-label{padding:0 12px;font-size:.78rem;color:#475569;font-weight:700;text-align:right;border-right:1px solid #e2e8f0;background:#f8fafc;display:flex;align-items:center;justify-content:flex-end}

        .day-col{position:relative;border-bottom:1px solid #e2e8f0;border-right:1px solid #e2e8f0;padding:0;min-height:60px;overflow:visible;background:#fff}
        .day-col:last-child{border-right:none}
        .day-col.today-col{background:#f7fbff}
        .day-col.day-highlight{background:rgba(56,189,248,0.06)}
        .cal-head-cell.day-highlight{background:#dbeafe;color:#1A3C5A;border-color:#bfdbfe}

        .hour-line{position:absolute;left:12px;right:12px;border-top:1px solid #f1f5f9;top:30px}
        .hour-line.half{border-top-style:dashed;border-color:#f8fafc}

        .ev-card{position:absolute;left:10px;right:10px;border-radius:16px;border-left:6px solid;padding:12px 12px;background:#eff6ff;color:#0f172a;box-shadow:0 12px 30px rgba(15,23,42,.06);transition:transform .15s,box-shadow .15s}
        .ev-card:hover{transform:translateY(-1px)}
        .ev-card .ev-time{font-size:.75rem;font-weight:700;opacity:.82;margin-bottom:6px}
        .ev-card .ev-subject{font-size:.96rem;font-weight:800;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .ev-card .ev-class{font-size:.78rem;opacity:.78;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

        .cal-empty{text-align:center;padding:48px 24px;color:#94a3b8}
        .cal-empty i{font-size:2.5rem;display:block;margin-bottom:12px}
        .legend{display:flex;flex-wrap:wrap;gap:10px;margin-top:20px}
        .legend-item{display:flex;align-items:center;gap:7px;font-size:.82rem;color:#475569;background:#f8fafc;padding:6px 12px;border-radius:8px}
        .legend-dot{width:12px;height:12px;border-radius:3px;flex-shrink:0}

        @media(max-width:768px){.cal-grid{grid-template-columns:72px repeat(6,minmax(120px,1fr))}}
        @media(max-width:640px){.cal-grid{grid-template-columns:56px repeat(6,minmax(90px,1fr))}}
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
            <div class="schedule-sheet">
                <div class="schedule-top">
                    <div class="schedule-box">
                        <label>Enseignant</label>
                        <strong>Prof. <?= htmlspecialchars($profData['prenom'].' '.$profData['nom']) ?></strong>
                    </div>
                    <div class="schedule-title">Mon emploi du temps</div>
                    <div class="schedule-box">
                        <label>Semaine n°</label>
                        <strong><?= date('W') ?></strong>
                    </div>
                </div>
                <div class="schedule-meta">
                    <span>Année scolaire <?= htmlspecialchars(date('Y') . '-' . (date('Y') + 1)) ?></span>
                    <span>Matière : <?= htmlspecialchars($profData['nom_matiere']) ?></span>
                </div>
                <div class="schedule-actions">
                    <button onclick="downloadSchedulePDF('emploi-du-temps-professeur.pdf')" class="button-primary button-small" style="border:none;cursor:pointer;">Télécharger PDF</button>
                </div>

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
                            <div class="cal-head-cell <?= $day===$todayName ? 'today-col' : '' ?> <?= in_array($day, ['Lundi','Mardi','Mercredi','Jeudi','Samedi']) ? 'day-highlight' : '' ?>">
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
            <div class="schedule-footer">
                <strong>Notes :</strong> ____________________________________________
            </div>

            <?php else: ?>
                <div class="cal-empty">
                    <i class='bx bx-calendar-x'></i>
                    <p>Aucun créneau trouvé pour votre profil.</p>
                    <p style="font-size:.9rem;">Contactez l'administrateur pour configurer votre emploi du temps.</p>
                </div>
            <?php endif; ?>
            </div>
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

if (new URLSearchParams(window.location.search).get('auto') === '1') {
    window.addEventListener('load', () => setTimeout(() => window.print(), 500));
}
</script>
</body>
</html>
