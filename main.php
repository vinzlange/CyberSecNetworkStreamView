<!DOCTYPE html>
<html lang="de">
<script src="jquery-3.4.1.min.js"></script>
<script src="jquery-ui-1.12.1/jquery-ui.min.js"></script>
<link href="slider.css" type="text/css" rel="stylesheet" />
<link href="style.css" type="text/css" rel="stylesheet" />
<link href="radiobutton.css" type="text/css" rel="stylesheet" />
<head>
    <meta charset="UTF-8">
<!--       <meta http-equiv="refresh" content="5">-->
    <title>Main</title>


    <script>
        var CHANGE = false;
        function Myfunktion() {
            if(CHANGE){
                var x = document.getElementById("form_slider");
                x.submit();
                console.log(x)
            }
            console.log(CHANGE);
        }
        function Change() {
            var x = document.getElementById("form_toggle");
            x.submit();
        }
        var cookieList = (document.cookie) ? document.cookie.split('; ') : [];

        var cookieValues = {};
        for (var i = 0, n = cookieList.length; i != n; ++i) {
            var cookie = cookieList[i];
            var f = cookie.indexOf('=');
            if (f >= 0) {
                var cookieName = cookie.substring(0, f);
                var cookieValue = cookie.substring(f + 1);

                console.log ("cookie: " + cookieName + ": " + cookieValue);

                if (!cookieValues.hasOwnProperty(cookieName)) {
                    cookieValues[cookieName] = cookieValue;
                }
            }
        }


    </script>


</head>
<body class="fullscreen" onmouseup="Myfunktion()">
<!--SQL VERBINDUNG-->
<?php
include ("functions.php");
$click_ip = "192.168.1.1";
$netzmaske = 4;

//Sind cookies vorhanden?
if (!isset($_COOKIE["max"])){
    setcookie("max","24:00:00");
    setcookie("min","00:00:00");
    setcookie("max_sec","86400");
    setcookie("min_sec","0");
}
if (isset($_POST['group-b'])){
    $netzmaske = $_POST['group-b'];
    setcookie("netzmaske",$_POST['group-b']);
}else{
    $netzmaske = $_COOKIE["netzmaske"];
}
echo $netzmaske;
echo "<br>";



$conn = new mysqli($servername, $username, $password, $dbname);
if (isset($_GET['ip'])) {


    $click_ip = $_GET['ip'];
// Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }


}
$connection = array();
$endpoint = array();
$distance = 200;
$max_circle = 45;
$normalise = 0;


//Initialisiere der ip_filter Tabelle
$sql = " SELECT
    SUBSTRING_INDEX( INET6_NTOA(tmpip),'.','$netzmaske')  as inetsrc
FROM (SELECT
          src as tmpip
      FROM connections
      GROUP BY tmpip
      UNION (SELECT
                 dst as tmpip
            FROM connections


             GROUP BY tmpip)) t
GROUP BY inetsrc";

$result = $conn->query($sql);
for($i = 0; $row = $result->fetch_assoc(); $i++) {

    $tmp_ip = $row["inetsrc"];
    $sql = "INSERT IGNORE INTO ip_filter (ip, status) VALUE (INET6_ATON('$tmp_ip'), '1')";   //Initialisiere der ip_filter Tabelle
    $conn->query($sql);
}

//DATEN AUSLESEN
if ($_COOKIE["max"] == "live"){
    $_COOKIE["max"] = date("H:i:s",time());
    //TESTZWECK
    $_COOKIE["max"] = "24:00:00";
}
echo $_COOKIE["max"];
$sql = "SELECT
    inetsrc,
    inetdst,
    count,
    length,
    avg,
    statustmp * ip_filter.status as status
FROM (
         SELECT
             time,
             SUBSTRING_INDEX( INET6_NTOA(src),'.','$netzmaske')  as inetsrc,
             SUBSTRING_INDEX( INET6_NTOA(dst),'.','$netzmaske')  as inetdst,
             INET6_NTOA(dst)  as inetdsttmp,
             Count(*)         as count,
             SUM(length)      as length,
             AVG(length)      as avg,
             ip_filter.status as statustmp

         FROM connections
                  LEFT JOIN ip_filter ON connections.src = ip_filter.ip
         WHERE time >= '".$_COOKIE["min"]."' AND time <= '".$_COOKIE["max"]."'
         GROUP BY inetsrc, inetdst
     )t
         LEFT JOIN ip_filter ON inetdsttmp = INET6_NTOA(ip_filter.ip)
WHERE status > 0 AND statustmp > 0 AND inetsrc != inetdst;
";

$result = $conn->query($sql);
$sql2 = "SELECT
    SUBSTRING_INDEX( INET6_NTOA(tmpip),'.','$netzmaske')  as inetsrc,
    SUM(send) as send,
    SUM(received) as received,
    SUM(send) + SUM(received) as summe,
    SUM(length) as length,
    ip_filter.status as status
FROM (SELECT
          src as tmpip,
          Count(*) as send,
          '0' as received,
          SUM(length) as length
      FROM connections
      WHERE time >= '".$_COOKIE["min"]."' AND time <= '".$_COOKIE["max"]."'
      GROUP BY tmpip
      UNION (SELECT
                 dst as tmpip,
                 '0' as send,
                 Count(*) as received,
                 SUM(length) as length

             FROM connections
             WHERE time >= '".$_COOKIE["min"]."' AND time <= '".$_COOKIE["max"]."'

             GROUP BY tmpip)) t
         LEFT JOIN ip_filter ON ip = tmpip
WHERE status > 0
GROUP BY inetsrc";
$result2 = $conn->query($sql2);




//Wandeln von result2 in 2D Array Endpoint
for($i = 0; $row = $result2->fetch_assoc(); $i++) {
    $row["x"] = (float) sin(deg2rad($i*360/$result2->num_rows))*$distance;
    $row["y"] = (float) cos(deg2rad($i*360/$result2->num_rows))*$distance;
    $endpoint[$i] = $row;

}
for($i = 0; $i < count($endpoint); $i++){
    //Kreiradius bestimmen mithilfe von Maximalgröße
    $max = get_max_length($endpoint);
    $normalise = 360 / (count($endpoint) * 1) / $max;
    if (sqrt($normalise * $max) > sqrt($max_circle)){
        $normalise = $max_circle / $max;
    }
    $endpoint[$i]["size"] = $normalise * $endpoint[$i]["length"]/sqrt($endpoint[$i]['length']/$max);

    //Farbe einstellen
    $sql = "SELECT
       INET6_NTOA(ip) as ip
FROM dns
WHERE client = INET6_ATON('$click_ip')";
    $result_color = $conn->query($sql);
    $endpoint[$i]["color"] = get_color($endpoint[$i]["inetsrc"], $click_ip, $result_color);

}

//Wandeln von result in 2D Array Connection
for($i = 0; $row = $result->fetch_assoc(); $i++) {
    $row["x1"] = (int) get_position($endpoint, $row["inetsrc"], "x");
    $row["y1"] = (int) get_position($endpoint, $row["inetsrc"], "y");
    $row["x2"] = (int) get_position($endpoint, $row["inetdst"], "x");
    $row["y2"] = (int) get_position($endpoint, $row["inetdst"], "y");
    $connection[$i] = $row;
}
for($i = 0; $i < count($connection); $i++){

    // farbe einstellen

    $connection[$i]["color"] = get_position($endpoint, $connection[$i]["inetsrc"], "color");


    //Normalvektor berechnen
    $normdist = sqrt(pow($connection[$i]["x1"]-$connection[$i]["x2"],2)+pow($connection[$i]["y1"]-$connection[$i]["y2"],2));
    //Normalvektor -> um -90 Grad drehen
    $connection[$i]["normx"] = (($connection[$i]["y1"]-$connection[$i]["y2"])/$normdist)*1;
    $connection[$i]["normy"] = (($connection[$i]["x1"]-$connection[$i]["x2"])/$normdist)*-1;
}



?>

<!--// ÜBERSICHTSFENSTER-->
<nav>
    <p style="font-size: 21px;margin-top: 17px;margin-bottom: 13px;">Cyber Sec Network Stream View</p>
    <h1>
        <?php
        echo $click_ip;


        $sql = "SELECT
                    INET6_NTOA(ip) as ip
                FROM ip_filter
                WHERE SUBSTRING_INDEX( INET6_NTOA(ip),'.',3) = '$click_ip'";
        $result = $conn->query($sql);
        if (mysqli_num_rows($result) <= 3) {
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                echo "<p style='font-size: medium;'>";
                $link = "info.php?ip=".$row['ip'];
                echo "<span><a href='$link'> ". $row['ip']. "</a></span>";
                echo "</p>";
            }
        }else{
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                echo "<p style='font-size: medium; text-align: left;'>";
                $link = "info.php?ip=".$row['ip'];
                echo "<span><a href='$link'> ". $row['ip']. "</a></span>";
                if ($row = $result->fetch_array(MYSQLI_ASSOC)){
                    $link = "info.php?ip=".$row['ip'];
                    echo "<span style='float: right'><a href='$link'> ". $row['ip']. "</a></span>";
                }
                echo "</p>";

            }
        }

        ?>
    </h1>

    <form action="insert_page.php" method="get">
        <input type="hidden" id="ip" value="<?php echo $click_ip ?>" name="ip">
        <input type="hidden" id="status" value="0" name="status">
        <input type="hidden" id="url" value="main.php" name="url">
        <input type="submit" class="button übersicht" value="ausblenden">
    </form>
        <?php
        $link = "info.php?ip=".$click_ip;
        echo "<form action='$link' method='post'>";
        ?>
        <input type="submit" class="button übersicht" value="Info">
    </form>
    <form action="summary.php">
        <input type="submit" class="button übersicht" value="Gesamtübersicht">
    </form>
    <form action="reset.php">
        <input type="hidden" id="url" value="main.php" name="url">
        <input type="submit" class="button übersicht" value="reset">
    </form>

    <p>
    </p>
    <div class="slidecontainer">
        <?php
        $sql = "SELECT 
                CAST(MIN(time) AS time(0)) as min,
                CAST(MAX(time) AS time(0)) as max,
                CAST(MAX(time) AS time(0))-CAST(MIN(time) AS time(0)) as diff
                FROM connections";
        $result = $conn->query($sql);
        $limits = $result->fetch_assoc();
        $link = "main.php?ip=".$click_ip;
        $new_diff_sec = $limits["diff"]%100;
        $new_diff_min = floor($limits["diff"]/100);
        $new_diff_h = floor($limits["diff"]/10000);
        $new_diff = $limits["diff"]%100 + floor($limits["diff"]/100) * 60 + floor($limits["diff"]/10000) * 3600;
        $new_min_slider_tmp = explode(":", $limits["min"]);
        $new_min_slider = ($new_min_slider_tmp[0]*3600)+$new_min_slider_tmp[1]*60+$new_min_slider_tmp[2];
        ?>
        <form action='<?php echo $link; ?>' method='post' id='form_slider'>
            <div id="time-range">
                <div class="sliders_step1">
                    <div id="slider-range"</div>
            </div>
            <p style="text-align:left; margin-top: 5px;">
            <span class="slider-time" id="slider-time"></span>
            <span class="slider-time2" id="slider-time2"  style="float:right"></span>

            </p>
            <script>
                var SLIDER_LOWER = cookieValues["min"];
                var SLIDER_UPPER = cookieValues["max"];
                var SLIDER_LOWER_SEC = cookieValues["min_sec"];
                if (SLIDER_UPPER =="live"){
                    var SLIDER_UPPER_SEC = <?php echo $new_diff+$new_min_slider;?>;
                }else {
                    var SLIDER_UPPER_SEC = cookieValues["max_sec"];
                }
                console.log(SLIDER_UPPER_SEC);

                $( "#slider-range" ).slider({
                    range: true,
                    min: <?php echo $new_min_slider;?>,
                    max: <?php echo $new_diff+$new_min_slider;?>,
                    values: [ SLIDER_LOWER_SEC, SLIDER_UPPER_SEC ], //or whatever default time you want
                    slide: function(e, ui) {
                        console.log(SLIDER_UPPER_SEC);
                        var hours1 = Math.floor(ui.values[0] / 3600);
                        var minutes1 = Math.floor((ui.values[0] / 60 - (hours1 * 60)));
                        var seconds1 = ui.values[0] - (minutes1 * 60) - hours1 * 3600;

                        if(minutes1 < 10) minutes1 = '0' + minutes1;
                        if(seconds1 < 10) seconds1 = '0' + seconds1;

                        $('.slider-time').html(hours1+':'+minutes1+':'+seconds1);
                        document.cookie = "min="+hours1+':'+minutes1+':'+seconds1;
                        document.cookie = "min_sec="+ui.values[0];
                        
                        var hours2 = Math.floor(ui.values[1] / 3600);
                        var minutes2 = Math.floor((ui.values[1] / 60 - (hours2 * 60)));
                        var seconds2 = ui.values[1] - (minutes2 * 60) - hours2 * 3600;

                        if(minutes2 < 10) minutes2 = '0' + minutes2;
                        if(seconds2 < 10) seconds2 = '0' + seconds2;
                        if(<?php echo $new_diff+$new_min_slider;?> == ui.values[1]){
                            $('.slider-time2').html('live');
                            document.cookie = "max=live";
                            document.cookie = "max_sec="+ui.values[1];
                        }else{
                            $('.slider-time2').html(hours2+':'+minutes2+':'+seconds2);
                            document.cookie = "max="+hours2+':'+minutes2+':'+seconds2;
                            document.cookie = "max_sec="+ui.values[1];
                        }

                        CHANGE = true;
                    }
                });

                document.getElementById("slider-time").innerHTML = SLIDER_LOWER;
                document.getElementById("slider-time2").innerHTML = SLIDER_UPPER;
            </script>
    </div>
    </form>
    </div>
    <form action='<?php echo $link; ?>' method='post' id='form_toggle'>
    <div class="toggle-buttons">
        <p style="margin-bottom: 5px">Netzmaske:</p>
        <input type="radio" id="b1" class="toggle toggle-left" name="group-b" value="2" onchange="Change()" <?php if ($netzmaske == 2) echo "checked"; ?> />
        <label for="b1" class="btn">/16</label>
        <input type="radio" id="b2" class="toggle toggle-middle" name="group-b" value="3" onchange="Change()" <?php if ($netzmaske == 3) echo "checked"; ?> />
        <label for="b2" class="btn">/24</label>
        <input type="radio" id="b3" class="toggle toggle-right" name="group-b" value="4" onchange="Change()" <?php if ($netzmaske == 4) echo "checked"; ?> />
        <label for="b3" class="btn">/32</label>
    </div>
    </form>
    <p style="font-size: 12px;color: #64BEFA;bottom: 5px;position: absolute;">
        This software was created at the Otto-von-Guericke University, Magdeburg, Germany and has been supported in part by the European Commission  in the Context of the Programme "Cyber-Sec-Verbund, CyberSec LSA" with the grant number ZS/2018/12/96222.<br>
        CyberSecNetworkStreamView is research software only. It is distributed as is and WITHOUT ANY WARRANTY. It is licensed under the terms and conditions of the GNU GENERAL PUBLIC LICENSE (GPL) Version 3. No author or distributor accepts responsibility to anyone for the consequences of using it or for whether it serves any particular purpose or works at all.
    </p>
</nav>
<div id="legende">
    <img src="signet.png" style="position: absolute;width: 400px;bottom: -110px;right: inherit">    <span class="dot selection"></span>
    Ausgewählte IP <br>
    <span class="dot dns"></span>
    IPs mit DNS Anfrage <br>
    <span class="dot"></span>
    IPs ohne DNS Anfrage <br>

</div>

<!--// GRAPH-->
<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="-250 -250 500 500" style="left: 200px;">

    <?php
    $i = 0;
    for ($i = 0; $i < count($connection); $i++){

        $tmpx1 = get_position($endpoint, $connection[$i]["inetsrc"], "x");
        $tmpy1 = get_position($endpoint, $connection[$i]["inetsrc"], "y");
        $tmpx2 = get_position($endpoint, $connection[$i]["inetdst"], "x");
        $tmpy2 = get_position($endpoint, $connection[$i]["inetdst"], "y");
        $width = $connection[$i]["length"]*$normalise;
        echo "<polygon
        points='", $tmpx1,",", $tmpy1," 
                ", $tmpx1 + ($connection[$i]["normx"] * $width),",", $tmpy1 + ($connection[$i]["normy"] * $width)," 
                ", $tmpx2 + ($connection[$i]["normx"] * $width),",", $tmpy2 + ($connection[$i]["normy"] * $width),"
                ", $tmpx2,",", $tmpy2,"',
                fill = '",$connection[$i]["color"],"'
                
        />";
        echo "<line 
        x1='", $connection[$i]["x1"],"' 
        y1='", $connection[$i]["y1"],"' 
        x2='", $connection[$i]["x2"],"' 
        y2='", $connection[$i]["y2"],"' 
        stroke=\"black\" 
        fill=\"url(#solids)\" 
        stroke-width='0.5'
        />";    }

    for ($i = 0; $i < count($endpoint); $i++){
        $link = "main.php?ip=".$endpoint[$i]["inetsrc"];
        echo "<a xlink:href=$link>";
        echo "<circle id ='",$endpoint[$i]["inetsrc"],"' cx='", $endpoint[$i]['x'], "' cy='", $endpoint[$i]['y'], "' fill='", $endpoint[$i]['color'], "' r='", $endpoint[$i]['size']+5, "'onclick='myFunction(this)'></circle>";

        $tmp_ip = $endpoint[$i]["inetsrc"];

        $sql = "SELECT
                country_code
                FROM ip2location_db1
                WHERE INET_ATON('$tmp_ip') BETWEEN ip_from AND ip_to;
        ";
        $result = $conn->query($sql);

        $country_code = $result->fetch_assoc()["country_code"];
        $cc_link = 'country-flags/svg/'.strtolower($country_code).'.svg';

        echo "<text 
        font-size=\"10px\"            
        y=".$endpoint[$i]['y']." 
        fill='aliceblue'";
        if ($endpoint[$i]['x'] < 0){
            echo "text-anchor=\"end\"
            x=".($endpoint[$i]['x']-$endpoint[$i]['size']-5) ."> ";
            echo $endpoint[$i]["inetsrc"];
            echo "</text>";
            echo $netzmaske;
            if ($netzmaske==4){
                echo "<image x=".($endpoint[$i]['x']-$endpoint[$i]['size']-23) ." y=".($endpoint[$i]['y']+2)." height=\"10\" xlink:href=".$cc_link.">";
            }

        } else {
            echo "text-anchor=\"start\"
            x=" . ($endpoint[$i]['x'] + $endpoint[$i]['size'] + 5) . "> ";
            echo $endpoint[$i]["inetsrc"];
            echo "</text>";
            if ($netzmaske == 4) {
                echo "<image x=" . ($endpoint[$i]['x'] + $endpoint[$i]['size'] + 6) . " y=" . ($endpoint[$i]['y'] + 2) . " height=\"10\" xlink:href=" . $cc_link . ">";
            }
        }

        echo "</a>";


    }
    $conn->close();
    ?>
</svg>

</body>
</html>
