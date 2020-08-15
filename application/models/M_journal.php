<?php

class M_journal extends CI_Model {

    function loan_approved($idunit,$amount,$coa_ar_id,$coa_bank_id,$date,$memo,$coa_ar_interest_id,$total_interest_amount){
        $amount = intval($amount);
        $tgl = explode("-", $date);
        $userin = 0;

        $idjournal = $this->m_data->getPrimaryID2(null,'journal','idjournal');

        $d = array(
            'idjournal' => $idjournal,
            'idjournaltype' => 10, //piutang
            'nojournal' => rand(11111,99999),
//                    name character varying(225),
            'datejournal' => $date,
            'memo' => $memo,
            'totaldebit' => $amount,
            'totalcredit' => $amount,
//                    'totaltax' double precision,
//                    isrecuring boolean,
            'year' => $tgl[0],
            'month' => $tgl[1],
//                    display integer,
            'userin' => $userin,
            'usermod' =>$userin,
            'datein' => date('Y-m-d H:m:s'),
            'datemod' => date('Y-m-d H:m:s'),
            'idunit' => $idunit
        );
        $this->db->insert('journal', $d);

        //piutang - debit
        // $curBalanceD = $this->m_account->getCurrBalance($coa_ar_id, $idunit);
        // $newBalanceD = $curBalanceD + ($amount-$total_interest_amount);
        // $ditem = array(
        //     'idjournal' => $idjournal,
        //     'idaccount' => $coa_ar_id,
        //     'debit' => ($amount-$total_interest_amount),
        //     'credit' => 0,
        //     'lastbalance' => $curBalanceD,
        //     'currbalance' => $newBalanceD
        // );
        // $this->db->insert('journalitem', $ditem);
        // $this->m_account->saveNewBalance($coa_ar_id, $newBalanceD, $idunit,$userin);
        // $this->m_account->saveAccountLog($idunit,$coa_ar_id,0,($amount-$total_interest_amount),$date,$idjournal,$userin);

        $curBalanceD = $this->m_account->getCurrBalance($coa_ar_id, $idunit);
        $newBalanceD = $curBalanceD + ($amount);
        $ditem = array(
            'idjournal' => $idjournal,
            'idaccount' => $coa_ar_id,
            'debit' => ($amount),
            'credit' => 0,
            'lastbalance' => $curBalanceD,
            'currbalance' => $newBalanceD
        );
        $this->db->insert('journalitem', $ditem);
        $this->m_account->saveNewBalance($coa_ar_id, $newBalanceD, $idunit,$userin);
        $this->m_account->saveAccountLog($idunit,$coa_ar_id,0,($amount),$date,$idjournal,$userin);

        //piutang bunga - debit
        // $curBalanceD = $this->m_account->getCurrBalance($coa_ar_interest_id, $idunit);
        // $newBalanceD = $curBalanceD + $total_interest_amount;
        // $ditem = array(
        //     'idjournal' => $idjournal,
        //     'idaccount' => $coa_ar_interest_id,
        //     'debit' => $total_interest_amount,
        //     'credit' => 0,
        //     'lastbalance' => $curBalanceD,
        //     'currbalance' => $newBalanceD
        // );
        // $this->db->insert('journalitem', $ditem);
        // $this->m_account->saveNewBalance($coa_ar_interest_id, $newBalanceD, $idunit,$userin);
        // $this->m_account->saveAccountLog($idunit,$coa_ar_interest_id,0,$total_interest_amount,$date,$idjournal,$userin);

        //kas/bank - credit
        $curBalanceK = $this->m_account->getCurrBalance($coa_bank_id, $idunit);
        $newBalanceK = $curBalanceK - $amount;

        $ditem = array(
            'idjournal' => $idjournal,
            'idaccount' => $coa_bank_id,
            'debit' => 0,
            'credit' => $amount,
            'lastbalance' => $curBalanceK,
            'currbalance' => $newBalanceK
        );
        $this->db->insert('journalitem', $ditem);
        $this->m_account->saveNewBalance($coa_bank_id, $newBalanceK, $idunit,$userin);
        $this->m_account->saveAccountLog($idunit,$coa_bank_id,$amount,0,$date,$idjournal,$userin);

        return $idjournal;
    }

    function loan_payment($idunit,$amount,$coa_ar_id,$coa_bank_id,$date,$memo){
        
    }

    function loan_full_payment($idunit,$amount,$coa_ar_id,$coa_bank_id,$date,$memo,$approved_interest_amount=null,$coa_interest_id=null){
        $amount = intval($amount);
        $tgl = explode("-", $date);
        $userin = 0;

        $idjournal = $this->m_data->getPrimaryID2(null,'journal','idjournal');
        $date_arr = explode('-', $date);

        $params = array(
            'idunit' => $idunit,
            'prefix' => 'LP',
            'table' => 'journal',
            'fieldpk' => 'idjournal',
            'fieldname' => 'nojournal',
            'extraparams'=> null,
            'year'=>substr($date_arr[0], -2),
            'month'=>$date_arr[1]
        );
        // $this->load->library('../controllers/setup');
        $nojournal = $this->m_data->getNextNoArticle($params);

        $d = array(
            'idjournal' => $idjournal,
            'idjournaltype' => 10, //piutang
            'nojournal' => $nojournal,
//                    name character varying(225),
            'datejournal' => $date,
            'memo' => $memo,
            'totaldebit' => $amount,
            'totalcredit' => $amount,
//                    'totaltax' double precision,
//                    isrecuring boolean,
            'year' => $tgl[0],
            'month' => $tgl[1],
//                    display integer,
            'userin' => $userin,
            'usermod' =>$userin,
            'datein' => date('Y-m-d H:m:s'),
            'datemod' => date('Y-m-d H:m:s'),
            'idunit' => $idunit
        );
        $this->db->insert('journal', $d);

        //kas/bank - debit
        $curBalanceK = $this->m_account->getCurrBalance($coa_bank_id, $idunit);
        $newBalanceK = $curBalanceK + $amount;

        $ditem = array(
            'idjournal' => $idjournal,
            'idaccount' => $coa_bank_id,
            'debit' => $amount,
            'credit' => 0,
            'lastbalance' => $curBalanceK,
            'currbalance' => $newBalanceK
        );
        $this->db->insert('journalitem', $ditem);
        $this->m_account->saveNewBalance($coa_bank_id, $newBalanceK, $idunit,$userin);
        $this->m_account->saveAccountLog($idunit,$coa_bank_id,0,$amount,$date,$idjournal,$userin);

        if($coa_interest_id!=null){
            //pendapatan bunga- credit
            $curBalanceD = $this->m_account->getCurrBalance($coa_interest_id, $idunit);
            $newBalanceD = $curBalanceD + $approved_interest_amount;
            $ditem = array(
                'idjournal' => $idjournal,
                'idaccount' => $coa_interest_id,
                'debit' => 0,
                'credit' => $approved_interest_amount,
                'lastbalance' => $curBalanceD,
                'currbalance' => $newBalanceD
            );
            $this->db->insert('journalitem', $ditem);
            $this->m_account->saveNewBalance($coa_interest_id, $newBalanceD, $idunit,$userin);
            $this->m_account->saveAccountLog($idunit,$coa_interest_id,$approved_interest_amount,0,$date,$idjournal,$userin);
        }
        

        //piutang pinjaman - credit
        if($coa_interest_id!=null){
            $amount = $amount-$approved_interest_amount;
        }

        $curBalanceD = $this->m_account->getCurrBalance($coa_ar_id, $idunit);
        $newBalanceD = $curBalanceD - $amount;
        $ditem = array(
            'idjournal' => $idjournal,
            'idaccount' => $coa_ar_id,
            'debit' => 0,
            'credit' => $amount,
            'lastbalance' => $curBalanceD,
            'currbalance' => $newBalanceD
        );
        $this->db->insert('journalitem', $ditem);
        $this->m_account->saveNewBalance($coa_ar_id, $newBalanceD, $idunit,$userin);
        $this->m_account->saveAccountLog($idunit,$coa_ar_id,$amount,0,$date,$idjournal,$userin);

        

        return $idjournal;
    }

    function withdraw_saving($idunit, $accdebet, $acckredit, $memo, $noref, $date, $amount,$taxReceive=0,$userin){
        $amount = intval($amount);
        $tgl = explode("-", $date);

        $idjournal = $this->m_data->getPrimaryID2(null,'journal','idjournal');

        $d = array(
            'idjournal' => $idjournal,
            'idjournaltype' => 6,
            'nojournal' => $noref,
//                    name character varying(225),
            'datejournal' => $date,
            'memo' => $memo,
            'totaldebit' => $amount,
            'totalcredit' => $amount,
//                    'totaltax' double precision,
//                    isrecuring boolean,
            'year' => $tgl[0],
            'month' => $tgl[1],
//                    display integer,
            'userin' => $userin,
            'usermod' =>$userin,
            'datein' => date('Y-m-d H:m:s'),
            'datemod' => date('Y-m-d H:m:s'),
            'idunit' => $idunit
        );
        $this->db->insert('journal', $d);

        //debit / Simpanan
        // $accdebet = ;
        $curBalanceD = $this->m_account->getCurrBalance($accdebet, $idunit);
        $newBalanceD = $curBalanceD - $amount;
        $ditem = array(
            'idjournal' => $idjournal,
            'idaccount' => $accdebet,
            'debit' => $amount,
            'credit' => 0,
            'lastbalance' => $curBalanceD,
            'currbalance' => $newBalanceD
        );
        $this->db->insert('journalitem', $ditem);
        $this->m_account->saveNewBalance($accdebet, $newBalanceD, $idunit,$userin);
        $this->m_account->saveAccountLog($idunit,$accdebet,0,$amount,$date,$idjournal,$userin);

        //credit / Kas
        // $acckredit = ;
        $curBalanceK = $this->m_account->getCurrBalance($acckredit, $idunit);
        $newBalanceK = $curBalanceK - $amount;

        $ditem = array(
            'idjournal' => $idjournal,
            'idaccount' => $acckredit,
            'debit' => 0,
            'credit' => $amount,
            'lastbalance' => $curBalanceK,
            'currbalance' => $newBalanceK
        );
        $this->db->insert('journalitem', $ditem);
        $this->m_account->saveNewBalance($acckredit, $newBalanceK, $idunit,$userin);
        $this->m_account->saveAccountLog($idunit,$acckredit,$amount,0,$date,$idjournal,$userin);
      

        return $idjournal;
    }

	function receive_saving($idunit, $accdebet, $acckredit, $memo, $noref, $date, $amount,$taxReceive=0,$userin){
        $amount = intval($amount);
        $tgl = explode("-", $date);

        $idjournal = $this->m_data->getPrimaryID2(null,'journal','idjournal');

        $d = array(
            'idjournal' => $idjournal,
            'idjournaltype' => 6,
            'nojournal' => $noref,
//                    name character varying(225),
            'datejournal' => $date,
            'memo' => $memo,
            'totaldebit' => $amount,
            'totalcredit' => $amount,
//                    'totaltax' double precision,
//                    isrecuring boolean,
            'year' => $tgl[0],
            'month' => $tgl[1],
//                    display integer,
            'userin' => $userin,
            'usermod' =>$userin,
            'datein' => date('Y-m-d H:m:s'),
            'datemod' => date('Y-m-d H:m:s'),
            'idunit' => $idunit
        );
        $this->db->insert('journal', $d);

        //debit / Kas
        // $accdebet = ;
        $curBalanceD = $this->m_account->getCurrBalance($accdebet, $idunit);
        $newBalanceD = $curBalanceD + $amount;
        $ditem = array(
            'idjournal' => $idjournal,
            'idaccount' => $accdebet,
            'debit' => $amount,
            'credit' => 0,
            'lastbalance' => $curBalanceD,
            'currbalance' => $newBalanceD
        );
        $this->db->insert('journalitem', $ditem);
        $this->m_account->saveNewBalance($accdebet, $newBalanceD, $idunit,$userin);
        $this->m_account->saveAccountLog($idunit,$accdebet,0,$amount,$date,$idjournal,$userin);

        //credit / Simpanan
        // $acckredit = ;
   		$curBalanceK = $this->m_account->getCurrBalance($acckredit, $idunit);
        $newBalanceK = $curBalanceK + $amount;

        $ditem = array(
            'idjournal' => $idjournal,
            'idaccount' => $acckredit,
            'debit' => 0,
            'credit' => $amount,
            'lastbalance' => $curBalanceK,
            'currbalance' => $newBalanceK
        );
        $this->db->insert('journalitem', $ditem);
        $this->m_account->saveNewBalance($acckredit, $newBalanceK, $idunit,$userin);
        $this->m_account->saveAccountLog($idunit,$acckredit,$amount,0,$date,$idjournal,$userin);

        if($taxReceive!=0){
            //pajak
            $idaccount = $this->m_data->getIdAccount(34, $idunit);
            $curBalanceK = $this->m_account->getCurrBalance($idaccount, $idunit);
            $newBalanceK = $curBalanceK + $taxReceive;
            $ditem = array(
                'idjournal' => $idjournal,
                'idaccount' => $idaccount,
                'debit' => 0,
                'credit' => $taxReceive,
                'lastbalance' => $curBalanceK,
                'currbalance' => $newBalanceK
            );
            $this->db->insert('journalitem', $ditem);
            $this->m_account->saveNewBalance($idaccount, $newBalanceK, $idunit,$userin);
            $this->m_account->saveAccountLog($idunit,$idaccount,$taxReceive,0,$date,$idjournal,$userin);
        }
      

        return $idjournal;
    }
    
    function asset_journal($idjournal,$idunit, $accdebet, $acckredit, $memo, $noref,$code, $date, $amount, $userin){
        $amount = intval($amount);
        $tgl = explode("-", $date);

        // $idjournal = $this->m_data->getPrimaryID2(null,'journal','idjournal');

        $d = array(
            'idjournal' => $idjournal,
            'idjournaltype' => 6,
            'nojournal' =>$code,
//                    name character varying(225),
            'datejournal' => $date,
            'memo' => 'New Asset '.$code.' '.$memo,
            'totaldebit' => $amount,
            'totalcredit' => $amount,
//                    'totaltax' double precision,
//                    isrecuring boolean,
            'year' => $tgl[0],
            'month' => $tgl[1],
//                    display integer,
            'userin' => $userin,
            'usermod' =>$userin,
            'datein' => date('Y-m-d H:m:s'),
            'datemod' => date('Y-m-d H:m:s'),
            'idunit' => $idunit
        );
        $this->db->insert('journal', $d);

        //debit / Kas
        // $accdebet = ;
        $curBalanceD = $this->m_account->getCurrBalance($accdebet, $idunit);
        $newBalanceD = $curBalanceD + $amount;
        $ditem = array(
            'idjournal' => $idjournal,
            'idaccount' => $accdebet,
            'debit' => $amount,
            'credit' => 0,
            'lastbalance' => $curBalanceD,
            'currbalance' => $newBalanceD
        );
        $this->db->insert('journalitem', $ditem);
        $this->m_account->saveNewBalance($accdebet, $newBalanceD, $idunit,$userin);
        $this->m_account->saveAccountLog($idunit,$accdebet,0,$amount,$date,$idjournal,$userin);

        //credit / Simpanan
        // $acckredit = ;
        $curBalanceK = $this->m_account->getCurrBalance($acckredit, $idunit);
        $newBalanceK = $curBalanceK - $amount;

        $ditem = array(
            'idjournal' => $idjournal,
            'idaccount' => $acckredit,
            'debit' => 0,
            'credit' => $amount,
            'lastbalance' => $curBalanceK,
            'currbalance' => $newBalanceK
        );
        $this->db->insert('journalitem', $ditem);
        $this->m_account->saveNewBalance($acckredit, $newBalanceK, $idunit,$userin);
        $this->m_account->saveAccountLog($idunit,$acckredit,$amount,0,$date,$idjournal,$userin);
    }

    function nilai_awal_journal($idjournal,$idunit, $accdebet, $acckredit, $memo, $noref,$code, $date, $amount, $userin){
        $amount = intval($amount);
        $tgl = explode("-", $date);

        // $idjournal = $this->m_data->getPrimaryID2(null,'journal','idjournal');

        $d = array(
            'idjournal' => $idjournal,
            'idjournaltype' => 6,
            'nojournal' => $noref.$code,
//                    name character varying(225),
            'datejournal' => $date,
            'memo' => 'Open Depreciation '.$noref.' '.$memo,
            'totaldebit' => $amount,
            'totalcredit' => $amount,
//                    'totaltax' double precision,
//                    isrecuring boolean,
            'year' => $tgl[0],
            'month' => $tgl[1],
//                    display integer,
            'userin' => $userin,
            'usermod' =>$userin,
            'datein' => date('Y-m-d H:m:s'),
            'datemod' => date('Y-m-d H:m:s'),
            'idunit' => $idunit
        );
        $this->db->insert('journal', $d);

        //debit / Kas
        // $accdebet = ;
        $curBalanceD = $this->m_account->getCurrBalance($accdebet, $idunit);
        $newBalanceD = $curBalanceD + $amount;
        $ditem = array(
            'idjournal' => $idjournal,
            'idaccount' => $accdebet,
            'debit' => $amount,
            'credit' => 0,
            'lastbalance' => $curBalanceD,
            'currbalance' => $newBalanceD
        );
        $this->db->insert('journalitem', $ditem);
        $this->m_account->saveNewBalance($accdebet, $newBalanceD, $idunit,$userin);
        $this->m_account->saveAccountLog($idunit,$accdebet,0,$amount,$date,$idjournal,$userin);

        //credit / Simpanan
        // $acckredit = ;
        $curBalanceK = $this->m_account->getCurrBalance($acckredit, $idunit);
        $newBalanceK = $curBalanceK + $amount;

        $ditem = array(
            'idjournal' => $idjournal,
            'idaccount' => $acckredit,
            'debit' => 0,
            'credit' => $amount,
            'lastbalance' => $curBalanceK,
            'currbalance' => $newBalanceK
        );
        $this->db->insert('journalitem', $ditem);
        $this->m_account->saveNewBalance($acckredit, $newBalanceK, $idunit,$userin);
        $this->m_account->saveAccountLog($idunit,$acckredit,$amount,0,$date,$idjournal,$userin);

    }

    function depreciation_journal($idjournal,$debit,$kredit,$date,$name,$code,$amount,$noref,$idunit,$userin){
        $amount = intval($amount);
        $tgl = explode("-", $date);

        // $idjournal = $this->m_data->getPrimaryID2(null,'journal','idjournal');

        $d = array(
            'idjournal' => $idjournal,
            'idjournaltype' => 6,
            'nojournal' => $noref,
//                    name character varying(225),
            'datejournal' => $date,
            'memo' => 'Depresiasi Asset '.$code.' '.$name,
            'totaldebit' => $amount,
            'totalcredit' => $amount,
//                    'totaltax' double precision,
//                    isrecuring boolean,
            'year' => $tgl[0],
            'month' => $tgl[1],
//                    display integer,
            'userin' => $userin,
            'usermod' =>$userin,
            'datein' => date('Y-m-d H:m:s'),
            'datemod' => date('Y-m-d H:m:s'),
            'idunit' => $idunit
        );
        $this->db->insert('journal', $d);

        //debit/beban akumulasi penyusutan 
        $accdebet = $kredit;
        $curBalanceD = $this->m_account->getCurrBalance($accdebet, $idunit);
        $newBalanceD = $curBalanceD + $amount;
        $ditem = array(
            'idjournal' => $idjournal,
            'idaccount' => $accdebet,
            'debit' => $amount,
            'credit' => 0,
            'lastbalance' => $curBalanceD,
            'currbalance' => $newBalanceD
        );
        $this->db->insert('journalitem', $ditem);
        $this->m_account->saveNewBalance($accdebet, $newBalanceD, $idunit,$userin);
        $this->m_account->saveAccountLog($idunit,$accdebet,0,$amount,$date,$idjournal,$userin);

        //credit /akumulasi penyusutan
        $acckredit = $debit;
        $curBalanceK = $this->m_account->getCurrBalance($acckredit, $idunit);
        $newBalanceK = $curBalanceK + $amount;

        $ditem = array(
            'idjournal' => $idjournal,
            'idaccount' => $acckredit,
            'debit' => 0,
            'credit' => $amount,
            'lastbalance' => $curBalanceK,
            'currbalance' => $newBalanceK
        );
        $this->db->insert('journalitem', $ditem);
        $this->m_account->saveNewBalance($acckredit, $newBalanceK, $idunit,$userin);
        $this->m_account->saveAccountLog($idunit,$acckredit,$amount,0,$date,$idjournal,$userin);

    }

    function dispose_journal($dispose_journal_id,$coa_fixed_asset_id,$coa_liability_id,$coa_accum_deprec_id,$coa_dispose_id,$date,$name,$code,$acquisition_price,$total_depreciation,$liability,$dispose_price,$noref,$idunit,$userin){
        // $amount = intval($amount);
        if($coa_dispose_id!=null){
            $totaldebit  = $total_depreciation+$liability+$dispose_price;
            $totalcredit = $acquisition_price;
            $nilai_buku  = $acquisition_price-$total_depreciation;
        }else{
            $totaldebit  = $total_depreciation+$liability;
            $totalcredit = $acquisition_price;

        }

        $tgl = explode("-", $date);

        // $idjournal = $this->m_data->getPrimaryID2(null,'journal','idjournal');

        $d = array(
            'idjournal' => $dispose_journal_id,
            'idjournaltype' => 6,
            'nojournal' => $noref,
//                    name character varying(225),
            'datejournal' => $date,
            'memo' => 'Pelepasan Asset '.$code.' '.$name,
            'totaldebit' => $totaldebit,
            'totalcredit' =>$totalcredit,
//                    'totaltax' double precision,
//                    isrecuring boolean,
            'year' => $tgl[0],
            'month' => $tgl[1],
//                    display integer,
            'userin' => $userin,
            'usermod' =>$userin,
            'datein' => date('Y-m-d H:m:s'),
            'datemod' => date('Y-m-d H:m:s'),
            'idunit' => $idunit
        );
        $this->db->insert('journal', $d);


        //debit / akumulasi penyusustan
        if($total_depreciation >0){
            $accdebet = $coa_accum_deprec_id;
            $curBalanceD = $this->m_account->getCurrBalance($accdebet, $idunit);
            $newBalanceD = $curBalanceD + $total_depreciation;
            $ditem = array(
                'idjournal' => $dispose_journal_id,
                'idaccount' => $accdebet,
                'debit' => $total_depreciation,
                'credit' => 0,
                'lastbalance' => $curBalanceD,
                'currbalance' => $newBalanceD
            );
            $this->db->insert('journalitem', $ditem);
            $this->m_account->saveNewBalance($accdebet, $newBalanceD, $idunit,$userin);
            $this->m_account->saveAccountLog($idunit,$accdebet,0,$total_depreciation,$date,$dispose_journal_id,$userin);
    
        }
        
        if($coa_dispose_id!=null && $dispose_price>0){
            //debit / Kas Bank
            $accdebet    = $coa_dispose_id;
            $curBalanceD = $this->m_account->getCurrBalance($accdebet, $idunit);
            $newBalanceD = $curBalanceD +$dispose_price;
            $ditem = array(
                'idjournal' => $dispose_journal_id,
                'idaccount' => $accdebet,
                'debit' => $dispose_price,
                'credit' => 0,
                'lastbalance' => $curBalanceD,
                'currbalance' => $newBalanceD
            );
            $this->db->insert('journalitem', $ditem);
            $this->m_account->saveNewBalance($accdebet, $newBalanceD, $idunit,$userin);
            $this->m_account->saveAccountLog($idunit,$accdebet,0,$dispose_price,$date,$dispose_journal_id,$userin); 
        }
        //debit / kerugian 
        if(isset($dispose_price) && $nilai_buku > $dispose_price){
            $accdebet = $coa_liability_id;
            $curBalanceD = $this->m_account->getCurrBalance($accdebet, $idunit);
            $newBalanceD = $curBalanceD + $liability;
            $ditem = array(
                'idjournal' => $dispose_journal_id,
                'idaccount' => $accdebet,
                'debit' => $liability,
                'credit' => 0,
                'lastbalance' => $curBalanceD,
                'currbalance' => $newBalanceD
            );
            $this->db->insert('journalitem', $ditem);
            $this->m_account->saveNewBalance($accdebet, $newBalanceD, $idunit,$userin);
            $this->m_account->saveAccountLog($idunit,$accdebet,0,$liability,$date,$dispose_journal_id,$userin);
        
        }else if(isset($dispose_price) && $nilai_buku < $dispose_price){
            $liability   = $liability*(-1); 
            $acckredit    = $coa_liability_id;
            $curBalanceD = $this->m_account->getCurrBalance($acckredit, $idunit);
            $newBalanceD = $curBalanceD - $liability;
            $ditem = array(
                'idjournal' => $dispose_journal_id,
                'idaccount' => $acckredit,
                'debit' => 0,
                'credit' => $liability,
                'lastbalance' => $curBalanceD,
                'currbalance' => $newBalanceD
            );
            $this->db->insert('journalitem', $ditem);
            $this->m_account->saveNewBalance($acckredit, $newBalanceD, $idunit,$userin);
            $this->m_account->saveAccountLog($idunit,$acckredit,$liability,0,$date,$dispose_journal_id,$userin);
            // $this->m_account->saveAccountLog($idunit,$acckredit,$acquisition_price,0,$date,$dispose_journal_id,$userin);
        }else{
            $accdebet = $coa_liability_id;
            $curBalanceD = $this->m_account->getCurrBalance($accdebet, $idunit);
            $newBalanceD = $curBalanceD + $liability;
            $ditem = array(
                'idjournal' => $dispose_journal_id,
                'idaccount' => $accdebet,
                'debit' => $liability,
                'credit' => 0,
                'lastbalance' => $curBalanceD,
                'currbalance' => $newBalanceD
            );
            $this->db->insert('journalitem', $ditem);
            $this->m_account->saveNewBalance($accdebet, $newBalanceD, $idunit,$userin);
            $this->m_account->saveAccountLog($idunit,$accdebet,0,$liability,$date,$dispose_journal_id,$userin);
            // $this->m_account->saveAccountLog($idunit,$acckredit,$acquisition_price,0,$date,$dispose_journal_id,$userin);
        }
        

        //credit / Akuisisi
        $acckredit = $coa_fixed_asset_id;
        $curBalanceK = $this->m_account->getCurrBalance($acckredit, $idunit);
        $newBalanceK = $curBalanceK  - $acquisition_price;

        $ditem = array(
            'idjournal' => $dispose_journal_id,
            'idaccount' => $acckredit,
            'debit' => 0,
            'credit' => $acquisition_price,
            'lastbalance' => $curBalanceK,
            'currbalance' => $newBalanceK
        );
        $this->db->insert('journalitem', $ditem);
        $this->m_account->saveNewBalance($acckredit, $newBalanceK, $idunit,$userin);
        $this->m_account->saveAccountLog($idunit,$acckredit,$acquisition_price,0,$date,$dispose_journal_id,$userin);

    }


    function delete($idjournal,$user_id){
        $q = $this->db->query("select idunit,idjournaltype
                                from journal
                                where idjournal = $idjournal");
        if($q->num_rows()>0){
            $r = $q->row();
            $idunit = $r->idunit;
            $idjournaltype = $r->idjournaltype;

            //coa debit
            $qdebit = $this->db->query("select idaccount,debit
                                    from journalitem
                                    where idjournal = $idjournal and credit = 0");
            foreach ($qdebit->result() as $r) {
               $curBalance = $this->m_account->getCurrBalance($r->idaccount, $idunit);

               if($idjournaltype==3 || $idjournaltype==6){
                    //Penjualan,Kas Masuk
                    $newBalance = $curBalance - $r->debit;
               } else {
                    $newBalance = $curBalance + $r->debit;
               }               

               $this->m_account->saveNewBalance($r->idaccount, $newBalance, $idunit,$user_id);
            }

            //coa credit
            $qdebit = $this->db->query("select idaccount,credit
                                    from journalitem
                                    where idjournal = $idjournal and debit = 0");
            foreach ($qdebit->result() as $r) {
               $curBalance = $this->m_account->getCurrBalance($r->idaccount, $idunit);

               if($idjournaltype==3 || $idjournaltype==6){
                    //Penjualan,Kas Masuk
                    $newBalance = $curBalance - $r->credit;
               } else {
                    //pengeluaran
                    $newBalance = $curBalance + $r->credit;
               }                

               $this->m_account->saveNewBalance($r->idaccount, $newBalance, $idunit,$user_id);
            }


            //delete journal item
            $this->db->where('idjournal',$idjournal);
            $this->db->delete('journalitem');

             //delete journal
            $this->db->where('idjournal',$idjournal);
            $this->db->delete('journal');

            return true;
        } else {
            return false;
        }

        
    }

    function receive_import_saving($idunit, $accdebet, $acckredit, $memo, $noref, $date, $amount,$taxReceive=0,$userin){
        $amount = intval($amount);
        $tgl = explode("-", $date);

        $idjournal = $this->m_data->getPrimaryID2(null,'journal','idjournal');

        $d = array(
            'idjournal' => $idjournal,
            'idjournaltype' => 6,
            'nojournal' => $noref,
//                    name character varying(225),
            'datejournal' => $date,
            'memo' => $memo,
            'totaldebit' => $amount,
            'totalcredit' => $amount,
//                    'totaltax' double precision,
//                    isrecuring boolean,
            'year' => $tgl[0],
            'month' => $tgl[1],
//                    display integer,
            'userin' => $userin,
            'usermod' =>$userin,
            'datein' => date('Y-m-d H:m:s'),
            'datemod' => date('Y-m-d H:m:s'),
            'idunit' => $idunit
        );
        $this->db->insert('journal', $d);

        //debit / Kas
        // $accdebet = ;
        $curBalanceD = $this->m_account->getCurrBalance($accdebet, $idunit);
        $newBalanceD = $curBalanceD - $amount;
        $ditem = array(
            'idjournal' => $idjournal,
            'idaccount' => $accdebet,
            'debit' => $amount,
            'credit' => 0,
            'lastbalance' => $curBalanceD,
            'currbalance' => $newBalanceD
        );
        $this->db->insert('journalitem', $ditem);
        $this->m_account->saveNewBalance($accdebet, $newBalanceD, $idunit,$userin);
        $this->m_account->saveAccountLog($idunit,$accdebet,0,$amount,$date,$idjournal,$userin);

        //credit / Simpanan
        // $acckredit = ;
        $curBalanceK = $this->m_account->getCurrBalance($acckredit, $idunit);
        $newBalanceK = $curBalanceK + $amount;

        $ditem = array(
            'idjournal' => $idjournal,
            'idaccount' => $acckredit,
            'debit' => 0,
            'credit' => $amount,
            'lastbalance' => $curBalanceK,
            'currbalance' => $newBalanceK
        );
        $this->db->insert('journalitem', $ditem);
        $this->m_account->saveNewBalance($acckredit, $newBalanceK, $idunit,$userin);
        $this->m_account->saveAccountLog($idunit,$acckredit,$amount,0,$date,$idjournal,$userin);

        if($taxReceive!=0){
            //pajak
            $idaccount = $this->m_data->getIdAccount(34, $idunit);
            $curBalanceK = $this->m_account->getCurrBalance($idaccount, $idunit);
            $newBalanceK = $curBalanceK + $taxReceive;
            $ditem = array(
                'idjournal' => $idjournal,
                'idaccount' => $idaccount,
                'debit' => 0,
                'credit' => $taxReceive,
                'lastbalance' => $curBalanceK,
                'currbalance' => $newBalanceK
            );
            $this->db->insert('journalitem', $ditem);
            $this->m_account->saveNewBalance($idaccount, $newBalanceK, $idunit,$userin);
            $this->m_account->saveAccountLog($idunit,$idaccount,$taxReceive,0,$date,$idjournal,$userin);
        }
      

        return $idjournal;
    }

}
?>