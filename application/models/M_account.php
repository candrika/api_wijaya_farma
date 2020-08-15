<?php

class M_account extends CI_Model {
    
    function getIdAccount($idlinked)
    {
        $q = $this->db->get_where('linkedacc',array('idlinked'=>$idlinked));
        if($q->num_rows()>0)
        {
            $r = $q->row();
            if($r->idaccount==null || $r->idaccount==null)
            {
                echo json_encode(array('success'=>false,'message'=>"Link akun <b>$r->namelinked</b> belum ditentukan<br><br> Pengaturan link akun:<br> Setup > Link Akun"));
                $q->free_result();
                exit;
            } else {
                return $r->idaccount;
            }
        } else {
            return false;
        }
    }
    
    function getCurrBalance($idaccount,$idunit)
    {
        //ambil saldo sekarang
        $q = $this->db->get_where('account',array('idaccount'=>$idaccount,'idunit'=>$idunit));
//         echo $this->db->last_query();
        if($q->num_rows()>0)
        {
            $r = $q->row();
            return $r->balance;
        } else {
            return 0;
        }
       
        $q->free_result();
    }
    
    function saveNewBalance($idaccount,$balance,$idunit,$userid)
    {
//        $this->db->select('idaccount');
//        $q = $this->db->get_where('linkedacc',array('idlinked'=>$idaccount))->row();
        $this->db->where('idunit',$idunit);
        $this->db->where('idaccount',$idaccount);
        $this->db->update('account',array(
               'balance'=>$balance
            ));
        
        //account history
        $tgl = explode('-',date('Y-m-d'));
        $d = array(
            'idaccount' =>$idaccount,
            'balance' => $balance,
            'day' => $tgl[2],
            'month'  => $tgl[1],
            'year' =>  $tgl[0],
            'datein' => date('Y-m-d H:m:s'),
            'userin' => $userid,
            'idunit'=> $idunit
        );
        $this->db->insert('accounthistory',$d);
//        $q->free_result();        
    }
    

    function saveAccountLog($idunit,$idaccount,$credit,$debit,$date,$idjournal=null,$userid)
    {
        $d = array(
                    "idaccount"=>$idaccount,
                    "credit" =>$credit,
                    "debit" =>$debit,
                    "tanggal" => $date,
                    "idjournal" => $idjournal,
                    "datein" => date('Y-m-d H:m:s'),
                    "userid" => $userid,
                    "idunit" => $idunit
            );

        $arrWer = array('idaccount'=>$idaccount, 'tanggal'=>$date, 'idjournal'=>$idjournal, 'userid'=>$userid, 'idunit'=>$idunit);
        $q = $this->db->get_where('accountlog',$arrWer);
        if($q->num_rows()>0)
        {
            $this->db->where($arrWer);
            $this->db->update('accountlog',$d);
        } else {
            $this->db->insert('accountlog',$d);
        }
        
    }

     function cekAccLink($idacclink,$idunit)
    {
         // $idaccountangkut = $this->m_data->getIdAccount(17, $idunit);
        $q = $this->db->get_where('linkedaccunit',array('idlinked'=>$idacclink,'idunit'=>$idunit));
        // echo $this->db->last_query();
        $qacc = $this->db->get_where('linkedacc',array('idlinked'=>$idacclink));
        $racc = $qacc->row(0);

        if($q->num_rows()>0)
        {
            $r = $q->row();
            if($r->idaccount==null)
            {
                 $json = array('success' => false, 'idaccount' => 0,'message'=>"Link akun <b>$racc->namelinked</b> belum ditentukan<br><br> Pengaturan link akun:<br> Setup > Link Akun");
            } else {
                $json = array('success' => true, 'idaccount' => $r->idaccount,'message'=>'');
            }
            
        }
        else {
            $json = array('success' => false, 'idaccount' => 0,'message'=>"Link akun <b>$racc->namelinked</b> belum ditentukan<br><br> Pengaturan link akun:<br> Setup > Link Akun");
        }

        return $json;
    }

    function updatelog($idjournal,$selisih,$tipe)
    {
        if($tipe=='minus')
        {
            $q = $this->db->get_where('accountlog',array('idjournal'=>$idjournal));
            foreach ($q->result() as $r) {
                $this->db->where('idaccount',$r->idaccount);
                $this->db->where('idjournal',$r->idjournal);
                $this->db->update('accountlog',array('credit'=>$r->credit-$selisih,'debit'=>$r->debit-$selisih));
            }
        } else {
            //plus
             $q = $this->db->get_where('accountlog',array('idjournal'=>$idjournal));
            foreach ($q->result() as $r) {
                $this->db->where('idaccount',$r->idaccount);
                $this->db->where('idjournal',$r->idjournal);
                $this->db->update('accountlog',array('credit'=>$r->credit+$selisih,'debit'=>$r->debit+$selisih));
            }
        }
    }

    function is_coa_tax_ap_exist($rate){
        $q = $this->db->query("select a.idtax,a.code,a.nametax,a.rate,a.coa_ap_id
                                        from tax a
                                        where a.idunit = ".$this->user_data->idunit."  and a.status = 1 and a.deleted = 0 and a.rate = ".$rate);
        if($q->num_rows()>0){
            $r = $q->row();
            if($r->coa_ap_id==null || $r->coa_ap_id==''){
                $msg = array('success'=>false,'message'=>'Akun hutang pada jenis pajak '.$r->nametax.' belum ditentukan');               
            } else {
                $msg = array('success'=>true,'nametax'=>$r->nametax,'rate'=>$r->rate,'coa_ap_id'=>$r->coa_ap_id);
            }
        } else {
            $msg = array('success'=>false,'message'=>'Pajak tidak ditentukan');
        }
        return $msg;
    }
   

}

?>