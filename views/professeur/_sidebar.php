<?php

$_sid_classe = isset($selected_classe) ? intval($selected_classe) : 0;
$_sid_page   = isset($active_page) ? $active_page : '';
$_initials   = strtoupper(substr($profData['prenom'], 0, 1) . substr($profData['nom'], 0, 1));
?>
<aside class="student-sidebar">
    <div class="sidebar-brand">
        <img src="../../assets/img/logo_simple-SAP.png" alt="SIGES logo">
        <div class="brand-title">
            <strong>SIGES</strong>
            <span>Espace Enseignant</span>
        </div>
    </div>

    <div class="profile-box">
        <div class="profile-avatar"><?= htmlspecialchars($_initials) ?></div>
        <div class="profile-info">
            <h2><?= htmlspecialchars($profData['prenom'] . ' ' . $profData['nom']) ?></h2>
            <p><?= htmlspecialchars($profData['nom_matiere']) ?></p>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="dashboard.php<?= $_sid_classe ? '?id_classe='.$_sid_classe : '' ?>"
           class="<?= $_sid_page === 'dashboard' ? 'active' : '' ?>">
            <i class='bx bx-grid-alt'></i>Dashboard
        </a>
        <a href="schedule.php"
           class="<?= $_sid_page === 'schedule' ? 'active' : '' ?>">
            <i class='bx bx-calendar'></i>Emploi du temps
        </a>
        <a href="grades_entry.php<?= $_sid_classe ? '?id_classe='.$_sid_classe : '' ?>"
           class="<?= $_sid_page === 'grades_entry' ? 'active' : '' ?>">
            <i class='bx bx-edit'></i>Saisir notes
        </a>
        <a href="view_students.php<?= $_sid_classe ? '?id_classe='.$_sid_classe : '' ?>"
           class="<?= $_sid_page === 'view_students' ? 'active' : '' ?>">
            <i class='bx bx-group'></i>Mes élèves
        </a>
        <a href="view_grades.php<?= $_sid_classe ? '?id_classe='.$_sid_classe : '' ?>"
           class="<?= $_sid_page === 'view_grades' ? 'active' : '' ?>">
            <i class='bx bx-bar-chart-alt-2'></i>Classement
        </a>
    </nav>

    <a href="../../controllers/Logout.php" class="logout-btn">
        <i class='bx bx-log-out'></i>Déconnexion
    </a>
</aside>
