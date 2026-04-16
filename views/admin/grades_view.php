<?php
require_once '../../config/auth.php';
requireRole('Admin');
require_once '../../config/database.php';
require_once '../../models/Grade.php';

$database = new Database();
$db = $database->getConnection();
$gradeModel = new Grade($db);

$selected_classe = isset($_GET['id_classe']) ? $_GET['id_classe'] : null;
$classes = $db->query("SELECT * FROM classe")->fetchAll(PDO::FETCH_ASSOC);

$pv = [];
if ($selected_classe) {
    $pv = $gradeModel->getClassPV($selected_classe);
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>PV de Délibération - SIGES</title>
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
                    <span>Espace Admin</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php"><i class='bx bx-home'></i>Dashboard</a>
                <a href="users.php"><i class='bx bx-user-circle'></i>Gestion utilisateurs</a>
                <a href="grades_view.php" class="active"><i class='bx bx-book'></i>PV Délibération</a>
                <a href="schedule.php"><i class='bx bx-calendar'></i>Emploi du temps</a>
            </nav>

            <a href="../../controllers/Logout.php" class="logout-btn"><i class='bx bx-log-out'></i>Déconnexion</a>
        </aside>

        <main class="student-main">
            <section class="page-header page-header-reclamation">
                <div>
                    <p class="eyebrow">PV de délibération</p>
                    <h1>Classement officiels</h1>
                    <p>Consultez les résultats de chaque classe et imprimez le PV officiel directement.</p>
                </div>
                <div class="header-user-card">
                    <strong>Administrateur</strong>
                    <span>Vision globale</span>
                </div>
            </section>

            <section class="section-block">
                <div class="section-title-row">
                    <h2>Classe sélectionnée</h2>
                </div>
                <div class="filter-section" style="background: rgba(226, 232, 240, 0.8);">
                    <form method="GET" style="display: flex; gap: 10px; align-items: center; width: 100%;">
                        <label>Choisir une classe :</label>
                        <select name="id_classe" onchange="this.form.submit()" style="flex:1; min-width:180px;">
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['Id_Classe'] ?>" <?= $selected_classe == $c['Id_Classe'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['libelle']) ?> (<?= htmlspecialchars($c['niveau']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <button class="button-primary" onclick="window.print()">Imprimer le PV</button>
                </div>

                <?php if ($selected_classe && !empty($pv)): ?>
                    <div class="table-card">
                        <table>
                            <thead>
                                <tr>
                                    <th>Rang</th>
                                    <th>Nom & Prénom</th>
                                    <th>Moyenne Générale</th>
                                    <th>Décision</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; foreach ($pv as $row): ?>
                                    <tr>
                                        <td><?= $rank++ ?></td>
                                        <td><?= htmlspecialchars($row['nom']) ?> <?= htmlspecialchars($row['prenom']) ?></td>
                                        <td><?= number_format($row['moyenne_generale'], 2) ?> / 20</td>
                                        <td>
                                            <?php if ($row['moyenne_generale'] >= 10): ?>
                                                <span class="badge badge-success">ADMIS</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">AJOURNÉ</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif ($selected_classe): ?>
                    <p>Aucune note enregistrée pour cette classe pour le moment.</p>
                <?php else: ?>
                    <p>Veuillez sélectionner une classe pour afficher les résultats.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>

</html>