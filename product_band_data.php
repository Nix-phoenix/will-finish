<?php
include 'includes/auth.php';
include 'db/connection.php';

// Handle add / edit submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Use consistent variable names based on the POST data
    $pbid = intval($_POST['productBrandId'] ?? 0);
    $pbname = trim($_POST['productBrandName'] ?? '');

    if ($pbname) {
        if ($pbid > 0) {
            // Update an existing product brand
            $stmt = $conn->prepare('UPDATE ProductBrand SET pb_name=? WHERE pb_id=?');
            $stmt->bind_param('si', $pbname, $pbid);
        } else {
            // Insert a new product brand
            $stmt = $conn->prepare('INSERT INTO ProductBrand (pb_name) VALUES (?)');
            $stmt->bind_param('s', $pbname);
        }

        if (!$stmt->execute()) {
            $message = 'DB Error: ' . $stmt->error;
        }
        $stmt->close();
        header('Location: product_band_data.php');
        exit;
    } else {
        $message = 'ກະລຸນາກຽບກອບຂໍ້ມູນໃຫ້ຄົບ';
    }
}

// Handle delete request
if (isset($_GET['delete_id'])) {
    $pbid = intval($_GET['delete_id']);
    if ($pbid > 0) {
        $stmt = $conn->prepare('DELETE FROM ProductBrand WHERE pb_id = ?');
        $stmt->bind_param('i', $pbid);
        if (!$stmt->execute()) {
            $message = 'DB Error: ' . $stmt->error;
        }
        $stmt->close();
    }
    header('Location: product_type_data.php');
    exit;
}

// Fetch product brands based on the search term
$search = isset($_GET['search']) ? $_GET['search'] : '';
$productBrands = [];
try {
    $like = '%' . $search . '%';
    $stmt = $conn->prepare("SELECT pb_id AS id, pb_name AS name FROM ProductBrand WHERE pb_name LIKE ? ORDER BY pb_id ASC");
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $productBrands[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    $message = 'Error fetching product brands: ' . $e->getMessage();
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
                <?php if (empty($productBrands)): ?>
                    <tr>
                        <td colspan="3" style="text-align:center;">ບໍ່ພົບຂໍ້ມູນ</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($productBrands as $brand): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($brand['id']); ?></td>
                            <td><?php echo htmlspecialchars($brand['name']); ?></td>
                            <td>
                                <button type="button" class="dashboard-edit-btn" onclick="openEditModal(this)" data-id="<?php echo htmlspecialchars($brand['id']); ?>" data-name="<?php echo htmlspecialchars($brand['name']); ?>">ແກ້ໄຂ</button>
                                <a class="dashboard-delete-btn" href="product_type_data.php?delete_id=<?php echo htmlspecialchars($brand['id']); ?>" onclick="return confirm('ຢືນຢັນການລົບ?');">ລົບ</a>
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
            <!-- Updated input names to match the POST handling -->
            <input type="hidden" id="productBrandId" name="productBrandId">
            <label for="productBrandName">ຊື່ປະເພດສິນຄ້າ:</label>
            <input type="text" id="productBrandName" name="productBrandName" class="gpg-input" required>
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
        document.getElementById('productBrandId').value = '';
        document.getElementById('productTypeModal').style.display = 'block';
    }

    function openEditModal(btn) {
        document.getElementById('modalTitle').textContent = 'ແກ້ໄຂຂໍ້ມູນປະເພດສິນຄ້າ';
        // Updated to use the new data attributes
        document.getElementById('productBrandId').value = btn.dataset.id;
        document.getElementById('productBrandName').value = btn.dataset.name;
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
<!-- The CREATE TABLE statement for reference -->
<!--
CREATE TABLE ProductBrand (
    pb_id INT AUTO_INCREMENT PRIMARY KEY,
    pb_name VARCHAR(255) NOT NULL
);
-->
