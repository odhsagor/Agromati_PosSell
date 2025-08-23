<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header("Location: retailer_login.php"); exit(); }

$db = new mysqli('localhost','root','','agromatiDB');
if ($db->connect_error) { die("DB error: ".$db->connect_error); }

$sessionRetailerId = $_SESSION['retailer_id'];
$stmt = $db->prepare("SELECT id FROM retailers WHERE retailer_id=?");
$stmt->bind_param("s",$sessionRetailerId);
$stmt->execute(); $ridRow = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$ridRow) { die("Retailer not found."); }
$retailer_id = (int)$ridRow['id'];

$stmt = $db->prepare("SELECT id FROM carts WHERE retailer_id=? AND status='active' LIMIT 1");
$stmt->bind_param("i",$retailer_id);
$stmt->execute(); $cartRow = $stmt->get_result()->fetch_assoc(); $stmt->close();
$cart_id = $cartRow ? (int)$cartRow['id'] : 0;

$items = [];
$total = 0.00;
if ($cart_id) {
  $sql = "SELECT ci.id as cart_item_id, p.id as product_id, p.name, p.user_id AS farmer_id, ci.quantity, ci.unit_price, ci.subtotal, p.total_units
          FROM cart_items ci JOIN products p ON p.id = ci.product_id
          WHERE ci.cart_id=?";
  $stmt = $db->prepare($sql);
  $stmt->bind_param("i",$cart_id);
  $stmt->execute(); $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) { $items[] = $row; $total += (float)$row['subtotal']; }
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Cart - AGROMATI</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/retailer_styles.css">
</head>
<body class="agri-container">
  <aside class="agri-sidebar">
      <div class="agri-sidebar-header">
            <img src="Image/logo.png" alt="AGROMATI Logo" class="logo-image">
      </div>
      <nav class="agri-sidebar-nav">
          <ul>
              <li class="agri-nav-item" data-page="dashboard">
                  <a href="retailer_dashboard.php" class="agri-nav-link">
                      <i class="fas fa-tachometer-alt agri-nav-icon"></i> Dashboard
                  </a>
              </li>
              <li class="agri-nav-item" data-page="Shop">
                  <a href="shop.php" class="agri-nav-link">
                      <i class="fas fa-users agri-nav-icon"></i> Shop
                  </a>
              </li>
              <li class="agri-nav-item active" data-page="Cart">
                  <a href="cart.php" class="agri-nav-link">
                      <i class="fas fa-clipboard-list agri-nav-icon"></i> Cart
                  </a>
              </li>
              <li class="agri-nav-item" data-page="My orders">
                  <a href="retailer_orders.php" class="agri-nav-link">
                      <i class="fas fa-clipboard-list agri-nav-icon"></i> Orders
                  </a>
              </li>
              <li class="agri-nav-item" data-page="pos">
                    <a href="retailer_pos.php" class="agri-nav-link">
                        <i class="fas fa-cash-register agri-nav-icon"></i> POS (Sell)
                    </a>
                </li>
                <li class="agri-nav-item" data-page="pos_cart">
                    <a href="pos_cart.php" class="agri-nav-link">
                        <i class="fas fa-shopping-cart agri-nav-icon"></i> POS Cart
                    </a>
                </li>
                <li class="agri-nav-item" data-page="pos_sales">
                    <a href="retailer_posSell.php" class="agri-nav-link">
                        <i class="fas fa-history agri-nav-icon"></i> POS Sales History
                    </a>
                </li>
                <li class="agri-nav-item" data-page="stock">
                      <a href="retailer_stock.php" class="agri-nav-link">
                          <i class="fas fa-warehouse agri-nav-icon"></i> 
                            Stock Movement
                      </a>
                  </li>
              <li class="agri-nav-item">
                  <a href="retailer_logout.php" class="agri-nav-link">
                      <i class="fas fa-sign-out-alt agri-nav-icon"></i> Logout
                  </a>
              </li>
          </ul>
      </nav>
  </aside>

  <main class="flex-grow-1 p-4">
  <div class="page-shell">

    <div class="card-panel card-panel--compact rounded-xl">
      <div class="page-header">
        <h2>Your Cart</h2>
        <div>
          <a class="btn btn-outline-secondary me-2" href="shop.php">
            <i class="fas fa-store me-1"></i> Continue Shopping
          </a>
          <a class="btn btn-outline-primary" href="retailer_dashboard.php">
            <i class="fas fa-home me-1"></i> Dashboard
          </a>
        </div>
      </div>

      <?php if(isset($_SESSION['cart_error'])): ?>
        <div class="alert alert-warning"><?=$_SESSION['cart_error']; unset($_SESSION['cart_error']);?></div>
      <?php endif; ?>

      <?php if (!$cart_id || empty($items)): ?>
        <div class="alert alert-info">Your cart is empty.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead><tr>
              <th>Product</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th><th>Stock</th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
              <tr>
                <td><?=htmlspecialchars($it['name'])?></td>
                <td style="max-width:140px">
                  <form class="d-flex" method="post" action="update_cart.php">
                    <input type="hidden" name="cart_item_id" value="<?=$it['cart_item_id']?>">
                    <input type="number" name="quantity" class="form-control me-2" min="1" max="<?=$it['total_units']?>" value="<?=$it['quantity']?>" required>
                    <button class="btn btn-sm btn-outline-secondary">Update</button>
                  </form>
                </td>
                <td>৳<?=number_format($it['unit_price'],2)?></td>
                <td>৳<?=number_format($it['subtotal'],2)?></td>
                <td><?=number_format($it['total_units'])?></td>
                <td>
                  <form method="post" action="remove_from_cart.php" onsubmit="return confirm('Remove this item?')">
                    <input type="hidden" name="cart_item_id" value="<?=$it['cart_item_id']?>">
                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr>
                <th colspan="3" class="text-end">Total:</th>
                <th colspan="3">৳<?=number_format($total,2)?></th>
              </tr>
            </tfoot>
          </table>
        </div>
        <div class="place-order-row">
        <form method="post" action="place_order.php" class="text-end">
          <button class="btn btn-success btn-lg"><i class="fas fa-credit-card me-1"></i>Place Order</button>
        </form>
      <?php endif; ?>
      </div>
    </div>

  </div>
  </main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</body>
</html>
