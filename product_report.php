<?php
// includes/auth.php securely starts the session
require_once 'includes/auth.php';
require_once 'db/connection.php'; // This file is assumed to use mysqli

// Fetch product data from the database using mysqli
$products = [];

// The SQL query is updated to use the correct column names from your database schema.
$sql = "SELECT 
            p.p_id, 
            p.p_name, 
            p.price, 
            p.qty,
            pt.pt_name AS type,
            pb.pb_name AS brand,
            punit.punit_name AS unit,
            pslf.pslf_location AS shelf
        FROM Product AS p
        LEFT JOIN ProductType AS pt ON p.pt_id = pt.pt_id
        LEFT JOIN ProductBrand AS pb ON p.pb_id = pb.pb_id
        LEFT JOIN ProductUnit AS punit ON p.punit_id = punit.punit_id
        LEFT JOIN ProductShelf AS pslf ON p.pslf_id = pslf.pslf_id
        ORDER BY p.p_id ASC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    // Fetch all results into an associative array
    $products = $result->fetch_all(MYSQLI_ASSOC);
} elseif ($conn->error) {
    // Handle database errors gracefully
    die("Database query failed: " . $conn->error);
}

// It's good practice to free the result set
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
    <title>ໃບລາຍງານບັນທືກສິນຄ້າ - Product Report</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Hide elements with class no-print during printing */
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
        .receipt-subtitle {
            text-align: center;
            font-size: 22px;
            margin: 0;
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
            font-family: 'Noto Sans Lao', Arial, sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            display: inline-block;
            text-align: center;
        }
        .Print-btn:hover {
            background: #388e3c;
        }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="receipt-container">
    <div class="container no-print" style="text-align:left;margin-top:20px;">
        <button class="Print-btn" onclick="window.print();">ພິມລາຍງານ</button>
    </div>
    
    <h2 class="receipt-title">ໃບລາຍງານສິນຄ້າ<br>Product Report</h2>

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
            <p>ຜູ້ພິມ:<span class="dotted"><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></span></p>
            <p>ວັນທີ:<span class="dotted"><?php echo date('d/m/Y'); ?></span></p>
        </div>
    </div>

    <table class="receipt-table">
        <thead>
            <tr>
                <th>ລ.ດ NO</th>
                <th>ຊື່ສິນຄ້າ</th>
                <th>ປະເພດ</th>
                <th>ຍີ່ຫໍ້</th> <!-- New column for Brand -->
                <th>ຫົວໜ່ວຍ</th>
                <th>ລາຄາ</th>
                <th>ຈຳນວນຄົງເຫຼືອ</th>
                <th>ມູນຄ່າລວມ</th>
                <th>ບ່ອນເກັບ</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $grandTotalValue = 0;
            foreach ($products as $index => $product): 
                $totalValue = (float)$product['price'] * (int)$product['qty'];
                $grandTotalValue += $totalValue;
            ?>
            <tr>
                <td><?php echo $index + 1; ?></td>
                <td class="text-left"><?php echo htmlspecialchars($product['p_name']); ?></td>
                <td><?php echo htmlspecialchars($product['type']); ?></td>
                <td><?php echo htmlspecialchars($product['brand']); ?></td> <!-- Display brand here -->
                <td><?php echo htmlspecialchars($product['unit']); ?></td>
                <td><?php echo laoNumberFormat($product['price']); ?></td>
                <td><?php echo htmlspecialchars($product['qty']); ?></td>
                <td><?php echo laoNumberFormat($totalValue); ?></td>
                <td><?php echo htmlspecialchars($product['shelf']); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="9">ບໍ່ມີຂໍ້ມູນສິນຄ້າໃນລະບົບ.</td>
                </tr>
            <?php else: ?>
                <tr>
                    <!-- The colspan is adjusted to 7 to accommodate the new brand column -->
                    <td colspan="7" style="text-align:right;font-weight:bold;">ມູນຄ່າສິນຄ້າທັງໝົດ</td>
                    <td colspan="2" style="font-weight:bold;"><?php echo laoNumberFormat($grandTotalValue); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="receipt-footer">
        <div style="text-align:center; font-weight:bold;">
            <p>ຜູ້ກວດສອບ<br>Auditor</p>
        </div>
        <div style="text-align:center; font-weight:bold;">
            <p>ຜູ້ຈັດການ<br>Manager</p>
        </div>
        <div style="text-align:center;">
            <p style="text-align:right;font-style:italic;">ຂອບໃຈທີ່ໃຊ້ບໍລິການ</p>
        </div>
    </div>
</div>

</body>
</html>
