<?php

require_once 'config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

// create instance
$cssToInlineStyles = new CssToInlineStyles();

$html = file_get_contents(__DIR__ . '/examples/sumo/index.htm');
$css = file_get_contents(__DIR__ . '/examples/sumo/style.css');

$cssToInlineStyles->setHTML($html);
$cssToInlineStyles->setCSS($css);

// output
echo $cssToInlineStyles->convert();
