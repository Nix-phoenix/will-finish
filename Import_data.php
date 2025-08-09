<?php
// Includes for authentication and database connection.
// It is assumed 'includes/auth.php' and 'db/connection.php' exist.
include 'includes/auth.php';
include 'db/connection.php';

// Get search term from URL, if it exists.
$search = isset($_GET['search']) ? $_GET['search'] : '';

// --- PHP LOGIC TO FETCH DATA ---

// This code fetches data from the PurchaseOrder table for the dropdown.
$stmt_po = $conn->prepare("SELECT po_id, date, sup_id FROM PurchaseOrder ORDER BY po_id DESC");
$stmt_po->execute();
$result_po = $stmt_po->get_result();
$purchaseOrders = $result_po->fetch_all(MYSQLI_ASSOC);
$stmt_po->close();

// Fetch import data, performing a search across import ID, date, and purchase order ID.
$stmt = $conn->prepare("SELECT
    i.Ip_id AS id,
    i.DATE AS date,
    i.po_id
FROM
    Import AS i
WHERE
    i.Ip_id LIKE ? OR i.DATE LIKE ? OR i.po_id LIKE ?
ORDER BY
    i.DATE DESC");

// Create a like parameter for the prepared statement.
$like = '%' . $search . '%';
$stmt->bind_param("sss", $like, $like, $like);
$stmt->execute();
$result = $stmt->get_result();
$imports = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// NEW: Fetch data from the ImportDetail table.
// This query joins with the Product table to get the product name.
$stmt_import_detail = $conn->prepare("SELECT
    id.Ipd_id,
    id.Ip_id,
    id.p_id,
    p.p_name,
    id.qty,
    id.price
FROM
    ImportDetail AS id
LEFT JOIN
    Product AS p ON id.p_id = p.p_id
ORDER BY
    id.Ip_id DESC");
$stmt_import_detail->execute();
$result_import_detail = $stmt_import_detail->get_result();
$importDetails = $result_import_detail->fetch_all(MYSQLI_ASSOC);
$stmt_import_detail->close();

// NEW: Fetch all products for the new modal dropdown.
$stmt_product = $conn->prepare("SELECT p_id, p_name FROM Product ORDER BY p_name ASC");
$stmt_product->execute();
$result_product = $stmt_product->get_result();
$products = $result_product->fetch_all(MYSQLI_ASSOC);
$stmt_product->close();
?>
<!DOCTYPE html>
<html lang="lo">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ບັນທຶກຂໍ້ມູນການນຳເຂົ້າ - ລະບົບຈັດການຮ້ານGPG</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* General Body and Container Styles */
        body {
            background-color: #f4f7f9;
            font-family: 'Noto Sans Lao', sans-serif;
            line-height: 1.6;
            color: #444;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }

        /* Dashboard Container and Title */
        .dashboard-container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        .dashboard-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        /* Search and Add Button Group */
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            gap: 15px;
        }

        .search-form {
            display: flex;
            flex: 1;
            gap: 10px;
        }

        .search-input {
            flex: 1;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .search-input:focus {
            border-color: #007bff;
            outline: none;
        }

        .btn {
            padding: 12px 25px;
            border-radius: 6px;
            border: none;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }

        .btn-add {
            background-color: #28a745;
            width: 250px;
        }

        .btn-add:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        /* Data Table */
        .dashboard-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .dashboard-table thead tr {
            background-color: #f0f4f7;
            color: #555;
            text-align: left;
        }

        .dashboard-table th, .dashboard-table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 15px;
            text-align: left;
        }

        .dashboard-table th:first-child, .dashboard-table td:first-child {
            text-align: center;
        }

        .dashboard-table tbody tr:last-child td {
            border-bottom: none;
        }

        .dashboard-table tbody tr:hover {
            background-color: #f9f9f9;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .btn-edit, .btn-delete {
            padding: 8px 12px;
            border-radius: 4px;
            border: none;
            color: #fff;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 14px;
        }

        .btn-edit {
            background-color: #ffc107;
        }

        .btn-edit:hover {
            background-color: #e0a800;
        }

        .btn-delete {
            background-color: #dc3545;
        }

        .btn-delete:hover {
            background-color: #c82333;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            transition: color 0.3s;
        }

        .close:hover, .close:focus {
            color: #333;
            text-decoration: none;
            cursor: pointer;
        }

        .modal-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .gpg-input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .gpg-input:focus {
            border-color: #007bff;
            outline: none;
        }

        .gpg-input[type="file"] {
            padding: 10px;
        }

        label {
            display: block;
            font-weight: 700;
            margin-bottom: 8px;
            color: #555;
        }

        .modal-buttons {
            text-align: center;
            margin-top: 20px;
        }

        /* Confirmation Modal Styles */
        #confirmationModal .modal-content {
            max-width: 400px;
            text-align: center;
        }
        #confirmationModal .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        /* Responsive design for smaller screens */
        @media (max-width: 768px) {
            .action-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .btn-add {
                width: 100%;
            }
            .dashboard-table {
                border-radius: 8px;
            }
            .dashboard-table thead {
                display: none; /* Hide header on mobile */
            }
            .dashboard-table tr {
                display: block;
                margin-bottom: 15px;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                background-color: #fff;
                padding: 15px;
            }
            .dashboard-table td {
                display: block;
                border-bottom: 1px solid #e0e0e0;
                position: relative;
                padding-left: 50%;
                text-align: right;
                font-size: 14px;
            }
            .dashboard-table td:before {
                content: attr(data-label);
                position: absolute;
                left: 15px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                text-align: left;
                font-weight: bold;
                color: #555;
            }
            .dashboard-table td:last-child {
                border-bottom: none;
            }
            .action-buttons {
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
<!-- Navbar is assumed to be in 'includes/navbar.php' -->
<?php include 'includes/navbar.php'; ?>

<div class="container">
    <!-- Imports Data Table -->
    <div class="dashboard-container">
        <h2 class="dashboard-title">ບັນທຶກຂໍ້ມູນການນຳເຂົ້າ</h2>

        <div class="action-bar">
            <form method="get" class="search-form">
                <input type="text" name="search" placeholder="ຄົ້ນຫາ (ID, ວັນທີ, ເລກທີ່ໃບສັ່ງຊື້)" value="<?php echo htmlspecialchars($search); ?>" class="search-input">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> ຄົ້ນຫາ</button>
            </form>
            <button type="button" class="btn btn-add" onclick="openAddModal()">
                <i class="fas fa-plus"></i> ເພີ່ມຂໍ້ມູນການນຳເຂົ້າ
            </button>
        </div>

        <table class="dashboard-table">
            <thead>
                <tr>
                    <th style="width: 10%;">ລຳດັບ</th>
                    <th style="width: 30%;">ວັນທີ</th>
                    <th style="width: 35%;">ເລກທີ່ໃບສັ່ງຊື້</th>
                    <th style="width: 25%; text-align: center;">ຈັດການ</th>
                </tr>
            </thead>
            <tbody id="importTableBody"></tbody>
        </table>
    </div>

    <!-- NEW: Import Details Data Table -->
    <div class="dashboard-container">
        <h2 class="dashboard-title">ລາຍລະອຽດການນຳເຂົ້າສິນຄ້າ</h2>
        <div class="action-bar">
            <button type="button" class="btn btn-add" onclick="openAddImportDetailModal()">
                <i class="fas fa-plus"></i> ເພີ່ມລາຍລະອຽດການນຳເຂົ້າ
            </button>
        </div>
        <table class="dashboard-table">
            <thead>
                <tr>
                    <th style="width: 10%;">ລ/ດ</th>
                    <th style="width: 15%;">ລະຫັດນຳເຂົ້າ</th>
                    <th style="width: 30%;">ຊື່ສິນຄ້າ</th>
                    <th style="width: 15%;">ຈຳນວນ</th>
                    <th style="width: 10%;">ລາຄາ</th>
                    <!-- NEW: Action column for Import Details -->
                    <th style="width: 20%; text-align: center;">ຈັດການ</th>
                </tr>
            </thead>
            <tbody id="importDetailTableBody"></tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Main Import Modal -->
<div id="importModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('importModal')">&times;</span>
        <h3 class="modal-title" id="modalTitle">ເພີ່ມຂໍ້ມູນການນຳເຂົ້າ</h3>
        <form id="importForm">
            <input type="hidden" id="importId" name="importId">
            <label for="importDate">ວັນທີ:</label>
            <input type="date" id="importDate" name="importDate" class="gpg-input" required>
            
            <label for="purchaseOrderId">ເລກທີ່ໃບສັ່ງຊື້:</label>
            <select id="purchaseOrderId" name="purchaseOrderId" class="gpg-input" required>
                <option value="">ເລືອກເລກທີ່ໃບສັ່ງຊື້</option>
                <?php foreach ($purchaseOrders as $po): ?>
                    <option value="<?php echo htmlspecialchars($po['po_id']); ?>">
                        <?php echo htmlspecialchars($po['po_id']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <div class="modal-buttons">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> ບັນທຶກ</button>
            </div>
        </form>
    </div>
</div>

<!-- NEW: Add/Edit Import Detail Modal -->
<div id="importDetailModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('importDetailModal')">&times;</span>
        <h3 class="modal-title" id="importDetailModalTitle">ເພີ່ມລາຍລະອຽດການນຳເຂົ້າ</h3>
        <form id="importDetailForm">
            <input type="hidden" id="importDetailId" name="importDetailId">
            
            <label for="parentImportId">ເລກທີ່ນຳເຂົ້າ:</label>
            <select id="parentImportId" name="parentImportId" class="gpg-input" required>
                <option value="">ເລືອກເລກທີ່ນຳເຂົ້າ</option>
                <?php foreach ($imports as $imp): ?>
                    <option value="<?php echo htmlspecialchars($imp['id']); ?>">
                        <?php echo htmlspecialchars($imp['id']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <label for="importDetailProductId">ຊື່ສິນຄ້າ:</label>
            <select id="importDetailProductId" name="importDetailProductId" class="gpg-input" required>
                <option value="">ເລືອກສິນຄ້າ</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?php echo htmlspecialchars($p['p_id']); ?>">
                        <?php echo htmlspecialchars($p['p_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <label for="importDetailQty">ຈຳນວນ:</label>
            <input type="number" id="importDetailQty" name="importDetailQty" class="gpg-input" required>
            
            <label for="importDetailPrice">ລາຄາ:</label>
            <input type="number" step="0.01" id="importDetailPrice" name="importDetailPrice" class="gpg-input" required>
            
            <div class="modal-buttons">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> ບັນທຶກ</button>
            </div>
        </form>
    </div>
</div>

<!-- Custom Confirmation Modal -->
<div id="confirmationModal" class="modal">
    <div class="modal-content">
        <h3 class="modal-title">ຢືນຢັນການລົບ</h3>
        <p>ທ່ານແນ່ໃຈບໍ່ວ່າຈະລົບຂໍ້ມູນນີ້?</p>
        <div class="modal-buttons">
            <button id="confirmDeleteBtn" class="btn btn-delete">ລົບ</button>
            <button id="cancelDeleteBtn" class="btn btn-primary">ຍົກເລີກ</button>
        </div>
    </div>
</div>

<script>
    // --- JAVASCRIPT LOGIC ---

    // Pass PHP data to JavaScript as JSON.
    let imports = <?php echo json_encode($imports, JSON_UNESCAPED_UNICODE); ?>;
    let purchaseOrders = <?php echo json_encode($purchaseOrders, JSON_UNESCAPED_UNICODE); ?>;
    let importDetails = <?php echo json_encode($importDetails, JSON_UNESCAPED_UNICODE); ?>;
    // NEW: Pass product data
    let products = <?php echo json_encode($products, JSON_UNESCAPED_UNICODE); ?>;
    
    let importToDeleteId = null;
    // NEW: Variable to hold the ID of the import detail to be deleted
    let importDetailToDeleteId = null; 

    /**
     * Renders the purchase order and product dropdowns.
     */
    function renderDropdowns() {
        // Purchase Order Dropdown for the main import modal
        const poSelect = document.getElementById('purchaseOrderId');
        poSelect.innerHTML = '<option value="">ເລືອກເລກທີ່ໃບສັ່ງຊື້</option>';
        purchaseOrders.forEach(po => {
            const option = document.createElement('option');
            option.value = po.po_id;
            option.textContent = po.po_id;
            poSelect.appendChild(option);
        });
        
        // Product Dropdown for the import detail modal
        const productSelect = document.getElementById('importDetailProductId');
        productSelect.innerHTML = '<option value="">ເລືອກສິນຄ້າ</option>';
        products.forEach(p => {
            const option = document.createElement('option');
            option.value = p.p_id;
            option.textContent = p.p_name;
            productSelect.appendChild(option);
        });
        
        // Parent Import ID dropdown for the import detail modal
        const parentImportSelect = document.getElementById('parentImportId');
        parentImportSelect.innerHTML = '<option value="">ເລືອກເລກທີ່ນຳເຂົ້າ</option>';
        imports.forEach(imp => {
            const option = document.createElement('option');
            option.value = imp.id;
            option.textContent = imp.id;
            parentImportSelect.appendChild(option);
        });
    }

    /**
     * Renders the Import Details table with the current data.
     */
    function renderImportDetailTable() {
        const tbody = document.getElementById('importDetailTableBody');
        tbody.innerHTML = '';
        if (importDetails.length === 0) {
            const row = tbody.insertRow();
            row.innerHTML = `<td colspan="6" style="text-align: center; padding: 20px;">ບໍ່ມີຂໍ້ມູນລາຍລະອຽດການນຳເຂົ້າ</td>`;
            return;
        }

        importDetails.forEach((detail, i) => {
            const row = tbody.insertRow();
            row.innerHTML = `
                <td data-label="ລ/ດ">${detail.Ipd_id}</td>
                <td data-label="ລະຫັດນຳເຂົ້າ">${detail.Ip_id}</td>
                <td data-label="ຊື່ສິນຄ້າ">${detail.p_name}</td>
                <td data-label="ຈຳນວນ">${detail.qty}</td>
                <td data-label="ລາຄາ">${detail.price}</td>
                <td data-label="ຈັດການ" style="text-align: center;">
                    <div class="action-buttons">
                        <button class="btn-edit" onclick="editImportDetail(${detail.Ipd_id})">ແກ້ໄຂ</button>
                        <button class="btn-delete" onclick="showDeleteDetailConfirmation(${detail.Ipd_id})">ລົບ</button>
                    </div>
                </td>
            `;
        });
    }

    /**
     * Opens the "add" modal for main imports and resets the form.
     */
    function openAddModal() {
        document.getElementById('modalTitle').textContent = 'ເພີ່ມຂໍ້ມູນການນຳເຂົ້າ';
        document.getElementById('importForm').reset();
        document.getElementById('importId').value = '';
        renderDropdowns(); // Ensure all dropdowns are populated
        document.getElementById('importModal').style.display = 'block';
    }
    
    /**
     * Opens the "add" modal for import details and resets the form.
     */
    function openAddImportDetailModal() {
        document.getElementById('importDetailModalTitle').textContent = 'ເພີ່ມລາຍລະອຽດການນຳເຂົ້າ';
        document.getElementById('importDetailForm').reset();
        document.getElementById('importDetailId').value = '';
        renderDropdowns(); // Ensure all dropdowns are populated
        document.getElementById('importDetailModal').style.display = 'block';
    }

    /**
     * Opens the "edit" modal and populates the form with import data.
     * @param {number} id The ID of the import record to edit.
     */
    function editImport(id) {
        const imp = imports.find(x => x.id == id);
        if (!imp) return;

        document.getElementById('modalTitle').textContent = 'ແກ້ໄຂຂໍ້ມູນການນຳເຂົ້າ';
        document.getElementById('importId').value = imp.id;
        document.getElementById('importDate').value = imp.date ? imp.date.split(' ')[0] : '';
        
        renderDropdowns(); // Re-render to ensure fresh options
        document.getElementById('purchaseOrderId').value = imp.po_id;

        document.getElementById('importModal').style.display = 'block';
    }

    /**
     * NEW: Opens the "edit" modal for import details and populates the form.
     * @param {number} id The ID of the import detail record to edit.
     */
    function editImportDetail(id) {
        const detail = importDetails.find(x => x.Ipd_id == id);
        if (!detail) return;

        document.getElementById('importDetailModalTitle').textContent = 'ແກ້ໄຂລາຍລະອຽດການນຳເຂົ້າ';
        document.getElementById('importDetailId').value = detail.Ipd_id;
        
        // Select the correct product and populate other fields
        renderDropdowns(); // Re-render all dropdowns
        document.getElementById('parentImportId').value = detail.Ip_id;
        document.getElementById('importDetailProductId').value = detail.p_id;
        document.getElementById('importDetailQty').value = detail.qty;
        document.getElementById('importDetailPrice').value = detail.price;

        document.getElementById('importDetailModal').style.display = 'block';
    }
    
    /**
     * Shows a custom confirmation modal before deleting a main import.
     * @param {number} id The ID of the import record to delete.
     */
    function showDeleteConfirmation(id) {
        importToDeleteId = id;
        importDetailToDeleteId = null; // Ensure detail ID is cleared
        document.getElementById('confirmationModal').style.display = 'block';
    }

    /**
     * NEW: Shows a custom confirmation modal before deleting an import detail.
     * @param {number} id The ID of the import detail record to delete.
     */
    function showDeleteDetailConfirmation(id) {
        importDetailToDeleteId = id;
        importToDeleteId = null; // Ensure main import ID is cleared
        document.getElementById('confirmationModal').style.display = 'block';
    }

    /**
     * Sends a delete request for the main import after confirmation.
     * NOTE: This assumes an `import_api.php` exists to handle the request.
     */
    function deleteConfirmed() {
        const id = importToDeleteId;
        const detailId = importDetailToDeleteId;

        // Determine which deletion action to take
        let actionParam;
        let formData = new FormData();
        
        if (id) {
            actionParam = 'delete_import';
            formData.append('id', id);
        } else if (detailId) {
            actionParam = 'delete_import_detail';
            formData.append('id', detailId);
        } else {
            closeModal('confirmationModal');
            return;
        }

        formData.append('action', actionParam);

        fetch('import_api.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                // Update the correct data array based on the deletion type
                if (res.imports) {
                    imports = res.imports;
                    renderTable();
                } else if (res.importDetails) {
                    importDetails = res.importDetails;
                    renderImportDetailTable();
                }
                
                // Re-render other tables as well to keep data consistent
                renderDropdowns();
                alert('ລົບສຳເລັດແລ້ວ');
            } else {
                alert(res.error || 'Error');
            }
            closeModal('confirmationModal');
        })
        .catch(() => {
            alert('Network error');
            closeModal('confirmationModal');
        });
    }

    /**
     * Closes a modal.
     * @param {string} modalId The ID of the modal element to close.
     */
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
        if (modalId === 'confirmationModal') {
            importToDeleteId = null;
            importDetailToDeleteId = null;
        }
    }

    /**
     * Renders the main import table with the current data.
     */
    function renderTable() {
        const tbody = document.getElementById('importTableBody');
        tbody.innerHTML = '';
        if (imports.length === 0) {
            const row = tbody.insertRow();
            row.innerHTML = `<td colspan="4" style="text-align: center; padding: 20px;">ບໍ່ມີຂໍ້ມູນການນຳເຂົ້າ</td>`;
            return;
        }

        imports.forEach((imp, i) => {
            const row = tbody.insertRow();
            row.innerHTML = `
                <td data-label="ລຳດັບ">${imp.id}</td>
                <td data-label="ວັນທີ">${new Date(imp.date).toLocaleDateString('lo-LA')}</td>
                <td data-label="ເລກທີ່ໃບສັ່ງຊື້">${imp.po_id}</td>
                <td data-label="ຈັດການ" style="text-align: center;">
                    <div class="action-buttons">
                        <button class="btn-edit" onclick="editImport(${imp.id})">ແກ້ໄຂ</button>
                        <button class="btn-delete" onclick="showDeleteConfirmation(${imp.id})">ລົບ</button>
                    </div>
                </td>
            `;
        });
    }

    // Event listener for the main import form submission (Add/Edit).
    document.getElementById('importForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        // Use a more specific action for the combined API file.
        formData.append('action', this.importId.value ? 'edit_import' : 'add_import');

        fetch('import_api.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                imports = res.imports;
                renderTable();
                alert('ບັນທຶກສຳເລັດແລ້ວ');
            } else {
                alert(res.error || 'Error');
            }
            closeModal('importModal');
        })
        .catch(() => alert('Network error'));
    });

    // NEW: Event listener for the import detail form submission (Add/Edit).
    document.getElementById('importDetailForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        // Use a more specific action for the combined API file.
        formData.append('action', this.importDetailId.value ? 'edit_import_detail' : 'add_import_detail');

        fetch('import_api.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                importDetails = res.importDetails;
                renderImportDetailTable();
                alert('ບັນທຶກລາຍລະອຽດສຳເລັດແລ້ວ');
            } else {
                alert(res.error || 'Error');
            }
            closeModal('importDetailModal');
        })
        .catch(() => alert('Network error'));
    });

    // Event listeners to close modals when clicking outside of them.
    window.onclick = e => {
        if (e.target === document.getElementById('importModal')) closeModal('importModal');
        if (e.target === document.getElementById('importDetailModal')) closeModal('importDetailModal');
        if (e.target === document.getElementById('confirmationModal')) closeModal('confirmationModal');
    };

    // Initial setup on page load.
    document.addEventListener('DOMContentLoaded', () => {
        renderTable();
        renderDropdowns();
        renderImportDetailTable();
    });

    // Event listeners for the custom confirmation modal buttons.
    document.getElementById('confirmDeleteBtn').addEventListener('click', deleteConfirmed);
    document.getElementById('cancelDeleteBtn').addEventListener('click', () => closeModal('confirmationModal'));
</script>

<?php
// Close the database connection at the end of the script.
$conn->close();
?>
