<?php
$jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi'];
$periodes = ['midi', 'soir'];
?>

<div id="rak-survey">
    <form action="vote.php" method="POST">
        <?php foreach ($jours as $jour): ?>
            <?php foreach ($periodes as $periode): ?>
                <?php $nomChamp = 'presence_' . $jour . '_' . $periode; ?>
                <h3>Mangerez-vous au RAK <?php echo $jour; ?> <?php echo $periode; ?> ?</h3>
                <label>
                    <input type="radio" name="<?php echo $nomChamp; ?>" value="oui" required> Oui, je viens !
                </label><br>
                <label>
                    <input type="radio" name="<?php echo $nomChamp; ?>" value="non"> Non, pas cette fois.
                </label><br>
                <label>
                    <input type="radio" name="<?php echo $nomChamp; ?>" value="peut-etre"> Je ne sais pas encore.
                </label><br><br>
            <?php endforeach; ?>
        <?php endforeach; ?>

        <button type="submit">Envoyer ma réponse</button>
    </form>
</div>
