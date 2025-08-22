<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header("Location: retailer_login.php"); exit(); }
if (!isset($_POST['product_id'])) { header("Location: pos_cart.php"); exit(); }

$pid = (int)$_POST['product_id'];
if (isset($_SESSION['pos_cart'][$pid])) unset($_SESSION['pos_cart'][$pid]);
$_SESSION['pos_msg'] = "Item removed.";
header("Location: pos_cart.php");
