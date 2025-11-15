<?php
session_start();
$levelsFile = 'levels.json';
$levelsData = file_exists($levelsFile) ? file_get_contents($levelsFile) : '[]';
$levels = json_decode($levelsData, true) ?? [];

$userParam = trim($_GET['user'] ?? '');
$levelParam = trim($_GET['level'] ?? '');

if ($userParam === '' || $levelParam === '') {
    http_response_code(404);
    die('<h1 style="color:#ff6666;text-align:center;margin:50px;">Invalid URL</h1>');
}

$level = null;
foreach ($levels as $lvl) {
    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $lvl['level_name'] ?? '');
    $safeUser = preg_replace('/[^a-zA-Z0-9_-]/', '', $lvl['creator'] ?? '');
    if ($safeUser === $userParam && $safeName === $levelParam) {
        $level = $lvl;
        break;
    }
}

if (!$level) {
    http_response_code(404);
    die('<h1 style="color:#ff6666;text-align:center;margin:50px;">Level not found.</h1>');
}

$isAdmin = isLoggedIn() && (($_SESSION['role'] ?? '') === 'admin');
if (!$isAdmin && !empty($level['hidden'])) {
    die('<h1 style="color:#ff6666;text-align:center;margin:50px;">This level is hidden.</h1>');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $commentText = trim($_POST['comment'] ?? '');
    $sanitizedComment = htmlspecialchars($commentText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $username = htmlspecialchars($_SESSION['username'] ?? 'Anonymous', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    if ($sanitizedComment !== '') {
        $level['comments'][] = [
            'username' => $username,
            'text' => $sanitizedComment,
            'date' => date('Y-m-d H:i:s')
        ];

        foreach ($levels as &$lvl) {
            $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $lvl['level_name'] ?? '');
            $safeUser = preg_replace('/[^a-zA-Z0-9_-]/', '', $lvl['creator'] ?? '');
            if ($safeUser === $userParam && $safeName === $levelParam) {
                $lvl = $level;
                break;
            }
        }
        file_put_contents($levelsFile, json_encode($levels, JSON_PRETTY_PRINT));
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $level['level_name'] ?? '');
        $safeUser = preg_replace('/[^a-zA-Z0-9_-]/', '', $level['creator'] ?? '');
        header("Location: level.php?user=$safeUser&level=$safeName");
        exit;
    }
}

$ytId = $level['youtube_id'] ?? '';
$fullYtUrl = "https://www.youtube.com/watch?v=$ytId";
$ytDlpCmd = "yt-dlp -f 'best[ext=mp4]/best' \"$fullYtUrl\"";

// Safe URL parameters for Report link
$safeUser = preg_replace('/[^a-zA-Z0-9_-]/', '', $level['creator'] ?? '');
$safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $level['level_name'] ?? '');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($level['level_name'] ?? 'Level'); ?> by <?php echo htmlspecialchars($level['creator'] ?? 'Unknown'); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #000; color: #eee; font-family: 'Segoe UI', sans-serif; padding: 20px 0; }
        .container { max-width: 1000px; margin: 0 auto; padding: 0 15px; }
        .back-link { display: inline-block; margin: 15px 0; color: #ff6666; text-decoration: none; font-weight: bold; }
        .back-link:hover { text-decoration: underline; }
        .level-header { background: #111; padding: 20px; border-radius: 16px; margin-bottom: 20px; text-align: center; }
        .level-name { font-size: 2em; color: #ff6666; margin-bottom: 10px; font-weight: bold; }
        .creator { font-size: 1.4em; color: #ff9999; margin: 8px 0; }
        .level-info { font-size: 1em; color: #ccc; margin: 5px 0; }
        .level-info strong { color: #ff9999; }
        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            background: #000;
            border-radius: 12px;
            margin: 20px 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.5);
        }
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        .video-actions {
            text-align: center;
            margin: 15px 0;
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .download-btn {
            display: inline-block;
            background: #cc0000;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 0.95em;
            transition: 0.2s;
            border: none;
            cursor: pointer;
            min-width: 140px;
        }
        .download-btn:hover {
            transform: translateY(-1px);
        }
        .download-btn[style*="ff6666"] { background: #ff6666; }
        .download-btn[style*="ff6666"]:hover { background: #cc5555; }
        .download-btn[style*="cc6600"] { background: #cc6600; }
        .download-btn[style*="cc6600"]:hover { background: #994d00; }
        .yt-dlp-section {
            background: #111;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            text-align: left;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        .yt-dlp-section h4 {
            color: #ff6666;
            margin-bottom: 8px;
        }
        .yt-dlp-cmd {
            background: #000;
            color: #0f0;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            word-break: break-all;
            margin: 8px 0;
            user-select: all;
        }
        .install-note {
            font-size: 0.85em;
            color: #ccc;
            margin-top: 5px;
        }
        .gdb-link {
            display: inline-block;
            background: #0a0a0a;
            color: #ff6666;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            margin: 10px 0;
        }
        .gdb-link:hover { background: #111; color: #ff9999; }
        .comments-section { background: #0a0a0a; padding: 16px; border-radius: 10px; border: 1px solid #333; margin-top: 20px; }
        .comments-title { color: #ff6666; font-size: 1.1em; font-weight: bold; margin-bottom: 12px; }
        .comment { background: #111; padding: 10px; margin-bottom: 10px; border-radius: 6px; border-left: 3px solid #ff6666; }
        .comment-header { font-size: 0.85em; color: #aaa; margin-bottom: 4px; }
        .comment-header strong { color: #ff9999; }
        .comment-text { color: #eee; font-size: 0.9em; line-height: 1.4; }
        .comment-text strong { color: #ff6666; }
        .comment-form textarea {
            width: 100%;
            background: #111;
            color: #eee;
            border: 1px solid #333;
            border-radius: 6px;
            padding: 8px;
            resize: vertical;
            font-family: 'Segoe UI', sans-serif;
        }
        .comment-form button {
            margin-top: 6px;
            background: #ff6666;
            color: #fff;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }
        .comment-form button:hover { background: #ff8888; }
        .no-comments { color: #888; font-style: italic; margin: 10px 0; }
        .hidden-badge { background: #333; color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 0.82em; font-weight: bold; display: inline-block; margin: 4px 0; }
        @media (max-width: 768px) {
            .video-actions { flex-direction: column; align-items: center; }
            .download-btn { width: 100%; max-width: 300px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="levels.php" class="back-link">Back to List</a>

        <div class="level-header">
            <div class="level-name"><?php echo htmlspecialchars($level['level_name'] ?? 'Untitled'); ?></div>
            <div class="creator">by <strong><?php echo htmlspecialchars($level['creator'] ?? 'Unknown'); ?></strong></div>
            <div class="level-info"><strong>Difficulty:</strong> <?php echo htmlspecialchars($level['difficulty'] ?? 'N/A'); ?></div>
            <div class="level-info"><strong>Date:</strong> <?php echo htmlspecialchars($level['date'] ?? 'N/A'); ?></div>
            <?php if (!empty($level['top_rank'])): ?>
                <div style="margin-top:10px;display:inline-block;background:#cc0000;color:white;padding:4px 12px;border-radius:6px;font-weight:bold;">
                    Top <?php echo htmlspecialchars($level['top_rank']); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($level['hidden']) && $isAdmin): ?>
                <div class="hidden-badge">HIDDEN</div>
            <?php endif; ?>
            <br>
            <a href="https://gdbrowser.com/<?php echo urlencode($level['gdbrowser_id'] ?? ''); ?>" 
               target="_blank" rel="noopener" class="gdb-link">
                View on GD Browser
            </a>
        </div>

        <div class="video-container">
            <iframe 
                src="https://www.youtube.com/embed/<?php echo htmlspecialchars($ytId); ?>?rel=0&modestbranding=1&fs=1&cc_load_policy=0&iv_load_policy=3" 
                title="YouTube video player" 
                frameborder="0" 
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                allowfullscreen>
            </iframe>
        </div>

        <!-- VIDEO ACTIONS: YouTube, yt-dlp, Report -->
        <div class="video-actions">
            <a href="<?php echo $fullYtUrl; ?>" 
               target="_blank" class="download-btn" style="background:#ff6666;">
               Open in YouTube
            </a>
            <button id="copy-btn" class="download-btn">
               Copy yt-dlp Command
            </button>
            <a href="report.php?user=<?php echo urlencode($safeUser); ?>&level=<?php echo urlencode($safeName); ?>" 
               class="download-btn" style="background:#cc6600;">
               Report
            </a>
        </div>

        <!-- yt-dlp Guide -->
        <div class="yt-dlp-section">
            <h4>Download MP4 Directly from YouTube</h4>
            <p>1. Install yt-dlp: <code>pip install yt-dlp</code></p>
            <p>2. Copy & paste this command in terminal:</p>
            <div class="yt-dlp-cmd" id="cmd-text"><?php echo $ytDlpCmd; ?></div>
            <p class="install-note">Downloads highest quality MP4. <a href="https://github.com/yt-dlp/yt-dlp" target="_blank" style="color:#ff6666;">GitHub</a></p>
        </div>

        <!-- Comments Section -->
        <div class="comments-section">
            <div class="comments-title">Comments (<?php echo count($level['comments'] ?? []); ?>)</div>

            <?php if (!empty($level['comments'])): ?>
                <?php 
                usort($level['comments'], fn($a, $b) => strtotime($a['date']) - strtotime($b['date']));
                foreach ($level['comments'] as $c): 
                    $text = htmlspecialchars($c['text'] ?? '');
                    $text = preg_replace('/#(\w+)/', '<strong>#$1</strong>', $text);
                ?>
                    <div class="comment">
                        <div class="comment-header">
                            <strong><?php echo htmlspecialchars($c['username'] ?? 'Anonymous'); ?></strong>
                            <span> · <?php echo htmlspecialchars($c['date'] ?? ''); ?></span>
                        </div>
                        <div class="comment-text"><?php echo $text; ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-comments">No comments yet. Be the first!</p>
            <?php endif; ?>

            <?php if (isLoggedIn()): ?>
                <form method="POST" class="comment-form">
                    <textarea name="comment" rows="3" placeholder="Add a comment..." required></textarea>
                    <button type="submit">Post Comment</button>
                </form>
            <?php else: ?>
                <p style="margin-top:10px;color:#888;font-style:italic;">
                    <a href="login.php" style="color:#ff6666;">Login</a> to comment.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Copy Button Script -->
    <script>
        document.getElementById('copy-btn').addEventListener('click', async () => {
            const text = document.getElementById('cmd-text').innerText;
            try {
                await navigator.clipboard.writeText(text);
                alert('Command copied to clipboard!\nPaste into terminal.');
            } catch (err) {
                alert('Failed to copy. Select and copy manually.');
            }
        });
    </script>
</body>
</html>