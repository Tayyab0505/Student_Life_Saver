<?php

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'student_lifesaver';

$connection = mysqli_connect($host, $user, $pass, $dbname)
if (!$connection) {
    die('Connection to this database failed ' . mysqli_connect_error());
}

?>