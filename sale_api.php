<?php
header('Content-Type: application/json');
require_once 'db/connection.php';

$response = ['success' => false, 'error' => ''];

// Read the JSON data from the request body
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if ($data === null) {
    // Check if it's a GET request for fetching data
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = 'fetch';
        $search_term = $_GET['search'] ?? '';
    } else {
        $response['error'] = 'Invalid JSON input.';
        echo json_encode($response);
        exit();
    }
} else {
    $action = $data['action'] ?? '';
}

// Start a transaction to ensure all related operations succeed or fail together for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();
}

try {
    switch ($action) {
        case 'add':
            // Get data for a new sale
            $date = $data['date'] ?? '';
            $c_id = $data['c_id'] ?? null;
            $items = $data['items'] ?? [];

            if (empty($date) || empty($c_id) || empty($items)) {
                throw new Exception('Invalid input data for adding a sale.');
            }

            // Insert into Sell table
            $stmt = $conn->prepare("INSERT INTO Sell (date, c_id) VALUES (?, ?)");
            $stmt->bind_param('si', $date, $c_id);
            if (!$stmt->execute()) {
                throw new Exception('Error inserting into Sell: ' . $stmt->error);
            }
            $s_id = $stmt->insert_id;
            $stmt->close();

            // Insert into SellDetail and update product stock for each item
            foreach ($items as $item) {
                $p_id = $item['p_id'] ?? 0;
                $qty = $item['qty'] ?? 0;
                $unit_price = $item['unit_price'] ?? 0;
                $total_price = $item['total_price'] ?? 0;

                // --- FIX: Atomic Update ---
                // We perform the stock check and update in a single query.
                // This prevents race conditions where the stock could change between a SELECT and an UPDATE.
                $update_stmt = $conn->prepare("UPDATE Product SET qty = qty - ? WHERE p_id = ? AND qty >= ?");
                $update_stmt->bind_param('iii', $qty, $p_id, $qty);
                $update_stmt->execute();

                // Check if the update was successful (i.e., a row was affected)
                if ($update_stmt->affected_rows === 0) {
                    throw new Exception("Not enough stock for product ID {$p_id}. Requested: {$qty}. Available stock may be 0 or less.");
                }
                $update_stmt->close();
                // --- END FIX ---
                
                // Insert into SellDetail table
                $stmt = $conn->prepare("INSERT INTO SellDetail (s_id, p_id, qty, price, total_price) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('iiddi', $s_id, $p_id, $qty, $unit_price, $total_price);
                if (!$stmt->execute()) {
                    throw new Exception('Error inserting into SellDetail: ' . $stmt->error);
                }
                $stmt->close();
            }
            break;

        case 'edit':
            $s_id = $data['s_id'] ?? null;
            $date = $data['date'] ?? '';
            $c_id = $data['c_id'] ?? null;
            $items = $data['items'] ?? [];

            if (empty($s_id) || empty($date) || empty($c_id) || empty($items)) {
                throw new Exception('Invalid input data for editing a sale.');
            }

            // 1. Restore old product stock and delete old details
            $stmt = $conn->prepare("SELECT p_id, qty FROM SellDetail WHERE s_id = ?");
            $stmt->bind_param('i', $s_id);
            $stmt->execute();
            $old_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            foreach ($old_items as $old_item) {
                $stmt = $conn->prepare("UPDATE Product SET qty = qty + ? WHERE p_id = ?");
                $stmt->bind_param('ii', $old_item['qty'], $old_item['p_id']);
                if (!$stmt->execute()) {
                    throw new Exception('Error restoring old product stock: ' . $stmt->error);
                }
                $stmt->close();
            }

            $stmt = $conn->prepare("DELETE FROM SellDetail WHERE s_id = ?");
            $stmt->bind_param('i', $s_id);
            if (!$stmt->execute()) {
                throw new Exception('Error deleting old SellDetail: ' . $stmt->error);
            }
            $stmt->close();

            // 2. Update Sell table
            $stmt = $conn->prepare("UPDATE Sell SET date = ?, c_id = ? WHERE s_id = ?");
            $stmt->bind_param('sii', $date, $c_id, $s_id);
            if (!$stmt->execute()) {
                throw new Exception('Error updating Sell table: ' . $stmt->error);
            }
            $stmt->close();

            // 3. Insert new details and update product stock
            foreach ($items as $item) {
                $p_id = $item['p_id'] ?? 0;
                $qty = $item['qty'] ?? 0;
                $unit_price = $item['unit_price'] ?? 0;
                $total_price = $item['total_price'] ?? 0;
                
                // --- FIX: Atomic Update ---
                // Similar to the 'add' case, we ensure the stock is sufficient in one atomic step.
                $update_stmt = $conn->prepare("UPDATE Product SET qty = qty - ? WHERE p_id = ? AND qty >= ?");
                $update_stmt->bind_param('iii', $qty, $p_id, $qty);
                $update_stmt->execute();
                
                if ($update_stmt->affected_rows === 0) {
                    throw new Exception("Not enough stock for product ID {$p_id}. Requested: {$qty}. Available stock may be 0 or less.");
                }
                $update_stmt->close();
                // --- END FIX ---

                $stmt = $conn->prepare("INSERT INTO SellDetail (s_id, p_id, qty, price, total_price) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('iiddi', $s_id, $p_id, $qty, $unit_price, $total_price);
                if (!$stmt->execute()) {
                    throw new Exception('Error inserting new SellDetail: ' . $stmt->error);
                }
                $stmt->close();
            }

            break;

        case 'delete':
            $s_id = $data['id'] ?? null;

            if (empty($s_id)) {
                throw new Exception('Sale ID is required for deletion.');
            }

            // 1. Get sold product quantities to restore stock
            $stmt = $conn->prepare("SELECT p_id, qty FROM SellDetail WHERE s_id = ?");
            $stmt->bind_param('i', $s_id);
            $stmt->execute();
            $sale_details = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            // Restore product stock
            foreach ($sale_details as $detail) {
                $stmt = $conn->prepare("UPDATE Product SET qty = qty + ? WHERE p_id = ?");
                $stmt->bind_param('ii', $detail['qty'], $detail['p_id']);
                if (!$stmt->execute()) {
                    throw new Exception('Error restoring product stock: ' . $stmt->error);
                }
                $stmt->close();
            }

            // 2. Delete from SellDetail
            $stmt = $conn->prepare("DELETE FROM SellDetail WHERE s_id = ?");
            $stmt->bind_param('i', $s_id);
            if (!$stmt->execute()) {
                throw new Exception('Error deleting from SellDetail: ' . $stmt->error);
            }
            $stmt->close();

            // 3. Delete from Sell
            $stmt = $conn->prepare("DELETE FROM Sell WHERE s_id = ?");
            $stmt->bind_param('i', $s_id);
            if (!$stmt->execute()) {
                throw new Exception('Error deleting from Sell: ' . $stmt->error);
            }
            $stmt->close();
            break;

        case 'fetch': // New case to handle fetch requests (including search)
        case 'search':
            $search_term = $_GET['search'] ?? '';
            $search_param = "%" . $search_term . "%";

            // Base query to get all sales
            $query = "SELECT
                s.s_id,
                s.date,
                c.c_id,
                c.c_name,
                p.p_id,
                p.p_name,
                pt.pt_name AS type,
                pu.punit_name AS unit,
                sd.qty,
                sd.price AS unit_price,
                sd.total_price
            FROM Sell s
            JOIN SellDetail sd ON s.s_id = sd.s_id
            JOIN Product p ON sd.p_id = p.p_id
            JOIN ProductType pt ON p.pt_id = pt.pt_id
            JOIN ProductUnit pu ON p.punit_id = pu.punit_id
            LEFT JOIN Customer c ON c.c_id = s.c_id";
            
            if (!empty($search_term)) {
                // Use the search query provided by the user
                $query .= " WHERE c.c_name LIKE ? OR p.p_name LIKE ? OR s.s_id LIKE ?";
            }
            
            $query .= " ORDER BY s.s_id DESC LIMIT 100";
            
            $stmt = $conn->prepare($query);
            
            if (!empty($search_term)) {
                $stmt->bind_param('sss', $search_param, $search_param, $search_param);
            }
            
            $stmt->execute();
            $flat_sales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            // Group the flat data into a nested structure for the frontend
            $sales = [];
            foreach ($flat_sales as $row) {
                if (!isset($sales[$row['s_id']])) {
                    $sales[$row['s_id']] = [
                        's_id' => $row['s_id'],
                        'date' => $row['date'],
                        'c_id' => $row['c_id'],
                        'c_name' => $row['c_name'],
                        'items' => []
                    ];
                }
                $sales[$row['s_id']]['items'][] = [
                    'p_id' => $row['p_id'],
                    'p_name' => $row['p_name'],
                    'type' => $row['type'],
                    'unit' => $row['unit'],
                    'qty' => $row['qty'],
                    'unit_price' => $row['unit_price'],
                    'total_price' => $row['total_price']
                ];
            }

            $response['sales'] = array_values($sales);
            $response['success'] = true;
            break;

        default:
            throw new Exception('Invalid action specified.');
    }

    // If all database operations succeed, commit the transaction for POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $conn->commit();
        $response['success'] = true;
    }
} catch (Exception $e) {
    // Rollback the transaction on error
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $conn->rollback();
    }
    $response['error'] = $e->getMessage();
}

// Re-fetch all sales to send the updated data to the frontend after POST operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $response['success']) {
    $search_param = "%";
    $query = "SELECT
        s.s_id,
        s.date,
        c.c_id,
        c.c_name,
        p.p_id,
        p.p_name,
        pt.pt_name AS type,
        pu.punit_name AS unit,
        sd.qty,
        sd.price AS unit_price,
        sd.total_price
    FROM Sell s
    JOIN SellDetail sd ON s.s_id = sd.s_id
    JOIN Product p ON sd.p_id = p.p_id
    JOIN ProductType pt ON p.pt_id = pt.pt_id
    JOIN ProductUnit pu ON p.punit_id = pu.punit_id
    LEFT JOIN Customer c ON c.c_id = s.c_id
    ORDER BY s.s_id DESC LIMIT 100";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $flat_sales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $sales = [];
    foreach ($flat_sales as $row) {
        if (!isset($sales[$row['s_id']])) {
            $sales[$row['s_id']] = [
                's_id' => $row['s_id'],
                'date' => $row['date'],
                'c_id' => $row['c_id'],
                'c_name' => $row['c_name'],
                'items' => []
            ];
        }
        $sales[$row['s_id']]['items'][] = [
            'p_id' => $row['p_id'],
            'p_name' => $row['p_name'],
            'type' => $row['type'],
            'unit' => $row['unit'],
            'qty' => $row['qty'],
            'unit_price' => $row['unit_price'],
            'total_price' => $row['total_price']
        ];
    }
    $response['sales'] = array_values($sales);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

$conn->close();
?>
