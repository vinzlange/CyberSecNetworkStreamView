<?php
echo "<br>";
echo "<h1 style='padding-top: 1em'>";
echo $ips[0]['ip'];
echo "</h1>";


if (sizeof($ips) <= 4) {
    for ($i = 1; $i < sizeof($ips); $i++){
        echo "<p style='font-size: medium;'>";
        $link = "info.php?ip=".$ips[$i]['ip'];
        echo "<span><a href='$link'> ". $ips[$i]['ip']. "</a></span>";
        echo "</p>";
    }
}else{
    for ($i = 1; $i < sizeof($ips); $i++){
        echo "<p style='font-size: medium; text-align: left;'>";
        $link = "info.php?ip=".$ips[$i]['ip'];
        echo "<span><a href='$link'> ". $ips[$i]['ip']. "</a></span>";
        $i++;
        if (isset($ips[$i])){
            $link = "info.php?ip=".$ips[$i]['ip'];
            echo "<span style='float: right'><a href='$link'> ". $ips[$i]['ip']. "</a></span>";
        }
        echo "</p>";

    }
}
echo "
<script> 
    function localClick(){
       alert('hello world')
    }



 </script>
"

?>

