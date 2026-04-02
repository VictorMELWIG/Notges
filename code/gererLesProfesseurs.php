<?php
if (!isset($_SESSION['login'])) {
    header("Location: utilisateur_vue_connexion.php");
    exit();
}

if (!estGestionnaire()) {
    die("Accès refusé : cette page est réservée au gestionnaire.");
}

$conn = bddconnect();
$message = "";
$profAModifier = null;

/*traitement pour ajouter un prof*/
if (isset($_POST['ajouter'])) {
    $nom = trim($_POST['nomUt'] ?? '');
    $prenom = trim($_POST['prenomUt'] ?? '');
    $mail = trim($_POST['mail'] ?? '');
    $tel = trim($_POST['telUt'] ?? '');
    $dateNaissance = trim($_POST['dateNaissance'] ?? '');
    $mdp = trim($_POST['mdp'] ?? '');

    if (!empty($nom) && !empty($prenom) && !empty($mail) && !empty($tel) && !empty($dateNaissance) && !empty($mdp)) {
        $mdpHash = password_hash($mdp, PASSWORD_DEFAULT);
        $idUtType = 2; // Professeur

        $sql = "INSERT INTO utilisateur (mail, mdp, nomUt, prenomUt, telUt, dateNaissance, idUtType, idSection)
                VALUES (:mail, :mdp, :nomUt, :prenomUt, :telUt, :dateNaissance, :idUtType, NULL)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':mail' => $mail,
            ':mdp' => $mdpHash,
            ':nomUt' => $nom,
            ':prenomUt' => $prenom,
            ':telUt' => $tel,
            ':dateNaissance' => $dateNaissance,
            ':idUtType' => $idUtType
        ]);

        $message = "Professeur ajouté avec succès.";
    } else {
        $message = "Tous les champs sont obligatoires.";
    }
}

/*traitement pour supprimer un prof*/
if (isset($_GET['supprimer'])) {
    $id = (int) $_GET['supprimer'];

    $sql = "DELETE FROM utilisateur WHERE idUt = :idUt AND idUtType = 2";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':idUt' => $id]);

    $message = "Professeur supprimé avec succès.";
}

/*preapartion de la modif des profs*/
if (isset($_GET['modifier'])) {
    $id = (int) $_GET['modifier'];

    $sql = "SELECT * FROM utilisateur WHERE idUt = :idUt AND idUtType = 2";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':idUt' => $id]);
    $profAModifier = $stmt->fetch(PDO::FETCH_ASSOC);
}

/*modifs des profs existants*/
if (isset($_POST['update'])) {
    $id = (int) ($_POST['idUt'] ?? 0);
    $nom = trim($_POST['nomUt'] ?? '');
    $prenom = trim($_POST['prenomUt'] ?? '');
    $mail = trim($_POST['mail'] ?? '');
    $tel = trim($_POST['telUt'] ?? '');
    $dateNaissance = trim($_POST['dateNaissance'] ?? '');

    if (!empty($nom) && !empty($prenom) && !empty($mail) && !empty($tel) && !empty($dateNaissance)) {
        $sql = "UPDATE utilisateur
                SET nomUt = :nomUt,
                    prenomUt = :prenomUt,
                    mail = :mail,
                    telUt = :telUt,
                    dateNaissance = :dateNaissance
                WHERE idUt = :idUt AND idUtType = 2";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':nomUt' => $nom,
            ':prenomUt' => $prenom,
            ':mail' => $mail,
            ':telUt' => $tel,
            ':dateNaissance' => $dateNaissance,
            ':idUt' => $id
        ]);

        $message = "Professeur modifié avec succès.";
    } else {
        $message = "Tous les champs sont obligatoires.";
    }
}

/*affichage des profs*/
$sql = "SELECT * FROM utilisateur WHERE idUtType = 2 ORDER BY nomUt, prenomUt";
$stmt = $conn->query($sql);
$professeurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content">
    <h2>Gérer les professeurs</h2>

    <?php if (!empty($message)) : ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <?php if ($profAModifier) : ?>
        <h3>Modifier un professeur</h3>
        <form method="post" action="index.php?page=gererlesProfesseurs">
            <input type="hidden" name="idUt" value="<?php echo htmlspecialchars($profAModifier['idUt']); ?>">

            <label for="nomUt">Nom :</label>
            <input type="text" name="nomUt" id="nomUt" value="<?php echo htmlspecialchars($profAModifier['nomUt']); ?>" required><br><br>

            <label for="prenomUt">Prénom :</label>
            <input type="text" name="prenomUt" id="prenomUt" value="<?php echo htmlspecialchars($profAModifier['prenomUt']); ?>" required><br><br>

            <label for="mail">Email :</label>
            <input type="email" name="mail" id="mail" value="<?php echo htmlspecialchars($profAModifier['mail']); ?>" required><br><br>

            <label for="telUt">Téléphone :</label>
            <input type="text" name="telUt" id="telUt" value="<?php echo htmlspecialchars($profAModifier['telUt']); ?>" required><br><br>

            <label for="dateNaissance">Date de naissance :</label>
            <input type="date" name="dateNaissance" id="dateNaissance" value="<?php echo htmlspecialchars($profAModifier['dateNaissance']); ?>" required><br><br>

            <input type="submit" name="update" value="Modifier">
        </form>
    <?php else : ?>
        <h3>Ajouter un professeur</h3>
        <form method="post" action="index.php?page=gererlesProfesseurs">
            <label for="nomUt">Nom :</label>
            <input type="text" name="nomUt" id="nomUt" required><br><br>

            <label for="prenomUt">Prénom :</label>
            <input type="text" name="prenomUt" id="prenomUt" required><br><br>

            <label for="mail">Email :</label>
            <input type="email" name="mail" id="mail" required><br><br>

            <label for="telUt">Téléphone :</label>
            <input type="text" name="telUt" id="telUt" required><br><br>

            <label for="dateNaissance">Date de naissance :</label>
            <input type="date" name="dateNaissance" id="dateNaissance" required><br><br>

            <label for="mdp">Mot de passe :</label>
            <input type="password" name="mdp" id="mdp" required><br><br>

            <input type="submit" name="ajouter" value="Ajouter">
        </form>
    <?php endif; ?>

    <h3>Liste des professeurs</h3>
    <table border="1" cellpadding="10" cellspacing="0">
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Email</th>
            <th>Téléphone</th>
            <th>Date de naissance</th>
            <th>Actions</th>
        </tr>

        <?php foreach ($professeurs as $prof) : ?>
            <tr>
                <td><?php echo htmlspecialchars($prof['idUt']); ?></td>
                <td><?php echo htmlspecialchars($prof['nomUt']); ?></td>
                <td><?php echo htmlspecialchars($prof['prenomUt']); ?></td>
                <td><?php echo htmlspecialchars($prof['mail']); ?></td>
                <td><?php echo htmlspecialchars($prof['telUt']); ?></td>
                <td><?php echo htmlspecialchars($prof['dateNaissance']); ?></td>
                <td>
                    <a href="index.php?page=gererlesProfesseurs&modifier=<?php echo $prof['idUt']; ?>">Modifier</a>
                    |
                    <a href="index.php?page=gererlesProfesseurs&supprimer=<?php echo $prof['idUt']; ?>" onclick="return confirm('Voulez-vous vraiment supprimer ce professeur ?');">Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
