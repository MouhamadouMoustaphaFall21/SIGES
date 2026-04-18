<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../models/User.php';

requireRole('Admin'); // On vérifie que c'est bien l'admin

$database = new Database();
$db = $database->getConnection();
$userModel = new User($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // Cas : Ajout d'un étudiant
    if ($_POST['action'] === 'add_student') {
        $login    = $_POST['login'];
        $password = $_POST['password'];
        $nom      = $_POST['nom'];
        $prenom   = $_POST['prenom'];
        $id_classe= $_POST['id_classe'];

        if ($userModel->createStudent($login, $password, $nom, $prenom, $id_classe)) {
            header("Location: ../views/admin/users.php?msg=success");
        } else {
            header("Location: ../views/admin/users.php?msg=error");
        }
        exit();
    }

    // Cas : Ajout d'un admin
    if ($_POST['action'] === 'add_admin') {
        $login    = $_POST['login'];
        $password = $_POST['password'];

        $query = "INSERT INTO utilisateur (login, password, role) VALUES (:login, :pass, 'Admin')";
        $stmt = $db->prepare($query);
        $stmt->execute([
            'login' => $login,
            'pass'  => password_hash($password, PASSWORD_DEFAULT)
        ]);

        header("Location: ../views/admin/users.php?msg=success");
        exit();
    }

    // Cas : Ajout d'un professeur
    if ($_POST['action'] === 'add_teacher') {
        $login     = $_POST['login'];
        $password  = $_POST['password'];
        $nom       = $_POST['nom'];
        $prenom    = $_POST['prenom'];
        $id_matiere= $_POST['id_matiere'];

        // Créer le compte utilisateur
        $queryUser = "INSERT INTO utilisateur (login, password, role) VALUES (:login, :pass, 'Professeur')";
        $stmtUser = $db->prepare($queryUser);
        $stmtUser->execute([
            'login' => $login,
            'pass'  => password_hash($password, PASSWORD_DEFAULT)
        ]);

        // Créer le profil professeur
        $queryProf = "INSERT INTO professeur (nom, prenom, Id_Matiere, login) VALUES (:nom, :prenom, :id_matiere, :login)";
        $stmtProf = $db->prepare($queryProf);
        $stmtProf->execute([
            'nom'       => $nom,
            'prenom'    => $prenom,
            'id_matiere' => $id_matiere,
            'login'     => $login
        ]);

        header("Location: ../views/admin/users.php?msg=success");
        exit();
    }

    // Cas : Ajout d'une classe
    if ($_POST['action'] === 'add_class') {
        $libelle = $_POST['libelle'];
        $niveau  = $_POST['niveau'];

        $query = "INSERT INTO classe (libelle, niveau) VALUES (:lib, :niv)";
        $stmt = $db->prepare($query);
        $stmt->execute(['lib' => $libelle, 'niv' => $niveau]);

        header("Location: ../views/admin/users.php?msg=success");
        exit();
    }

    // Cas : Ajout d'une matière
    if ($_POST['action'] === 'add_subject') {
        $libelle = $_POST['libelle'];
        $coefficient = $_POST['coefficient'];

        $query = "INSERT INTO matiere (libelle, coefficient) VALUES (:lib, :coef)";
        $stmt = $db->prepare($query);
        $stmt->execute(['lib' => $libelle, 'coef' => $coefficient]);

        header("Location: ../views/admin/users.php?msg=success");
        exit();
    }

    // Cas : Affectation d'un professeur à une classe
    if ($_POST['action'] === 'assign_professor') {
        $id_professeur = $_POST['id_professeur'];
        $id_classe = $_POST['id_classe'];

        $query = "INSERT INTO affecter (Id_Professeur, Id_Classe) VALUES (:id_p, :id_c)";
        $stmt = $db->prepare($query);
        $stmt->execute(['id_p' => $id_professeur, 'id_c' => $id_classe]);

        header("Location: ../views/admin/users.php?msg=success");
        exit();
    }

    // Action : Suppression d'un utilisateur
    if ($_POST['action'] === 'delete_user') {
        $login = $_POST['login'];
        $query = "DELETE FROM utilisateur WHERE login = :l";
        $stmt = $db->prepare($query);
        $stmt->execute(['l' => $login]);
        header("Location: ../views/admin/users.php?status=deleted");
        exit();
    }

    // Cas : Mise à jour d'un étudiant
    if ($_POST['action'] === 'update_student') {
        $id_etudiant = $_POST['id_etudiant'];
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $id_classe = $_POST['id_classe'];
        $login = $_POST['login'];

        if ($userModel->updateStudent($id_etudiant, $nom, $prenom, $id_classe, $login)) {
            header("Location: ../views/admin/users.php?msg=success");
        } else {
            header("Location: ../views/admin/users.php?msg=error");
        }
        exit();
    }

    // Cas : Mise à jour d'un professeur
    if ($_POST['action'] === 'update_prof') {
        $id_prof = $_POST['id_prof'];
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $login = $_POST['login'];

        if ($userModel->updateProf($id_prof, $nom, $prenom, $login)) {
            header("Location: ../views/admin/users.php?msg=success");
        } else {
            header("Location: ../views/admin/users.php?msg=error");
        }
        exit();
    }

    // Cas : Mise à jour d'un admin
    if ($_POST['action'] === 'update_admin') {
        $old_login = $_POST['old_login'];
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $login = $_POST['login'];

        if ($userModel->updateAdmin($old_login, $nom, $prenom, $login)) {
            header("Location: ../views/admin/users.php?msg=success");
        } else {
            header("Location: ../views/admin/users.php?msg=error");
        }
        exit();
    }
}

?>