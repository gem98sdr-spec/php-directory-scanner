<?php
$baseDir = realpath(__DIR__);
$ignore = ['.git', '.idea', 'node_modules', 'vendor'];

$maxDepth = isset($_GET['depth']) && is_numeric($_GET['depth']) && $_GET['depth'] !== '' ? (int)$_GET['depth'] : -1;
$extFilter = !empty($_GET['ext']) ? array_map('trim', explode(',', strtolower($_GET['ext']))) : [];
$showHidden = isset($_GET['hidden']) && $_GET['hidden'] === '1';

function formatSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

function scanDirRecursive($dir, $ignore, $maxDepth, $currentDepth, $extFilter, $showHidden, &$stats) {
    $result = [];
    if ($maxDepth !== -1 && $currentDepth > $maxDepth) return $result;
    
    $items = @scandir($dir);
    if (!$items) return $result;
    
    $folders = [];
    $files = [];
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        if (!$showHidden && $item[0] === '.') continue;
        if (in_array($item, $ignore)) continue;
        
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            $folders[] = $item;
        } else {
            $files[] = $item;
        }
    }
    
    foreach ($folders as $item) {
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        $stats['dirs']++;
        $sub = scanDirRecursive($path, $ignore, $maxDepth, $currentDepth + 1, $extFilter, $showHidden, $stats);
        if (!empty($sub) || empty($extFilter)) {
             $result[$item] = $sub;
        }
    }
    
    foreach ($files as $item) {
        $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
        if (!empty($extFilter) && !in_array($ext, $extFilter)) continue;
        
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        $stats['files']++;
        $size = @filesize($path) ?: 0;
        $stats['size'] += $size;
        $result[] = ['name' => $item, 'size' => $size];
    }
    return $result;
}

$stats = ['files' => 0, 'dirs' => 0, 'size' => 0];
$tree = scanDirRecursive($baseDir, $ignore, $maxDepth, 0, $extFilter, $showHidden, $stats);

if (isset($_GET['api']) && $_GET['api'] === 'true') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['path' => $baseDir, 'options' => ['depth' => $maxDepth, 'extensions' => $extFilter, 'hidden' => $showHidden], 'statistics' => $stats, 'tree' => $tree]);
    exit;
}

function renderTree($array, $level = 0) {
    if (empty($array)) return '';
    $html = '<ul style="list-style-type: none; padding-left: ' . ($level > 0 ? '24px' : '0') . ';">';
    foreach ($array as $key => $value) {
        if (is_array($value) && !isset($value['name'])) {
            $html .= '<li><span class="icon">📁</span> <strong>' . htmlspecialchars($key) . '</strong>' . renderTree($value, $level + 1) . '</li>';
        } elseif (isset($value['name'])) {
            $html .= '<li><span class="icon">📄</span> ' . htmlspecialchars($value['name']) . ' <span class="size">(' . formatSize($value['size']) . ')</span></li>';
        }
    }
    $html .= '</ul>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Directory Tree Scanner - Advanced</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background-color: #0d1117; color: #c9d1d9; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: #161b22; border: 1px solid #30363d; border-radius: 8px; padding: 25px; }
        .header { border-bottom: 1px solid #30363d; padding-bottom: 15px; margin-bottom: 20px; }
        .header h2 { margin: 0 0 10px 0; color: #58a6ff; }
        code { background: #23272d; padding: 4px 8px; border-radius: 6px; font-size: 0.9em; }
        .controls { background: #21262d; border: 1px solid #30363d; padding: 15px; border-radius: 6px; margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap; align-items: center; }
        .controls label { font-size: 0.9em; display: flex; align-items: center; gap: 5px; }
        .controls input[type="text"], .controls input[type="number"] { background: #0d1117; border: 1px solid #30363d; color: #c9d1d9; padding: 6px 10px; border-radius: 4px; }
        .controls button { background: #238636; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .controls button:hover { background: #2ea043; }
        .stats { display: flex; gap: 15px; margin-bottom: 25px; flex-wrap: wrap; }
        .stat-box { flex: 1; min-width: 150px; background: #21262d; border: 1px solid #30363d; padding: 15px; border-radius: 6px; text-align: center; }
        .stat-box span { display: block; font-size: 24px; font-weight: bold; color: #3fb950; margin-top: 5px; }
        .tree-view { background: #0d1117; border: 1px solid #30363d; padding: 20px; border-radius: 6px; overflow-x: auto; line-height: 1.6; }
        .tree-view ul { margin: 5px 0; }
        .tree-view li { margin: 4px 0; white-space: nowrap; }
        .icon { display: inline-block; width: 20px; text-align: center; margin-right: 5px; }
        .size { color: #8b949e; font-size: 0.85em; margin-left: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Directory Tree Scanner</h2>
            <p>Scanned Path: <code><?php echo htmlspecialchars($baseDir); ?></code></p>
        </div>
        
        <form class="controls" method="GET">
            <label>Ext Filter: <input type="text" name="ext" placeholder="php,js,css" value="<?php echo htmlspecialchars($_GET['ext'] ?? ''); ?>"></label>
            <label>Max Depth: <input type="number" name="depth" placeholder="Unlimited" min="0" value="<?php echo htmlspecialchars($_GET['depth'] ?? ''); ?>"></label>
            <label><input type="checkbox" name="hidden" value="1" <?php echo $showHidden ? 'checked' : ''; ?>> Show Hidden Files</label>
            <button type="submit">Scan Now</button>
        </form>

        <div class="stats">
            <div class="stat-box">Files <span><?php echo number_format($stats['files']); ?></span></div>
            <div class="stat-box">Folders <span><?php echo number_format($stats['dirs']); ?></span></div>
            <div class="stat-box">Size <span><?php echo formatSize($stats['size']); ?></span></div>
        </div>
        <div class="tree-view">
            <?php echo renderTree($tree) ?: '<em>No files found matching criteria.</em>'; ?>
        </div>
    </div>
</body>
</html>
