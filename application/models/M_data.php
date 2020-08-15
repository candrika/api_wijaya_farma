<?php

class M_data extends CI_Model {

    function getPrimaryID($post_id_data,$table, $kolom, $idunit=null)
    {
        if($post_id_data==null)
        {
           
            if($idunit==null){
                 $q = $this->db->query("select max($kolom) as id from $table");
            } else {
                 $q = $this->db->query("select max($kolom) as id from $table where idunit = $idunit");
            }
             // echo $this->db->last_query(); 
            if($q->num_rows()>0)
            {
                $r = $q->row();
                return $r->id+1;
            } else {
                return 1;
            }
        } else {
            //edit
            return $post_id_data;
        }
       
    }

    function getPrimaryID2($post_id_data,$table, $kolom)
    {
        if($post_id_data==null)
        {
            //input baru
             $q = $this->db->query("select max($kolom) as id from $table");
            if($q->num_rows()>0)
            {
                $r = $q->row();
                return $r->id+1;
            } else {
                return 1;
            }
        } else {
            //edit
            return $post_id_data;
        }
       
    }

    function getID($table, $kolom, $vkolom, $id) {
        if ($id == '0') {
            return null;
        } else {
            $q = $this->db->get_where($table, array($kolom => $id));
            echo $this->db->last_query();
            if ($q->num_rows() > 0) {
                $r = $q->row();
                return $r->$vkolom;
            } else {
                return null;
            }
        }
        $q->free_result();
    }
    
    function getSeqVal($nameSeq)
    {
        $q = $this->db->query("select nextval('".$nameSeq."') as id")->row();
        return $q->id;
        $q->free_result();
    }
    
    function getIdAccount($idlinked,$idunit)
    {
        
        //ambil idaccount dari tabel linkedaccunit
        // $q = $this->db->get_where('linkedaccunit',array('idlinked'=>$idlinked,'idunit'=>$idunit));
        $q = $this->db->query("SELECT a.*
                                FROM linkedaccunit a
                                join account b oN a.idaccount = b.idaccount and b.idunit = ".$idunit."
                                WHERE a.idlinked = ".$idlinked." AND a.idunit =  ".$idunit." ");
       // echo $this->db->last_query();
        $qacc = $this->db->get_where('linkedacc',array('idlinked'=>$idlinked));
        $racc = $qacc->row(0);

        if($q->num_rows()>0)
        {
            $r = $q->row();
           if($r->idaccount==null)
           {
               echo json_encode(array('success'=>false,'message'=>"Link akun <b>$racc->namelinked</b> belum ditentukan<br><br> Menu pengaturan link akun:<br> Keuangan > Pengaturan > Pemetaan Akun"));
               $q->free_result();
               exit;
           } else {
                return $r->idaccount;
           }
        } else {
             
             echo json_encode(array('success'=>false,'message'=>"Link akun <b>$racc->namelinked</b> belum ditentukan<br><br> Menu pengaturan link akun:<br> Keuangan > Pengaturan > Pemetaan Akun"));
             $q->free_result();
             exit;
        }
    }
    
    function getCurrBalance($idaccount)
    {
        $q = $this->db->get_where('account',array('idaccount'=>$idaccount));
        if($q->num_rows()>0)
        {
            $r = $r->row();
            return $r->balance;
        } else {
            return false;
        }
        $q->free_result();
    }

     function insertTaxHistory($idtax,$taxval,$rate,$datein,$idjournal,$type)
    {
        $d = array(
                "idtax" =>$idtax,
                "taxval" =>$taxval,
                "rate" =>$rate,
                "datein" =>$datein,
                "idjournal" =>$idjournal,
                "type"=>$type
            );
        $this->db->insert('taxhistory',$d);
    }

    function getMeasurement($short_desc,$idunit){
        $qmeasurement = $this->db->query("select measurement_id from productmeasurement where short_desc = '".$short_desc."' and idunit = ".$idunit." ");
            if($qmeasurement->num_rows()>0)
            {
                $rMeasurement = $qmeasurement->row();
                $measure = $rMeasurement->measurement_id;
            } else {
                $measure = null;
            }

            return intval($measure);
    }
    
    function getIDmaster($value_field,$value,$primary_key,$table,$idunit){

        if($value=='' || $value==null){
            return null;
        } 

        $q = $this->db->query("select $primary_key from $table where $value_field = '".$value."' and idunit = ".$idunit." ");
            if($q->num_rows()>0)
            {
                $r = $q->row();
                $v = $r->$primary_key;
            } else {
                $v = null;
            }
            return intval($v);
    }

    function getIdTax($rate){
        //mendapatkan idtax berdasarkan rate(%)
        $q = $this->db->query("select idtax
                                from tax
                                where rate = $rate")->row();
        return $q->idtax;
    }

    function get_idunit($uid){
        $q = $this->db->query("select idunit
                                from sys_user
                                where user_id = $uid")->row();
        return $q->idunit;
    }

    function dataunit($idunit){
        $dtunit = array();
        $qunit = $this->db->get_where('unit',array('idunit'=>$idunit));
        if($qunit->num_rows()>0)
        {
            $runit = $qunit->row();
            $dtunit['logo'] = $runit->logo==null ? 'logo_aktiva2.png' : $runit->logo;
            $dtunit['namaunit'] = $runit->namaunit;
            $dtunit['alamat'] = $runit->alamat;
            $dtunit['alamat3'] = $runit->alamat3;
            $dtunit['telp'] = $runit->telp;
            $dtunit['fax'] = $runit->fax;
        } else {

        }

        return $dtunit;
    }
   
    function getNextCode($params){ // << kalau request dari php pakai ini
        $nextval = 0;
        $digit = $params['digit'];
        $prefix = $params['prefix'];
        $fieldpk = $params['fieldpk'];
        $fieldname = $params['fieldname'];
        $table = $params['table'];
        $extraparams = $params['extraparams'];
        $idunit = $params['idunit'];
            
        $wer = null;

        $f_unit = $this->is_field_exists($table,'idunit');
        if($f_unit){
             $wer.= " and idunit = $idunit";
        }

        $f_display = $this->is_field_exists($table,'display');
        if($f_display){
            $wer.= " and display is null";
        }

        $f_deleted = $this->is_field_exists($table,'deleted');
        if($f_deleted){
            $wer.= " and deleted = 0";
        }

        $sql = "select $fieldname 
                from $table where true $wer and $fieldname is not null
                $extraparams
                order by $fieldpk desc
                limit 1";
            // echo $sql;
        $q = $this->db->query($sql);
        if($q->num_rows() > 0)
            $nextval = (int) str_replace($prefix, '', $q->row()->$fieldname);
        
        // echo $nextval;
        if($nextval == 999)
            $digit = 4; 

        $nextval += 1;
        $nextval = sprintf("%0".$digit."d", $nextval);

        $q->free_result(); //relese memory
        return $prefix.$nextval;
    }

    function getNextNoArticle($params){ // << kalau request dari php pakai ini
        $nextval = 0;
        $digit = 4;
        $prefix = $params['prefix'];
        $fieldpk = $params['fieldpk'];
        $fieldname = $params['fieldname'];
        $table = $params['table'];
        $extraparams = $params['extraparams'];
        $idunit = $params['idunit'];
        $orderby = isset($params['orderby']) ? $params['orderby'] : $fieldpk;
        $y = isset($params['year']) ? $params['year'] : null;
        $m = isset($params['month']) ? $params['month'] : null;
        
        $wer = null;

        if($y==null){
            $y = date('y');
        }

        if($m==null){
            $m = date('m');
        }
        
       $f_unit = $this->is_field_exists($table,'idunit');
        if($f_unit){
             $wer.= " and idunit = $idunit";
        }

        $f_display = $this->is_field_exists($table,'display');
        if($f_display){
            $wer.= " and display is null";
        }

        $f_deleted = $this->is_field_exists($table,'deleted');
        if($f_deleted){
            $wer.= " and deleted = 0";
        }

        $sql = "select $fieldname 
                from $table where true $wer
                $extraparams and $fieldname like '%$y$m%' 
                order by $orderby desc
                limit 1";


       

        $q = $this->db->query($sql);
        if($q->num_rows() > 0)
            $nextval = (int) str_replace($prefix.$y.$m, '', $q->row()->$fieldname);
        // echo '-nextval:'.$nextval.'-';
        if($nextval == 999)
            $digit = 4; 

        $nextval += 1;
        $nextval = sprintf("%0".$digit."d", $nextval);

        $q->free_result(); //relese memory
        return $prefix.$y.$m.$nextval;
    }

    function auth_check($apikey){
        if($apikey=='17091945'){
            //17091945 -> from internal source
            return true;
        } else {

            $decode =base64_decode($apikey);
            $arr = explode(':',$decode);
            // echo $arr;
            $username = $arr[0];
            $password = $arr[1];

            $q = $this->db->get_where('sys_user',array('username'=>$username,'password'=>$password));
            if($q->num_rows()>0){
                $r = $q->row();
                // $this->m_user->cekUser($apikey,$password, $uset=false);
             
                return $r; 
            } else {
                return false;
            }
        }
        
    }

    function get_saldo(){
        $q = $this->db->get('balance');
        $data=[];
        $balance_current=0;
        
        if($q->num_rows() >0){

            foreach ($q->re as $key => $value) {
                # code...
                $balance_current=$value->balance_current;

                $data=  array(
                    'status' => true,
                    'saldo' =>$balance_current
                );
            }

            return $data;

        }else{

            $data=  array(
                    'status' => true,
                    'saldo' =>$balance_current
                );

            return $data;     
        }
    }

    function tax_config(){
        $qcek = $this->db->query("select default_tax_sales_id,default_tax_purchase_id,b.coa_ppn_sales_id,b.coa_pph23_sales_id,b.coa_ppn_purchase_id,b.coa_pph23_purchase_id,
                                    b.nametax as nametax_sales,
                                    b.coa_ppn_rate as ppn_rate_sales,
                                    b.coa_pph23_rate as pph23_rate_sales,
                                    c.nametax as nametax_purchase,
                                    c.coa_ppn_rate as ppn_rate_purchase,
                                    c.coa_pph23_rate as pph23_rate_purchase
                                    from unit a
                                    join tax b ON a.default_tax_sales_id = b.idtax
                                    join tax c ON a.default_tax_purchase_id = c.idtax
                                    left join account d ON b.coa_ppn_sales_id = d.idaccount and d.idunit = a.idunit
                                    left join account e ON b.coa_pph23_sales_id = e.idaccount and e.idunit = a.idunit
                                    where a.idunit = ".$this->user_data->idunit." and b.deleted = 0");
        if($qcek->num_rows()<=0){
            $message = [
                'success' => false,
                'message'=>'tax sales not defined'
            ];
        } else {
            $r = $qcek->row();
            $message = [
                'success' => true,
                'tax_sales_id'=>(int) $r->default_tax_sales_id,
                'tax_sales_name'=>$r->nametax_sales,
                'tax_sales_rate'=>$r->ppn_rate_sales+$r->pph23_rate_sales,
                'tax_purchase_id'=>(int) $r->default_tax_purchase_id,
                'tax_purchase_name'=>$r->nametax_purchase,
                'tax_purchase_rate'=>$r->ppn_rate_purchase+$r->pph23_rate_purchase
            ];
            
        }
        return $message;
    }


    function is_field_exists($table,$fieldname){
         $qidunit = $this->db->query("SELECT column_name 
                                            FROM information_schema.columns 
                                            WHERE table_name='".$table."' and column_name='".$fieldname."'")->row();

        if(isset($qidunit->column_name))
        {
            if($qidunit->column_name==$fieldname)
            {   
                return true;
            }
        } else {
            return false;
        }
    }

    function get_business_id($user_id){
        $q = $this->db->query("select business_id
                                from employee a
                                where a.user_id = $user_id");
        if($q->num_rows()>0){
            $r = $q->row();
            return $r->business_id;
        } else {    
            return null;
        }
    }

    function get_nusafin_key($unit_id){
        $q = $this->db->query("select api_key_live,api_key_dev
                                from nusafin_key
                                where unit_id = $unit_id");
        if($q->num_rows()>0){
            $r = $q->row();
            if($r->api_key_live=='' || $r->api_key_dev==''){
                 return false;
            } else {
                return array(
                    'api_key_live'=>$r->api_key_live,
                    'api_key_dev'=>$r->api_key_dev
                );
            }
        } else {
            return false;
        }
    }
}

?>