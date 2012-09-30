<?php

//require
require_once 'config.php';
require_once '../css_to_inline_styles.php';

// create instance
$cssToInlineStyles = new CSSToInlineStyles();

$html = file_get_contents('./examples/sumo/index.htm');
$css = file_get_contents('./examples/sumo/style.css');

$cssToInlineStyles->setHTML($html);
$cssToInlineStyles->setCSS($css);

//output
echo $cssToInlineStyles->convert();
