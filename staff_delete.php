<?php
include 'includes/auth.php';
include 'db/connection.php';

// Check if the user is logged in and has the 'admin' role
requireAdmin();

$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    $stmt = $conn->prepare('DELETE FROM Employee WHERE emp_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
}
header('Location: staff_data.php');
exit;
