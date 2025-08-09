<?php
include 'includes/auth.php';
include 'db/connection.php';

// Initialize variables
$search = isset($_GET['search']) ? $_GET['search'] : '';
$message = '';

// Handle add / edit submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cid = intval($_POST['customerId'] ?? 0);
    $name = trim($_POST['customerName'] ?? '');
    $tel  = trim($_POST['customerPhone'] ?? '');
    $addr = trim($_POST['customerAddress'] ?? '');
    if ($name && $tel && $addr) {
        if ($cid > 0) {
            $stmt = $conn->prepare('UPDATE Customer SET c_name=?, tel=?, address=? WHERE c_id=?');
            $stmt->bind_param('sssi', $name, $tel, $addr, $cid);
        } else {
            $stmt = $conn->prepare('INSERT INTO Customer (c_name, tel, address) VALUES (?,?,?)');
            $stmt->bind_param('sss', $name, $tel, $addr);
        }
        if (!$stmt->execute()) {
            $message = 'DB Error: '. $stmt->error;
        }
        $stmt->close();
        header('Location: customer_data.php');
        exit;
    } else {
        $message = 'ກະລຸນາກຽບກອບຂໍ້ມູນໃຫ້ຄົບ';
    }
}

// Fetch customer list with optional search
$customers = [];
try {
    $like = '%' . $search . '%';
    $stmt = $conn->prepare("SELECT c_id, c_name, tel, address FROM Customer WHERE c_name LIKE ? OR tel LIKE ? OR address LIKE ? ORDER BY c_id DESC");
    $stmt->bind_param('sss', $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    $message = 'Error fetching customers: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ບັນທືກຂໍ້ມູນລູກຄ້າ - ລະບົບຈັດການຮ້ານGPG</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<?php include 'includes/navbar.php'; ?>

    <div class="container">
        <div class="dashboard-container">
            <h2 class="dashboard-title" style="margin-bottom: 18px;">ບັນທືກຂໍ້ມູນລູກຄ້າ</h2>
        <form method="get" style="display:flex;gap:12px;margin-bottom:18px;">
            <input type="text" name="search" placeholder="ຄົ້ນຫາ (ລູກຄ້າ, ສິນຄ້າ, ເລກທີ)" value="<?php echo htmlspecialchars($search); ?>" style="flex:1;padding:10px;border-radius:6px;border:1px solid #ccc;">
            <button type="submit" class="dashboard-add-btn" style="width:180px;">ຄົ້ນຫາ</button>
        </form>
        <button type="button" class="dashboard-add-btn" onclick="openAddModal()" style="margin-bottom:18px;width:200px;"> ເພີ່ມຂໍ້ມູນລູກຄ້າ</button>

            <!-- Customer Data Table -->
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>ລະບົດ</th>
                        <th>ຊື່ ແລະ ນາມສະກຸນ</th>
                        <th>ເບີໂທ</th>
                        <th>ທີ່ຢູ່</th>
                        <th>ຈັດການ</th>
                    </tr>
                </thead>
                <tbody>
<?php if (empty($customers)): ?>
    <tr><td colspan="5" style="text-align:center;">ບໍ່ພົບຂໍ້ມູນ</td></tr>
<?php else: ?>
    <?php foreach ($customers as $index => $cus): ?>
        <tr>
            <td><?php echo $index + 1; ?></td>
            <td><?php echo htmlspecialchars($cus['c_name']); ?></td>
            <td><?php echo htmlspecialchars($cus['tel']); ?></td>
            <td><?php echo htmlspecialchars($cus['address']); ?></td>
            <td>
                <button type="button" class="dashboard-edit-btn" onclick="openEditModal(this)" data-id="<?php echo $cus['c_id']; ?>" data-name="<?php echo htmlspecialchars($cus['c_name']); ?>" data-tel="<?php echo htmlspecialchars($cus['tel']); ?>" data-address="<?php echo htmlspecialchars($cus['address']); ?>">ແກ້ໄຂ</button>
                <a class="dashboard-delete-btn" href="customer_delete.php?id=<?php echo $cus['c_id']; ?>" onclick="return confirm('ຢືນຢັນການລົບ?');">ລົບ</a>
            </td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
</tbody>

    <!-- Add/Edit Customer Modal -->
    <div id="customerModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3 class="modal-title" id="modalTitle">ເພີ່ມຂໍ້ມູນລູກຄ້າ</h3>
            <form id="customerForm" method="post">
                <input type="hidden" id="customerId" name="customerId">
                
                <label for="customerName">ຊື່ ແລະ ນາມສະກຸນ:</label>
                <input type="text" id="customerName" name="customerName" class="gpg-input" required>
                
                <label for="customerPhone">ເບີໂທ:</label>
                <input type="text" id="customerPhone" name="customerPhone" class="gpg-input" required>
                
                <label for="customerAddress">ທີ່ຢູ່:</label>
                <textarea id="customerAddress" name="customerAddress" class="gpg-input" rows="3" required></textarea>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="dashboard-add-btn">ບັນທຶກ</button>
                </div>
            </form>
        </div>
    </div>

    <script>
function openAddModal(){
  document.getElementById('modalTitle').textContent='ເພີ່ມຂໍ້ມູນລູກຄ້າ';
  document.getElementById('customerForm').reset();
  document.getElementById('customerId').value='';
  document.getElementById('customerModal').style.display='block';
}
function openEditModal(btn){
  document.getElementById('modalTitle').textContent='ແກ້ໄຂຂໍ້ມູນລູກຄ້າ';
  document.getElementById('customerId').value=btn.dataset.id;
  document.getElementById('customerName').value=btn.dataset.name;
  document.getElementById('customerPhone').value=btn.dataset.tel;
  document.getElementById('customerAddress').value=btn.dataset.address;
  document.getElementById('customerModal').style.display='block';
}
function closeModal(){
  document.getElementById('customerModal').style.display='none';
}
window.onclick=function(e){
  if(e.target==document.getElementById('customerModal')){closeModal();}
};
</script>
</body>
</html>
