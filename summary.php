<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
<!--        <meta http-equiv="refresh" content="5">-->
    <title>Zusammenfassung</title>
</head>

<body>
<link href="style.css" type="text/css" rel="stylesheet" />
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">
<?php
include ("functions.php");

// For extra protection these are the columns of which the user can sort by (in your database table).
$columns = array('ip','proto','send','received','length');

// Only get the column if it exists in the above columns array, if it doesn't exist the database table will be sorted by the first item in the columns array.
$column = isset($_GET['column']) && in_array($_GET['column'], $columns) ? $_GET['column'] : $columns[0];

// Get the sort order for the column, ascending or descending, default is ascending.
$sort_order = isset($_GET['order']) && strtolower($_GET['order']) == 'desc' ? 'DESC' : 'ASC';

$connection = array();
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$sql2 = "SELECT
    INET6_NTOA(tmpip) as inetsrc,
    SUM(send) as send,
    SUM(received) as received,
    SUM(send) + SUM(received) as length,
    proto,
    ip_filter.status as status
FROM (SELECT
          src as tmpip,
          SUM(length) as send,
          '0' as received,
            proto
      FROM connections
      GROUP BY tmpip
      UNION (SELECT
                 dst as tmpip,
                 '0' as send,
                 SUM(length) as received,
                    proto
             FROM connections
             GROUP BY tmpip)) t
         LEFT JOIN ip_filter ON ip = tmpip
GROUP BY inetsrc
ORDER BY " .  $column . " " . $sort_order . ";
";
// Some variables we need for the table.
$up_or_down = str_replace(array('ASC','DESC'), array('up','down'), $sort_order);
$asc_or_desc = $sort_order == 'ASC' ? 'desc' : 'asc';
$add_class = ' class="highlight"';
$result2 = $conn->query($sql2);

//echo $result2->fetch_assoc()["length"];
$i = 0;
while($row = $result2->fetch_assoc()) {
    $row["x"] = (float) sin(deg2rad($i*360/$result2->num_rows));
    $row["y"] = (float) cos(deg2rad($i*360/$result2->num_rows));
    $connection[$i] = $row;
    $i++;
}

for($i = 0; $i < count($connection); $i++){
    $max = get_max_length($connection);
    $norm = (360 / (count($connection) * 1)) / $max;
    if ($norm * $max){
        $norm = 50 / $max;
    }
    $connection[$i]["size"] = $norm * $connection[$i]["length"];
}
?>
<form action="main.php" >
    <input type="submit" class="button back" value="zurück">
</form>
<img src="signet.png" style="right: 0;float: right; height: position: ;position: absolute;top: 0;height: 130px;">
<table>
    <thead>
    <tr>
        <th></th>

        <th><a href="summary.php?column=ip&order=<?php echo $asc_or_desc; ?>">ip<i class="fas fa-sort<?php echo $column == 'ip' ? '-' . $up_or_down : ''; ?>"></i></a></th>
        <th>url</th>
        <th><a href="summary.php?column=proto&order=<?php echo $asc_or_desc; ?>">proto<i class="fas fa-sort<?php echo $column == 'proto' ? '-' . $up_or_down : ''; ?>"></i></a></th>
        <th><a href="summary.php?column=send&order=<?php echo $asc_or_desc; ?>">send<i class="fas fa-sort<?php echo $column == 'send' ? '-' . $up_or_down : ''; ?>"></i></a></th>
        <th></th>
        <th><a href="summary.php?column=received&order=<?php echo $asc_or_desc; ?>">received<i class="fas fa-sort<?php echo $column == 'received' ? '-' . $up_or_down : ''; ?>"></i></a></th>
        <th><a href="summary.php?column=send&order=<?php echo $asc_or_desc; ?>">traffic<i class="fas fa-sort<?php echo $column == 'send' ? '-' . $up_or_down : ''; ?>"></i></a></th>
        <th>status</th>

    </tr>
    </thead>
    <tbody>
    <!--Use a while loop to make a table row for every DB row-->
    <?php $i = 0; while ( $i < count($connection)) : ?>
        <tr>
            <td width="8em"><?php
                $link = "info.php?ip=".$connection[$i]["inetsrc"];
                echo "<form action='$link' method='post'>";
                echo "<input type=\"submit\" class=\"button info\" value=\"info\">";
                echo "</form>"
                ?></td>
            <td><?php
                $tmp_ip = $connection[$i]["inetsrc"];
                $sql = "SELECT
                        country_code
                        FROM ip2location_db1
                        WHERE INET_ATON(\"$tmp_ip\") BETWEEN ip_from AND ip_to;
                ";
                $result = $conn->query($sql);

                $country_code = $result->fetch_assoc()["country_code"];


                echo $tmp_ip;
                ?>
            <img src="country-flags/<?php echo strtolower($country_code)?>.png" class="icon" alt=" "/>
            </td>
            <td><?php
                $sql= "SELECT
            url
            FROM dns
            WHERE ip = INET6_ATON(\"$tmp_ip\")";
            $result = $conn->query($sql);
            while ($row = $result->fetch_assoc()){
                echo $row["url"];
            }


            ?>
            </td>
            <td><?php echo $connection[$i]["proto"]; ?></td>
            <td><?php echo format_size($connection[$i]["send"]); ?></td>
            <td width="10%" >
                <div style="float:left;width:100%;       background:red;height: 40px;text-align: right">
                    <div style="height:20px;background:green;height: 40px; width:<?php echo $connection[$i]["send"] / $connection[$i]["length"] * 100 ?>%;text-align: left"></div>
                </div>
            </td>
            <td><?php echo format_size($connection[$i]["received"]); ?></td>
            <td><?php echo format_size($connection[$i]["length"]); ?></td>

            <td width="333px"><form action="insert_page.php" method="get">
                    <input type="hidden" id="ip" value="<?php echo $connection[$i]["inetsrc"] ?>" name="ip">
                    <input type="hidden" id="url" value="summary.php" name="url">
            <?php
                switch ($connection[$i]["status"]){
                    case 0: echo "<input type=\"hidden\" id=\"status\" value=\"1\" name=\"status\">";
                            echo "<input type=\"button\" class=\"button active\" value=\"ausblenden\">";
                            echo "<input type=\"submit\" class=\"button\" value=\"anzeigen\">";
                            break;
                    case 1: echo "<input type=\"hidden\" id=\"status\" value=\"0\" name=\"status\">";
                            echo "<input type=\"submit\" class=\"button\" value=\"ausblenden\">";
                            echo "<input type=\"button\" class=\"button active\" value=\"anzeigen\">";
                            break;
                }
            ?></form></td>



        </tr>
        <?php
        $i++;
        endwhile;
        $conn->close();
        ?>

    </tbody>
</table>
<p style="font-size: 12px;color: #64BEFA;bottom: 5px;position: fixed;display: contents">
    This software was created at the Otto-von-Guericke University, Magdeburg, Germany and has been supported in part by the European Commission  in the Context of the Programme "Cyber-Sec-Verbund, CyberSec LSA" with the grant number ZS/2018/12/96222.<br>
    CyberSecNetworkStreamView is research software only. It is distributed as is and WITHOUT ANY WARRANTY. It is licensed under the terms and conditions of the GNU GENERAL PUBLIC LICENSE (GPL) Version 3. No author or distributor accepts responsibility to anyone for the consequences of using it or for whether it serves any particular purpose or works at all.
</p>

</body>
</html>
