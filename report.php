<?php
session_start();
require_once 'db.php';

$levelsFile = 'levels.json';
$levels = json_decode(file_exists($levelsFile) ? file_get_contents($levelsFile) : '[]', true) ?? [];

$userParam = trim($_GET['user'] ?? '');
$levelParam = trim($_GET['level'] ?? '');

$level = null;
foreach ($levels as $lvl) {
    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $lvl['level_name'] ?? '');
    $safeUser = preg_replace('/[^a-zA-Z0-9_-]/', '', $lvl['creator'] ?? '');
    if ($safeUser === $userParam && $safeName === $levelParam) {
        $level = $lvl;
        break;
    }
}

if (!$level || empty($level['id'])) {
    http_response_code(404);
    die('<h1 style="color:#ff6666;text-align:center;margin:100px;">Level not found.</h1>');
}

$levelId = $level['id'];
$levelName = htmlspecialchars($level['level_name']);
$creator = htmlspecialchars($level['creator']);

$reportsFile = 'reports.json';
$reports = json_decode(file_exists($reportsFile) ? file_get_contents($reportsFile) : '[]', true) ?? [];

$alreadyReported = false;
if (isset($_SESSION['user_id'])) {
    foreach ($reports as $r) {
        if ($r['level_id'] == $levelId && $r['user_id'] == $_SESSION['user_id']) {
            $alreadyReported = true;
            break;
        }
    }
}

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id']) && !$alreadyReported) {
    $reason = trim($_POST['reason'] ?? '');
    if ($reason === '') {
        $error = "Please provide a reason.";
    } else {
        $reports[] = [
            'level_id' => $levelId,
            'level_name' => $level['level_name'],
            'creator' => $level['creator'],
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'reason' => $reason,
            'date' => date('Y-m-d H:i:s')
        ];
        file_put_contents($reportsFile, json_encode($reports, JSON_PRETTY_PRINT));
        $success = "Report submitted. Thank you.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Level - <?php echo $levelName; ?></title>
    <style>
        body { background: #000; color: #eee; font-family: 'Segoe UI', sans-serif; padding: 20px; }
        .container { max-width: 700px; margin: 40px auto; background: #111; padding: 25px; border-radius: 12px; border: 1px solid #333; }
        h1 { color: #ff6666; text-align: center; margin-bottom: 20px; }
        .back { display: block; text-align: center; margin: 15px 0; color: #ff9999; text-decoration: none; font-weight: bold; }
        .back:hover { text-decoration: underline; }
        .level-info { background: #1a1a1a; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .level-info strong { color: #ff9999; }
        .report-form textarea {
            width: 100%; background: #000; color: #eee; border: 1px solid #444;
            border-radius: 6px; padding: 12px; resize: vertical; font-family: inherit; font-size: 1em;
        }
        .report-form button {
            margin-top: 12px; background: #cc0000; color: white; border: none;
            padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold;
        }
        .report-form button:hover { background: #990000; }
        .success { background: #003300; color: #66ff66; padding: 12px; border-radius: 6px; margin: 15px 0; text-align: center; }
        .error { background: #330000; color: #ff6666; padding: 12px; border-radius: 6px; margin: 15px 0; text-align: center; }
        .note { font-size: 0.9em; color: #aaa; margin-top: 20px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Report Level</h1>
        <a href="level.php?user=<?php echo urlencode($userParam); ?>&level=<?php echo urlencode($levelParam); ?>" class="back">Back to Level</a>

        <div class="level-info">
            <p><strong>Level:</strong> <?php echo $levelName; ?></p>
            <p><strong>Creator:</strong> <?php echo $creator; ?></p>
        </div>

        <?php if ($success): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php elseif ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php elseif ($alreadyReported): ?>
            <p class="success">You have already reported this level.</p>
        <?php elseif (!isset($_SESSION['user_id'])): ?>
            <p style="text-align:center; color:#ff6666;">
                <a href="login.php" style="color:#ff9999;">Login</a> to report.
            </p>
        <?php else: ?>
            <form method="POST" class="report-form">
                <textarea name="reason" rows="5" placeholder="Explain why this level should be reviewed (e.g., stolen, inappropriate, broken link)..." required></textarea>
                <button type="submit">Submit Report</button>
            </form>
        <?php endif; ?>

        <p class="note">Reports are reviewed by admins. False reports may lead to account penalties.</p>
    </div>
</body>
</html>