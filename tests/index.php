<?php

//require
require_once '../../../autoload.php';
require_once 'config.php';

use \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

// create instance
$cssToInlineStyles = new CssToInlineStyles();

$html = file_get_contents('./examples/sumo/index.htm');
$css = file_get_contents('./examples/sumo/style.css');

$cssToInlineStyles->setHTML($html);
$cssToInlineStyles->setCSS($css);

// output
echo $cssToInlineStyles->convert();
