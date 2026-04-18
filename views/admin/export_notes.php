<?php
require_once '../../config/auth.php';
requireRole('Admin');
require_once '../../config/database.php';
require_once '../../models/Grade.php';
require_once '../../models/Student.php';

$database = new Database();
$db = $database->getConnection();

// Récupérer l'ID de classe
$selected_classe = isset($_GET['id_classe']) ? htmlspecialchars($_GET['id_classe']) : null;

if (!$selected_classe) {
    die('Classe non spécifiée.');
}

$gradeModel = new Grade($db);
$studentModel = new Student($db);

// Récupérer les étudiants de la classe
$queryStudents = "SELECT e.*, c.libelle as classe_nom, c.niveau FROM etudiant e
                 LEFT JOIN classe c ON e.Id_Classe = c.Id_Classe
                 WHERE e.Id_Classe = ? ORDER BY e.nom";
$stmtStudents = $db->prepare($queryStudents);
$stmtStudents->execute([$selected_classe]);
$students = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les matières et notes
$queryMatieres = "SELECT DISTINCT m.Id_Matiere, m.libelle, m.coefficient
                 FROM matiere m
                 JOIN affecter a ON m.Id_Matiere = a.Id_Matiere
                 JOIN professeur p ON a.Id_Professeur = p.Id_Professeur
                 JOIN classe c ON a.Id_Classe = c.Id_Classe
                 WHERE c.Id_Classe = ? ORDER BY m.libelle";
$stmtMatieres = $db->prepare($queryMatieres);
$stmtMatieres->execute([$selected_classe]);
$matieres = $stmtMatieres->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les notes pour chaque étudiant
foreach ($students as &$student) {
    $queryGrades = "SELECT Id_Note, note FROM note WHERE id_Etudiant = ?";
    $stmtGrades = $db->prepare($queryGrades);
    $stmtGrades->execute([$student['id_Etudiant']]);
    $notes = $stmtGrades->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $student['notes'] = [];
    $sum_note_coeff = 0;
    $sum_coeff = 0;
    
    foreach ($matieres as $matiere) {
        $note = $notes[$matiere['Id_Matiere']] ?? null;
        $student['notes'][$matiere['Id_Matiere']] = $note;
        
        if ($note !== null) {
            $sum_note_coeff += $note * $matiere['coefficient'];
            $sum_coeff += $matiere['coefficient'];
        }
    }
    
    $student['moyenne_generale'] = $sum_coeff > 0 ? round($sum_note_coeff / $sum_coeff, 2) : null;
}

usort($students, function($a, $b) {
    if ($a['moyenne_generale'] === null && $b['moyenne_generale'] === null) return 0;
    if ($a['moyenne_generale'] === null) return 1;
    if ($b['moyenne_generale'] === null) return -1;
    return $b['moyenne_generale'] <=> $a['moyenne_generale'];
});

// Récupérer le nom de la classe
$queryClasse = "SELECT libelle, niveau FROM classe WHERE Id_Classe = ?";
$stmtClasse = $db->prepare($queryClasse);
$stmtClasse->execute([$selected_classe]);
$classe = $stmtClasse->fetch(PDO::FETCH_ASSOC);
$classe_nom = $classe ? $classe['libelle'] . ' (' . $classe['niveau'] . ')' : 'Inconnue';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Procès-verbal - SIGES</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f9fafb;
            padding: 24px;
        }
        .export-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .export-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 32px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 20px;
        }
        .export-title h1 {
            font-size: 28px;
            color: #1f2937;
            margin-bottom: 8px;
        }
        .export-title p {
            font-size: 14px;
            color: #6b7280;
        }
        .export-actions {
            display: flex;
            gap: 12px;
        }
        .btn-print {
            padding: 10px 24px;
            background: #1A3C5A;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
        }
        .btn-print:hover {
            background: #0f2a42;
        }
        .pv-header {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            gap: 20px;
            align-items: center;
            margin-bottom: 32px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
        }
        .pv-header img {
            max-width: 80px;
            height: auto;
        }
        .pv-document-title {
            text-align: center;
        }
        .pv-document-title h2 {
            font-size: 18px;
            color: #1f2937;
            margin-bottom: 4px;
        }
        .pv-document-title p {
            font-size: 13px;
            color: #6b7280;
        }
        .pv-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 24px;
        }
        .pv-table th {
            background: #1f2937;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            border: 1px solid #d1d5db;
        }
        .pv-table td {
            padding: 12px;
            border: 1px solid #d1d5db;
            font-size: 13px;
        }
        .pv-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        .pv-table tbody tr:hover {
            background: #f3f4f6;
        }
        .pv-table .text-center {
            text-align: center;
        }
        .pv-table .font-bold {
            font-weight: 600;
        }
        .moyenne-cell {
            font-weight: 600;
            background: rgba(56,189,248,0.1);
            color: #1A3C5A;
        }
        .note-null {
            color: #9ca3af;
            font-style: italic;
        }
        @media print {
            body { background: white; padding: 0; }
            .export-container { box-shadow: none; padding: 20px; }
            .export-actions { display: none; }
            .export-header { margin-bottom: 20px; padding-bottom: 16px; }
        }
    </style>
</head>
<body>
    <div class="export-container">
        <div class="export-header">
            <div class="export-title">
                <h1>📋 Procès-verbal de délibération</h1>
                <p>Classe: <?= htmlspecialchars($classe_nom) ?> • <?= date('d/m/Y') ?></p>
            </div>
            <div class="export-actions">
                <button class="btn-print" onclick="window.print()">🖨️ Imprimer / Télécharger</button>
            </div>
        </div>

        <div class="pv-header">
            <div>
                <img src="../../assets/img/logo_simple-SAP.png" alt="Logo SIGES">
            </div>
            <div class="pv-document-title">
                <h2>Procès-verbal de Délibération</h2>
                <p>Classe: <strong><?= htmlspecialchars($classe_nom) ?></strong></p>
                <p>Date: <?= date('d/m/Y H:i') ?></p>
            </div>
            <div style="text-align: right; font-size: 12px; color: #6b7280;">
                <p><strong>SIGES</strong></p>
                <p>Système de Gestion Scolaire</p>
            </div>
        </div>

        <table class="pv-table">
            <thead>
                <tr>
                    <th>Étudiant</th>
                    <?php foreach ($matieres as $matiere): ?>
                        <th class="text-center"><?= htmlspecialchars(substr($matiere['libelle'], 0, 10)) ?><br><small style="font-weight:400;">(Coef: <?= $matiere['coefficient'] ?>)</small></th>
                    <?php endforeach; ?>
                    <th class="text-center" style="background: rgba(56,189,248,0.2);">Moyenne</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td class="font-bold"><?= htmlspecialchars($student['nom'] . ' ' . $student['prenom']) ?></td>
                        <?php foreach ($matieres as $matiere): ?>
                            <td class="text-center">
                                <?php 
                                    $note = $student['notes'][$matiere['Id_Matiere']] ?? null;
                                    if ($note !== null) {
                                        echo number_format($note, 2);
                                    } else {
                                        echo '<span class="note-null">—</span>';
                                    }
                                ?>
                            </td>
                        <?php endforeach; ?>
                        <td class="text-center moyenne-cell">
                            <?php 
                                if ($student['moyenne_generale'] !== null) {
                                    echo number_format($student['moyenne_generale'], 2);
                                } else {
                                    echo '—';
                                }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #e5e7eb; font-size: 12px; color: #6b7280;">
            <p><strong>Observations:</strong></p>
            <p style="margin-top: 8px;">Procès-verbal généré le <?= date('d/m/Y à H:i') ?> par le système SIGES.</p>
        </div>
    </div>

    <script>
        // Auto-print si paramètre auto=1
        if (new URLSearchParams(window.location.search).get('auto') === '1') {
            window.addEventListener('load', function() {
                setTimeout(() => window.print(), 500);
            });
        }
    </script>
</body>
</html>
