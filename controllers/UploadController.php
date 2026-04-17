<?php

session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Professeur') {
    header("Location: ../index.php");
    exit();
}

$uploadType = $_POST['upload_type'] ?? '';
if (!in_array($uploadType, ['notes', 'emplois_du_temps'], true)) {
    header("Location: ../views/professeur/dashboard.php?status=upload_error");
    exit();
}

// Constantes
define('MAX_SIZE', 5 * 1024 * 1024); // 5 Mo
define('UPLOAD_DIR', __DIR__ . '/../uploads/' . $uploadType . '/');

// Vérifications basiques
if (empty($_FILES['pdf_file']['name'])) {
    header("Location: ../views/professeur/dashboard.php?status=upload_error");
    exit();
}

$file     = $_FILES['pdf_file'];
$tmpPath  = $file['tmp_name'];
$origName = $file['name'];
$size     = $file['size'];
$error    = $file['error'];

// Erreur PHP upload
if ($error !== UPLOAD_ERR_OK) {
    header("Location: ../views/professeur/dashboard.php?status=upload_error");
    exit();
}

// Taille max
if ($size > MAX_SIZE) {
    header("Location: ../views/professeur/dashboard.php?status=upload_error");
    exit();
}

// Vérification MIME réelle (pas juste l'extension)
$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $tmpPath);
finfo_close($finfo);
if ($mimeType !== 'application/pdf') {
    header("Location: ../views/professeur/dashboard.php?status=upload_error");
    exit();
}

// Extension
$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
if ($ext !== 'pdf') {
    header("Location: ../views/professeur/dashboard.php?status=upload_error");
    exit();
}

// Nom de fichier sécurisé
$login     = preg_replace('/[^a-z0-9]/', '', strtolower($_SESSION['user_login']));
$timestamp = date('Ymd_His');
$idClasse  = intval($_POST['id_classe_select'] ?? $_POST['id_classe'] ?? 0);
$semestre  = intval($_POST['semestre'] ?? 0);

if ($uploadType === 'notes') {
    $newName = 'notes_' . ($idClasse ? 'c'.$idClasse.'_' : '') . $timestamp . '_' . $login . '.pdf';
} else {
    $newName = 'edt_' . ($semestre ? 's'.$semestre.'_' : '') . $timestamp . '_' . $login . '.pdf';
}

// Déplacement
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

if (move_uploaded_file($tmpPath, UPLOAD_DIR . $newName)) {
    $statusKey = $uploadType === 'notes' ? 'upload_notes_ok' : 'upload_edt_ok';
    header("Location: ../views/professeur/dashboard.php?status=" . $statusKey);
} else {
    header("Location: ../views/professeur/dashboard.php?status=upload_error");
}
exit();
