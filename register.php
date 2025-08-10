<?php
session_start();
include 'includes/auth.php';
include 'db/connection.php';

// Check if the user is logged in and has the 'admin' role
requireAdmin();

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $emp_name = trim($_POST['emp_name']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $email = trim($_POST['email']);
    $tel = trim($_POST['tel']);
    $address = trim($_POST['address']);

    // Validate inputs
    if (empty($emp_name) || empty($password) || empty($confirm_password) || empty($email)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT emp_id FROM Employee WHERE emp_name = ? OR email = ?");
        $stmt->bind_param("ss", $emp_name, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Username or email already exists';
        } else {
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO Employee (emp_name, password, email, tel, address) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $emp_name, $password, $email, $tel, $address);
            
            if ($stmt->execute()) {
                $success = 'Registration successful! You can now <a href="login.php">login</a>';
                // Clear form
                $emp_name = $email = $tel = $address = '';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Store Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;700&display=swap" rel="stylesheet">
    <style>
        .center-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f5f5f5;
        }
        .gpg-card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .gpg-title {
            text-align: center;
            color: #333;
            margin-bottom: 1.5rem;
        }
        .gpg-login-title {
            text-align: center;
            color: #444;
            margin-bottom: 1.5rem;
        }
        .gpg-input {
            width: 100%;
            padding: 10px;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .gpg-btn {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .gpg-btn:hover {
            background-color: #45a049;
        }
        .error {
            color: #f44336;
            margin: 10px 0;
            text-align: center;
        }
        .success {
            color: #4CAF50;
            margin: 10px 0;
            text-align: center;
        }
        .gpg-login-text {
            text-align: center;
            margin-top: 1rem;
        }
        .gpg-login-link {
            color: #4CAF50;
            text-decoration: none;
        }
        .gpg-login-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body class="gpg-bg">
    <div class="center-container">
        <div class="gpg-card">
            <h1 class="gpg-title">GPG Store</h1>
            <h2 class="gpg-login-title">Create New Account</h2>
            
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="post" action="">
                <input type="text" name="emp_name" placeholder="Username" class="gpg-input" value="<?php echo isset($emp_name) ? htmlspecialchars($emp_name) : ''; ?>" required>
                <input type="email" name="email" placeholder="Email" class="gpg-input" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                <input type="password" name="password" placeholder="Password" class="gpg-input" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" class="gpg-input" required>
                <input type="tel" name="tel" placeholder="Phone Number" class="gpg-input" value="<?php echo isset($tel) ? htmlspecialchars($tel) : ''; ?>">
                <input type="text" name="address" placeholder="Address" class="gpg-input" value="<?php echo isset($address) ? htmlspecialchars($address) : ''; ?>">
                
                <button type="submit" class="gpg-btn"> <a href="index.php"></a>Register</button>
            </form>
            
            <p class="gpg-login-text">
                Already have an account? <a href="login.php" class="gpg-login-link">Login here</a>
            </p>
        </div>
    </div>
</body>
</html>
