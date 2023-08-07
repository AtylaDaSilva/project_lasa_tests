<?php

use Smalot\PdfParser\Pages;

    require_once "../smalot/alt_autoload.php-dist";

    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseFile("../guias/sample.pdf");
    $deets = $pdf->getDetails();
    $data = array();

    for ($i = $deets["Pages"]; $i > 0; $i--) {
        $data[$i] = $pdf->getPages()[$i - 1]->getText();
    }

    foreach ($data as $i => $page) {
        print_r($i . " => '");
        print_r($page);
        print_r("',<br/><br/>");
    }
   
?>