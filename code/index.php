<?php
session_start();
require_once("fonctions.php");

if (!isset($_SESSION['login'])) {
    header("Location: utilisateur_vue_connexion.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Hecten Academy</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="navbar">
        <a href="index.php?page=accueil">Accueil</a>
        

        <?php if (estProfesseur()): ?>
            <a href="index.php?page=eleve_vue_ajout">Ajouter élève</a>
            <a href="index.php?page=eleve_vue_liste">Liste élèves</a>
        <?php endif; ?>

        <?php if (estGestionnaire()): ?>
            <a href="index.php?page=gererlesProfesseurs">Gérer les professeurs</a>
        <?php endif; ?>

        <a href="index.php?page=deconnexion">Déconnexion</a>
    </div>

    <div class="content">
        <?php
        if (isset($_GET['page'])) {
            $page = $_GET['page'];

            switch ($page) {
                case 'accueil':
                    include 'accueil.php';
                    break;
                case 'gererlesProfesseurs':
                    include 'gererlesProfesseurs.php';
                    break;
                case 'deconnexion':
                    include 'deconnexion.php';
                    break;
                default:
                    include 'accueil.php';
                    break;
            }
        } else {
            include 'accueil.php';
        }
        ?>
    </div>
</body>
</html>
