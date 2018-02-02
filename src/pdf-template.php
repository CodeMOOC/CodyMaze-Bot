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
        <div class="wrapper">
            <div class="title">Certificate of Completion</div>
            <div class="name"><?= $name ?></div>
            <div class="font-20">has successfully completed the “Hour of Code” activity with <b>CodyMaze</b>, performing code interpretation with basic coding instructions, among which sequence of elementary instructions, conditionals, repetitions, and conditional repetitions.</div>
            <div class="font-20 details">This certificate, released on <span class="unbreakable"><?= $date ?>,</span> has the following identifier: <span class="unbreakable"><?= $guid ?>.</span></div>
        </div>
    </div>

    </body>
    </html>

<?php
$myStaticHtml = ob_get_clean();
file_put_contents('temp.html', $myStaticHtml);
?>