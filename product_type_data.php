<?php
include 'includes/auth.php';
include 'db/connection.php';

// Handle add / edit submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ptid = intval($_POST['productTypeId'] ?? 0);
    $name = trim($_POST['productTypeName'] ?? '');

    if ($name) {
        if ($ptid > 0) {
            $stmt = $conn->prepare('UPDATE ProductType SET pt_name=? WHERE pt_id=?');
            $stmt->bind_param('si', $name, $ptid);
        } else {
            $stmt = $conn->prepare('INSERT INTO ProductType (pt_name) VALUES (?)');
            $stmt->bind_param('s', $name);
        }
        if (!$stmt->execute()) {
            $message = 'DB Error: ' . $stmt->error;
        }
        $stmt->close();
        header('Location: product_type_data.php');
        exit;
    } else {
        $message = 'ກະລຸນາກຽບກອບຂໍ້ມູນໃຫ້ຄົບ';
    }
}

// Handle delete request
if (isset($_GET['delete_id'])) {
    $ptid = intval($_GET['delete_id']);
    if ($ptid > 0) {
        $stmt = $conn->prepare('DELETE FROM ProductType WHERE pt_id = ?');
        $stmt->bind_param('i', $ptid);
        if (!$stmt->execute()) {
            $message = 'DB Error: ' . $stmt->error;
        }
        $stmt->close();
    }
    header('Location: product_type_data.php');
    exit;
}

// Fetch product types based on the search term
$search = isset($_GET['search']) ? $_GET['search'] : '';
$productTypes = [];
try {
    $like = '%' . $search . '%';
    $stmt = $conn->prepare("SELECT pt_id AS id, pt_name AS name FROM ProductType WHERE pt_name LIKE ? ORDER BY pt_id ASC");
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $productTypes[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    $message = 'Error fetching product types: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ບັນທຶກຂໍ້ມູນປະເພດສິນຄ້າ - ລະບົບຈັດການຮ້ານGPG</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container">
    <div class="dashboard-container">
        <h2 class="dashboard-title" style="margin-bottom: 18px;">ບັນທຶກຂໍ້ມູນປະເພດສິນຄ້າ</h2>
        <form method="get" style="display:flex;gap:12px;margin-bottom:18px;">
            <input type="text" name="search" placeholder="ຄົ້ນຫາປະເພດສິນຄ້າ" value="<?php echo htmlspecialchars($search); ?>" style="flex:1;padding:10px;border-radius:6px;border:1px solid #ccc;">
            <button type="submit" class="dashboard-add-btn" style="width:180px;">ຄົ້ນຫາ</button>
        </form>
        <button type="button" class="dashboard-add-btn" onclick="openAddModal()" style="margin-bottom: 18px; width: 200px;">ເພີ່ມຂໍ້ມູນປະເພດສິນຄ້າ</button>

        <table class="dashboard-table">
            <thead>
                <tr>
                    <th>ລະຫັດ</th>
                    <th>ຊື່ປະເພດສິນຄ້າ</th>
                    <th>ຈັດການ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($productTypes)): ?>
                    <tr>
                        <td colspan="3" style="text-align:center;">ບໍ່ພົບຂໍ້ມູນ</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($productTypes as $type): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($type['id']); ?></td>
                            <td><?php echo htmlspecialchars($type['name']); ?></td>
                            <td>
                                <button type="button" class="dashboard-edit-btn" onclick="openEditModal(this)" data-id="<?php echo htmlspecialchars($type['id']); ?>" data-name="<?php echo htmlspecialchars($type['name']); ?>">ແກ້ໄຂ</button>
                                <a class="dashboard-delete-btn" href="product_type_data.php?delete_id=<?php echo htmlspecialchars($type['id']); ?>" onclick="return confirm('ຢືນຢັນການລົບ?');">ລົບ</a>
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
        <h3 class="modal-title" id="modalTitle">ເພີ່ມຂໍ້ມູນປະເພດສິນຄ້າ</h3>
        <form id="productTypeForm" method="post">
            <input type="hidden" id="productTypeId" name="productTypeId">
            <label for="productTypeName">ຊື່ປະເພດສິນຄ້າ:</label>
            <input type="text" id="productTypeName" name="productTypeName" class="gpg-input" required>
            <div style="text-align:center;margin-top:20px;">
                <button type="submit" class="dashboard-add-btn">ບັນທຶກ</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('modalTitle').textContent = 'ເພີ່ມຂໍ້ມູນປະເພດສິນຄ້າ';
        document.getElementById('productTypeForm').reset();
        document.getElementById('productTypeId').value = '';
        document.getElementById('productTypeModal').style.display = 'block';
    }

    function openEditModal(btn) {
        document.getElementById('modalTitle').textContent = 'ແກ້ໄຂຂໍ້ມູນປະເພດສິນຄ້າ';
        document.getElementById('productTypeId').value = btn.dataset.id;
        document.getElementById('productTypeName').value = btn.dataset.name;
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
</body>
</html>