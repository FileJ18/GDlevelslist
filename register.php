<?php
// register.php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $makeAdmin = isset($_POST['make_admin']) && $_POST['make_admin'] === 'yes';

    if (empty($username) || empty($password)) {
        $error = "Username and password are required.";
    } else {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            $error = "Username already taken.";
        } else {
            // Check if admin already exists
            $adminExists = $pdo->query("SELECT 1 FROM users WHERE role = 'admin' LIMIT 1")->rowCount() > 0;

            // Block admin creation if already exists OR if button not used when needed
            if ($makeAdmin && $adminExists) {
                $error = "Admin already exists! Only one allowed.";
            } elseif ($makeAdmin && !$adminExists) {
                $role = 'admin';
            } else {
                $role = 'user';
            }

            // Insert user
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            if ($stmt->execute([$username, $password, $role])) {
                $success = $role === 'admin' 
                    ? "Admin account created successfully! Login now." 
                    : "Account created! Login to continue.";
                // Optional: auto-login
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                header("Location: index.php");
                exit;
            } else {
                $error = "Registration failed. Try again.";
            }
        }
    }
}

// Check if admin exists (for display)
$adminExists = $pdo->query("SELECT 1 FROM users WHERE role = 'admin' LIMIT 1")->rowCount() > 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Darkness List</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Register</h1>

        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="post">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>

            <!-- Admin Button: Only show if NO admin exists -->
            <?php if (!$adminExists): ?>
                <p style="margin: 15px 0;">
                    <button type="submit" name="make_admin" value="yes" class="admin-btn">
                        Make Me the First Admin
                    </button>
                    <br><small style="color:#aaa;">(Only one admin allowed. This button will disappear after use.)</small>
                </p>
                <p style="margin: 10px 0; color:#666;">
                    <em>— or —</em>
                </p>
            <?php else: ?>
                <p style="color:#0a0; font-weight:bold;">
                    Admin account already exists.
                </p>
            <?php endif; ?>

            <!-- Regular Register Button -->
            <button type="submit" <?php echo $adminExists ? '' : 'style="background:#666;"'; ?>>
                <?php echo $adminExists ? 'Register as User' : 'Register as User'; ?>
            </button>
        </form>

        <p><a href="login.php">Already have an account? Login</a></p>
    </div>
</body>
</html>