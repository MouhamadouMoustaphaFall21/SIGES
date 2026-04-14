<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGES</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="connexion">
         <div class="debut">
          <img src="assets/img/logo_SIGES.jpeg" alt="Logo SIGES">
          <h1>SIGES</h1>
          <p>Le Système de Gestion des Etudiants de l'Université de Thiès</p>
         </div>
    <h2>Connexion</h2>
      <form action="actions/login_action.php" method="POST" autocomplete="off">
        <label for="email"> Email </label>
        <input type="email" name="email" placeholder="Modou@univ.sn" required>
        <label for="mdp"> Mot de passe </label>
        <div style="position: relative;">
            <input type="password" name="mdp" placeholder="........" required id="password">
            <i class="fa-solid fa-eye" id="togglePassword" style="position: absolute; right: 5px; top: 30%; transform: translateY(-60%); font-size: 12px; cursor: pointer;"></i>
        </div>    
        <button type="submit">Se connecter</button>
      </form>
    </div>
    
</body>
</html>