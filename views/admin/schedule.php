<?php
require_once '../../config/auth.php';
requireRole('Admin');
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Données pour les listes déroulantes
$classes = $db->query("SELECT * FROM classe")->fetchAll(PDO::FETCH_ASSOC);
$profs = $db->query("SELECT * FROM professeur")->fetchAll(PDO::FETCH_ASSOC);
$matieres = $db->query("SELECT * FROM matiere")->fetchAll(PDO::FETCH_ASSOC);

// Récupération du planning existant
require_once '../../models/Schedule.php';
$scheduleModel = new Schedule($db);
$allSchedules = $scheduleModel->getAllSchedules()->fetchAll(PDO::FETCH_ASSOC);

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

$editSlot = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editSlot = $scheduleModel->getById(intval($_GET['edit']));
}

$statusMessage = '';
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'success':
            $statusMessage = 'Créneau ajouté avec succès.';
            break;
        case 'updated':
            $statusMessage = 'Créneau mis à jour avec succès.';
            break;
        case 'deleted':
            $statusMessage = 'Créneau supprimé avec succès.';
            break;
        case 'error':
            $statusMessage = 'Une erreur est survenue. Vérifiez les champs et réessayez.';
            break;
    }
}

$days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
$scheduleGrid = [];
$timeSlots = [];
foreach ($allSchedules as $slot) {
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
    <title>Gestion Emploi du Temps - SIGES</title>
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
        .grid-header.day-highlight { background: rgba(56,189,248,0.12); color: #1A3C5A; }
        .schedule-cell.day-highlight { background: rgba(56,189,248,0.08); }

        @media print {
            body { background: #fff; margin: 0; color: #1a202c; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
            .student-shell, .student-main { margin: 0; padding: 0; }
            .student-sidebar, .sidebar-nav, .logout-btn, .button-primary, .toolbar-right, .toolbar-select, .btn-today, .sidebar-section, .filter-section { display: none !important; }
            
            .page-header { display: block !important; margin: 0 0 20px; padding: 16px 0; border-bottom: 3px solid #2d3748; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border-radius: 0 0 12px 12px; }
            .page-header .eyebrow { display: block; font-size: .95rem; color: rgba(255,255,255,0.9); margin-bottom: 6px; font-weight: 600; }
            .page-header h1 { font-size: 1.6rem; margin: 0; color: #fff; font-weight: 700; }
            .page-header p { font-size: .95rem; margin: 6px 0 0; color: rgba(255,255,255,0.9); }
            
            .section-block { border: none !important; box-shadow: none !important; background: transparent !important; padding: 0 !important; margin: 0 !important; }
            .table-card { border: 3px solid #2d3748 !important; box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important; background: #fff !important; border-radius: 16px; overflow: hidden; }
            
            table { width: 100% !important; border-collapse: collapse !important; margin: 16px 0; }
            th { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; color: #fff !important; border: 2px solid #2d3748 !important; padding: 14px 12px !important; text-align: center !important; font-weight: 700 !important; font-size: .95rem !important; text-transform: uppercase; letter-spacing: 0.5px; }
            td { border: 1px solid #4a5568 !important; padding: 12px !important; text-align: center !important; font-size: .9rem !important; background: #f7fafc; }
            tr:nth-child(even) { background: linear-gradient(135deg, #f093fb 0%, #f5576c 50%, #a8edea 100%) !important; opacity: 0.8; }
            
            .section-title-row { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%) !important; padding: 16px 20px; border-radius: 12px; margin-bottom: 20px; border: 2px solid #2d3748; }
            .section-title-row h2 { color: #2d3748; font-size: 1.4rem; font-weight: 700; margin: 0; }
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
                    <span>Espace Admin</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php"><i class='bx bx-home'></i>Dashboard</a>
                <a href="users.php"><i class='bx bx-user-circle'></i>Gestion utilisateurs</a>
                <a href="grades_view.php"><i class='bx bx-book'></i>PV Délibération</a>
                <a href="schedule.php" class="active"><i class='bx bx-calendar'></i>Emploi du temps</a>
            </nav>

            <div class="sidebar-section">
                <h3>Créateur</h3>
                <div class="course-list">
                    <a href="creators.php">Crédits</a>
                </div>
            </div>

            <a href="../../controllers/Logout.php" class="logout-btn"><i class='bx bx-log-out'></i>Déconnexion</a>
        </aside>

        <main class="student-main">
            <section class="page-header page-header-schedule">
                <div>
                    <p class="eyebrow">Emploi du temps</p>
                    <h1>Gestion du planning</h1>
                    <p>Ajoutez des créneaux et suivez le planning global de l'établissement.</p>
                </div>
                <div class="header-user-card">
                    <strong>Administrateur</strong>
                    <span>Planification</span>
                </div>
            </section>

            <section class="section-block">
                <?php if ($statusMessage): ?>
                    <div class="form-hint" style="margin-bottom: 18px;"><?= htmlspecialchars($statusMessage) ?></div>
                <?php endif; ?>
                <div class="section-title-row" style="justify-content: space-between; align-items: center; gap: 16px;">
                    <h2><?= $editSlot ? 'Modifier un créneau' : 'Ajouter un créneau' ?></h2>
                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <button onclick="downloadSchedulePDF('emploi-du-temps-admin.pdf')" class="button-primary button-small" style="white-space: nowrap; border:none; cursor:pointer;">Télécharger PDF</button>
                    </div>
                </div>
                <?php if ($editSlot): ?>
                    <div class="schedule-toolbar">
                        <span class="schedule-selected-label"><i class="bx bx-check-circle"></i> Créneau sélectionné : <?= htmlspecialchars($editSlot['matiere_nom']) ?> - <?= htmlspecialchars($editSlot['jour']) ?> <?= substr($editSlot['heure_debut'], 0, 5) ?> / <?= substr($editSlot['heure_fin'], 0, 5) ?></span>
                        <a href="#slot-form" class="button-soft button-small button-small-icon" title="Modifier">Modifier</a>
                        <form method="POST" style="display:inline-flex; gap:8px; align-items:center;">
                            <input type="hidden" name="action" value="delete_slot">
                            <input type="hidden" name="id_slot" value="<?= htmlspecialchars($editSlot['Id_Creneau']) ?>">
                            <button type="submit" class="button-danger button-small button-small-icon" title="Supprimer" onclick="return confirm('Voulez-vous supprimer ce créneau ?')">
                                Supprimer
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
                <form id="slot-form" action="../../controllers/ScheduleController.php" method="POST">
                    <input type="hidden" name="action" value="<?= $editSlot ? 'update_slot' : 'add_slot' ?>">
                    <?php if ($editSlot): ?>
                        <input type="hidden" name="id_slot" value="<?= htmlspecialchars($editSlot['Id_Creneau']) ?>">
                    <?php endif; ?>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Jour</label>
                            <select name="jour" required>
                                <option value="Lundi" <?= $editSlot && $editSlot['jour'] === 'Lundi' ? 'selected' : '' ?>>Lundi</option>
                                <option value="Mardi" <?= $editSlot && $editSlot['jour'] === 'Mardi' ? 'selected' : '' ?>>Mardi</option>
                                <option value="Mercredi" <?= $editSlot && $editSlot['jour'] === 'Mercredi' ? 'selected' : '' ?>>Mercredi</option>
                                <option value="Jeudi" <?= $editSlot && $editSlot['jour'] === 'Jeudi' ? 'selected' : '' ?>>Jeudi</option>
                                <option value="Vendredi" <?= $editSlot && $editSlot['jour'] === 'Vendredi' ? 'selected' : '' ?>>Vendredi</option>
                                <option value="Samedi" <?= $editSlot && $editSlot['jour'] === 'Samedi' ? 'selected' : '' ?>>Samedi</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Heure début</label>
                            <input type="time" name="heure_debut" required value="<?= htmlspecialchars($editSlot['heure_debut'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Heure fin</label>
                            <input type="time" name="heure_fin" required value="<?= htmlspecialchars($editSlot['heure_fin'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Classe</label>
                            <select name="id_classe" required>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['Id_Classe'] ?>" <?= $editSlot && $editSlot['Id_Classe'] == $c['Id_Classe'] ? 'selected' : '' ?>><?= htmlspecialchars($c['libelle']) ?> (<?= htmlspecialchars($c['niveau']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Matière</label>
                            <select name="id_matiere" required>
                                <?php foreach ($matieres as $m): ?>
                                    <option value="<?= $m['Id_Matiere'] ?>" <?= $editSlot && $editSlot['Id_Matiere'] == $m['Id_Matiere'] ? 'selected' : '' ?>><?= htmlspecialchars($m['libelle']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Enseignant</label>
                            <select name="id_professeur" required>
                                <?php foreach ($profs as $p): ?>
                                    <option value="<?= $p['Id_Professeur'] ?>" <?= $editSlot && $editSlot['Id_Professeur'] == $p['Id_Professeur'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nom']) ?> <?= htmlspecialchars($p['prenom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-full" style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
                            <button type="submit" class="button-primary button-small button-small-icon" title="Enregistrer">
                                Enregistrer
                            </button>
                            <?php if ($editSlot): ?>
                                <a href="schedule.php" class="button-soft button-small button-small-icon" title="Annuler">
                                    Annuler
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </section>

            <section class="section-block">
                <div class="section-title-row">
                    <h2>Planning hebdomadaire</h2>
                </div>
                <?php if (count($allSchedules) > 0): ?>
                    <div class="schedule-grid">
                        <div class="grid-header"></div>
                        <?php foreach ($days as $day): ?>
                            <div class="grid-header <?= in_array($day, ['Lundi','Mardi','Mercredi','Jeudi','Samedi']) ? 'day-highlight' : '' ?>"><?= $day ?></div>
                        <?php endforeach; ?>

                        <?php foreach ($timeSlots as $time): ?>
                            <div class="grid-time"><?= $time ?></div>
                            <?php foreach ($days as $day): ?>
                                <div class="schedule-cell <?= in_array($day, ['Lundi','Mardi','Mercredi','Jeudi','Samedi']) ? 'day-highlight' : '' ?>">
                                    <?php if (!empty($scheduleGrid[$time][$day])): ?>
                                        <?php foreach ($scheduleGrid[$time][$day] as $slot): ?>
                                            <?php $slotColor = getScheduleColor($slot['Id_Matiere']); ?>
                                            <a href="?edit=<?= htmlspecialchars($slot['Id_Creneau']) ?>" class="schedule-slot-link <?= $editSlot && $editSlot['Id_Creneau'] == $slot['Id_Creneau'] ? 'selected-slot-link' : '' ?>">
                                                <div class="schedule-slot" style="background: <?= $slotColor['bg'] ?>; border-color: <?= $slotColor['border'] ?>; color: <?= $slotColor['text'] ?>;">
                                                    <strong><?= htmlspecialchars($slot['matiere_nom']) ?></strong>
                                                    <span><?= htmlspecialchars($slot['classe_nom']) ?> (<?= htmlspecialchars($slot['niveau']) ?>)</span>
                                                    <span>Prof. <?= htmlspecialchars($slot['prof_nom']) ?></span>
                                                    <span class="schedule-slot-label">Cliquez pour sélectionner</span>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="cell-empty">Libre</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="form-hint">Aucun créneau défini pour le moment.</div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>

</html>