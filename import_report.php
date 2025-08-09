<?php
require_once 'includes/auth.php';
require_once 'db/connection.php';

// Fetch product import data from the database
$imports = [];
$sql = "SELECT 
            po.po_id,
            s.sup_name,
            p.p_name,
            p.unit,
            pod.qty,
            pod.price,
            po.date AS import_date
        FROM PurchaseOrder po
        JOIN PurchaseOrderDetail pod ON po.po_id = pod.po_id
        JOIN Product p ON pod.p_id = p.p_id
        LEFT JOIN Supplier s ON po.sup_id = s.sup_id
        ORDER BY po.date DESC";

$result = $conn->query($sql);

if ($result === false) {
    die("Database query failed: " . $conn->error);
}

if ($result->num_rows > 0) {
    $imports = $result->fetch_all(MYSQLI_ASSOC);
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
    <title>ລາຍງານການນໍາເຂົ້າສິນຄ້າ</title>
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
            font-size: 11px;
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
    
    <h2 class="receipt-title">ໃບລາຍງານການນຳເຂົ້າສິນຄ້າ<br>Product Import Report Receipt</h2>

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
                <th>ເລກທີ່ນຳເຂົ້າ</th>
                <th>ຊື່ຜູ້ສະໜອງ</th>
                <th>ພະນັກງານຜູ້ຮັບຜິດຊອບ</th>
                <th>ຊື່ສິນຄ້າ</th>
                <th>ຫົວໜ່ວຍ</th>
                <th>ຈຳນວນ</th>
                <th>ລາຄາຕໍ່ອັນ</th>
                <th>ວັນທີນຳເຂົ້າ</th>
                <th>ລາຄາລວມ</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (!empty($imports)):
                $grandTotal = 0;
                foreach ($imports as $import):
                    $totalPrice = (float)$import['price'] * (int)$import['qty'];
                    $grandTotal += $totalPrice;
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($import['po_id']); ?></td>
                    <td class="text-left"><?php echo htmlspecialchars($import['sup_name'] ?? 'N/A'); ?></td>
                    <td class="text-left"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'N/A'); ?></td>
                    <td class="text-left"><?php echo htmlspecialchars($import['p_name']); ?></td>
                    <td><?php echo htmlspecialchars($import['unit']); ?></td>
                    <td><?php echo htmlspecialchars($import['qty']); ?></td>
                    <td><?php echo laoNumberFormat($import['price']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($import['import_date'])); ?></td>
                    <td><?php echo laoNumberFormat($totalPrice); ?></td>
                </tr>
            <?php 
                endforeach;
            ?>
                <tr>
                    <td colspan="8" style="text-align:right;font-weight:bold;">ຍອດລວມທັງໝົດ</td>
                    <td style="font-weight:bold;"><?php echo laoNumberFormat($grandTotal); ?></td>
                </tr>
            <?php
            else: 
            ?>
                <tr>
                    <td colspan="9">ບໍ່ມີຂໍ້ມູນການນຳເຂົ້າສິນຄ້າ.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="receipt-footer">
        <div style="text-align:center; font-weight:bold;">
            <p>ເຈົ້າຂອງຮ້ານ<br>Owner</p>
        </div>
        <div style="text-align:center; font-weight:bold;">
            <p>ຜູ້ສະໜອງ<br>Supplier</p>
        </div>
        <div style="text-align:center;">
            <p style="text-align:right;font-style:italic;">ຂອບໃຈທີ່ໃຊ້ບໍລິການ</p>
        </div>
    </div>
</div>

</body>
</html>