<?php

/**
 * Page des créateurs - SIGES
 */
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: ../../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Créateurs - SIGES</title>
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
                <a href="schedule.php"><i class='bx bx-calendar'></i>Emploi du temps</a>
            </nav>

            <div class="sidebar-section">

                <h3>Créateur</h3>
                <div class="course-list">
                    <a href="creators.php" class="active">Crédits</a>
                </div>
            </div>

            <a href="../../controllers/Logout.php" class="logout-btn"><i class='bx bx-log-out'></i>Déconnexion</a>
        </aside>

        <main class="student-main">
            <section class="page-header page-header-dashboard">
                <div>
                    <p class="eyebrow">Crédits</p>
                    <h1>Créateurs du logiciel</h1>
                    <p>Découvrez l'équipe à l'origine de SIGES, leurs formations, l'université et ce que leur parcours en PHP leur a apporté.</p>
                </div>
                <div class="header-user-card">
                    <strong>Administrateur</strong>
                    <span>Crédits</span>
                </div>
            </section>

            <section class="section-block">
                <div class="section-title-row">
                    <h2>Université Iba der Thiam de Thiés</h2>
                </div>
                <div class="table-card" style="padding: 24px;">
                    <p>L'Université Iba der Thiam de Thiés est un établissement d'enseignement supérieur reconnu pour sa formation en informatique et ses programmes de licence en technologies numériques.</p>
                    <p>Les étudiants qui ont réalisé SIGES ont suivi une licence Informatique à l'Université Iba der Thiam de Thiés, où ils ont étudié les fondamentaux du PHP, le développement d'applications web, la gestion des bases de données et les bonnes pratiques de sécurité.</p>
                    <p>Cette formation leur a permis de concevoir des interfaces ergonomiques, de structurer des données efficacement et de développer une application métier complète pour la gestion scolaire.</p>
                </div>
            </section>

            <section class="section-block">
                <div class="section-title-row">
                    <h2>Équipe de créateurs (8 personnes)</h2>
                </div>
                <div class="chart-grid">
                    <div class="chart-card">
                        <h3>Sadio Ba</h3>
                        <p><strong>Diplôme :</strong> Licence Informatique</p>
                        <p><strong>Formation :</strong> Université Iba der Thiam de Thiés</p>
                        <p><strong>Spécialité :</strong> Développement backend PHP, architecture de base de données, sécurité.</p>
                        <p><strong>Ce que le cours PHP lui a apporté :</strong> une maîtrise des requêtes SQL, de l'authentification sécurisée et de la gestion des données utilisateurs.</p>
                    </div>
                    <div class="chart-card">
                        <h3>Mouhamadou Moustapha Fall</h3>
                        <p><strong>Diplôme :</strong> Licence Informatique</p>
                        <p><strong>Formation :</strong> Université Iba der Thiam de Thiés</p>
                        <p><strong>Spécialité :</strong> Interface utilisateur, ergonomie, intégration HTML/CSS.</p>
                        <p><strong>Ce que le cours PHP lui a apporté :</strong> la capacité à transformer des maquettes en applications dynamiques.</p>
                    </div>
                    <div class="chart-card">
                        <h3>Daouda Camara</h3>
                        <p><strong>Diplôme :</strong> Licence Informatique</p>
                        <p><strong>Formation :</strong> Université Iba der Thiam de Thiés</p>
                        <p><strong>Spécialité :</strong> Gestion de projet, déploiement et retours utilisateurs.</p>
                        <p><strong>Ce que le cours PHP lui a apporté :</strong> un regard sur le cycle complet du développement jusqu'à la production.</p>
                    </div>
                    <div class="chart-card">
                        <h3>MBaye Babacar Diagne</h3>
                        <p><strong>Diplôme :</strong> Licence Informatique</p>
                        <p><strong>Formation :</strong> Université Iba der Thiam de Thiés</p>
                        <p><strong>Spécialité :</strong> Analyse fonctionnelle, tests et qualité logicielle.</p>
                        <p><strong>Ce que le cours PHP lui a apporté :</strong> une approche rigoureuse pour structurer le code et sécuriser les formulaires.</p>
                    </div>
                    <div class="chart-card">
                        <h3>Pa Aly Ndiaye</h3>
                        <p><strong>Diplôme :</strong> Licence Informatique</p>
                        <p><strong>Formation :</strong> Université Iba der Thiam de Thiés</p>
                        <p><strong>Spécialité :</strong> Architecture des bases de données et optimisation SQL.</p>
                        <p><strong>Ce que le cours PHP lui a apporté :</strong> une expertise pour concevoir des requêtes performantes et fiables.</p>
                    </div>
                    <div class="chart-card">
                        <h3>Mohamed El Bachir Gueye</h3>
                        <p><strong>Diplôme :</strong> Licence Informatique</p>
                        <p><strong>Formation :</strong> Université Iba der Thiam de Thiés</p>
                        <p><strong>Spécialité :</strong> UX et design d'application.</p>
                        <p><strong>Ce que le cours PHP lui a apporté :</strong> l'intégration fluide entre le design visuel et le backend.</p>
                    </div>
                    <div class="chart-card">
                        <h3>Gaissiri Tounkara</h3>
                        <p><strong>Diplôme :</strong> Licence Informatique</p>
                        <p><strong>Formation :</strong> Université Iba der Thiam de Thiés</p>
                        <p><strong>Spécialité :</strong> Gestion de session, authentification et workflow métier.</p>
                        <p><strong>Ce que le cours PHP lui a apporté :</strong> la maîtrise de la logique serveur et de la gestion des utilisateurs.</p>
                    </div>
                    <div class="chart-card">
                        <h3>Modou Niang</h3>
                        <p><strong>Diplôme :</strong> Licence Informatique</p>
                        <p><strong>Formation :</strong> Université Iba der Thiam de Thiés</p>
                        <p><strong>Spécialité :</strong> Sécurité PHP et validation des données.</p>
                        <p><strong>Ce que le cours PHP lui a apporté :</strong> une expertise pratique pour protéger les formulaires et les sections d'administration.</p>
                    </div>
                </div>
            </section>

            <section class="section-block">
                <div class="section-title-row">
                    <h2>Acquis de la formation</h2>
                </div>
                <div class="table-card" style="padding: 24px;">
                    <ul style="margin: 0; padding-left: 20px;">
                        <li>Compétences en PHP moderne et architecture MVC simple.</li>
                        <li>Gestion des bases de données MySQL/PDO et création de requêtes sécurisées.</li>
                        <li>Conception d'interfaces administratives et de vues dynamiques.</li>
                        <li>Utilisation de bibliothèques tierces et de composants frontend légers.</li>
                        <li>Travail en équipe sur des fonctionnalités métiers réelles.</li>
                    </ul>
                </div>
            </section>
        </main>
    </div>
</body>

</html>
