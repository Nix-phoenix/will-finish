<?php
/* =============================================
 *  Invoice / Receipt Page
 *  ---------------------------------------------
 *  Expects ?id=<invoice_id> in the query string.
 *  Pulls invoice details + items from DB and renders a printable receipt.
 *  "Print" button is hidden when printing via @media print.
 *  ---------------------------------------------
 */
include 'includes/auth.php';
include 'db/connection.php';

$invoiceId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($invoiceId === 0) {
    die('Invoice ID not provided');
}

/* -------------------------------------------------
 *  Fetch invoice header & items from database schema defined in sql/create_database.sql
 *  -------------------------------------------------
 *  Sell            (s_id, c_id, emp_id, date)
 *  SellDetail      (sd_id, s_id, p_id, qty, price, total_price)
 *  Customer        (c_id, c_name, address, tel)
 *  Employee        (emp_id, emp_name)
 *  Product         (p_id, p_name)
 * Payment          (pm_id, s_id, amount, date, customer_paid)
 * --------------------------------------------------*/

// Fetch invoice header and payment details
$invoiceSql = "SELECT s.s_id, s.date, c.c_name, c.tel AS customer_tel, c.address AS customer_address, e.emp_name AS cashier_name, p.customer_paid
               FROM Sell s
               LEFT JOIN Customer c ON s.c_id = c.c_id
               LEFT JOIN Employee e ON s.emp_id = e.emp_id
               LEFT JOIN Payment p ON s.s_id = p.s_id
               WHERE s.s_id = ? LIMIT 1";

// Fetch invoice items
$itemSql    = "SELECT sd.qty, sd.price, p.p_name
               FROM SellDetail sd
               JOIN Product p ON sd.p_id = p.p_id
               WHERE sd.s_id = ?";

$invoiceStmt = $conn->prepare($invoiceSql);
$invoiceStmt->bind_param('i', $invoiceId);
$invoiceStmt->execute();
$invoiceRes  = $invoiceStmt->get_result();
$invoice     = $invoiceRes->fetch_assoc();
if (!$invoice) {
    die('Invoice not found');
}

$itemStmt = $conn->prepare($itemSql);
$itemStmt->bind_param('i', $invoiceId);
$itemStmt->execute();
$itemsRes = $itemStmt->get_result();
$items    = $itemsRes->fetch_all(MYSQLI_ASSOC);

function laoNumberFormat($value) {
    return number_format($value, 0, '.', ',') . ' ກິບ';
}

$totalAmount = 0;
foreach ($items as $it) {
    $totalAmount += ($it['qty'] * $it['price']);
}

$customerPaid = $invoice['customer_paid'] ?? 0;
$change = $customerPaid - $totalAmount;
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ໃບຮັບເງິນ / Receipt</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            width: 200px;
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
<div class="container no-print" style="text-align:right;margin-top:20px;">
    <button class="Print-btn" onclick="window.print();">ພິມໃບບິນ</button>
</div>
    <h2 class="receipt-title">ໃບຮັບເງິນ <br> Receipt</h2>

    <div class="receipt-header">
        <div class="store-info">
            <h3>ຮ້ານ ຈິພິຈີ</h3>
            <p style="font-weight:bold;">ສະຫນອງອຸປະປະກອນແລະທໍ່ນໍ້າປະປາທຸກຊະນິດ<br>
            <p style="font-weight:bold; margin-left:50px;">ໂທ 020-58828288</p>
            <p style="font-weight:bold;">WhatsApp 030-5656555<br>Facebook @gpqlaosstore</p>
        </div>
        <div class="right-info">
            <p style="font-weight:bold; margin-right:129px;">ທີ່ຢູ່ ບ້ານໂພນຕ້ອງ</p>
            <p style="font-weight:bold;">ເມືອງ ຈັນທະບູລີ ແຂວງ ນະຄອນຫຼວງວຽງຈັນ</p>
            <p>ເລກທີ:<span class="dotted"><?php echo $invoice['s_id']; ?></span></p>
            <p>ວັນທີ:<span class="dotted"><?php echo date('d/m/Y', strtotime($invoice['date'])); ?></span></p>
        </div>
    </div>

    <p style="margin-top:12px;">ຊື່ລູກຄ້າ: <?php echo htmlspecialchars($invoice['c_name'] ?? '-'); ?> &nbsp;&nbsp;ເບີໂທ: <?php echo htmlspecialchars($invoice['customer_tel'] ?? '-'); ?> &nbsp;&nbsp;ທີ່ຢູ່: <?php echo htmlspecialchars($invoice['customer_address'] ?? '-'); ?></p>

    <table class="receipt-table">
        <thead>
            <tr>
                <th>ລ.ດ NO</th>
                <th>ລາຍການ Description</th>
                <th>ຈຳນວນ Quantity</th>
                <th>ລາຄາ Price</th>
                <th>ຈຳນວນເງິນ Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $index => $it): ?>
            <tr>
                <td><?php echo $index + 1; ?></td>
                <td><?php echo htmlspecialchars($it['p_name']); ?></td>
                <td><?php echo $it['qty']; ?></td>
                <td><?php echo laoNumberFormat($it['price']); ?></td>
                <td><?php echo laoNumberFormat($it['qty'] * $it['price']); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="4" style="text-align:right;font-weight:bold;">ລາຄາລວມ Total</td>
                <td><?php echo laoNumberFormat($totalAmount); ?></td>
            </tr>
            <tr>
                <td colspan="4" style="text-align:right;font-weight:bold;">ຈຳນວນເງິນທີ່ຈ່າຍ Amount Paid</td>
                <td><?php echo laoNumberFormat($customerPaid); ?></td>
            </tr>
            <tr>
                <td colspan="4" style="text-align:right;font-weight:bold;">ເງິນທອນ Change</td>
                <td><?php echo laoNumberFormat($change); ?></td>
            </tr>
        </tbody>
    </table>

    <div class="receipt-footer">
        <div style="text-align:center; font-weight:bold;">
            <p>ຜູ້ຮັບງິນ<br>Cashier</p>
            <p><?php echo htmlspecialchars($invoice['cashier_name'] ?? '-'); ?></p>
        </div>
        <div style="text-align:center; font-weight:bold;">
            <p>ຜູ້ຈ່າຍງິນ<br>Payer</p>
            <br>
        </div>
        <div style="text-align:center;">
        <p style="text-align:right;font-style:italic;">ຂອບໃຈທີ່ໃຊ້ບໍລິການ</p>
        </div>
    </div>
</div>

</body>
</html>