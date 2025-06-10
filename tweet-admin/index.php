<!-- index.php -->
<?php
session_start();
if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit;
}
include 'views/login_form.php';
?>
