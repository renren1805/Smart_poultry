<?php
require('../connection.php');

if (isset($_POST['update'])) {

    $id = $_POST['id'];
    $product = $_POST['Product'];

    $get = mysqli_query($connection, "SELECT * FROM admins WHERE UserID='$id'");
    $data = mysqli_fetch_assoc($get);

    $oldPicture = $data['Picture'];


    if($_FILES['Picture']['name'] != "") {

        $newPicture = $_FILES['Picture']['name'];
        $tempName = $_FILES['Picture']['tmp_name'];
        $folder = "picture/".$newPicture;

        move_uploaded_file($tempName, $folder);

        $oldpath = "picture/".$oldPicture;

        if(file_exists($oldpath)){
            unlink($oldpath);
        }

        $squery = "UPDATE admins
                   SET Product='$product', Picture='$newPicture' 
                   WHERE UserID='$id'";
    } else {

        $squery = "UPDATE admins 
                   SET Product='$product' 
                   WHERE UserID='$id'";
    }

mysqli_query($connection, $squery);
header("Location: dashboard.php");
exit();

}
?>