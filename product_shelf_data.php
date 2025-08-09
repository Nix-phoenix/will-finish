<?php
// ไฟล์นี้ใช้จัดการการเพิ่ม, แก้ไข, ลบ และค้นหาข้อมูลชั้นวางสินค้า
include 'includes/auth.php';
include 'db/connection.php';

// จัดการการส่งฟอร์มเพื่อเพิ่มหรือแก้ไขข้อมูล
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ใช้ชื่อตัวแปรที่สอดคล้องกันตามข้อมูลที่ส่งมาจากฟอร์ม POST
    $pslfid = intval($_POST['productShelfId'] ?? 0);
    $pslf_location = trim($_POST['productShelfLocation'] ?? '');

    if ($pslf_location) {
        if ($pslfid > 0) {
            // อัปเดตข้อมูลชั้นวางสินค้าที่มีอยู่
            $stmt = $conn->prepare('UPDATE ProductShelf SET pslf_location=? WHERE pslf_id=?');
            $stmt->bind_param('si', $pslf_location, $pslfid);
        } else {
            // เพิ่มข้อมูลชั้นวางสินค้าใหม่
            $stmt = $conn->prepare('INSERT INTO ProductShelf (pslf_location) VALUES (?)');
            $stmt->bind_param('s', $pslf_location);
        }

        if (!$stmt->execute()) {
            $message = 'ข้อผิดพลาดในฐานข้อมูล: ' . $stmt->error;
        }
        $stmt->close();
        // ส่งผู้ใช้กลับไปที่หน้าเดิมหลังจากเสร็จสิ้นการทำงาน
        header('Location: product_shelf_data.php');
        exit;
    } else {
        $message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    }
}

// จัดการคำขอเพื่อลบข้อมูล
if (isset($_GET['delete_id'])) {
    $pslfid = intval($_GET['delete_id']);
    if ($pslfid > 0) {
        $stmt = $conn->prepare('DELETE FROM ProductShelf WHERE pslf_id = ?');
        $stmt->bind_param('i', $pslfid);
        if (!$stmt->execute()) {
            $message = 'ข้อผิดพลาดในฐานข้อมูล: ' . $stmt->error;
        }
        $stmt->close();
    }
    header('Location: product_shelf_data.php');
    exit;
}

// ดึงข้อมูลชั้นวางสินค้าตามคำค้นหา
$search = isset($_GET['search']) ? $_GET['search'] : '';
$productShelfs = [];
try {
    $like = '%' . $search . '%';
    $stmt = $conn->prepare("SELECT pslf_id AS id, pslf_location AS location FROM ProductShelf WHERE pslf_location LIKE ? ORDER BY pslf_id ASC");
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $productShelfs[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    $message = 'ข้อผิดพลาดในการดึงข้อมูลชั้นวางสินค้า: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ບັນທຶກຂໍ້ມູນຊັ້ນວາງສິນຄ້າ - ລະບົບຈັດການຮ້ານGPG</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container">
    <div class="dashboard-container">
        <h2 class="dashboard-title" style="margin-bottom: 18px;">ບັນທຶກຂໍ້ມູນຊັ້ນວາງສິນຄ້າ</h2>
        <form method="get" style="display:flex;gap:12px;margin-bottom:18px;">
            <input type="text" name="search" placeholder="ຄົ້ນຫາຊັ້ນວາງສິນຄ້າ" value="<?php echo htmlspecialchars($search); ?>" style="flex:1;padding:10px;border-radius:6px;border:1px solid #ccc;">
            <button type="submit" class="dashboard-add-btn" style="width:180px;">ຄົ້ນຫາ</button>
        </form>
        <button type="button" class="dashboard-add-btn" onclick="openAddModal()" style="margin-bottom: 18px; width: 200px;">ເພີ່ມຂໍ້ມູນຊັ້ນວາງສິນຄ້າ</button>

        <table class="dashboard-table">
            <thead>
                <tr>
                    <th>ລະຫັດ</th>
                    <th>ຕຳແໜ່ງຊັ້ນວາງສິນຄ້າ</th>
                    <th>ຈັດການ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($productShelfs)): ?>
                    <tr>
                        <td colspan="3" style="text-align:center;">ບໍ່ພົບຂໍ້ມູນ</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($productShelfs as $shelf): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($shelf['id']); ?></td>
                            <td><?php echo htmlspecialchars($shelf['location']); ?></td>
                            <td>
                                <button type="button" class="dashboard-edit-btn" onclick="openEditModal(this)" data-id="<?php echo htmlspecialchars($shelf['id']); ?>" data-location="<?php echo htmlspecialchars($shelf['location']); ?>">ແກ້ໄຂ</button>
                                <a class="dashboard-delete-btn" href="product_shelf_data.php?delete_id=<?php echo htmlspecialchars($shelf['id']); ?>" onclick="return confirm('ຢືນຢັນການລົບ?');">ລົບ</a>
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
        <h3 class="modal-title" id="modalTitle">ເພີ່ມຂໍ້ມູນຊັ້ນວາງສິນຄ້າ</h3>
        <form id="productShelfForm" method="post">
            <input type="hidden" id="productShelfId" name="productShelfId">
            <label for="productShelfLocation">ຕຳແໜ່ງຊັ້ນວາງສິນຄ້າ:</label>
            <input type="text" id="productShelfLocation" name="productShelfLocation" class="gpg-input" required>
            <div style="text-align:center;margin-top:20px;">
                <button type="submit" class="dashboard-add-btn">ບັນທຶກ</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('modalTitle').textContent = 'ເພີ່ມຂໍ້ມູນຊັ້ນວາງສິນຄ້າ';
        document.getElementById('productShelfForm').reset();
        document.getElementById('productShelfId').value = '';
        document.getElementById('productTypeModal').style.display = 'block';
    }

    function openEditModal(btn) {
        document.getElementById('modalTitle').textContent = 'ແກ້ໄຂຂໍ້ມູນຊັ້ນວາງສິນຄ້າ';
        document.getElementById('productShelfId').value = btn.dataset.id;
        document.getElementById('productShelfLocation').value = btn.dataset.location;
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
<!-- คำสั่ง SQL สำหรับการสร้างตาราง ProductShelf เพื่ออ้างอิง -->
<!--
CREATE TABLE ProductShelf (
    pslf_id INT AUTO_INCREMENT PRIMARY KEY,
    pslf_location VARCHAR(255) NOT NULL
);
-->
