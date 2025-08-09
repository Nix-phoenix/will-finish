<?php
include 'includes/auth.php';
include 'db/connection.php';

$p_id = $_GET['id'] ?? null;
if (!$p_id) die('Product ID is required.');

// Fetch product info
$product = $conn->query("SELECT * FROM Product WHERE p_id = $p_id")->fetch_assoc();
if (!$product) die('Product not found.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p_name = $_POST['p_name'];
    $price = $_POST['price'];
    $qty = $_POST['qty'];
    $unit = $_POST['unit'];
    $shelf = $_POST['shelf'];
    $type = $_POST['type'];

    $stmt = $conn->prepare("UPDATE Product SET p_name=?, price=?, qty=?, unit=?, shelf=?, type=? WHERE p_id=?");
    $stmt->bind_param("sdisssi", $p_name, $price, $qty, $unit, $shelf, $type, $p_id);
    $stmt->execute();

    header("Location: storage.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>ແກ້ໄຂສິນຄ້າ</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="gpg-card edit-product-card">
        <h2 class="gpg-title">ແກ້ໄຂສິນຄ້າ</h2>
        <form method="post" autocomplete="off">
            <label for="p_name">ຊື່ສິນຄ້າ</label>
            <input type="text" id="p_name" name="p_name" class="gpg-input" value="<?php echo htmlspecialchars($product['p_name']); ?>" required>

            <label for="price">ລາຄາ</label>
            <input type="number" id="price" name="price" class="gpg-input" value="<?php echo htmlspecialchars($product['price']); ?>" min="0" step="0.01" required>

            <label for="qty">ຈຳນວນ</label>
            <input type="number" id="qty" name="qty" class="gpg-input" value="<?php echo htmlspecialchars($product['qty']); ?>" min="0" required>

            <label for="unit">ຫົວໜ່ວຍ</label>
            <input type="text" id="unit" name="unit" class="gpg-input" value="<?php echo htmlspecialchars($product['unit']); ?>">

            <label for="shelf">ຊັ້ນວາງ</label>
            <input type="text" id="shelf" name="shelf" class="gpg-input" value="<?php echo htmlspecialchars($product['shelf']); ?>">

            <label for="type">ປະເພດ</label>
            <input type="text" id="type" name="type" class="gpg-input" value="<?php echo htmlspecialchars($product['type']); ?>">

            <button type="submit" class="dashboard-edit-btn">💾 ບັນທຶກ</button>
            <a href="storage.php" class="dashboard-delete-btn">ຍົກເລີກ</a>
        </form>
    </div>
</body>
</html>