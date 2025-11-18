<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$user = $_SESSION['user'];
$userId = $user['id'];

$subtitle = "Products";

/* ---------------------------
   FETCH PRODUCTS
----------------------------*/
$res = $conn->query("SELECT * FROM products ORDER BY id ASC");
$products = $res->fetch_all(MYSQLI_ASSOC);

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
   ADD TO CART ACTION
----------------------------*/
if (isset($_GET['add'])) {
    $pid = intval($_GET['add']);

    $chk = $conn->prepare("SELECT id, quantity FROM cart_items WHERE cart_id=? AND product_id=?");
    $chk->bind_param("ii", $cartId, $pid);
    $chk->execute();
    $cr = $chk->get_result();

    if ($cr->num_rows > 0) {
        $row = $cr->fetch_assoc();
        $u = $conn->prepare("UPDATE cart_items SET quantity=quantity+1 WHERE id=?");
        $u->bind_param("i", $row['id']);
        $u->execute();
    } else {
        $i = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?,?,1)");
        $i->bind_param("ii", $cartId, $pid);
        $i->execute();
    }

    header("Location: products.php");
    exit;
}

/* ---------------------------
   CART COUNT BADGE
----------------------------*/
$q = $conn->prepare("SELECT COALESCE(SUM(quantity),0) AS cnt FROM cart_items WHERE cart_id=?");
$q->bind_param("i", $cartId);
$q->execute();
$cRes = $q->get_result();
$cartCount = $cRes->fetch_assoc()['cnt'];
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Shopping App — Products</title>
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

        <a class="btn btn-ghost btn-small" href="cart.php">Cart (<?= $cartCount ?>)</a>

        <div class="avatar">
          <?= strtoupper(substr($_SESSION['user']['name'],0,2)) ?>
        </div>

        <a class="btn btn-primary btn-small" href="logout.php">Logout</a>
    </div>
</div>


<!-- PRODUCT GRID -->
<div class="container">

    <div class="grid">
        <?php foreach($products as $p): ?>
            <div class="product-card">

                <div>
                    <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                    <div class="product-desc"><?= htmlspecialchars($p['description']) ?></div>

                    <div class="product-meta">
                        <div class="product-price">₹<?= number_format($p['price'],2) ?></div>
                    </div>
                </div>

                <div class="product-actions">
                    <a class="add-btn" href="products.php?add=<?= $p['id'] ?>">Add</a>
                    <a class="buy-btn" href="cart.php?add=<?= $p['id'] ?>">Buy</a>
                </div>

            </div>
        <?php endforeach; ?>
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
