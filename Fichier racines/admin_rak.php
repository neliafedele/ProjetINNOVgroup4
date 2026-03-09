<?php
$statsParJour = json_decode(file_get_contents('stats.json'), true);

if (!is_array($statsParJour)) {
    $statsParJour = [];
}
?>

<style>
    .progress-container { width: 100%; max-width: 400px; margin: 20px 0; font-family: sans-serif; }
    .progress-bar-bg { background: #eee; border-radius: 10px; height: 20px; width: 100%; margin-bottom: 10px; }
    .progress-fill { height: 100%; border-radius: 10px; transition: width 1s ease-in-out; }
    .fill-oui { background: #27ae60; }
    .fill-non { background: #e74c3c; }
    .fill-peut-etre { background: #f4d435; }
</style>

<?php if (empty($statsParJour)): ?>
    <p>Aucune statistique disponible.</p>
<?php else: ?>
    <?php foreach ($statsParJour as $jour => $statsJour): ?>
        <?php foreach (['midi', 'soir'] as $periode):
            $periodeStats = (isset($statsJour[$periode]) && is_array($statsJour[$periode])) ? $statsJour[$periode] : [];
            $oui = isset($periodeStats['oui']) ? (int) $periodeStats['oui'] : 0;
            $non = isset($periodeStats['non']) ? (int) $periodeStats['non'] : 0;
            $peutEtre = isset($periodeStats['peut-etre']) ? (int) $periodeStats['peut-etre'] : 0;

            $votes = [
                'oui' => $oui,
                'non' => $non,
                'peut-etre' => $peutEtre
            ];
            $total = array_sum($votes);
        ?>
            <div class="progress-container">
                <h3>Résultats du CROUS (<?php echo ucfirst($jour); ?> - <?php echo ucfirst($periode); ?>) :</h3>

                <?php foreach ($votes as $choix => $quantite):
                    $pourcentage = ($total > 0) ? round(($quantite / $total) * 100) : 0;
                    $couleur = ($choix == 'oui') ? 'fill-oui' : (($choix == 'non') ? 'fill-non' : 'fill-peut-etre');
                ?>
                    <p><?php echo ucfirst($choix); ?> : <?php echo $pourcentage; ?>% (<?php echo $quantite; ?> votes)</p>
                    <div class="progress-bar-bg">
                        <div class="progress-fill <?php echo $couleur; ?>" style="width: <?php echo $pourcentage; ?>%"></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
<?php endif; ?>