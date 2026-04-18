<?php
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Professeur') {
    header("Location: ../../index.php");
    exit();
}

require_once '../../config/database.php';
require_once '../../models/Teacher.php';
require_once '../../models/Grade.php';

$database = new Database();
$db = $database->getConnection();
$teacherModel = new Teacher($db);
$gradeModel   = new Grade($db);

$profData = $teacherModel->getProfileByLogin($_SESSION['user_login']);
$id_prof  = $profData['Id_Professeur'];

$reclamations = $gradeModel->getReclamationsForTeacher($id_prof);
$statusMessage = '';
$statusType = 'info';
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'updated':
            $statusMessage = 'Le statut de la réclamation a été mis à jour.';
            $statusType = 'success';
            break;
        case 'error':
            $statusMessage = 'Une erreur est survenue. Veuillez réessayer.';
            $statusType = 'error';
            break;
    }
}

$selected_classe = null;
$active_page = 'reclamations';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réclamations - SIGES Enseignant</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <style>
        .toast{display:flex;align-items:center;gap:12px;padding:14px 20px;border-radius:12px;font-size:.95rem;font-weight:500;margin-bottom:20px;animation:slideIn .35s ease}
        .toast-success{background:#d1fae5;color:#065f46;border:1px solid #6ee7b7}
        .toast-error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
        @keyframes slideIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
        .action-buttons{display:flex;flex-wrap:wrap;gap:8px;justify-content:center}
        .status-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:10px;font-size:.82rem;font-weight:700}
        .status-pending{background:#f8fafc;color:#1d4ed8;border:1px solid #bfdbfe}
        .status-traite{background:#ecfdf5;color:#166534;border:1px solid #86efac}
        .status-rejete{background:#fef2f2;color:#9f1239;border:1px solid #fecaca}
        .action-small{padding:.55rem .9rem;border-radius:10px;font-size:.82rem;border:none;cursor:pointer;transition:.2s}
        .action-small:hover{transform:translateY(-1px)}
        .action-small-primary{background:#1A3C5A;color:#fff}
        .action-small-secondary{background:#f8fafc;color:#1A3C5A;border:1px solid #cbd5e1}
    </style>
</head>
<body>
    <div class="student-shell">
        <?php include '_sidebar.php'; ?>

        <main class="student-main">
            <section class="page-header page-header-dashboard">
                <div>
                    <p class="eyebrow">Réclamations</p>
                    <h1>Gestion des réclamations</h1>
                    <p>Suivez les demandes des étudiants et marquez-les comme traitées ou rejetées.</p>
                    <?php if ($statusMessage): ?>
                        <div class="toast toast-<?= $statusType ?>">
                            <i class='bx <?= $statusType === 'success' ? 'bx-check-circle' : 'bx-x-circle' ?>'></i>
                            <?= htmlspecialchars($statusMessage) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="header-user-card">
                    <strong>Prof. <?= htmlspecialchars($profData['prenom'] . ' ' . $profData['nom']) ?></strong>
                    <span>Réclamations élèves</span>
                </div>
            </section>

            <section class="section-block">
                <?php if (empty($reclamations)): ?>
                    <div class="form-hint" style="border-color:#dbeafe;background:#eff6ff;color:#1e3a8a;">
                        <strong>Aucune réclamation à traiter.</strong>
                        <p>Les étudiants n'ont pas encore soumis de réclamation pour vos évaluations.</p>
                    </div>
                <?php else: ?>
                    <div class="table-card">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Étudiant</th>
                                    <th>Classe</th>
                                    <th>Matière</th>
                                    <th>Semestre</th>
                                    <th>Motif</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reclamations as $rec): ?>
                                    <?php
                                        $badgeClass = 'status-pending';
                                        if ($rec['statut'] === 'Corrigé') {
                                            $badgeClass = 'status-traite';
                                        } elseif ($rec['statut'] === 'Décliné') {
                                            $badgeClass = 'status-rejete';
                                        }
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($rec['id_reclamation']) ?></td>
                                        <td><?= htmlspecialchars($rec['etu_prenom'] . ' ' . $rec['etu_nom']) ?></td>
                                        <td><?= htmlspecialchars($rec['classe'] . ' ' . $rec['niveau']) ?></td>
                                        <td><?= htmlspecialchars($rec['matiere'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($rec['semestre'] ?? 'N/A') ?></td>
                                        <td style="text-align:left;white-space:pre-wrap;"><?= htmlspecialchars($rec['motif']) ?></td>
                                        <td><span class="status-badge <?= $badgeClass ?>"><?= htmlspecialchars($rec['statut']) ?></span></td>
                                        <td>
                                            <?php if ($rec['statut'] === 'En attente'): ?>
                                                <div class="action-buttons">
                                                    <button type="button" class="action-small action-small-primary" onclick="openModal(<?= $rec['id_reclamation'] ?>, 'Corrigé')">Corriger</button>
                                                    <button type="button" class="action-small action-small-secondary" onclick="openModal(<?= $rec['id_reclamation'] ?>, 'Décliné')">Décliner</button>
                                                </div>
                                            <?php else: ?>
                                                <span style="color:#475569;opacity:.8;">Aucune action</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- Modal pour saisir le commentaire -->
    <div id="commentModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Répondre à la réclamation</h3>
                <span class="modal-close" onclick="closeModal()">&times;</span>
            </div>
            <form id="commentForm" action="../../controllers/GradeController.php" method="POST">
                <input type="hidden" name="action" value="update_reclamation_status">
                <input type="hidden" id="modalReclamationId" name="id_reclamation" value="">
                <input type="hidden" id="modalStatus" name="new_status" value="">
                
                <div class="form-group">
                    <label for="commentaire_prof">Votre réponse (optionnel)</label>
                    <textarea id="commentaire_prof" name="commentaire_prof" rows="4" placeholder="Expliquez votre décision..."></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Annuler</button>
                    <button type="submit" class="btn-primary" id="modalSubmitBtn">Confirmer</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .modal { position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 0; border: 1px solid #888; width: 90%; max-width: 500px; border-radius: 12px; }
        .modal-header { padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { margin: 0; color: #1A3C5A; }
        .modal-close { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
        .modal-close:hover { color: #000; }
        .form-group { padding: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; resize: vertical; }
        .modal-actions { padding: 20px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 10px; }
        .btn-primary, .btn-secondary { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn-primary { background: #1A3C5A; color: white; }
        .btn-secondary { background: #f8fafc; color: #1A3C5A; border: 1px solid #cbd5e1; }
    </style>

    <script>
        function openModal(reclamationId, status) {
            document.getElementById('modalReclamationId').value = reclamationId;
            document.getElementById('modalStatus').value = status;
            document.getElementById('modalTitle').textContent = status === 'Corrigé' ? 'Corriger la réclamation' : 'Décliner la réclamation';
            document.getElementById('modalSubmitBtn').textContent = status === 'Corrigé' ? 'Corriger' : 'Décliner';
            document.getElementById('commentModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('commentModal').style.display = 'none';
            document.getElementById('commentaire_prof').value = '';
        }

        // Fermer le modal si on clique en dehors
        window.onclick = function(event) {
            if (event.target == document.getElementById('commentModal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>
