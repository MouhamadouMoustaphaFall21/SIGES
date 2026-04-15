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

$userModel = new User($db);
$users = $userModel->readAll();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Gestion des Utilisateurs - SIGES Admin</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1100px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .grid-forms {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .form-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .form-box h3 {
            margin-top: 0;
            color: #2d3748;
            border-bottom: 1px solid #cbd5e0;
            padding-bottom: 10px;
        }

        input,
        select,
        button {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button {
            background-color: #4a90e2;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }

        button:hover {
            background-color: #357abd;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #edf2f7;
            text-align: left;
        }

        th {
            background-color: #4a5568;
            color: white;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-admin {
            background: #fed7d7;
            color: #822727;
        }

        .badge-prof {
            background: #feebc8;
            color: #744210;
        }

        .badge-etu {
            background: #c6f6d5;
            color: #22543d;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="header">
            <h1>Gestion des Utilisateurs</h1>
            <a href="dashboard.php" style="text-decoration: none; color: #4a90e2;">← Retour au Dashboard</a>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div style="padding: 10px; margin-bottom: 20px; border-radius: 5px; background: <?= $_GET['msg'] == 'success' ? '#c6f6d5' : '#fed7d7' ?>; color: <?= $_GET['msg'] == 'success' ? '#22543d' : '#822727' ?>;">
                <?= $_GET['msg'] == 'success' ? 'Opération réussie !' : 'Erreur lors de l\'opération.' ?>
            </div>
        <?php endif; ?>

        <div class="grid-forms">
            <div class="form-box">
                <h3>Ajouter un Administrateur</h3>
                <form action="../../controllers/AdminController.php" method="POST">
                    <input type="hidden" name="action" value="add_admin">
                    <input type="text" name="nom" placeholder="Nom" required>
                    <input type="text" name="prenom" placeholder="Prénom" required>
                    <input type="email" name="login" placeholder="Email" required>
                    <input type="password" name="password" placeholder="Mot de passe" required>
                    <button type="submit" style="background-color: #e53e3e;">Créer Administrateur</button>
                </form>
            </div>

            <div class="form-box">
                <h3>Créer une Classe</h3>
                <form action="../../controllers/AdminController.php" method="POST">
                    <input type="hidden" name="action" value="add_class">
                    <input type="text" name="libelle" placeholder="Nom de la classe" required>
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
                    <button type="submit" style="background-color: #9f7aea;">Créer la Classe</button>
                </form>
            </div>

            <div class="form-box">
                <h3>Inscrire un Étudiant</h3>
                <form action="../../controllers/AdminController.php" method="POST">
                    <input type="hidden" name="action" value="add_student">
                    <input type="text" name="nom" placeholder="Nom de l'élève" required>
                    <input type="text" name="prenom" placeholder="Prénom de l'élève" required>
                    <input type="email" name="login" placeholder="Email (Login)" required>
                    <input type="password" name="password" placeholder="Mot de passe" required>
                    <select name="id_classe" required>
                        <option value="">-- Sélectionner la Classe --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['Id_Classe'] ?>"><?= $c['libelle'] ?> - <?= $c['niveau'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">Créer le profil Étudiant</button>
                </form>
            </div>

            <div class="form-box">
                <h3>Recruter un Professeur</h3>
                <form action="../../controllers/AdminController.php" method="POST">
                    <input type="hidden" name="action" value="add_teacher">
                    <input type="text" name="nom" placeholder="Nom du professeur" required>
                    <input type="text" name="prenom" placeholder="Prénom" required>
                    <input type="email" name="login" placeholder="Email professionnel" required>
                    <select name="id_matiere" required>
                        <option value="">-- Matière principale --</option>
                        <?php foreach ($matieres as $m): ?>
                            <option value="<?= $m['Id_Matiere'] ?>"><?= $m['libelle'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" style="background-color: #48bb78;">Créer le profil Enseignant</button>
                </form>
            </div>
        </div>

        <h3>Liste des Comptes Actifs</h3>
        <table>
            <thead>
                <tr>
                    <th>Login / Email</th>
                    <th>Rôle</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $users->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['login']) ?></strong></td>
                        <td>
                            <span class="badge <?= $row['role'] == 'Admin' ? 'badge-admin' : ($row['role'] == 'Professeur' ? 'badge-prof' : 'badge-etu') ?>">
                                <?= $row['role'] ?>
                            </span>
                        </td>
                        <td><span style="color: green;">● Actif</span></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</body>

</html>