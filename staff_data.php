<?php
include 'includes/auth.php';
include 'db/connection.php';

// Check if the user is logged in and has the 'admin' role
requireAdmin();

// Initialize variables
$search = isset($_GET['search']) ? $_GET['search'] : '';
$message = '';

// Handle add / edit / delete submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $emp_id = intval($_POST['empId'] ?? 0);
        $name = trim($_POST['employeeName'] ?? '');
        $tel = trim($_POST['employeePhone'] ?? '');
        $email = trim($_POST['employeeEmail'] ?? '');
        $address = trim($_POST['employeeAddress'] ?? '');
        $role = trim($_POST['employeeRole'] ?? '');
        
        // IMPORTANT: Storing passwords in plain text is a security risk.
        // This code uses the 'password' column as per your database schema.
        $password = $_POST['employeePassword'] ?? '';

        if ($name && $tel && $email && $address && $role) {
            if ($action === 'edit' && $emp_id > 0) {
                // For editing, only update password if it's provided
                if ($password) {
                    $stmt = $conn->prepare('UPDATE Employee SET emp_name=?, tel=?, password=?, email=?, address=?, role=? WHERE emp_id=?');
                    $stmt->bind_param('ssssssi', $name, $tel, $password, $email, $address, $role, $emp_id);
                } else {
                    $stmt = $conn->prepare('UPDATE Employee SET emp_name=?, tel=?, email=?, address=?, role=? WHERE emp_id=?');
                    $stmt->bind_param('sssssi', $name, $tel, $email, $address, $role, $emp_id);
                }
            } else if ($action === 'add' && $password) {
                $stmt = $conn->prepare('INSERT INTO Employee (emp_name, tel, password, email, address, role) VALUES (?,?,?,?,?,?)');
                $stmt->bind_param('ssssss', $name, $tel, $password, $email, $address, $role);
            } else {
                 $message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
            }

            if (isset($stmt) && $stmt->execute()) {
                $message = 'บันทึกข้อมูลเรียบร้อย';
            } else if(isset($stmt)) {
                $message = 'DB Error: ' . $stmt->error;
            }
            if (isset($stmt)) {
                $stmt->close();
            }
        } else {
            $message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        }
    } else if ($action === 'delete' && isset($_POST['empId'])) {
        $emp_id = intval($_POST['empId']);
        $stmt = $conn->prepare('DELETE FROM Employee WHERE emp_id=?');
        $stmt->bind_param('i', $emp_id);
        if ($stmt->execute()) {
            $message = 'ลบข้อมูลเรียบร้อย';
        } else {
            $message = 'DB Error: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch employee list with optional search
$employees = [];
try {
    $like = '%' . $search . '%';
    $stmt = $conn->prepare("SELECT emp_id, emp_name, tel, email, address, role FROM Employee WHERE emp_name LIKE ? OR tel LIKE ? OR email LIKE ? OR address LIKE ? ORDER BY emp_id DESC");
    $stmt->bind_param('ssss', $like, $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    $message = 'Error fetching employees: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการข้อมูลพนักงาน - ລະບົບຈັດການຮ້ານGPG</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<?php include 'includes/navbar.php'; ?>

<div class="container">
    <div class="dashboard-container">
        <h2 class="dashboard-title" style="margin-bottom: 18px;">ຈັດການຂໍ້ມູນພະນັກງານ</h2>
        <?php if ($message): ?>
            <p class="status-message" style="color: green; font-weight: bold;"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="get" style="display:flex;gap:12px;margin-bottom:18px;">
            <input type="text" name="search" placeholder="ຄົ້ນຫາ (ຊື່, ເບີໂທ, ອີເມລ)" value="<?= htmlspecialchars($search) ?>" style="flex:1;padding:10px;border-radius:6px;border:1px solid #ccc;">
            <button type="submit" class="dashboard-add-btn" style="width:180px;">ຄົ້ນຫາ</button>
        </form>
        <button type="button" class="dashboard-add-btn" onclick="openAddModal()" style="margin-bottom:18px;width:200px;">ເພີ່ມຂໍ້ມູນພະນັກງານ</button>

        <!-- Employee Data Table -->
        <table class="dashboard-table">
            <thead>
                <tr>
                    <th>ລະບົດ</th>
                    <th>ຊື່ ແລະ ນາມສະກຸນ</th>
                    <th>ເບີໂທ</th>
                    <th>ອີເມວ</th>
                    <th>ທີ່ຢູ່</th>
                    <th>บทบาท</th>
                    <th>ຈັດການ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($employees)): ?>
                    <tr><td colspan="7" style="text-align:center;">ບໍ່ພົບຂໍ້ມູນພະນັກງານ</td></tr>
                <?php else: ?>
                    <?php foreach ($employees as $index => $emp): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($emp['emp_name']) ?></td>
                            <td><?= htmlspecialchars($emp['tel']) ?></td>
                            <td><?= htmlspecialchars($emp['email']) ?></td>
                            <td><?= htmlspecialchars($emp['address']) ?></td>
                            <td><?= htmlspecialchars($emp['role']) ?></td>
                            <td>
                                <button type="button" class="dashboard-edit-btn" onclick="openEditModal(this)" data-id="<?= $emp['emp_id'] ?>" data-name="<?= htmlspecialchars($emp['emp_name']) ?>" data-tel="<?= htmlspecialchars($emp['tel']) ?>" data-email="<?= htmlspecialchars($emp['email']) ?>" data-address="<?= htmlspecialchars($emp['address']) ?>" data-role="<?= htmlspecialchars($emp['role']) ?>">ແກ້ໄຂ</button>
                                <button type="button" class="dashboard-delete-btn" onclick="deleteEmployee(<?= $emp['emp_id'] ?>)">ລົບ</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Employee Modal -->
<div id="employeeModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3 class="modal-title" id="modalTitle">เพิ่มข้อมูลพนักงาน</h3>
        <form id="employeeForm" method="post">
            <input type="hidden" id="action" name="action">
            <input type="hidden" id="empId" name="empId">
            
            <label for="employeeName">ຊື່ ແລະ ນາມສະກຸນ:</label>
            <input type="text" id="employeeName" name="employeeName" class="gpg-input" required>
            
            <label for="employeePhone">ເບີໂທ:</label>
            <input type="text" id="employeePhone" name="employeePhone" class="gpg-input" required>
            
            <label for="employeeEmail">ອີເມລ:</label>
            <input type="email" id="employeeEmail" name="employeeEmail" class="gpg-input" required>

            <label for="employeePassword">ລະຫັດຜ່ານ:</label>
            <input type="password" id="employeePassword" name="employeePassword" class="gpg-input" placeholder="ใส่เมื่อต้องการเพิ่มหรือเปลี่ยนรหัสผ่านเท่านั้น">
            
            <label for="employeeAddress">ທີ່ຢູ່:</label>
            <textarea id="employeeAddress" name="employeeAddress" class="gpg-input" rows="3" required></textarea>
            
            <label for="employeeRole">ບົດບາດ:</label>
            <select id="employeeRole" name="employeeRole" class="gpg-input" required>
                <option value="admin">Admin</option>
                <option value="employee">Employee</option>
            </select>
            
            <div style="text-align: center; margin-top: 20px;">
                <button type="submit" class="dashboard-add-btn">ບັນທຶກ</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('modalTitle').textContent = 'เพิ่มข้อมูลพนักงาน';
        document.getElementById('action').value = 'add';
        document.getElementById('employeeForm').reset();
        document.getElementById('empId').value = '';
        document.getElementById('employeePassword').required = true;
        document.getElementById('employeeModal').style.display = 'block';
    }

    function openEditModal(btn) {
        document.getElementById('modalTitle').textContent = 'แก้ไขข้อมูลพนักงาน';
        document.getElementById('action').value = 'edit';
        document.getElementById('empId').value = btn.dataset.id;
        document.getElementById('employeeName').value = btn.dataset.name;
        document.getElementById('employeePhone').value = btn.dataset.tel;
        document.getElementById('employeeEmail').value = btn.dataset.email;
        document.getElementById('employeeAddress').value = btn.dataset.address;
        document.getElementById('employeeRole').value = btn.dataset.role;
        document.getElementById('employeePassword').required = false;
        document.getElementById('employeePassword').value = ''; // Clear password field for security
        document.getElementById('employeeModal').style.display = 'block';
    }

    function deleteEmployee(id) {
        if (confirm('ยืนยันการลบข้อมูลพนักงานนี้หรือไม่?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete';
            form.appendChild(actionInput);

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'empId';
            idInput.value = id;
            form.appendChild(idInput);

            document.body.appendChild(form);
            form.submit();
        }
    }

    function closeModal() {
        document.getElementById('employeeModal').style.display = 'none';
    }

    window.onclick = function(e) {
        if (e.target == document.getElementById('employeeModal')) {
            closeModal();
        }
    };
</script>
</body>
</html>
