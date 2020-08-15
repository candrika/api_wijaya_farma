<?php

function fetchDate($i) {
//     echo $i.'<br>';
    $date = str_replace(" ", "", $i);
    // echo $date.'<br>';
    $arrMonth = array('January' => '01', 'February' => '02', 'March' => '03', 'April' => '04', 'May' => '05', 'June' => '06', 'July' => '07', 'August' => '08', 'September' => '09', 'October' => '10', 'November' => '11', 'December' => '12');
    // // explode(delimiter, string)
    $i = explode(",", $date);
    // echo "date ".$i[0]." ".$i[1]." : ";
    // echo $arrMonth[$i[0]]."<hr>";
    // echo "asdasd ".print_r($i)."<hr>";
    if(isset($arrMonth[$i[0]]))
    {
        return array('bulan' => $arrMonth[$i[0]], 'tahun' => $i[1]);
    } else {
        return false;
    }
}

function getNoMonth($d)
{
     $arrMonth = array('January' => '01', 'February' => '02', 'March' => '03', 'April' => '04', 'May' => '05', 'June' => '06', 'July' => '07', 'August' => '08', 'September' => '09', 'October' => '10', 'November' => '11', 'December' => '12');
    // // explode(delimiter, string)
    return $arrMonth[$d];
}

function getMonth($d)
{
     $arrMonth = array('1'=>'January', '2'=>'February', '3'=>'March', '4'=>'April', '5'=>'May', '6'=>'June' , '7' => 'July','8' => 'August' ,'9' => 'September', '10'=>'October' ,'11'=> 'November','12' => 'December');
    // // explode(delimiter, string)
    return $arrMonth[$d];
}

function ambilBulan($i) {
    $arrMonth = array('01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember');
    return $arrMonth[$i];
}

function ambilNoBulan($i) {
    $arrMonth = array('Januari' => '01', 'Februari' => '02', 'Maret' => '03', 'April' => '04', 'Mei' => '05', 'Juni' => '06', 'Juli' => '07', 'Agustus' => '08', 'September' => '09', 'Oktober' => '10', 'November' => '11', 'Desember' => '12');
    // echo $arrMonth[$i];
    return $arrMonth[$i];
}

function inputDate($v) {
    return $v == null ? null : str_replace("T00:00:00", "", $v);
}

function inputDate_reverse($v) {
    $s = str_replace("T00:00:00", "", $v);
    $tgl = explode("-", $s);
    return $tgl[2].'-'.$tgl[1].'-'.$tgl[0];
}

function inputDate_reverse2($v) {
    $s = str_replace("T00:00:00", "", $v);
    $tgl = explode("-", $s);
    return $tgl[0].'-'.$tgl[1].'-'.$tgl[2];
}

function backdate($d)
{
    $tgl = explode("/", $d);
//    echo  $tgl[2].'/'.$tgl[1].'/'.$tgl[0];
    return $tgl[2].'/'.$tgl[1].'/'.$tgl[0];
}

function remove_slash($d){
    $tgl = explode("/", $d);
    // echo  $tgl[2].'-'.$tgl[1].'-'.$tgl[0];
    return $tgl[2].'-'.$tgl[1].'-'.$tgl[0];
}

function backdate2($d)
{
    $tgl = explode("-", $d);
    return $tgl[2].'-'.$tgl[1].'-'.$tgl[0];
}

function backdate2_reverse($d)
{
    //30-05-2015
    $tgl = explode("-", $d);
    return $tgl[2].'-'.$tgl[1].'-'.$tgl[0];
}

function lastday($month,$year)
{
    return cal_days_in_month(CAL_GREGORIAN, $month, $year);
}

// function count_days($startdate,$enddate){
//     $startTimeStamp = strtotime($startdate);
//     $endTimeStamp = strtotime($enddate);

//     $timeDiff = abs($endTimeStamp - $startTimeStamp);

//     $numberDays = $timeDiff/86400;  // 86400 seconds in one day

//     // and you might want to convert to integer
//     $numberDays = intval($numberDays);

//     return $numberDays;
// }

 function validasitgl($no,$jenis,$date)
    {
        $tgl = explode(".", $date);
// echo 'count:'.count($tgl).'   ';
        if(count($tgl)!=3)
        {
            $status = false;   
            $message = 'Error data NO ' . $no .' : Format '.str_replace("%20", " ", $jenis).' Salah. <br><br> Format Tanggal: dd.mm.yyyy';
        } else {

            $message = 'Error data NO ' . $no .' : Format '.str_replace("%20", " ", $jenis).' Salah. <br><br> Format Tanggal: dd.mm.yyyy';
            if(isset($tgl[1]))
            {
                $bulan = intval($tgl[1]);
                 // $status = false;   
                if(count($tgl)<3)
                {
                    $status = false;            
                } else if($tgl[0]>33) {
                    $status = false;
                } else if($bulan>12) {
                    $status = false;
                }  else {
                    $status = true;
                    $message = null;
                }
            } else {
                $status = false;   
            }
        }

        // $bulan = intval($tgl[1]);

        return array('message'=>$message,'status'=>$status);
    }

function cleartanggal($tgl)
{
    return str_replace("T00:00:00", "", $tgl);
}

function diff_two_dates($dateIni,$dateFin){
   // Set dates
    // $dateIni = '2014-07-01';
    // $dateFin = '2016-07-01';

    // Get year and month of initial date (From)
    $yearIni = date("Y", strtotime($dateIni));
    $monthIni = date("m", strtotime($dateIni));

    // Get year an month of finish date (To)
    $yearFin = date("Y", strtotime($dateFin));
    $monthFin = date("m", strtotime($dateFin));

    // Checking if both dates are some year

    if ($yearIni == $yearFin) {
       $numberOfMonths = ($monthFin-$monthIni) + 1;
    } else {
       $numberOfMonths = ((($yearFin - $yearIni) * 12) - $monthIni) + 1 + $monthFin;
    }
    return $numberOfMonths;
}

function diff_two_dates2($startdate,$enddate){
            // Declare and define two dates 
        $date1 = strtotime($startdate);  
        $date2 = strtotime($enddate);  
          
        // Formulate the Difference between two dates 
        $diff = abs($date2 - $date1);  
          
          
        // To get the year divide the resultant date into 
        // total seconds in a year (365*60*60*24) 
        $years = floor($diff / (365*60*60*24));  
          
          
        // To get the month, subtract it with years and 
        // divide the resultant date into 
        // total seconds in a month (30*60*60*24) 
        $months = floor(($diff - $years * 365*60*60*24) 
                                       / (30*60*60*24));  
          
          
        // To get the day, subtract it with years and  
        // months and divide the resultant date into 
        // total seconds in a days (60*60*24) 
        $days = floor(($diff - $years * 365*60*60*24 -  
                     $months*30*60*60*24)/ (60*60*24)); 
          
          
        // To get the hour, subtract it with years,  
        // months & seconds and divide the resultant 
        // date into total seconds in a hours (60*60) 
        $hours = floor(($diff - $years * 365*60*60*24  
               - $months*30*60*60*24 - $days*60*60*24) 
                                           / (60*60));  
          
          
        // To get the minutes, subtract it with years, 
        // months, seconds and hours and divide the  
        // resultant date into total seconds i.e. 60 
        $minutes = floor(($diff - $years * 365*60*60*24  
                 - $months*30*60*60*24 - $days*60*60*24  
                                  - $hours*60*60)/ 60);  
          
          
        // To get the minutes, subtract it with years, 
        // months, seconds, hours and minutes  
        $seconds = floor(($diff - $years * 365*60*60*24  
                 - $months*30*60*60*24 - $days*60*60*24 
                        - $hours*60*60 - $minutes*60));  
          
        // Print the result       
        return array(
            'seconds'=>$seconds,
            'minutes'=>$minutes,
            'hours'=>$hours,
            'days'=>$days,
            'months'=>$months,
            'years'=>$years
        );
}

function addition_time($time,$int){
    
    $arr = explode(' ', $time);
    
    $time_addited = date('H:i', strtotime($arr[0]."$int hour"));

    return $time_addited;
}

?>