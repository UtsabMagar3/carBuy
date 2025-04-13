<?php
$server = 'mysql';
$username = 'v.je';
$password = 'v.je';
$database = 'assignment1';

$datapageConnection = new PDO('mysql:dbname=' . $database . ';host=' . $server, $username, $password);
?>
