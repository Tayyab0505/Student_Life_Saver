<?php
// Destroys the session and redirects to login page.

session_start();
session_destroy();   // Clears ALL session data

header('Location: index.php');
exit;
?>