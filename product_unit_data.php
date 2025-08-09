<?php
// ไฟล์นี้ใช้จัดการการเพิ่ม, แก้ไข, ลบ และค้นหาข้อมูลหน่วยสินค้า
include 'includes/auth.php';
include 'db/connection.php';

// จัดการการส่งฟอร์มเพื่อเพิ่มหรือแก้ไขข้อมูล
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ใช้ชื่อตัวแปรที่สอดคล้องกันตามข้อมูลที่ส่งมาจากฟอร์ม POST
    $punitid = intval($_POST['productUnitId'] ?? 0);
    $punit_name = trim($_POST['productUnitName'] ?? '');

    if ($punit_name) {
        if ($punitid > 0) {
            // อัปเดตข้อมูลหน่วยสินค้าที่มีอยู่
            $stmt = $conn->prepare('UPDATE ProductUnit SET punit_name=? WHERE punit_id=?');
            $stmt->bind_param('si', $punit_name, $punitid);
        } else {
            // เพิ่มข้อมูลหน่วยสินค้าใหม่
            $stmt = $conn->prepare('INSERT INTO ProductUnit (punit_name) VALUES (?)');
            $stmt->bind_param('s', $punit_name);
        }

        if (!$stmt->execute()) {
            $message = 'ข้อผิดพลาดในฐานข้อมูล: ' . $stmt->error;
        }
        $stmt->close();
        // ส่งผู้ใช้กลับไปที่หน้าเดิมหลังจากเสร็จสิ้นการทำงาน
        header('Location: product_unit_data.php');
        exit;
    } else {
        $message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    }
}

// จัดการคำขอเพื่อลบข้อมูล
if (isset($_GET['delete_id'])) {
    $punitid = intval($_GET['delete_id']);
    if ($punitid > 0) {
        $stmt = $conn->prepare('DELETE FROM ProductUnit WHERE punit_id = ?');
        $stmt->bind_param('i', $punitid);
        if (!$stmt->execute()) {
            $message = 'ข้อผิดพลาดในฐานข้อมูล: ' . $stmt->error;
        }
        $stmt->close();
    }
    header('Location: product_unit_data.php');
    exit;
}

// ดึงข้อมูลหน่วยสินค้าตามคำค้นหา
$search = isset($_GET['search']) ? $_GET['search'] : '';
$productUnits = [];
try {
    $like = '%' . $search . '%';
    $stmt = $conn->prepare("SELECT punit_id AS id, punit_name AS name FROM ProductUnit WHERE punit_name LIKE ? ORDER BY punit_id ASC");
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $productUnits[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    $message = 'ข้อผิดพลาดในการดึงข้อมูลหน่วยสินค้า: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ບັນທຶກຂໍ້ມູນໜ່ວຍສິນຄ້າ - ລະບົບຈັດການຮ້ານGPG</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container">
    <div class="dashboard-container">
        <h2 class="dashboard-title" style="margin-bottom: 18px;">ບັນທຶກຂໍ້ມູນໜ່ວຍສິນຄ້າ</h2>
        <form method="get" style="display:flex;gap:12px;margin-bottom:18px;">
            <input type="text" name="search" placeholder="ຄົ້ນຫາໜ່ວຍສິນຄ້າ" value="<?php echo htmlspecialchars($search); ?>" style="flex:1;padding:10px;border-radius:6px;border:1px solid #ccc;">
            <button type="submit" class="dashboard-add-btn" style="width:180px;">ຄົ້ນຫາ</button>
        </form>
        <button type="button" class="dashboard-add-btn" onclick="openAddModal()" style="margin-bottom: 18px; width: 200px;">ເພີ່ມຂໍ້ມູນໜ່ວຍສິນຄ້າ</button>

        <table class="dashboard-table">
            <thead>
                <tr>
                    <th>ລະຫັດ</th>
                    <th>ຊື່ໜ່ວຍສິນຄ້າ</th>
                    <th>ຈັດການ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($productUnits)): ?>
                    <tr>
                        <td colspan="3" style="text-align:center;">ບໍ່ພົບຂໍ້ມູນ</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($productUnits as $unit): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($unit['id']); ?></td>
                            <td><?php echo htmlspecialchars($unit['name']); ?></td>
                            <td>
                                <button type="button" class="dashboard-edit-btn" onclick="openEditModal(this)" data-id="<?php echo htmlspecialchars($unit['id']); ?>" data-name="<?php echo htmlspecialchars($unit['name']); ?>">ແກ້ໄຂ</button>
                                <a class="dashboard-delete-btn" href="product_unit_data.php?delete_id=<?php echo htmlspecialchars($unit['id']); ?>" onclick="return confirm('ຢືນຢັນການລົບ?');">ລົບ</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="productTypeModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3 class="modal-title" id="modalTitle">ເພີ່ມຂໍ້ມູນໜ່ວຍສິນຄ້າ</h3>
        <form id="productUnitForm" method="post">
            <input type="hidden" id="productUnitId" name="productUnitId">
            <label for="productUnitName">ຊື່ໜ່ວຍສິນຄ້າ:</label>
            <input type="text" id="productUnitName" name="productUnitName" class="gpg-input" required>
            <div style="text-align:center;margin-top:20px;">
                <button type="submit" class="dashboard-add-btn">ບັນທຶກ</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('modalTitle').textContent = 'ເພີ່ມຂໍ້ມູນໜ່ວຍສິນຄ້າ';
        document.getElementById('productUnitForm').reset();
        document.getElementById('productUnitId').value = '';
        document.getElementById('productTypeModal').style.display = 'block';
    }

    function openEditModal(btn) {
        document.getElementById('modalTitle').textContent = 'ແກ້ໄຂຂໍ້ມູນໜ່ວຍສິນຄ້າ';
        document.getElementById('productUnitId').value = btn.dataset.id;
        document.getElementById('productUnitName').value = btn.dataset.name;
        document.getElementById('productTypeModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('productTypeModal').style.display = 'none';
    }

    window.onclick = function(e) {
        if (e.target == document.getElementById('productTypeModal')) {
            closeModal();
        }
    };
</script>
<!-- คำสั่ง SQL สำหรับการสร้างตาราง ProductUnit เพื่ออ้างอิง -->
<!--
CREATE TABLE ProductUnit (
    punit_id INT AUTO_INCREMENT PRIMARY KEY,
    punit_name VARCHAR(100) NOT NULL
);
-->
