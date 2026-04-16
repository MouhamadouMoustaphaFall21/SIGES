<?php
class User
{
    private $conn;
    public $login;
    public $password;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Crée un étudiant complet : Compte Utilisateur + Profil Etudiant
     */
    public function createStudent($login, $password, $nom, $prenom, $id_classe)
    {
        try {
            $this->conn->beginTransaction();

            // 1. Insertion dans la table utilisateur
            $queryUser = "INSERT INTO utilisateur (login, password, role) VALUES (:login, :pass, 'Etudiant')";
            $stmtUser = $this->conn->prepare($queryUser);
            $stmtUser->execute([
                'login' => $login,
                'pass'  => password_hash($password, PASSWORD_DEFAULT)
            ]);

            // 2. Insertion dans la table etudiant
            $queryStudent = "INSERT INTO etudiant (nom, prenom, Id_Classe, login) 
                             VALUES (:nom, :prenom, :id_classe, :login)";
            $stmtStudent = $this->conn->prepare($queryStudent);
            $stmtStudent->execute([
                'nom'       => $nom,
                'prenom'    => $prenom,
                'id_classe' => $id_classe,
                'login'     => $login
            ]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    /**
     * Authentifier un utilisateur
     */
    public function login()
    {
        $query = "SELECT login, password, role FROM utilisateur WHERE login = :login";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':login', $this->login);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Supporter les mots de passe stockés en clair dans l'ancien schéma
            if ($this->password === $row['password'] || password_verify($this->password, $row['password'])) {
                return $row;
            }
        }

        return false;
    }

    /**
     * Récupère tous les utilisateurs pour la liste admin
     */
    public function readAll()
    {
        $query = "SELECT * FROM utilisateur ORDER BY role";
        return $this->conn->query($query);
    }
}
