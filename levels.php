<?php
session_start();
$levelsFile = 'levels.json';
$levelsData = file_exists($levelsFile) ? file_get_contents($levelsFile) : '[]';
$levels = json_decode($levelsData, true) ?? [];

// === LOAD REPORTS & COUNT ===
$reportsFile = 'reports.json';
$reports = json_decode(file_exists($reportsFile) ? file_get_contents($reportsFile) : '[]', true) ?? [];
$reportCounts = [];
foreach ($reports as $r) {
    $key = $r['level_name'] . '|' . $r['creator'];
    $reportCounts[$key] = ($reportCounts[$key] ?? 0) + 1;
}

function isLoggedIn() { return isset($_SESSION['user_id']); }
$isAdmin = isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';

// FILTER HIDDEN LEVELS FOR NON-ADMINS
$levelsToDisplay = $isAdmin ? $levels : array_filter($levels, fn($l) => empty($l['hidden']));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $levelId = filter_input(INPUT_POST, 'level_id', FILTER_VALIDATE_INT);
    $commentText = trim($_POST['comment'] ?? '');
    $sanitizedComment = htmlspecialchars($commentText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $username = htmlspecialchars($_SESSION['username'] ?? 'Anonymous', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    if ($levelId !== false && $sanitizedComment !== '') {
        $levelFound = false;
        foreach ($levels as &$lvl) {
            if ((int)$lvl['id'] === $levelId) {
                $lvl['comments'][] = [
                    'username' => $username,
                    'text' => $sanitizedComment,
                    'date' => date('Y-m-d H:i:s')
                ];
                $levelFound = true;
                break;
            }
        }
        unset($lvl);
        if ($levelFound) {
            file_put_contents($levelsFile, json_encode($levels, JSON_PRETTY_PRINT));
            $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $lvl['level_name'] ?? 'level');
            $safeUser = preg_replace('/[^a-zA-Z0-9_-]/', '', $lvl['creator'] ?? 'user');
            header("Location: level.php?user=$safeUser&level=$safeName");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Darkness List</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #000; color: #eee; font-family: 'Segoe UI', sans-serif; padding: 20px 0; }
        .container { max-width: 1100px; margin: 0 auto; padding: 0 15px; }
        h1 { text-align: center; color: #ff6666; font-size: 2em; margin-bottom: 10px; font-weight: 700; }
        .header-links { text-align: center; margin: 15px 0; font-size: 0.95em; }
        .header-links a { color: #ff6666; text-decoration: none; margin: 0 12px; font-weight: bold; }
        .header-links a:hover { color: #ff9999; text-decoration: underline; }
        .logout-btn { background: #cc0000; color: white; padding: 6px 14px; border-radius: 6px; font-size: 0.9em; font-weight: bold; }
        .logout-btn:hover { background: #990000; }
        .levels-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(580px, 1fr)); gap: 24px; max-width: 1000px; margin: 30px auto; justify-content: center; }
        .level-card { background: #111; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.5); transition: transform 0.3s ease; display: flex; height: 200px; }
        .level-card.hidden-admin { opacity: 0.7; border: 2px solid #550000; }
        .level-card:hover { transform: translateY(-6px); }
        .card-text { padding: 18px; flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .level-name {
            color: #ff6666;
            font-size: 1.35em;
            font-weight: bold;
            margin-bottom: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            flex-wrap: wrap;
        }
        .level-name:hover { color: #ff9999; text-decoration: underline; }
        .top-rank {
            font-weight: bold;
            color: #ff9999;
            font-size: 0.9em;
            margin-left: 4px;
        }
        .comment-count { 
            font-size: 0.7em; 
            color: #ff6666; 
            font-weight: bold; 
            background: #220000; 
            padding: 2px 6px; 
            border-radius: 4px; 
        }
        .report-count {
            font-size: 0.7em;
            color: #fff;
            font-weight: bold;
            background: #cc6600;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 4px;
        }
        .hidden-badge { background: #333; color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 0.82em; font-weight: bold; display: inline-block; margin: 4px 8px 8px 0; }
        .level-info { font-size: 0.92em; color: #ccc; margin: 3px 0; }
        .level-info strong { color: #ff9999; }
        .thumb-wrapper { width: 320px; height: 180px; flex-shrink: 0; overflow: hidden; border-left: 1px solid #333; }
        .thumbnail { width: 100%; height: 100%; object-fit: cover; display: block; cursor: pointer; transition: transform 0.3s ease; }
        .thumbnail:hover { transform: scale(1.03); }
        .gdb-link { text-align: center; padding: 10px; background: #0a0a0a; color: #ff6666; font-weight: bold; font-size: 0.9em; text-decoration: none; border-top: 1px solid #333; }
        .gdb-link:hover { color: #ff9999; background: #111; }
        .comments-section { margin: 20px auto 0; max-width: 1000px; background: #0a0a0a; padding: 16px; border-radius: 10px; border: 1px solid #333; }
        .comments-title { color: #ff6666; font-size: 1.1em; font-weight: bold; margin-bottom: 12px; }
        .comment { background: #111; padding: 10px; margin-bottom: 10px; border-radius: 6px; border-left: 3px solid #ff6666; }
        .comment-header { font-size: 0.85em; color: #aaa; margin-bottom: 4px; }
        .comment-header strong { color: #ff9999; }
        .comment-text { color: #eee; font-size: 0.9em; line-height: 1.4; }
        .comment-text strong { color: #ff6666; }
        .comment-form textarea { width: 100%; background: #111; color: #eee; border: 1px solid #333; border-radius: 6px; padding: 8px; resize: vertical; font-family: 'Segoe UI', sans-serif; }
        .comment-form button { margin-top: 6px; background: #ff6666; color: #fff; border: none; padding: 8px 14px; border-radius: 6px; cursor: pointer; font-weight: bold; transition: 0.2s ease; }
        .comment-form button:hover { background: #ff8888; }
        .no-levels { grid-column: 1 / -1; text-align: center; color: #888; font-style: italic; margin: 40px 0; font-size: 1.1em; }
        @media (max-width: 768px) {
            .levels-grid { grid-template-columns: 1fr; max-width: 380px; }
            .level-card { flex-direction: column; height: auto; }
            .thumb-wrapper { width: 100%; height: 180px; border-left: none; border-top: 1px solid #333; }
            .comments-section { margin: 15px auto 0; padding: 12px; }
            .level-name { font-size: 1.2em; flex-wrap: wrap; }
            .comment-count, .report-count { font-size: 0.65em; }
            .top-rank { font-size: 0.85em; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Darkness List</h1>

        <?php if (isLoggedIn()): ?>
            <p class="header-links">
                Logged in as: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                <a href="logout.php" class="logout-btn">Logout</a>
                <a href="submit.php">Submit</a>
                <?php if ($isAdmin): ?>
                    <a href="admin.php">Admin</a>
                <?php endif; ?>
            </p>
        <?php else: ?>
            <p class="header-links">
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            </p>
        <?php endif; ?>

        <div class="levels-grid">
            <?php if (empty($levelsToDisplay)): ?>
                <p class="no-levels">No levels yet. Submit one!</p>
            <?php else: ?>
                <?php foreach ($levelsToDisplay as $level): 
                    $levelId = $level['id'] ?? 0;
                    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $level['level_name'] ?? 'level');
                    $safeUser = preg_replace('/[^a-zA-Z0-9_-]/', '', $level['creator'] ?? 'user');
                    $isHidden = !empty($level['hidden']);
                    $cardClass = $isHidden && $isAdmin ? 'level-card hidden-admin' : 'level-card';
                    $levelLink = "level.php?user=$safeUser&level=$safeName";
                    
                    // Report count
                    $reportKey = $level['level_name'] . '|' . $level['creator'];
                    $reportCount = $reportCounts[$reportKey] ?? 0;
                ?>
                    <div class="<?php echo $cardClass; ?>" id="level-<?php echo $levelId; ?>">
                        <div class="card-text">
                            <a href="<?php echo $levelLink; ?>" class="level-name">
                                <?php echo htmlspecialchars($level['level_name'] ?? 'Untitled Level'); ?>
                                <?php if (!empty($level['top_rank'])): ?>
                                    <span class="top-rank">#<?php echo htmlspecialchars($level['top_rank']); ?></span>
                                <?php endif; ?>
                                <?php $count = count($level['comments'] ?? []); if ($count > 0): ?>
                                    <span class="comment-count"><?php echo $count; ?></span>
                                <?php endif; ?>
                                <?php if ($reportCount > 0): ?>
                                    <span class="report-count"><?php echo $reportCount; ?> Report<?php echo $reportCount > 1 ? 's' : ''; ?></span>
                                <?php endif; ?>
                            </a>

                            <?php if ($isHidden && $isAdmin): ?>
                                <div class="hidden-badge">HIDDEN</div>
                            <?php endif; ?>

                            <div class="level-info"><strong>Diff:</strong> <?php echo htmlspecialchars($level['difficulty'] ?? 'N/A'); ?></div>
                            <div class="level-info"><strong>By:</strong> <?php echo htmlspecialchars($level['creator'] ?? 'Unknown'); ?></div>
                            <div class="level-info"><strong>Date:</strong> <?php echo htmlspecialchars($level['date'] ?? 'N/A'); ?></div>

                            <a href="https://gdbrowser.com/<?php echo urlencode($level['gdbrowser_id'] ?? ''); ?>" 
                               target="_blank" rel="noopener" class="gdb-link">
                                GD Browser
                            </a>
                        </div>

                        <a href="<?php echo $levelLink; ?>">
                            <div class="thumb-wrapper">
                                <img src="https://img.youtube.com/vi/<?php echo urlencode($level['youtube_id'] ?? 'placeholder'); ?>/hqdefault.jpg" 
                                     alt="Thumbnail" class="thumbnail">
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>