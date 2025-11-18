<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$user = $_SESSION['user'];
$subtitle = "Invoice";

/* ---------------------------
   VALIDATE ORDER ID
----------------------------*/
if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit;
}

$orderId = intval($_GET['id']);

/* ---------------------------
   FETCH ORDER
----------------------------*/
$o = $conn->prepare("SELECT * FROM orders WHERE id=? AND user_id=? LIMIT 1");
$o->bind_param("ii", $orderId, $user['id']);
$o->execute();
$ordRes = $o->get_result();

if ($ordRes->num_rows === 0) {
    header("Location: products.php");
    exit;
}

$order = $ordRes->fetch_assoc();

/* ---------------------------
   FETCH ORDER ITEMS
----------------------------*/
$oi = $conn->prepare("
    SELECT oi.*, p.name 
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE order_id=?
");
$oi->bind_param("i", $orderId);
$oi->execute();
$items = $oi->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Shopping App — Invoice</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<!-- HEADER -->
<div class="app-header no-print">
    <div class="brand">
        <div class="brand-logo">SA</div>
        <div>
            <div class="brand-title">Shopping App</div>
            <div class="brand-sub"><?= $subtitle ?></div>
        </div>
    </div>

    <div class="header-right">
        <button id="themeBtn" class="btn btn-ghost btn-small" onclick="changeTheme()">Theme</button>
        <div class="avatar"><?= strtoupper(substr($_SESSION['user']['name'],0,2)) ?></div>
        <a class="btn btn-primary btn-small" href="logout.php">Logout</a>
    </div>
</div>

<!-- INVOICE CARD -->
<div class="container">

    <div class="invoice-box card">

        <h2 style="font-size:22px; font-weight:800; margin-bottom:12px;">Invoice</h2>

        <div class="small" style="margin-bottom:20px;">
            Order ID: <strong>#<?= $orderId ?></strong><br>
            Date: <?= date("d M Y, h:i A", strtotime($order['created_at'])) ?><br>
            Customer: <?= htmlspecialchars($order['name']) ?><br>
            Phone: <?= htmlspecialchars($order['phone']) ?>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Item</th><th>Qty</th><th>Price</th><th>Total</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach($items as $it): ?>
                    <tr>
                        <td><?= htmlspecialchars($it['name']) ?></td>
                        <td><?= $it['quantity'] ?></td>
                        <td>₹<?= number_format($it['price'],2) ?></td>
                        <td>₹<?= number_format($it['price'] * $it['quantity'],2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- TOTAL BOX -->
        <div class="summary" style="margin-top:16px;">
            <div class="small">Subtotal: ₹<?= number_format($order['subtotal'],2) ?></div>
            <div class="small">Discount: ₹<?= number_format($order['discount'],2) ?></div>
            <div style="font-size:22px; font-weight:800; margin-top:10px;">
                Payable: <span style="color:var(--accent-solid);">₹<?= number_format($order['total'],2) ?></span>
            </div>
        </div>

        <!-- BUTTONS -->
        <div class="no-print" style="margin-top:24px; display:flex; gap:10px;">
            <button onclick="window.print()" class="btn btn-ghost">Print Invoice</button>
            <a href="generate_pdf.php?id=<?= $orderId ?>" class="btn btn-primary">Download PDF</a>
            <a href="products.php" class="btn btn-ghost">Continue Shopping</a>
        </div>

    </div>
</div>

<!-- THEME SCRIPT -->
<script>
const savedTheme = localStorage.getItem("theme") || "dark";
document.body.classList.add(savedTheme);

function changeTheme() {
    let curr = localStorage.getItem("theme") || "dark";
    let next = curr === "dark" ? "light" : curr === "light" ? "premium" : "dark";

    document.body.classList.remove("light","dark","premium");
    document.body.classList.add(next);
    localStorage.setItem("theme", next);

    document.getElementById("themeBtn").innerText =
        next.charAt(0).toUpperCase() + next.slice(1);
}

document.getElementById("themeBtn").innerText =
  savedTheme.charAt(0).toUpperCase() + savedTheme.slice(1);
</script>

</body>
</html>
