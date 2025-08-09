<?php
include 'includes/auth.php';
include 'db/connection.php';

$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    $stmt = $conn->prepare('DELETE FROM ProductType WHERE pt_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
}
header('Location: product_type_data.php');
exit;
