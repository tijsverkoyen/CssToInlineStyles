<?php
// datetime
if (ini_get('date.timezone') == '') {
    date_default_timezone_set('Europe/Brussels');
}

// parse headers
header('content-type: text/html;charset=utf-8');
