<?php
error_reporting(0);

$baseDir = realpath(__DIR__);
$dir = $_GET['dir'] ?? $baseDir;
$dir = realpath($dir);
if ($dir === false || strpos($dir, $baseDir) !== 0) $dir = $baseDir;
chdir($dir);

function formatSize($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}
function perms($file) {
    $p = fileperms($file);
    return (($p & 0x4000) ? 'd' : '-') .
           (($p & 0x0100) ? 'r' : '-') .
           (($p & 0x0080) ? 'w' : '-') .
           (($p & 0x0040) ? 'x' : '-') .
           (($p & 0x0020) ? 'r' : '-') .
           (($p & 0x0010) ? 'w' : '-') .
           (($p & 0x0008) ? 'x' : '-') .
           (($p & 0x0004) ? 'r' : '-') .
           (($p & 0x0002) ? 'w' : '-') .
           (($p & 0x0001) ? 'x' : '-');
}
function redirect($to, $msg = '') {
    header("Location: ?dir=" . urlencode($to) . ($msg ? "&msg=" . urlencode($msg) : ''));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['mkdir'])) mkdir($_POST['mkdir']) && redirect($dir, "Folder created");
    if (!empty($_POST['newfile'])) touch($_POST['newfile']) && redirect($dir, "File created");
    if (!empty($_POST['rename']) && !empty($_POST['newname'])) rename($_POST['rename'], $_POST['newname']) && redirect($dir, "Renamed");
    if (!empty($_POST['chmod']) && !empty($_POST['chmod_target'])) chmod($_POST['chmod_target'], octdec($_POST['chmod'])) && redirect($dir, "Permission changed");
    if (!empty($_FILES['upload'])) move_uploaded_file($_FILES['upload']['tmp_name'], $_FILES['upload']['name']) && redirect($dir, "Uploaded");
    if (!empty($_POST['editfile']) && isset($_POST['content'])) file_put_contents($_POST['editfile'], $_POST['content']) && redirect($dir, "Saved");
    redirect($dir);
}
if (isset($_GET['download']) && is_file($_GET['download'])) {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($_GET['download']) . '"');
    readfile($_GET['download']); exit;
}
if (isset($_GET['delete'])) {
    $t = $_GET['delete'];
    is_dir($t) ? rmdir($t) : unlink($t);
    redirect($dir, "Deleted");
}
if (isset($_GET['zip'])) {
    $z = new ZipArchive(); $name = basename($_GET['zip']) . ".zip";
    if ($z->open($name, ZipArchive::CREATE)) {
        $p = $_GET['zip'];
        $it = is_dir($p) ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($p), RecursiveIteratorIterator::LEAVES_ONLY) : [$p];
        foreach ($it as $f) {
            if (is_string($f)) $z->addFile($f, basename($f));
            elseif (!$f->isDir()) $z->addFile($f->getRealPath(), substr($f->getRealPath(), strlen($p)+1));
        }
        $z->close();
    }
    redirect($dir, "Zipped");
}
if (isset($_GET['unzip'])) {
    $z = new ZipArchive();
    if ($z->open($_GET['unzip']) === TRUE) {
        $z->extractTo($dir); $z->close();
    }
    redirect($dir, "Unzipped");
}
$files = scandir($dir);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Simple File Manager</title>
    <style>
        body {
            font-family: sans-serif;
            background: #fafafa;
            color: #333;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #f0f0f0;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        input, textarea {
            font-family: monospace;
            padding: 4px;
            border: 1px solid #ccc;
        }
        textarea {
            width: 100%;
            height: 400px;
            margin-top: 10px;
        }
        button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 6px 12px;
            cursor: pointer;
        }
        button:hover {
            background: #45a049;
        }
        form.inline {
            display: inline-block;
            margin-right: 10px;
        }
        .msg {
            background: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
            padding: 10px;
            margin-top: 10px;
            display: inline-block;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<h2>🗂 Simple File Manager</h2>
<p><b>Current Directory:</b> <?= htmlspecialchars($dir) ?></p>

<?php if (!empty($_GET['msg'])): ?>
    <div class="msg"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    📤 <input type="file" name="upload" required>
    <button>Upload</button>
</form>

<form method="post" class="inline">
    📁 <input name="mkdir" placeholder="New Folder" required>
    <button>Create</button>
</form>

<form method="post" class="inline">
    📄 <input name="newfile" placeholder="New File" required>
    <button>Create</button>
</form>

<table>
    <tr>
        <th>Name</th><th>Size</th><th>Modified</th><th>Permissions</th><th>Actions</th>
    </tr>
    <?php if ($dir !== $baseDir): ?>
        <tr><td colspan="5"><a href="?dir=<?= urlencode(dirname($dir)) ?>">[..]</a></td></tr>
    <?php endif; ?>
    <?php foreach ($files as $f):
        if ($f === '.' || $f === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $f;
        $real = realpath($path);
        $is_dir = is_dir($path);
    ?>
    <tr>
        <td><?= $is_dir ? "<a href='?dir=" . urlencode($real) . "'>[{$f}]</a>" : htmlspecialchars($f) ?></td>
        <td><?= $is_dir ? '-' : formatSize(filesize($path)) ?></td>
        <td><?= date("d M Y, H:i", filemtime($path)) ?></td>
        <td><?= perms($path) ?></td>
        <td>
            <?php if (!$is_dir): ?>
                <a href="?download=<?= urlencode($path) ?>">Download</a> |
                <a href="?edit=<?= urlencode($path) ?>">Edit</a> |
            <?php endif; ?>
            <a href="?delete=<?= urlencode($path) ?>" onclick="return confirm('Delete this?')">Delete</a> |
            <a href="?zip=<?= urlencode($path) ?>">Zip</a>
            <?php if (pathinfo($path, PATHINFO_EXTENSION) === 'zip'): ?>
                | <a href="?unzip=<?= urlencode($path) ?>">Unzip</a>
            <?php endif; ?>
            <form method="post" class="inline">
                <input type="hidden" name="rename" value="<?= htmlspecialchars($path) ?>">
                <input name="newname" value="<?= htmlspecialchars(basename($path)) ?>">
                <button>Rename</button>
            </form>
            <form method="post" class="inline">
                <input type="hidden" name="chmod_target" value="<?= htmlspecialchars($path) ?>">
                <input name="chmod" value="<?= substr(sprintf('%o', fileperms($path)), -4) ?>" size="4">
                <button>Chmod</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<?php if (isset($_GET['edit']) && is_file($_GET['edit'])):
    $target = $_GET['edit'];
    $contents = htmlspecialchars(file_get_contents($target));
?>
<h3>✍️ Edit File: <?= htmlspecialchars(basename($target)) ?></h3>
<form method="post">
    <input type="hidden" name="editfile" value="<?= htmlspecialchars($target) ?>">
    <textarea name="content"><?= $contents ?></textarea><br>
    <button>💾 Save</button>
</form>
<?php endif; ?>
</body>
</html>
