<?php
include 'includes/auth.php';
include 'db/connection.php';

// Check if the user is logged in and has the 'admin' role
requireAdmin();

$search = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch dropdown data
$suppliers = $conn->query("SELECT sup_id, sup_name FROM Supplier ORDER BY sup_name ASC")->fetch_all(MYSQLI_ASSOC);
$employees = $conn->query("SELECT emp_id, emp_name FROM Employee ORDER BY emp_name ASC")->fetch_all(MYSQLI_ASSOC);
$products = $conn->query("SELECT p_id, p_name, price FROM Product ORDER BY p_name ASC")->fetch_all(MYSQLI_ASSOC);

// Fetch orders list with simple join (one product per order)
$stmt = $conn->prepare("SELECT po.po_id AS id, sup.sup_name AS supplier, emp.emp_name AS employee, p.p_name AS product, pod.qty, po.date, pod.price AS unitPrice FROM PurchaseOrder po JOIN Supplier sup ON po.sup_id=sup.sup_id JOIN Employee emp ON po.emp_id=emp.emp_id JOIN PurchaseOrderDetail pod ON pod.po_id=po.po_id JOIN Product p ON p.p_id=pod.p_id WHERE sup.sup_name LIKE ? OR p.p_name LIKE ? OR po.po_id LIKE ? ORDER BY po.po_id DESC");
$like='%'.$search.'%';
$stmt->bind_param('sss',$like,$like,$like);
$stmt->execute();
$orders=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການການສັ່ງຊື້ສິນຄ້າ - ລະບົບຈັດການຮ້ານGPG</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<?php include 'includes/navbar.php'; ?>

<div class="container">
    <div class="dashboard-container">
        <h2 class="dashboard-title" style="margin-bottom: 18px;">ຈັດການການສັ່ງຊື້ສິນຄ້າ</h2>
        <!-- Search Bar -->
        <form method="get" style="display:flex;gap:12px;margin-bottom:18px;">
            <input type="text" name="search" placeholder="ຄົ້ນຫາ (ຜູ້ສະຫນອງ, ສິນຄ້າ, ເລກທີ)" value="<?php echo htmlspecialchars($search); ?>" style="flex:1;padding:10px;border-radius:6px;border:1px solid #ccc;">
            <button type="submit" class="dashboard-add-btn" style="width:180px;">ຄົ້ນຫາ</button>
        </form>
        <button type="button" class="dashboard-add-btn" onclick="openAddModal()" style="margin-bottom: 18px; width: 220px;">ເພີ່ມການສັ່ງຊື້ໃໝ່</button>

        <!-- Orders Table -->
        <table class="dashboard-table">
            <thead>
                <tr>
                    <th>ລຳດັບ</th>
                    <th>ຜູ້ສະຫນອງ</th>
                    <th>ພະນັກງານຮັບອອກຄຳສັ່ງ</th>
                    <th>ສິນຄ້າ</th>
                    <th>ຈຳນວນ</th>
                    <th>ວັນທີສັ່ງ</th>
                    <th>ລາຄາຕໍ່ຫນ່ວຍ</th>
                    <th>ລາຄາລວມ</th>
                    <th>ຈັດການ</th>
                </tr>
            </thead>
            <tbody id="orderTableBody"></tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Order Modal -->
<div id="orderModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3 class="modal-title" id="modalTitle">ເພີ່ມການສັ່ງຊື້ໃໝ່</h3>
        <form id="orderForm">
            <input type="hidden" name="orderId" id="orderId">
            <label for="supplierName">ຜູ້ສະຫນອງ:</label>
            <select id="supplierId" name="supplierId" class="gpg-input" required><?php foreach($suppliers as $s){echo "<option value='{$s['sup_id']}'>{$s['sup_name']}</option>";} ?></select>
            <label for="employeeName">ພະນັກງານຮັບອອກຄຳສັ່ງ:</label>
            <select id="employeeId" name="employeeId" class="gpg-input" required><?php foreach($employees as $e){echo "<option value='{$e['emp_id']}'>{$e['emp_name']}</option>";} ?></select>
            <label for="productName">ສິນຄ້າ:</label>
            <select id="productId" name="productId" class="gpg-input" required onchange="updateUnitPrice()"><?php foreach($products as $p){echo "<option value='{$p['p_id']}' data-price='{$p['price']}'>{$p['p_name']}</option>";} ?></select>
            <label for="orderQty">ຈຳນວນ:</label>
            <input type="number" id="orderQty" name="orderQty" class="gpg-input" required>
            <label for="orderDate">ວັນທີສັ່ງ:</label>
            <input type="date" id="orderDate" name="orderDate" class="gpg-input" required>
            <label for="unitPrice">ລາຄາຕໍ່ຫນ່ວຍ:</label>
            <input type="number" id="unitPrice" name="unitPrice" class="gpg-input" required>
            <div style="text-align:center;margin-top:20px;">
                <button type="submit" class="dashboard-add-btn">ບັນທຶກ</button>
            </div>
        </form>
    </div>
</div>

<script>
    const suppliers = <?php echo json_encode($suppliers, JSON_UNESCAPED_UNICODE); ?>;
const employees = <?php echo json_encode($employees, JSON_UNESCAPED_UNICODE); ?>;
const products = <?php echo json_encode($products, JSON_UNESCAPED_UNICODE); ?>;
let orders = <?php echo json_encode($orders, JSON_UNESCAPED_UNICODE); ?>;

    function calcTotal(o){return o.qty*o.unitPrice;}
function nameById(arr,id,key){const f=arr.find(x=>x[key]==id);return f?f[key==='p_id'?'p_name':'sup_name']:'';}
function updateUnitPrice(){const sel=document.getElementById('productId');const price=parseFloat(sel.options[sel.selectedIndex].dataset.price||0);document.getElementById('unitPrice').value=price;}

    function openAddModal(){
        document.getElementById('modalTitle').textContent='ເພີ່ມການສັ່ງຊື້ໃໝ່';
        document.getElementById('orderForm').reset();
        document.getElementById('orderId').value='';
        document.getElementById('orderModal').style.display='block';
    }
    function editOrder(id){
        const o=orders.find(x=>x.id===id);
        if(!o) return;
        document.getElementById('modalTitle').textContent='ແກ້ໄຂການສັ່ງຊື້';
        document.getElementById('orderId').value=o.id;
        document.getElementById('supplierId').value=o.sup_id;
        document.getElementById('employeeId').value=o.emp_id;
        document.getElementById('productId').value=o.p_id;
        document.getElementById('orderQty').value=o.qty;
        document.getElementById('orderDate').value=o.date;
        document.getElementById('unitPrice').value=o.unitPrice;
        document.getElementById('orderModal').style.display='block';
    }
    function deleteOrder(id){
        if(confirm('ທ່ານແນ່ໃຈບໍ່ວ່າຈະລົບ?')){
            fetch('purchase_order_api.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'delete',id})})
            .then(r=>r.json()).then(res=>{if(res.success){orders=res.orders;renderTable();}else alert(res.error||'Error');});
        }
    }
    function closeModal(){document.getElementById('orderModal').style.display='none';}
    function renderTable(){
        const tbody=document.getElementById('orderTableBody');
        tbody.innerHTML='';
        orders.forEach((o,i)=>{
            tbody.insertRow().innerHTML=`<td>${i+1}</td><td>${o.supplier}</td><td>${o.employee}</td><td>${o.product}</td><td>${o.qty}</td><td>${new Date(o.date).toLocaleDateString('en-GB')}</td><td>${o.unitPrice.toLocaleString()} ກິບ</td><td>${calcTotal(o).toLocaleString()} ກິບ</td><td><button class=\"dashboard-edit-btn\" onclick=\"editOrder(${o.id})\">ແກ້ໄຂ</button> <button class=\"dashboard-delete-btn\" onclick=\"deleteOrder(${o.id})\">ລົບ</button></td>`;
        });
    }
    document.getElementById('orderForm').addEventListener('submit',function(e){
        e.preventDefault();
        const id=this.orderId.value;
        const data={sup_id:parseInt(this.supplierId.value),emp_id:parseInt(this.employeeId.value),p_id:parseInt(this.productId.value),qty:parseInt(this.orderQty.value),date:this.orderDate.value,unitPrice:parseFloat(this.unitPrice.value)};
        const payload={...data,action: id ? 'edit':'add',id:id?parseInt(id):undefined};
        fetch('purchase_order_api.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)})
        .then(r=>r.json()).then(res=>{if(res.success){orders=res.orders;renderTable();closeModal();}else alert(res.error||'Error');});
    });
    window.onclick=e=>{if(e.target===document.getElementById('orderModal')) closeModal();};
    document.addEventListener('DOMContentLoaded',renderTable);
</script>
</html>
