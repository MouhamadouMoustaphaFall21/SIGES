<?php
/**
 * Dashboard Enseignant - SIGES
 */
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Professeur') {
    header("Location: ../../index.php");
    exit();
}

require_once '../../config/database.php';
require_once '../../models/Teacher.php';
require_once '../../models/Grade.php';

$database = new Database();
$db = $database->getConnection();
$teacherModel = new Teacher($db);
$gradeModel   = new Grade($db);

$profData  = $teacherModel->getProfileByLogin($_SESSION['user_login']);
$id_prof   = $profData['Id_Professeur'];

$classesStmt = $teacherModel->getAssignedClasses($id_prof);
$classes     = $classesStmt->fetchAll(PDO::FETCH_ASSOC);

$selected_classe    = isset($_GET['id_classe']) ? intval($_GET['id_classe']) : ($classes[0]['Id_Classe'] ?? null);
$teacherSummary     = $gradeModel->getTeacherClassSummary($id_prof);
$distribution       = $selected_classe
    ? $gradeModel->getClassDistribution($selected_classe)
    : ['0-7' => 0, '8-9.99' => 0, '10-12.99' => 0, '13-15.99' => 0, '16-20' => 0];
$evaluations        = $gradeModel->getTeacherEvaluations($id_prof);


$statusType    = 'info';
$statusMessage = '';
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'eval_created':     $statusMessage = 'Évaluation créée avec succès.'; $statusType = 'success'; break;
        case 'student_added':    $statusMessage = 'Étudiant ajouté avec succès.';  $statusType = 'success'; break;

        case 'error':            $statusMessage = 'Une erreur est survenue. Vérifiez les champs.'; $statusType = 'error'; break;
    }
}

$chartLabels = $chartAverages = $chartSuccess = [];
foreach ($teacherSummary as $item) {
    $chartLabels[]   = htmlspecialchars($item['libelle'] . ' ' . $item['niveau']);
    $chartAverages[] = round(floatval($item['moyenne_classe']), 2);
    $chartSuccess[]  = round(floatval($item['taux_reussite']), 2);
}

$active_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace Enseignant - SIGES</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .toast{display:flex;align-items:center;gap:12px;padding:14px 20px;border-radius:12px;font-size:.95rem;font-weight:500;margin-bottom:20px;animation:slideIn .35s ease}
        .toast-success{background:#d1fae5;color:#065f46;border:1px solid #6ee7b7}
        .toast-error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
        .toast-info{background:#dbeafe;color:#1e3a8a;border:1px solid #93c5fd}
        @keyframes slideIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
        .export-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px}
        @media(max-width:768px){.export-grid{grid-template-columns:1fr}}
        .export-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:28px;display:flex;flex-direction:column;gap:16px}
        .export-card h3{margin:0;font-size:1rem;color:#1A3C5A;display:flex;align-items:center;gap:9px}
        .export-card p{margin:0;font-size:.88rem;color:#64748b;line-height:1.6}
        .export-btn{display:flex;align-items:center;justify-content:center;gap:8px;padding:13px 20px;border-radius:11px;font-size:.93rem;font-weight:700;text-decoration:none;transition:background .2s,transform .15s;cursor:pointer;border:none}
        .export-btn:hover{transform:translateY(-1px)}
        .export-btn-primary{background:#1A3C5A;color:white}
        .export-btn-primary:hover{background:#122b40}
        .export-btn-secondary{background:#f1f5f9;color:#1A3C5A;border:1px solid #e2e8f0}
        .export-btn-secondary:hover{background:#e2e8f0}
        .select-inline{padding:8px 12px;border:1px solid #e2e8f0;border-radius:9px;font-size:.88rem;color:#334155;background:#f8fafc;width:100%}
    </style>
</head>
<body>
<div class="student-shell">
    <?php include '_sidebar.php'; ?>

    <main class="student-main">
        <section class="page-header page-header-dashboard">
            <div>
                <p class="eyebrow">Tableau de bord</p>
                <h1>Bonjour <?= htmlspecialchars($profData['prenom']) ?>,</h1>
                <p>Retrouvez vos classes, évaluations et statistiques de réussite.</p>
                <?php if ($statusMessage): ?>
                    <div class="toast toast-<?= $statusType ?>">
                        <i class='bx <?= $statusType==='success'?'bx-check-circle':($statusType==='error'?'bx-x-circle':'bx-info-circle') ?>'></i>
                        <?= htmlspecialchars($statusMessage) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="header-user-card">
                <strong>Prof. <?= htmlspecialchars($profData['prenom'].' '.$profData['nom']) ?></strong>
                <span><?= htmlspecialchars($profData['nom_matiere']) ?></span>
            </div>
        </section>

        <section class="stats-grid">
            <article class="stat-card" style="background:linear-gradient(135deg,#122b40 20%,rgba(235,238,242,.95) 95%);">
                <h3 style="color:white;">Classes affectées</h3>
                <p class="stat-value" style="color:white;"><?= count($classes) ?></p>
                <span class="badge badge-soft" style="color:white;">Total</span>
            </article>
            <article class="stat-card" style="background:linear-gradient(135deg,#2691be 20%,rgba(241,241,241,.95) 95%);">
                <h3 style="color:white;">Évaluations créées</h3>
                <p class="stat-value" style="color:white;"><?= count($evaluations) ?></p>
                <span class="badge badge-soft" style="color:white;">Dernières</span>
            </article>
            <article class="stat-card" style="background:linear-gradient(135deg,#00c45b 20%,rgba(248,248,248,.95) 95%);">
                <h3 style="color:white;">Taux de réussite</h3>
                <p class="stat-value" style="color:white;"><?= count($chartSuccess)?round(array_sum($chartSuccess)/count($chartSuccess),1):0 ?>%</p>
                <span class="badge badge-success" style="color:white;">Moyenne</span>
            </article>
        </section>

        <section class="section-block">
            <div class="section-title-row">
                <h2>Performances de mes classes</h2>
                <form method="GET" style="display:inline-flex;gap:12px;align-items:center;">
                    <label>Classe active :</label>
                    <select name="id_classe" onchange="this.form.submit()">
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['Id_Classe'] ?>" <?= $selected_classe==$c['Id_Classe']?'selected':'' ?>>
                                <?= htmlspecialchars($c['libelle'].' '.$c['niveau']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div class="chart-grid">
                <div class="chart-card"><canvas id="teacherSummaryChart"></canvas></div>
                <div class="chart-card"><canvas id="distributionChart"></canvas></div>
            </div>
        </section>

        <section class="section-block">
            <div class="section-title-row"><h2>Actions rapides</h2></div>
            <div class="form-grid">

                <div class="teacher-form-box">
                    <h3><i class='bx bx-calendar-plus' style="margin-right:8px;"></i>Créer une évaluation</h3>
                    <form action="../../controllers/TeacherController.php" method="POST">
                        <input type="hidden" name="action" value="create_evaluation">
                        <div class="form-group">
                            <label><i class='bx bx-group'></i> Classe concernée <span style="color:#e03e3e;">*</span></label>
                            <select name="id_classe" required>
                                <option value="">-- Choisir une classe --</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['Id_Classe'] ?>" <?= $selected_classe==$c['Id_Classe']?'selected':'' ?>>
                                        <?= htmlspecialchars($c['libelle'].' '.$c['niveau']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class='bx bx-calendar'></i> Date de l'évaluation</label>
                            <input type="date" name="date_eval" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="form-group">
                            <label><i class='bx bx-book'></i> Semestre</label>
                            <select name="semestre" required>
                                <option value="1">Semestre 1</option>
                                <option value="2">Semestre 2</option>
                            </select>
                        </div>
                        <button type="submit"><i class='bx bx-plus-circle' style="margin-right:8px;"></i>Créer l'évaluation</button>
                    </form>
                </div>

                <div class="teacher-form-box">
                    <h3><i class='bx bx-user-plus' style="margin-right:8px;"></i>Ajouter un étudiant</h3>
                    <form action="../../controllers/TeacherController.php" method="POST">
                        <input type="hidden" name="action" value="add_student">
                        <div class="form-group">
                            <label><i class='bx bx-user'></i> Nom</label>
                            <input type="text" name="nom" placeholder="Nom" required>
                        </div>
                        <div class="form-group">
                            <label><i class='bx bx-user'></i> Prénom</label>
                            <input type="text" name="prenom" placeholder="Prénom" required>
                        </div>
                        <div class="form-group">
                            <label><i class='bx bx-envelope'></i> Email</label>
                            <input type="email" name="login" placeholder="Email de connexion" required>
                        </div>
                        <div class="form-group">
                            <label><i class='bx bx-lock'></i> Mot de passe</label>
                            <input type="password" name="password" placeholder="Mot de passe" required>
                        </div>
                        <div class="form-group">
                            <label><i class='bx bx-group'></i> Classe</label>
                            <select name="id_classe" required>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['Id_Classe'] ?>"><?= htmlspecialchars($c['libelle'].' '.$c['niveau']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit"><i class='bx bx-user-plus' style="margin-right:8px;"></i>Créer l'étudiant</button>
                    </form>
                </div>
            </div>
        </section>

        <!-- Export PDF -->
        <section class="section-block">
            <div class="section-title-row">
                <h2><i class='bx bx-export' style="margin-right:8px;color:#2E86AB;"></i>Exporter en PDF</h2>
            </div>
            <div class="export-grid">

                <!-- Export notes -->
                <div class="export-card">
                    <h3><i class='bx bxs-file-pdf' style="color:#e03e3e;"></i>Exporter les notes</h3>
                    <p>Générez un document PDF imprimable avec les notes d'une évaluation ou le bulletin complet (classement + moyennes par matière).</p>

                    <div>
                        <label style="font-size:.82rem;font-weight:600;color:#475569;display:block;margin-bottom:6px;">Classe</label>
                        <select class="select-inline" id="exp_classe" onchange="updateEvalSelect()">
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['Id_Classe'] ?>" <?= $selected_classe==$c['Id_Classe']?'selected':'' ?>>
                                    <?= htmlspecialchars($c['libelle'].' '.$c['niveau']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label style="font-size:.82rem;font-weight:600;color:#475569;display:block;margin-bottom:6px;">Évaluation (optionnel)</label>
                        <select class="select-inline" id="exp_eval">
                            <option value="">— Bulletin complet (toutes les notes) —</option>
                            <?php foreach ($evaluations as $ev): ?>
                                <option value="<?= $ev['Id_Evaluation'] ?>">
                                    <?= htmlspecialchars($ev['matiere']) ?> · S<?= $ev['semestre'] ?> · <?= $ev['date_eval'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display:flex;gap:10px;">
                        <a id="btn_export_notes"
                           href="export_notes.php?id_classe=<?= $selected_classe ?>"
                           target="_blank"
                           class="export-btn export-btn-primary" style="flex:1;">
                            <i class='bx bx-download'></i>Aperçu &amp; Export
                        </a>
                        <a id="btn_export_notes_auto"
                           href="export_notes.php?id_classe=<?= $selected_classe ?>&auto=1"
                           target="_blank"
                           class="export-btn export-btn-secondary">
                            <i class='bx bx-printer'></i>Direct
                        </a>
                    </div>
                </div>

                <!-- Export emploi du temps -->
                <div class="export-card">
                    <h3><i class='bx bx-calendar-check' style="color:#2E86AB;"></i>Exporter l'emploi du temps</h3>
                    <p>Générez un document PDF de votre planning hebdomadaire, prêt à imprimer au format paysage (A4).</p>

                    <div style="background:#f0f7fb;border:1px solid #bfdbfe;border-radius:10px;padding:14px 16px;">
                        <p style="font-size:.85rem;color:#1e3a8a;font-weight:600;margin:0 0 4px;">
                            <i class='bx bx-info-circle'></i> Planning de : <?= htmlspecialchars($profData['prenom'].' '.$profData['nom']) ?>
                        </p>
                        <p style="font-size:.82rem;color:#3b5998;margin:0;">
                            Matière : <?= htmlspecialchars($profData['nom_matiere']) ?>
                        </p>
                    </div>

                    <div style="display:flex;gap:10px;margin-top:auto;">
                        <a href="export_schedule.php" target="_blank"
                           class="export-btn export-btn-primary" style="flex:1;">
                            <i class='bx bx-download'></i>Aperçu &amp; Export
                        </a>
                        <a href="export_schedule.php?auto=1" target="_blank"
                           class="export-btn export-btn-secondary">
                            <i class='bx bx-printer'></i>Direct
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="section-block">
            <div class="section-title-row"><h2>Dernières évaluations</h2></div>
            <div class="table-card">
                <table>
                    <thead>
                        <tr><th>Date</th><th>Semestre</th><th>Matière</th><th>Classe</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php if (count($evaluations) > 0): ?>
                            <?php foreach ($evaluations as $eval): ?>
                                <?php
                                    $evalClasse = $eval['Id_Classe'] ?? $selected_classe;
                                    $classeLabel = '';
                                    foreach ($classes as $c) { if ($c['Id_Classe'] == $evalClasse) { $classeLabel = $c['libelle']; break; } }
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($eval['date_eval']) ?></td>
                                    <td>S<?= htmlspecialchars($eval['semestre']) ?></td>
                                    <td><?= htmlspecialchars($eval['matiere']) ?></td>
                                    <td><?= $classeLabel ? htmlspecialchars($classeLabel) : '<span style="color:#94a3b8;">—</span>' ?></td>
                                    <td><a href="grades_entry.php?id_classe=<?= $evalClasse ?>&id_evaluation=<?= $eval['Id_Evaluation'] ?>" class="button-soft">Saisir notes</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center;padding:24px;">Aucune évaluation créée.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<script>
function updateEvalSelect() {
    const classe = document.getElementById('exp_classe').value;
    const eval_  = document.getElementById('exp_eval').value;
    const base   = 'export_notes.php?id_classe=' + classe + (eval_ ? '&id_evaluation=' + eval_ : '');
    document.getElementById('btn_export_notes').href      = base;
    document.getElementById('btn_export_notes_auto').href = base + '&auto=1';
}
document.getElementById('exp_eval').addEventListener('change', updateEvalSelect);
document.getElementById('exp_classe').addEventListener('change', updateEvalSelect);

new Chart(document.getElementById('teacherSummaryChart').getContext('2d'), {
    type:'line',
    data:{ labels:<?= json_encode($chartLabels) ?>, datasets:[
        {label:'Moyenne',borderColor:'#3B82F6',backgroundColor:'rgba(59,130,246,.2)',data:<?= json_encode($chartAverages) ?>,tension:.35,fill:true},
        {label:'Taux réussite (%)',borderColor:'#10B981',backgroundColor:'rgba(16,185,129,.2)',data:<?= json_encode($chartSuccess) ?>,tension:.35,fill:true}
    ]},
    options:{responsive:true,plugins:{legend:{position:'top'}},scales:{y:{beginAtZero:true,max:100}}}
});

new Chart(document.getElementById('distributionChart').getContext('2d'), {
    type:'doughnut',
    data:{labels:<?= json_encode(array_keys($distribution)) ?>,datasets:[{data:<?= json_encode(array_values($distribution)) ?>,backgroundColor:['#F87171','#FBBF24','#60A5FA','#34D399','#8B5CF6']}]},
    options:{responsive:true,plugins:{legend:{position:'bottom'}}}
});

const t = document.querySelector('.toast');
if (t) setTimeout(()=>{t.style.opacity='0';t.style.transition='opacity .5s';setTimeout(()=>t.remove(),500)},5000);
</script>
</body>
</html>
