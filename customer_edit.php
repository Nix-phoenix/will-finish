<?php
include 'includes/auth.php';
include 'db/connection.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: customer_data.php');
    exit;
}

// Fetch existing
$stmt = $conn->prepare('SELECT c_name, tel, address FROM Customer WHERE c_id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();

if (!$customer) {
    header('Location: customer_data.php');
    exit;
}

$name = $customer['c_name'];
$tel = $customer['tel'];
$address = $customer['address'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $tel  = trim($_POST['tel'] ?? '');
    $address = trim($_POST['address'] ?? '');
    if ($name === '') $errors[]='ກະລຸນາປ້ອນຊື່';
    if ($tel === '') $errors[]='ກະລຸນາປ້ອນເບີໂທ';
    if ($address === '') $errors[]='ກະລຸນາປ້ອນທີ່ຢູ່';

    if (!$errors) {
        $stmt = $conn->prepare('UPDATE Customer SET c_name=?, tel=?, address=? WHERE c_id=?');
        $stmt->bind_param('sssi', $name, $tel, $address, $id);
        if ($stmt->execute()) {
            header('Location: customer_data.php');
            exit;
        } else {
            $errors[]='ອັບເດດຜິດພາດ: '.$stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
<meta charset="UTF-8">
<title>ແກ້ໄຂລູກຄ້າ</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container">
<h2 class="dashboard-title">ແກ້ໄຂຂໍ້ມູນລູກຄ້າ</h2>
<?php if ($errors): ?><div class="error-msg"><?php foreach ($errors as $e) echo '<p>'.htmlspecialchars($e).'</p>'; ?></div><?php endif; ?>
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
