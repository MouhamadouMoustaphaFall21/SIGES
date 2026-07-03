# SIGES — Système de Gestion Scolaire

> Application web PHP/MySQL de gestion universitaire : étudiants, notes, emplois du temps et bulletins PDF.

---

## Sommaire

- [Présentation](#présentation)
- [Fonctionnalités](#fonctionnalités)
- [Technologies](#technologies)
- [Structure du projet](#structure-du-projet)
- [Installation](#installation)
- [Base de données](#base-de-données)
- [Comptes de test](#comptes-de-test)
- [Rôles et accès](#rôles-et-accès)
- [Auteurs](#auteurs)

---

## Présentation

SIGES est une plateforme de gestion scolaire développée dans le cadre d'un projet de **Licence 2 Informatique**. Elle permet à une université de gérer ses étudiants, ses enseignants, ses cours, ses notes et ses emplois du temps à travers une interface web sécurisée avec trois niveaux d'accès distincts.

---

## Fonctionnalités

### Administrateur
- Gestion complète (CRUD) des utilisateurs (étudiants, enseignants, admins)
- Affectation enseignant / classe / matière
- Gestion des emplois du temps
- Tableau de bord avec statistiques et graphiques
- Génération du PV de délibération en PDF

### Enseignant
- Consultation de son emploi du temps
- Saisie des notes (devoir + examen) avec calcul automatique de la moyenne
- Ajout d'étudiants à sa classe
- Statistiques de réussite par classe (%)

### Étudiant
- Consultation de ses notes par matière et par semestre
- Courbe d'évolution colorée (vert / rouge)
- Consultation de son emploi du temps
- Téléchargement du bulletin PDF (si toutes les notes sont saisies)

---

## Technologies

| Couche | Technologie |
|--------|-------------|
| Langage serveur | PHP |
| Base de données | MySQL  |
| Frontend | HTML5, CSS3, JavaScript |
| Graphiques | Chart.js |
| Génération PDF | FPDF  |
| Serveur local | XAMPP  |
| Versioning | Git |

---

## Structure du projet

```
/SIGES
│
├── index.php                   # Point d'entrée unique — routeur principal
│
├── /config
│   ├── database.php            # Connexion PDO à la base de données
│   └── auth.php                # Fonctions de sécurité et vérification des rôles
│
├── /controllers
│   ├── AuthController.php      # Connexion / déconnexion / gestion de session
│   ├── AdminController.php     # Dashboard admin, stats, gestion utilisateurs
│   ├── TeacherController.php   # Espace enseignant, classes, étudiants
│   ├── StudentController.php   # Espace étudiant, notes, bulletin
│   ├── GradeController.php     # Saisie notes + calcul automatique moyenne
│   ├── ScheduleController.php  # Emplois du temps
│   └── ReportController.php    # Génération PDF (bulletin, PV délibération)
│
├── /models
│   ├── User.php                # Requêtes SQL — table utilisateurs
│   ├── Student.php             # Requêtes SQL — table etudiants
│   ├── Teacher.php             # Requêtes SQL — table enseignants
│   ├── Grade.php               # Requêtes SQL — table notes
│   ├── Schedule.php            # Requêtes SQL — table emploi_du_temps
│   └── Report.php              # Logique de génération des PDF
│
├── /views
│   ├── /layouts
│   │   ├── header.php          # En-tête HTML commun + menu navigation
│   │   └── footer.php          # Pied de page HTML commun
│   ├── /auth
│   │   └── login.php           # Page de connexion
│   ├── /admin
│   │   ├── dashboard.php       # Tableau de bord admin
│   │   ├── users.php           # Gestion des utilisateurs
│   │   ├── courses.php         # Affectation prof/classe/matière
│   │   └── schedule.php        # Gestion emploi du temps
│   ├── /teacher
│   │   ├── dashboard.php       # Tableau de bord enseignant
│   │   ├── grades.php          # Saisie des notes
│   │   ├── students.php        # Liste étudiants de la classe
│   │   └── schedule.php        # Emploi du temps enseignant
│   └── /student
│       ├── dashboard.php       # Tableau de bord étudiant
│       ├── grades.php          # Consultation des notes
│       ├── schedule.php        # Emploi du temps étudiant
│       └── report.php          # Téléchargement bulletin PDF
│
└── /assets
    ├── /css                    # Feuilles de style
    └── /js                     # Scripts JavaScript + Chart.js
```

---

## Installation

### Prérequis
- XAMPP / WAMP / Laragon installé
- PHP >= 8.0
- MySQL >= 8.0

### Étapes

**1. Cloner le projet**
```bash
git clone https://github.com/votre-repo/siges.git
```
Ou copier le dossier `SIGES` dans `htdocs/` (XAMPP) ou `www/` (WAMP).

**2. Créer la base de données**

Ouvrir phpMyAdmin, puis importer le fichier :
```
siges_database.sql
```
Cela crée automatiquement la base `siges` avec toutes les tables et des données de test.

**3. Configurer la connexion**

Ouvrir `config/database.php` et adapter si nécessaire :
```php
$host = 'localhost';
$db   = 'siges';
$user = 'root';
$pass = '';        // laisser vide sur XAMPP par défaut
```

**4. Lancer l'application**

Démarrer Apache et MySQL depuis XAMPP, puis ouvrir :
```
http://localhost/SIGES/
```

---

## Base de données

Le fichier `siges_database.sql` contient :

| Table | Description |
|-------|-------------|
| `utilisateurs` | Tous les comptes (admin, enseignant, étudiant) |
| `filieres` | Filières universitaires |
| `classes` | Classes par filière et niveau |
| `etudiants` | Profils étudiants liés aux utilisateurs |
| `enseignants` | Profils enseignants liés aux utilisateurs |
| `matieres` | Cours avec coefficient et semestre |
| `affectations` | Lien enseignant / matière / classe |
| `notes` | Notes devoir, examen et moyenne calculée |
| `emploi_du_temps` | Créneaux horaires par affectation |
| `bulletins` | Suivi de l'état des bulletins par étudiant |
| `deliberations` | PV de délibération générés par l'admin |

---

## Comptes de test

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Administrateur | admin@siges.edu | password123 |
| Enseignant | mdiallo@siges.edu | password123 |
| Étudiant | isow@siges.edu | password123 |

> ⚠️ Changer ces mots de passe avant tout déploiement en production.

---

## Rôles et accès

```
Connexion (login.php)
        │
        ├── role = admin      →  /admin/dashboard
        ├── role = enseignant →  /teacher/dashboard
        └── role = etudiant   →  /student/dashboard
```

Chaque route est protégée dans `index.php` : un étudiant ne peut jamais accéder à une page admin, même en tapant l'URL manuellement.

---

## Calcul des notes

La moyenne finale est calculée automatiquement selon la formule :

```
Moyenne = (Note Devoir × 0.4) + (Note Examen × 0.6)
```

Le bulletin n'est disponible en téléchargement que lorsque **toutes les matières du semestre** ont une note saisie.

