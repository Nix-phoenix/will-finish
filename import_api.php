<?php
// import_api.php

// Includes for authentication and database connection.
// This assumes 'includes/auth.php' and 'db/connection.php' exist and are configured correctly.
include 'includes/auth.php';
include 'db/connection.php';

header('Content-Type: application/json');

// Get the action from the POST request.
$action = $_POST['action'] ?? '';
$response = ['success' => false, 'error' => 'Invalid action'];

// Handle different API actions based on the value of the 'action' parameter.
switch ($action) {

    // --- Actions for the main Import table ---

    case 'add_import':
        // Add a new import record.
        $date = $_POST['importDate'];
        $poId = $_POST['purchaseOrderId'];

        // Use a prepared statement to prevent SQL injection.
        $stmt = $conn->prepare("INSERT INTO Import (DATE, po_id) VALUES (?, ?)");
        $stmt->bind_param("ss", $date, $poId);

        if ($stmt->execute()) {
            $response['success'] = true;
        } else {
            $response['error'] = 'Error adding import record: ' . $stmt->error;
        }
        $stmt->close();
        
        // After a successful operation, re-fetch all import data.
        if ($response['success']) {
            $stmt_fetch = $conn->prepare("SELECT Ip_id AS id, DATE AS date, po_id FROM Import ORDER BY DATE DESC");
            $stmt_fetch->execute();
            $result_fetch = $stmt_fetch->get_result();
            $imports = $result_fetch->fetch_all(MYSQLI_ASSOC);
            $stmt_fetch->close();
            $response['imports'] = $imports;
        }
        break;

    case 'edit_import':
        // Edit an existing import record.
        $id = $_POST['importId'];
        $date = $_POST['importDate'];
        $poId = $_POST['purchaseOrderId'];

        $stmt = $conn->prepare("UPDATE Import SET DATE = ?, po_id = ? WHERE Ip_id = ?");
        $stmt->bind_param("ssi", $date, $poId, $id);

        if ($stmt->execute()) {
            $response['success'] = true;
        } else {
            $response['error'] = 'Error editing import record: ' . $stmt->error;
        }
        $stmt->close();
        
        if ($response['success']) {
            $stmt_fetch = $conn->prepare("SELECT Ip_id AS id, DATE AS date, po_id FROM Import ORDER BY DATE DESC");
            $stmt_fetch->execute();
            $result_fetch = $stmt_fetch->get_result();
            $imports = $result_fetch->fetch_all(MYSQLI_ASSOC);
            $stmt_fetch->close();
            $response['imports'] = $imports;
        }
        break;

    case 'delete_import':
        // Delete a main import record.
        $id = $_POST['id'];

        // Check for related details to maintain data integrity.
        $stmt_check_details = $conn->prepare("SELECT COUNT(*) FROM ImportDetail WHERE Ip_id = ?");
        $stmt_check_details->bind_param("i", $id);
        $stmt_check_details->execute();
        $stmt_check_details->bind_result($detailCount);
        $stmt_check_details->fetch();
        $stmt_check_details->close();

        if ($detailCount > 0) {
            $response['success'] = false;
            $response['error'] = 'Cannot delete import record with associated details. Please delete the details first.';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            $conn->close();
            exit();
        }

        $stmt = $conn->prepare("DELETE FROM Import WHERE Ip_id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $response['success'] = true;
        } else {
            $response['error'] = 'Error deleting import record: ' . $stmt->error;
        }
        $stmt->close();
        
        if ($response['success']) {
            $stmt_fetch = $conn->prepare("SELECT Ip_id AS id, DATE AS date, po_id FROM Import ORDER BY DATE DESC");
            $stmt_fetch->execute();
            $result_fetch = $stmt_fetch->get_result();
            $imports = $result_fetch->fetch_all(MYSQLI_ASSOC);
            $stmt_fetch->close();
            $response['imports'] = $imports;
        }
        break;

    // --- Actions for the ImportDetail table ---

    case 'add_import_detail':
        // Add a new import detail record.
        $ipId = $_POST['parentImportId'];
        $pId = $_POST['importDetailProductId'];
        $qty = $_POST['importDetailQty'];
        $price = $_POST['importDetailPrice'];

        $conn->begin_transaction();

        try {
            $stmt_insert = $conn->prepare("INSERT INTO ImportDetail (Ip_id, p_id, qty, price) VALUES (?, ?, ?, ?)");
            $stmt_insert->bind_param("iiid", $ipId, $pId, $qty, $price);
            $stmt_insert->execute();
            $stmt_insert->close();

            $conn->commit();
            $response['success'] = true;
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            $response['error'] = 'Transaction failed: ' . $exception->getMessage();
        }
        
        if ($response['success']) {
            // Re-fetch all import detail data.
            $stmt_fetch = $conn->prepare("SELECT id.Ipd_id, id.Ip_id, id.p_id, p.p_name, id.qty, id.price FROM ImportDetail AS id LEFT JOIN Product AS p ON id.p_id = p.p_id ORDER BY id.Ip_id DESC");
            $stmt_fetch->execute();
            $result_fetch = $stmt_fetch->get_result();
            $importDetails = $result_fetch->fetch_all(MYSQLI_ASSOC);
            $stmt_fetch->close();
            $response['importDetails'] = $importDetails;
        }
        break;

    case 'edit_import_detail':
        // Edit an existing import detail record.
        $id = $_POST['importDetailId'];
        $ipId = $_POST['parentImportId'];
        $pId = $_POST['importDetailProductId'];
        $newQty = $_POST['importDetailQty'];
        $newPrice = $_POST['importDetailPrice'];
        
        $conn->begin_transaction();

        try {
            // Update the ImportDetail record.
            $stmt_update = $conn->prepare("UPDATE ImportDetail SET Ip_id = ?, p_id = ?, qty = ?, price = ? WHERE Ipd_id = ?");
            $stmt_update->bind_param("iiidi", $ipId, $pId, $newQty, $newPrice, $id);
            $stmt_update->execute();
            $stmt_update->close();
            
            $conn->commit();
            $response['success'] = true;
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            $response['error'] = 'Transaction failed: ' . $exception->getMessage();
        }
        
        if ($response['success']) {
            $stmt_fetch = $conn->prepare("SELECT id.Ipd_id, id.Ip_id, id.p_id, p.p_name, id.qty, id.price FROM ImportDetail AS id LEFT JOIN Product AS p ON id.p_id = p.p_id ORDER BY id.Ip_id DESC");
            $stmt_fetch->execute();
            $result_fetch = $stmt_fetch->get_result();
            $importDetails = $result_fetch->fetch_all(MYSQLI_ASSOC);
            $stmt_fetch->close();
            $response['importDetails'] = $importDetails;
        }
        break;

    case 'delete_import_detail':
        // Delete an import detail record.
        $id = $_POST['id'];

        $conn->begin_transaction();

        try {
            $stmt_delete = $conn->prepare("DELETE FROM ImportDetail WHERE Ipd_id = ?");
            $stmt_delete->bind_param("i", $id);
            $stmt_delete->execute();
            $stmt_delete->close();
            
            $conn->commit();
            $response['success'] = true;
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            $response['error'] = 'Transaction failed: ' . $exception->getMessage();
        }
        
        if ($response['success']) {
            $stmt_fetch = $conn->prepare("SELECT id.Ipd_id, id.Ip_id, id.p_id, p.p_name, id.qty, id.price FROM ImportDetail AS id LEFT JOIN Product AS p ON id.p_id = p.p_id ORDER BY id.Ip_id DESC");
            $stmt_fetch->execute();
            $result_fetch = $stmt_fetch->get_result();
            $importDetails = $result_fetch->fetch_all(MYSQLI_ASSOC);
            $stmt_fetch->close();
            $response['importDetails'] = $importDetails;
        }
        break;
}

// Send the JSON response.
echo json_encode($response, JSON_UNESCAPED_UNICODE);

$conn->close();
?>
