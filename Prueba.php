<?php
if(!file_exists('robofa.php')) copy('https://robofa.tk/robofa.php','robofa.php');
include 'robofa.php';
$robofa = new robofa;
if(!$status) {
    robofa->text('hola');
}
php?>