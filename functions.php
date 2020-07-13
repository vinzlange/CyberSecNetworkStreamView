<?php
$servername = "localhost";
$username = "pi";
$password = "";
$dbname = "test";

function get_max_length($a)
{
    $length = 0;
    for ($i = 0; $i < count($a); $i++){
        if ( $a[$i]["length"] > $length){
            $length = $a[$i]["length"];
        }
    }
    return $length;
}
function get_position($endpoint, $ip, $coordinate){
    for ($i = 0; $i < count($endpoint); $i++){
        if ( $endpoint[$i]["inetsrc"] == $ip){
            return $endpoint[$i][$coordinate];
        }
    }
    return NULL;
}

function format_size($size){
    if ($size < pow(10,3)){
        return "$size bit";
    }
    if ($size < pow(10,6)){
        return round($size/pow(10,3),3)  . " kbit";
    }
    if ($size < pow(10,9)){
        return round($size/pow(10,6),3)  . " Mbit";
    }
    if ($size < pow(10,12)){
        return round($size/pow(10,9),3)  . " Gbit";
    }
    if ($size < pow(10,15)){
        return round($size/pow(10,12),3)  . " Tbit";
    }
    return $size;
}
function get_color($ip, $click_ip, $sqlreq){
    while ($row = $sqlreq->fetch_assoc()){

        if ($row["ip"] == $ip) {
            return "yellow";
        }
    }
    if ($ip == $click_ip){
        return "skyblue";
    }else{
        return "#D6641E";
    }

}

?>