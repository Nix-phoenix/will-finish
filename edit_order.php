<?php
include 'includes/auth.php';
include 'db/connection.php';

$order_id = $_GET['id'] ?? null;
if (!$order_id) die('Order ID is required.');

// Fetch order, customer, and payment info
$order = $conn->query("
    SELECT s.s_id, s.c_id, c.c_name, s.status
    FROM Sell s
    LEFT JOIN Customer c ON s.c_id = c.c_id
    WHERE s.s_id = $order_id
")->fetch_assoc();
if (!$order) die('Order not found.');

// Fetch order item (assuming one product per order for simplicity)
$item = $conn->query("
    SELECT sd.sd_id, sd.p_id, p.p_name, sd.qty, sd.price
    FROM SellDetail sd
    JOIN Product p ON sd.p_id = p.p_id
    WHERE sd.s_id = $order_id
    LIMIT 1
")->fetch_assoc();

// Fetch all customers for dropdown
$customers = $conn->query("SELECT c_id, c_name FROM Customer");

// Fetch all products for dropdown and build a PHP array for JS
$products = $conn->query("SELECT p_id, p_name, price FROM Product");
$product_prices = [];
$product_options = '';
while($p = $products->fetch_assoc()) {
    $product_prices[$p['p_id']] = $p['price'];
    $selected = ($p['p_id'] == $item['p_id']) ? 'selected' : '';
    $product_options .= '<option value="' . $p['p_id'] . '" ' . $selected . '>' . htmlspecialchars($p['p_name']) . '</option>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $c_id = intval($_POST['c_id']);
    $p_id = intval($_POST['p_id']);
    $qty = intval($_POST['qty']);
    $price = floatval($_POST['price']);
    $status = $_POST['status'];
    $payment_date = isset($_POST['payment_date']) ? $_POST['payment_date'] : null;

    // Get the old product and quantity
    $old = $conn->query("SELECT p_id, qty FROM SellDetail WHERE s_id = $order_id")->fetch_assoc();
    $old_p_id = $old['p_id'];
    $old_qty = $old['qty'];

    // Undo the old order
    $stmt = $conn->prepare("UPDATE Product SET qty = qty + ? WHERE p_id = ?");
    $stmt->bind_param("ii", $old_qty, $old_p_id);
    $stmt->execute();
    $stmt->close();

    // Apply the new order
    $stmt = $conn->prepare("UPDATE Product SET qty = qty - ? WHERE p_id = ?");
    $stmt->bind_param("ii", $qty, $p_id);
    $stmt->execute();
    $stmt->close();

    // Update Sell
    $update = $conn->prepare("UPDATE Sell SET c_id=?, status=? WHERE s_id=?");
    $update->bind_param('isi', $c_id, $status, $order_id);
    $update->execute();

    // Update SellDetail
    $total_price = $qty * $price;
    $update2 = $conn->prepare("UPDATE SellDetail SET p_id=?, qty=?, price=?, total_price=? WHERE s_id=? AND sd_id=?");
    $update2->bind_param('iidiii', $p_id, $qty, $price, $total_price, $order_id, $item['sd_id']);
    $update2->execute();

    // Update or insert Payment
    $exists = $conn->query("SELECT pm_id FROM Payment WHERE s_id = $order_id")->fetch_assoc();
    if ($exists) {
        if ($payment_date) {
            $stmt = $conn->prepare("UPDATE Payment SET status=?, date=? WHERE s_id=?");
            $stmt->bind_param('ssi', $status, $payment_date, $order_id);
        } else {
            $stmt = $conn->prepare("UPDATE Payment SET status=? WHERE s_id=?");
            $stmt->bind_param('si', $status, $order_id);
        }
    } else {
        if ($payment_date) {
            $stmt = $conn->prepare("INSERT INTO Payment (s_id, status, date) VALUES (?, ?, ?)");
            $stmt->bind_param('iss', $order_id, $status, $payment_date);
        } else {
            $stmt = $conn->prepare("INSERT INTO Payment (s_id, status) VALUES (?, ?)");
            $stmt->bind_param('is', $order_id, $status);
        }
    }
    $stmt->execute();

    $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : 'ordering_payment.php';
    header('Location: ' . $redirect);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>ແກ້ໄຂຄຳສັ່ງຊື້</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="gpg-card" style="max-width: 500px; margin: 40px auto;">
        <h2 class="gpg-title">ແກ້ໄຂຄຳສັ່ງຊື້</h2>
        <form method="post">
            <label for="c_id">ລູກຄ້າ</label>
            <select id="c_id" name="c_id" class="gpg-input" required>
                <?php
                // Reset pointer and fetch customers again for dropdown
                $customers->data_seek(0);
                while($c = $customers->fetch_assoc()): ?>
                    <option value="<?php echo $c['c_id']; ?>" <?php if($c['c_id']==$order['c_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($c['c_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="p_id">ສິນຄ້າ</label>
            <select id="p_id" name="p_id" class="gpg-input" required>
                <?php echo $product_options; ?>
            </select>

            <label for="qty">ຈຳນວນ</label>
            <input type="number" id="qty" name="qty" class="gpg-input" value="<?php echo htmlspecialchars($item['qty']); ?>" min="1" required>

            <label for="price">ລາຄາຕໍ່ຫນ່ວຍ</label>
            <input type="number" id="price" name="price" class="gpg-input" value="<?php echo htmlspecialchars($item['price'] ?? $product_prices[$item['p_id']]); ?>" min="0" step="0.01" required readonly>

            <label for="status">ສະຖານະການຈ່າຍ</label>
            <select id="status" name="status" class="gpg-input" required>
                <option value="unpaid" <?php if($order['status']=='unpaid') echo 'selected'; ?>>ຄ້າງຈ່າຍ</option>
                <option value="paid" <?php if($order['status']=='paid') echo 'selected'; ?>>ຈ່າຍແລ້ວ</option>
            </select>

            <button type="submit" class="gpg-btn" style="margin-top:16px;">ບັນທຶກ</button>
            <a href="index.php" class="gpg-btn" style="background:#888;margin-top:8px;">ຍົກເລີກ</a>
        </form>
    </div>
    <script>
        // Product prices from PHP
        var productPrices = <?php echo json_encode($product_prices); ?>;

        function updatePrice() {
            var p_id = document.getElementById('p_id').value;
            var price = productPrices[p_id] || 0;
            document.getElementById('price').value = price;
        }

        document.getElementById('p_id').addEventListener('change', updatePrice);

        // Set price on page load
        window.onload = updatePrice;
    </script>
</body>
</html>