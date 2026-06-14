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

/* Affecter ou modifier un prof sur un couple section-matiere */
if (isset($_POST['affecter'])) {
    $idSection = (int) ($_POST['idSection'] ?? 0);
    $idMatiere = (int) ($_POST['idMatiere'] ?? 0);
    $idUt      = (int) ($_POST['idUt'] ?? 0);

    $sql = "UPDATE `section-matiere` SET idUt = :idUt WHERE idSection = :idSection AND idMatiere = :idMatiere";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':idUt'      => $idUt ?: null,
        ':idSection' => $idSection,
        ':idMatiere' => $idMatiere
    ]);

    $message = "Affectation mise à jour avec succès.";
}

/* Supprimer l'affectation */
if (isset($_GET['supprimer'])) {
    $idSection = (int) ($_GET['idSection'] ?? 0);
    $idMatiere = (int) ($_GET['idMatiere'] ?? 0);

    $sql = "UPDATE `section-matiere` SET idUt = NULL WHERE idSection = :idSection AND idMatiere = :idMatiere";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':idSection' => $idSection, ':idMatiere' => $idMatiere]);

    $message = "Affectation supprimée avec succès.";
}

/* Section sélectionnée */
$idSectionChoisie = (int) ($_POST['idSectionFiltre'] ?? $_POST['idSection'] ?? $_GET['idSectionFiltre'] ?? 0);

/* Récupérer toutes les sections */
$sections = $conn->query("SELECT * FROM section ORDER BY formation, filiere, specialite, niveau")->fetchAll(PDO::FETCH_ASSOC);

/* Récupérer les matières de la section choisie */
$matieres = [];
if ($idSectionChoisie) {
    $sql = "SELECT m.idMatiere, m.nomMatiere
            FROM `section-matiere` sm
            JOIN matiere m ON sm.idMatiere = m.idMatiere
            WHERE sm.idSection = :idSection
            ORDER BY m.nomMatiere";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':idSection' => $idSectionChoisie]);
    $matieres = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* Récupérer tous les professeurs */
$professeurs = $conn->query("SELECT * FROM utilisateur WHERE idUtType = 2 ORDER BY nomUt, prenomUt")->fetchAll(PDO::FETCH_ASSOC);

/* Récupérer les affectations — filtrées si une classe est choisie */
if ($idSectionChoisie) {
    $sql = "SELECT sm.idSection, sm.idMatiere, sm.idUt,
                   s.formation, s.filiere, s.specialite, s.niveau,
                   m.nomMatiere, u.nomUt, u.prenomUt
            FROM `section-matiere` sm
            JOIN section s ON sm.idSection = s.idSection
            JOIN matiere m ON sm.idMatiere = m.idMatiere
            LEFT JOIN utilisateur u ON sm.idUt = u.idUt
            WHERE sm.idSection = :idSection
            ORDER BY m.nomMatiere";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':idSection' => $idSectionChoisie]);
} else {
    $sql = "SELECT sm.idSection, sm.idMatiere, sm.idUt,
                   s.formation, s.filiere, s.specialite, s.niveau,
                   m.nomMatiere, u.nomUt, u.prenomUt
            FROM `section-matiere` sm
            JOIN section s ON sm.idSection = s.idSection
            JOIN matiere m ON sm.idMatiere = m.idMatiere
            LEFT JOIN utilisateur u ON sm.idUt = u.idUt
            ORDER BY s.formation, s.filiere, s.specialite, s.niveau, m.nomMatiere";
    $stmt = $conn->query($sql);
}
$affectations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content">
    <h2>Gérer les affectations professeurs / matières</h2>

    <?php if (!empty($message)) : ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <h3>Modifier une affectation</h3>

    <!-- Étape 1 : choisir la classe -->
    <form method="post" action="index.php?page=affectationProfesseurs">
        <label for="idSectionFiltre">Classe :</label>
        <select name="idSectionFiltre" id="idSectionFiltre">
            <option value="">-- Toutes les classes --</option>
            <?php foreach ($sections as $s) : ?>
                <option value="<?php echo $s['idSection']; ?>" <?php echo $idSectionChoisie == $s['idSection'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($s['formation'] . ' ' . $s['filiere'] . ' ' . $s['specialite'] . ' ' . $s['niveau']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="submit" value="Sélectionner">
    </form><br>

    <!-- Étape 2 : affecter si une classe est choisie -->
    <?php if ($idSectionChoisie && !empty($matieres)) : ?>
        <form method="post" action="index.php?page=affectationProfesseurs">
            <input type="hidden" name="idSection" value="<?php echo $idSectionChoisie; ?>">

            <label for="idMatiere">Matière :</label>
            <select name="idMatiere" id="idMatiere" required>
                <option value="">-- Choisir une matière --</option>
                <?php foreach ($matieres as $m) : ?>
                    <option value="<?php echo $m['idMatiere']; ?>">
                        <?php echo htmlspecialchars($m['nomMatiere']); ?>
                    </option>
                <?php endforeach; ?>
            </select><br><br>

            <label for="idUt">Professeur :</label>
            <select name="idUt" id="idUt">
                <option value="">-- Aucun --</option>
                <?php foreach ($professeurs as $p) : ?>
                    <option value="<?php echo $p['idUt']; ?>">
                        <?php echo htmlspecialchars($p['nomUt'] . ' ' . $p['prenomUt']); ?>
                    </option>
                <?php endforeach; ?>
            </select><br><br>

            <input type="submit" name="affecter" value="Enregistrer">
        </form>
    <?php elseif ($idSectionChoisie) : ?>
        <p>Aucune matière trouvée pour cette classe.</p>
    <?php endif; ?>

    <h3>Liste des affectations <?php echo $idSectionChoisie ? '(classe filtrée)' : '(toutes les classes)'; ?></h3>
    <table border="1" cellpadding="10" cellspacing="0">
        <tr>
            <th>Classe</th>
            <th>Matière</th>
            <th>Professeur affecté</th>
            <th>Action</th>
        </tr>
        <?php foreach ($affectations as $a) : ?>
            <tr>
                <td><?php echo htmlspecialchars($a['formation'] . ' ' . $a['filiere'] . ' ' . $a['specialite'] . ' ' . $a['niveau']); ?></td>
                <td><?php echo htmlspecialchars($a['nomMatiere']); ?></td>
                <td><?php echo $a['idUt'] ? htmlspecialchars($a['nomUt'] . ' ' . $a['prenomUt']) : 'Aucun'; ?></td>
                <td>
                    <?php if ($a['idUt']) : ?>
                        <a href="index.php?page=affectationProfesseurs&supprimer=1&idSection=<?php echo $a['idSection']; ?>&idMatiere=<?php echo $a['idMatiere']; ?>"
                           onclick="return confirm('Supprimer cette affectation ?');">Supprimer</a>
                    <?php else : ?>
                        —
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>