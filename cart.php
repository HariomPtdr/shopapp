<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$user = $_SESSION['user'];
$userId = $user['id'];
$subtitle = "Your Cart";

/* ---------------------------
   ENSURE CART EXISTS
----------------------------*/
$c = $conn->prepare("SELECT id FROM carts WHERE user_id=? LIMIT 1");
$c->bind_param("i", $userId);
$c->execute();
$r = $c->get_result();

if ($r->num_rows === 0) {
    $ci = $conn->prepare("INSERT INTO carts (user_id) VALUES (?)");
    $ci->bind_param("i", $userId);
    $ci->execute();
    $cartId = $ci->insert_id;
} else {
    $cartId = $r->fetch_assoc()['id'];
}

/* ---------------------------
   CART ACTIONS
----------------------------*/
if (isset($_GET['increase'])) {
    $id = intval($_GET['increase']);
    $u = $conn->prepare("UPDATE cart_items SET quantity = quantity + 1 WHERE id=? AND cart_id=?");
    $u->bind_param("ii", $id, $cartId);
    $u->execute();
    header("Location: cart.php");
    exit;
}

if (isset($_GET['decrease'])) {
    $id = intval($_GET['decrease']);

    $q = $conn->prepare("SELECT quantity FROM cart_items WHERE id=? AND cart_id=?");
    $q->bind_param("ii", $id, $cartId);
    $q->execute();
    $res = $q->get_result();

    if ($res->num_rows) {
        $qty = $res->fetch_assoc()['quantity'];
        if ($qty <= 1) {
            $d = $conn->prepare("DELETE FROM cart_items WHERE id=? AND cart_id=?");
            $d->bind_param("ii", $id, $cartId);
            $d->execute();
        } else {
            $u = $conn->prepare("UPDATE cart_items SET quantity = quantity - 1 WHERE id=? AND cart_id=?");
            $u->bind_param("ii", $id, $cartId);
            $u->execute();
        }
    }

    header("Location: cart.php");
    exit;
}

if (isset($_GET['remove'])) {
    $id = intval($_GET['remove']);
    $d = $conn->prepare("DELETE FROM cart_items WHERE id=? AND cart_id=?");
    $d->bind_param("ii", $id, $cartId);
    $d->execute();
    header("Location: cart.php");
    exit;
}

if (isset($_GET['add'])) {
    $pid = intval($_GET['add']);
    $chk = $conn->prepare("SELECT id FROM cart_items WHERE cart_id=? AND product_id=?");
    $chk->bind_param("ii", $cartId, $pid);
    $chk->execute();
    $cr = $chk->get_result();

    if ($cr->num_rows > 0) {
        $row = $cr->fetch_assoc();
        $u = $conn->prepare("UPDATE cart_items SET quantity=quantity+1 WHERE id=?");
        $u->bind_param("i", $row['id']);
        $u->execute();
    } else {
        $i = $conn->prepare("INSERT INTO cart_items(cart_id,product_id,quantity) VALUES (?,?,1)");
        $i->bind_param("ii", $cartId, $pid);
        $i->execute();
    }

    header("Location: cart.php");
    exit;
}

if (isset($_GET['clear'])) {
    $d = $conn->prepare("DELETE FROM cart_items WHERE cart_id=?");
    $d->bind_param("i", $cartId);
    $d->execute();
    header("Location: cart.php");
    exit;
}

/* ---------------------------
   FETCH CART ITEMS
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

/* ---------------------------
   DISCOUNT
----------------------------*/
$discount = 0;
if (isset($_GET['coupon']) && $_GET['coupon'] === 'FLAT20') {
    $discount = round($subtotal * 0.20, 2);
}

$total = round($subtotal - $discount, 2);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Shopping App — Cart</title>
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

        <div class="avatar"><?= strtoupper(substr($_SESSION['user']['name'],0,2)) ?></div>

        <a class="btn btn-primary btn-small" href="logout.php">Logout</a>
    </div>

</div>

<!-- CART SECTION -->
<div class="container">

    <div class="card">

        <?php if (empty($items)): ?>

            <div class="small" style="text-align:center; padding:20px;">
                Cart is empty. <a href="products.php" style="color:var(--accent-solid);">Continue shopping</a>
            </div>

        <?php else: ?>

            <table class="table">
                <thead>
                    <tr>
                        <th>Item</th><th>Price</th><th>Qty</th><th>Line</th><th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($items as $it): ?>
                    <tr>
                        <td><?= $it['name'] ?></td>
                        <td>₹<?= $it['price'] ?></td>
                        <td><?= $it['quantity'] ?></td>
                        <td>₹<?= $it['line_total'] ?></td>
                        <td>
                            <a class="btn btn-ghost btn-small" href="?increase=<?= $it['cart_item_id'] ?>">+</a>
                            <a class="btn btn-ghost btn-small" href="?decrease=<?= $it['cart_item_id'] ?>">-</a>
                            <a class="btn btn-ghost btn-small" href="?remove=<?= $it['cart_item_id'] ?>">x</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <!-- SUMMARY -->
            <div style="margin-top:24px; text-align:right;">

                <div class="summary" style="display:inline-block; text-align:left;">
                    <div class="small">Subtotal: ₹<?= number_format($subtotal,2) ?></div>
                    <div class="small">Discount: ₹<?= number_format($discount,2) ?></div>

                    <div style="font-size:20px; font-weight:800; margin-top:8px;">
                        Total: <span style="color:var(--accent-solid);">₹<?= number_format($total,2) ?></span>
                    </div>

                    <form method="post" action="checkout.php" style="margin-top:12px;">
                        <input type="hidden" name="discount" value="<?= $discount ?>">
                        <button class="btn btn-primary" style="width:100%;">Checkout</button>
                    </form>

                    <a href="?clear=1" class="small" style="display:block; text-align:center; margin-top:8px; color:#f87171;">Clear Cart</a>
                </div>

            </div>

        <?php endif; ?>

    </div>
</div>


<!-- THEME SWITCH SCRIPT -->
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
