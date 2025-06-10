<!-- logout.php -->
<?php
session_start();
session_destroy();
header("Location: views/login_form.php");
exit;
