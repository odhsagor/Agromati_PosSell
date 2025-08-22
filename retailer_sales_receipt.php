<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header("Location: retailer_login.php"); exit(); }
if (!isset($_GET['sale_id'])) { header("Location: retailer_dashboard.php"); exit(); }
$sale_id = (int)$_GET['sale_id'];

$db = new mysqli('localhost','root','','agromatiDB'); if ($db->connect_error) die($db->connect_error);

// numeric retailer id
$stmt = $db->prepare("SELECT id, name FROM retailers WHERE retailer_id=? LIMIT 1");
$stmt->bind_param("s", $_SESSION['retailer_id']); $stmt->execute();
$ret = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$ret) { die("Retailer not found."); }
$retailer_id = (int)$ret['id'];
$retailer_name = $ret['name'];

$stmt = $db->prepare("SELECT * FROM retailer_sales WHERE id=? AND retailer_id=? LIMIT 1");
$stmt->bind_param("ii",$sale_id,$retailer_id);
$stmt->execute(); $sale = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$sale) { die("Sale not found."); }

$sql = "SELECT rsi.quantity, rsi.unit_price, rsi.subtotal, p.name, p.unit_of_measure
        FROM retailer_sale_items rsi
        JOIN products p ON p.id = rsi.product_id
        WHERE rsi.sale_id = ?";
$stmt = $db->prepare($sql); $stmt->bind_param("i",$sale_id);
$stmt->execute(); $res = $stmt->get_result(); $lines = $res->fetch_all(MYSQLI_ASSOC); $stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Receipt #<?=$sale_id?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>@media print {.no-print{display:none}}</style>
</head>
<body class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Receipt #<?=$sale_id?></h3>
    <div class="no-print">
      <button class="btn btn-outline-secondary" onclick="window.print()">Print</button>
      <a class="btn btn-outline-primary" href="retailer_pos.php">New Sale</a>
    </div>
  </div>
  <div class="mb-2"><strong>Retailer:</strong> <?=htmlspecialchars($retailer_name)?></div>
  <div class="mb-2"><strong>Date:</strong> <?=htmlspecialchars($sale['created_at'])?></div>
  <?php if($sale['customer_name']): ?><div class="mb-2"><strong>Customer:</strong> <?=htmlspecialchars($sale['customer_name'])?></div><?php endif; ?>
  <?php if($sale['customer_phone']): ?><div class="mb-2"><strong>Phone:</strong> <?=htmlspecialchars($sale['customer_phone'])?></div><?php endif; ?>

  <div class="table-responsive mt-3">
    <table class="table table-sm">
      <thead><tr><th>Product</th><th class="text-end">Qty</th><th>Unit</th><th class="text-end">Price (৳)</th><th class="text-end">Subtotal (৳)</th></tr></thead>
      <tbody>
      <?php foreach ($lines as $ln): ?>
        <tr>
          <td><?=htmlspecialchars($ln['name'])?></td>
          <td class="text-end"><?=number_format($ln['quantity'])?></td>
          <td><?=htmlspecialchars($ln['unit_of_measure'])?></td>
          <td class="text-end"><?=number_format($ln['unit_price'],2)?></td>
          <td class="text-end"><?=number_format($ln['subtotal'],2)?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="4" class="text-end">Total Items</th>
          <th class="text-end"><?=number_format($sale['total_items'])?></th>
        </tr>
        <tr>
          <th colspan="4" class="text-end">Grand Total</th>
          <th class="text-end">৳<?=number_format($sale['total_amount'],2)?></th>
        </tr>
      </tfoot>
    </table>
  </div>
</body>
</html>
