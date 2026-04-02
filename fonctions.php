<?php
 function bddconnect(){
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "notges";
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;

    }
    catch (PDOException $e) {
        echo "Erreur : " . $e->getMessage();
    }
}

function estGestionnaire() {
    return isset($_SESSION['idUtType']) && $_SESSION['idUtType'] == 3;
}
function estProfesseur() {
    return isset($_SESSION['idUtType']) && $_SESSION['idUtType'] == 2;
}
?>