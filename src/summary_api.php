<?php

//allow the json to be loaded from other origins
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: x-requested-with');


$res = "{data:{\n";

$servername = "localhost";
$username = "root";
$password = "zxcvbnm";
$dbname = "hedgefund";

$lastdate = "";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
     die("Connection failed: " . $conn->connect_error);
} 


$sql = "SELECT * FROM indicatoridaily AS i INNER JOIN (SELECT Max( id ) AS id FROM indicatoridaily GROUP BY codalfa) AS t ON t.id = i.id;";
$result = $conn->query($sql);

if ($result->num_rows > 0) {

     // output data of each row
     while($row = $result->fetch_assoc()) {
     	
     	$sql = "SELECT * FROM indicatoristorici WHERE codalfa='".$row["codalfa"]."' AND timewindow < TIME('".$row["timestamp"]."') ORDER BY timewindow DESC LIMIT 1";
     	$result2 = $conn->query($sql);
     	$row2 = $result2->fetch_assoc();

         $summary = ($row['totalturnover']-$row2['totalturnover'])/$row2['totalturnover'];
         $summary += ($row['turnover30']-$row2['turnover30'])/$row2['turnover30'];
         $summary += ($row['numberoftrades30']-$row2['numberoftrades30'])/$row2['numberoftrades30'];
         $summary += ($row['averageturnover30']-$row2['averageturnover30'])/$row2['averageturnover30'];
         $summary = $summary/4;

         $res .= "\"".$row["codalfa"]."\": ".number_format($summary*100,2, ".", "").",\n";
     }
}



$conn->close();

$res .= "}}";

if (isset($_GET['callback'])) {
    echo $_GET['callback'] . "(".$res.");";
} else {
    echo $res;
}

?>
