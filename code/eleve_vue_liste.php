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

/* Récupérer les classes où le prof enseigne */
$sql = "SELECT DISTINCT s.idSection, s.formation, s.filiere, s.specialite, s.niveau
        FROM `section-matiere` sm
        JOIN section s ON sm.idSection = s.idSection
        WHERE sm.idUt = :idUt
        ORDER BY s.formation, s.niveau";
$stmt = $conn->prepare($sql);
$stmt->execute([':idUt' => $idProf]);
$sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Si le prof choisit une classe */
$eleves = [];
$sectionChoisie = null;
if (isset($_GET['idSection'])) {
    $idSection = (int) $_GET['idSection'];

    /* Vérifier que le prof enseigne bien dans cette classe */
    $sql = "SELECT COUNT(*) FROM `section-matiere` WHERE idSection = :idSection AND idUt = :idUt";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':idSection' => $idSection, ':idUt' => $idProf]);
    $autorise = $stmt->fetchColumn();

    if ($autorise) {
        $sql = "SELECT idUt, nomUt, prenomUt, mail
                FROM utilisateur
                WHERE idSection = :idSection AND idUtType = 1
                ORDER BY nomUt, prenomUt";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':idSection' => $idSection]);
        $eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);

        /* Nom de la classe choisie pour l'affichage */
        foreach ($sections as $s) {
            if ($s['idSection'] == $idSection) {
                $sectionChoisie = $s;
                break;
            }
        }
    } else {
        die("Accès refusé : vous n'enseignez pas dans cette classe.");
    }
}
?>

<div class="content">
    <h2>Liste des élèves</h2>

    <form method="get" action="index.php">
        <input type="hidden" name="page" value="eleve_vue_liste">

        <label for="idSection">Choisir une classe :</label>
        <select name="idSection" id="idSection">
            <option value="">-- Sélectionner --</option>
            <?php foreach ($sections as $s) : ?>
                <option value="<?php echo $s['idSection']; ?>"
                    <?php echo (isset($_GET['idSection']) && $_GET['idSection'] == $s['idSection']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($s['formation'] . ' ' . $s['filiere'] . ' ' . $s['specialite'] . ' ' . $s['niveau']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="submit" value="Afficher">
    </form>

    <?php if ($sectionChoisie) : ?>
        <h3>
            Classe : <?php echo htmlspecialchars($sectionChoisie['formation'] . ' ' . $sectionChoisie['filiere'] . ' ' . $sectionChoisie['specialite'] . ' ' . $sectionChoisie['niveau']); ?>
        </h3>

        <?php if (empty($eleves)) : ?>
            <p>Aucun élève dans cette classe.</p>
        <?php else : ?>
            <table border="1" cellpadding="10" cellspacing="0">
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                </tr>
                <?php foreach ($eleves as $e) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($e['nomUt']); ?></td>
                        <td><?php echo htmlspecialchars($e['prenomUt']); ?></td>
                        <td><?php echo htmlspecialchars($e['mail']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>