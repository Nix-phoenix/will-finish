<?php
// Include auth functions to check user role
if (!isset($_SESSION)) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="gpg-header">
        <h1 class="gpg-navbar-title">ລະບົບຈັດການຮ້ານGPG</h1>
        <?php if (isset($_SESSION['emp_name'])): ?>
            <div style="position: absolute; top: 10px; right: 20px; color: white; font-size: 14px;">
                Welcome, <?php echo htmlspecialchars($_SESSION['emp_name']); ?> 
                (<?php echo htmlspecialchars($_SESSION['role'] ?? 'Unknown'); ?>)
            </div>
        <?php endif; ?>
    </header>
    <nav class="gpg-navbar">
        <ul>
            <li><a href="index.php">Homepage</a></li>
            <li class="dropdown">
                <a href="#" class="dropbtn">ການຈັດການຂໍ້ມູນ</a>
                <div class="dropdown-content">
                    <a href="customer_data.php">ບັນທືກຂໍ້ມູນລູກຄ້າ</a>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="staff_data.php">ບັນທືກຂໍ້ມູນພະນັກງານ</a>
                    <?php endif; ?>
                    <a href="supplier_data.php">ບັນທືກຂໍ້ມູນຜູ້ສະຫນອງ</a>
                    <a href="product_data.php">ບັນທືກຂໍ້ມູນສິນຄ້າ</a>
                    <a href="product_unit_data.php">ບັນທຶກຂໍ້ມູນຫົວໜ່ວຍສິນຄ້າ</a>
                    <a href="product_band_data.php">ບັນທຶກຂໍ້ມູນຍີ່ຫໍ້ສິນຄ້າ</a>
                    <a href="product_shelf_data.php">ບັນທຶກຂໍ້ມູນຖ້ານວາງສິນຄ້າ</a>
                    <a href="product_type_data.php">ບັນທຶກປະເພດຂໍ້ມູນສິນຄ້າ</a>
                    <a href="Import_data.php">ບັນທຶກສິນຄ້ານຳເຂົ້າ</a>
                </div>
            </li>
            <li class="dropdown">
                <a href="#" class="dropbtn">ຈັດການການສັ່ງຊື້-ຂາຍ</a>
                <div class="dropdown-content">
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="product_ordering.php">ຈັດການການສັ່ງຊື້ສິນຄ້າ</a>
                        <a href="product_import.php">ຈັດການສິນຄ້ານຳເຂົ້າ</a>
                    <?php endif; ?>
                    <a href="payment.php">ຈັດການການຊຳລະ</a>
                    <a href="sale.php">ຈັດການຂາຍສິນຄ້າ</a>
                </div>
            </li>
            <a href="warehouse.php"></a>
            <li class="dropdown">
                <a href="#" class="dropbtn">ລາຍງານ</a>
                <div class="dropdown-content">
                    <a href="invoice_list.php">ລາຍການໃບບິນ</a> 
                    <a href="sales_report.php">ລາຍງານການຂາຍ</a> 
                    <a href="product_report.php">ລາຍງານສິນຄ້າ</a> 
                    <a href="expense_report.php">ລາຍງານລາຍຈ່າຍ</a>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="order_report.php">ລາຍງານການສັ່ງຊື້ສິນຄ້າ</a>
                        <a href="subproduct_report.php">ລາຍງານໃບບິນນໍາເຂົ້າສິນຄ້າ</a>
                    <?php endif; ?>
                    <a href="payment_report.php">ລາຍງານການຊໍາລະ</a>
                    <a href="customer_report.php">ລາຍງານຂໍ້ມູນລູກຄ້າ</a>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="staff_report.php">ລາຍງານຂໍ້ມູນພະນັກງານ</a>
                    <?php endif; ?>
                    <a href="supplier_report.php">ລາຍງານຂໍ້ມູນຜູ້ສະຫນອງ</a>
                    <a href="receipt_report.php">ລາຍງານລາຍຮັບ</a>
                </div>
            </li>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li style="float:right"><a href="register.php">ເພີ່ມຜູ້ໃຊ້</a></li>
            <?php endif; ?>
            <li style="float:right"><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
</body>
</html>