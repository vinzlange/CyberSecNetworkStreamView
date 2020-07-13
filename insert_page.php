<html>
<head>

<?php
$ip = $_GET['ip'];
$status = $_GET['status'];
$url = $_GET['url'];
$servername = "localhost";
$username = "pi";
$password = "";
$dbname = "test";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "UPDATE ip_filter SET status = '$status' WHERE (INET6_NTOA(ip_filter.ip) = '$ip')";



if ($conn->query($sql) === TRUE) {
    echo "New record created successfully";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
    <link href="style.css" type="text/css" rel="stylesheet" />
    <meta http-equiv="refresh" content="0; url=<?php echo $url?>">
</head>
</html>
