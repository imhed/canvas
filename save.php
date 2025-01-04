<?php
$servername="localhost";
$username="root";
$password="";
$dbname="canvadatabase";
$conn="";

$conn=new mysqli($servername,$username,$password,$dbname);

if($conn->connect_error)
{
    die("Connection failed: ". $conn->connect_error);

}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $drawing_data = $_POST['drawing'];
    $full_name = $_POST['full_name'];

    $sql =("INSERT INTO drawings (full_name, drawing_data) VALUES (?, ?)");

    if ($conn ->query($sql) === TRUE){
        echo "image saved successfully";
    }else{
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}
$conn->close();
?>