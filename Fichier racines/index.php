<?php
$jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi'];
$periodes = ['midi', 'soir'];
?>

<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#27ae60">
    <link rel="manifest" href="manifest.json">
    <title>Sondage CROUS</title>
</head>
<body>
    <div id="rak-survey">
        <form action="vote.php" method="POST">
            <?php foreach ($jours as $jour): ?>
                <?php foreach ($periodes as $periode): ?>
                    <?php $nomChamp = 'presence_' . $jour . '_' . $periode; ?>
                    <h3>Mangerez-vous au CROUS <?php echo $jour; ?> <?php echo $periode; ?> ?</h3>
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

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('service-worker.js');
            });
        }
    </script>
</body>
</html>
