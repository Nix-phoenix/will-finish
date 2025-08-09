<?php
session_start();

function requireLogin() {
    if (!isset($_SESSION['emp_id'])) {
        header("Location: login.php");
        exit;
    }
}

function requireRole($role) {
    requireLogin();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        echo "<div style='text-align: center; padding: 50px; font-family: Arial, sans-serif;'>";
        echo "<h2>🚫 Access Denied</h2>";
        echo "<p>You do not have permission to access this page.</p>";
        echo "<p>Required role: <strong>$role</strong></p>";
        echo "<p>Your role: <strong>" . ($_SESSION['role'] ?? 'None') . "</strong></p>";
        echo "<a href='index.php' style='color: #007bff; text-decoration: none;'>← Back to Dashboard</a>";
        echo "</div>";
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        echo "<div style='text-align: center; padding: 50px; font-family: Arial, sans-serif;'>";
        echo "<h2>🚫 Access Denied</h2>";
        echo "<p>This page requires administrator privileges.</p>";
        echo "<p>Your role: <strong>" . ($_SESSION['role'] ?? 'None') . "</strong></p>";
        echo "<a href='index.php' style='color: #007bff; text-decoration: none;'>← Back to Dashboard</a>";
        echo "</div>";
        exit;
    }
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isEmployee() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'employee';
}
?>
