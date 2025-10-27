<?php
/**
 * Diagnostic and Password Reset Tool
 * Place this file in the root directory (travelease/)
 * Access it via: http://localhost/travelease/diagnose.php
 * DELETE THIS FILE AFTER USE!
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Database.php';

echo "<h2>TravelEase Diagnostic Tool</h2>";
echo "<hr>";

// Test 1: Database Connection
echo "<h3>1. Testing Database Connection...</h3>";
try {
    $db = Database::getInstance()->getConnection();
    echo "✅ Database connection successful!<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    die("Cannot proceed without database connection.");
}

// Test 2: Check if admin_users table exists
echo "<h3>2. Checking admin_users table...</h3>";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'admin_users'");
    if ($stmt->rowCount() > 0) {
        echo "✅ admin_users table exists<br>";
    } else {
        echo "❌ admin_users table does not exist!<br>";
        echo "Please run the database_schema.sql file first.<br>";
        die();
    }
} catch (Exception $e) {
    echo "❌ Error checking table: " . $e->getMessage() . "<br>";
    die();
}

// Test 3: Check existing admin users
echo "<h3>3. Checking existing admin users...</h3>";
try {
    $stmt = $db->query("SELECT id, username, email, created_at FROM admin_users");
    $users = $stmt->fetchAll();
    
    if (count($users) > 0) {
        echo "Found " . count($users) . " admin user(s):<br>";
        echo "<table border='1' cellpadding='5' style='margin-top: 10px;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Created At</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . $user['username'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "<td>" . $user['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "⚠️ No admin users found in database!<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 4: Password Reset Form
echo "<h3>4. Reset Admin Password</h3>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $username = $_POST['username'] ?? 'admin';
    $newPassword = $_POST['new_password'] ?? 'Admin@123';
    
    // Generate hash
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Check if user exists
    $stmt = $db->prepare("SELECT id FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Update existing user
        $updateStmt = $db->prepare("UPDATE admin_users SET password = ? WHERE username = ?");
        $updateStmt->execute([$hashedPassword, $username]);
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>";
        echo "✅ <strong>Password updated successfully!</strong><br><br>";
        echo "Username: <strong>" . htmlspecialchars($username) . "</strong><br>";
        echo "Password: <strong>" . htmlspecialchars($newPassword) . "</strong><br>";
        echo "</div>";
    } else {
        // Create new user
        $insertStmt = $db->prepare("INSERT INTO admin_users (username, password, email) VALUES (?, ?, ?)");
        $insertStmt->execute([$username, $hashedPassword, 'admin@travelease.com']);
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>";
        echo "✅ <strong>New admin user created!</strong><br><br>";
        echo "Username: <strong>" . htmlspecialchars($username) . "</strong><br>";
        echo "Password: <strong>" . htmlspecialchars($newPassword) . "</strong><br>";
        echo "</div>";
    }
    
    // Test the password
    echo "<h3>5. Testing Password Verification...</h3>";
    $testStmt = $db->prepare("SELECT * FROM admin_users WHERE username = ?");
    $testStmt->execute([$username]);
    $testUser = $testStmt->fetch();
    
    if ($testUser && password_verify($newPassword, $testUser['password'])) {
        echo "✅ Password verification test PASSED!<br>";
        echo "<strong style='color: green;'>You can now login with these credentials!</strong><br>";
    } else {
        echo "❌ Password verification test FAILED!<br>";
        echo "There might be an issue with the password hashing.<br>";
    }
}
?>

<form method="POST" style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
    <h4>Reset/Create Admin User</h4>
    <p>
        <label>Username:</label><br>
        <input type="text" name="username" value="admin" style="padding: 8px; width: 250px;">
    </p>
    <p>
        <label>New Password:</label><br>
        <input type="text" name="new_password" value="Admin@123" style="padding: 8px; width: 250px;">
    </p>
    <button type="submit" name="reset_password" style="background: #0a2540; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
        Reset/Create Password
    </button>
</form>

<div style="background: #fff3cd; padding: 15px; border: 1px solid #ffc107; border-radius: 5px; margin: 20px 0;">
    <strong>⚠️ IMPORTANT:</strong> Delete this file (diagnose.php) after you've successfully logged in!
</div>

<p>
    <a href="<?php echo BASE_URL; ?>/admin/login.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
        Go to Admin Login Page
    </a>
</p>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 900px;
        margin: 50px auto;
        padding: 20px;
    }
    table {
        border-collapse: collapse;
        width: 100%;
    }
    th {
        background: #0a2540;
        color: white;
        padding: 10px;
    }
    td {
        padding: 8px;
    }
</style>