<?php
if (!isset($_SESSION['login'])) {
    header("Location: utilisateur_vue_connexion.php");
    exit();
}

if (!estProfesseur()) {
    die("Accès refusé : cette page est réservée aux professeurs.");
}

$conn = bddconnect();
$idProf = $_SESSION['idUtilisateur'];
$message = "";

/* Ajouter un devoir */
if (isset($_POST['ajouter'])) {
    $idSection    = (int) ($_POST['idSection'] ?? 0);
    $idMatiere    = (int) ($_POST['idMatiere'] ?? 0);
    $dateDevoir   = trim($_POST['dateDevoir'] ?? '');
    $coefficient  = trim($_POST['coefficient'] ?? '');

    if ($idSection && $idMatiere && !empty($dateDevoir) && !empty($coefficient)) {
        $sql = "INSERT INTO devoir (dateDevoir, coefficient, idSection, idMatiere)
                VALUES (:dateDevoir, :coefficient, :idSection, :idMatiere)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':dateDevoir'  => strtotime($dateDevoir),
            ':coefficient' => $coefficient,
            ':idSection'   => $idSection,
            ':idMatiere'   => $idMatiere
        ]);
        $message = "Devoir ajouté avec succès.";
    } else {
        $message = "Tous les champs sont obligatoires.";
    }
}

/* Supprimer un devoir */
if (isset($_GET['supprimer'])) {
    $idDevoir = (int) $_GET['supprimer'];

    /* Vérifier que le devoir appartient bien à une matière du prof */
    $sql = "SELECT d.idDevoir FROM devoir d
            JOIN `section-matiere` sm ON d.idSection = sm.idSection AND d.idMatiere = sm.idMatiere
            WHERE d.idDevoir = :idDevoir AND sm.idUt = :idUt";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':idDevoir' => $idDevoir, ':idUt' => $idProf]);

    if ($stmt->fetch()) {
        /* Supprimer d'abord les notes liées */
        $sql = "DELETE FROM `utilisateur-devoir` WHERE idDevoir = :idDevoir";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':idDevoir' => $idDevoir]);

        /* Supprimer le devoir */
        $sql = "DELETE FROM devoir WHERE idDevoir = :idDevoir";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':idDevoir' => $idDevoir]);

        $message = "Devoir supprimé avec succès.";
    } else {
        $message = "Action non autorisée.";
    }
}

/* Préparer la modification */
$devoirAModifier = null;
if (isset($_GET['modifier'])) {
    $idDevoir = (int) $_GET['modifier'];

    $sql = "SELECT d.* FROM devoir d
            JOIN `section-matiere` sm ON d.idSection = sm.idSection AND d.idMatiere = sm.idMatiere
            WHERE d.idDevoir = :idDevoir AND sm.idUt = :idUt";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':idDevoir' => $idDevoir, ':idUt' => $idProf]);
    $devoirAModifier = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* Modifier un devoir */
if (isset($_POST['update'])) {
    $idDevoir    = (int) ($_POST['idDevoir'] ?? 0);
    $dateDevoir  = trim($_POST['dateDevoir'] ?? '');
    $coefficient = trim($_POST['coefficient'] ?? '');

    if ($idDevoir && !empty($dateDevoir) && !empty($coefficient)) {
        $sql = "UPDATE devoir SET dateDevoir = :dateDevoir, coefficient = :coefficient
                WHERE idDevoir = :idDevoir";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':dateDevoir'  => strtotime($dateDevoir),
            ':coefficient' => $coefficient,
            ':idDevoir'    => $idDevoir
        ]);
        $message = "Devoir modifié avec succès.";
    } else {
        $message = "Tous les champs sont obligatoires.";
    }
}

/* Récupérer les classes du prof */
$sql = "SELECT DISTINCT s.idSection, s.formation, s.filiere, s.specialite, s.niveau
        FROM `section-matiere` sm
        JOIN section s ON sm.idSection = s.idSection
        WHERE sm.idUt = :idUt
        ORDER BY s.formation, s.niveau";
$stmt = $conn->prepare($sql);
$stmt->execute([':idUt' => $idProf]);
$sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Récupérer les matières du prof */
$sql = "SELECT m.idMatiere, m.nomMatiere, sm.idSection
        FROM `section-matiere` sm
        JOIN matiere m ON sm.idMatiere = m.idMatiere
        WHERE sm.idUt = :idUt
        ORDER BY m.nomMatiere";
$stmt = $conn->prepare($sql);
$stmt->execute([':idUt' => $idProf]);
$matieres = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Récupérer la liste des devoirs du prof */
$sql = "SELECT d.idDevoir, d.dateDevoir, d.coefficient,
               s.formation, s.filiere, s.specialite, s.niveau,
               m.nomMatiere
        FROM devoir d
        JOIN `section-matiere` sm ON d.idSection = sm.idSection AND d.idMatiere = sm.idMatiere
        JOIN section s ON d.idSection = s.idSection
        JOIN matiere m ON d.idMatiere = m.idMatiere
        WHERE sm.idUt = :idUt
        ORDER BY d.dateDevoir DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([':idUt' => $idProf]);
$devoirs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content">
    <h2>Gérer les devoirs</h2>

    <?php if (!empty($message)) : ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <?php if ($devoirAModifier) : ?>
        <h3>Modifier un devoir</h3>
        <form method="post" action="index.php?page=gererLesDevoirs">
            <input type="hidden" name="idDevoir" value="<?php echo $devoirAModifier['idDevoir']; ?>">

            <label for="dateDevoir">Date :</label>
            <input type="date" name="dateDevoir" id="dateDevoir"
                   value="<?php echo date('Y-m-d', $devoirAModifier['dateDevoir']); ?>" required><br><br>

            <label for="coefficient">Coefficient :</label>
            <input type="number" name="coefficient" id="coefficient" step="0.01" min="0"
                   value="<?php echo htmlspecialchars($devoirAModifier['coefficient']); ?>" required><br><br>

            <input type="submit" name="update" value="Modifier">
        </form>

    <?php else : ?>
        <h3>Ajouter un devoir</h3>
        <form method="post" action="index.php?page=gererLesDevoirs">

            <label for="idSection">Classe :</label>
            <select name="idSection" id="idSection" required>
                <option value="">-- Choisir une classe --</option>
                <?php foreach ($sections as $s) : ?>
                    <option value="<?php echo $s['idSection']; ?>">
                        <?php echo htmlspecialchars($s['formation'] . ' ' . $s['filiere'] . ' ' . $s['specialite'] . ' ' . $s['niveau']); ?>
                    </option>
                <?php endforeach; ?>
            </select><br><br>

            <label for="idMatiere">Matière :</label>
            <select name="idMatiere" id="idMatiere" required>
                <option value="">-- Choisir une matière --</option>
                <?php foreach ($matieres as $m) : ?>
                    <option value="<?php echo $m['idMatiere']; ?>">
                        <?php echo htmlspecialchars($m['nomMatiere']); ?>
                    </option>
                <?php endforeach; ?>
            </select><br><br>

            <label for="dateDevoir">Date :</label>
            <input type="date" name="dateDevoir" id="dateDevoir" required><br><br>

            <label for="coefficient">Coefficient :</label>
            <input type="number" name="coefficient" id="coefficient" step="0.01" min="0" required><br><br>

            <input type="submit" name="ajouter" value="Ajouter">
        </form>
    <?php endif; ?>

    <h3>Liste des devoirs</h3>
    <?php if (empty($devoirs)) : ?>
        <p>Aucun devoir pour l'instant.</p>
    <?php else : ?>
        <table border="1" cellpadding="10" cellspacing="0">
            <tr>
                <th>ID</th>
                <th>Classe</th>
                <th>Matière</th>
                <th>Date</th>
                <th>Coefficient</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($devoirs as $d) : ?>
                <tr>
                    <td><?php echo $d['idDevoir']; ?></td>
                    <td><?php echo htmlspecialchars($d['formation'] . ' ' . $d['filiere'] . ' ' . $d['specialite'] . ' ' . $d['niveau']); ?></td>
                    <td><?php echo htmlspecialchars($d['nomMatiere']); ?></td>
                    <td><?php echo date('d/m/Y', $d['dateDevoir']); ?></td>
                    <td><?php echo htmlspecialchars($d['coefficient']); ?></td>
                    <td>
                        <a href="index.php?page=gererLesDevoirs&modifier=<?php echo $d['idDevoir']; ?>">Modifier</a>
                        |
                        <a href="index.php?page=gererLesDevoirs&supprimer=<?php echo $d['idDevoir']; ?>"
                           onclick="return confirm('Supprimer ce devoir et toutes ses notes ?');">Supprimer</a>
                        |
                        <a href="index.php?page=gererLesNotes&idDevoir=<?php echo $d['idDevoir']; ?>">Gérer les notes</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
