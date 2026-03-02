<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fichier = 'stats.json';
    $jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi'];
    $periodes = ['midi', 'soir'];
    $choixValides = ['oui', 'non', 'peut-etre'];

    $contenu = file_exists($fichier) ? file_get_contents($fichier) : '';
    $donnees = json_decode($contenu, true);

    if (!is_array($donnees)) {
        $donnees = [];
    }

    $donneesNormalisees = [];
    foreach ($jours as $jour) {
        $donneesNormalisees[$jour] = [];

        foreach ($periodes as $periode) {
            $donneesNormalisees[$jour][$periode] = [
                'oui' => isset($donnees[$jour][$periode]['oui']) ? (int) $donnees[$jour][$periode]['oui'] : 0,
                'non' => isset($donnees[$jour][$periode]['non']) ? (int) $donnees[$jour][$periode]['non'] : 0,
                'peut-etre' => isset($donnees[$jour][$periode]['peut-etre']) ? (int) $donnees[$jour][$periode]['peut-etre'] : 0
            ];

            $nomChamp = 'presence_' . $jour . '_' . $periode;
            $choix = isset($_POST[$nomChamp]) ? $_POST[$nomChamp] : null;

            if (!in_array($choix, $choixValides, true)) {
                header("Location: index.php");
                exit;
            }

            $donneesNormalisees[$jour][$periode][$choix]++;
        }
    }

    file_put_contents($fichier, json_encode($donneesNormalisees, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

    header("Refresh:2; url=index.php");
    echo "Merci ! Vos réponses ont été prises en compte pour le RAK.";
    exit;
}
?>