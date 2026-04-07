<?php 
    if(session_status()===PHP_SESSION_NONE){
        session_start(); // Start session only if not start
    }

    if(!isset($_SESSION['user_id'])){
        // Not loged in -> send back to login page
        header('Location: ../index.php');
        exit;
    }

    $current_user_id = $_SESSION['user_id'];
    $current_user_name = $_SESSION['user_name'];
?>