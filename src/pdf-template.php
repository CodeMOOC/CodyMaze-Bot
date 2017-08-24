<?php
ob_start();
?>

    <html>
    <head>
        <meta charset="UTF-8" />
        <link href="css/pdf-template.css" rel="stylesheet" />
    </head>
    <body>

    <div id="container">
        <span class="spaced font-20">Questo certificato attesta che</span>
        <span class="font-30"><?= $name ?></span>
        <span class="font-20 padded">ha concluso con successo l'attività dell'ora di coding con CodyMaze sperimentando le istruzioni elementari, le istruzioni in sequenza, le condizioni, le ripetizioni e le ripetizioni condizionate.</span>
        <span class="font-20 padded">Questo certificato, rilasciato in data <?= $date ?>, ha l'identificativo <?= $guid ?></span>
    </div>

    </body>
    </html>

<?php
$myStaticHtml = ob_get_clean();
file_put_contents('temp.html', $myStaticHtml);
?>