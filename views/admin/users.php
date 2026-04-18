<?php
require_once '../../config/auth.php';
requireRole('Admin');
require_once '../../config/database.php';
require_once '../../models/User.php';

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

// Récupération des étudiants
$students = $db->query("SELECT e.*, c.libelle as classe_nom, c.niveau FROM etudiant e LEFT JOIN classe c ON e.Id_Classe = c.Id_Classe ORDER BY e.nom")->fetchAll(PDO::FETCH_ASSOC);

// Récupération des professeurs
$profs = $db->query("SELECT p.*, m.libelle as matiere_nom FROM professeur p LEFT JOIN matiere m ON p.Id_Matiere = m.Id_Matiere ORDER BY p.nom")->fetchAll(PDO::FETCH_ASSOC);

// Récupération des admins
$admins = $db->query("SELECT * FROM utilisateur WHERE role = 'Admin' ORDER BY login")->fetchAll(PDO::FETCH_ASSOC);
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

                <!-- Formulaire unique et dynamique de création d'utilisateurs -->
                <div class="form-box" style="margin-bottom: 24px;">
                    <h3>Créer un nouvel utilisateur</h3>
                    
                    <form id="userCreationForm" action="../../controllers/AdminController.php" method="POST">
                        <input type="hidden" id="actionInput" name="action" value="add_admin">
                        
                        <!-- Sélection du rôle -->
                        <div class="form-group">
                            <label>Rôle de l'utilisateur</label>
                            <select id="roleSelect" name="role" required onchange="updateFormFields()">
                                <option value="">-- Sélectionner un rôle --</option>
                                <option value="admin">Administrateur</option>
                                <option value="teacher">Professeur</option>
                                <option value="student">Étudiant</option>
                            </select>
                        </div>

                        <!-- Champs communs (Nom, Prénom, Email, Mot de passe) -->
                        <div id="commonFields" style="display: none;">
                            <div class="form-group">
                                <label>Nom</label>
                                <input type="text" name="nom" id="nomInput" placeholder="Nom" required>
                            </div>
                            <div class="form-group">
                                <label>Prénom</label>
                                <input type="text" name="prenom" id="prenomInput" placeholder="Prénom" required>
                            </div>
                            <div class="form-group">
                                <label>Adresse email</label>
                                <input type="email" name="login" id="loginInput" placeholder="Adresse email" required>
                            </div>
                            <div class="form-group">
                                <label>Mot de passe</label>
                                <input type="password" name="password" id="passwordInput" placeholder="Mot de passe" required>
                            </div>
                        </div>

                        <!-- Champs spécifiques à Professeur -->
                        <div id="teacherFields" style="display: none;">
                            <div class="form-group">
                                <label>Matière</label>
                                <select name="id_matiere" id="matiereSelect">
                                    <option value="">-- Matière principale --</option>
                                    <?php foreach ($matieres as $m): ?>
                                        <option value="<?= $m['Id_Matiere'] ?>"><?= htmlspecialchars($m['libelle']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Champs spécifiques à Étudiant -->
                        <div id="studentFields" style="display: none;">
                            <div class="form-group">
                                <label>Classe</label>
                                <select name="id_classe" id="classeSelect">
                                    <option value="">-- Sélectionner la classe --</option>
                                    <?php foreach ($classes as $c): ?>
                                        <option value="<?= $c['Id_Classe'] ?>"><?= htmlspecialchars($c['libelle']) ?> - <?= htmlspecialchars($c['niveau']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <button type="submit" id="submitBtn" class="button-primary" style="display: none;">Créer l'utilisateur</button>
                    </form>
                </div>

                <!-- Formulaires auxiliaires pour Classes et Matières -->
                <div class="form-grid" style="margin-bottom: 24px;">
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
                                        <button type="button" class="button-secondary" onclick="openEditUser('<?= $row['role'] ?>', '<?= addslashes($row['login']) ?>')">Modifier</button>
                                        <form action="../../controllers/AdminController.php" method="POST" style="display:inline; margin-left:8px;">
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

                <div class="form-box" style="margin-top: 24px;">
                    <h3>Modifier un utilisateur</h3>
                    <form action="../../controllers/AdminController.php" method="POST">
                        <input type="hidden" name="action" id="updateAction" value="update_student">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <input type="hidden" name="old_login" id="edit_old_login">

                        <div class="form-group">
                            <label>Type d'utilisateur</label>
                            <input type="text" id="edit_user_role" readonly value="Étudiant">
                        </div>
                        <div class="form-group">
                            <label>Email (login)</label>
                            <input type="email" name="login" id="edit_user_login" required>
                        </div>
                        <div class="form-group">
                            <label>Nom</label>
                            <input type="text" name="nom" id="edit_user_nom">
                        </div>
                        <div class="form-group">
                            <label>Prénom</label>
                            <input type="text" name="prenom" id="edit_user_prenom">
                        </div>
                        <div class="form-group" id="edit_class_group">
                            <label>Classe</label>
                            <select name="id_classe" id="edit_user_classe">
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['Id_Classe'] ?>"><?= htmlspecialchars($c['libelle']) ?> (<?= htmlspecialchars($c['niveau']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="button-primary">Mettre à jour</button>
                    </form>
                </div>

                <script>
                    const studentsData = <?= json_encode(array_map(function($student) {
                        return [
                            'id' => $student['id_Etudiant'],
                            'nom' => $student['nom'],
                            'prenom' => $student['prenom'],
                            'login' => $student['login'],
                            'classe' => $student['Id_Classe']
                        ];
                    }, $students), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
                    const profsData = <?= json_encode(array_map(function($prof) {
                        return [
                            'id' => $prof['Id_Professeur'],
                            'nom' => $prof['nom'],
                            'prenom' => $prof['prenom'],
                            'login' => $prof['login']
                        ];
                    }, $profs), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
                    const adminsData = <?= json_encode(array_map(function($admin) {
                        return [
                            'login' => $admin['login']
                        ];
                    }, $admins), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

                    // Fonction pour mettre à jour le formulaire en fonction du rôle sélectionné
                    function updateFormFields() {
                        const roleSelect = document.getElementById('roleSelect');
                        const selectedRole = roleSelect.value;
                        const commonFields = document.getElementById('commonFields');
                        const teacherFields = document.getElementById('teacherFields');
                        const studentFields = document.getElementById('studentFields');
                        const submitBtn = document.getElementById('submitBtn');
                        const actionInput = document.getElementById('actionInput');

                        // Masquer tous les champs
                        commonFields.style.display = 'none';
                        teacherFields.style.display = 'none';
                        studentFields.style.display = 'none';
                        submitBtn.style.display = 'none';

                        // Afficher les champs appropriés et définir l'action
                        if (selectedRole === 'admin') {
                            commonFields.style.display = 'block';
                            submitBtn.style.display = 'block';
                            actionInput.value = 'add_admin';
                            submitBtn.className = 'button-danger';
                            submitBtn.textContent = 'Créer administrateur';
                            
                            // Marquer les champs comme requis
                            document.getElementById('nomInput').required = true;
                            document.getElementById('prenomInput').required = true;
                            document.getElementById('loginInput').required = true;
                            document.getElementById('passwordInput').required = true;
                        } else if (selectedRole === 'teacher') {
                            commonFields.style.display = 'block';
                            teacherFields.style.display = 'block';
                            submitBtn.style.display = 'block';
                            actionInput.value = 'add_teacher';
                            submitBtn.className = 'button-primary';
                            submitBtn.textContent = 'Créer le profil enseignant';
                            
                            // Marquer les champs comme requis
                            document.getElementById('nomInput').required = true;
                            document.getElementById('prenomInput').required = true;
                            document.getElementById('loginInput').required = true;
                            document.getElementById('passwordInput').required = true;
                            document.getElementById('matiereSelect').required = true;
                        } else if (selectedRole === 'student') {
                            commonFields.style.display = 'block';
                            studentFields.style.display = 'block';
                            submitBtn.style.display = 'block';
                            actionInput.value = 'add_student';
                            submitBtn.className = 'button-success';
                            submitBtn.textContent = 'Créer le profil étudiant';
                            
                            // Marquer les champs comme requis
                            document.getElementById('nomInput').required = true;
                            document.getElementById('prenomInput').required = true;
                            document.getElementById('loginInput').required = true;
                            document.getElementById('passwordInput').required = true;
                            document.getElementById('classeSelect').required = true;
                        }
                    }

                    function openEditUser(role, login) {
                        document.getElementById('edit_user_role').value = role;
                        document.getElementById('edit_user_login').value = login;
                        document.getElementById('edit_user_nom').value = '';
                        document.getElementById('edit_user_prenom').value = '';
                        document.getElementById('edit_user_id').value = '';
                        document.getElementById('edit_old_login').value = role === 'Admin' ? login : '';
                        document.getElementById('updateAction').value = role === 'Professeur' ? 'update_prof' : (role === 'Admin' ? 'update_admin' : 'update_student');

                        if (role === 'Etudiant') {
                            document.getElementById('edit_class_group').style.display = 'block';
                            const student = studentsData.find(u => u.login === login);
                            if (student) {
                                document.getElementById('edit_user_nom').value = student.nom;
                                document.getElementById('edit_user_prenom').value = student.prenom;
                                document.getElementById('edit_user_id').value = student.id;
                                document.getElementById('edit_user_classe').value = student.classe;
                            }
                        } else {
                            document.getElementById('edit_class_group').style.display = 'none';
                        }

                        if (role === 'Professeur') {
                            const prof = profsData.find(u => u.login === login);
                            if (prof) {
                                document.getElementById('edit_user_nom').value = prof.nom;
                                document.getElementById('edit_user_prenom').value = prof.prenom;
                                document.getElementById('edit_user_id').value = prof.id;
                            }
                        }

                        if (role === 'Admin') {
                            const admin = adminsData.find(u => u.login === login);
                            if (admin) {
                                document.getElementById('edit_user_nom').value = '';
                                document.getElementById('edit_user_prenom').value = '';
                            }
                        }

                        window.scrollTo({top: document.getElementById('edit_user_role').offsetTop - 100, behavior: 'smooth'});
                    }
                </script>
            </section>
        </main>
    </div>
</body>

</html>