<?php
require_once 'includes/auth.php';
require_once 'db/connection.php';

session_start();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$like_search = "%$search%";

// Fetch sales data and total payments for each sale.
$sql = "SELECT
            s.s_id,
            c.c_name,
            SUM(sd.qty) AS total_qty,
            SUM(sd.total_price) AS total_sale_price,
            COALESCE(SUM(pm.customer_paid), 0) AS customer_paid,
            (SUM(sd.total_price) - COALESCE(SUM(pm.customer_paid), 0)) AS balance_due
        FROM Sell s
        JOIN SellDetail sd ON s.s_id = sd.s_id
        LEFT JOIN Customer c ON s.c_id = c.c_id
        LEFT JOIN Payment pm ON s.s_id = pm.s_id";

if (!empty($search)) {
    $sql .= " WHERE s.s_id LIKE ? OR c.c_name LIKE ?";
}

$sql .= " GROUP BY s.s_id, c.c_name ORDER BY s.s_id DESC";

$stmt = $conn->prepare($sql);
if (!empty($search)) {
    $stmt->bind_param('ss', $like_search, $like_search);
}
$stmt->execute();
$sales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function laoNumberFormat($value) {
    return number_format((float)$value, 0, '.', ',') . ' ກິບ';
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການການຊຳລະ</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Noto Sans Lao', sans-serif; }
        .container-box { max-width: 1200px; margin: 20px auto; padding: 24px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .page-header { font-size: 22px; font-weight: 700; margin-bottom: 16px; }
        .search-form { display: flex; gap: 10px; margin-bottom: 16px; }
        .search-input { flex-grow: 1; padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; }
        .btn { padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; color: white; }
        .btn-green { background-color: #28a745; }
        .btn-red { background-color: #dc3545; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table thead th { background-color: #007bff; color: white; padding: 12px; text-align: center; font-weight: bold; }
        .data-table tbody td { padding: 10px; border-bottom: 1px solid #e0e0e0; text-align: center; }
        .data-table tbody tr:nth-child(even) { background-color: #f8f9fa; }
        .data-table td.text-left { text-align: left; }
        .balance-due-positive { color: #dc3545; font-weight: bold; }
        .balance-due-zero { color: #28a745; font-weight: bold; }
        .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; font-weight: bold; }
        .alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
        .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
        .modal { display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); padding-top: 60px; }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; position: relative; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; }
        .close:hover, .close:focus { color: black; text-decoration: none; cursor: pointer; }
        .modal-title { text-align: center; font-size: 20px; font-weight: 700; margin-bottom: 20px; }
        .gpg-input { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .dashboard-add-btn { background-color: #007bff; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .dashboard-add-btn:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="container-box">
        <h2 class="page-header">ຈັດການການຊຳລະ</h2>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <form method="get" class="search-form">
            <input type="text" name="search" class="search-input" placeholder="ຄົ້ນຫາ (ເລກທີບິນ, ລູກຄ້າ)" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-green">ຄົ້ນຫາ</button>
        </form>

        <a href="sale.php" class="btn btn-green" style="margin-bottom: 16px;">ເພີ່ມການຊຳລະໃໝ່</a>

        <table class="data-table">
            <thead>
                <tr>
                    <th>ເລກທີຊຳລະ</th>
                    <th>ຊື່ລູກຄ້າ</th>
                    <th>ຈຳນວນສິນຄ້າທັງໝົດ</th>
                    <th>ລາຄາລວມທັງໝົດ</th>
                    <th>ຈຳນວນເງິນລູກຄ້າຈ່າຍ</th>
                    <th>ຍອດເຫຼືອ</th>
                    <th>ຈັດການ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($sales)): ?>
                    <?php foreach ($sales as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['s_id']); ?></td>
                            <td class="text-left"><?php echo htmlspecialchars($row['c_name'] ?? 'Walk-in'); ?></td>
                            <td><?php echo htmlspecialchars($row['total_qty']); ?></td>
                            <td><?php echo laoNumberFormat($row['total_sale_price']); ?></td>
                            <td><?php echo laoNumberFormat($row['customer_paid'] ?? 0); ?></td>
                            <td class="<?php echo ($row['balance_due'] > 0) ? 'balance-due-positive' : 'balance-due-zero'; ?>">
                                <?php echo laoNumberFormat($row['balance_due']); ?>
                            </td>
                            <td>
                                <button onclick="openEditPaymentModal(this)"
                                        data-sid="<?php echo htmlspecialchars($row['s_id']); ?>"
                                        data-customer-paid="<?php echo htmlspecialchars($row['customer_paid'] ?? 0); ?>"
                                        class="btn btn-green"
                                        style="margin-bottom: 5px;">ແກ້ໄຂ</button>
                                <a href="payment_api.php?action=delete_sale&sid=<?php echo $row['s_id']; ?>" onclick="return confirm('ການລົບລາຍການຂາຍນີ້ຈະລົບການຈ່າຍເງິນທີ່ກ່ຽວຂ້ອງນຳ. ດຳເນີນການຕໍ່?')" class="btn btn-red">ລົບ</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="padding: 20px;">ບໍ່ມີຂໍ້ມູນ</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('paymentModal')">&times;</span>
            <h3 class="modal-title" id="paymentModalTitle">ແກ້ໄຂຈຳນວນເງິນທີ່ຊຳລະ</h3>
            <form id="paymentForm" action="payment_api.php" method="post">
                <input type="hidden" id="saleId" name="sid">
                <input type="hidden" name="action" value="update_customer_paid">

                <label for="customerPaid">ຈຳນວນເງິນລູກຄ້າຈ່າຍ:</label>
                <input type="number" id="customerPaid" name="customerPaid" class="gpg-input" required>

                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="dashboard-add-btn">ບັນທຶກ</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditPaymentModal(btn) {
            document.getElementById('paymentModalTitle').textContent = 'ແກ້ໄຂຈຳນວນເງິນທີ່ຊຳລະ';
            document.getElementById('saleId').value = btn.dataset.sid;
            document.getElementById('customerPaid').value = parseFloat(btn.dataset.customerPaid) || 0;
            document.getElementById('paymentModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        window.onclick = function(e) {
            const paymentModal = document.getElementById('paymentModal');
            if (e.target === paymentModal) {
                closeModal('paymentModal');
            }
        };
    </script>
</body>
</html>
