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
            // add PurchaseOrder and detail
            $conn->begin_transaction();
            $stmt = $conn->prepare("INSERT INTO PurchaseOrder (sup_id, emp_id, date) VALUES (?,?,?)");
            $stmt->bind_param('iis', $data['sup_id'], $data['emp_id'], $data['date']);
            $stmt->execute();
            $po_id = $stmt->insert_id;
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO PurchaseOrderDetail (po_id, p_id, qty, price) VALUES (?,?,?,?)");
            $stmt->bind_param('iiid', $po_id, $data['p_id'], $data['qty'], $data['unitPrice']);
            $stmt->execute();
            $stmt->close();
            $conn->commit();
            break;

        case 'edit':
            $conn->begin_transaction();
            // update PurchaseOrder
            $stmt = $conn->prepare("UPDATE PurchaseOrder SET sup_id=?, emp_id=?, date=? WHERE po_id=?");
            $stmt->bind_param('iisi', $data['sup_id'], $data['emp_id'], $data['date'], $data['id']);
            $stmt->execute();
            $stmt->close();
            // update details (assume single detail row per order)
            $stmt = $conn->prepare("UPDATE PurchaseOrderDetail SET p_id=?, qty=?, price=? WHERE po_id=?");
            $stmt->bind_param('iidi', $data['p_id'], $data['qty'], $data['unitPrice'], $data['id']);
            $stmt->execute();
            $stmt->close();
            $conn->commit();
            break;

        case 'delete':
            $conn->begin_transaction();
            $stmt = $conn->prepare("DELETE FROM PurchaseOrderDetail WHERE po_id=?");
            $stmt->bind_param('i', $data['id']);
            $stmt->execute();
            $stmt->close();
            $stmt = $conn->prepare("DELETE FROM PurchaseOrder WHERE po_id=?");
            $stmt->bind_param('i', $data['id']);
            $stmt->execute();
            $stmt->close();
            $conn->commit();
            break;
        default:
            throw new Exception('Invalid action');
    }

    // fetch updated list
    $result = $conn->query("SELECT po.po_id AS id, sup.sup_name AS supplier, sup.sup_id, emp.emp_name AS employee, emp.emp_id, p.p_name AS product, p.p_id, pod.qty, po.date, pod.price AS unitPrice FROM PurchaseOrder po JOIN Supplier sup ON po.sup_id=sup.sup_id JOIN Employee emp ON po.emp_id=emp.emp_id JOIN PurchaseOrderDetail pod ON pod.po_id=po.po_id JOIN Product p ON p.p_id=pod.p_id ORDER BY po.po_id DESC");
    $orders = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['success'=>true,'orders'=>$orders], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
