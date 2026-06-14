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

$idDevoir = 0;
if (isset($_GET['idDevoir'])) {
    $idDevoir = (int) $_GET['idDevoir'];
} elseif (isset($_POST['idDevoir'])) {
    $idDevoir = (int) $_POST['idDevoir'];
}

if (!$idDevoir) {
    die("Aucun devoir sélectionné.");
}

$sql = "SELECT d.*, m.nomMatiere, s.formation, s.filiere, s.specialite, s.niveau
        FROM devoir d
        JOIN `section-matiere` sm ON d.idSection = sm.idSection AND d.idMatiere = sm.idMatiere
        JOIN matiere m ON d.idMatiere = m.idMatiere
        JOIN section s ON d.idSection = s.idSection
        WHERE d.idDevoir = :idDevoir AND sm.idUt = :idUt";
$stmt = $conn->prepare($sql);
$stmt->execute([':idDevoir' => $idDevoir, ':idUt' => $idProf]);
$devoir = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$devoir) {
    die("Accès refusé ou devoir introuvable.");
}

/* Enregistrer les notes */
if (isset($_POST['enregistrer'])) {
    $notes = $_POST['notes'] ?? [];

    foreach ($notes as $idUt => $note) {
        $idUt = (int) $idUt;
        $note = trim($note);

        if ($note === '') {
            $sql = "DELETE FROM `utilisateur-devoir` WHERE idUt = :idUt AND idDevoir = :idDevoir";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':idUt' => $idUt, ':idDevoir' => $idDevoir]);
        } else {
            $sql = "SELECT COUNT(*) FROM `utilisateur-devoir` WHERE idUt = :idUt AND idDevoir = :idDevoir";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':idUt' => $idUt, ':idDevoir' => $idDevoir]);
            $existe = $stmt->fetchColumn();

            if ($existe) {
                $sql = "UPDATE `utilisateur-devoir` SET note = :note WHERE idUt = :idUt AND idDevoir = :idDevoir";
            } else {
                $sql = "INSERT INTO `utilisateur-devoir` (idUt, idDevoir, note) VALUES (:idUt, :idDevoir, :note)";
            }
            $stmt = $conn->prepare($sql);
            $stmt->execute([':idUt' => $idUt, ':idDevoir' => $idDevoir, ':note' => $note]);
        }
    }
    $message = "Notes enregistrées avec succès.";
}

$sql = "SELECT u.idUt, u.nomUt, u.prenomUt, ud.note
        FROM utilisateur u
        LEFT JOIN `utilisateur-devoir` ud ON u.idUt = ud.idUt AND ud.idDevoir = :idDevoir
        WHERE u.idSection = :idSection AND u.idUtType = 1
        ORDER BY u.nomUt, u.prenomUt";
$stmt = $conn->prepare($sql);
$stmt->execute([':idDevoir' => $idDevoir, ':idSection' => $devoir['idSection']]);
$eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content">
    <h2>Gérer les notes</h2>

    <p>
        <strong>Devoir :</strong> <?php echo htmlspecialchars($devoir['nomMatiere']); ?> —
        <?php echo htmlspecialchars($devoir['formation'] . ' ' . $devoir['filiere'] . ' ' . $devoir['specialite'] . ' ' . $devoir['niveau']); ?> —
        <?php echo date('d/m/Y', $devoir['dateDevoir']); ?> —
        Coefficient : <?php echo htmlspecialchars($devoir['coefficient']); ?>
    </p>

    <a href="index.php?page=gererLesDevoirs">← Retour aux devoirs</a><br><br>

    <?php if (!empty($message)) : ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <?php if (empty($eleves)) : ?>
        <p>Aucun élève dans cette classe.</p>
    <?php else : ?>
        <form method="post" action="index.php?page=gererLesNotes&idDevoir=<?php echo $idDevoir; ?>">
            <input type="hidden" name="idDevoir" value="<?php echo $idDevoir; ?>">

            <table border="1" cellpadding="10" cellspacing="0">
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Note (sur 20)</th>
                </tr>
                <?php foreach ($eleves as $e) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($e['nomUt']); ?></td>
                        <td><?php echo htmlspecialchars($e['prenomUt']); ?></td>
                        <td>
                            <input type="number" name="notes[<?php echo $e['idUt']; ?>]"
                                   step="0.01" min="0" max="20"
                                   value="<?php echo htmlspecialchars($e['note'] ?? ''); ?>">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table><br>

            <input type="submit" name="enregistrer" value="Enregistrer les notes">
        </form>
    <?php endif; ?>
</div>