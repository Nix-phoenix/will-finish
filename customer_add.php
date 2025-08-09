<?php
include 'includes/auth.php';
include 'db/connection.php';

$name = $tel = $address = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $tel     = trim($_POST['tel'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '')  $errors[] = 'ກະລຸນາປ້ອນຊື່ລູກຄ້າ';
    if ($tel === '')   $errors[] = 'ກະລຸນາປ້ອນເບີໂທ';
    if ($address === '') $errors[] = 'ກະລຸນາປ້ອນທີ່ຢູ່';

    if (!$errors) {
        $stmt = $conn->prepare('INSERT INTO Customer (c_name, tel, address) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $name, $tel, $address);
        if ($stmt->execute()) {
            header('Location: customer_data.php');
            exit;
        } else {
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
    <title>ເພີ່ມລູກຄ້າ</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container">
    <h2 class="dashboard-title">ເພີ່ມຂໍ້ມູນລູກຄ້າ</h2>
    <?php if ($errors): ?>
        <div class="error-msg">
            <?php foreach ($errors as $e) echo '<p>'.htmlspecialchars($e).'</p>'; ?>
        </div>
    <?php endif; ?>
    <form method="post" class="gpg-form" style="max-width:600px;">
        <label>ຊື່ ແລະ ນາມສະກຸນ:</label>
        <input class="gpg-input" type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required>

        <label>ເບີໂທ:</label>
        <input class="gpg-input" type="text" name="tel" value="<?php echo htmlspecialchars($tel); ?>" required>

        <label>ທີ່ຢູ່:</label>
        <textarea class="gpg-input" name="address" rows="3" required><?php echo htmlspecialchars($address); ?></textarea>

        <button type="submit" class="dashboard-add-btn" style="margin-top:18px;">ບັນທຶກ</button>
        <a href="customer_data.php" class="dashboard-cancel-btn" style="margin-left:8px;">ຍົກເລີກ</a>
    </form>
</div>
</body>
</html>
