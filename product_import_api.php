<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
include 'includes/auth.php';
include 'db/connection.php';

// Check if the user is logged in and has the 'admin' role
requireAdmin();

header('Content-Type: application/json; charset=UTF-8');
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            $stmt = $conn->prepare("INSERT INTO PurchaseOrderDetail (po_id, p_id, qty, price) VALUES (?,?,?,?)");
            $stmt->bind_param('iiid', $data['po_id'], $data['p_id'], $data['qty'], $data['unitPrice']);
            $stmt->execute();
            $stmt->close();
            break;
        case 'edit':
            $stmt = $conn->prepare("UPDATE PurchaseOrderDetail SET po_id=?, p_id=?, qty=?, price=? WHERE pod_id=?");
            $stmt->bind_param('iiidi', $data['po_id'], $data['p_id'], $data['qty'], $data['unitPrice'], $data['id']);
            $stmt->execute();
            $stmt->close();
            break;
        case 'delete':
            $stmt = $conn->prepare("DELETE FROM PurchaseOrderDetail WHERE pod_id=?");
            $stmt->bind_param('i', $data['id']);
            $stmt->execute();
            $stmt->close();
            break;
        default:
            throw new Exception('Invalid action');
    }

    // Updated list - Corrected to remove 'p.category' and 'p.type' and use the correct joins and columns.
    $sql = "SELECT pod.pod_id AS id, DATE(po.date) AS date, po.po_id AS orderRef, p.p_id, p.p_name AS product, pu.punit_name AS unit, pod.qty, pod.price AS unitPrice
            FROM PurchaseOrderDetail pod
            JOIN PurchaseOrder po ON po.po_id = pod.po_id
            JOIN Product p ON p.p_id = pod.p_id
            JOIN ProductUnit pu ON pu.punit_id = p.punit_id
            ORDER BY pod.pod_id DESC";
    $imports = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success'=>true, 'imports'=>$imports], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
