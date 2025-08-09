<?php
// Includes for authentication and database connection.
// These files are assumed to exist in the 'includes' and 'db' directories.
include 'includes/auth.php';
include 'db/connection.php';

// This function re-fetches all product data with the necessary joined table information.
// This is called after a successful action (add, edit, delete) to send the updated data back to the frontend.
function fetchProducts($conn) {
    // MODIFIED: Fetch products from the database, performing a search across multiple fields.
    // This query joins all four related tables (ProductType, ProductBrand, ProductShelf, ProductUnit)
    // and includes their respective IDs for use in the modals.
    $stmt = $conn->prepare("SELECT
        p.p_id AS id,
        p.p_name AS name,
        p.qty,
        p.price,
        p.image_path,
        p.pt_id,
        p.pb_id,
        p.pslf_id,
        p.punit_id,
        pt.pt_name AS category,
        pb.pb_name AS brand,
        pslf.pslf_location AS shelf,
        punit.punit_name AS unit
    FROM
        Product AS p
    LEFT JOIN
        ProductType AS pt ON p.pt_id = pt.pt_id
    LEFT JOIN
        ProductBrand AS pb ON p.pb_id = pb.pb_id
    LEFT JOIN
        ProductShelf AS pslf ON p.pslf_id = pslf.pslf_id
    LEFT JOIN
        ProductUnit AS punit ON p.punit_id = punit.punit_id
    ORDER BY
        p.p_id ASC");

    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $products;
}

header('Content-Type: application/json');

// Check if the request method is POST and an action is provided.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        switch ($action) {
            // --- Handle Add Product Action ---
            case 'add':
                // Retrieve all product details from the POST request.
                $productName = $_POST['productName'];
                $productCategory = $_POST['productCategory'];
                $productBrand = $_POST['productBrand'];
                $productShelf = $_POST['productShelf'];
                $productUnit = $_POST['productUnit'];
                $productQty = $_POST['productQty'];
                $productPrice = $_POST['productPrice'];
                $image_path = null;

                // Handle image upload if a file is provided.
                if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] === UPLOAD_ERR_OK) {
                    $file_tmp_path = $_FILES['productImage']['tmp_name'];
                    $file_name = $_FILES['productImage']['name'];
                    $dest_path = 'uploads/' . basename($file_name);
                    if (move_uploaded_file($file_tmp_path, $dest_path)) {
                        $image_path = $dest_path;
                    }
                }

                // Prepare and execute the SQL INSERT statement.
                $stmt = $conn->prepare("INSERT INTO Product (p_name, qty, price, image_path, pt_id, pb_id, pslf_id, punit_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sidsiiii", $productName, $productQty, $productPrice, $image_path, $productCategory, $productBrand, $productShelf, $productUnit);
                $stmt->execute();
                $stmt->close();

                // On success, re-fetch the product list and return it.
                $products = fetchProducts($conn);
                echo json_encode(['success' => true, 'products' => $products]);
                break;

            // --- Handle Edit Product Action ---
            case 'edit':
                // Retrieve all product details from the POST request.
                $productId = $_POST['productId'];
                $productName = $_POST['productName'];
                $productCategory = $_POST['productCategory'];
                $productBrand = $_POST['productBrand'];
                $productShelf = $_POST['productShelf'];
                $productUnit = $_POST['productUnit'];
                $productQty = $_POST['productQty'];
                $productPrice = $_POST['productPrice'];
                $image_path = null;

                // Check if a new image file was uploaded.
                if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] === UPLOAD_ERR_OK) {
                    $file_tmp_path = $_FILES['productImage']['tmp_name'];
                    $file_name = $_FILES['productImage']['name'];
                    $dest_path = 'uploads/' . basename($file_name);
                    if (move_uploaded_file($file_tmp_path, $dest_path)) {
                        $image_path = $dest_path;
                        // If a new image is uploaded, use the UPDATE statement that includes image_path.
                        $stmt = $conn->prepare("UPDATE Product SET p_name = ?, qty = ?, price = ?, image_path = ?, pt_id = ?, pb_id = ?, pslf_id = ?, punit_id = ? WHERE p_id = ?");
                        $stmt->bind_param("sidsiiiii", $productName, $productQty, $productPrice, $image_path, $productCategory, $productBrand, $productShelf, $productUnit, $productId);
                    } else {
                        // If there was an error moving the new file, use the UPDATE without changing the image.
                        $stmt = $conn->prepare("UPDATE Product SET p_name = ?, qty = ?, price = ?, pt_id = ?, pb_id = ?, pslf_id = ?, punit_id = ? WHERE p_id = ?");
                        $stmt->bind_param("sidsiiii", $productName, $productQty, $productPrice, $productCategory, $productBrand, $productShelf, $productUnit, $productId);
                    }
                } else {
                    // If no new image was uploaded, use the UPDATE statement without changing image_path.
                    $stmt = $conn->prepare("UPDATE Product SET p_name = ?, qty = ?, price = ?, pt_id = ?, pb_id = ?, pslf_id = ?, punit_id = ? WHERE p_id = ?");
                    $stmt->bind_param("sidsiiii", $productName, $productQty, $productPrice, $productCategory, $productBrand, $productShelf, $productUnit, $productId);
                }

                $stmt->execute();
                $stmt->close();

                // On success, re-fetch the product list and return it.
                $products = fetchProducts($conn);
                echo json_encode(['success' => true, 'products' => $products]);
                break;

            // --- Handle Delete Product Action ---
            case 'delete':
                // Retrieve the product ID from the POST request.
                $productId = $_POST['id'];

                // First, delete related records from the `selldetail` table to prevent a foreign key constraint error.
                $stmt_selldetail_delete = $conn->prepare("DELETE FROM selldetail WHERE p_id = ?");
                $stmt_selldetail_delete->bind_param("i", $productId);
                $stmt_selldetail_delete->execute();
                $stmt_selldetail_delete->close();
                
                // Now, safely delete the product from the `Product` table.
                $stmt = $conn->prepare("DELETE FROM Product WHERE p_id = ?");
                $stmt->bind_param("i", $productId);
                $stmt->execute();
                $stmt->close();

                // On success, re-fetch the product list and return it.
                $products = fetchProducts($conn);
                echo json_encode(['success' => true, 'products' => $products]);
                break;

            default:
                echo json_encode(['success' => false, 'error' => 'Invalid action.']);
        }
    } catch (Exception $e) {
        // Handle any exceptions and return a JSON error response.
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    // If the request is not a POST or has no action, return an error.
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
}

$conn->close();
