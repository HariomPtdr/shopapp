<?php
session_start();
include 'db_connect.php';

if (isset($_SESSION['user'])) {
    header("Location: products.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $pwd   = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $u = $res->fetch_assoc();
        if (password_verify($pwd, $u['password'])) {

            $_SESSION['user'] = [
                'id' => $u['id'],
                'name' => $u['name'],
                'email' => $email
            ];

            // ensure cart exists
            $c = $conn->prepare("SELECT id FROM carts WHERE user_id=?");
            $c->bind_param("i", $u['id']);
            $c->execute();
            $r = $c->get_result();

            if ($r->num_rows === 0) {
                $ci = $conn->prepare("INSERT INTO carts (user_id) VALUES (?)");
                $ci->bind_param("i", $u['id']);
                $ci->execute();
            }

            header("Location: products.php");
            exit;
        } else {
            $error = "Incorrect password";
        }
    } else {
        $error = "No account found";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Shopping App — Login</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<!-- HEADER -->
<div class="app-header">
    <div class="brand">
        <div class="brand-logo">SA</div>
        <div>
            <div class="brand-title">Shopping App</div>
            <div class="brand-sub">Login</div>
        </div>
    </div>

    <div class="header-right">
        <button id="themeBtn" class="btn btn-ghost btn-small" onclick="changeTheme()">Theme</button>
    </div>
</div>

<!-- LOGIN CARD -->
<div class="container" style="margin-top:80px;">
    <div class="card" style="max-width:420px; margin:0 auto;">
        
        <h2 style="margin-bottom:14px; font-size:22px; font-weight:800;">Sign in</h2>

        <?php if($error): ?>
            <div class="small" style="color:#f87171; margin-bottom:10px;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <input class="input" name="email" placeholder="Email" required>
            <input class="input" type="password" name="password" placeholder="Password" required>

            <button class="btn btn-primary" style="width:100%; margin-top:12px;">Login</button>
        </form>

        <div class="small" style="margin-top:16px; text-align:center;">
            <a href="signup.php" style="color:var(--accent-solid); text-decoration:none;">Create Account</a>
        </div>
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
