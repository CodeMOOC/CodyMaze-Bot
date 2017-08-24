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
        <span class="font-30"><?= $name ?></span>
        <span class="font-30"><?= $date ?></span>
        <span class="font-30"><?= $guid ?></span>

    </div>

    </body>
    </html>

<?php
$myStaticHtml = ob_get_clean();
file_put_contents('temp.html', $myStaticHtml);
?>