<?php
// phpinfo.php - ADMIN ONLY
require_once 'db.php'; // Starts session + DB

// === ADMIN ONLY CHECK ===
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    require_once 'access_denied.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Info - Admin Only</title>
    <style>
        body {
            background: #f9f9f9;
            color: #333;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #d00;
            font-size: 2em;
            margin-bottom: 20px;
            font-weight: 700;
        }

        /* DARK GO BACK BUTTON */
        .back-btn {
            display: inline-block;
            background: #cc0000;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            text-decoration: none;
            font-size: 1em;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            margin: 20px 0;
        }
        .back-btn:hover {
            background: #990000;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.3);
        }

        .btn-container {
            text-align: center;
            margin: 30px 0;
        }

        /* phpinfo() clean */
        table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 0.95em; }
        th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f0f0f0; color: #333; font-weight: bold; }
        tr:hover { background: #f5f5f5; }
        .e { background: #fff3cd; }
        .v { background: #fff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>PHP Information (Admin Only)</h1>

        <!-- DARK BUTTON -->
        <div class="btn-container">
            <a href="index.php" class="back-btn">Back to Levels</a>
        </div>

        <!-- PHP INFO -->
        <?php phpinfo(); ?>
    </div>
</body>
</html>