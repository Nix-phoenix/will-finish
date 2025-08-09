<?php
session_start();
require_once 'includes/auth.php';
require_once 'db/connection.php';

// Sanitize and get search query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$like_search = "%$search%";

// Fetch sales data and total payments for each sale.
$sql = "SELECT
            s.s_id,
            c.c_name,
            GROUP_CONCAT(DISTINCT p.p_name SEPARATOR ', ') as products,
            SUM(sd.qty) AS total_qty,
            SUM(sd.total_price) AS total_sale_price,
            COALESCE(pm.amount, 0) AS customer_paid,
            (SUM(sd.total_price) - COALESCE(pm.amount, 0)) AS balance_due
        FROM Sell s
        JOIN SellDetail sd ON s.s_id = sd.s_id
        JOIN Product p ON sd.p_id = p.p_id
        LEFT JOIN Customer c ON s.c_id = c.c_id
        LEFT JOIN Payment pm ON s.s_id = pm.s_id";

if (!empty($search)) {
    $sql .= " WHERE s.s_id LIKE ? OR c.c_name LIKE ?";
}

$sql .= " GROUP BY s.s_id, c.c_name, pm.amount ORDER BY s.s_id DESC";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

if (!empty($search)) {
    $stmt->bind_param('ss', $like_search, $like_search);
}
$stmt->execute();
$sales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Helper function for Lao number formatting
function laoNumberFormat($value) {
    $numeric_value = is_numeric($value) ? (float)$value : 0;
    return number_format($numeric_value, 0, '.', ',') . ' ກິບ';
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ລາຍງານການຈັດການການຊຳລະ</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        @media print {
            .no-print, .gpg-navbar { display: none !important; }
            body { margin: 0; }
        }
        body { background-color: #f0f2f5; font-family: 'Noto Sans Lao', sans-serif; }
        .receipt-container {
            max-width: 900px; /* Reduced max-width */
            margin: 10px auto; /* Reduced margin */
            background: #fff;
            padding: 10px 20px; /* Reduced padding */
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .receipt-title {
            text-align: center;
            font-size: 24px; /* Reduced font size */
            font-weight: 700;
            margin-bottom: 2px; /* Reduced margin */
        }
        .receipt-header {
            display: flex;
            justify-content: space-between;
            margin-top: 5px; /* Reduced margin */
        }
        .receipt-header .store-name {
            font-size: 18px; /* New class for smaller header */
            font-weight: 700;
            margin: 0;
        }
        .store-info p{margin:0;font-size:12px; /* Reduced font size */}
        .right-info{ text-align:right; font-size:12px; /* Reduced font size */}
        .dotted{display:inline-block;min-width:100px;border-bottom:1px dotted #000;margin-left:6px;}
        .receipt-table, .receipt-table th, .receipt-table td {
            border: 1px solid #000;
            border-collapse: collapse;
        }
        .receipt-table {
            width: 100%;
            margin-top: 10px; /* Reduced margin */
        }
        .receipt-table th, .receipt-table td {
            padding: 4px 2px; /* Reduced padding */
            text-align: center;
            font-size: 10px; /* Reduced font size */
        }
        .receipt-table td.text-left {
            text-align: left;
        }
        .receipt-footer {
            margin-top: 16px; /* Reduced margin */
            display: flex;
            justify-content: space-between;
        }
        .Print-btn {
            background: #4caf50;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 8px 16px; /* Reduced padding */
            font-size: 0.9rem; /* Reduced font size */
            cursor: pointer;
            transition: background 0.2s;
        }
        .Print-btn:hover { background: #388e3c; }
        .search-form { display: flex; gap: 10px; margin-bottom: 8px; margin-top: 10px; } /* Reduced margin */
        .search-input { flex-grow: 1; padding: 6px 10px; border: 1px solid #ccc; border-radius: 6px; } /* Reduced padding */
        .btn-green { background-color: #28a745; color: white; padding: 6px 12px; border-radius: 6px; border: none; cursor: pointer; font-weight: bold; } /* Reduced padding */
        .balance-due-positive { color: #dc3545; font-weight: bold; }
        .balance-due-zero { color: #28a745; font-weight: bold; }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="receipt-container">
    <div class="container no-print" style="text-align:right;margin-top:10px;">
        <button class="Print-btn" onclick="window.print();">ພິມລາຍງານ</button>
    </div>

    <h2 class="receipt-title">ໃບລາຍງານການຈັດການການຊຳລະ<br>Payment Management Report Receipt</h2>

    <div class="receipt-header">
        <div class="store-info">
            <p class="store-name">ຮ້ານ ຈິພິຈີ</p>
            <p style="font-weight:bold;">ສະຫນອງອຸປະປະກອນແລະທໍ່ນໍ້າປະປາທຸກຊະນິດ</p>
            <p style="font-weight:bold;">ໂທ 020-58828288</p>
            <p style="font-weight:bold;">WhatsApp 030-5656555<br>Facebook @gpglaosstore</p>
        </div>
        <div class="right-info">
            <p style="font-weight:bold; margin-right:129px;">ທີ່ຢູ່ ບ້ານໂພນຕ້ອງ</p>
            <p style="font-weight:bold;">ເມືອງ ຈັນທະບູລີ ແຂວງ ນະຄອນຫຼວງວຽງຈັນ</p>
            <p>ເລກທີ:<span class="dotted"><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></span></p>
            <p>ວັນທີ:<span class="dotted"><?php echo date('d/m/Y'); ?></span></p>
        </div>
    </div>
    <p style="margin-top:8px; font-size:12px;">ທີ່ຢູ່: ບ້ານ ນາຄວາຍ, ເມືອງ ສີສັດຕະນາກ, ນະຄອນຫຼວງວຽງຈັນ</p>
    
    <form method="get" class="search-form no-print">
        <input type="text" name="search" class="search-input" placeholder="ຄົ້ນຫາ (ເລກທີບິນ, ລູກຄ້າ)" value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn-green">ຄົ້ນຫາ</button>
    </form>

    <table class="receipt-table">
        <thead>
            <tr>
                <th>ເລກທີ່ຊຳລະ</th>
                <th>ຊື່ລູກຄ້າ</th>
                <th>ຊື່ສິນຄ້າ</th>
                <th>ຈຳນວນສິນຄ້າ</th>
                <th>ລາຄາລວມທັງໝົດ</th>
                <th>ຈຳນວນເງິນລູກຄ້າຈ່າຍ</th>
                <th>ຍອດເຫຼືອ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($sales)): ?>
                <?php foreach ($sales as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['s_id']); ?></td>
                        <td class="text-left"><?php echo htmlspecialchars($row['c_name'] ?? 'Walk-in'); ?></td>
                        <td class="text-left"><?php echo htmlspecialchars($row['products']); ?></td>
                        <td><?php echo htmlspecialchars($row['total_qty']); ?></td>
                        <td><?php echo laoNumberFormat($row['total_sale_price']); ?></td>
                        <td><?php echo laoNumberFormat($row['customer_paid'] ?? 0); ?></td>
                        <td class="<?php echo ($row['balance_due'] > 0) ? 'balance-due-positive' : 'balance-due-zero'; ?>">
                            <?php echo laoNumberFormat($row['balance_due']); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">ບໍ່ມີຂໍ້ມູນ</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="receipt-footer">
        <div style="text-align:center; font-weight:bold;">
            <p>ເຈົ້າຂອງຮ້ານ<br>Owner</p>
        </div>
        <div style="text-align:center; font-weight:bold;">
            <p>ຜູ້ຮັບເງິນ<br>Cashier</p>
        </div>
        <div style="text-align:center;">
            <p style="text-align:right;font-style:italic;">ຂອບໃຈທີ່ໃຊ້ບໍລິການ</p>
        </div>
    </div>
</div>

</body>
</html>
