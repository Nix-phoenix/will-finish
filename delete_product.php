<?php
include 'includes/auth.php';
include 'db/connection.php';

$p_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$p_id) {
    header('Location: storage.php?msg=missing_id');
    exit;
}

// Check if product exists
$check = $conn->prepare('SELECT p_id FROM Product WHERE p_id = ?');
$check->bind_param('i', $p_id);
$check->execute();
$check->store_result();
if ($check->num_rows === 0) {
    $check->close();
    header('Location: storage.php?msg=not_found');
    exit;
}
$check->close();

// Check for references in SellDetail
$ref = $conn->prepare('SELECT 1 FROM SellDetail WHERE p_id = ? LIMIT 1');
$ref->bind_param('i', $p_id);
$ref->execute();
$ref->store_result();
if ($ref->num_rows > 0) {
    $ref->close();
    header('Location: storage.php?msg=has_sales');
    exit;
}
$ref->close();

// Delete product
$del = $conn->prepare('DELETE FROM Product WHERE p_id = ?');
$del->bind_param('i', $p_id);
if ($del->execute()) {
    $del->close();
    header('Location: storage.php?msg=deleted');
    exit;
} else {
    $del->close();
    header('Location: storage.php?msg=delete_error');
    exit;
} 