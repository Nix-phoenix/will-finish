<?php
// This is an API endpoint for managing product types.
// It handles add, edit, and delete actions via POST requests.

// Includes necessary files for authentication and database connection
include 'includes/auth.php';
include 'db/connection.php';

// Set the response content type to JSON
header('Content-Type: application/json');

// Initialize a response array
$response = [
    'success' => false,
    'message' => 'Invalid request.'
];

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the 'action' parameter is set in the POST data
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            // --- Logic for adding a new product type ---
            $productTypeName = trim($_POST['productTypeName'] ?? '');

            if (empty($productTypeName)) {
                $response['message'] = 'ກະລຸນາປ້ອນຊື່ປະເພດສິນຄ້າ.';
            } else {
                $stmt = $conn->prepare('INSERT INTO ProductType (pt_name) VALUES (?)');
                $stmt->bind_param('s', $productTypeName);

                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'ເພີ່ມຂໍ້ມູນປະເພດສິນຄ້າສຳເລັດແລ້ວ.';
                } else {
                    $response['message'] = 'ບັນທຶກຜິດພາດ: ' . $stmt->error;
                }
                $stmt->close();
            }
            break;

        case 'edit':
            // --- Logic for editing an existing product type ---
            $productTypeId = intval($_POST['productTypeId'] ?? 0);
            $productTypeName = trim($_POST['productTypeName'] ?? '');

            if ($productTypeId <= 0) {
                $response['message'] = 'ID ປະເພດສິນຄ້າບໍ່ຖືກຕ້ອງ.';
            } elseif (empty($productTypeName)) {
                $response['message'] = 'ກະລຸນາປ້ອນຊື່ປະເພດສິນຄ້າ.';
            } else {
                $stmt = $conn->prepare('UPDATE ProductType SET pt_name = ? WHERE pt_id = ?');
                $stmt->bind_param('si', $productTypeName, $productTypeId);
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'ແກ້ໄຂຂໍ້ມູນປະເພດສິນຄ້າສຳເລັດແລ້ວ.';
                } else {
                    $response['message'] = 'ອັບເດດຜິດພາດ: ' . $stmt->error;
                }
                $stmt->close();
            }
            break;

        case 'delete':
            // --- Logic for deleting a product type ---
            $productTypeId = intval($_POST['productTypeId'] ?? 0);

            if ($productTypeId <= 0) {
                $response['message'] = 'ID ປະເພດສິນຄ້າບໍ່ຖືກຕ້ອງ.';
            } else {
                $stmt = $conn->prepare('DELETE FROM ProductType WHERE pt_id = ?');
                $stmt->bind_param('i', $productTypeId);

                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'ລຶບຂໍ້ມູນປະເພດສິນຄ້າສຳເລັດແລ້ວ.';
                } else {
                    $response['message'] = 'ລຶບຂໍ້ມູນຜິດພາດ: ' . $stmt->error;
                }
                $stmt->close();
            }
            break;

        default:
            $response['message'] = 'ການກະທຳບໍ່ຖືກຕ້ອງ.';
            break;
    }
}

// Encode the response array as a JSON string and output it
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
