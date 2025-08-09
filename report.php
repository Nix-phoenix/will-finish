<?php
include 'includes/auth.php';
include 'db/connection.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$inventory_where = '';
$date_filter = '';
if ($search !== '') {
    $search_escaped = $conn->real_escape_string($search);
    // If the search looks like a date (YYYY-MM-DD), filter by date
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $search_escaped)) {
        $date_filter = "HAVING sale_date = '$search_escaped'";
    } else {
        $inventory_where = "WHERE p.p_name LIKE '%$search_escaped%' OR p.type LIKE '%$search_escaped%' OR p.unit LIKE '%$search_escaped%' OR p.shelf LIKE '%$search_escaped%'";
    }
}

// Sales report: total sales per day and best-selling product
$sales_sql = "
    SELECT 
        DATE(s.date) as sale_date,
        SUM(sd.total_price) as total_sales,
        (SELECT p.p_name 
         FROM SellDetail sd2 
         JOIN Product p ON sd2.p_id = p.p_id 
         WHERE sd2.s_id = s.s_id 
         ORDER BY sd2.qty DESC LIMIT 1) as best_product
    FROM Sell s
    JOIN SellDetail sd ON s.s_id = sd.s_id
    GROUP BY DATE(s.date)
    $date_filter
    ORDER BY sale_date DESC
    LIMIT 10
";
$sales_result = $conn->query($sales_sql);

// Income & expense report (using PurchaseOrder and PurchaseOrderDetail for expenses)
$income_expense_sql = "
    SELECT 
        DATE(s.date) as sale_date,
        SUM(sd.total_price) as income,
        IFNULL((
            SELECT SUM(pod.price * pod.qty)
            FROM PurchaseOrder po
            JOIN PurchaseOrderDetail pod ON po.po_id = pod.po_id
            WHERE DATE(po.date) = DATE(s.date)
        ), 0) as expense,
        SUM(sd.total_price) - IFNULL((
            SELECT SUM(pod.price * pod.qty)
            FROM PurchaseOrder po
            JOIN PurchaseOrderDetail pod ON po.po_id = pod.po_id
            WHERE DATE(po.date) = DATE(s.date)
        ), 0) as profit
    FROM Sell s
    JOIN SellDetail sd ON s.s_id = sd.s_id
    GROUP BY DATE(s.date)
    $date_filter
    ORDER BY sale_date DESC
    LIMIT 10
";
$income_expense_result = $conn->query($income_expense_sql);

// Inventory report: product and quantity left (using Product.qty as the source of truth)
$inventory_sql = "
    SELECT 
        p.p_name,
        p.qty AS qty_left
    FROM Product p
    $inventory_where
    ORDER BY qty_left DESC
    LIMIT 10
";
$inventory_result = $conn->query($inventory_sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>ລາຍງານ - Store Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="dashboard-container">
        <h2 class="dashboard-title">ການລາຍງານ</h2>
        <button class="dashboard-add-btn" id="printReportBtn" style="margin-bottom: 18px; width: 200px;">ພິມລາຍງານ</button>
        <form style="display:flex;gap:16px;margin-bottom:18px;" method="get">
            <input type="text" name="search" placeholder="ຄົ້ນຫາລາຍງານ...." value="<?php echo htmlspecialchars($search); ?>" style="flex:1;padding:10px;border-radius:6px;border:1px solid #ccc;">
            <button type="submit" class="dashboard-add-btn" style="width:200px;">ຄົ້ນຫາ</button>
        </form>
        <h3 class="report-section-title" style="margin:18px 0 8px 0;">ລາຍງານການຂາຍ</h3>
        <table class="dashboard-table report-table" id="sales-table">
            <thead>
                <tr>
                    <th>ວັນທີ</th>
                    <th>ຍອດຂາຍລວມ</th>
                    <th>ສິນຄ້າຂາຍດີ</th>
                    <th>ຈັດການ</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $sales_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['sale_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['total_sales']); ?></td>
                    <td><?php echo htmlspecialchars($row['best_product']); ?></td>
                    <td><button class="dashboard-edit-btn viewProductBtn"
                        data-date="<?php echo htmlspecialchars($row['sale_date']); ?>"
                        data-total="<?php echo htmlspecialchars($row['total_sales']); ?>"
                        data-best="<?php echo htmlspecialchars($row['best_product']); ?>"
                    >View</button></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h3 class="report-section-title" style="margin:18px 0 8px 0;">ລາຍງານຮັບເງິນ & ຈ່າຍເງິນ</h3>
        <table class="dashboard-table report-table" id="income-table">
            <thead>
                <tr>
                    <th>ວັນທີ</th>
                    <th>ຮັບເງິນ</th>
                    <th>ຈ່າຍເງິນ</th>
                    <th>ກຳໄລ</th>
                    <th>ຈັດການ</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $income_expense_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['sale_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['income']); ?>kip</td>
                    <td><?php echo htmlspecialchars($row['expense']); ?>kip</td>
                    <td><?php echo htmlspecialchars($row['profit']); ?>kip</td>
                    <td><button class="dashboard-edit-btn viewProductBtn"
                        data-date="<?php echo htmlspecialchars($row['sale_date']); ?>"
                        data-income="<?php echo htmlspecialchars($row['income']); ?>"
                        data-expense="<?php echo htmlspecialchars($row['expense']); ?>"
                        data-profit="<?php echo htmlspecialchars($row['profit']); ?>"
                    >View</button></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h3 class="report-section-title" style="margin:18px 0 8px 0;">ລາຍງານຄັ່ງສິນຄ້າ</h3>
        <table class="dashboard-table report-table" id="inventory-table">
            <thead>
                <tr>
                    <th>ຊື່ສິນຄໍາ</th>
                    <th>ຈຳນວນຄົງເຫຼືອ</th>
                    <th>ຈັດການ</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $inventory_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['p_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['qty_left']); ?></td>
                    <td><button class="dashboard-edit-btn viewProductBtn"
                        data-pname="<?php echo htmlspecialchars($row['p_name']); ?>"
                        data-qtyleft="<?php echo htmlspecialchars($row['qty_left']); ?>"
                    >View</button></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <!-- Product Detail Modal -->
    <div id="productDetailModal" class="modal">
      <div class="modal-content">
        <span class="close" id="closeProductDetailModal">&times;</span>
        <h3 class="modal-title">ລາຍລະອຽດສິນຄ້າ</h3>
        <div id="productDetailContent">
          <!-- Details will be loaded here -->
        </div>
      </div>
    </div>
    <script src="assets/js/scripts.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.viewProductBtn').forEach(function(btn) {
            btn.onclick = function() {
                let html = '';
                if (btn.hasAttribute('data-best')) {
                    // Sales report
                    html = `
                        <p><strong>ວັນທີ:</strong> ${btn.getAttribute('data-date')}</p>
                        <p><strong>ຍອດຂາຍລວມ:</strong> ${btn.getAttribute('data-total')}</p>
                        <p><strong>ສິນຄ້າຂາຍດີ:</strong> ${btn.getAttribute('data-best')}</p>
                    `;
                } else if (btn.hasAttribute('data-income')) {
                    // Income/Expense report
                    html = `
                        <p><strong>ວັນທີ:</strong> ${btn.getAttribute('data-date')}</p>
                        <p><strong>ຮັບເງິນ:</strong> ${btn.getAttribute('data-income')}</p>
                        <p><strong>ຈ່າຍເງິນ:</strong> ${btn.getAttribute('data-expense')}</p>
                        <p><strong>ກຳໄລ:</strong> ${btn.getAttribute('data-profit')}</p>
                    `;
                } else {
                    // Inventory report (already works)
                    html = `
                        <p><strong>ຊື່ສິນຄ້າ:</strong> ${btn.getAttribute('data-pname')}</p>
                        <p><strong>ຈຳນວນຄົງເຫຼືອ:</strong> ${btn.getAttribute('data-qtyleft')}</p>
                    `;
                }
                document.getElementById('productDetailContent').innerHTML = html;
                document.getElementById('productDetailModal').style.display = 'block';
            };
        });
        document.getElementById('closeProductDetailModal').onclick = function() {
            document.getElementById('productDetailModal').style.display = 'none';
        };
        window.onclick = function(event) {
            var modal = document.getElementById('productDetailModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        };
        // Print button: just print the page
        document.getElementById('printReportBtn').onclick = function() {
            window.print();
        };
    });
    </script>
    <style>
    @media print {
        nav, .gpg-navbar, .gpg-header, .dashboard-add-btn, .dashboard-edit-btn, .dashboard-delete-btn, .modal, .modal-content, .close, form, button, input[type="button"], input[type="submit"], a.button, .viewProductBtn {
            display: none !important;
        }
        #productDetailModal, #closeProductDetailModal, #productDetailContent {
            display: none !important;
        }
        .dashboard-table, .dashboard-table th, .dashboard-table td {
            border: 1px solid #333 !important;
            color: #000 !important;
            background: #fff !important;
        }
        .dashboard-table {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 14px !important;
            margin: 0 !important;
        }
        .dashboard-table th, .dashboard-table td {
            padding: 6px 10px !important;
            text-align: left !important;
            vertical-align: middle !important;
        }
        .dashboard-table th {
            background: #f2f2f2 !important;
            font-weight: bold !important;
        }
        .report-section-title {
            margin-top: 24px !important;
            margin-bottom: 8px !important;
            color: #000 !important;
            page-break-after: avoid !important;
        }
        .dashboard-title {
            text-align: center !important;
        }
        @page {
            margin: 16mm 10mm 16mm 10mm;
        }
    }
    </style>
</body>
</html>