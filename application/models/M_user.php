<?php

class M_user extends CI_Model {

function cekUser($id, $pass, $uset=false) {

        $q = $this->db->query("select a.*,b.idemployee,a.idunit,b.firstname,c.group_name,a.api_key,
                                a.email,a.password,a.api_key
                            from sys_user a
                            join employee b on b.user_id=a.user_id
                            join sys_group c ON a.group_id = c.group_id
                            where (a.username = '".$id."' or a.email='".$id."') and a.password = '".$pass."' and a.deleted=0 and a.display is null");
        
        if ($q->num_rows() > 0) {
            $r = $q->row();
            // print_r($r);
           
            $idunit = $r->idunit == null ? $r->idunit_employee : $r->idunit;
            $realname = $r->realname != null ? $r->realname:''; 
            
            $qunit = false;
            $qunit = $this->db->get_where('unit',array('idunit'=>$idunit))->row(); 
            $unit = $qunit->namaunit;
            $periode =  null;
            $curfinanceyear = null;
            $conversionmonth = null;
            
            $dataSession = array(
                'userid' => $r->user_id,
                'api_key' => $r->api_key,
                'idcompany' => $r->idcompany,
                'clientid' => $r->clientid,
                'username' => $r->email,
                'password' => $r->password,
                'realname' => $realname,
                'group_id' => $r->group_id,
                'usergroup' => $r->group_name,
                'unit'=> $unit,
                'idunit'=> 12,
                'logged' => true
            );

            $dataSession['conversionmonth'] = $conversionmonth;
            $dataSession['curfinanceyear'] = $curfinanceyear;
            $dataSession['periode'] = $periode;
         
            $this->db->where('user_id', $r->user_id);
            $this->db->update('sys_user', array('laslogin' => date('Y-m-d H:m:s')));
            return array('success' => true,'msg' => '', 'data'=>$dataSession);
        } else {
           return array('success' => false, 'message' => 'ID atau Password Salah');
        }
    }

    function get_account($api_key){
        $q = $this->db->query("select user_id,idunit
                        from sys_user 
                        where api_key = '".$api_key."'");
        if($q->num_rows()>0){
            $r = $q->row();
            return array(
                    'user_id'=>$r->user_id,
                    'idunit'=>$r->idunit
            );
        } else {
            return false;
        }
    }

    function sys_group($idunit,$group_id=null){

        $wer = "";

        if($idunit!=null){
            $wer = " and a.idunit = ".$idunit." ";
        }

        if($group_id!=null){
            $wer = " and a.group_id=$group_id";
        }

        $sql = "SELECT group_id,
                       group_name,
                       userin,
                       datein,
                       description
                FROM    
                        sys_group a
                WHERE 
                        a.deleted = 0 $wer";

        $q = $this->db->query($sql);                
                        
        return $q;                
    }

}
?>