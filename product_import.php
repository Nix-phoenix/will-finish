<?php
include 'includes/auth.php';
include 'db/connection.php';

// Check if the user is logged in and has the 'admin' role
requireAdmin();



$search = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch dropdown data
$purchaseOrders = $conn->query("SELECT po_id FROM PurchaseOrder ORDER BY po_id DESC")->fetch_all(MYSQLI_ASSOC);
$products = $conn->query("SELECT p.p_id, p.p_name, p.price, pu.punit_name AS unit, pu.punit_id FROM Product p JOIN ProductUnit pu ON pu.punit_id = p.punit_id ORDER BY p.p_name ASC")->fetch_all(MYSQLI_ASSOC);
$productUnits = $conn->query("SELECT punit_id, punit_name FROM ProductUnit ORDER BY punit_name ASC")->fetch_all(MYSQLI_ASSOC);
$productBrands = $conn->query("SELECT pb_id, pb_name FROM ProductBrand ORDER BY pb_name ASC")->fetch_all(MYSQLI_ASSOC);

// Fetch import list
$stmt = $conn->prepare("SELECT pod.pod_id AS id, DATE(po.date) AS date, po.po_id AS orderRef, p.p_id, p.p_name AS product, pu.punit_name AS unit, pod.qty, pod.price AS unitPrice FROM PurchaseOrderDetail pod JOIN PurchaseOrder po ON po.po_id = pod.po_id JOIN Product p ON p.p_id = pod.p_id JOIN ProductUnit pu ON pu.punit_id = p.punit_id WHERE p.p_name LIKE ? OR po.po_id LIKE ? ORDER BY pod.pod_id DESC");
$like = '%'.$search.'%';
$stmt->bind_param('ss',$like,$like);
$stmt->execute();
$imports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການສິນຄ້ານຳເຂົ້າ - ລະບົບຈັດການຮ້ານGPG</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<?php include 'includes/navbar.php'; ?>

<div class="container">
    <div class="dashboard-container">
        <h2 class="dashboard-title" style="margin-bottom: 18px;">ຈັດການສິນຄ້ານຳເຂົ້າ</h2>
        <!-- Search Bar -->
        <form method="get" style="display:flex;gap:12px;margin-bottom:18px;">
            <input type="text" name="search" placeholder="ຄົ້ນຫາ (ລະຫັດ, ສິນຄ້າ, ວັນທີ)" value="<?php echo htmlspecialchars($search); ?>" style="flex:1;padding:10px;border-radius:6px;border:1px solid #ccc;">
            <button type="submit" class="dashboard-add-btn" style="width:180px;">ຄົ້ນຫາ</button>
        </form>
        <button type="button" class="dashboard-add-btn" onclick="openAddModal()" style="margin-bottom: 18px; width: 220px;">ເພີ່ມການນຳເຂົ້າໃໝ່</button>

        <!-- Import Table -->
        <table class="dashboard-table">
            <thead>
                <tr>
                    <th>ລະຫັດນຳເຂົ້າ</th>
                    <th>ວັນທີນຳເຂົ້າ</th>
                    <th>ລະຫັດສັ່ງຊື້</th>
                    <th>ຊື່ສິນຄ້າ</th>
                    <th>ຫນ່ວຍ</th>
                    <th>ຈຳນວນ</th>
                    <th>ລາຄາຕໍ່ຫນ່ວຍ</th>
                    <th>ລາຄາລວມ</th>
                    <th>ຈັດການ</th>
                </tr>
            </thead>
            <tbody id="importTableBody"></tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Import Modal -->
<div id="importModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3 class="modal-title" id="modalTitle">ເພີ່ມການນຳເຂົ້າໃໝ່</h3>
        <form id="importForm">
            <input type="hidden" name="importId" id="importId">
            <label for="importDate">ວັນທີນຳເຂົ້າ:</label>
            <input type="date" id="importDate" name="importDate" class="gpg-input" required>
            <label for="orderRef">ລະຫັດສັ່ງຊື້ (Ref):</label>
            <select id="orderRef" name="orderRef" class="gpg-input" required><?php foreach($purchaseOrders as $po){echo "<option value='{$po['po_id']}'>{$po['po_id']}</option>";} ?></select>
            <label for="productId">ຊື່ສິນຄ້າ:</label>
            <select id="productId" name="productId" class="gpg-input" required onchange="fillProductDetails()"><?php foreach($products as $p){echo "<option value='{$p['p_id']}' data-unit='{$p['unit']}' data-price='{$p['price']}' data-unit-id='{$p['punit_id']}'>{$p['p_name']}</option>";} ?></select>
            <!-- Product Unit Dropdown -->
            <label for="productUnitId">ຫນ່ວຍ:</label>
            <select id="productUnitId" name="productUnitId" class="gpg-input" required>
                <?php foreach($productUnits as $unit){echo "<option value='{$unit['punit_id']}'>{$unit['punit_name']}</option>";} ?>
            </select>
            <label for="qty">ຈຳນວນ:</label>
            <input type="number" id="qty" name="qty" class="gpg-input" required>
            <label for="unitPrice">ລາຄາຕໍ່ຫນ່ວຍ:</label>
            <input type="number" id="unitPrice" name="unitPrice" class="gpg-input" required>
            <div style="text-align:center;margin-top:20px;">
                <button type="submit" class="dashboard-add-btn">ບັນທຶກ</button>
            </div>
        </form>
    </div>
</div>

<script>
    const purchaseOrders = <?php echo json_encode($purchaseOrders, JSON_UNESCAPED_UNICODE); ?>;
    const products = <?php echo json_encode($products, JSON_UNESCAPED_UNICODE); ?>;
    const productUnits = <?php echo json_encode($productUnits, JSON_UNESCAPED_UNICODE); ?>;
    let imports = <?php echo json_encode($imports, JSON_UNESCAPED_UNICODE); ?>;

    function total(i){return i.qty*i.unitPrice;}
    function fillProductDetails(){
        const sel=document.getElementById('productId');
        const opt=sel.options[sel.selectedIndex];
        document.getElementById('productUnitId').value=opt.dataset.unitId||'';
        document.getElementById('unitPrice').value=opt.dataset.price||'';
    }

    function openAddModal(){
        document.getElementById('modalTitle').textContent='ເພີ່ມການນຳເຂົ້າໃໝ່';
        document.getElementById('importForm').reset();
        document.getElementById('importId').value='';
        document.getElementById('importModal').style.display='block';
    }

    function editImport(id){
        const imp=imports.find(x=>x.id===id);
        if(!imp) return;
        document.getElementById('modalTitle').textContent='ແກ້ໄຂການນຳເຂົ້າ';
        document.getElementById('importId').value=imp.id;
        document.getElementById('importDate').value=imp.date;
        document.getElementById('orderRef').value=imp.orderRef;
        document.getElementById('productId').value=imp.p_id;
        fillProductDetails(); // Call fillProductDetails after setting the product ID
        document.getElementById('qty').value=imp.qty;
        document.getElementById('unitPrice').value=imp.unitPrice;
        document.getElementById('importModal').style.display='block';
    }
    
    function deleteImport(id){
        if(confirm('ທ່ານແນ່ໃຈບໍ່ວ່າຈະລົບ?')){
            fetch('product_import_api.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'delete',id})})
            .then(r=>r.json()).then(res=>{if(res.success){imports=res.imports;renderTable();}else alert(res.error||'Error');});
        }
    }
    function closeModal(){document.getElementById('importModal').style.display='none';}
    function renderTable(){
        const tbody=document.getElementById('importTableBody');
        tbody.innerHTML='';
        imports.forEach((imp,i)=>{
            tbody.insertRow().innerHTML=`<td>${i+1}</td><td>${new Date(imp.date).toLocaleDateString('en-GB')}</td><td>${imp.orderRef}</td><td>${imp.product}</td><td>${imp.unit}</td><td>${imp.qty}</td><td>${Number(imp.unitPrice).toLocaleString()} ກິບ</td><td>${total(imp).toLocaleString()} ກິບ</td><td><button class=\"dashboard-edit-btn\" onclick=\"editImport(${imp.id})\">ແກ້ໄຂ</button> <button class=\"dashboard-delete-btn\" onclick=\"deleteImport(${imp.id})\">ລົບ</button></td>`;
        });
    }
    document.getElementById('importForm').addEventListener('submit',function(e){
        e.preventDefault();
        const id=this.importId.value;
        const data={po_id:parseInt(this.orderRef.value),p_id:parseInt(this.productId.value),qty:parseInt(this.qty.value),unitPrice:parseFloat(this.unitPrice.value)};
        const payload={...data,action: id ? 'edit':'add',id:id?parseInt(id):undefined};
        fetch('product_import_api.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)})
            .then(r=>r.json())
            .then(res=>{
                if(res.success){
                    imports=res.imports;
                    renderTable();
                    closeModal();
                }else alert(res.error||'Error');
            });
    });
    window.onclick=e=>{if(e.target===document.getElementById('importModal')) closeModal();};
    document.addEventListener('DOMContentLoaded',renderTable);
</script>
</html>
