<?php
// access_denied.php
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Access Denied</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .denied {
            text-align: center;
            margin: 100px auto;
            padding: 30px;
            background: #220000;
            border: 2px solid #cc0000;
            border-radius: 12px;
            max-width: 500px;
            color: #ff6666;
            font-family: Arial, sans-serif;
        }
        .denied h1 {
            color: #ff3333;
            font-size: 2.5em;
            margin-bottom: 20px;
        }
        .denied p {
            font-size: 1.2em;
            margin: 15px 0;
        }
        .denied a {
            color: #ff9999;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="denied">
        <h1>Access Denied</h1>
        <p>You do not have permission to access this page.</p>
        <p>Only <strong>admins</strong> are allowed.</p>
        <p><a href="index.php">Back to Levels</a></p>
    </div>
</body>
</html>
<?php exit; ?>