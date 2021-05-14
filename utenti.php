<!DOCTYPE html>
<html>
<head>
<style type="text/css">
.tg  {border-collapse:collapse;border-spacing:0;border-color:#6E6E6E;margin:0px auto;}
.tg td{font-family:Arial, sans-serif;font-size:15.5px;padding:12px 16px;border-style:outset;border-width:1px;overflow:hidden;word-break:normal;border-color:#948e8e;color:#8c7b7b;background-color:#222;}
.tg th{font-family:Arial, sans-serif;font-size:15.5px;font-weight:normal;padding:15px 19px;border-style:outset;border-width:1px;overflow:hidden;word-break:normal;border-color:#6E6E6E;color:#afa6a6;background-color:#0e3252;}
.tg .tg-yw4l{vertical-align:top}
td button {margin-left:10px}
body {background-color: #444;}
a {color:#ada0a0;
text-decoration: none;}
.green {
    color: #12a012;
}   
.red {
    color: #da2929;
}
</style>
<body>
<table class="tg"><tr><th>N</th><th>Email</th><th>Nome</th><th>Cognome</th><th>Data iscrizione</th><th>Scadenza abbonamento</th></tr>
<?php

//allow the json to be loaded from other origins
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: x-requested-with');


$servername = "localhost";
$username = "root";
$password = "zxcvbnm";
$dbname = "aurora";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
     die("Connection failed: " . $conn->connect_error);
} 

//attivazione se richiesta
if (isset($_GET["attiva"])) {
    $result = $conn->query("INSERT INTO subscription SET user_id = " . $conn->real_escape_string($_GET['attiva']) . ", subscription_next_payment = UNIX_TIMESTAMP()+60*60*24*365, subscription_status='active', merchant_subscription_id=0, subscription_start_date=UNIX_TIMESTAMP(), subscription_current_billing_start=UNIX_TIMESTAMP(), subscription_initial_amount=0, subscription_amount=0;");
}
if (isset($_GET["disattiva"])) {
    $result = $conn->query("UPDATE subscription SET subscription_status='terminated' WHERE user_id = " . $conn->real_escape_string($_GET['disattiva']) . ";");
}

$sql = "SELECT *, FROM_UNIXTIME(added_at) AS timestamp, FROM_UNIXTIME(next_payment) AS next_payment 
FROM users LEFT JOIN (SELECT user_id, MAX(subscription_next_payment) AS next_payment FROM subscription WHERE subscription_status != 'terminated' GROUP BY user_id) as sub ON users.id = sub.user_id 
WHERE added_at > 1504543906 AND last_name <> 'Buffetti' AND last_name <> 'testtt' ORDER BY added_at DESC";
$result = $conn->query($sql);

$i = 0;

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        //print_r($row);
        //echo '{"date": "' . $row['timestamp'] . '","value": ' . $row[$indicatore] . ',"value2": ' .  $row['storico'] . "},\n";
        //. $row['timestamp'] . '","indicatore": ' . $row[$indicatore] . ',"storico": ' .  $row['storico'] . ', "price": ' . $row['price']  ."},\n";
	$i += 1;
	echo "<tr><td>" . $i . "</td><td>". $row['email'] . "</td><td>" . $row['first_name'] . "</td><td>" . $row['last_name'] . "</td><td>" . $row['timestamp']  . "</td><td>" . $row['next_payment'] . 
             " <button onclick=\"attiva(".$row['id'].")\">Attiva per un anno</button> <button onclick=\"disattiva(".$row['id'].")\">Disattiva</button></td></tr>";
    }
} else {
     $res.= "";
}

$conn->close();


?>
</table>
</body>
<script>
function attiva(n) {fetch("http://35.187.38.51/utenti.php?attiva="+String(n)).then(data=>{location.reload();})}
function disattiva(n) {fetch("http://35.187.38.51/utenti.php?disattiva="+String(n)).then(data=>{location.reload();})}
</script>
</html>
