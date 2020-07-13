<?php
$labels = '';
$data1 = '';
$data2 = '';
$graph_label = array();
$graph_data = array();

$selection = "";
for ($i = 1; $i < sizeof($ips); $i++){
    $selection = $selection . " , SUM(CASE WHEN src = INET6_ATON('". $ips[$i]['ip'] ."') THEN length END) as '". $ips[$i]['ip'] ."'";

}

$sql = "SELECT
    TIME_FORMAT(time, '%H:%i:%s') as time,
    SUM(length) as Gesamttraffic
    $selection
FROM connections
GROUP BY time DIV 1;
";

$result = $conn->query($sql);

while ($row = $result->fetch_array()){
    $labels = $labels . '"'. $row[0].'",';
    for ($i = 1; $i <= sizeof($ips); $i++){
        if (isset($graph_data[$i])){
            $graph_data[$i] = $graph_data[$i] . '"'. $row[$i].'",';
        }else{
            // Initialiesieren der Arrays
            $graph_data[$i] = '"'. $row[$i].'",';
            $graph_label[$i] = $result->fetch_field_direct($i)->name;
        }
    };
}

$colorlist = array("255, 99, 132,","135,206,235","54, 162, 235","153, 102, 255","255, 159, 64","255, 206, 86");
$dataset = "";
for ($i = 1; $i <= sizeof($ips); $i++){
    if (!isset($colorlist[$i])){
        $colorlist[$i]=rand(0,255).", ".rand(0,255).", ".rand(0,255);
    }
    $dataset = $dataset."{
                            label: '" . $graph_label[$i] . "',
                            data: [" . $graph_data[$i] . "],
                            borderColor: 'rgba(". $colorlist[$i] .",1)',
                            backgroundColor: 'rgba(". $colorlist[$i] .",0.1)',
                            borderWidth: 1,
                        },";

}

$labels = trim($labels, ",");
$dataset = trim($dataset, ",");


echo " <canvas id=\"myChart\"></canvas>
 <script src=\"https://cdn.jsdelivr.net/npm/chart.js@2.9.1\"></script>
<script src=\"https://cdn.jsdelivr.net/npm/hammerjs@2.0.8\"></script>
<script src=\"https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@0.7.4\"></script>
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
                var ctx = document.getElementById('myChart').getContext('2d');
                var myChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: [" . $labels . "],
                        datasets: [".$dataset."]
                    },
                    options: {
                        maintainAspectRatio: false,
                        legend: {
                            labels: {
                                fontColor: \"white\",
                                fontSize: 18
                            }
                        },
                        scales: {
                            yAxes: [{
                                ticks: {
                                    fontColor: \"white\",
                                    fontSize: 14,
                                    beginAtZero: true,
                                    callback: function(value, index, values) {
                                        return value + ' bit/sek';
                                    }

                                }
                            }],
                            xAxes: [{
                                ticks: {
                                    fontColor: \"white\",
                                    fontSize: 14,
                                    stepSize: 1,
                                    beginAtZero: true
                                }
                            }]
                        },
                        chartArea: {
                            backgroundColor: 'rgba(60, 63, 65, 0.4)'
                        },
                        plugins: {
                            zoom: {
                                // Container for pan options
                                pan: {
                                    // Boolean to enable panning
                                    enabled: true,
        
                                    // Panning directions. Remove the appropriate direction to disable
                                    // Eg. 'y' would only allow panning in the y direction
                                    mode: 'xy'
                                },
        
                                // Container for zoom options
                                zoom: {
                                    // Boolean to enable zooming
                                    enabled: true,
        
                                    // Zooming directions. Remove the appropriate direction to disable
                                    // Eg. 'y' would only allow zooming in the y direction
                                    mode: 'x',
                                }
                            }
                        }
                    }
                });
            </script>";
?>