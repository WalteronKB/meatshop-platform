<?php
include 'connection.php';
$query = "SELECT prod_id, prod_name, prod_mainpic FROM mrb_fireex LIMIT 10";
$result = mysqli_query($conn, $query);
echo "Current image paths in database:\n";
while($row = mysqli_fetch_assoc($result)) {
    echo "ID: " . $row['prod_id'] . " - Name: " . $row['prod_name'] . " - Path: " . $row['prod_mainpic'] . "\n";
}
mysqli_close($conn);
?>