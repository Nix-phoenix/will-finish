<?php
include 'includes/auth.php';
include 'db/connection.php';

$id = intval($_GET['id'] ?? 0);
if($id<=0){ echo 'Invalid ID'; exit; }

// fetch existing
$stmt=$conn->prepare('SELECT emp_name,tel,email,address FROM Employee WHERE emp_id=? LIMIT 1');
$stmt->bind_param('i',$id);
$stmt->execute();
$res=$stmt->get_result();
$data=$res->fetch_assoc();
$stmt->close();
if(!$data){ echo 'Record not found'; exit; }

if($_SERVER['REQUEST_METHOD']==='POST'){
    $name = trim($_POST['staffName'] ?? '');
    $tel  = trim($_POST['staffPhone'] ?? '');
    $email= trim($_POST['staffEmail'] ?? '');
    $addr = trim($_POST['staffAddress'] ?? '');
    if($name && $tel && $email && $addr){
        $stmt=$conn->prepare('UPDATE Employee SET emp_name=?,tel=?,email=?,address=? WHERE emp_id=?');
        $stmt->bind_param('ssssi',$name,$tel,$email,$addr,$id);
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
<title>ແກ້ໄຂພະນັກງານ</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="padding:20px;font-family:'Noto Sans Lao',sans-serif;">
<h3>ແກ້ໄຂຂໍ້ມູນພະນັກງານ</h3>
<?php if(!empty($msg)) echo '<p style="color:red;">'.$msg.'</p>'; ?>
<form method="post">
<label>ຊື່ ແລະ ນາມສະກຸນ:</label><br>
<input type="text" name="staffName" class="gpg-input" value="<?php echo htmlspecialchars($data['emp_name']); ?>" required><br>
<label>ເບີໂທ:</label><br>
<input type="text" name="staffPhone" class="gpg-input" value="<?php echo htmlspecialchars($data['tel']); ?>" required><br>
<label>ອີເມລ:</label><br>
<input type="email" name="staffEmail" class="gpg-input" value="<?php echo htmlspecialchars($data['email']); ?>" required><br>
<label>ທີ່ຢູ່:</label><br>
<textarea name="staffAddress" rows="3" class="gpg-input" required><?php echo htmlspecialchars($data['address']); ?></textarea><br><br>
<button type="submit" class="dashboard-add-btn">ບັນທຶກ</button>
</form>
</body>
</html>
