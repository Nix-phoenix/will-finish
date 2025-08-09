<?php
// Includes necessary files for authentication and database connection
include 'includes/auth.php';
include 'db/connection.php';

// Get the ID from the URL and validate it
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: product_type.php');
    exit;
}

// Fetch the existing product type data from the database
$stmt = $conn->prepare('SELECT pt_name FROM ProductType WHERE pt_id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$productType = $result->fetch_assoc();
$stmt->close();

// If no product type is found with the given ID, redirect
if (!$productType) {
    header('Location: product_type.php');
    exit;
}

// Assign the fetched data to a variable for form pre-population
$productTypeName = $productType['pt_name'];
$errors = [];

// --- Handle Form Submission (POST) for Editing ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize the new product type name from the form
    $productTypeName = trim($_POST['productTypeName'] ?? '');

    // Validate the input
    if ($productTypeName === '') {
        $errors[] = 'ກະລຸນາປ້ອນຊື່ປະເພດສິນຄ້າ.';
    }

    // If there are no validation errors, proceed with the database update
    if (empty($errors)) {
        $stmt = $conn->prepare('UPDATE ProductType SET pt_name = ? WHERE pt_id = ?');
        $stmt->bind_param('si', $productTypeName, $id);
        
        if ($stmt->execute()) {
            // Redirect to the main product type list page on success
            header('Location: product_type.php');
            exit;
        } else {
            // Store the error message if the database query fails
            $errors[] = 'ອັບເດດຜິດພາດ: ' . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ແກ້ໄຂປະເພດສິນຄ້າ</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container">
    <h2 class="dashboard-title">ແກ້ໄຂປະເພດສິນຄ້າ</h2>

    <?php if (!empty($errors)): ?>
        <div class="error-msg">
            <?php foreach ($errors as $e) echo '<p>' . htmlspecialchars($e) . '</p>'; ?>
        </div>
    <?php endif; ?>

    <form method="post" class="gpg-form" style="max-width:600px;">
        <label>ຊື່ປະເພດສິນຄ້າ:</label>
        <input class="gpg-input" type="text" name="productTypeName" value="<?php echo htmlspecialchars($productTypeName); ?>" required>

        <div style="margin-top:18px;">
            <button type="submit" class="dashboard-add-btn">ບັນທຶກ</button>
            <a href="product_type.php" class="dashboard-cancel-btn" style="margin-left:8px;">ຍົກເລີກ</a>
        </div>
    </form>
</div>

</body>
</html>
