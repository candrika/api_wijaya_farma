<?php
//helper
function cleardot($num)
{
    return str_replace(".", "", $num);
}

function clearnumberic($num) {
//    62,000.00 -> 62000.00
    $num =  str_replace(",", "", str_replace(".00", "", $num));
    return $num == "" ? null : $num;
}

function request_number($num) {
//    320.200,00 -> 320200.00
    $num =  str_replace(",", ".", str_replace(".", "", $num));
    return $num == "" ? null : $num;
}

// function cleardot2($num){
//     return str_replace(".", "", $num);
// }

function request_number2($num) {
//    83,333.33 -> 83333.33
    $num =  str_replace(",", "", $num);
    return $num == "" ? null : $num;
}

function request_date_form($date){
    if(isset($date)){
        return $date!='' ? str_replace('T00:00:00','',$date) : null;
    } else {
        return null;
    }
}

function nextMonth($date)
{
    $occDate=$date;
    $forOdNextMonth= date('Y-m-d', strtotime("+1 month", strtotime($occDate)));
    return $forOdNextMonth;
    // echo $occDate.' '.$forOdNextMonth;
    //Output:- $forOdNextMonth=02


    /*****************more example****************/
    // $occDate='2014-12-28';

    // $forOdNextMonth= date('m', strtotime("+1 month", strtotime($occDate)));
    //Output:- $forOdNextMonth=01

    //***********************wrong way**********************************//
    // $forOdNextMonth= date('m', strtotime("+1 month", $occDate));
    //Output:- $forOdNextMonth=02; //instead of $forOdNextMonth=01;
    //******************************************************************//
}

function add_months($months, DateTime $dateObject) 
{
    $next = new DateTime($dateObject->format('Y-m-d'));
    $next->modify('last day of +'.$months.' month');

    if($dateObject->format('d') > $next->format('d')) {
        return $dateObject->diff($next);
    } else {
        return new DateInterval('P'.$months.'M');
    }
}

function count_days($date1,$date2){
    $now = time(); // or your date as well
    $date1 = strtotime($date1);
    $date2 = strtotime($date2);
    $datediff = $date2 - $date1;

    return round($datediff / (60 * 60 * 24));
}

function search_str_query($query,$searchField){
        $w = null;

        if (isset($query)) {

            $field = 0;
            $start = 0;

            foreach ($searchField as $key => $value) {
                if ($field == 0) {
                    // $w .="(";
                    $w.=" AND ((" . $value . " LIKE '%" . strtoupper($query) . "%') OR (" . $value . " LIKE '%" . strtolower($query) . "%')  OR (" . $value . " LIKE '%" . ucwords(strtolower($query)) . "%')";
                } else {
                    $w.=" OR (" . $value . " LIKE '%" . strtoupper($query) . "%') OR (" . $value . " LIKE '%" . strtolower($query) . "%') OR (" . $value . " LIKE '%" . ucwords(strtolower($query)) . "%')";
                }
                $field++;
            }
            $w .=")";

        } 

        return $w;
    }

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function fetchUnit($unit,$alias=null)
{
    //buat di rekap
    $u = explode(",", $unit);
    // echo count($u);
    $wer='(';
        $i=1;
    foreach ($u as $key => $value) {
        $wer.=$alias.".idunit=".$value;
        if($i<count($u))
        {
            $wer.=" OR ";
        }
        $i++;
        
    }
    $wer.=')';
    return $wer;
}

 function periode($sd,$nd)
    {
        $sd = str_replace("%20", " ", $sd);
        $nd = str_replace("%20", " ", $nd);
        $periode = $sd==$nd ? $sd : $sd.' s/d '.$nd;
        return $periode;
    }

    
function nicetime($date)
{
    if(empty($date)) {
        return "No date provided";
    }
    
    $periods         = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
    $lengths         = array("60","60","24","7","4.35","12","10");
    
    $now             = time();
    $unix_date       = strtotime($date);
    
       // check validity of date
    if(empty($unix_date)) {    
        return "Bad date";
    }

    // is it future date or past date
    if($now > $unix_date) {    
        $difference     = $now - $unix_date;
        $tense         = "ago";
        
    } else {
        $difference     = $unix_date - $now;
        $tense         = "from now";
    }
    
    for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
        $difference /= $lengths[$j];
    }
    
    $difference = round($difference);
    
    if($difference != 1) {
        $periods[$j].= "s";
    }
    
    return "$difference $periods[$j] {$tense}";
}

function addition_time($time,$int){

    $timesheet_1_end = date('H:i', strtotime($time."+$int hour"));

}
?>