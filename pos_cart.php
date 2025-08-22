<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header("Location: retailer_login.php"); exit(); }
$cart = $_SESSION['pos_cart'] ?? [];

$total_items = 0; $total_amount = 0.00;
foreach ($cart as $pid=>$line) {
  $total_items += (int)$line['qty'];
  $total_amount += $line['qty'] * $line['price'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>POS Cart - Retailer</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/retailer_styles.css">
    <style>
        .pos-sticky-actions { position: sticky; top: 0; z-index: 10; background: #fff; padding: .75rem 0; }
        .badge-pill { border-radius: 999px; }
    </style>
</head>
<body>
<div class="agri-container">
    <aside class="agri-sidebar">
        <div class="agri-sidebar-header">
            <img src="Image/logo.png" alt="AGROMATI Logo" class="logo-image">
        </div>
        <nav class="agri-sidebar-nav">
            <ul>
                    <li class="agri-nav-item" data-page="dashboard">
                        <a href="retailer_dashboard.php" class="agri-nav-link">
                            <i class="fas fa-tachometer-alt agri-nav-icon"></i> 
                            Dashboard
                        </a>
                    </li>
                    <li class="agri-nav-item" data-page="Shop">
                        <a href="shop.php" class="agri-nav-link">
                            <i class="fas fa-users agri-nav-icon"></i> 
                            Shop
                        </a>
                    </li>
                    <li class="agri-nav-item" data-page="Cart">
                        <a href="cart.php" class="agri-nav-link">
                            <i class="fas fa-clipboard-list agri-nav-icon"></i> 
                            Cart
                        </a>
                    </li>
                    <li class="agri-nav-item" data-page="My orders">
                        <a href="retailer_orders.php" class="agri-nav-link">
                            <i class="fas fa-clipboard-list agri-nav-icon"></i> 
                            Orders
                        </a>
                    </li>
                    <li class="agri-nav-item" data-page="pos">
                        <a href="retailer_pos.php" class="agri-nav-link">
                            <i class="fas fa-cash-register agri-nav-icon"></i> POS (Sell)
                        </a>
                    </li>
                    <li class="agri-nav-item  active" data-page="pos_cart">
                        <a href="pos_cart.php" class="agri-nav-link  active">
                            <i class="fas fa-shopping-cart agri-nav-icon"></i> POS Cart
                            <?php if ($cartCount > 0): ?>
                            <span class="badge bg-success badge-pill ms-2"><?= $cartCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="agri-nav-item" data-page="pos_sales">
                        <a href="retailer_posSell.php" class="agri-nav-link">
                            <i class="fas fa-history agri-nav-icon"></i> POS Sales History
                        </a>
                    </li>
                    <li class="agri-nav-item">
                        <a href="retailer_logout.php" class="agri-nav-link">
                            <i class="fas fa-sign-out-alt agri-nav-icon"></i> 
                            Logout
                        </a>
                    </li>
                </ul>
        </nav>
    </aside>
     <main class="agri-main-content">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2>POS Cart</h2>
    <div>
      <a class="btn btn-outline-secondary" href="retailer_pos.php"><i class="bi bi-cart-plus"></i> Add More</a>
      <a class="btn btn-outline-primary" href="retailer_dashboard.php">Dashboard</a>
    </div>
  </div>

  <?php if (!empty($_SESSION['pos_msg'])): ?>
    <div class="alert alert-success"><?=$_SESSION['pos_msg']; unset($_SESSION['pos_msg']);?></div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['pos_err'])): ?>
    <div class="alert alert-danger"><?=$_SESSION['pos_err']; unset($_SESSION['pos_err']);?></div>
  <?php endif; ?>

  <?php if (empty($cart)): ?>
    <div class="alert alert-info">Your POS cart is empty.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table align-middle">
        <thead class="table-light">
          <tr>
            <th>Product</th><th>Unit</th><th style="width:200px;">Qty</th><th style="width:200px;">Unit Price (৳)</th><th class="text-end">Subtotal</th><th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($cart as $pid=>$line): $sub = $line['qty'] * $line['price']; ?>
          <tr>
            <td><?=htmlspecialchars($line['name'])?></td>
            <td><?=htmlspecialchars($line['unit'])?></td>
            <td>
              <form class="d-flex" method="post" action="pos_update_cart.php">
                <input type="hidden" name="product_id" value="<?=$pid?>">
                <input type="number" class="form-control me-2" name="quantity" min="1" value="<?=$line['qty']?>" required>
                <button class="btn btn-sm btn-outline-secondary">Update</button>
              </form>
            </td>
            <td>
              <form class="d-flex" method="post" action="pos_update_cart.php">
                <input type="hidden" name="product_id" value="<?=$pid?>">
                <input type="number" class="form-control me-2" step="0.01" min="0.01" name="unit_price" value="<?=number_format($line['price'],2,'.','')?>" required>
                <button class="btn btn-sm btn-outline-secondary">Update</button>
              </form>
            </td>
            <td class="text-end">৳<?=number_format($sub,2)?></td>
            <td>
              <form method="post" action="pos_remove.php" onsubmit="return confirm('Remove this item?');">
                <input type="hidden" name="product_id" value="<?=$pid?>">
                <button class="btn btn-sm btn-outline-danger">Remove</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <th colspan="2">Totals</th>
            <th><?=number_format($total_items)?></th>
            <th></th>
            <th class="text-end">৳<?=number_format($total_amount,2)?></th>
            <th></th>
          </tr>
        </tfoot>
      </table>
    </div>

    <form class="row g-2 mt-3" method="post" action="pos_checkout.php">
      <div class="col-md-4">
        <input name="customer_name" class="form-control" placeholder="Customer name (optional)">
      </div>
      <div class="col-md-4">
        <input name="customer_phone" class="form-control" placeholder="Customer phone (optional)">
      </div>
      <div class="col-md-4 text-end">
        <button class="btn btn-success btn-lg"><i class="bi bi-credit-card"></i> Checkout & Save Sale</button>
      </div>
    </form>
  <?php endif; ?>

  </main>
</div>
</body>
</html>
