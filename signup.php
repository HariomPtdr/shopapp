<?php
session_start();
include 'db_connect.php';

if (isset($_SESSION['user'])) {
    header("Location: products.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $pwd   = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // check if email exists
    $check = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
    $check->bind_param("s", $email);
    $check->execute();
    $cRes = $check->get_result();

    if ($cRes->num_rows > 0) {
        $error = "Email already registered";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name,email,password) VALUES (?,?,?)");
        $stmt->bind_param("sss", $name, $email, $pwd);
        if ($stmt->execute()) {
            $success = "Account created. You can now login.";
        } else {
            $error = "Something went wrong.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Shopping App — Signup</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<!-- HEADER -->
<div class="app-header">

    <div class="brand">
        <div class="brand-logo">SA</div>
        <div>
            <div class="brand-title">Shopping App</div>
            <div class="brand-sub">Create Account</div>
        </div>
    </div>

    <div class="header-right">
        <button id="themeBtn" class="btn btn-ghost btn-small" onclick="changeTheme()">Theme</button>
    </div>

</div>

<!-- SIGNUP CARD -->
<div class="container" style="margin-top:70px;">
    <div class="card" style="max-width:450px; margin:0 auto;">

        <h2 style="margin-bottom:14px; font-size:22px; font-weight:800;">Sign up</h2>

        <?php if($error): ?>
            <div class="small" style="color:#f87171; margin-bottom:10px;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="small" style="color:#4ade80; margin-bottom:10px;">
                <?= $success ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <input class="input" name="name" placeholder="Full name" required>
            <input class="input" name="email" placeholder="Email" required>
            <input class="input" type="password" name="password" placeholder="Password" required>

            <button class="btn btn-primary" style="width:100%; margin-top:12px;">Create Account</button>
        </form>

        <div class="small" style="margin-top:16px; text-align:center;">
            <a href="index.php" style="color:var(--accent-solid); text-decoration:none;">Already have an account?</a>
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
