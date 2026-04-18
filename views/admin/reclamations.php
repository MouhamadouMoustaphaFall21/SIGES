<?php
require_once '../../config/auth.php';
requireRole('Professeur');
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();
$pendingReclamations = 0;
try {
    $hasReclamTable = (int) $db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'reclamation'")->fetchColumn();
    if ($hasReclamTable) {
        $pendingReclamations = (int) $db->query("SELECT COUNT(*) FROM reclamation WHERE statut = 'En attente'")->fetchColumn();
    }
} catch (PDOException $e) {
    $pendingReclamations = 0;
}
$tableExists = false;
$reclamations = [];

try {
    $stmt = $db->prepare("SHOW TABLES LIKE 'reclamation'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;

    if ($tableExists) {
        $query = "SELECT r.id_reclamation, r.motif, r.statut, e.nom AS etu_nom, e.prenom AS etu_prenom,
                         m.libelle AS matiere, ev.semestre
                  FROM reclamation r
                  LEFT JOIN etudiant e ON e.id_Etudiant = r.id_Etudiant
                  LEFT JOIN evaluation ev ON ev.Id_Evaluation = r.Id_Evaluation
                  LEFT JOIN matiere m ON m.Id_Matiere = ev.Id_Matiere
                  ORDER BY r.statut ASC, r.id_reclamation DESC";
        $reclamations = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $tableExists = false;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réclamations - SIGES Professeur</title>
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
                <a href="../professeur/dashboard.php"><i class='bx bx-home'></i>Dashboard</a>
                <a href="grades_view.php"><i class='bx bx-book'></i>PV Délibération</a>
                <a href="../professeur/schedule.php"><i class='bx bx-calendar'></i>Emploi du temps</a>
                <a href="reclamations.php" class="active"><i class='bx bx-message-square-detail'></i>Réclamations</a>
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
            <section class="page-header page-header-dashboard">
                <div>
                    <p class="eyebrow">Réclamations</p>
                    <h1>Demandes des étudiants</h1>
                    <p>Consultez et suivez les réclamations envoyées par les étudiants concernant leurs notes.</p>
                </div>
                <div class="header-user-card">
                    <strong>Administrateur</strong>
                    <span>Gestion des réclamations</span>
                </div>
            </section>

            <section class="section-block">
                <?php if (!$tableExists): ?>
                    <div class="form-hint" style="border-color:#fee2e2;background:#fff1f2;color:#9b1c1c;">
                        <strong>Table de réclamations introuvable.</strong>
                        <p>La table "reclamation" n'existe pas dans la base de données. Les réclamations ne peuvent pas être affichées pour le moment.</p>
                    </div>
                <?php elseif (empty($reclamations)): ?>
                    <div class="form-hint" style="border-color:#dbeafe;background:#eff6ff;color:#1e3a8a;">
                        <strong>Aucune réclamation pour le moment.</strong>
                        <p>Les étudiants n'ont pas encore soumis de réclamation.</p>
                    </div>
                <?php else: ?>
                    <div class="table-card">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Étudiant</th>
                                    <th>Matière</th>
                                    <th>Semestre</th>
                                    <th>Motif</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reclamations as $rec): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($rec['id_reclamation']) ?></td>
                                        <td><?= htmlspecialchars($rec['etu_prenom'] . ' ' . $rec['etu_nom']) ?></td>
                                        <td><?= htmlspecialchars($rec['matiere'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($rec['semestre'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($rec['motif']) ?></td>
                                        <td><?= htmlspecialchars($rec['statut']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>