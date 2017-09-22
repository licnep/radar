<?php

//allow the json to be loaded from other origins
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: x-requested-with');


$codalfa = $_GET['codalfa'];
$indicatore = $_GET['indicatore'];

$res = "{data:[";

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

if (!isset($_GET['hist'])) {
//SELECT i.timestamp, price, totalturnover FROM indicatoridaily as i, trades as t WHERE i.codalfa='A2A'and t.codalfa='A2A' and i.timestamp>(select DATE_FORMAT((SELECT max(timestamp) FROM indicatoridaily where codalfa='A2A'), '%Y-%m-%d 09:00:59')) and t.timestamp=i.timestamp order by timestamp;
        $sql = "SELECT t.timestamp, price, ".$indicatore." FROM indicatoridaily as i, trades as t WHERE i.codalfa='".$codalfa."' and t.codalfa='".$codalfa."' and i.timestamp>(select DATE_FORMAT((SELECT max(timestamp) FROM indicatoridaily where codalfa='".$codalfa."'), '%Y-%m-%d 09:00:59')) and t.timestamp=i.timestamp order by timestamp";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $lastdate = $row['timestamp'];
            //$res .= '{"date": "' . $row['timestamp'] . '","value": ' . $row[$indicatore] . "},\n";
            $res .= '{"date": "' . $row['timestamp'] . '","value": ' . $row[$indicatore] . ',"price": ' . $row['price'] . "},\n";
        }
    } else {
         $res .= "{}";
    }

} else {
    $sql = "SELECT ".$indicatore.", timewindow AS timewindow FROM indicatoristorici WHERE codalfa='".$codalfa."' ORDER BY timewindow"; //timewindow <= TIME(timestamp) AND
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            //echo '{"date": "' . $row['timewindow'] . '","value": ' . $row[$indicatore] . ',"value2": ' .  $row['storico'] . "},\n";
            $res .= '{"date": "' . substr($lastdate, 0,11) . $row['timewindow'] . '","value": ' . $row[$indicatore] . ',"price": ' . $row[$indicatore] . "},\n";
        }
    }
}

$conn->close();

$res .= "]}";

if (isset($_GET['callback'])) {
    echo $_GET['callback'] . "(".$res.");";
} else {
    echo $res;
}

?>
