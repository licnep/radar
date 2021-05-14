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

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
     die("Connection failed: " . $conn->connect_error);
} 

if (!isset($_GET['timestamp'])) {
    $sql = "SELECT timestamp, ".$indicatore.", COALESCE((SELECT ".$indicatore." FROM indicatoristorici WHERE timewindow <= TIME(timestamp) AND codalfa='".$codalfa."' ORDER BY timewindow DESC LIMIT 1), 0) AS storico, (SELECT price FROM trades AS t WHERE t.codalfa='".$codalfa."' AND t.timestamp = indicatoridaily.timestamp LIMIT 1) AS price FROM indicatoridaily WHERE codalfa='".$codalfa."' and timestamp>(select DATE_FORMAT((SELECT max(timestamp) FROM indicatoridaily where codalfa='".$codalfa."'), '%Y-%m-%d 09:00:59')) order by timestamp";
} else if (!isset($_GET['maxTimestamp'])) {
    $sql = "SELECT timestamp, ".$indicatore.", COALESCE((SELECT ".$indicatore." FROM indicatoristorici WHERE timewindow <= TIME(timestamp) AND codalfa='".$codalfa."' ORDER BY timewindow DESC LIMIT 1), 0) AS storico, (SELECT price FROM trades AS t WHERE t.codalfa='".$codalfa."' AND t.timestamp = indicatoridaily.timestamp LIMIT 1) AS price FROM indicatoridaily WHERE codalfa='".$codalfa."'".
            " and timestamp>(select DATE_FORMAT(FROM_UNIXTIME(".$_GET['timestamp']."), '%Y-%m-%d 09:00:59'))".
            " and timestamp<(select DATE_FORMAT(FROM_UNIXTIME(".$_GET['timestamp']."), '%Y-%m-%d 18:00:00')) order by timestamp";
} else {
    $sql = "SELECT timestamp, ".$indicatore.", COALESCE((SELECT ".$indicatore." FROM indicatoristorici WHERE timewindow <= TIME(timestamp) AND codalfa='".$codalfa."' ORDER BY timewindow DESC LIMIT 1), 0) AS storico, (SELECT price FROM trades AS t WHERE t.codalfa='".$codalfa."' AND t.timestamp = indicatoridaily.timestamp LIMIT 1) AS price FROM indicatoridaily WHERE codalfa='".$codalfa."'".
            " and timestamp>(select DATE_FORMAT(FROM_UNIXTIME(".$_GET['timestamp']."), '%Y-%m-%d 09:00:59'))".
            " and timestamp<LEAST(DATE_FORMAT(FROM_UNIXTIME(".$_GET['timestamp']."), '%Y-%m-%d 18:00:00'), FROM_UNIXTIME(".$_GET['maxTimestamp'].")) order by timestamp";
}

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        //echo '{"date": "' . $row['timestamp'] . '","value": ' . $row[$indicatore] . ',"value2": ' .  $row['storico'] . "},\n";
        $res .= '{"date": "' . $row['timestamp'] . '","indicatore": ' . $row[$indicatore] . ',"storico": ' .  $row['storico'] . ', "price": ' . $row['price']  ."},\n";
    }
} else {
     $res.= "";
}

$conn->close();

$res .= "]}";

if (isset($_GET['callback'])) {
    echo $_GET['callback'] . "(".$res.");";
} else {
    echo $res;
}

?>
