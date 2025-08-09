<?php
include 'includes/auth.php';
include 'db/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p_name = trim($_POST['p_name']);
    $sup_name = trim($_POST['sup_name']);
    $tel = trim($_POST['tel']);
    $address = trim($_POST['address']);
    $import_qty = intval($_POST['import_qty']);
    $import_price = floatval($_POST['import_price']);
    $import_date = $_POST['import_date'];

    // 1. Find or create supplier
    $stmt = $conn->prepare("SELECT sup_id FROM Supplier WHERE sup_name = ? LIMIT 1");
    $stmt->bind_param("s", $sup_name);
    $stmt->execute();
    $stmt->bind_result($sup_id);
    if ($stmt->fetch()) {
        $stmt->close();
    } else {
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO Supplier (sup_name, tel, address) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $sup_name, $tel, $address);
        $stmt->execute();
        $sup_id = $stmt->insert_id;
        $stmt->close();
    }

    // 2. Find product
    $stmt = $conn->prepare("SELECT p_id FROM Product WHERE p_name = ? LIMIT 1");
    $stmt->bind_param("s", $p_name);
    $stmt->execute();
    $stmt->bind_result($p_id);
    if ($stmt->fetch()) {
        $stmt->close();
    } else {
        $stmt->close();
        // Product not found, redirect with error
        header("Location: warehouse.php?error=product_not_found");
        exit;
    }

    // 3. Insert into PurchaseOrder
    $stmt = $conn->prepare("INSERT INTO PurchaseOrder (sup_id, date) VALUES (?, ?)");
    $stmt->bind_param("is", $sup_id, $import_date);
    $stmt->execute();
    $po_id = $stmt->insert_id;
    $stmt->close();

    // 4. Insert into PurchaseOrderDetail
    $stmt = $conn->prepare("INSERT INTO PurchaseOrderDetail (po_id, p_id, qty, price) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiid", $po_id, $p_id, $import_qty, $import_price);
    $stmt->execute();
    $stmt->close();

    // 5. Update product quantity
    $stmt = $conn->prepare("UPDATE Product SET qty = qty + ? WHERE p_id = ?");
    $stmt->bind_param("ii", $import_qty, $p_id);
    $stmt->execute();
    $stmt->close();

    header("Location: warehouse.php");
    exit;
}
// If not POST, redirect
header("Location: warehouse.php");
exit; 