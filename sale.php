<?php   
// Note: This is a static PHP file for demonstration.   
// The data is now handled by JavaScript for a better user experience.   

// Simplified initial data fetch for customers and products   
require_once 'db/connection.php';   
$customers = $conn->query("SELECT c_id, c_name FROM Customer ORDER BY c_name ASC")->fetch_all(MYSQLI_ASSOC);   
$products_query = "SELECT p.p_id, p.p_name, p.price, p.qty, pt.pt_name AS type_name, pu.punit_name AS unit_name FROM Product p   
                         JOIN ProductType pt ON p.pt_id = pt.pt_id   
                         JOIN ProductUnit pu ON p.punit_id = pu.punit_id   
                         ORDER BY p.p_name ASC";   
$products_stmt = $conn->prepare($products_query);   
$products_stmt->execute();   
$products = $products_stmt->get_result()->fetch_all(MYSQLI_ASSOC);   
$products_stmt->close();   
$conn->close();   
?>   
<!DOCTYPE html>   
<html lang="lo">   
<head>   
    <meta charset="UTF-8">   
    <meta name="viewport" content="width=device-width, initial-scale=1.0">   
    <title>ຈັດການຂາຍສິນຄ້າ</title>   
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;700&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="assets/css/style.css">   
    <style>   
        .dashboard-add-btn { background-color: #4CAF50; }   
        .dashboard-edit-btn { background-color: #2196F3; }   
        .dashboard-delete-btn { background-color: #f44336; }   
        .sale-item {   
            margin-top: 10px;   
            padding: 10px;   
            border: 1px solid #ccc;   
            border-radius: 6px;   
            background-color: #f9f9f9;   
        }   
        .sale-item-remove-btn {   
            background-color: #f44336;   
            color: white;   
            border: none;   
            padding: 5px 10px;   
            cursor: pointer;   
            border-radius: 4px;   
            float: right;   
        }   
        .modal-body {   
            max-height: 70vh;   
            overflow-y: auto;   
        }   
        .modal-footer {   
            padding: 15px;   
            border-top: 1px solid #ddd;   
            text-align: right;   
        }   
        .table-nested-row {   
            background-color: #f2f2f2;   
        }   
        .expand-toggle {   
            cursor: pointer;   
            font-weight: bold;   
            font-size: 1.2em;   
        }   
        .hidden-details {   
            display: none;   
        }   
    </style>   
</head>   
<body>   
<?php include 'includes/navbar.php'; ?>   

<div class="container">   
    <div class="dashboard-container">   
        <h2 class="dashboard-title">ຈັດການການຂາຍ</h2>   
        <form id="searchForm" style="display:flex;gap:12px;margin-bottom:18px;">   
            <input type="text" name="search" placeholder="ຄົ້ນຫາ (ບິນ, ລູກຄ້າ)" value="" style="flex:1;padding:10px;border-radius:6px;border:1px solid #ccc;">   
            <button type="submit" class="dashboard-add-btn">ຄົ້ນຫາ</button>   
        </form>   
        <div style="display:flex; gap:12px; margin-bottom: 18px;">   
            <button type="button" class="dashboard-add-btn" onclick="openAddModal()">ເພີ່ມການຂາຍໃໝ່</button>   
            <button type="button" class="dashboard-add-btn" id="printInvoiceBtn" disabled onclick="printInvoice()">ພິມໃບບິນ</button>   
        </div>   

        <table class="dashboard-table">   
            <thead>   
                <tr>   
                    <th></th> <!-- For radio button -->   
                    <th>ເລກທີຂາຍ</th>   
                    <th>ວັນທີ</th>   
                    <th>ຊື່ລູກຄ້າ</th>   
                    <th>ລາຍລະອຽດສິນຄ້າ</th>   
                    <th>ລາຄາລວມ</th>   
                    <th>ຈັດການ</th>   
                </tr>   
            </thead>   
            <tbody id="saleTableBody"></tbody>   
            <tfoot id="saleTableFooter"></tfoot>
        </table>   
    </div>   
</div>   

<!-- Add/Edit Sale Modal -->   
<div id="saleModal" class="modal">   
    <div class="modal-content">   
        <span class="close" onclick="closeModal()">&times;</span>   
        <h3 class="modal-title" id="modalTitle"></h3>   
        <form id="saleForm">   
            <div class="modal-body">   
                <input type="hidden" name="s_id" id="s_id">   
                <label for="date">ວັນທີ:</label>   
                <input type="date" id="date" name="date" class="gpg-input" required>   
                <label for="customer">ຊື່ລູກຄ້າ:</label>   
                <select id="customer" name="customer_id" class="gpg-input" required>   
                    <?php foreach($customers as $c){echo "<option value='{$c['c_id']}'>{$c['c_name']}</option>";} ?>   
                </select>   

                <h4>ສິນຄ້າທີ່ຂາຍ:</h4>   
                <div id="saleItemsContainer">   
                    <!-- Product items will be added here dynamically -->   
                </div>   
                   
                <button type="button" class="dashboard-add-btn" style="margin-top: 10px;" onclick="addSaleItem()">ເພີ່ມສິນຄ້າ</button>   

                <div style="margin-top: 20px;">   
                    <strong>ລາຄາລວມທັງໝົດ: <span id="grandTotal">0.00</span> ກີບ</strong>   
                </div>   
            </div>   
               
            <div class="modal-footer">   
                <button type="submit" class="dashboard-add-btn">ບັນທຶກ</button>   
            </div>   
        </form>   
    </div>   
</div>   

<script>   
    let customers = <?php echo json_encode($customers, JSON_UNESCAPED_UNICODE); ?>;   
    let products = <?php echo json_encode($products, JSON_UNESCAPED_UNICODE); ?>;   
    let sales = [];   
    let selectedSaleId = null;   

    async function fetchSales(searchTerm = '') {   
        try {   
            const response = await fetch(`sale_api.php?search=${encodeURIComponent(searchTerm)}`);   
            const result = await response.json();   
            if (result.success) {   
                sales = result.sales;   
                renderTable();   
            } else {   
                alert('Error fetching sales: ' + result.error);   
            }   
        } catch (error) {   
            alert('An error occurred: ' + error.message);   
        }   
    }   

    async function handleSaleAction(action, data) {   
        try {   
            const response = await fetch('sale_api.php', {   
                method: 'POST',   
                headers: { 'Content-Type': 'application/json' },   
                body: JSON.stringify({ action, ...data })   
            });   
            const result = await response.json();   
            if (result.success) {   
                sales = result.sales;   
                renderTable();   
                closeModal();   
            } else {   
                alert('Error: ' + result.error);   
            }   
        } catch (error) {   
            alert('An error occurred: ' + error.message);   
        }   
    }   

    /**
     * Calculates the grand total of all sales in the sales array.
     * @returns {number} The total price of all sales.
     */
    function grandTotal() {
        if (!sales || sales.length === 0) {
            return 0;
        }
        
        let total = 0;
        sales.forEach(sale => {
            if (sale.items && sale.items.length > 0) {
                const saleTotal = sale.items.reduce((sum, item) => sum + parseFloat(item.total_price), 0);
                total += saleTotal;
            }
        });
        return total;
    }

    function renderTable() {   
        const tbody = document.getElementById('saleTableBody');   
        const tfoot = document.getElementById('saleTableFooter');   
        tbody.innerHTML = '';   
        tfoot.innerHTML = '';
        
        if (!sales || sales.length === 0) {   
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">ບໍ່ພົບຂໍ້ມູນ</td></tr>';   
            return;   
        }   

        sales.forEach(s => {   
            // Calculate the total price for the current sale item
            const saleGrandTotal = s.items.reduce((sum, item) => sum + parseFloat(item.total_price), 0);   
            const trMain = tbody.insertRow();   
            trMain.innerHTML = `   
                <td><input type="radio" name="selectSale" value="${s.s_id}" onchange="selectSale(${s.s_id})"></td>   
                <td>${s.s_id}</td>   
                <td>${new Date(s.date).toLocaleDateString('lo-LA')}</td>   
                <td>${s.c_name}</td>   
                <td>   
                    <span class="expand-toggle" onclick="toggleDetails(${s.s_id})">&#9658;</span>   
                    (${s.items.length} ລາຍການ)   
                </td>   
                <td>${parseFloat(saleGrandTotal).toLocaleString()} ກີບ</td>   
                <td>   
                    <button class="dashboard-edit-btn" onclick='openEditModal(${JSON.stringify(s)})'>ແກ້ໄຂ</button>   
                    <button class="dashboard-delete-btn" onclick="deleteSale(${s.s_id})">ລົບ</button>   
                </td>   
            `;   

            // Nested rows for product details   
            const trDetails = tbody.insertRow();   
            trDetails.className = `table-nested-row hidden-details sale-details-${s.s_id}`;   
            const tdDetails = trDetails.insertCell(0);   
            tdDetails.colSpan = 7;   
            tdDetails.innerHTML = `   
                <table style="width:100%; border-left: 2px solid #2196F3; margin-left: 20px;">   
                    <thead>   
                        <tr>   
                            <th>ຊື່ສິນຄ້າ</th>   
                            <th>ປະເພດ</th>   
                            <th>ໜ່ວຍ</th>   
                            <th>ຈຳນວນ</th>   
                            <th>ລາຄາຕໍ່ໜ່ວຍ</th>   
                            <th>ລາຄາລວມ</th>   
                        </tr>   
                    </thead>   
                    <tbody>   
                        ${s.items.map(item => `   
                            <tr>   
                                <td>${item.p_name}</td>   
                                <td>${item.type}</td>   
                                <td>${item.unit}</td>   
                                <td>${item.qty}</td>   
                                <td>${parseFloat(item.unit_price).toLocaleString()} ກີບ</td>   
                                <td>${parseFloat(item.total_price).toLocaleString()} ກີບ</td>   
                            </tr>   
                        `).join('')}   
                    </tbody>   
                </table>   
            `;   
        });   

        // Add a total row in the table footer
        const totalRow = tfoot.insertRow();
        const total = grandTotal();
        totalRow.innerHTML = `
            <td colspan="5"></td>
            <td colspan="2" style="text-align:right;"><b>ຍອດລວມທັງໝົດ: ${total.toLocaleString()} ກີບ</b></td>
        `;
    }   
       
    function toggleDetails(s_id) {   
        const detailsRow = document.querySelector(`.sale-details-${s_id}`);   
        const toggleSpan = detailsRow.previousElementSibling.querySelector('.expand-toggle');   
        if (detailsRow) {   
            detailsRow.classList.toggle('hidden-details');   
            if (detailsRow.classList.contains('hidden-details')) {   
                toggleSpan.innerHTML = '&#9658;'; // Right arrow   
            } else {   
                toggleSpan.innerHTML = '&#9660;'; // Down arrow   
            }   
        }   
    }   

    function selectSale(s_id) {   
        selectedSaleId = s_id;   
        document.getElementById('printInvoiceBtn').disabled = false;   
    }   

    function printInvoice() {   
        if (selectedSaleId) {   
            window.open(`invoice.php?id=${selectedSaleId}`, '_blank');   
        }   
    }   

    function closeModal() {   
        document.getElementById('saleModal').style.display = 'none';   
        document.getElementById('saleForm').reset();   
        document.getElementById('saleItemsContainer').innerHTML = ''; // Clear items   
        document.getElementById('grandTotal').textContent = '0.00';   
    }   

    function addSaleItem(item = null) {   
        const container = document.getElementById('saleItemsContainer');   
        const itemDiv = document.createElement('div');   
        itemDiv.className = 'sale-item';   
        itemDiv.innerHTML = `   
            <button type="button" class="sale-item-remove-btn" onclick="removeSaleItem(this)">&times;</button>   
            <label>ຊື່ສິນຄ້າ:</label>   
            <select name="p_id" class="gpg-input" required onchange="updateItemPrice(this)">   
                <option value="">-- ເລືອກສິນຄ້າ --</option>   
                ${products.map(p => `<option value="${p.p_id}" data-price="${p.price}" data-stock="${p.qty}" ${item && item.p_id == p.p_id ? 'selected' : ''}>${p.p_name} (Stock: ${p.qty}) - ${p.price} ກີບ</option>`).join('')}   
            </select>   
            <label>ຈຳນວນ:</label>   
            <input type="number" name="qty" class="gpg-input" min="1" required value="${item ? item.qty : 1}" oninput="calculateItemTotal(this)">   
            <label>ລາຄາຕໍ່ໜ່ວຍ:</label>   
            <input type="number" name="unit_price" class="gpg-input" min="0" step="0.01" required value="${item ? item.unit_price : ''}" oninput="calculateItemTotal(this)">   
            <label>ລາຄາລວມ:</label>   
            <input type="number" name="total_price" class="gpg-input" readonly value="${item ? item.total_price : ''}">   
        `;   
        container.appendChild(itemDiv);   
        if (!item) {   
            calculateGrandTotal();   
        }   
    }   

    function removeSaleItem(button) {   
        button.closest('.sale-item').remove();   
        calculateGrandTotal();   
    }   

    function calculateItemTotal(input) {   
        const itemDiv = input.closest('.sale-item');   
        const qty = parseFloat(itemDiv.querySelector('input[name="qty"]').value) || 0;   
        const unitPrice = parseFloat(itemDiv.querySelector('input[name="unit_price"]').value) || 0;   
        const totalPrice = qty * unitPrice;   
        itemDiv.querySelector('input[name="total_price"]').value = totalPrice.toFixed(2);   
        calculateGrandTotal();   
    }   
       
    function updateItemPrice(select) {   
        const itemDiv = select.closest('.sale-item');   
        const selectedOption = select.options[select.selectedIndex];   
        if (selectedOption.value) {   
            const price = parseFloat(selectedOption.getAttribute('data-price'));   
            itemDiv.querySelector('input[name="unit_price"]').value = price;   
        } else {   
            itemDiv.querySelector('input[name="unit_price"]').value = '';   
        }   
        calculateItemTotal(itemDiv.querySelector('input[name="qty"]'));   
    }   

    function calculateGrandTotal() {   
        const itemTotals = document.querySelectorAll('#saleItemsContainer input[name="total_price"]');   
        let grandTotal = 0;   
        itemTotals.forEach(input => {   
            grandTotal += parseFloat(input.value) || 0;   
        });   
        document.getElementById('grandTotal').textContent = grandTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });   
    }   

    function openAddModal() {   
        document.getElementById('modalTitle').textContent = 'ເພີ່ມການຂາຍໃໝ່';   
        document.getElementById('s_id').value = '';   
        document.getElementById('date').valueAsDate = new Date();   
        addSaleItem(); // Start with one empty item   
        document.getElementById('saleModal').style.display = 'block';   
    }   

    function openEditModal(sale) {   
        document.getElementById('modalTitle').textContent = 'ແກ້ໄຂການຂາຍ';   
        document.getElementById('s_id').value = sale.s_id;   
        document.getElementById('date').value = sale.date.split(' ')[0];   
        document.getElementById('customer').value = sale.c_id;   
           
        // Populate items   
        document.getElementById('saleItemsContainer').innerHTML = '';   
        sale.items.forEach(item => addSaleItem(item));   
        calculateGrandTotal();   

        document.getElementById('saleModal').style.display = 'block';   
    }   

    function deleteSale(s_id) {   
        if (!confirm('ທ່ານແນ່ໃຈບໍ່ວ່າຈະລົບລາຍການຂາຍນີ້?')) return;   
        handleSaleAction('delete', { id: s_id });   
    }   
       
    document.getElementById('saleForm').addEventListener('submit', function(e) {   
        e.preventDefault();   
           
        const saleItems = [];   
        const itemElements = document.querySelectorAll('#saleItemsContainer .sale-item');   
        let hasError = false;   

        itemElements.forEach(itemDiv => {   
            const p_id = itemDiv.querySelector('select[name="p_id"]').value;   
            const qty = itemDiv.querySelector('input[name="qty"]').value;   
            const unit_price = itemDiv.querySelector('input[name="unit_price"]').value;   
               
            if (!p_id || !qty || !unit_price) {   
                alert('ກະລຸນາປ້ອນຂໍ້ມູນສິນຄ້າໃຫ້ຄົບຖ້ວນ.');   
                hasError = true;   
                return;   
            }   
               
            saleItems.push({   
                p_id: parseInt(p_id),   
                qty: parseInt(qty),   
                unit_price: parseFloat(unit_price),   
                total_price: parseFloat(itemDiv.querySelector('input[name="total_price"]').value)   
            });   
        });   

        if (hasError) return;   

        const s_id = document.getElementById('s_id').value;   
        const c_id = document.getElementById('customer').value;   
        const date = document.getElementById('date').value;   

        if (s_id) {   
            handleSaleAction('edit', { s_id: parseInt(s_id), date, c_id: parseInt(c_id), items: saleItems });   
        } else {   
            handleSaleAction('add', { date, c_id: parseInt(c_id), items: saleItems });   
        }   
    });   

    document.getElementById('searchForm').addEventListener('submit', function(e) {   
        e.preventDefault();   
        const searchTerm = this.querySelector('input[name="search"]').value;   
        fetchSales(searchTerm);   
    });   

    window.onclick = e => {   
        if (e.target === document.getElementById('saleModal')) closeModal();   
    };   
       
    document.addEventListener('DOMContentLoaded', () => {   
        fetchSales();   
    });   
</script>   
</body>   
</html>
