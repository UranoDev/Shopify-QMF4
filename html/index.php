<?php
include_once '../includes/utils.php';
include_once 'header.php';

$sql = "SELECT * from shops WHERE shop = '$shop' and uninstalled IS NULL";
$result = $conn->query($sql);
$row = $result->fetch_assoc();



?>

<h2>Hola!!!</h2>
<?php
include_once 'footer.php';
