<?php
session_start();
include 'db/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $emp_name = $_POST['emp_name'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM Employee WHERE emp_name = ? AND password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $emp_name, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['emp_id'] = $user['emp_id'];
        $_SESSION['emp_name'] = $user['emp_name'];
        $_SESSION['role'] = $user['role'];
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid credentials";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Store Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;700&display=swap" rel="stylesheet">
</head>
<body class="gpg-bg">
    <div class="center-container">
        <div class="gpg-card">
            <h1 class="gpg-title">Welcome to GPG Store</h1>
            <h2 class="gpg-login-title">Login</h2>
            <form method="post" action="">
                <input type="text" name="emp_name" placeholder="Username" class="gpg-input" required><br>
                <input type="password" name="password" placeholder="Password" class="gpg-input" required><br>
                <button type="submit" class="gpg-btn">Login</button>
                <?php if(isset($error)) { echo "<p class='error'>$error</p>"; } ?>
            </form>
            <p class="gpg-register-text">
                Don't have an account? <a href="register.php" class="gpg-register-link">Register here</a>
            </p>
        </div>
    </div>
</body>
</html>