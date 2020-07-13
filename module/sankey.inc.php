<?php
$graph_label = "";
$graph_data = "";
$graph_color = "";
$colorlist = array("255, 99, 132","135,206,235","54, 162, 235","153, 102, 255","255, 159, 64","255, 206, 86");

function random_color_part() {
    return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT);
}

function random_color() {
    return random_color_part() . random_color_part() . random_color_part();
}

for ($i = 1; $i < sizeof($ips); $i++){
    if ($i > 1){
        $selection1 = $selection1 . " OR src = INET6_ATON('". $ips[$i]['ip'] ."') ";
        $selection2 = $selection2 . " OR dst = INET6_ATON('". $ips[$i]['ip'] ."') ";
    }else{
        $selection1 = " src = INET6_ATON('". $ips[$i]['ip'] ."') ";
        $selection2 = " dst = INET6_ATON('". $ips[$i]['ip'] ."') ";
    }
}

$sql = "SELECT
    INET6_NTOA(src) as src,
    SUM(length) as traffic,
    INET6_NTOA(dst) as dst,
    1 as status
FROM connections
WHERE ". $selection1 ."
GROUP BY src, dst
UNION (

SELECT
    INET6_NTOA(src),
    SUM(length),
    INET6_NTOA(dst),
    0 as status
FROM connections
WHERE ". $selection2 ."
GROUP BY src, dst);
";


$result = $conn->query($sql);

$nodes_first = array();
$nodes_second = array();
$nodes_third = array();
$links = array();
$maximum = 0;
for($i = 0; $row = $result->fetch_assoc(); $i++) {
    $links[$i] = $row;
    $maximum = $maximum + $row['traffic'];
    if (!$row['status']){
        //Array für Knoten in erster Spalte mit deren Größe
        if (!isset($nodes_first[inet_pton($row['src'])])){
            $nodes_first[inet_pton($row['src'])] = $row['traffic'];
        }else{
            $nodes_first[inet_pton($row['src'])] = $row['traffic'] + $nodes_first[inet_pton($row['src'])];
        }
        //Array für Knoten in zweiter Spalte. Größe abhängig von eingehenen Kanten
        if (!isset($nodes_second[inet_pton($row['dst'])])){
            $nodes_second[inet_pton($row['dst'])] = $row['traffic'];
        }else{
            $nodes_second[inet_pton($row['dst'])] = $row['traffic'] + $nodes_second[inet_pton($row['dst'])];
        }
    }else{
        //Array für Knoten in zweiter Spalte. Größe von ausgehenden Kanten addiert
        if (!isset($nodes_second[inet_pton($row['src'])])){
            $nodes_second[inet_pton($row['src'])] = $row['traffic'];
        }else{
            $nodes_second[inet_pton($row['src'])] = $row['traffic'] + $nodes_second[inet_pton($row['src'])];
        }
        //Array für Knoten in dritten Spalte mit deren Größe
        if (!isset($nodes_third[inet_pton($row['dst'])])){
            $nodes_third[inet_pton($row['dst'])] = $row['traffic'];
        }else{
            $nodes_third[inet_pton($row['dst'])] = $row['traffic'] + $nodes_third[inet_pton($row['dst'])];
        }
    }
}
ksort($nodes_first);
ksort($nodes_second);
ksort($nodes_third);
echo "Sankey";

echo "
<svg
    xmlns=\"http://www.w3.org/2000/svg\"
    viewBox='0 0 110 115' preserveAspectRatio='none' id='svg'>
    <style>
        #tooltip {
            dominant-baseline: hanging;
        }
    </style>
    ";
    // jede Spalte mit id: <Spalte>_<ip> mit höhe der größe normalisiert auf 100/maximum, Gesamtabstand von allen Knoten 3
    // 1. Spalte
    $tmp_top_distance = 10;
    for ($i = 0; $tmp_array = current($nodes_first)*(100/$maximum); $i++){
        echo "<rect id =1_".inet_ntop(key($nodes_first))." x='5' y='". ($tmp_top_distance)  ."' width=\"10\" height=\"". $tmp_array  * 1 ."\" fill='#".random_color()."' class=\"tooltip-trigger\" used='0' data-tooltip-text=".inet_ntop(key($nodes_first))."></rect>";
        $tmp_top_distance = $tmp_top_distance + $tmp_array + 3/sizeof($nodes_second);
        next($nodes_first);
    }
    // 2. Spalte
    $tmp_top_distance = 10;
    for ($i = 0; $tmp_array = current($nodes_second)*(100/$maximum); $i++){
        echo "<rect id =2_".inet_ntop(key($nodes_second))." x='50' y='". ($tmp_top_distance)  ."' width=\"10\" height=\"". $tmp_array  * 1 ."\" fill='#".random_color()."' class=\"tooltip-trigger\" used='0' data-tooltip-text=".inet_ntop(key($nodes_second))."></rect>";
        $tmp_top_distance = $tmp_top_distance + $tmp_array + 3/sizeof($nodes_second);
        next($nodes_second);
    }
    // 3. Spalte
    $tmp_top_distance = 10;
    for ($i = 0; $tmp_array = current($nodes_third)*(100/$maximum); $i++){
        echo "<rect id =3_".inet_ntop(key($nodes_third))." x='95' y='". ($tmp_top_distance)  ."' width=\"10\" height=\"". $tmp_array  * 1 ."\" fill='#".random_color()."' class=\"tooltip-trigger\" used='0' data-tooltip-text=".inet_ntop(key($nodes_third))."></rect>";
        $tmp_top_distance = $tmp_top_distance + $tmp_array + 3/sizeof($nodes_second);
        next($nodes_third);
    }
    echo "    
    <g id=\"tooltip\" visibility=\"hidden\">
        <rect width=\"60\" height=\"5%\" fill=\"black\" rx=\"1%\" ry=\"1%\" opacity='75%'/>
        <text x=\"1\" y=\"1\" font-size='3' fill='aliceblue'>Tooltip</text>
    </g>
    <script type=\"text/ecmascript\"><![CDATA[
        (function() {
            var svg = document.getElementById('svg');
            var tooltip = svg.getElementById('tooltip');
            var tooltipText = tooltip.getElementsByTagName('text')[0];
            var tooltipRects = tooltip.getElementsByTagName('rect');
            var triggers = svg.getElementsByClassName('tooltip-trigger');
            for (var i = 0; i < triggers.length; i++) {
                triggers[i].addEventListener('mousemove', showTooltip);
                triggers[i].addEventListener('mouseout', hideTooltip);
            }
            function showTooltip(evt) {
                var CTM = svg.getScreenCTM();
                var length = tooltipText.getComputedTextLength();
                var x = (evt.clientX - CTM.e + 10) / CTM.a;
                var y = (evt.clientY - CTM.f - 7) / CTM.d;
                x = (( x >= 70) ? x - length - 5 : x);
                tooltip.setAttributeNS(null, \"transform\", \"translate(\" + x + \" \" + y + \")\");
                tooltip.setAttributeNS(null, \"visibility\", \"visible\");
                tooltipText.firstChild.data = evt.target.getAttributeNS(null, \"data-tooltip-text\");
                //console.log(tooltipText.firstChild.data + ' ' + length);
                
                
                for (var i = 0; i < tooltipRects.length; i++) {
                    tooltipRects[i].setAttributeNS(null, 'width', length + 3);
                }
            }
            function hideTooltip(evt) {
                tooltip.setAttributeNS(null, \"visibility\", \"hidden\");
            }
            function addConnection(start_x ,start_y, ziel_x, ziel_y, color, width) {
                var newElement = document.createElementNS('http://www.w3.org/2000/svg', 'path'); //Create a path in SVG's namespace
                newElement.setAttribute('d','M ' + start_x + ' ' + start_y + ' C ' + parseFloat(start_x+15) + ' ' + start_y + ', ' + parseFloat(ziel_x-15) + ' ' + ziel_y + ', ' + ziel_x + ' ' + ziel_y); //Set path's data
                newElement.style.stroke = color; //Set stroke colour
                newElement.style.strokeWidth = width; //Set stroke width
                newElement.style.fill='transparent';
                newElement.style.zIndex='0';
                newElement.style.opacity='0.4';
                //console.log(newElement);
                return newElement;               
            }
            
";
// Übergabe der links in Array mit [src,traffic,dst,status] (status 0 für Spalte 1-2, 1 für Spalte 2-3)
for ($i = 0; $tmp_array = current($links); $i++) {



        if ($tmp_array['status']) {
            echo "
        var src =" . json_encode($tmp_array['src']) . ";
        var dst =" . json_encode($tmp_array['dst']) . ";
        var traffic =" . json_encode($tmp_array['traffic']* (100 / $maximum)) . ";
        var left = svg.getElementById('2_'+src);
        var right = svg.getElementById('3_'+dst);
        svg.appendChild(addConnection(
        parseFloat(left.getAttribute('x'))+10,
        parseFloat(left.getAttribute('y'))+parseFloat(left.getAttribute('used'))+traffic/2,
        parseFloat(right.getAttribute('x')),
        parseFloat(right.getAttribute('y'))+parseFloat(right.getAttribute('used'))+traffic/2,
        left.getAttribute('fill'),
        traffic));
        left.setAttribute('used',parseFloat(left.getAttribute('used'))+parseFloat(traffic));
        right.setAttribute('used',parseFloat(right.getAttribute('used'))+parseFloat(traffic));       
        
        
        
        
        ";
        } else {
            echo "
        var src =" . json_encode($tmp_array['src']) . ";
        var dst =" . json_encode($tmp_array['dst']) . ";
        var traffic =" . json_encode($tmp_array['traffic'] * (100 / $maximum)) . ";
        
        var left = svg.getElementById('1_'+src);
        var right = svg.getElementById('2_'+dst);
        
        console.log(right);
        console.log(traffic);

        svg.appendChild(addConnection(
        parseFloat(left.getAttribute('x'))+10,
        parseFloat(left.getAttribute('y'))+parseFloat(left.getAttribute('used'))+traffic/2,
        parseFloat(right.getAttribute('x')),
        parseFloat(right.getAttribute('y'))+parseFloat(right.getAttribute('used'))+traffic/2,
        right.getAttribute('fill'),
        traffic));
        left.setAttribute('used',parseFloat(left.getAttribute('used'))+parseFloat(traffic));
        right.setAttribute('used',parseFloat(right.getAttribute('used'))+parseFloat(traffic));
        
        ";


    }
    next($links);

}
  echo " 
        })()
        ]]></script>
        <use xlink:href=\"#tooltip\"/>
</svg>
 ";
?>