<?php
// Includes necessary files for authentication and database connection
include 'includes/auth.php';
include 'db/connection.php';

// Initialize variables for the form data and potential errors
$productTypeName = '';
$errors = [];

// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productTypeName = trim($_POST['productTypeName'] ?? '');

    // Validate the input
    if ($productTypeName === '') {
        $errors[] = 'ກະລຸນາປ້ອນຊື່ປະເພດສິນຄ້າ.';
    }

    // If there are no errors, proceed with database insertion
    if (empty($errors)) {
        $stmt = $conn->prepare('INSERT INTO ProductType (pt_name) VALUES (?)');
        $stmt->bind_param('s', $productTypeName);

        if ($stmt->execute()) {
            // Redirect to the main product type list page on success
            header('Location: product_type.php');
            exit;
        } else {
            // Store the error message if the database query fails
            $errors[] = 'ບັນທຶກຜິດພາດ: ' . $stmt->error;
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
    <title>ເພີ່ມປະເພດສິນຄ້າ</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container">
    <h2 class="dashboard-title">ເພີ່ມປະເພດສິນຄ້າ</h2>

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
