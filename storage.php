<?php
include 'includes/auth.php';
include 'db/connection.php';

// Fetch products from the new Product table structure
$sql = "SELECT p_id, p_name, price, qty, unit, shelf, type FROM Product";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Storage of Goods - Store Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="dashboard-container">
        <?php if (isset($_GET['msg'])): ?>
            <div class="dashboard-alert" style="margin-bottom:16px;">
                <?php
                switch ($_GET['msg']) {
                    case 'deleted':
                        echo 'ລົບສິນຄ້າສຳເລັດ!';
                        break;
                    case 'has_sales':
                        echo 'ບໍ່ສາມາດລົບສິນຄ້ານີ້ໄດ້ ເນື່ອງຈາກມີການຂາຍແລ້ວ!';
                        break;
                    case 'not_found':
                        echo 'ບໍ່ພົບສິນຄ້າ!';
                        break;
                    case 'delete_error':
                        echo 'ລົບສິນຄ້າຜິດພາດ!';
                        break;
                    case 'missing_id':
                        echo 'ຂໍ້ມູນສິນຄ້າບໍ່ຖືກຕ້ອງ!';
                        break;
                }
                ?>
            </div>
        <?php endif; ?>
        <h2 class="dashboard-title">ຈັດການສິນຄ້າ</h2>
        <button class="dashboard-add-btn" id="openAddProductModal">ເພີ່ມສິນຄ້າໃໝ່</button>

        <div id="addProductModal" class="modal">
            <div class="modal-content">
                <span class="close" id="closeAddProductModal">&times;</span>
                <h3 class="modal-title">ເພີ່ມສິນຄ້າໃໝ່</h3>
                <form action="add_product.php" method="post">
                    <label>ຊື່ສິນຄ້າ</label>
                    <input type="text" name="p_name" required>
                    <label>ລາຄາ</label>
                    <input type="number" name="price" required>
                    <label>ຈຳນວນ</label>
                    <input type="number" name="qty" required>
                    <label>ຫົວໜ່ວຍ</label>
                    <input type="text" name="unit">
                    <label>ຊັ້ນວາງ</label>
                    <input type="text" name="shelf">
                    <label>ປະເພດ</label>
                    <input type="text" name="type">
                    <button type="submit" class="dashboard-edit-btn" style="width:100%;">ບັນທຶກ</button>
                </form>
            </div>
        </div>

        <!-- Edit Product Modal -->
        <div id="editProductModal" class="modal">
            <div class="modal-content">
                <span class="close" id="closeEditProductModal">&times;</span>
                <h3 class="modal-title">ແກ້ໄຂສິນຄ້າ</h3>
                <form id="editProductForm" method="post">
                    <input type="hidden" name="p_id" id="edit_p_id">
                    <label>ຊື່ສິນຄ້າ</label>
                    <input type="text" name="p_name" id="edit_p_name" required>
                    <label>ລາຄາ</label>
                    <input type="number" name="price" id="edit_price" required>
                    <label>ຈຳນວນ</label>
                    <input type="number" name="qty" id="edit_qty" required>
                    <label>ຫົວໜ່ວຍ</label>
                    <input type="text" name="unit" id="edit_unit">
                    <label>ຊັ້ນວາງ</label>
                    <input type="text" name="shelf" id="edit_shelf">
                    <label>ປະເພດ</label>
                    <input type="text" name="type" id="edit_type">
                    <button type="submit" class="dashboard-edit-btn" style="width:100%;">ບັນທຶກ</button>
                </form>
            </div>
        </div>

        <table class="dashboard-table">
            <thead>
                <tr>
                    <th>ລະຫັດສິນຄ້າ</th>
                    <th>ຊື່ສິນຄ້າ</th>
                    <th>ລາຄາ</th>
                    <th>ຈຳນວນ</th>
                    <th>ຫົວໜ່ວຍ</th>
                    <th>ຊັ້ນວາງ</th>
                    <th>ປະເພດ</th>
                    <th>ຈັດການ</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['p_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['p_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['price']); ?>kip</td>
                    <td><?php echo htmlspecialchars($row['qty']); ?></td>
                    <td><?php echo htmlspecialchars($row['unit']); ?></td>
                    <td><?php echo htmlspecialchars($row['shelf']); ?></td>
                    <td><?php echo htmlspecialchars($row['type']); ?></td>
                    <td>
                        <button type="button" class="dashboard-edit-btn openEditProductModal"
                            data-pid="<?php echo htmlspecialchars($row['p_id']); ?>"
                            data-pname="<?php echo htmlspecialchars($row['p_name']); ?>"
                            data-price="<?php echo htmlspecialchars($row['price']); ?>"
                            data-qty="<?php echo htmlspecialchars($row['qty']); ?>"
                            data-unit="<?php echo htmlspecialchars($row['unit']); ?>"
                            data-shelf="<?php echo htmlspecialchars($row['shelf']); ?>"
                            data-type="<?php echo htmlspecialchars($row['type']); ?>"
                        >ແກ້ໄຂ</button>
                        <a href="delete_product.php?id=<?php echo urlencode($row['p_id']); ?>" class="dashboard-delete-btn" onclick="return confirm('ຢືນຢັນການລົບ?');">ລົບ</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <script src="assets/js/scripts.js"></script>
    <script>
document.getElementById('openAddProductModal').onclick = function() {
    document.getElementById('addProductModal').style.display = 'block';
};
document.getElementById('closeAddProductModal').onclick = function() {
    document.getElementById('addProductModal').style.display = 'none';
};
// Optional: close modal when clicking outside
window.onclick = function(event) {
    var modal = document.getElementById('addProductModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
};

// Edit Product Modal logic
var editModal = document.getElementById('editProductModal');
var closeEditBtn = document.getElementById('closeEditProductModal');
var editForm = document.getElementById('editProductForm');

// Open modal and fill fields
Array.from(document.getElementsByClassName('openEditProductModal')).forEach(function(btn) {
    btn.onclick = function() {
        document.getElementById('edit_p_id').value = btn.getAttribute('data-pid');
        document.getElementById('edit_p_name').value = btn.getAttribute('data-pname');
        document.getElementById('edit_price').value = btn.getAttribute('data-price');
        document.getElementById('edit_qty').value = btn.getAttribute('data-qty');
        document.getElementById('edit_unit').value = btn.getAttribute('data-unit');
        document.getElementById('edit_shelf').value = btn.getAttribute('data-shelf');
        document.getElementById('edit_type').value = btn.getAttribute('data-type');
        editModal.style.display = 'block';
    };
});
closeEditBtn.onclick = function() {
    editModal.style.display = 'none';
};
window.onclick = function(event) {
    if (event.target == editModal) {
        editModal.style.display = 'none';
    }
};
// Submit form
editForm.onsubmit = function(e) {
    e.preventDefault();
    var pid = document.getElementById('edit_p_id').value;
    editForm.action = 'edit_product.php?id=' + encodeURIComponent(pid);
    editForm.submit();
};
</script>
</body>
</html>