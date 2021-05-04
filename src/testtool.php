<!DOCTYPE html>
<html>
<head>
<style type="text/css">
.tg  {border-collapse:collapse;border-spacing:0;border-color:#999;margin:0px auto;}
.tg td{font-family:Arial, sans-serif;font-size:14px;padding:10px 14px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#999;color:blue;background-color:white;}
.tg th{font-family:Arial, sans-serif;font-size:14px;font-weight:normal;padding:10px 14px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#999;color:#fff;background-color:lightskyblue;}
.tg .tg-yw4l{vertical-align:top}
body {background-color: white;}
a {color:blue;}
</style>
<body>
<?php
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
    
echo "<table class=\"tg\"><tr><th>Codalfa</th><th>Tempo</th><th>Controvalore Totale</th><th>Controvalore 30</th><th>Numero Trade 30</th><th>Controvalore medio 30</th><th>Market Delta 30</th><th>Market Buy Percentage 30</th><th>Volatilità</th><th>Spread 30</th><th>Impatto Book Medio</th><th>Impatto Buy</th><th>Impatto Sell</th></tr>";
    
$sql = "SELECT * FROM indicatoridaily WHERE id IN (SELECT Max( id ) FROM indicatoridaily GROUP BY codalfa ) order by codalfa";
$result = $conn->query($sql);

if ($result->num_rows > 0) {

     // output data of each row
     while($row = $result->fetch_assoc()) {
         echo "<tr>";
         stampaCella($row["codalfa"]);
         stampaCella($row["timestamp"]);
         foreach (array("totalturnover", "turnover30", "numberoftrades30", "averageturnover30", "marketorderdelta30", "marketbuypercentage30", "standarddeviation", "spread30", "bookimpact30", "bookimpactbuy30", "bookimpactsell30") as $indicatore) {
             $link = creaLink($row["codalfa"], $indicatore, $row[$indicatore] );
             stampaCella( $link );
         }
         echo "<tr/>";
         /*echo "<tr><td>".$row["codalfa"]."</td><td>".$row["timestamp"]."</td><td>".$row["totalturnover"]." €</td><td>".$row["turnover30"]." €</td><td>".$row["numberoftrades30"]."</td><td>".$row["averageturnover30"]." €</td><td>".$row["marketorderdelta30"]." €</td><td>".$row["marketbuypercentage30"]."</td><td>".$row["standarddeviation"]."</td><td>".$row["spread"]."</td><td>".$row["bookimpact"]."</td></tr>";*/
     }
     echo "</table>";
} else {
     echo "0 results";
}

$conn->close();
    

function creaLink($codalfa, $indicatore, $dato) {
    return "<a href=\"#\" onClick=\"window.open('chart.php?codalfa=".$codalfa."&indicatore=".$indicatore."','".$codalfa.$indicatore."','resizable,width=800,height=600,left=300,top=150'); return false;\">".$dato."</a>";
}
    
function stampaCella($dato) {
    echo "<td>" . $dato . "</td>";
}
    
?>  

</body>    
</html>