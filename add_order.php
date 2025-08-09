<?php
include 'includes/auth.php';
include 'db/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $c_name = trim($_POST['c_name']);
    $p_id = intval($_POST['p_id']);
    $qty = intval($_POST['qty']);
    $status = $_POST['status'];
    $payment_date = isset($_POST['payment_date']) ? $_POST['payment_date'] : null;

    // 1. Find or create customer
    $stmt = $conn->prepare("SELECT c_id FROM Customer WHERE c_name = ? LIMIT 1");
    $stmt->bind_param("s", $c_name);
    $stmt->execute();
    $stmt->bind_result($c_id);
    if ($stmt->fetch()) {
        $stmt->close();
    } else {
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO Customer (c_name) VALUES (?)");
        $stmt->bind_param("s", $c_name);
        $stmt->execute();
        $c_id = $stmt->insert_id;
        $stmt->close();
    }

    // 2. Find product by p_id
    $stmt = $conn->prepare("SELECT price FROM Product WHERE p_id = ? LIMIT 1");
    $stmt->bind_param("i", $p_id);
    $stmt->execute();
    $stmt->bind_result($price);
    if ($stmt->fetch()) {
        $stmt->close();
    } else {
        $stmt->close();
        // Product not found, redirect with error
        header("Location: ordering_payment.php?error=product_not_found");
        exit;
    }

    // 3. Insert into Sell
    $stmt = $conn->prepare("INSERT INTO Sell (c_id, status) VALUES (?, ?)");
    $stmt->bind_param("is", $c_id, $status);
    $stmt->execute();
    $s_id = $stmt->insert_id;
    $stmt->close();

    // 4. Insert into SellDetail
    $total_price = $qty * $price;
    $stmt = $conn->prepare("INSERT INTO SellDetail (s_id, p_id, qty, price, total_price) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiidd", $s_id, $p_id, $qty, $price, $total_price);
    $stmt->execute();
    $stmt->close();

    // 4.1. Subtract ordered quantity from product stock
    $stmt = $conn->prepare("UPDATE Product SET qty = qty - ? WHERE p_id = ?");
    $stmt->bind_param("ii", $qty, $p_id);
    $stmt->execute();
    $stmt->close();

    // 5. Insert into Payment if paid and payment_date is set
    if ($status === 'paid' && $payment_date) {
        $stmt = $conn->prepare("INSERT INTO Payment (s_id, status, date) VALUES (?, ?, ?)");
        $payment_status = 'paid';
        $stmt->bind_param("iss", $s_id, $payment_status, $payment_date);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: ordering_payment.php");
    exit;
}
// If not POST, redirect
header("Location: ordering_payment.php");
exit; 