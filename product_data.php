<?php
// Includes for authentication and database connection.
// These files are assumed to exist in the 'includes' and 'db' directories.
include 'includes/auth.php';
include 'db/connection.php';

// Get search term from URL, if it exists.
$search = isset($_GET['search']) ? $_GET['search'] : '';

// --- PHP LOGIC TO FETCH DATA ---

// Fetch data for the product type dropdown.
$stmt_types = $conn->prepare("SELECT pt_id, pt_name FROM ProductType ORDER BY pt_name ASC");
$stmt_types->execute();
$result_types = $stmt_types->get_result();
$productTypes = $result_types->fetch_all(MYSQLI_ASSOC);
$stmt_types->close();

// Fetch data for the product brand dropdown.
$stmt_brands = $conn->prepare("SELECT pb_id, pb_name FROM ProductBrand ORDER BY pb_name ASC");
$stmt_brands->execute();
$result_brands = $stmt_brands->get_result();
$productBrands = $result_brands->fetch_all(MYSQLI_ASSOC);
$stmt_brands->close();

// Fetch data for the product shelf dropdown.
$stmt_shelves = $conn->prepare("SELECT pslf_id, pslf_location FROM ProductShelf ORDER BY pslf_location ASC");
$stmt_shelves->execute();
$result_shelves = $stmt_shelves->get_result();
$productShelves = $result_shelves->fetch_all(MYSQLI_ASSOC);
$stmt_shelves->close();

// NEW: Fetch data for the product unit dropdown.
$stmt_units = $conn->prepare("SELECT punit_id, punit_name FROM ProductUnit ORDER BY punit_name ASC");
$stmt_units->execute();
$result_units = $stmt_units->get_result();
$productUnits = $result_units->fetch_all(MYSQLI_ASSOC);
$stmt_units->close();


// MODIFIED: Fetch products from the database, performing a search across multiple fields.
// This query joins all four related tables (ProductType, ProductBrand, ProductShelf, ProductUnit)
// and includes their respective IDs for use in the modals.
$stmt = $conn->prepare("SELECT
    p.p_id AS id,
    p.p_name AS name,
    p.qty,
    p.price,
    p.image_path,
    p.pt_id,
    p.pb_id,
    p.pslf_id,
    p.punit_id,
    pt.pt_name AS category,
    pb.pb_name AS brand,
    pslf.pslf_location AS shelf,
    punit.punit_name AS unit
FROM
    Product AS p
LEFT JOIN
    ProductType AS pt ON p.pt_id = pt.pt_id
LEFT JOIN
    ProductBrand AS pb ON p.pb_id = pb.pb_id
LEFT JOIN
    ProductShelf AS pslf ON p.pslf_id = pslf.pslf_id
LEFT JOIN
    ProductUnit AS punit ON p.punit_id = punit.punit_id
WHERE
    p.p_name LIKE ? OR pt.pt_name LIKE ? OR pb.pb_name LIKE ? OR pslf.pslf_location LIKE ? OR punit.punit_name LIKE ?
ORDER BY
    p.p_id ASC");

// Create a like parameter for the prepared statement.
$like = '%' . $search . '%';
$stmt->bind_param("sssss", $like, $like, $like, $like, $like);
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="lo">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ບັນທຶກຂໍ້ມູນສິນຄ້າ - ລະບົບຈັດການຮ້ານGPG</title>
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
            width: 200px;
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

        .product-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #e0e0e0;
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
            background-color: #23af46ff;
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

        /* NEW: Confirmation Modal Styles */
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
    <div class="dashboard-container">
        <h2 class="dashboard-title">ບັນທຶກຂໍ້ມູນສິນຄ້າ</h2>

        <div class="action-bar">
            <form method="get" class="search-form">
                <input type="text" name="search" placeholder="ຄົ້ນຫາ (ສິນຄ້າ, ໝວດ, ຍິ່ຫໍ້)" value="<?php echo htmlspecialchars($search); ?>" class="search-input">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> ຄົ້ນຫາ</button>
            </form>
            <button type="button" class="btn btn-add" onclick="openAddModal()">
                <i class="fas fa-plus"></i> ເພີ່ມຂໍ້ມູນສິນຄ້າ
            </button>
        </div>

        <table class="dashboard-table">
            <thead>
                <tr>
                    <th style="width: 5%;">ລຳດັບ</th>
                    <th style="width: 10%;">ຮູບພາບ</th>
                    <th style="width: 20%;">ຊື່ສິນຄ້າ</th>
                    <th style="width: 15%;">ປະເພດສິນຄ້າ</th>
                    <th style="width: 15%;">ຍີ່ຫໍ້</th>
                    <th style="width: 10%;">ຖານວາງ</th>
                    <th style="width: 10%;">ຈຳນວນ</th>
                    <th style="width: 15%;">ລາຄາຕໍ່ໜ່ວຍ</th>
                    <th style="width: 15%; text-align: center;">ຈັດການ</th>
                </tr>
            </thead>
            <tbody id="productTableBody"></tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="productModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('productModal')">&times;</span>
        <h3 class="modal-title" id="modalTitle">ເພີ່ມຂໍ້ມູນສິນຄ້າ</h3>
        <form id="productForm" enctype="multipart/form-data">
            <input type="hidden" id="productId" name="productId">
            <label for="productImage">ຮູບພາບ:</label>
            <input type="file" id="productImage" name="productImage" class="gpg-input">
            <label for="productName">ຊື່ສິນຄ້າ:</label>
            <input type="text" id="productName" name="productName" class="gpg-input" required>

            <!-- Dropdown for Product Type -->
            <label for="productCategory">ປະເພດສິນຄ້າ:</label>
            <select id="productCategory" name="productCategory" class="gpg-input" required></select>

            <!-- Dropdown for Product Brand -->
            <label for="productBrand">ຍີ່ຫໍ້:</label>
            <select id="productBrand" name="productBrand" class="gpg-input" required></select>

            <!-- Dropdown for Product Shelf -->
            <label for="productShelf">ຖານວາງ:</label>
            <select id="productShelf" name="productShelf" class="gpg-input" required></select>

            <!-- NEW: Dropdown for Product Unit -->
            <label for="productUnit">ໜ່ວຍ:</label>
            <select id="productUnit" name="productUnit" class="gpg-input" required></select>

            <label for="productQty">ຈຳນວນ:</label>
            <input type="number" id="productQty" name="productQty" class="gpg-input" required>
            <label for="productPrice">ລາຄາຕໍ່ໜ່ວຍ:</label>
            <input type="number" id="productPrice" name="productPrice" class="gpg-input" required>
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
        <p>ທ່ານແນ່ໃຈບໍ່ວ່າຈະລົບສິນຄ້ານີ້?</p>
        <div class="modal-buttons">
            <button id="confirmDeleteBtn" class="btn btn-delete">ລົບ</button>
            <button id="cancelDeleteBtn" class="btn btn-primary">ຍົກເລີກ</button>
        </div>
    </div>
</div>

<script>
    // --- JAVASCRIPT LOGIC ---

    // Pass PHP data to JavaScript as JSON.
    let products = <?php echo json_encode($products, JSON_UNESCAPED_UNICODE); ?>;
    let productTypes = <?php echo json_encode($productTypes, JSON_UNESCAPED_UNICODE); ?>;
    let productBrands = <?php echo json_encode($productBrands, JSON_UNESCAPED_UNICODE); ?>;
    let productShelves = <?php echo json_encode($productShelves, JSON_UNESCAPED_UNICODE); ?>;
    let productUnits = <?php echo json_encode($productUnits, JSON_UNESCAPED_UNICODE); ?>;
    let productToDeleteId = null;

    /**
     * Renders all product dropdowns (type, brand, shelf, unit).
     */
    function renderDropdowns() {
        // Render Product Types
        const categorySelect = document.getElementById('productCategory');
        categorySelect.innerHTML = '<option value="">ເລືອກປະເພດສິນຄ້າ</option>';
        productTypes.forEach(type => {
            const option = document.createElement('option');
            option.value = type.pt_id;
            option.textContent = type.pt_name;
            categorySelect.appendChild(option);
        });

        // Render Product Brands
        const brandSelect = document.getElementById('productBrand');
        brandSelect.innerHTML = '<option value="">ເລືອກຍີ່ຫໍ້</option>';
        productBrands.forEach(brand => {
            const option = document.createElement('option');
            option.value = brand.pb_id;
            option.textContent = brand.pb_name;
            brandSelect.appendChild(option);
        });

        // Render Product Shelves
        const shelfSelect = document.getElementById('productShelf');
        shelfSelect.innerHTML = '<option value="">ເລືອກຖານວາງ</option>';
        productShelves.forEach(shelf => {
            const option = document.createElement('option');
            option.value = shelf.pslf_id;
            option.textContent = shelf.pslf_location;
            shelfSelect.appendChild(option);
        });

        // NEW: Render Product Units
        const unitSelect = document.getElementById('productUnit');
        unitSelect.innerHTML = '<option value="">ເລືອກໜ່ວຍ</option>';
        productUnits.forEach(unit => {
            const option = document.createElement('option');
            option.value = unit.punit_id;
            option.textContent = unit.punit_name;
            unitSelect.appendChild(option);
        });
    }

    /**
     * Opens the "add" modal and resets the form.
     */
    function openAddModal() {
        document.getElementById('modalTitle').textContent = 'ເພີ່ມຂໍ້ມູນສິນຄ້າ';
        document.getElementById('productForm').reset();
        document.getElementById('productId').value = '';
        renderDropdowns(); // Ensure all dropdowns are populated
        document.getElementById('productModal').style.display = 'block';
    }

    /**
     * Opens the "edit" modal and populates the form with product data.
     * @param {number} id The ID of the product to edit.
     */
    function editProduct(id) {
        const p = products.find(x => x.id === id);
        if (!p) return;

        document.getElementById('modalTitle').textContent = 'ແກ້ໄຂຂໍ້ມູນສິນຄ້າ';
        document.getElementById('productId').value = p.id;
        document.getElementById('productName').value = p.name;
        document.getElementById('productQty').value = p.qty;
        document.getElementById('productPrice').value = p.price;

        // Select the correct option in the dropdowns using the foreign key IDs.
        document.getElementById('productCategory').value = p.pt_id;
        document.getElementById('productBrand').value = p.pb_id;
        document.getElementById('productShelf').value = p.pslf_id;
        document.getElementById('productUnit').value = p.punit_id;

        document.getElementById('productModal').style.display = 'block';
    }

    /**
     * Shows a custom confirmation modal before deleting.
     * @param {number} id The ID of the product to delete.
     */
    function showDeleteConfirmation(id) {
        productToDeleteId = id;
        document.getElementById('confirmationModal').style.display = 'block';
    }

    /**
     * Sends a delete request to the API after confirmation.
     */
    function deleteConfirmed() {
        const id = productToDeleteId;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);

        fetch('product_api.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                products = res.products;
                renderTable();
                alert('ລົບສຳເລັດແລ້ວ'); // Keeping the original alert for success message
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
            productToDeleteId = null;
        }
    }

    /**
     * Renders the product table with the current data.
     */
    function renderTable() {
        const tbody = document.getElementById('productTableBody');
        tbody.innerHTML = '';
        if (products.length === 0) {
            const row = tbody.insertRow();
            row.innerHTML = `<td colspan="9" style="text-align: center; padding: 20px;">ບໍ່ມີຂໍ້ມູນສິນຄ້າ</td>`;
            return;
        }

        products.forEach((p, i) => {
            const imgTag = p.image_path ? `<img src="${p.image_path}" alt="${p.name}" class="product-img">` : '<span>ບໍ່ມີຮູບ</span>';
            const row = tbody.insertRow();
            row.innerHTML = `
                <td data-label="ລຳດັບ">${p.id}</td>
                <td data-label="ຮູບພາບ" style="text-align: center;">${imgTag}</td>
                <td data-label="ຊື່ສິນຄ້າ">${p.name}</td>
                <td data-label="ປະເພດສິນຄ້າ">${p.category}</td>
                <td data-label="ຍີ່ຫໍ້">${p.brand}</td>
                <td data-label="ຖານວາງ">${p.shelf}</td>
                <td data-label="ຈຳນວນ">${p.qty} ${p.unit}</td>
                <td data-label="ລາຄາຕໍ່ໜ່ວຍ">${p.price.toLocaleString()} ກິບ</td>
                <td data-label="ຈັດການ" style="text-align: center;">
                    <div class="action-buttons">
                        <button class="btn-edit" onclick="editProduct(${p.id})">ແກ້ໄຂ</button>
                        <button class="btn-delete" onclick="showDeleteConfirmation(${p.id})">ລົບ</button>
                    </div>
                </td>
            `;
        });
    }

    // Event listener for the form submission (Add/Edit).
    document.getElementById('productForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('action', this.productId.value ? 'edit' : 'add');

        fetch('product_api.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                products = res.products;
                renderTable();
                closeModal('productModal');
                alert('ບັນທຶກສຳເລັດແລ້ວ');
            } else {
                alert(res.error || 'Error');
            }
        })
        .catch(() => alert('Network error'));
    });

    // Event listeners to close modals when clicking outside of them.
    window.onclick = e => {
        if (e.target === document.getElementById('productModal')) closeModal('productModal');
        if (e.target === document.getElementById('confirmationModal')) closeModal('confirmationModal');
    };

    // Initial setup on page load.
    document.addEventListener('DOMContentLoaded', () => {
        renderDropdowns();
        renderTable();
    });

    // Event listeners for the custom confirmation modal buttons.
    document.getElementById('confirmDeleteBtn').addEventListener('click', deleteConfirmed);
    document.getElementById('cancelDeleteBtn').addEventListener('click', () => closeModal('confirmationModal'));
</script>
</body>
</html>
