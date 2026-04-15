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
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            padding: 30px;
            background: #f0f2f5;
        }

        .pv-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .filter-section {
            background: #e2e8f0;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #2d3748;
            color: white;
        }

        .rank {
            font-weight: bold;
            color: #4a90e2;
        }

        .admis {
            color: #2f855a;
            font-weight: bold;
        }

        .ajourne {
            color: #c53030;
            font-weight: bold;
        }

        .btn-print {
            background: #4a5568;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        @media print {

            .filter-section,
            .btn-print {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="pv-container">
        <h1>PV de Délibération Annuelle</h1>

        <div class="filter-section">
            <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                <label>Choisir une classe :</label>
                <select name="id_classe" onchange="this.form.submit()">
                    <option value="">-- Sélectionner --</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['Id_Classe'] ?>" <?= $selected_classe == $c['Id_Classe'] ? 'selected' : '' ?>>
                            <?= $c['libelle'] ?> (<?= $c['niveau'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <button class="btn-print" onclick="window.print()">Imprimer le PV</button>
        </div>

        <?php if ($selected_classe && !empty($pv)): ?>
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
                    <?php $rank = 1;
                    foreach ($pv as $row): ?>
                        <tr>
                            <td class="rank">#<?= $rank++ ?></td>
                            <td><?= htmlspecialchars($row['nom']) ?> <?= htmlspecialchars($row['prenom']) ?></td>
                            <td><?= number_format($row['moyenne_generale'], 2) ?> / 20</td>
                            <td>
                                <?php if ($row['moyenne_generale'] >= 10): ?>
                                    <span class="admis">ADMIS</span>
                                <?php else: ?>
                                    <span class="ajourne">AJOURNÉ</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($selected_classe): ?>
            <p>Aucune note enregistrée pour cette classe pour le moment.</p>
        <?php else: ?>
            <p>Veuillez sélectionner une classe pour afficher les résultats.</p>
        <?php endif; ?>
    </div>

</body>

</html>