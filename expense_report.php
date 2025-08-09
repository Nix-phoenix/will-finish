<?php
require_once 'includes/auth.php';
require_once 'db/connection.php';

// Fetch expense data (from purchase orders)
$expenses = [];
$sql = "SELECT 
            po.po_id,
            po.date,
            s.sup_name,
            e.emp_name,
            SUM(pod.qty) AS total_items,
            SUM(pod.qty * pod.price) AS total_cost
        FROM PurchaseOrder po
        JOIN PurchaseOrderDetail pod ON po.po_id = pod.po_id
        LEFT JOIN Supplier s ON po.sup_id = s.sup_id
        LEFT JOIN Employee e ON po.emp_id = e.emp_id
        GROUP BY po.po_id
        ORDER BY po.date DESC";

$result = $conn->query($sql);

if ($result === false) {
    die("Database query failed: " . $conn->error);
}

if ($result->num_rows > 0) {
    $expenses = $result->fetch_all(MYSQLI_ASSOC);
}

if ($result) {
    $result->free();
}

function laoNumberFormat($value) {
    return number_format($value, 0, '.', ',') . ' ກິບ';
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ລາຍງານລາຍຈ່າຍ</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        @media print {
            .no-print, .gpg-navbar { display: none !important; }
            body { margin: 0; }
        }
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 10px 30px;
            font-family: 'Noto Sans Lao', sans-serif;
        }
        .receipt-title {
            text-align: center;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .receipt-header {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
        .receipt-header h3 {
            font-size: 34px;
            font-weight: 700;
            margin: 0;
        }
        .store-info p{margin:0;font-size:14px;}
        .right-info{ text-align:right; font-size:14px;}
        .dotted{display:inline-block;min-width:100px;border-bottom:1px dotted #000;margin-left:6px;}
        .receipt-table, .receipt-table th, .receipt-table td {
            border: 1px solid #000;
            border-collapse: collapse;
        }
        .receipt-table {
            width: 100%;
            margin-top: 16px;
        }
        .receipt-table th, .receipt-table td {
            padding: 6px 4px;
            text-align: center;
            font-size: 12px;
        }
        .receipt-table td.text-left {
            text-align: left;
        }
        .receipt-footer {
            margin-top: 24px;
            display: flex;
            justify-content: space-between;
        }
        .Print-btn {
            background: #4caf50;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-size: 1.05rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .Print-btn:hover { background: #388e3c; }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="receipt-container">
    <div class="container no-print" style="text-align:left;margin-top:20px;">
        <button class="Print-btn" onclick="window.print();">ພິມລາຍງານ</button>
    </div>
    
    <h2 class="receipt-title">ໃບລາຍງານລາຍຈ່າຍ<br>Expense Report</h2>

    <div class="receipt-header">
        <div class="store-info">
            <h3>ຮ້ານ ຈິພິຈີ</h3>
            <p style="font-weight:bold;">ສະຫນອງອຸປະປະກອນແລະທໍ່ນໍ້າປະປາທຸກຊະນິດ<br>
            <p style="font-weight:bold; margin-left:50px;">ໂທ 020-58828288</p>
            <p style="font-weight:bold;">WhatsApp 030-5656555<br>Facebook @gpglaosstore</p>
        </div>
        <div class="right-info">
            <p style="font-weight:bold; margin-right:129px;">ທີ່ຢູ່ ບ້ານໂພນຕ້ອງ</p>
            <p style="font-weight:bold;">ເມືອງ ຈັນທະບູລີ ແຂວງ ນະຄອນຫຼວງວຽງຈັນ</p>
            <p>ເລກທີ:<span class="dotted"><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></span></p>
            <p>ວັນທີ:<span class="dotted"><?php echo date('d/m/Y'); ?></span></p>
        </div>
    </div>

    <table class="receipt-table">
        <thead>
            <tr>
                <th>ເລກທີ່ໃບບິນ</th>
                <th>ວັນທີຊື້</th>
                <th>ຊື່ຜູ້ສະໜອງ</th>
                <th>ພະນັກງານ</th>
                <th>ຈຳນວນລາຍການ</th>
                <th>ຍອດລວມ</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (!empty($expenses)):
                $grandTotal = 0;
                foreach ($expenses as $expense):
                    $grandTotal += $expense['total_cost'];
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($expense['po_id']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($expense['date'])); ?></td>
                    <td class="text-left"><?php echo htmlspecialchars($expense['sup_name'] ?? 'N/A'); ?></td>
                    <td class="text-left"><?php echo htmlspecialchars($expense['emp_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($expense['total_items']); ?></td>
                    <td><?php echo laoNumberFormat($expense['total_cost']); ?></td>
                </tr>
            <?php 
                endforeach;
            ?>
                <tr>
                    <td colspan="5" style="text-align:right;font-weight:bold;">ລາຍຈ່າຍລວມທັງໝົດ</td>
                    <td style="font-weight:bold;"><?php echo laoNumberFormat($grandTotal); ?></td>
                </tr>
            <?php
            else: 
            ?>
                <tr>
                    <td colspan="6">ບໍ່ມີຂໍ້ມູນລາຍຈ່າຍ.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="receipt-footer">
        <div style="text-align:center; font-weight:bold;">
            <p>ເຈົ້າຂອງຮ້ານ<br>Owner</p>
        </div>
        <div style="text-align:center; font-weight:bold;">
            <p>ຜູ້ກວດສອບ<br>Auditor</p>
        </div>
        <div style="text-align:center;">
            <p style="text-align:right;font-style:italic;">ຂອບໃຈທີ່ໃຊ້ບໍລິການ</p>
        </div>
    </div>
</div>

</body>
</html>
