<?php
session_start();

// Ensure retailer is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: retailer_login.php");
    exit();
}

// Database connection
$db = new mysqli('localhost', 'root', '', 'agromatiDB');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Get search parameters from the GET request
$q = isset($_GET['q']) ? trim($_GET['q']) : ''; // Search query for Sale ID or Customer Name
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : ''; // Start date for date range
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : ''; // End date for date range

// SQL query to fetch sales records
$sql = "SELECT rs.id, rs.customer_name, rs.customer_phone, rs.total_items, rs.total_amount, rs.created_at
        FROM retailer_sales rs
        WHERE rs.retailer_id = ?";

if ($q) {
    $sql .= " AND (rs.id LIKE ? OR rs.customer_name LIKE ?)";
}

if ($start_date && $end_date) {
    $sql .= " AND rs.created_at BETWEEN ? AND ?";
}

$sql .= " ORDER BY rs.created_at DESC";

// Prepare the statement
$stmt = $db->prepare($sql);

// Bind parameters for the query
if ($q) {
    $like = "%{$q}%";
    if ($start_date && $end_date) {
        $stmt->bind_param("issss", $_SESSION['retailer_id'], $like, $like, $start_date, $end_date);
    } else {
        $stmt->bind_param("iss", $_SESSION['retailer_id'], $like, $like);
    }
} else if ($start_date && $end_date) {
    $stmt->bind_param("iss", $_SESSION['retailer_id'], $start_date, $end_date);
} else {
    $stmt->bind_param("i", $_SESSION['retailer_id']);
}

// Execute the query
$stmt->execute();
$res = $stmt->get_result();

// Check if any results are returned
$sales = [];
if ($res->num_rows > 0) {
    $sales = $res->fetch_all(MYSQLI_ASSOC); // Fetch all the sales data
} else {
    $sales = [];  // No sales found
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Retailer Sales Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/retailer_styles.css">
</head>
<body>
<div class="container py-4">
    <h2 class="mb-4">Retailer Sales Records</h2>

    <!-- Search Form -->
    <form method="GET" class="mb-3">
        <div class="row">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control" placeholder="Search by Sale ID or Customer Name" value="<?= htmlspecialchars($q) ?>" />
            </div>
            <div class="col-md-3">
                <input type="date" name="start_date" class="form-control" placeholder="Start Date" value="<?= $start_date ?>" />
            </div>
            <div class="col-md-3">
                <input type="date" name="end_date" class="form-control" placeholder="End Date" value="<?= $end_date ?>" />
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary mt-2">Search</button>
            </div>
        </div>
    </form>

    <!-- Display message if no sales found -->
    <?php if (empty($sales)): ?>
        <div class="alert alert-warning">No sales found matching your criteria.</div>
    <?php endif; ?>

    <!-- Sales Table -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Sale ID</th>
                <th>Customer Name</th>
                <th>Customer Phone</th>
                <th>Total Items</th>
                <th>Total Amount</th>
                <th>Sale Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sales as $sale): ?>
                <tr>
                    <td><?= htmlspecialchars($sale['id']) ?></td>
                    <td><?= htmlspecialchars($sale['customer_name']) ?: 'N/A' ?></td>
                    <td><?= htmlspecialchars($sale['customer_phone']) ?: 'N/A' ?></td>
                    <td><?= number_format($sale['total_items']) ?></td>
                    <td>৳<?= number_format($sale['total_amount'], 2) ?></td>
                    <td><?= $sale['created_at'] ?></td>
                    <td>
                        <a href="view_sale.php?sale_id=<?= $sale['id'] ?>" class="btn btn-info btn-sm">View</a>
                        <a href="download_receipt.php?sale_id=<?= $sale['id'] ?>" class="btn btn-success btn-sm">Download</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>
