<?php
include 'includes/auth.php';
include 'db/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p_name = $_POST['p_name'];
    $price = $_POST['price'];
    $qty = $_POST['qty'];
    $unit = $_POST['unit'];
    $shelf = $_POST['shelf'];
    $type = $_POST['type'];

    $stmt = $conn->prepare("INSERT INTO Product (p_name, price, qty, unit, shelf, type) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sdisss", $p_name, $price, $qty, $unit, $shelf, $type);
    $stmt->execute();

    header("Location: warehouse.php");
    exit;
}
?>