<?php

class M_laporan extends CI_Model {

    function fetchWhereUnit($unit,$alias=null)
    {
        $u = explode(",", $unit);
        // echo count($u);
        
        $i=1;
        $where='(';
        foreach ($u as $key => $value) {
            // $q = $this->db->get_where('unit',array('idunit'=>$value))->row();

            if($alias==null)
            {
                $where.= $alias.'idunit='.$value;
            } else {
                $where.= $alias.'.idunit='.$value;
            }
            

            if($i<count($u))
            {
                $where.=" OR ";
            }
            $i++;            
        }
        $where.=')';
        return $where;
    }

    function getAccBalance($idunit = null, $idaccounttype) {


        $sql = "select a.idaccount,a.accnumber,a.accname,a.balance,a.idpos
                    from account a
                    where a.idunit = $idunit and a.idaccounttype=$idaccounttype
                    AND a.display ISNULL and a.active=TRUE";
                    // echo $sql;
        $qkas = $this->db->query($sql);
        
        $arr = array();
        $i = 0;
        $total=0;
        foreach ($qkas->result() as $r) {
            $arr[$i]['idaccount'] = $r->idaccount;
            $arr[$i]['accnumber'] = $r->accnumber;
            $arr[$i]['accname'] = $r->accname;
            $arr[$i]['balance'] = $r->balance;
            $arr[$i]['idpos'] = $r->idpos;
//            echo $r->accname;
            $child = $this->checkChild2($idunit, $r->idaccount,$idaccounttype);
            $arr[$i]['child'] = $child['data'];
//            echo $r->accname.'-'.$arr[$i]['child'].'<br>';
            $i++;
            $total+=+$r->balance+$child['total'];
        }
        return array('data'=>$arr,'total'=>$total);
    }

    function cekDataExist($idunit,$idaccount,$arr)
    {   
        $var='false';
        if(is_array($arr))
        {
            $i=0;
            foreach ($arr as $key => $value) {
                echo $i.' foreach ';
                 echo " (".$value['idunit']."==".$idunit." && ".$value['idaccount']."==".$idaccount.")<br>";
                if($value['idunit']==$idunit && $value['idaccount']==$idaccount)
                {
                    //udah ada
                    break;
                    $var='true';  
                }
                $i++;
         }
                
        }
         
        return $var;
    }

    

    function getDataNeraca2($idunit = null, $startdate = null, $enddate = null, $idaccounttype) {
         // AND a.idaccount not in(select idparent from account where idparent!=0)
        // $sql = "select a.idaccount,a.accnumber,a.accname,b.balance,a.idpos,a.idunit
        //             from account a
        //             join clossing b ON a.idaccount = b.idaccount and a.idunit = b.idunit
        //             where ".$this->fetchWhereUnit($idunit,'b')." and a.idaccounttype=$idaccounttype 
        //             AND a.display ISNULL and a.active=TRUE AND
        //             b.dateclose between '$startdate' and '$enddate'";
                    // echo $sql;
        $sql = "select a.idaccount,a.idunit,a.accnumber,a.accname,b.balance,a.idpos
                    from account a
                    join clossing b ON a.idaccount = b.idaccount
                    where ".$this->fetchWhereUnit($idunit,'b')." and a.idaccounttype=$idaccounttype and a.idpos=1
                    AND a.display ISNULL and a.active=TRUE AND
                    b.dateclose between '$startdate' and '$enddate'";
                    // echo $sql.'               ';
        $qkas = $this->db->query($sql);
        
        $arr = array();
        $i = 0;
        $total=0;
        foreach ($qkas->result() as $r) {
               $arr[$i]['idunit'] = $r->idunit;
                    $arr[$i]['idaccount'] = $r->idaccount;
                    $arr[$i]['accnumber'] = $r->accnumber;
                    $arr[$i]['accname'] = $r->accname;
                    $arr[$i]['balance'] = $r->balance;
                    $arr[$i]['idpos'] = $r->idpos;
                   // echo $r->accname;
                    $child = $this->checkChild($idunit, $startdate, $enddate, $r->idaccount,$idaccounttype);
                    $arr[$i]['child'] = $child['data'];
                   // echo $r->accname.'-'.$arr[$i]['child'].'<br>';
                    $i++;
                    $total+=$child['total'];
            
        //     if($i>=1)
        //     {
        //         // echo ' i:'.$i;
        //         $ibefore = $i-1;
        //         // echo ' ibefore:'.$ibefore.' ';
        //         // print_r($arr[$ibefore]['idunit']);
        //         // echo 's'.$arr[$ibefore]['idunit'];
        //         // exit;
        //          echo "<br> $i>=1 :".$r->idunit.':'.$r->idaccount.':'.$r->accname.' - child:'.is_array($arr[$ibefore]['child']).'<hr>';
                     
        //         if ($arr[$ibefore]['child']) {
                    
        //              $cek = $this->cekDataExist($r->idunit,$r->idaccount,$arr[$ibefore]['child']);
        //              echo '<br>cek'.$cek.'<br>';
        //              if($cek=='true')
        //              {
        //                 echo ' udah ada.<br>';
        //                 // print_r($arr[$ibefore]['child']);
        //                 // exit;
        //              }
        //               else {
        //                 $arr[$i]['idunit'] = $r->idunit;
        //                 $arr[$i]['idaccount'] = $r->idaccount;
        //                 $arr[$i]['accnumber'] = $r->accnumber;
        //                 $arr[$i]['accname'] = $r->accname;
        //                 $arr[$i]['balance'] = $r->balance;
        //                 $arr[$i]['idpos'] = $r->idpos;
        //     //            echo $r->accname;
        //                 $child = $this->checkChild($idunit, $startdate, $enddate, $r->idaccount,$idaccounttype);
        //                 $arr[$i]['child'] = $child['data'];
        //     //            echo $r->accname.'-'.$arr[$i]['child'].'<br>';
        //                 $i++;
        //                 $total+=$child['total'];

        //              }
                   
        //         } else {
        //             echo "<br> ada idunit :".$r->idunit.':'.$r->accname;
        //             $arr[$i]['idunit'] = $r->idunit;
        //             $arr[$i]['idaccount'] = $r->idaccount;
        //             $arr[$i]['accnumber'] = $r->accnumber;
        //             $arr[$i]['accname'] = $r->accname;
        //             $arr[$i]['balance'] = $r->balance;
        //             $arr[$i]['idpos'] = $r->idpos;
        // //            echo $r->accname;
        //             $child = $this->checkChild($idunit, $startdate, $enddate, $r->idaccount,$idaccounttype);
        //             $arr[$i]['child'] = $child['data'];
        // //            echo $r->accname.'-'.$arr[$i]['child'].'<br>';
        //             $i++;
        //             $total+=$child['total'];
        //         }
        //         // exit;
        //     } else if($i==0)
        //     {
        //             $arr[$i]['idunit'] = $r->idunit;
        //             $arr[$i]['idaccount'] = $r->idaccount;
        //             $arr[$i]['accnumber'] = $r->accnumber;
        //             $arr[$i]['accname'] = $r->accname;
        //             $arr[$i]['balance'] = $r->balance;
        //             $arr[$i]['idpos'] = $r->idpos;
        //            // echo $r->accname;
        //             $child = $this->checkChild($idunit, $startdate, $enddate, $r->idaccount,$idaccounttype);
        //             $arr[$i]['child'] = $child['data'];
        //            // echo $r->accname.'-'.$arr[$i]['child'].'<br>';
        //             $i++;
        //             $total+=$child['total'];
        //              // echo "<br>ada idunit :".$r->idunit.':'.$r->accname.'<br>';
        //              // print_r($arr);
        //              // echo '<hr>';
        //              // // exit;
        //         // echo ' i:'.$i;

        //     }
        //      echo "<br>ada idunit :".$r->idunit.':'.$r->accname.'<br>';
        //              print_r($arr);
        //              echo '<hr>';
        //              // exit;
           
        }
        return array('data'=>$arr,'total'=>$total);
    }

	function getDataNeraca($idunit = null, $startdate = null, $enddate = null, $idaccounttype) {


        $sql = "select a.idaccount,a.accnumber,a.accname,b.balance,a.idpos
                    from account a
                    join clossing b ON a.idaccount = b.idaccount
                    where a.idunit = $idunit and a.idaccounttype=$idaccounttype and a.idpos=1
                    AND a.display ISNULL and a.active=TRUE AND
                    b.dateclose between '$startdate' and '$enddate'";
        $qkas = $this->db->query($sql);
        
        $arr = array();
        $i = 0;
        $total=0;
        foreach ($qkas->result() as $r) {
        	$arr[$i]['idaccount'] = $r->idaccount;
            $arr[$i]['accnumber'] = $r->accnumber;
            $arr[$i]['accname'] = $r->accname;
            $arr[$i]['balance'] = $r->balance;
            $arr[$i]['idpos'] = $r->idpos;
//            echo $r->accname;
            $child = $this->checkChild($idunit, $startdate, $enddate, $r->idaccount,$idaccounttype);
            $arr[$i]['child'] = $child['data'];
//            echo $r->accname.'-'.$arr[$i]['child'].'<br>';
            $i++;
            $total+=$child['total'];
        }
        return array('data'=>$arr,'total'=>$total);
    }

    function getDataNeraca3($idunit = null, $startdate = null, $enddate = null, $idaccounttype) {

        $sql = "select a.idaccount,a.idunit,a.accnumber,a.accname,a.idpos
                    from account a
                    where a.idunit=$idunit and a.idaccounttype=$idaccounttype
                    AND a.display ISNULL and a.active=TRUE and a.idpos=2";
                    // echo $sql.'               ';
        $qkas = $this->db->query($sql);
        
        $arr = array();
        $i = 0;
        $total=0;
        foreach ($qkas->result() as $r) {
// echo 'asd';
            $sql2 = "select sum(debit) as debit,sum(credit) as credit
                    from
                        accountlog a
                    join journal b ON a.idjournal = b.idjournal and b.idunit = $idunit
                    join account c ON a.idaccount = c.idaccount and c.idunit = $idunit
                    where a.idunit=$idunit and a.idaccount=$r->idaccount AND b.datejournal between '$startdate' and '$enddate'";
                    // if($idaccounttype==11)
                    // {
                    // echo $sql2.'<br>';
                    // }
            $q = $this->db->query($sql2);
            if($q->num_rows()>0)
            {
                $rlog = $q->row();
                if($rlog->debit>$rlog->credit)
                {
                    $balance = $rlog->debit-$rlog->credit;
                } else if($idaccounttype==1 || $idaccounttype==19){
                    //kas and bank
                    $balance = $rlog->debit-$rlog->credit;
                } else {
                    $balance = $rlog->credit-$rlog->debit;
                }
                
            } else {
                $balance=0;
            }
            $arr[$i]['idunit'] = $r->idunit;
            $arr[$i]['idaccount'] = $r->idaccount;
            $arr[$i]['accnumber'] = $r->accnumber;
            $arr[$i]['accname'] = $r->accname;
            $arr[$i]['balance'] = $balance;
            $arr[$i]['idpos'] = $r->idpos;
           // echo $r->accname;
            // $child = $this->checkChildLabaRugi($idunit, $startdate, $enddate, $r->idaccount,$idaccounttype);
            $child = array('data'=>'false','total'=>0);
            $arr[$i]['child'] = $child['data']; 
           // echo $r->accname.'-'.$arr[$i]['child'].'<br>';
            $i++;
            $total+=$balance;           
        }
        return array('data'=>$arr,'total'=>$total);
    }

    function getDataLabaRugi($idunit = null, $startdate = null, $enddate = null, $idaccounttype) {
         // AND a.idaccount not in(select idparent from account where idparent!=0)
        // $sql = "select a.idaccount,a.accnumber,a.accname,b.balance,a.idpos,a.idunit
        //             from account a
        //             join clossing b ON a.idaccount = b.idaccount and a.idunit = b.idunit
        //             where ".$this->fetchWhereUnit($idunit,'b')." and a.idaccounttype=$idaccounttype 
        //             AND a.display ISNULL and a.active=TRUE AND
        //             b.dateclose between '$startdate' and '$enddate'";
                    // echo $sql;
        // $sql = "select a.idaccount,a.idunit,a.accnumber,a.accname,b.balance,a.idpos
        //             from account a
        //             join clossing b ON a.idaccount = b.idaccount
        //             where ".$this->fetchWhereUnit($idunit,'b')." and a.idaccounttype=$idaccounttype and a.idpos=1
        //             AND a.display ISNULL and a.active=TRUE AND
        //             b.dateclose between '$startdate' and '$enddate'";

        $sql = "select a.idaccount,a.idunit,a.accnumber,a.accname,a.idpos
                    from account a
                    where a.idunit=$idunit and a.idaccounttype=$idaccounttype
                    AND a.display ISNULL and a.active=TRUE
                    AND a.idparent IN (select idaccount from account 
                                        where idunit = $idunit and display is null)";
                    // echo $sql.'               ';

                    //   if($idaccounttype==14) {
                    //    echo $sql; exit; 
                    // } 
        $qkas = $this->db->query($sql);
        
        $arr = array();
        $i = 0;
        $total=0;
        foreach ($qkas->result() as $r) {
// echo 'asd';
            $sql2 = "select sum(debit) as debit,sum(credit) as credit
                    from accountlog a
                    join account b ON a.idaccount = b.idaccount and a.idunit = b.idunit
                    where b.display is null and b.idunit=$idunit and a.idaccount=$r->idaccount AND tanggal between '$startdate' and '$enddate'";
                    // echo $sql2.'<br>';
                  
            $q = $this->db->query($sql2);
            if($q->num_rows()>0)
            {
                $rlog = $q->row();
                if($rlog->credit>$rlog->debit)
                {
                    $balance = $rlog->credit-$rlog->debit;
                } else {
                    $balance = $rlog->debit-$rlog->credit;
                }
            } else {
                $balance=0;
            }
            $arr[$i]['idunit'] = $r->idunit;
            $arr[$i]['idaccount'] = $r->idaccount;
            $arr[$i]['accnumber'] = $r->accnumber;
            $arr[$i]['accname'] = $r->accname;
            $arr[$i]['balance'] = $balance;
            $arr[$i]['idpos'] = $r->idpos;
           // echo $r->accname;
            // $child = $this->checkChildLabaRugi($idunit, $startdate, $enddate, $r->idaccount,$idaccounttype);
            $child = array('data'=>'false','total'=>0);
            $arr[$i]['child'] = $child['data']; 
           // echo $r->accname.'-'.$arr[$i]['child'].'<br>';
            $i++;
            $total+=$balance;           
        }
        return array('data'=>$arr,'total'=>$total);
    }

    function checkChildLabaRugi($idunit, $startdate, $enddate, $idaccount,$idaccounttype) {
      $sql = "select account.idaccount,accnumber,accname,sum(accountlog.debit-accountlog.credit) as balance,idpos,account.idunit
                from account
                left join accountlog ON accountlog.idaccount = account.idaccount and accountlog.idunit = account.idunit
                where (account.idunit=$idunit) and idaccounttype=$idaccounttype and idparent=$idaccount
                AND display ISNULL and active=TRUE AND
                tanggal between '$startdate' and '$enddate'
                group by account.idaccount,accnumber,accname,idpos,account.idunit";
                // echo $sql;
                // exit;
        // $sql = "select idaccount,accnumber,accname,balance,idpos,idunit
        //             from clossing
        //             where ".$this->fetchWhereUnit($idunit)." and idaccounttype=$idaccounttype and idparent=$idaccount
        //             AND display ISNULL and active=TRUE AND
        //             dateclose between '$startdate' and '$enddate'";
//        echo $sql,'<hr>';
//        exit;
        $qkas = $this->db->query($sql);
        if ($qkas->num_rows() > 0) {
            $arr = array();
            $i = 0;
            $total=0;
            foreach ($qkas->result() as $r) {
                $arr[$i]['idunit'] = $r->idunit;
                $arr[$i]['idaccount'] = $r->idaccount;
                $arr[$i]['accnumber'] = $r->accnumber;
                $arr[$i]['accname'] = $r->accname;
                $arr[$i]['balance'] = $r->balance;
                $arr[$i]['idpos'] = $r->idpos;
//                echo $r->accname;
//                exit;
                $recursive = $this->recursiveLabaRugi($idunit, $startdate, $enddate, $r->idaccount,$idaccounttype);
                $arr[$i]['child'] = $recursive['data'];
                $i++;
                $total+=$r->balance+$recursive['total'];
            }
            return array('data'=>$arr,'total'=>$total);
        } else {
            return array('data'=>'false','total'=>0);
        }
    }

    function checkChild($idunit, $startdate, $enddate, $idaccount,$idaccounttype) {

        $sql = "select idaccount,accnumber,accname,balance,idpos,idunit
                    from clossing
                    where ".$this->fetchWhereUnit($idunit)." and idaccounttype=$idaccounttype and idparent=$idaccount
                    AND display ISNULL and active=TRUE AND
                    dateclose between '$startdate' and '$enddate'";
//        echo $sql,'<hr>';
//        exit;
        $qkas = $this->db->query($sql);
        if ($qkas->num_rows() > 0) {
            $arr = array();
            $i = 0;
            $total=0;
            foreach ($qkas->result() as $r) {
                $arr[$i]['idunit'] = $r->idunit;
            	$arr[$i]['idaccount'] = $r->idaccount;
                $arr[$i]['accnumber'] = $r->accnumber;
                $arr[$i]['accname'] = $r->accname;
                $arr[$i]['balance'] = $r->balance;
                $arr[$i]['idpos'] = $r->idpos;
//                echo $r->accname;
//                exit;
                $recursive = $this->recursive($idunit, $startdate, $enddate, $r->idaccount,$idaccounttype);
                $arr[$i]['child'] = $recursive['data'];
                $i++;
                $total+=$r->balance+$recursive['total'];
            }
            return array('data'=>$arr,'total'=>$total);
        } else {
            return array('data'=>'false','total'=>0);
        }
    }

    function checkChild2($idunit, $idaccount,$idaccounttype) {

        $sql = "select idaccount,accnumber,accname,balance,idpos
                    from clossing
                    where idunit = $idunit and idaccounttype=$idaccounttype and idparent=$idaccount
                    AND display ISNULL and active=TRUE";
//        echo $sql,'<hr>';
//        exit;
        $qkas = $this->db->query($sql);
        if ($qkas->num_rows() > 0) {
            $arr = array();
            $i = 0;
            $total=0;
            foreach ($qkas->result() as $r) {
                $arr[$i]['idaccount'] = $r->idaccount;
                $arr[$i]['accnumber'] = $r->accnumber;
                $arr[$i]['accname'] = $r->accname;
                $arr[$i]['balance'] = $r->balance;
                $arr[$i]['idpos'] = $r->idpos;
//                echo $r->accname;
//                exit;
                $recursive = $this->recursive2($idunit, $r->idaccount,$idaccounttype);
                $arr[$i]['child'] = $recursive['data'];
                $i++;
                $total+=$r->balance+$recursive['total'];
            }
            return array('data'=>$arr,'total'=>$total);
        } else {
            return array('data'=>'false','total'=>0);
        }
    }

    function recursive($idunit, $startdate, $enddate, $idaccount,$idaccounttype) {
        return $this->checkChild($idunit, $startdate, $enddate, $idaccount,$idaccounttype);
    }

     function recursiveLabaRugi($idunit, $startdate, $enddate, $idaccount,$idaccounttype) {
        return $this->checkChildLabaRugi($idunit, $startdate, $enddate, $idaccount,$idaccounttype);
    }

    function recursive2($idunit, $idaccount,$idaccounttype) {
        return $this->checkChild2($idunit, $idaccount,$idaccounttype);
    }

}

?>