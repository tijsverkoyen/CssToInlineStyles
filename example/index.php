<?php
require_once __DIR__ . '/../vendor/autoload.php';

if( file_exists(__DIR__.'/config.php')) {
    require_once file_exists(__DIR__.'/config.php');
}

use \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

// create instance
$cssToInlineStyles = new CssToInlineStyles();

$html = file_get_contents(__DIR__.'/sumo/index.htm');
$css = file_get_contents(__DIR__.'/sumo/style.css');

$cssToInlineStyles->setHTML($html);
$cssToInlineStyles->setCSS($css);

// output
echo $cssToInlineStyles->convert();
