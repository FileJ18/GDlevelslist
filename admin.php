<?php
session_start();
require_once 'db.php';

// === ADMIN ONLY ACCESS ===
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('
    <!DOCTYPE html>
    <html><head><meta charset="UTF-8"><title>Access Denied</title>
    <style>body{background:#000;color:#eee;font-family:Segoe UI;padding:40px;text-align:center;}
    .box{background:#220000;border:2px solid #cc0000;border-radius:12px;padding:30px;max-width:500px;margin:50px auto;}
    h1{color:#ff3333;font-size:2.5em;margin-bottom:20px;}
    a{color:#ff9999;text-decoration:underline;}</style></head>
    <body><div class="box"><h1>Access Denied</h1>
    <p>Only <strong>admins</strong> allowed.</p>
    <p><a href="levels.php">Back to Levels</a></p></div></body></html>');
}

// === LOAD DATA ===
$levelsFile = 'levels.json';
$reportsFile = 'reports.json';

$levels = json_decode(file_exists($levelsFile) ? file_get_contents($levelsFile) : '[]', true) ?? [];
$reports = json_decode(file_exists($reportsFile) ? file_get_contents($reportsFile) : '[]', true) ?? [];

// === TOGGLE HIDDEN ===
if (isset($_GET['toggle_hidden'])) {
    $id = (int)$_GET['toggle_hidden'];
    foreach ($levels as &$level) {
        if ((int)$level['id'] === $id) {
            $level['hidden'] = empty($level['hidden']);
            $success = $level['hidden'] ? "Level hidden." : "Level unhidden.";
            break;
        }
    }
    unset($level);
    file_put_contents($levelsFile, json_encode($levels, JSON_PRETTY_PRINT));
    header("Location: admin.php#levels");
    exit;
}

// === DELETE LEVEL ===
if (isset($_GET['delete_level'])) {
    $deleteId = (int)$_GET['delete_level'];
    $levels = array_filter($levels, fn($l) => (int)$l['id'] !== $deleteId);
    $levels = array_values($levels);
    foreach ($levels as $i => &$l) { $l['id'] = $i + 1; }
    file_put_contents($levelsFile, json_encode($levels, JSON_PRETTY_PRINT));
    $success = "Level deleted.";
    header("Location: admin.php#levels");
    exit;
}

// === SET TOP RANK ===
if (isset($_POST['set_rank'])) {
    $levelId = (int)$_POST['level_id'];
    $newRank = !empty($_POST['top_rank']) ? (int)$_POST['top_rank'] : null;
    $found = false;
    foreach ($levels as &$level) {
        if ((int)$level['id'] === $levelId) {
            $found = true;
            if ($newRank !== null) {
                foreach ($levels as $l) {
                    if ((int)$l['id'] !== $levelId && isset($l['top_rank']) && (int)$l['top_rank'] === $newRank) {
                        $error = "Top #$newRank is already taken!";
                        break 2;
                    }
                }
            }
            $level['top_rank'] = $newRank;
            $success = "Top rank updated!";
            break;
        }
    }
    if ($found) file_put_contents($levelsFile, json_encode($levels, JSON_PRETTY_PRINT));
}

// === RESOLVE REPORT ===
if (isset($_GET['resolve_report'])) {
    $idx = (int)$_GET['resolve_report'];
    if (isset($reports[$idx])) {
        unset($reports[$idx]);
        $reports = array_values($reports);
        file_put_contents($reportsFile, json_encode($reports, JSON_PRETTY_PRINT));
        $success = "Report resolved.";
    }
    header("Location: admin.php#reports");
    exit;
}

// === DELETE REPORT ===
if (isset($_GET['delete_report'])) {
    $idx = (int)$_GET['delete_report'];
    if (isset($reports[$idx])) {
        unset($reports[$idx]);
        $reports = array_values($reports);
        file_put_contents($reportsFile, json_encode($reports, JSON_PRETTY_PRINT));
        $success = "Report deleted.";
    }
    header("Location: admin.php#reports");
    exit;
}

// === LOAD USERS ===
try {
    $users = $pdo->query("SELECT id, username, role FROM users ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $users = []; }

// === DELETE USER ===
if (isset($_GET['delete_user'])) {
    $userId = (int)$_GET['delete_user'];
    $stmt = $pdo->prepare("SELECT username, role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if ($user && $user['role'] !== 'admin') {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
        $success = "User '{$user['username']}' deleted.";
    } else {
        $error = "Cannot delete admin.";
    }
    header("Location: admin.php#users");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Darkness List</title>
    <style>
        body { background: #000; color: #eee; font-family: 'Segoe UI', sans-serif; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #ff6666; text-align: center; margin-bottom: 20px; font-size: 2.2em; }
        .nav { text-align: center; margin: 20px 0; }
        .nav a { color: #ff6666; text-decoration: none; margin: 0 15px; font-weight: bold; }
        .nav a:hover { color: #ff9999; text-decoration: underline; }
        .logout-btn { background: #cc0000; color: white; padding: 8px 16px; border-radius: 6px; font-size: 0.9em; font-weight: bold; text-decoration: none; }
        .logout-btn:hover { background: #990000; }
        .section { margin: 40px 0; padding: 25px; background: #111; border-radius: 12px; border: 1px solid #333; }
        .section h2 { color: #ff6666; text-align: center; margin-bottom: 20px; font-size: 1.8em; }
        .admin-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.95em; }
        .admin-table th { background: #222; color: #ff6666; padding: 12px; text-align: left; font-weight: bold; }
        .admin-table td { padding: 12px; border-bottom: 1px solid #333; vertical-align: top; }
        .admin-table tr:hover { background: #1a1a1a; }
        .btn { padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.85em; font-weight: bold; display: inline-block; margin: 2px; }
        .delete-btn { background: #cc0000; color: white; }
        .delete-btn:hover { background: #990000; }
        .hide-btn { background: #cc6600; color: white; }
        .hide-btn.hidden { background: #006600; }
        .hide-btn:hover, .delete-btn:hover { opacity: 0.9; }
        .top-rank-badge { background: linear-gradient(45deg, #cc0000, #ff3333); color: white; padding: 6px 12px; border-radius: 8px; font-weight: bold; font-size: 0.9em; display: inline-block; margin-left: 10px; }
        .rank-form { display: inline-flex; align-items: center; gap: 6px; }
        .rank-input { width: 70px; padding: 6px; background: #111; border: 1px solid #444; color: white; border-radius: 4px; text-align: center; font-weight: bold; }
        .rank-btn { background: #ff6666; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8em; font-weight: bold; }
        .rank-btn:hover { background: #cc0000; }
        .success { background: #003300; color: #66ff66; padding: 12px; border-radius: 6px; margin: 15px 0; text-align: center; font-weight: bold; }
        .error { background: #330000; color: #ff6666; padding: 12px; border-radius: 6px; margin: 15px 0; text-align: center; font-weight: bold; }
        .id-col { font-family: monospace; color: #0f0; font-weight: bold; }
        .report-reason { max-width: 300px; word-wrap: break-word; font-size: 0.9em; }
        .level-link { color: #ff9999; text-decoration: underline; }
        .level-link:hover { color: #fff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Panel</h1>
        <div class="nav">
            <a href="levels.php">Back to List</a> |
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>

        <?php if (isset($success)): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <!-- LEVEL MANAGER -->
        <div class="section" id="levels">
            <h2>Level Manager</h2>
            <?php if (empty($levels)): ?>
                <p style="text-align:center; color:#888;">No levels submitted.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Level Name</th>
                            <th>Difficulty</th>
                            <th>Creator</th>
                            <th>Date</th>
                            <th>Top #X</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($levels as $level): 
                            $isHidden = !empty($level['hidden']);
                        ?>
                            <tr>
                                <td class="id-col"><?php echo $level['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($level['level_name']); ?>
                                    <?php if (!empty($level['top_rank'])): ?>
                                        <span class="top-rank-badge">Top <?php echo $level['top_rank']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($level['difficulty']); ?></td>
                                <td><?php echo htmlspecialchars($level['creator']); ?></td>
                                <td><?php echo htmlspecialchars($level['date']); ?></td>
                                <td>
                                    <form method="post" class="rank-form">
                                        <input type="hidden" name="level_id" value="<?php echo $level['id']; ?>">
                                        <input type="number" name="top_rank" class="rank-input" 
                                               value="<?php echo $level['top_rank'] ?? ''; ?>" min="1" max="9999" placeholder="—">
                                        <button type="submit" name="set_rank" class="rank-btn">Set</button>
                                    </form>
                                </td>
                                <td style="white-space: nowrap;">
                                    <a href="admin.php?toggle_hidden=<?php echo $level['id']; ?>#levels"
                                       class="btn hide-btn <?php echo $isHidden ? 'hidden' : ''; ?>"
                                       onclick="return confirm('<?php echo $isHidden ? 'Unhide' : 'Hide'; ?> this level?')">
                                       <?php echo $isHidden ? 'Unhide' : 'Hide'; ?>
                                    </a>
                                    <a href="admin.php?delete_level=<?php echo $level['id']; ?>#levels"
                                       class="btn delete-btn"
                                       onclick="return confirm('Delete level ID <?php echo $level['id']; ?> (<?php echo addslashes(htmlspecialchars($level['level_name'])); ?>)?')">
                                       Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- REPORT MANAGER -->
        <div class="section" id="reports">
            <h2>Report Manager</h2>
            <?php if (empty($reports)): ?>
                <p style="text-align:center; color:#888;">No reports.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Level</th>
                            <th>Creator</th>
                            <th>Reporter</th>
                            <th>Reason</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $i => $r): 
                            $safeUser = preg_replace('/[^a-zA-Z0-9_-]/', '', $r['creator']);
                            $safeLevel = preg_replace('/[^a-zA-Z0-9_-]/', '', $r['level_name']);
                        ?>
                            <tr>
                                <td class="id-col"><?php echo $i + 1; ?></td>
                                <td>
                                    <a href="level.php?user=<?php echo urlencode($safeUser); ?>&level=<?php echo urlencode($safeLevel); ?>" 
                                       class="level-link" target="_blank">
                                        <?php echo htmlspecialchars($r['level_name']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($r['creator']); ?></td>
                                <td><?php echo htmlspecialchars($r['username']); ?></td>
                                <td class="report-reason"><?php echo htmlspecialchars($r['reason']); ?></td>
                                <td><?php echo htmlspecialchars($r['date']); ?></td>
                                <td style="white-space: nowrap;">
                                    <a href="admin.php?resolve_report=<?php echo $i; ?>#reports"
                                       class="btn" style="background:#006600;color:white;"
                                       onclick="return confirm('Mark as resolved?')">
                                       Resolve
                                    </a>
                                    <a href="admin.php?delete_report=<?php echo $i; ?>#reports"
                                       class="btn delete-btn"
                                       onclick="return confirm('Delete report?')">
                                       Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- USER MANAGER -->
        <div class="section" id="users">
            <h2>User Manager</h2>
            <?php if (empty($users)): ?>
                <p style="text-align:center; color:#888;">No users.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="id-col"><?php echo $user['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($user['username']); ?>
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <span style="color:#ff0;font-weight:bold;"> (ADMIN)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong style="color:<?php echo $user['role']==='admin'?'#ff0':'#0f0'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </strong>
                                </td>
                                <td>
                                    <?php if ($user['role'] !== 'admin'): ?>
                                        <a href="admin.php?delete_user=<?php echo $user['id']; ?>#users"
                                           class="btn delete-btn"
                                           onclick="return confirm('Delete user <?php echo addslashes(htmlspecialchars($user['username'])); ?>?')">
                                           Delete
                                        </a>
                                    <?php else: ?>
                                        <em style="color:#666;">Protected</em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>