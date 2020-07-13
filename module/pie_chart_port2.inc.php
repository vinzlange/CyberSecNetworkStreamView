<?php
$graph_label = "";
$graph_data = "";
$graph_color = "";
for ($i = 1; $i < sizeof($ips); $i++){
    if ($i > 1){
        $selection = $selection . " OR dst = INET6_ATON('". $ips[$i]['ip'] ."') ";
    }else{
        $selection = " dst = INET6_ATON('". $ips[$i]['ip'] ."') ";
    }
}
$sql = "SELECT
            SUM(length),
            src_port
        FROM connections
        WHERE ". $selection ."
        GROUP BY src_port;
";

$result = $conn->query($sql);
$colorlist = array("255, 99, 132","135,206,235","54, 162, 235","153, 102, 255","255, 159, 64","255, 206, 86");
$i = 0;
while ($row = $result->fetch_array()){
    if (!isset($colorlist[$i])){
        $colorlist[$i]=rand(0,255).", ".rand(0,255).", ".rand(0,255);
    }
    $graph_label = $graph_label . "'" . $row['src_port'] . "', ";
    $graph_data = $graph_data . "'" . $row['SUM(length)'] . "', ";
    $graph_color = $graph_color . "'rgba(". $colorlist[$i] .",1)', ";
    $i++;

}


echo " <canvas id=\"pie_Chart_port2\"></canvas>

            <script>
                Chart.pluginService.register({
                    beforeDraw: function (chart, easing) {
                        if (chart.config.options.chartArea && chart.config.options.chartArea.backgroundColor) {
                            var helpers = Chart.helpers;
                            var ctx = chart.chart.ctx;
                            var chartArea = chart.chartArea;

                            ctx.save();
                            ctx.fillStyle = chart.config.options.chartArea.backgroundColor;
                            ctx.fillRect(chartArea.left, chartArea.top, chartArea.right - chartArea.left, chartArea.bottom - chartArea.top);
                            ctx.restore();
                        }
                    }
                });
                var ctx = document.getElementById('pie_Chart_port2').getContext('2d');
                var pie_Chart_port = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                      labels: [".$graph_label."],
                      datasets: [{
                        label: \"Protokolle\",
                        backgroundColor: [". $graph_color ."],
                        data: [".$graph_data."]
                      }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        legend: {
                            labels: {
                                fontColor: \"white\",
                                fontSize: 18
                            }
                        },
                        title:{
                            display: true,     
                            text: \"Empfangen Ports\",
                            fontColor: \"white\",
                            fontSize: 18                  
                       }
                    }
                });
            </script>";
?>