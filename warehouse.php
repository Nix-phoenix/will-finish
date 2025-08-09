<?php
include 'includes/auth.php';
include 'db/connection.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = '';
$import_where = '';
if ($search !== '') {
    $search_escaped = $conn->real_escape_string($search);
    $where = "WHERE p_name LIKE '%$search_escaped%' OR type LIKE '%$search_escaped%' OR unit LIKE '%$search_escaped%' OR shelf LIKE '%$search_escaped%'";
    $import_where = "WHERE p.p_name LIKE '%$search_escaped%' OR s.sup_name LIKE '%$search_escaped%' OR s.tel LIKE '%$search_escaped%' OR s.address LIKE '%$search_escaped%' OR po.date LIKE '%$search_escaped%'";
}
$product_sql = "SELECT p_id, p_name, price, qty, unit, shelf, type FROM Product $where";
$product_result = $conn->query($product_sql);

// Fetch import records (join PurchaseOrder, PurchaseOrderDetail, Product, Supplier)
$import_sql = "SELECT 
    po.po_id,
    po.date AS import_date,
    p.p_name,
    s.sup_name,
    s.tel,
    s.address,
    pod.qty AS import_qty,
    pod.price AS import_price
FROM PurchaseOrder po
LEFT JOIN PurchaseOrderDetail pod ON po.po_id = pod.po_id
LEFT JOIN Product p ON pod.p_id = p.p_id
LEFT JOIN Supplier s ON po.sup_id = s.sup_id
$import_where
ORDER BY po.date DESC, po.po_id DESC";
$import_result = $conn->query($import_sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Warehouse & Import - Store Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="dashboard-container">
        <h2 class="dashboard-title">ຄັ່ງສິນຄ້າ & ການນຳເຂົ້າ</h2>
        <form style="display:flex;gap:16px;margin-bottom:18px;" method="get">
            <input type="text" name="search" placeholder="ຄົ້ນຫາສິນຄ້າ..." value="<?php echo htmlspecialchars($search); ?>" style="flex:1;padding:10px;border-radius:6px;border:1px solid #ccc;">
            <button type="submit" class="dashboard-add-btn" style="width:200px;">ຄົ້ນຫາ</button>
        </form>
        <h3 class="dashboard-section-title" style="margin:18px 0 8px 0;">ຄັ່ງສິນຄ້າ</h3>
        <button type="button" class="dashboard-add-btn" id="openAddProductModalBtn" style="margin-bottom: 10px; width: 200px;">ເພີ່ມສິນຄ້າໃໝ່</button>

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
            <tbody id="productTableBody">
                <?php while($row = $product_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['p_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['p_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['price']); ?></td>
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
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h3 class="dashboard-section-title" style="margin:18px 0 8px 0;">ການນຳເຂົ້າສິນຄ້າເຂົ້າ</h3>
        <button type="button" class="dashboard-add-btn" id="openAddImportModalBtn" style="margin: 18px 0 10px 0; width: 200px;">ເພີ່ມການນຳເຂົ້າໃໝ່</button>

        <table class="dashboard-table">
            <thead>
                <tr>
                    <th>ຊື່ສິນຄ້າ</th>
                    <th>ຜູ້ຈັດຫາ</th>
                    <th>ເບີໂທ</th>
                    <th>ທີ່ຢູ່</th>
                    <th>ຈຳນວນນຳເຂົ້າ</th>
                    <th>ລາຄານຳເຂົ້າ</th>
                    <th>ວັນທີນຳເຂົ້າ</th>
                    <th>ຈັດການ</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $import_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['p_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['sup_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['tel']); ?></td>
                    <td><?php echo htmlspecialchars($row['address']); ?></td>
                    <td><?php echo htmlspecialchars($row['import_qty']); ?></td>
                    <td><?php echo htmlspecialchars($row['import_price']); ?></td>
                    <td><?php echo htmlspecialchars($row['import_date']); ?></td>
                    <td><button type="button" class="dashboard-edit-btn openEditImportModal"
                        data-poid="<?php echo htmlspecialchars($row['po_id']); ?>"
                        data-pname="<?php echo htmlspecialchars($row['p_name']); ?>"
                        data-supname="<?php echo htmlspecialchars($row['sup_name']); ?>"
                        data-tel="<?php echo htmlspecialchars($row['tel']); ?>"
                        data-address="<?php echo htmlspecialchars($row['address']); ?>"
                        data-importqty="<?php echo htmlspecialchars($row['import_qty']); ?>"
                        data-importprice="<?php echo htmlspecialchars($row['import_price']); ?>"
                        data-importdate="<?php echo htmlspecialchars($row['import_date']); ?>"
                    >ແກ້ໄຂ</button></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <!-- Edit Product Modal -->
    <div id="editProductModal" class="modal" style="display:none;">
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
    <!-- Edit Import Modal -->
    <div id="editImportModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" id="closeEditImportModal">&times;</span>
            <h2>ແກ້ໄຂການນຳເຂົ້າສິນຄ້າ</h2>
            <form id="editImportForm" method="post">
                <input type="hidden" name="edit_po_id" id="edit_po_id">
                <label>ຊື່ສິນຄ້າ</label>
                <input type="text" name="edit_p_name" id="edit_import_p_name" readonly style="width:100%;margin-bottom:10px;">
                <label>ຜູ້ຈັດຫາ</label>
                <input type="text" name="edit_sup_name" id="edit_import_sup_name" readonly style="width:100%;margin-bottom:10px;">
                <label>ເບີໂທ</label>
                <input type="text" name="edit_tel" id="edit_import_tel" readonly style="width:100%;margin-bottom:10px;">
                <label>ທີ່ຢູ່</label>
                <input type="text" name="edit_address" id="edit_import_address" readonly style="width:100%;margin-bottom:10px;">
                <label>ຈຳນວນນຳເຂົ້າ</label>
                <input type="number" name="edit_import_qty" id="edit_import_qty" required style="width:100%;margin-bottom:10px;">
                <label>ລາຄານຳເຂົ້າ</label>
                <input type="number" name="edit_import_price" id="edit_import_price" required style="width:100%;margin-bottom:10px;">
                <label>ວັນທີນຳເຂົ້າ</label>
                <input type="text" name="edit_import_date" id="edit_import_date" readonly style="width:100%;margin-bottom:10px;">
                <button type="submit" class="dashboard-add-btn" style="width:100%;">ບັນທຶກ</button>
            </form>
        </div>
    </div>
    <!-- Add Product Modal -->
    <div id="addProductModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" id="closeAddProductModal">&times;</span>
            <h3 class="modal-title">ເພີ່ມສິນຄ້າໃໝ່</h3>
            <form id="addProductForm" method="post" action="add_product.php">
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
                <button type="submit" class="dashboard-add-btn" style="width:100%;">ບັນທຶກ</button>
            </form>
        </div>
    </div>
    <!-- Add Import Modal -->
    <div id="addImportModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" id="closeAddImportModal">&times;</span>
            <h3 class="modal-title">ເພີ່ມການນຳເຂົ້າໃໝ່</h3>
            <form id="addImportForm" method="post" action="add_import.php">
                <label>ຊື່ສິນຄ້າ</label>
                <input type="text" name="p_name" required>
                <label>ຜູ້ຈັດຫາ</label>
                <input type="text" name="sup_name" required>
                <label>ເບີໂທ</label>
                <input type="text" name="tel">
                <label>ທີ່ຢູ່</label>
                <input type="text" name="address">
                <label>ຈຳນວນນຳເຂົ້າ</label>
                <input type="number" name="import_qty" required>
                <label>ລາຄານຳເຂົ້າ</label>
                <input type="number" name="import_price" required>
                <label>ວັນທີນຳເຂົ້າ</label>
                <input type="date" name="import_date" required>
                <button type="submit" class="dashboard-add-btn" style="width:100%;">ບັນທຶກ</button>
            </form>
        </div>
    </div>
    <script src="assets/js/scripts.js"></script>
    <script>
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
    // Edit Import Modal logic
    var importModal = document.getElementById('editImportModal');
    var closeImportBtn = document.getElementById('closeEditImportModal');
    var importForm = document.getElementById('editImportForm');
    Array.from(document.getElementsByClassName('openEditImportModal')).forEach(function(btn) {
        btn.onclick = function() {
            document.getElementById('edit_po_id').value = btn.getAttribute('data-poid');
            document.getElementById('edit_import_p_name').value = btn.getAttribute('data-pname');
            document.getElementById('edit_import_sup_name').value = btn.getAttribute('data-supname');
            document.getElementById('edit_import_tel').value = btn.getAttribute('data-tel');
            document.getElementById('edit_import_address').value = btn.getAttribute('data-address');
            document.getElementById('edit_import_qty').value = btn.getAttribute('data-importqty');
            document.getElementById('edit_import_price').value = btn.getAttribute('data-importprice');
            document.getElementById('edit_import_date').value = btn.getAttribute('data-importdate');
            importModal.style.display = 'block';
        };
    });
    closeImportBtn.onclick = function() {
        importModal.style.display = 'none';
    };
    window.onclick = function(event) {
        if (event.target == importModal) {
            importModal.style.display = 'none';
        }
    };
    importForm.onsubmit = function(e) {
        e.preventDefault();
        var poid = document.getElementById('edit_po_id').value;
        importForm.action = 'edit_import.php?po_id=' + encodeURIComponent(poid);
        importForm.submit();
    };
    // Add Product Modal logic
    var addProductModal = document.getElementById('addProductModal');
    var openAddProductModalBtn = document.getElementById('openAddProductModalBtn');
    var closeAddProductModalBtn = document.getElementById('closeAddProductModal');
    openAddProductModalBtn.onclick = function() {
        addProductModal.style.display = 'block';
    };
    closeAddProductModalBtn.onclick = function() {
        addProductModal.style.display = 'none';
    };
    window.onclick = function(event) {
        if (event.target == addProductModal) {
            addProductModal.style.display = 'none';
        }
    };
    // Add Import Modal logic
    var addImportModal = document.getElementById('addImportModal');
    var openAddImportModalBtn = document.getElementById('openAddImportModalBtn');
    var closeAddImportModalBtn = document.getElementById('closeAddImportModal');
    openAddImportModalBtn.onclick = function() {
        addImportModal.style.display = 'block';
    };
    closeAddImportModalBtn.onclick = function() {
        addImportModal.style.display = 'none';
    };
    window.onclick = function(event) {
        if (event.target == addImportModal) {
            addImportModal.style.display = 'none';
        }
    };
    </script>
</body>
</html>