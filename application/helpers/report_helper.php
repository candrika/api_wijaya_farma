<?php

function recursive($data, $ident,$total) {
    echoChild($data, $ident + 20,$total);
}

function echoChild($data, $ident,$total) {
    $borderstyle = "border-bottom: #E6E8E6;border-bottom-width: thin; border-bottom-style: dotted; ";
    $identpx = $ident . "px";
    foreach ($data as $key => $value) {
        echo "<tr><td><div style='margin-left: $identpx;font-size: 12px; '>" . $value['accnumber']." ".$value['accname'] . '</td>';
        echo "<td><div class='balance'>" . number_format($value['balance']) . "</div></td>";
        echo "</tr>";
        $total+=$value['balance'];
        if ($value['child'] != 'false') {
            $total+=recursive($value['child'], $identpx + 20,$total);
        }
    }
    return $total;
}

function recursiveAkun($data, $ident) {
    echoChildAkun($data, $ident + 20);
}

function echoChildAkun($data, $ident) {
    $borderstyle = "border-bottom: #E6E8E6;border-bottom-width: thin; border-bottom-style: dotted; ";

    $identpx = $ident . "px";
    foreach ($data as $key => $value) {
        echo "<tr><td><div style='margin-left: $identpx;font-size: 12px; '>" . $value['accnumber'].' '.$value['accname'] . '</td>';
        echo "<td><div class='balance'>" . $value['acctypename'] . "</div></td>";
        echo "</tr>";
        if ($value['child'] != 'false') {
            recursiveAkun($value['child'], $identpx + 20);
        }
    }
}
?>