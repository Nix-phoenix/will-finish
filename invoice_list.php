<?php
include 'includes/auth.php';
include 'db/connection.php';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query 
$sql = "SELECT s.s_id, s.date, c.c_name, SUM(sd.total_price) AS total
         FROM Sell s 
         LEFT JOIN Customer c ON s.c_id = c.c_id 
         LEFT JOIN SellDetail sd ON s.s_id = sd.s_id 
         WHERE 1";
$params = [];
$types  = '';
if ($search !== '') {
    $sql   .= " AND (s.s_id = ? OR c.c_name LIKE ? )";
    $types .= 'is';
    $params[] = intval($search);
    $params[] = "%$search%";
}
$sql .= " GROUP BY s.s_id ORDER BY s.date DESC";

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$sales   = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="lo">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ລາຍການໃບບິນ</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<?php include 'includes/navbar.php'; ?>

<div class="container">
    <div class="dashboard-container">
        <h2 class="dashboard-title" style="margin-bottom:18px;">ລາຍການໃບບິນ</h2>
        <form method="get" style="display:flex;gap:12px;margin-bottom:18px;">
            <input type="text" name="search" placeholder="ຄົ້ນຫາ (ID, ຊື່ລູກຄ້າ)" value="<?php echo htmlspecialchars($search); ?>" style="flex:1;padding:10px;border-radius:6px;border:1px solid #ccc;">
            <button type="submit" class="dashboard-add-btn" style="width:180px;">ຄົ້ນຫາ</button>
        </form>
        <table class="dashboard-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ວັນທີ</th>
                    <th>ລູກຄ້າ</th>
                    <th>ຍອດລວມ</th>
                    <th>ຈັດການ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sales as $sale): ?>
                    <tr>
                        <td><?php echo $sale['s_id']; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($sale['date'])); ?></td>
                        <td><?php echo htmlspecialchars($sale['c_name']); ?></td>
                        <td><?php echo number_format($sale['total'], 0, '.', ','); ?> ກິບ</td>
                        <td>
                            <a href="invoice.php?id=<?php echo $sale['s_id']; ?>" class="dashboard-edit-btn" target="_blank">ເບິ່ງ/ພິມ</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($sales)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center;">ບໍ່ມີຂໍ້ມູນ</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</html>