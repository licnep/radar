<!DOCTYPE html>
<html>
<head>
<style type="text/css">
.tg  {border-collapse:collapse;border-spacing:0;border-color:#999;margin:0px auto;}
.tg td{font-family:Arial, sans-serif;font-size:14px;padding:10px 14px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#999;color:#444;background-color:#F7FDFA;}
.tg th{font-family:Arial, sans-serif;font-size:14px;font-weight:normal;padding:10px 14px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#999;color:#fff;background-color:#26ADE4;}
.tg .tg-yw4l{vertical-align:top}
</style>
<body>
<?php
$servername = "localhost";
$username = "root";
$password = "zxcvbnm";
$dbname = "hedgefund";   

$codalfa = $_GET['codalfa'];
$indicatore = $_GET['indicatore'];
    

    
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
     die("Connection failed: " . $conn->connect_error);
} 
    
    
$sql = "SELECT timestamp, ".$indicatore." FROM indicatoridaily WHERE codalfa='".$codalfa."' order by timestamp";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo $row['timestamp'] . " - " . $row[$indicatore] . "<br/>";
    }
} else {
     echo "0 results";
}

$conn->close();
    

function creaLink($codalfa, $indicatore, $dato) {
    return "<a href=\"#\" onClick=\"window.open('http://www.google.com?q=".$indicatore."','MiaFinestra','resizable,width=800,height=600,left=300,top=150'); return false;\">".$dato."</a>";
}
    
function stampaCella($dato) {
    echo "<td>" . $dato . "</td>";
}
    
?>  

</body>    
</html>