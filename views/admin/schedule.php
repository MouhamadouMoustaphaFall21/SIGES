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
    <style>
        body {
            font-family: sans-serif;
            background: #f8f9fa;
            padding: 20px;
        }

        .admin-panel {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            background: #f1f5f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #dee2e6;
            padding: 12px;
            text-align: left;
        }

        th {
            background: #007bff;
            color: white;
        }

        .btn-add {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            grid-column: 1 / -1;
        }
    </style>
</head>

<body>

    <div class="admin-panel">
        <h1>Configuration de l'Emploi du Temps</h1>
        <a href="dashboard.php">← Retour</a>

        <form action="../../controllers/ScheduleController.php" method="POST">
            <div class="form-grid">
                <div>
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
                <div>
                    <label>Heure Début</label>
                    <input type="time" name="heure_debut" required>
                </div>
                <div>
                    <label>Heure Fin</label>
                    <input type="time" name="heure_fin" required>
                </div>
                <div>
                    <label>Classe</label>
                    <select name="id_classe" required>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['Id_Classe'] ?>"><?= $c['libelle'] ?> (<?= $c['niveau'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Matière</label>
                    <select name="id_matiere" required>
                        <?php foreach ($matieres as $m): ?>
                            <option value="<?= $m['Id_Matiere'] ?>"><?= $m['libelle'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Enseignant</label>
                    <select name="id_professeur" required>
                        <?php foreach ($profs as $p): ?>
                            <option value="<?= $p['Id_Professeur'] ?>"><?= $p['nom'] ?> <?= $p['prenom'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-add">Ajouter ce créneau au planning</button>
            </div>
        </form>

        <h3>Planning Global de l'Établissement</h3>
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
                        <td><strong><?= $s['jour'] ?></strong></td>
                        <td><?= substr($s['heure_debut'], 0, 5) ?> - <?= substr($s['heure_fin'], 0, 5) ?></td>
                        <td><?= $s['classe_nom'] ?> (<?= $s['niveau'] ?>)</td>
                        <td><?= $s['matiere_nom'] ?></td>
                        <td>Prof. <?= $s['prof_nom'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</body>

</html>