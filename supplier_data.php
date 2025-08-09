<?php
include 'includes/auth.php';
include 'db/connection.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch suppliers from database based on search
$stmt = $conn->prepare("SELECT sup_id AS id, sup_name AS name, tel AS phone, address
                              FROM Supplier
                              WHERE sup_name LIKE ? OR tel LIKE ?
                              ORDER BY sup_id ASC");
$like = '%' . $search . '%';
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$result = $stmt->get_result();
$suppliers = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ບັນທືກຂໍ້ມູນຜູ້ສະຫນອງ - ລະບົບຈັດການຮ້ານGPG</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<?php include 'includes/navbar.php'; ?>

<div class="container">
    <div class="dashboard-container">
        <h2 class="dashboard-title" style="margin-bottom: 18px;">ບັນທືກຂໍ້ມູນຜູ້ສະຫນອງ</h2>
        <!-- Search Bar -->
        <form method="get" style="display:flex;gap:12px;margin-bottom:18px;">
            <input type="text" name="search" placeholder="ຄົ້ນຫາ (ຜູ້ສະຫນອງ, ເບີໂທ)" value="<?php echo htmlspecialchars($search); ?>" style="flex:1;padding:10px;border-radius:6px;border:1px solid #ccc;">
            <button type="submit" class="dashboard-add-btn" style="width:180px;">ຄົ້ນຫາ</button>
        </form>
        <button type="button" class="dashboard-add-btn" onclick="openAddModal()" style="margin-bottom: 18px; width: 200px;">ເພີ່ມຂໍ້ມູນຜູ້ສະຫນອງ</button>

        <!-- Supplier Data Table -->
        <table class="dashboard-table">
            <thead>
                <tr>
                    <th>ລະຫັດ</th>
                    <th>ຊື່ ແລະ ນາມສະກຸນ</th>
                    <th>ເບີໂທ</th>
                    <th>ທີ່ຢູ່</th>
                    <th>ຈັດການ</th>
                </tr>
            </thead>
            <tbody id="supplierTableBody"></tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Supplier Modal -->
<div id="supplierModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3 class="modal-title" id="modalTitle">ເພີ່ມຂໍ້ມູນຜູ້ສະຫນອງ</h3>
        <form id="supplierForm">
            <input type="hidden" id="supplierId" name="supplierId">
            <label for="supplierName">ຊື່ ແລະ ນາມສະກຸນ:</label>
            <input type="text" id="supplierName" name="supplierName" class="gpg-input" required>
            <label for="supplierPhone">ເບີໂທ:</label>
            <input type="text" id="supplierPhone" name="supplierPhone" class="gpg-input" required>
            <label for="supplierAddress">ທີ່ຢູ່:</label>
            <textarea id="supplierAddress" name="supplierAddress" class="gpg-input" rows="3" required></textarea>
            <div style="text-align:center;margin-top:20px;">
                <button type="submit" class="dashboard-add-btn">ບັນທຶກ</button>
            </div>
        </form>
    </div>
</div>

<script>
    let suppliers = <?php echo json_encode($suppliers, JSON_UNESCAPED_UNICODE); ?>;

    function openAddModal(){
        document.getElementById('modalTitle').textContent='ເພີ່ມຂໍ້ມູນຜູ້ສະຫນອງ';
        document.getElementById('supplierForm').reset();
        document.getElementById('supplierId').value='';
        document.getElementById('supplierModal').style.display='block';
    }
    function editSupplier(id){
        const s = suppliers.find(x=>x.id===id);
        if(!s) return;
        document.getElementById('modalTitle').textContent='ແກ້ໄຂຂໍ້ມູນຜູ້ສະຫນອງ';
        document.getElementById('supplierId').value=s.id;
        document.getElementById('supplierName').value=s.name;
        document.getElementById('supplierPhone').value=s.phone;
        document.getElementById('supplierAddress').value=s.address;
        document.getElementById('supplierModal').style.display='block';
    }
    function deleteSupplier(id){
        if(!confirm('ທ່ານແນ່ໃຈບໍ່ວ່າຈະລົບ?')) return;
        fetch('supplier_api.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({action:'delete', id:id})
        })
        .then(r=>r.json())
        .then(res=>{
            if(res.success){
                suppliers = res.suppliers;
                renderTable();
            }else{
                alert(res.error || 'Error deleting supplier');
            }
        })
        .catch(()=>alert('Network error'));
    }
    function closeModal(){document.getElementById('supplierModal').style.display='none';}
    function renderTable(){
        const tbody=document.getElementById('supplierTableBody');
        tbody.innerHTML='';
        suppliers.forEach((s,i)=>{
            const row=tbody.insertRow();
            row.innerHTML=`<td>${i+1}</td><td>${s.name}</td><td>${s.phone}</td><td>${s.address}</td><td><button class=\"dashboard-edit-btn\" onclick=\"editSupplier(${s.id})\">ແກ້ໄຂ</button> <button class=\"dashboard-delete-btn\" onclick=\"deleteSupplier(${s.id})\">ລົບ</button></td>`;
        })
    }
    document.getElementById('supplierForm').addEventListener('submit',function(e){
        e.preventDefault();
        const id=this.supplierId.value;
        const payload={
            action: id ? 'edit' : 'add',
            id: id ? parseInt(id) : undefined,
            name:this.supplierName.value,
            phone:this.supplierPhone.value,
            address:this.supplierAddress.value
        };
        fetch('supplier_api.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify(payload)
        })
        .then(r=>r.json())
        .then(res=>{
            if(res.success){
                suppliers = res.suppliers;
                renderTable();
                closeModal();
            }else{
                alert(res.error || 'Error saving supplier');
            }
        })
        .catch(()=>alert('Network error'));
    });
    window.onclick=e=>{if(e.target===document.getElementById('supplierModal')) closeModal();};
    document.addEventListener('DOMContentLoaded',renderTable);
</script>
</html>
