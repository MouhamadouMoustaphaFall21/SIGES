<?php

/**
 * Point d'entrée principal - SIGES
 * Gère l'affichage du formulaire de connexion et la redirection automatique
 */
session_start();

// 1. Redirection automatique si déjà connecté
if (isset($_SESSION['user_role'])) {
    $roleDir = strtolower($_SESSION['user_role']);
    header("Location: views/" . $roleDir . "/dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>SIGES - Connexion</title>
    <style>
        :root {
            --primary: #1A3C5A;
            --secondary: #2E86AB;
            --accent: #F29100;
            --text: #333333;
            --bg: #F4F7FA;
            --surface: #FFFFFF;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: clamp(0.98rem, 1vw + 0.3rem, 1.08rem);
            background: linear-gradient(135deg, rgba(8, 41, 71, 0.92), rgba(46, 134, 171, 0.92));
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--text);
        }

        .page-shell {
            width: 100%;
            max-width: 1200px;
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 40px 90px rgba(0, 0, 0, 0.18);
            background: var(--surface);
        }

        .hero-panel {
            position: relative;
            background: url('assets/img/login_image.png') center/cover no-repeat;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 40px;
            color: white;
        }

        .hero-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(26, 60, 90, 0.42), rgba(26, 60, 90, 0.88));
        }

        .hero-panel > * {
            position: relative;
            z-index: 1;
        }

        .brand-block {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .brand-block img {
            width: 62px;
            height: 62px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.18);
            padding: 12px;
            object-fit: contain;
        }

        .brand-block h1 {
            margin: 0;
            font-size: clamp(1.5rem, 1.6vw + 1rem, 1.9rem);
            letter-spacing: 0.02em;
        }

        .hero-text {
            max-width: 420px;
        }

        .hero-text h2 {
            margin: 0;
            font-size: clamp(2rem, 4vw + 1rem, 3rem);
            line-height: 1.05;
        }

        .hero-text p {
            margin-top: 22px;
            line-height: 1.75;
            color: rgba(255, 255, 255, 0.92);
            font-size: clamp(1rem, 1.2vw + 0.8rem, 1.12rem);
        }

        .hero-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-top: 32px;
        }

        .hero-footer .tag {
            padding: 11px 18px;
            border-radius: 999px;
            background: rgba(242, 145, 0, 0.18);
            color: var(--accent);
            font-weight: 700;
            font-size: 0.96rem;
        }

        .hero-footer .info {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.88);
        }

        .hero-footer .info img {
            width: 32px;
            height: 32px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.18);
            padding: 8px;
        }

        .form-panel {
            background: var(--surface);
            padding: 54px 46px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-panel small {
            display: block;
            margin-bottom: 18px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--secondary);
            font-weight: 700;
            font-size: clamp(0.82rem, 0.9vw + 0.35rem, 0.96rem);
        }

        .form-panel h2 {
            margin: 0;
            font-size: clamp(2rem, 3vw + 1rem, 2.6rem);
            color: var(--primary);
        }

        .form-title {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 8px;
        }

        .title-logo {
            width: 50px;
            height: 50px;
            opacity: 0.8;
            border-radius: 8px;
            background: rgba(255,255,255,0.6);
            padding: 4px;
        }

        .form-panel p {
            margin: 0 0 30px;
            color: #555;
            line-height: 1.75;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            color: var(--text);
            font-weight: 600;
            font-size: clamp(0.98rem, 1vw + 0.2rem, 1.1rem);
        }

        input {
            width: 100%;
            padding: 16px 18px;
            border-radius: 14px;
            border: 1px solid #d7dce4;
            font-size: clamp(1rem, 0.9vw + 0.4rem, 1.1rem);
            transition: border-color 0.25s ease, box-shadow 0.25s ease;
        }

        input:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 6px rgba(46, 134, 171, 0.14);
        }

        .btn-submit {
            width: 100%;
            padding: 18px 20px;
            margin-top: 6px;
            border: none;
            border-radius: 14px;
            background: var(--primary);
            color: white;
            font-size: clamp(1.05rem, 1vw + 0.4rem, 1.2rem);
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .btn-submit:hover {
            background: #162f4a;
            transform: translateY(-1px);
        }

        .error {
            margin-bottom: 18px;
            padding: 14px 16px;
            border-radius: 14px;
            background: #fdecea;
            color: #b33a26;
            font-size: 0.95rem;
        }

        .footer-note {
            margin-top: 28px;
            font-size: clamp(0.92rem, 0.9vw + 0.35rem, 1rem);
            color: #777;
        }

        .hero-footer .tag {
            font-size: clamp(0.92rem, 0.9vw + 0.32rem, 1rem);
        }

        .hero-footer .info {
            font-size: clamp(0.95rem, 0.9vw + 0.35rem, 1rem);
        }

        @media (max-width: 980px) {
            .page-shell {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 680px) {
            .page-shell {
                border-radius: 20px;
            }

            .hero-panel,
            .form-panel {
                padding: 30px;
            }

            .hero-text h2 {
                font-size: 2.1rem;
            }
        }
    </style>
</head>

<body>
    <div class="page-shell">
        <div class="hero-panel">
            <div class="brand-block">
                <img src="assets/img/logo_simple-SAP.png" alt="SIGES logo">
                <div>
                    <div style="font-size: 0.92rem; opacity: 0.82; text-transform: uppercase; letter-spacing: 0.14em;">SIGES</div>
                    <strong style="font-size: 1.3rem; letter-spacing: 0.02em;">Système de Gestion</strong>
                </div>
            </div>

            <div class="hero-text">
                <h2>Bienvenue dans votre espace SIGES</h2>
                <p>Connectez-vous pour consulter vos notes, votre emploi du temps, vos professeurs et gérer vos réclamations en toute simplicité.</p>
            </div>

            <div class="hero-footer">
                <span class="tag">2026</span>
                <div class="info">
                    <img src="assets/img/logo_simple-SAP.png" alt="logo">
                    <span>Éducation agile • Notes • Emploi du temps</span>
                </div>
            </div>
        </div>

        <div class="form-panel">
            <small>Connexion</small>
            <div class="form-title">
                <h2>Accès SIGES</h2>
                <img src="assets/img/logo_simple-SAP.png" alt="SIGES logo" class="title-logo">
            </div>
            <p>Entrez votre email et mot de passe pour ouvrir votre espace Administrateur, Professeur ou Étudiant.</p>

            <?php
            if (isset($_GET['error'])) {
                echo '<div class="error">';
                if ($_GET['error'] == 'login_failed') {
                    echo 'Login ou mot de passe incorrect.';
                } elseif ($_GET['error'] == 'session_expired') {
                    echo 'Votre session a expiré. Veuillez vous reconnecter.';
                } elseif ($_GET['error'] == 'access_denied') {
                    echo 'Accès refusé pour ce rôle.';
                }
                echo '</div>';
            }
            ?>

            <form action="controllers/AuthController.php" method="POST">
                <div class="form-group">
                    <label for="login">Email</label>
                    <input type="email" name="login" id="login" required placeholder="exemple@domaine.com">
                </div>
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" name="password" id="password" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn-submit">Se connecter</button>
            </form>

            <div class="footer-note">© Copyright 2026 SIGES - Tous droits réservés.</div>
        </div>
    </div>
</body>

</html>
