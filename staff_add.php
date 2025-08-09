<?php
include 'includes/auth.php';
include 'db/connection.php';

if ($_SERVER['REQUEST_METHOD']==='POST'){
    $name = trim($_POST['staffName'] ?? '');
    $tel  = trim($_POST['staffPhone'] ?? '');
    $email= trim($_POST['staffEmail'] ?? '');
    $addr = trim($_POST['staffAddress'] ?? '');
    if($name && $tel && $email && $addr){
        $stmt=$conn->prepare('INSERT INTO Employee(emp_name,tel,email,address) VALUES(?,?,?,?)');
        $stmt->bind_param('ssss',$name,$tel,$email,$addr);
        $stmt->execute();
        $stmt->close();
        echo "<script>window.opener.location.reload();window.close();</script>";
        exit;
    }
    $msg='ກະລຸນາກຽບກອບໃຫ້ຄົບ';
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
<meta charset="utf-8">
<title>ເພີ່ມພະນັກງານ</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="padding:20px;font-family:'Noto Sans Lao',sans-serif;">
<h3>ເພີ່ມຂໍ້ມູນພະນັກງານ</h3>
<?php if(!empty($msg)) echo '<p style="color:red;">'.$msg.'</p>'; ?>
<form method="post">
<label>ຊື່ ແລະ ນາມສະກຸນ:</label><br>
<input type="text" name="staffName" class="gpg-input" required><br>
<label>ເບີໂທ:</label><br>
<input type="text" name="staffPhone" class="gpg-input" required><br>
<label>ອີເມລ:</label><br>
<input type="email" name="staffEmail" class="gpg-input" required><br>
<label>ທີ່ຢູ່:</label><br>
<textarea name="staffAddress" rows="3" class="gpg-input" required></textarea><br><br>
<button type="submit" class="dashboard-add-btn">ບັນທຶກ</button>
</form>
</body>
</html>
