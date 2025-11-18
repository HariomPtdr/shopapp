<?php
session_start();
include 'db_connect.php';
require_once __DIR__ . '/fpdf/fpdf.php'; // <-- FIXED

if (!isset($_SESSION['user'])) {
    die("Unauthorized");
}

if (!isset($_GET['id'])) {
    die("Invalid Order");
}

$orderId = intval($_GET['id']);
$userId = $_SESSION['user']['id'];

/* FETCH ORDER */
$o = $conn->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
$o->bind_param("ii", $orderId, $userId);
$o->execute();
$r = $o->get_result();

if ($r->num_rows === 0) {
    die("Order not found");
}

$order = $r->fetch_assoc();

/* FETCH ITEMS */
$oi = $conn->prepare("
    SELECT oi.*, p.name 
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE order_id=?
");
$oi->bind_param("i", $orderId);
$oi->execute();
$items = $oi->get_result()->fetch_all(MYSQLI_ASSOC);

/* PDF */
$pdf = new FPDF();
$pdf->AddPage();

$pdf->SetFont('Arial', 'B', 18);
$pdf->Cell(0, 10, 'Shopping App - Invoice', 0, 1, 'C');

$pdf->SetFont('Arial', '', 12);
$pdf->Ln(5);
$pdf->Cell(0, 8, "Order ID: #".$orderId, 0, 1);
$pdf->Cell(0, 8, "Date: ".$order['created_at'], 0, 1);
$pdf->Cell(0, 8, "Customer: ".$order['name'], 0, 1);
$pdf->Cell(0, 8, "Phone: ".$order['phone'], 0, 1);

$pdf->Ln(10);

/* HEADERS */
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(90, 10, 'Item', 1);
$pdf->Cell(30, 10, 'Qty', 1);
$pdf->Cell(30, 10, 'Price', 1);
$pdf->Cell(40, 10, 'Total', 1);
$pdf->Ln();

/* ROWS */
$pdf->SetFont('Arial', '', 12);

foreach ($items as $it) {
    $lineTotal = $it['price'] * $it['quantity'];
    
    $pdf->Cell(90, 10, $it['name'], 1);
    $pdf->Cell(30, 10, $it['quantity'], 1);
    $pdf->Cell(30, 10, "RS.".number_format($it['price'],2), 1);
    $pdf->Cell(40, 10, "RS.".number_format($lineTotal,2), 1);
    $pdf->Ln();
}

$pdf->Ln(10);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, "Subtotal: RS.".number_format($order['subtotal'],2), 0, 1);
$pdf->Cell(0, 8, "Discount: RS.".number_format($order['discount'],2), 0, 1);
$pdf->Cell(0, 8, "Total Payable: RS.".number_format($order['total'],2), 0, 1);

$pdf->Output("I", "invoice_$orderId.pdf");
exit;
