<?php
require("fpdf/fpdf.php");
include "db_connect.php";
session_start();

if (!isset($_SESSION['user'])) {
    die("Unauthorized");
}

if (!isset($_GET['id'])) {
    die("Invalid");
}

$orderId = intval($_GET['id']);
$userId = $_SESSION['user']['id'];

/* Fetch order */
$o = $conn->prepare("SELECT * FROM orders WHERE id=? AND user_id=? LIMIT 1");
$o->bind_param("ii", $orderId, $userId);
$o->execute();
$order = $o->get_result()->fetch_assoc();

if (!$order) die("Invalid order");

/* Fetch items */
$oi = $conn->prepare("
    SELECT oi.*, p.name 
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE order_id=?
");
$oi->bind_param("i", $orderId);
$oi->execute();
$items = $oi->get_result()->fetch_all(MYSQLI_ASSOC);

/* Build PDF */
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont("Arial","B",16);
$pdf->Cell(0,10,"Shopping App Invoice",0,1,"C");

$pdf->SetFont("Arial","",12);
$pdf->Ln(2);
$pdf->Cell(0,8,"Order ID: #$orderId",0,1);
$pdf->Cell(0,8,"Name: ".$order['name'],0,1);
$pdf->Cell(0,8,"Phone: ".$order['phone'],0,1);
$pdf->Cell(0,8,"Date: ".$order['created_at'],0,1);

$pdf->Ln(6);

/* Table header */
$pdf->SetFont("Arial","B",12);
$pdf->Cell(80,10,"Item",1);
$pdf->Cell(30,10,"Qty",1);
$pdf->Cell(40,10,"Price",1);
$pdf->Cell(40,10,"Total",1);
$pdf->Ln();

/* Items */
$pdf->SetFont("Arial","",12);
foreach ($items as $it) {
    $pdf->Cell(80,10,$it['name'],1);
    $pdf->Cell(30,10,$it['quantity'],1);
    $pdf->Cell(40,10,"₹".$it['price'],1);
    $pdf->Cell(40,10,"₹".($it['price'] * $it['quantity']),1);
    $pdf->Ln();
}

$pdf->Ln(4);

/* Totals */
$pdf->SetFont("Arial","B",12);
$pdf->Cell(0,8,"Subtotal: ₹".$order['subtotal'],0,1);
$pdf->Cell(0,8,"Discount: ₹".$order['discount'],0,1);
$pdf->Cell(0,10,"Total: ₹".$order['total'],0,1);

$pdf->Output();
?>
