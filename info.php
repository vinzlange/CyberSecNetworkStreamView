
<html lang="de">
<link href="style.css" type="text/css" rel="stylesheet" />
<link href="https://fonts.googleapis.com/css?family=Roboto&display=swap" rel="stylesheet">
<link href="info.css" type="text/css" rel="stylesheet" />
<script src="Chart.min.js"></script>
<head>
    <meta charset="UTF-8">
    <!--    <meta http-equiv="refresh" content="5">-->
    <title>Main</title>
    <?php
    $ips = array();
    include ("functions.php");
    $click_ip = '192.168.1.1';
    if (isset($_GET['ip'])) {
        $click_ip = $_GET['ip'];
    }
    $ips[0]['ip'] = $click_ip;
    $conn = new mysqli($servername, $username, $password, $dbname);
    $count = substr_count($click_ip, '.')+1;
    $sql = "SELECT
                    INET6_NTOA(ip) as ip
                FROM ip_filter
                WHERE SUBSTRING_INDEX( INET6_NTOA(ip),'.',$count) = '$click_ip'";
    $result = $conn->query($sql);
    for($i = 1; $row = $result->fetch_assoc(); $i++) {
        $ips[$i] = $row;
    }
    ?>

    <script>
        function f(div) {
            if (div == 'local'){
                localClick();
            }
            else{
                if (document.getElementById(div).style.height == '80%'){
                    document.getElementById(div).style.height = '45%';
                    document.getElementById(div).style.width = 'auto';
                    document.getElementById(div).style.zIndex = '0';
                    document.getElementsByClassName('div_column')[0].style.width = '33.3%';
                    document.getElementsByClassName('div_column')[1].style.width = '33.3%';
                    document.getElementsByClassName('div_column')[2].style.width = '33.3%';
                    document.getElementsByClassName('div_column')[0].style.height = '100%';
                    document.getElementsByClassName('div_column')[1].style.height = '100%';
                    document.getElementsByClassName('div_column')[2].style.height = '100%';
                    swap_position();

                }else{
                    document.getElementById(div).style.height = '80%';
                    document.getElementById(div).style.width = 'calc(90% - 2em)';
                    document.getElementById(div).style.zIndex = '1';
                    document.getElementsByClassName('div_column')[0].style.width = '0px';
                    document.getElementsByClassName('div_column')[1].style.width = '0px';
                    document.getElementsByClassName('div_column')[2].style.width = '0px';
                    document.getElementsByClassName('div_column')[0].style.height = '0px';
                    document.getElementsByClassName('div_column')[1].style.height = '0px';
                    document.getElementsByClassName('div_column')[2].style.height = '0px';
                    swap_position();
                }
            }
        }
        function swap_position() {
            for (var i = 0; i < document.getElementsByClassName('stats_box').length; i++){
                if (document.getElementsByClassName('stats_box')[i].style.position == 'absolute'){
                    document.getElementsByClassName('stats_box')[i].style.position = 'relative';
                }else {
                    document.getElementsByClassName('stats_box')[i].style.position = 'absolute';
                }
            }
        }
    </script>


</head>
<body>
<form action="main.php" >
    <input type="submit" class="button back" value="zurück">
</form>
<img src="signet.png" style="right: 0;float: right; height: position: ;position: absolute;top: 0;height: 130px;">


<div style="width: 90%; height: 80%; margin: 0 auto;">
    <div class="div_column">
        <div id="div1" class ="stats_box" onclick="f('div1')">
            <?php require('module/chart1.inc.php') ?>
        </div>
        <div id="div2" class ="stats_box" onclick="f('div2')">
            <?php require('module/pie_chart_proto.inc.php') ?>
        </div>
    </div>
    <div class="div_column">
        <div id="div3" class ="stats_box" onclick="f('div3')">
            <?php require('module/ip.inc.php') ?>
        </div>
        <div id="div4" class ="stats_box" onclick="f('div4')">
            <?php require('module/pie_chart_port2.inc.php') ?>
        </div>
    </div>
    <div class="div_column">
        <div id="div5" class ="stats_box" onclick="f('div5')">
            <?php require('module/sankey.inc.php') ?>
        </div>
        <div id="div6" class ="stats_box" onclick="f('div6')">
            <?php require('module/pie_chart_port.inc.php') ?>
        </div>
    </div>
</div>
<?php $conn->close();?>
<p style="font-size: 12px;color: #64BEFA;bottom: 5px;position: fixed;display: contents">
    This software was created at the Otto-von-Guericke University, Magdeburg, Germany and has been supported in part by the European Commission  in the Context of the Programme "Cyber-Sec-Verbund, CyberSec LSA" with the grant number ZS/2018/12/96222.<br>
    CyberSecNetworkStreamView is research software only. It is distributed as is and WITHOUT ANY WARRANTY. It is licensed under the terms and conditions of the GNU GENERAL PUBLIC LICENSE (GPL) Version 3. No author or distributor accepts responsibility to anyone for the consequences of using it or for whether it serves any particular purpose or works at all.
</p>
</body>
</html>
