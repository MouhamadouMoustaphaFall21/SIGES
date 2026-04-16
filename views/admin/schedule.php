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
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Gestion Emploi du Temps - SIGES</title>
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
                <a href="grades_view.php"><i class='bx bx-book'></i>PV Délibération</a>
                <a href="schedule.php" class="active"><i class='bx bx-calendar'></i>Emploi du temps</a>
            </nav>

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
                <div class="section-title-row">
                    <h2>Ajouter un créneau</h2>
                </div>
                <form action="../../controllers/ScheduleController.php" method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Jour</label>
                            <select name="jour" required>
                                <option value="Lundi">Lundi</option>
                                <option value="Mardi">Mardi</option>
                                <option value="Mercredi">Mercredi</option>
                                <option value="Jeudi">Jeudi</option>
                                <option value="Vendredi">Vendredi</option>
                                <option value="Samedi">Samedi</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Heure début</label>
                            <input type="time" name="heure_debut" required>
                        </div>
                        <div class="form-group">
                            <label>Heure fin</label>
                            <input type="time" name="heure_fin" required>
                        </div>
                        <div class="form-group">
                            <label>Classe</label>
                            <select name="id_classe" required>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['Id_Classe'] ?>"><?= htmlspecialchars($c['libelle']) ?> (<?= htmlspecialchars($c['niveau']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Matière</label>
                            <select name="id_matiere" required>
                                <?php foreach ($matieres as $m): ?>
                                    <option value="<?= $m['Id_Matiere'] ?>"><?= htmlspecialchars($m['libelle']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Enseignant</label>
                            <select name="id_professeur" required>
                                <?php foreach ($profs as $p): ?>
                                    <option value="<?= $p['Id_Professeur'] ?>"><?= htmlspecialchars($p['nom']) ?> <?= htmlspecialchars($p['prenom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-full">
                            <button type="submit" class="button-primary">Ajouter ce créneau au planning</button>
                        </div>
                    </div>
                </form>
            </section>

            <section class="section-block">
                <div class="section-title-row">
                    <h2>Planning global</h2>
                </div>
                <div class="table-card">
                    <table>
                        <thead>
                            <tr>
                                <th>Jour</th>
                                <th>Horaire</th>
                                <th>Classe</th>
                                <th>Matière</th>
                                <th>Enseignant</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allSchedules as $s): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($s['jour']) ?></strong></td>
                                    <td><?= substr($s['heure_debut'], 0, 5) ?> - <?= substr($s['heure_fin'], 0, 5) ?></td>
                                    <td><?= htmlspecialchars($s['classe_nom']) ?> (<?= htmlspecialchars($s['niveau']) ?>)</td>
                                    <td><?= htmlspecialchars($s['matiere_nom']) ?></td>
                                    <td>Prof. <?= htmlspecialchars($s['prof_nom']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</body>

</html>