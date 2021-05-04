<!DOCTYPE html>
<html>
<head>
<style type="text/css">
.tg  {border-collapse:collapse;border-spacing:0;border-color:#6E6E6E;margin:0px auto;}
.tg td{font-family:Arial, sans-serif;font-size:15.5px;padding:12px 16px;border-style:outset;border-width:1px;overflow:hidden;word-break:normal;border-color:#948e8e;color:#8c7b7b;background-color:#222;}
.tg th{font-family:Arial, sans-serif;font-size:15.5px;font-weight:normal;padding:15px 19px;border-style:outset;border-width:1px;overflow:hidden;word-break:normal;border-color:#6E6E6E;color:#afa6a6;background-color:#0e3252;}
.tg .tg-yw4l{vertical-align:top}
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
    
echo "<table class=\"tg\"><tr><th>Codalfa</th><th>Tempo</th><th>Controvalore Totale</th><th>Controvalore 30</th><th>Num.Trade 30</th><th>Contr.Medio 30</th><th>Market Delta 30</th><th>Market Buy % 30</th><th>Volatilità</th><th>Spread 30</th><th>Impatto Book</th><th>Impatto Buy</th><th>Impatto Sell</th></tr>";
    
#$sql = "SELECT * FROM indicatoridaily WHERE id IN (SELECT Max( id ) FROM indicatoridaily GROUP BY codalfa ) order by codalfa";
#$sql = "SELECT * FROM indicatoristorici AS i INNER JOIN (SELECT Max( id ) AS id FROM indicatoridaily GROUP BY codalfa) AS t ON t.id = i.id;";
$sql = "SELECT * FROM indicatoristorici WHERE timewindow = (SELECT MAX(timewindow) from indicatoristorici WHERE timewindow<= (SELECT TIME(MAX(timestamp)) FROM indicatoridaily) );";
$result = $conn->query($sql);

if ($result->num_rows > 0) {

     // output data of each row
     while($row = $result->fetch_assoc()) {
         echo "<tr>";
         stampaCella($row["codalfa"]);
         stampaCella($row["timewindow"]);
         foreach (array("totalturnover", "turnover30", "numberoftrades30", "averageturnover30", "marketorderdelta30", "marketbuypercentage30", "standarddeviation", "spread30", "bookimpact30", "bookimpactbuy30", "bookimpactsell30") as $indicatore) {
$valore = $row[$indicatore];
             if (in_array($indicatore, array("totalturnover", "turnover30", "averageturnover30"))) {
                 $valore = number_format($valore) . " €"; 
                 $link = creaLink($row["codalfa"], $indicatore, $valore );
             }
             else if ($indicatore == "marketorderdelta30") {
                 $valore = number_format($valore) . " €"; 
                 if ($valore>=0){$link = creaLink($row["codalfa"], $indicatore, $valore, "green" );}
                else{$link = creaLink($row["codalfa"], $indicatore, $valore, "red" );}
                 
             }
             else if ($indicatore == "marketbuypercentage30") {
                 $valore = number_format($valore*100, 2) . " %"; 
                 if ($valore>=50){$link = creaLink($row["codalfa"], $indicatore, $valore, "green" );}
                else{$link = creaLink($row["codalfa"], $indicatore, $valore, "red" );}
             }
             else if ($indicatore == "standarddeviation") {
                 $valore = number_format($valore*100, 2) . " %"; 
                 $link = creaLink($row["codalfa"], $indicatore, $valore );
             }
             else{$link = creaLink($row["codalfa"], $indicatore, $valore );}
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
    

function creaLink($codalfa, $indicatore, $dato, $class="") {
    return "<a href=\"#\" class=\"".$class."\" onClick=\"window.open('chartstorico.php?codalfa=".$codalfa."&indicatore=".$indicatore."','".$codalfa.$indicatore."','resizable,width=800,height=600,left=300,top=150'); return false;\">".$dato."</a>";
}
    
function stampaCella($dato) {
    echo "<td>" . $dato . "</td>";
}
    
?>  

</body>    
</html>