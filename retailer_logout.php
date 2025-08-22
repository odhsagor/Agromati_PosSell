<?php
session_start();
$_SESSION = array();
session_destroy();
header("Location: retailer_login.php");
exit();
?>