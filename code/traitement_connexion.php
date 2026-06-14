<?php
session_start();
require_once("fonctions.php");
 
try {
    $conn = bddconnect();
 
    $mail = $_POST['login'] ?? '';
    $mdp = $_POST['mdp'] ?? '';
 
    $sql = "SELECT * FROM utilisateur WHERE mail = :mail";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':mail', $mail);
    $stmt->execute();
 
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
 
    if ($row && password_verify($mdp, $row['mdp'])) {
        $_SESSION['login'] = $mail;
        $_SESSION['idUtilisateur'] = $row['idUt'];
        $_SESSION['idUtType'] = $row['idUtType'];
 
        header("Location: index.php");
        exit();
    } else {
   echo "L'email ou le mot de passe est incorrect.";
}
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
 
$conn = null;
?>
 