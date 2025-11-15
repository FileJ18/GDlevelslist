<?php
// submit.php - Submit Level + Comment
require_once 'db.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$levelsFile = 'levels.json';
$levels = file_exists($levelsFile) ? json_decode(file_get_contents($levelsFile), true) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $level_name     = trim($_POST['level_name']);
    $difficulty     = trim($_POST['difficulty']);
    $youtube_id     = trim($_POST['youtube_id']);
    $gdbrowser_id   = trim($_POST['gdbrowser_id']);
    $comment        = trim($_POST['comment'] ?? ''); // Optional comment

    if (!empty($level_name) && !empty($difficulty) && !empty($youtube_id) && !empty($gdbrowser_id)) {
        $newLevel = [
            'id'           => uniqid(),
            'level_name'   => $level_name,
            'difficulty'   => $difficulty,
            'creator'      => $_SESSION['username'],
            'youtube_id'   => $youtube_id,
            'gdbrowser_id' => $gdbrowser_id,
            'date'         => date('Y-m-d H:i:s'),
            'top_rank'     => null,
            'comments'     => [] // Initialize comments array
        ];

        // Add comment if provided
        if ($comment !== '') {
            $newLevel['comments'][] = [
                'username' => $_SESSION['username'],
                'text'     => $comment,
                'date'     => date('Y-m-d H:i:s')
            ];
        }

        $levels[] = $newLevel;
        file_put_contents($levelsFile, json_encode($levels, JSON_PRETTY_PRINT));
        $success = "Level submitted successfully!";
    } else {
        $error = "Level name, difficulty, YouTube ID, and GD Browser ID are required.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Level - Darkness List</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #000;
            color: #eee;
            font-family: 'Segoe UI', sans-serif;
            padding: 20px 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 0 15px;
        }
        h1 {
            text-align: center;
            color: #ff6666;
            font-size: 2em;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .nav {
            text-align: center;
            margin-bottom: 20px;
            font-size: 0.95em;
        }
        .nav strong {
            color: #ff9999;
        }
        .logout-btn {
            background: #cc0000;
            color: white;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 0.9em;
            font-weight: bold;
            text-decoration: none;
            margin-left: 10px;
        }
        .logout-btn:hover {
            background: #990000;
        }

        .form-group {
            margin-bottom: 16px;
        }
        label {
            display: block;
            color: #ff9999;
            margin-bottom: 6px;
            font-weight: bold;
            font-size: 0.95em;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            background: #111;
            border: 1px solid #444;
            color: #eee;
            border-radius: 6px;
            font-size: 1em;
        }
        input:focus, textarea:focus {
            outline: none;
            border-color: #ff6666;
            box-shadow: 0 0 8px rgba(255,102,102,0.3);
        }
        textarea {
            min-height: 80px;
            resize: vertical;
        }

        .submit-btn {
            background: #cc0000;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 1em;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
            transition: all 0.3s ease;
        }
        .submit-btn:hover {
            background: #990000;
            transform: translateY(-2px);
        }

        .success {
            background: #002200;
            color: #66ff66;
            padding: 12px;
            border-radius: 6px;
            text-align: center;
            margin: 15px 0;
            font-weight: bold;
        }
        .error {
            background: #220000;
            color: #ff6666;
            padding: 12px;
            border-radius: 6px;
            text-align: center;
            margin: 15px 0;
            font-weight: bold;
        }

        .back-link {
            display: block;
            text-align: center;
            margin: 20px 0;
            color: #ff6666;
            text-decoration: none;
            font-weight: bold;
        }
        .back-link:hover {
            color: #ff9999;
            text-decoration: underline;
        }

        .note {
            font-size: 0.85em;
            color: #aaa;
            margin-top: 6px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Submit a Level</h1>

        <div class="nav">
            Logged in as: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>

        <?php if (isset($success)): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="level_name">Level Name *</label>
                <input type="text" name="level_name" id="level_name" required 
                       value="<?php echo $_POST['level_name'] ?? ''; ?>" placeholder="e.g. Bloodbath">
            </div>

            <div class="form-group">
                <label for="difficulty">Difficulty *</label>
                <input type="text" name="difficulty" id="difficulty" required 
                       value="<?php echo $_POST['difficulty'] ?? ''; ?>" placeholder="e.g. Extreme Demon">
            </div>

            <div class="form-group">
                <label for="youtube_id">YouTube Video ID *</label>
                <input type="text" name="youtube_id" id="youtube_id" required 
                       value="<?php echo $_POST['youtube_id'] ?? ''; ?>" placeholder="e.g. dQw4w9WgXcQ">
                <p class="note">From URL: https://www.youtube.com/watch?v=<strong>dQw4w9WgXcQ</strong></p>
            </div>

            <div class="form-group">
                <label for="gdbrowser_id">GD Browser ID *</label>
                <input type="text" name="gdbrowser_id" id="gdbrowser_id" required 
                       value="<?php echo $_POST['gdbrowser_id'] ?? ''; ?>" placeholder="e.g. 128">
                <p class="note">From URL: https://gdbrowser.com/<strong>128</strong></p>
            </div>

            <div class="form-group">
                <label for="comment">Comment (optional)</label>
                <textarea name="comment" id="comment" placeholder="Say something about this level... use #hashtags"></textarea>
                <p class="note">Your comment will appear under the level. #hashtag → highlighted in red.</p>
            </div>

            <button type="submit" class="submit-btn">Submit Level</button>
        </form>

        <a href="levels.php" class="back-link">Back to Levels</a>
    </div>
</body>
</html>