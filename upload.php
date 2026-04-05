<?php
    include 'connection.php';
    if(isset($_POST['submit'])){
        $file_name = $_FILES['image']['name'];
        $file_temp = $_FILES['image']['tmp_name'];
        $folder = 'Images/' . $file_name;

        $query = "UPDATE mrb_fireex  SET prod_mainpic = '$folder' where prod_id = 101";
        $result = mysqli_query($conn, $query);

        if($result){
            if(move_uploaded_file($file_temp, $folder)){
                echo "<script>
                            alert('File uploaded successfully.')
                      </script>;";
            } else {
                echo "<script>
                            alert('File not uploaded.')
                      </script>;";
            }
        } else {
            echo  "<script>
                        alert("."Database query failed: " . mysqli_error($conn) . "')
                   </script>";
        }

    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form action="upload.php" method="POST" enctype="multipart/form-data">
        <input type="file" name="image" id="file">
        <input type="submit" name="submit" value="Upload">

    </form>
    <?php
        $query = "SELECT prod_mainpic FROM mrb_fireex WHERE prod_id = 101";
        $result = mysqli_query($conn, $query);
        
        $row = mysqli_fetch_assoc($result);
    ?>
    <img src="<?php echo  $row['prod_mainpic']?>" alt="">
</body>
</html>