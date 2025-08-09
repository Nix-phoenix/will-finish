<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'includes/auth.php';
require_once 'db/connection.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

$response = ['success' => false];

try {
    switch ($action) {
        case 'add':
            $stmt = $conn->prepare("INSERT INTO Supplier (sup_name, tel, address) VALUES (?,?,?)");
            $stmt->bind_param('sss', $data['name'], $data['phone'], $data['address']);
            $stmt->execute();
            $stmt->close();
            break;
        case 'edit':
            $stmt = $conn->prepare("UPDATE Supplier SET sup_name=?, tel=?, address=? WHERE sup_id=?");
            $stmt->bind_param('sssi', $data['name'], $data['phone'], $data['address'], $data['id']);
            $stmt->execute();
            $stmt->close();
            break;
        case 'delete':
            $stmt = $conn->prepare("DELETE FROM Supplier WHERE sup_id=?");
            $stmt->bind_param('i', $data['id']);
            $stmt->execute();
            $stmt->close();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit;
    }

    // Return updated list
    $result = $conn->query("SELECT sup_id AS id, sup_name AS name, tel AS phone, address FROM Supplier ORDER BY sup_id ASC");
    $suppliers = $result->fetch_all(MYSQLI_ASSOC);

    $response['success'] = true;
    $response['suppliers'] = $suppliers;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
