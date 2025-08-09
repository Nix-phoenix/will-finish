<?php
include 'includes/auth.php';
include 'db/connection.php';

$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    $stmt = $conn->prepare('DELETE FROM Customer WHERE c_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
}
header('Location: customer_data.php');
exit;
