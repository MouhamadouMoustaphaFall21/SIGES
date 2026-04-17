<?php
require_once '../../config/auth.php';
requireRole('Admin');
require_once '../../config/database.php';
require_once '../../models/User.php';

$database = new Database();
$db = $database->getConnection();

// Récupération des classes pour le formulaire d'étudiant
$queryClasses = "SELECT * FROM classe ORDER BY libelle, niveau";
$stmtClasses = $db->prepare($queryClasses);
$stmtClasses->execute();
$classes = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);

// Récupération des matières pour le formulaire de professeur
$queryMatieres = "SELECT * FROM matiere ORDER BY libelle";
$stmtMatieres = $db->prepare($queryMatieres);
$stmtMatieres->execute();
$matieres = $stmtMatieres->fetchAll(PDO::FETCH_ASSOC);

// Récupération des professeurs pour l'affectation
$profsAssign = $db->query("SELECT Id_Professeur, nom, prenom FROM professeur ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);

// Récupération des affectations existantes
$assignments = $db->query("SELECT p.nom as prof_nom, p.prenom as prof_prenom, c.libelle as classe_nom, c.niveau as classe_niveau
                          FROM affecter a
                          JOIN professeur p ON a.Id_Professeur = p.Id_Professeur
                          JOIN classe c ON a.Id_Classe = c.Id_Classe
                          ORDER BY c.libelle, p.nom")->fetchAll(PDO::FETCH_ASSOC);

$userModel = new User($db);
$users = $userModel->readAll();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Gestion des Utilisateurs - SIGES Admin</title>
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
                <a href="users.php" class="active"><i class='bx bx-user-circle'></i>Gestion utilisateurs</a>
                <a href="grades_view.php"><i class='bx bx-book'></i>PV Délibération</a>
                <a href="schedule.php"><i class='bx bx-calendar'></i>Emploi du temps</a>
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
                    <p class="eyebrow">Gestion des utilisateurs</p>
                    <h1>Paramètres administrateur</h1>
                    <p>Créez et gérez les comptes administrateurs, enseignants et étudiants en toute simplicité.</p>
                </div>
                <div class="header-user-card">
                    <strong>Administrateur</strong>
                    <span>Équipe SIGES</span>
                </div>
            </section>

            <section class="section-block">
                <?php if (isset($_GET['msg'])): ?>
                    <div class="form-hint" style="border-color: <?= $_GET['msg'] == 'success' ? '#c6f6d5' : '#fed7d7' ?>; background: <?= $_GET['msg'] == 'success' ? 'rgba(198,246,213,0.35)' : 'rgba(254,215,215,0.35)' ?>; color: <?= $_GET['msg'] == 'success' ? '#22543d' : '#822727' ?>;">
                        <?= $_GET['msg'] == 'success' ? 'Opération réussie !' : 'Erreur lors de l\'opération.' ?>
                    </div>
                <?php endif; ?>

                <div class="form-grid" style="margin-bottom: 24px;">
                    <div class="form-box">
                        <h3>Ajouter un administrateur</h3>
                        <form action="../../controllers/AdminController.php" method="POST">
                            <input type="hidden" name="action" value="add_admin">
                            <div class="form-group">
                                <label>Nom</label>
                                <input type="text" name="nom" placeholder="Nom" required>
                            </div>
                            <div class="form-group">
                                <label>Prénom</label>
                                <input type="text" name="prenom" placeholder="Prénom" required>
                            </div>
                            <div class="form-group">
                                <label>Adresse email</label>
                                <input type="email" name="login" placeholder="Email" required>
                            </div>
                            <div class="form-group">
                                <label>Mot de passe</label>
                                <input type="password" name="password" placeholder="Mot de passe" required>
                            </div>
                            <button type="submit" class="button-danger">Créer administrateur</button>
                        </form>
                    </div>

                    <div class="form-box">
                        <h3>Créer une classe</h3>
                        <form action="../../controllers/AdminController.php" method="POST">
                            <input type="hidden" name="action" value="add_class">
                            <div class="form-group">
                                <label>Libellé</label>
                                <input type="text" name="libelle" placeholder="Nom de la classe" required>
                            </div>
                            <div class="form-group">
                                <label>Niveau</label>
                                <select name="niveau" required>
                                    <option value="">-- Niveau --</option>
                                    <option value="L1">L1</option>
                                    <option value="L2">L2</option>
                                    <option value="L3">L3</option>
                                    <option value="M1">M1</option>
                                    <option value="M2">M2</option>
                                    <option value="D1">D1</option>
                                    <option value="D2">D2</option>
                                    <option value="D3">D3</option>
                                </select>
                            </div>
                            <button type="submit" class="button-warning">Créer la classe</button>
                        </form>
                    </div>

                    <div class="form-box">
                        <h3>Inscrire un étudiant</h3>
                        <form action="../../controllers/AdminController.php" method="POST">
                            <input type="hidden" name="action" value="add_student">
                            <div class="form-group">
                                <label>Nom</label>
                                <input type="text" name="nom" placeholder="Nom de l'élève" required>
                            </div>
                            <div class="form-group">
                                <label>Prénom</label>
                                <input type="text" name="prenom" placeholder="Prénom de l'élève" required>
                            </div>
                            <div class="form-group">
                                <label>Login / Email</label>
                                <input type="email" name="login" placeholder="Email (Login)" required>
                            </div>
                            <div class="form-group">
                                <label>Mot de passe</label>
                                <input type="password" name="password" placeholder="Mot de passe" required>
                            </div>
                            <div class="form-group">
                                <label>Classe</label>
                                <select name="id_classe" required>
                                    <option value="">-- Sélectionner la classe --</option>
                                    <?php foreach ($classes as $c): ?>
                                        <option value="<?= $c['Id_Classe'] ?>"><?= htmlspecialchars($c['libelle']) ?> - <?= htmlspecialchars($c['niveau']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="button-success">Créer le profil étudiant</button>
                        </form>
                    </div>

                    <div class="form-box">
                        <h3>Recruter un professeur</h3>
                        <form action="../../controllers/AdminController.php" method="POST">
                            <input type="hidden" name="action" value="add_teacher">
                            <div class="form-group">
                                <label>Nom</label>
                                <input type="text" name="nom" placeholder="Nom du professeur" required>
                            </div>
                            <div class="form-group">
                                <label>Prénom</label>
                                <input type="text" name="prenom" placeholder="Prénom" required>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="login" placeholder="Email professionnel" required>
                            </div>
                            <div class="form-group">
                                <label>Mot de passe</label>
                                <input type="password" name="password" placeholder="Mot de passe" required>
                            </div>
                            <div class="form-group">
                                <label>Matière</label>
                                <select name="id_matiere" required>
                                    <option value="">-- Matière principale --</option>
                                    <?php foreach ($matieres as $m): ?>
                                        <option value="<?= $m['Id_Matiere'] ?>"><?= htmlspecialchars($m['libelle']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="button-primary">Créer le profil enseignant</button>
                        </form>
                    </div>

                    <div class="form-box">
                        <h3>Créer une matière</h3>
                        <form action="../../controllers/AdminController.php" method="POST">
                            <input type="hidden" name="action" value="add_subject">
                            <div class="form-group">
                                <label>Libellé de la matière</label>
                                <input type="text" name="libelle" placeholder="Ex: Programmation" required>
                            </div>
                            <div class="form-group">
                                <label>Coefficient</label>
                                <input type="number" name="coefficient" min="1" max="10" value="1" required>
                            </div>
                            <button type="submit" class="button-secondary">Ajouter la matière</button>
                        </form>
                    </div>

                    <div class="form-box">
                        <h3>Affecter un professeur</h3>
                        <form action="../../controllers/AdminController.php" method="POST">
                            <input type="hidden" name="action" value="assign_professor">
                            <div class="form-group">
                                <label>Professeur</label>
                                <select name="id_professeur" required>
                                    <option value="">-- Sélectionner le professeur --</option>
                                    <?php foreach ($profsAssign as $p): ?>
                                        <option value="<?= $p['Id_Professeur'] ?>"><?= htmlspecialchars($p['nom'] . ' ' . $p['prenom']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Classe</label>
                                <select name="id_classe" required>
                                    <option value="">-- Sélectionner la classe --</option>
                                    <?php foreach ($classes as $c): ?>
                                        <option value="<?= $c['Id_Classe'] ?>"><?= htmlspecialchars($c['libelle']) ?> (<?= htmlspecialchars($c['niveau']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="button-success">Affecter au cours</button>
                        </form>
                    </div>
                </div>

                <div class="table-card">
                    <h3>Affectations professeur / classe</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Professeur</th>
                                <th>Classe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($assignments) > 0): ?>
                                <?php foreach ($assignments as $assign): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($assign['prof_nom'] . ' ' . $assign['prof_prenom']) ?></td>
                                        <td><?= htmlspecialchars($assign['classe_nom']) ?> (<?= htmlspecialchars($assign['classe_niveau']) ?>)</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" style="text-align:center; padding: 24px;">Aucune affectation enregistrée.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="table-card">
                    <h3>Liste des comptes actifs</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Login / Email</th>
                                <th>Rôle</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $users->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['login']) ?></strong></td>
                                    <td>
                                        <span class="badge <?= $row['role'] == 'Admin' ? 'badge-admin' : ($row['role'] == 'Professeur' ? 'badge-prof' : 'badge-etu') ?>">
                                            <?= htmlspecialchars($row['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form action="../../controllers/AdminController.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="login" value="<?= htmlspecialchars($row['login']) ?>">
                                            <button type="submit" class="button-danger" style="font-size:0.85rem; padding: 6px 10px;">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</body>

</html>