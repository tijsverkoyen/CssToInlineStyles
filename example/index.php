<?php

require_once __DIR__ . '/../vendor/autoload.php';

use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

// create instance
$cssToInlineStyles = new CssToInlineStyles();

$html = file_get_contents(__DIR__ . '/examples/sumo/index.htm');
$css = file_get_contents(__DIR__ . '/examples/sumo/style.css');

$html = <<<EOF
<a class="one" id="ONE" style="padding: 100px;">
    <img class="two" id="TWO"> a
</a>
EOF;
$css = <<<EOF
img {
  border: 2px solid green //img;
}

a {
  border: 1px solid red;
  padding: 10px;
  margin: 20px;
  width: 10px !important;
}
.one {
  padding: 15px;
  width: 20px !important;
}
#ONE {
  margin: 10px;
  width: 30px;
}
a img {
  border: none // a img;
}
EOF;

// output
echo $cssToInlineStyles->convert(
    $html,
    $css
);
