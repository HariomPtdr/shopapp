<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$user = $_SESSION['user'];
$userId = $user['id'];
$subtitle = "Checkout";

/* ---------------------------
   GET CART
----------------------------*/
$c = $conn->prepare("SELECT id FROM carts WHERE user_id=? LIMIT 1");
$c->bind_param("i", $userId);
$c->execute();
$r = $c->get_result();

if ($r->num_rows === 0) {
    header("Location: products.php");
    exit;
}

$cartId = $r->fetch_assoc()['id'];

/* ---------------------------
   FETCH ITEMS
----------------------------*/
$q = $conn->prepare("
    SELECT ci.id AS cart_item_id, p.id AS product_id,
           p.name, p.price, ci.quantity,
           (p.price * ci.quantity) AS line_total
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    WHERE ci.cart_id = ?
");
$q->bind_param("i", $cartId);
$q->execute();
$res = $q->get_result();

$items = [];
$subtotal = 0;

while ($row = $res->fetch_assoc()) {
    $items[] = $row;
    $subtotal += $row['line_total'];
}

if (empty($items)) {
    header("Location: cart.php");
    exit;
}

/* ---------------------------
   DISCOUNT
----------------------------*/
$discount = isset($_POST['discount']) ? floatval($_POST['discount']) : 0;
$total = $subtotal - $discount;

/* ---------------------------
   PLACE ORDER
----------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {

    $customer_name  = trim($_POST['customer_name']);
    $customer_phone = trim($_POST['customer_phone']);

    if ($customer_name === "" || $customer_phone === "") {
        $error = "Fill all customer details.";
    } else {
        // create order
        $o = $conn->prepare("
            INSERT INTO orders (user_id, name, phone, subtotal, discount, total)
            VALUES (?,?,?,?,?,?)
        ");

        $o->bind_param("issddd",
            $userId,
            $customer_name,
            $customer_phone,
            $subtotal,
            $discount,
            $total
        );
        $o->execute();

        $orderId = $o->insert_id;

        // order items
        foreach ($items as $it) {
            $oi = $conn->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (?,?,?,?)
            ");
            $oi->bind_param("iiid",
                $orderId,
                $it['product_id'],
                $it['quantity'],
                $it['price']
            );
            $oi->execute();
        }

        // clear cart
        $clear = $conn->prepare("DELETE FROM cart_items WHERE cart_id=?");
        $clear->bind_param("i", $cartId);
        $clear->execute();

        header("Location: invoice.php?id=".$orderId);
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Shopping App — Checkout</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<!-- HEADER -->
<div class="app-header">
    <div class="brand">
        <div class="brand-logo">SA</div>
        <div>
            <div class="brand-title">Shopping App</div>
            <div class="brand-sub"><?= $subtitle ?></div>
        </div>
    </div>

    <div class="header-right">

        <button id="themeBtn" class="btn btn-ghost btn-small" onclick="changeTheme()">Theme</button>

        <div class="avatar">
            <?= strtoupper(substr($_SESSION['user']['name'],0,2)) ?>
        </div>

        <a class="btn btn-primary btn-small" href="logout.php">Logout</a>

    </div>
</div>

<!-- CONTENT -->
<div class="container">

    <div class="grid" style="grid-template-columns:1fr 1fr; gap:24px;">

        <!-- CUSTOMER DETAILS -->
        <div class="card">
            <h2 style="font-size:20px; font-weight:800; margin-bottom:14px;">Customer Details</h2>

            <?php if(isset($error)): ?>
                <div class="small" style="color:#f87171; margin-bottom:10px;">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="post">

                <input class="input" name="customer_name" placeholder="Full Name" required>
                <input class="input" name="customer_phone" placeholder="Phone Number" required>

                <input type="hidden" name="discount" value="<?= $discount ?>">

                <button class="btn btn-primary" name="place_order" style="width:100%; margin-top:10px;">
                    Place Order
                </button>

            </form>
        </div>

        <!-- ORDER SUMMARY -->
        <div class="card">
            <h2 style="font-size:20px; font-weight:800; margin-bottom:14px;">Order Summary</h2>

            <table class="table">
                <thead>
                    <tr>
                        <th>Item</th><th>Qty</th><th>Line</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($items as $it): ?>
                        <tr>
                            <td><?= $it['name'] ?></td>
                            <td><?= $it['quantity'] ?></td>
                            <td>₹<?= number_format($it['line_total'],2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="summary" style="margin-top:18px;">
                <div class="small">Subtotal: ₹<?= number_format($subtotal,2) ?></div>
                <div class="small">Discount: ₹<?= number_format($discount,2) ?></div>

                <div style="font-size:20px; font-weight:800; margin-top:10px;">
                    Total: <span style="color:var(--accent-solid);">₹<?= number_format($total,2) ?></span>
                </div>
            </div>

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
