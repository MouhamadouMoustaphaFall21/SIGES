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

    /**
     * Met à jour un étudiant
     */
    public function updateStudent($id_etudiant, $nom, $prenom, $id_classe, $login)
    {
        try {
            $this->conn->beginTransaction();

            // Récupérer l'ancien login de l'étudiant
            $queryOldLogin = "SELECT login FROM etudiant WHERE id_Etudiant = :id";
            $stmtOldLogin = $this->conn->prepare($queryOldLogin);
            $stmtOldLogin->execute(['id' => $id_etudiant]);
            $oldLogin = $stmtOldLogin->fetchColumn();

            if (!$oldLogin) {
                $this->conn->rollBack();
                return false;
            }

            // Si le login change, créer temporairement le nouvel utilisateur avant de mettre à jour le profil
            if ($login !== $oldLogin) {
                $queryCopyUser = "INSERT INTO utilisateur (login, password, role) SELECT :new_login, password, role FROM utilisateur WHERE login = :old_login";
                $stmtCopy = $this->conn->prepare($queryCopyUser);
                $stmtCopy->execute(['new_login' => $login, 'old_login' => $oldLogin]);
            }

            // Mettre à jour la table etudiant
            $queryStudent = "UPDATE etudiant SET nom = :nom, prenom = :prenom, Id_Classe = :id_classe, login = :login WHERE id_Etudiant = :id";
            $stmtStudent = $this->conn->prepare($queryStudent);
            $stmtStudent->execute([
                'nom' => $nom,
                'prenom' => $prenom,
                'id_classe' => $id_classe,
                'login' => $login,
                'id' => $id_etudiant
            ]);

            if ($login !== $oldLogin) {
                $queryDeleteOldUser = "DELETE FROM utilisateur WHERE login = :old_login";
                $stmtDeleteOldUser = $this->conn->prepare($queryDeleteOldUser);
                $stmtDeleteOldUser->execute(['old_login' => $oldLogin]);
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    /**
     * Met à jour un professeur
     */
    public function updateProf($id_prof, $nom, $prenom, $login)
    {
        try {
            $this->conn->beginTransaction();

            // Récupérer l'ancien login du professeur
            $queryOldLogin = "SELECT login FROM professeur WHERE Id_Professeur = :id";
            $stmtOldLogin = $this->conn->prepare($queryOldLogin);
            $stmtOldLogin->execute(['id' => $id_prof]);
            $oldLogin = $stmtOldLogin->fetchColumn();

            if (!$oldLogin) {
                $this->conn->rollBack();
                return false;
            }

            if ($login !== $oldLogin) {
                $queryCopyUser = "INSERT INTO utilisateur (login, password, role) SELECT :new_login, password, role FROM utilisateur WHERE login = :old_login";
                $stmtCopy = $this->conn->prepare($queryCopyUser);
                $stmtCopy->execute(['new_login' => $login, 'old_login' => $oldLogin]);
            }

            // Mettre à jour la table professeur
            $queryProf = "UPDATE professeur SET nom = :nom, prenom = :prenom, login = :login WHERE Id_Professeur = :id";
            $stmtProf = $this->conn->prepare($queryProf);
            $stmtProf->execute([
                'nom' => $nom,
                'prenom' => $prenom,
                'login' => $login,
                'id' => $id_prof
            ]);

            if ($login !== $oldLogin) {
                $queryDeleteOldUser = "DELETE FROM utilisateur WHERE login = :old_login";
                $stmtDeleteOldUser = $this->conn->prepare($queryDeleteOldUser);
                $stmtDeleteOldUser->execute(['old_login' => $oldLogin]);
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    /**
     * Met à jour un admin
     */
    public function updateAdmin($old_login, $nom, $prenom, $login)
    {
        try {
            $this->conn->beginTransaction();

            // Mettre à jour la table utilisateur
            $queryUser = "UPDATE utilisateur SET login = :login WHERE login = :old_login";
            $stmtUser = $this->conn->prepare($queryUser);
            $stmtUser->execute([
                'login' => $login,
                'old_login' => $old_login
            ]);

            // Pour admin, il n'y a pas de table séparée, mais on peut stocker nom/prenom dans session ou ailleurs
            // Ici, on suppose que nom/prenom sont dans utilisateur ou ignorés

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
}
