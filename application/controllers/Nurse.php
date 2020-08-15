<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Nurse extends MY_Controller {

    function __construct(){
        // Construct the parent class
        parent::__construct();

        $this->load->model('M_nurse','perawat');
        
        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        $this->methods['users_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['users_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['users_put']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['users_delete']['limit'] = 50; // 50 requests per hour per user/key
    }

    function datas_get(){

        if($this->get('group_id') == '' || $this->get('group_id') == null){

            $this->set_response(array('success'=>false,'message'=>'Sys group Undefined'),REST_Controller::HTTP_BAD_REQUEST);
            // return false;
        }

        $q = $this->perawat->datas($this->get('group_id'),$this->get('staff_id'),$this->get('query'));
        // echo $this->db->last_query();die;
        $d = $q['data']->result();
        $num_rows = $q['total']->result();
        
        $i=0;
        $data=[];

        foreach ($d as $key => $value) {
            # code...
            $data[$i] = $value;
            $i++;
        }

        $this->set_response(array('success'=>true,'numrows'=>$num_rows,'results'=>$num_rows,'rows'=>$data),REST_Controller::HTTP_OK);
    }

    function save_nurse_post(){

        $this->db->trans_begin();

        //data table staff as nurse
        $data_nurse=array(
            'group_id' =>4,      
            'staff_name' => $this->post('staff_name')!=''?$this->post('staff_name'):null,        
            'status' => $this->post('status')!=''?$this->post('status'):null,
            'staff_number'=> $this->post('staff_number')!=''?$this->post('staff_number'):null,
            'no_identity' => $this->post('no_identity')!=''?$this->post('no_identity'):null, 
            'staff_mobilephone' => $this->post('staff_mobilephone')!=''?$this->post('staff_mobilephone'):null,  
            'staff_whatsapp' => $this->post('staff_whatsapp')!=''?$this->post('staff_whatsapp'):null,     
            'staff_email' => $this->post('staff_email')!=''?$this->post('staff_email'):null,  
            'polytpe_id' => $this->post('polytpe_id')!=''?$this->post('polytpe_id'):null,      
            'location_id' => $this->post('location_id'),       
            'staff_address' => $this->post('staff_address')!=''?$this->post('staff_address'):null,              
            'bank_name' => $this->post('bank_name')!=''?$this->post('bank_name'):null,     
            'account_number' => $this->post('account_number')!=''?$this->post('account_number'):null,          
            'account_name' => $this->post('account_name')!=''?$this->post('account_name'):null
        );  

        
        //data table sys_user
        $sys_user = array(
            'email' => $this->post('staff_email'),  
            'password'=> $this->post('password'),
            'group_id' =>$data_nurse['group_id'],
            'realname' =>$this->post('staff_name'),
            'datein'=>date('Y-m-d H:m:s')
        );

        
        //data table poli type


        if($this->post('staff_id')!=''){

            if($this->check_email($sys_user['email'])){

            }else{
                $get   = $this->db->get_where('staff',array('staff_id'=>$this->post('staff_id'),'group_id'=>4));
                $r_get = $get->row();

                if($get->num_rows()>0){
                   $this->db->where('user_id',$r_get->{'user_id'});
                   $this->db->update('sys_user',array('email'=>$sys_user['email'],'username'=>$sys_user['username']));
                }
            }

            $this->db->where(array('staff_id'=>$this->post('staff_id'),'group_id'=>4));
            $this->db->update('staff',$data_nurse);

        }else{

            if($sys_user['password']==''){
               $this->set_response(array('success'=>false,'message'=>'Password tidak boleh kosong'),REST_Controller::HTTP_OK);
               return false;
        
            }

            if($this->check_email($sys_user['email'])){
               $this->set_response(array('success'=>false,'message'=>'Email sudah terdaftar, mohon gunakan email lainya'),REST_Controller::HTTP_OK);
               return false;
            }

            //insert ke sys_user
            $sys_user['user_id']=$this->m_data->getPrimaryID2(null,'sys_user', 'user_id'); 
            $this->db->insert('sys_user',$sys_user);

           //insert ke sys_user
            $sys_user['user_id']=$this->m_data->getPrimaryID2(null,'sys_user', 'user_id'); 
            $this->db->insert('sys_user',$sys_user);

            //insert ke location
            

            $data_nurse['staff_id'] = $this->m_data->getPrimaryID2($this->post('staff_id'),'staff', 'staff_id');
            $data_nurse['user_id'] = $sys_user['user_id'];

            $this->db->insert('staff',$data_nurse);

        }

        if($this->db->trans_status() === false){
            $this->db->trans_rollback();
            $this->set_response(array('success'=>false,'message'=>'Gagal melakukan penyimpanan data'),REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $this->db->trans_commit();
            $this->set_response(array('success'=>true,'message'=>'Penyimpanan data berhasil'),REST_Controller::HTTP_OK);
        }
    }

    private function check_email($email){
        $q = $this->db->get_where('sys_user',array('email'=>$email,'deleted'=>0));
        if($q->num_rows()>0){
            return true;
        } else {
            return false;
        }
    }

    function change_pass_post(){

        $staff_id = (int)$this->post('staff_id');
        $new_password = $this->post('new_password');
        $repeat_new_password = $this->post('repeat_new_password');

        if($staff_id <= 0){
            $this->set_response(array('success'=>false,'message'=>"Id can't be null"),REST_Controller::HTTP_OK);
            return false;
        }

        if($new_password != $repeat_new_password){
            $this->set_response(array('success'=>false,'message'=>"Kata kunci tidak sama"),REST_Controller::HTTP_OK);
            return false;   
        }

        $cek  = $this->db->get_where('staff',array('staff_id'=>$staff_id,'deleted'=>0,'group_id'=>4));
        $rcek = $cek->row();

        if($cek->num_rows()>0){
            $this->db->where('user_id',$rcek->{'user_id'});
            $this->db->update('sys_user',array('password'=>$repeat_new_password));

           if($this->db->affected_rows()>0){
                $message = [
                        'success' => true,
                        'message' => 'kata kunci berhasil diubah'
                ];
                $this->set_response($message, REST_Controller::HTTP_OK); 
                return true;
            } else {
                $message = [
                    'success' => false,
                    'message' => 'kata kunci gagal diubah'
                ];
            $this->set_response($message, REST_Controller::HTTP_BAD_REQUEST); 
            return false;
            }

        }else{
            $this->set_response(array('success'=>false,'message'=>"Data tidak ditemukan"),REST_Controller::HTTP_OK);
            return false;   
        }
    }

    function data_get(){

        if($this->get('group_id') == '' || $this->get('group_id') == null){

            $this->set_response(array('success'=>false,'message'=>'Sys group Undefined'),REST_Controller::HTTP_BAD_REQUEST);
            // return false;
        }

        $q = $this->perawat->datas($this->get('group_id'),$this->get('staff_id'),$this->get('query'));

        $d = $q['data']->row();
        
        $this->set_response(array('success'=>true,'rows'=>$d),REST_Controller::HTTP_OK);
    }

    function delete_post(){
        $this->db->trans_begin();
        
        $postdata = json_decode($this->post('postdata'));

        foreach ($postdata as $id) {
            $q = $this->db->get_where('staff',array('staff_id'=>$this->post('staff_id'),'group_id'=>4))->result_array();

            foreach ($q as $key => $value) {
                # code...
                $this->db->where(array('user_id'=>$value['user_id']));
                $this->db->update('sys_user',array('deleted'=>1));
            }
            
            $this->db->where(array('staff_id'=>$id,'group_id'=>4));
            $this->db->update('staff',array('deleted'=>1));
            
        }

        if($this->db->trans_status() === false){
            $this->db->trans_rollback();
            $this->set_response(array('success'=>false,'message'=>'Gagal melakukan penghapusan data'),REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $this->db->trans_commit();
            $this->set_response(array('success'=>true,'message'=>'Penghapusan data berhasil'),REST_Controller::HTTP_OK);
        }
    }

    private function insert_location($location_id){

        //data table location
        $location_id =array(
            "location_id" =>$location_id,
            // "location_name" =>,
            "deleted" =>0,
        );

        $this->db->insert('location',$location);
    }

    function photo_get(){

        $staff_id = $this->put('id');
        $group_id  = $this->get('group_id');

        //start query
        $wer = "deleted=0";

        if($group_id !=null){
            $wer .= " and group_id=$group_id";
        }

        if($staff_id !=null){
            $wer .= " and staff_id=$staff_id";
        }

        $d = $this->db->query("SELECT staff_photo FROM staff WHERE $wer");
        $q = $d->row();
        //end

        $this->set_response(array('success'=>true,'rows'=>$q),REST_Controller::HTTP_OK);

    }

    function nurseID_get(){

        $params = array(
            'idunit' => $this->get('idunit'),
            'prefix' => 'NRS',
            'table' => 'staff',
            'fieldpk' => 'staff_id',
            'fieldname' => 'staff_number',
            'extraparams'=> 'and group_id=4 and deleted=0',
        );
        
        $doctor_no = $this->m_data->getNextNoArticle($params);

        $this->set_response(array('success'=>true,'number'=>$doctor_no),REST_Controller::HTTP_OK);
    }
}
?>
