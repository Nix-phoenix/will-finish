<?php
require_once 'includes/auth.php';
require_once 'db/connection.php';

// Fetch payment data from the database with corrected query
$payments = [];
$sql = "SELECT
            s.s_id,
            c.c_name,
            GROUP_CONCAT(p.p_name SEPARATOR ', ') as products,
            SUM(sd.qty) as total_qty,
            (SELECT SUM(total_price) FROM SellDetail WHERE s_id = s.s_id) AS price_total,
            pm.customer_paid AS amount_paid,
            ((SELECT SUM(total_price) FROM SellDetail WHERE s_id = s.s_id) - pm.customer_paid) AS due_amount
        FROM Sell s
        JOIN SellDetail sd ON s.s_id = sd.s_id
        JOIN Product p ON sd.p_id = p.p_id
        LEFT JOIN Customer c ON s.c_id = c.c_id
        LEFT JOIN Payment pm ON s.s_id = pm.s_id
        GROUP BY s.s_id
        ORDER BY s.date DESC";

$result = $conn->query($sql);

if ($result === false) {
    die("Database query failed: " . $conn->error);
}

if ($result->num_rows > 0) {
    $payments = $result->fetch_all(MYSQLI_ASSOC);
}

if ($result) {
    $result->free();
}

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
    <div class="container no-print" style="text-align:right;margin-top:20px;">
        <button class="Print-btn" onclick="window.print();">ພິມລາຍງານ</button>
    </div>
    
    <h2 class="receipt-title">ໃບລາຍງານການຈັດການການຊຳລະ<br>Payment Management Report Receipt</h2>

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
    <p style="margin-top:12px;">ທີ່ຢູ່: ບ້ານ ນາຄວາຍ, ເມືອງ ສີສັດຕະນາກ, ນະຄອນຫຼວງວຽງຈັນ</p>

    <table class="receipt-table">
        <thead>
            <tr>
                <th>ເລກທີ່ບິນ</th>
                <th>ຊື່ລູກຄ້າ</th>
                <th>ຊື່ສິນຄ້າ</th>
                <th>ຈຳນວນ</th>
                <th>ລາຄາທັງໝົດ</th>
                <th>ຈຳນວນລູກຄ້າຈ່າຍ</th>
                <th>ຍອດເຫຼືອ</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (!empty($payments)):
                foreach ($payments as $payment):
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($payment['s_id']); ?></td>
                    <td class="text-left"><?php echo htmlspecialchars($payment['c_name'] ?? 'N/A'); ?></td>
                    <td class="text-left"><?php echo htmlspecialchars($payment['products']); ?></td>
                    <td><?php echo htmlspecialchars($payment['total_qty']); ?></td>
                    <td><?php echo laoNumberFormat($payment['price_total']); ?></td>
                    <td><?php echo laoNumberFormat($payment['amount_paid'] ?? 0); ?></td>
                    <td><?php echo laoNumberFormat($payment['due_amount'] ?? $payment['price_total']); ?></td>
                </tr>
            <?php 
                endforeach;
            else: 
            ?>
                <tr>
                    <td colspan="7">ບໍ່ມີຂໍ້ມູນການຊຳລະ.</td>
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
<?php
require_once 'includes/auth.php';
require_once 'db/connection.php';

// Start a session to use for flash messages
session_start();

// Handle the different actions requested by the front-end page
if (isset($_REQUEST['action'])) {
    $action = $_REQUEST['action'];
    $conn->begin_transaction();

    try {
        switch ($action) {
            case 'update_customer_paid':
                // Update the payment amount for a sale.
                // This assumes one payment record per sale for simplicity.
                if (isset($_POST['sid']) && isset($_POST['customerPaid'])) {
                    $s_id = $_POST['sid'];
                    $customer_paid = $_POST['customerPaid'];

                    // First, delete any existing payment records for this sale.
                    // This handles updates cleanly as the front-end provides a new total.
                    $sql_delete = "DELETE FROM Payment WHERE s_id = ?";
                    $stmt_delete = $conn->prepare($sql_delete);
                    $stmt_delete->bind_param('i', $s_id);
                    $stmt_delete->execute();
                    $stmt_delete->close();

                    // Then, insert the new payment amount.
                    $sql_insert = "INSERT INTO Payment (s_id, customer_paid) VALUES (?, ?)";
                    $stmt_insert = $conn->prepare($sql_insert);
                    $stmt_insert->bind_param('id', $s_id, $customer_paid);
                    $stmt_insert->execute();
                    $stmt_insert->close();

                    $conn->commit();
                    $_SESSION['success_message'] = "ການຊຳລະສຳລັບເລກທີ $s_id ໄດ້ຖືກອັບເດດແລ້ວ.";
                }
                break;

            case 'delete_sale':
                // Delete a full sale, including all related details and payments.
                if (isset($_GET['sid'])) {
                    $s_id = $_GET['sid'];

                    // It's crucial to delete in the correct order to respect foreign key constraints.
                    // 1. Delete payments
                    $sql_delete_payment = "DELETE FROM Payment WHERE s_id = ?";
                    $stmt_payment = $conn->prepare($sql_delete_payment);
                    $stmt_payment->bind_param('i', $s_id);
                    $stmt_payment->execute();
                    $stmt_payment->close();

                    // 2. Delete sell details
                    $sql_delete_details = "DELETE FROM SellDetail WHERE s_id = ?";
                    $stmt_details = $conn->prepare($sql_delete_details);
                    $stmt_details->bind_param('i', $s_id);
                    $stmt_details->execute();
                    $stmt_details->close();

                    // 3. Delete the sale itself
                    $sql_delete_sale = "DELETE FROM Sell WHERE s_id = ?";
                    $stmt_sale = $conn->prepare($sql_delete_sale);
                    $stmt_sale->bind_param('i', $s_id);
                    $stmt_sale->execute();
                    $stmt_sale->close();

                    $conn->commit();
                    $_SESSION['success_message'] = "ລາຍການຂາຍເລກທີ $s_id ຖືກລົບອອກສຳເລັດແລ້ວ.";
                }
                break;
            default:
                $_SESSION['error_message'] = "ການກະທຳທີ່ບໍ່ຖືກຕ້ອງ.";
                break;
        }
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "ເກີດຂໍ້ຜິດພາດ: " . $e->getMessage();
    }
}

// Redirect back to the payment management page
header("Location: payment.php");
exit();
?>
