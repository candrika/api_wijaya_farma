<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Docter extends MY_Controller {

    function __construct(){
        // Construct the parent class
        parent::__construct();

        $this->load->model('M_docter','dokter');
        
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

        $q = $this->dokter->datas($this->get('group_id'),$this->get('staff_id'),$this->get('query'));
        
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

    function save_docter_post(){

        $this->db->trans_begin();

        //data table staff as nurse
        $data_docter=array(
            'group_id' =>5,      
            'staff_name' => $this->post('staff_name')!=''?$this->post('staff_name'):null,        
            'staff_type_id' => $this->post('staff_type_id')!=''?$this->post('staff_type_id'):null,        
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
            'account_name' => $this->post('account_name')!=''?$this->post('account_name'):null,
            'fee_per_patient' => str_replace('.', '', $this->post('fee_per_patient'))!=''?str_replace('.', '', $this->post('fee_per_patient')):null
        );  

        // print_r($data_docter);

        //data table sys_user
        $sys_user = array(
            'username' => $this->post('staff_email'),  
            'email' => $this->post('staff_email'),  
            'password'=> $this->post('password'),
            'group_id' =>$data_docter['group_id'],
            'realname' =>$this->post('staff_name'),
            'datein'=>date('Y-m-d H:m:s')
        );

        
        if($this->post('staff_id')!=''){

            if($this->check_email($sys_user['email'])){

            }else{
                $get   = $this->db->get_where('staff',array('staff_id'=>$this->post('staff_id'),'group_id'=>5));
                $r_get = $get->row();

                if($get->num_rows()>0){
                   $this->db->where('user_id',$r_get->{'user_id'});
                   $this->db->update('sys_user',array('email'=>$sys_user['email'],'username'=>$sys_user['username'])); 
                }
            }

            $this->db->where(array('staff_id'=>$this->post('staff_id'),'group_id'=>5));
            $this->db->update('staff',$data_docter);

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

            $data_docter['staff_id'] = $this->m_data->getPrimaryID2($this->post('staff_id'),'staff', 'staff_id');
            $data_docter['user_id'] = $sys_user['user_id'];

            $this->db->insert('staff',$data_docter);

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

        $cek  = $this->db->get_where('staff',array('staff_id'=>$staff_id,'deleted'=>0,'group_id'=>5));
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

        $q = $this->dokter->datas($this->get('group_id'),$this->get('staff_id'),$this->get('query'));
        
        $d = $q['data']->row();
        
        $this->set_response(array('success'=>true,'rows'=>$d),REST_Controller::HTTP_OK);
    }

    function delete_post(){
        $this->db->trans_begin();
        
        $postdata = json_decode($this->post('postdata'));

        foreach ($postdata as $id) {
            $q = $this->db->get_where('staff',array('staff_id'=>$id,'group_id'=>5))->result_array();
            print_r($q);
            foreach ($q as $key => $value) {
                # code...
                // print_r($q);
                $this->db->where(array('user_id'=>$value['user_id']));
                $this->db->update('sys_user',array('deleted'=>1));
            }
            
            $this->db->where(array('staff_id'=>$id,'group_id'=>5));
            $this->db->update('staff',array('deleted'=>1));
            
        }

        if($this->db->trans_status() === false){
            $this->db->trans_rollback();
            $this->set_response(array('success'=>false,'message'=>'Gagal melakukan penghapusan data'),REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $this->db->trans_commit();
            $this->set_response(array('success'=>false,'message'=>'Penghapusan data berhasil'),REST_Controller::HTTP_OK);
        }
    }

    function schedule_datas_get(){

        $d = $this->dokter->doctor_schedule($this->get('docter_id'),$this->get('schedule_id'),$this->get('query'));
        // echo $this->db->last_query();
        $data=array();

        $i = 0;

        foreach ($d->result() as $key => $value) {
            
            $value->{'Shift_1'} = $value->{'timesheet_1_start'}.'-'.$value->{'timesheet_1_end'};
            $value->{'Shift_2'} = $value->{'timesheet_2_start'}.'-'.$value->{'timesheet_2_end'};
            $value->{'Shift_3'} = $value->{'timesheet_3_start'}.'-'.$value->{'timesheet_3_end'};
            $value->{'Shift_4'} = $value->{'timesheet_4_start'}.'-'.$value->{'timesheet_4_end'};

            $data[$i] = $value;
            $i++;
        }

        $this->set_response(array('success'=>true,'results'=>count($data),'num_rows'=>count($data),'rows'=>$data),REST_Controller::HTTP_OK);
    }

    function schedule_data_get(){

        $d = $this->dokter->doctor_schedule($this->get('doctor_id'),$this->get('schedule_id'),$this->get('query'))->row();

        $this->set_response(array('success'=>true,'rows'=>$d),REST_Controller::HTTP_OK);
    }

    function day_name_get(){

        $q = $this->db->get('day_name')->result();

        $this->set_response(array('success'=>true,'rows'=>$q),REST_Controller::HTTP_OK);
    }

    function save_schedule_post(){

        $this->db->trans_begin();

        $data_schedule = array(
            "doctor_id" => $this->post('doctor_id'),
            "day_id" => $this->post('day_id'),
            "timesheet_1_start" => $this->post('timesheet_1_start'),
            "timesheet_1_end" => addition_time($this->post('timesheet_1_start'),4),
            "timesheet_2_start" => $this->post('timesheet_2_start'),
            "timesheet_2_end" => addition_time($this->post('timesheet_2_start'),5),
            "timesheet_3_start" => $this->post('timesheet_3_start'),
            "timesheet_3_end" => addition_time($this->post('timesheet_3_start'),5),
            "timesheet_4_start" => $this->post('timesheet_4_start'),
            "timesheet_4_end" => addition_time($this->post('timesheet_4_start'),5),
            "status" => $this->post('status'),
        );

        if($this->post('schedule_id') == ''){
            $data_schedule['schedule_id'] = $this->m_data->getPrimaryID2($this->post('schedule_id'),'doctor_schedule','schedule_id');
            $this->db->insert('doctor_schedule',$data_schedule);
        }else{
            $data_schedule['schedule_id'] = $this->post('schedule_id');
            
            $this->db->where('schedule_id',$data_schedule['schedule_id']);
            $this->db->update('doctor_schedule',$data_schedule);

        }

        if($this->db->trans_status() === false){
            $this->db->trans_rollback();
            $this->set_response(array('success'=>false,'message'=>'Gagal melakukan penyimpanan data'),REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $this->db->trans_commit();
            $this->set_response(array('success'=>true,'message'=>'Penyimpanan data berhasil'),REST_Controller::HTTP_OK);
        }
    }

    function delete_schedule_post(){

        $this->db->trans_begin();

        $postdata = json_decode($this->post('postdata'));

        foreach ($postdata as $id) {
            
            $this->db->where('schedule_id',$id);
            $this->db->delete('doctor_schedule');
        }

        if($this->db->trans_status() === false){
            $this->db->trans_rollback();
            $this->set_response(array('success'=>false,'message'=>'Gagal melakukan penyimpanan data'),REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $this->db->trans_commit();
            $this->set_response(array('success'=>true,'message'=>'Penyimpanan data berhasil'),REST_Controller::HTTP_OK);
        }    
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

    function doctorID_get(){

        $params = array(
            'idunit' => $this->get('idunit'),
            'prefix' => 'DR',
            'table' => 'staff',
            'fieldpk' => 'staff_id',
            'fieldname' => 'staff_number',
            'extraparams'=> 'and group_id=5 and deleted=0',
        );
        
        $doctor_no = $this->m_data->getNextNoArticle($params);

        $this->set_response(array('success'=>true,'number'=>$doctor_no),REST_Controller::HTTP_OK);
    }

    function medical_disease_get(){

        if($this->get('medical_record_id')==''){
            $this->set_response(array('success'=>false,'message'=>'medical record id not found'),REST_Controller::HTTP_BAD_REQUEST);
        }

        $q = $this->dokter->data_icd($this->get('medical_record_id'),$this->get('disease_id'));

        $result = $q->result();
        
        $data = [];
        $i    = 0;

        foreach ($result as $key => $v) {
            $data[$i] = $v;
            $i++;
        }

        $this->set_response(array('success'=>true,'results'=>count($data),'num_rows'=>count($data),'rows'=>$data),REST_Controller::HTTP_OK);
    }

    function medical_action_get(){

        if($this->get('medical_record_id')==''){
            $this->set_response(array('success'=>false,'message'=>'medical record id not found'),REST_Controller::HTTP_BAD_REQUEST);
        }

        $q = $this->dokter->data_action($this->get('medical_record_id'),$this->get('action_id'));

        $result = $q->result();
        
        $data = [];
        $i    = 0;

        foreach ($result as $key => $v) {
            $data[$i] = $v;
            $i++;
        }

        $this->set_response(array('success'=>true,'results'=>count($data),'num_rows'=>count($data),'rows'=>$data),REST_Controller::HTTP_OK);
    }

    function medical_drug_get(){

        if($this->get('medical_record_id')==''){
            $this->set_response(array('success'=>false,'message'=>'medical record id not found'),REST_Controller::HTTP_BAD_REQUEST);
        }

        $q = $this->dokter->medical_record_drug($this->get('medical_record_id'),$this->get('product_id'),$this->get('business_id'));
        // echo $this->db->last_query();
        $result = $q->result();
        
        $data = [];
        $i    = 0;

        foreach ($result as $key => $v) {
            $data[$i] = $v;
            $i++;
        }

        $this->set_response(array('success'=>true,'results'=>count($data),'num_rows'=>count($data),'rows'=>$data),REST_Controller::HTTP_OK);
    }

    function medical_record_get(){

        if($this->get('patient_id') ==''){
            $this->set_response(array('success'=>false,'message'=>'patient id not found'),REST_Controller::HTTP_BAD_REQUEST);
        }

        if($this->get('doctor_id')==''){
            $this->set_response(array('success'=>false,'message'=>'doctor id not found'),REST_Controller::HTTP_BAD_REQUEST); 
        }

        $q = $this->dokter->data_medical_record($this->get('medical_record_id'),$this->get('patient_id'),$this->get('doctor_id'),$this->get('query'),$this->get('startdate'),$this->get('enddate'),$this->get('medicine_status'));
        
        $result = $q->result();

        $data = [];
        $i    = 0;

        foreach ($result as $key => $v) {
            
            if(isset($v->{'due_date'})){
                $v->{'due_date'} = backdate2($v->{'due_date'});
            }

            $qp1 = $this->db->query("SELECT 
                                            a.patient_id,a.patient_name,b.patient_name,a.member_id,b.business_id,c.business_name
                                     FROM 
                                            patient a 
                                            join patient b on a.patient_parent_id=b.patient_id 
                                            join business c on c.business_id=b.business_id 
                                     where 
                                            TRUE and a.patient_parent_id !=0 and a.patient_id=".$v->{'patient_id'});
            if($qp1->num_rows()>0){

                $rqp1 = $qp1->row();
                $v->{'member_name'} = $rqp1->{'patient_name'};
                $v->{'business_name'} = $rqp1->{'business_name'};
            }else{

                if($v->{'member_id'}!=''){
                    $v->{'member_name'} = $v->{'patient_name'};
                }
            }
            
            if($v->{'member_id'}!=''){
                $v->{'no_member'} = $v->{'np_number'};
            }

            $data[$i] = $v;
            $i++;
        }

        $this->set_response(array('success'=>true,'results'=>count($data),'num_rows'=>count($data),'rows'=>$data),REST_Controller::HTTP_OK);
    }

    function medical_record_post(){
        $this->db->trans_begin();

        $medical_record_no   = $this->post('medical_record_no')!= ''?$this->post('medical_record_no'):null;
        $patient_id          = $this->post('patient_id')!= ''? $this->post('patient_id'):null;
        $doctor_id           = $this->post('doctor_id')!= ''? $this->post('doctor_id'):null;
        $member_id           = $this->post('member_id')!= ''? $this->post('member_id'):null;
        $benefit_id_type     = $this->post('benefit_id_type')!= ''?$this->post('benefit_id_type'):null;
        $patient_type        = $this->post('patient_type')!= ''?$this->post('patient_type'):null;
        $notes               = $this->post('medical_record_desc')!= ''? $this->post('medical_record_desc'):null;
        $medical_record_date = str_replace('T00:00:00', '', $this->post('medical_record_date'));
        // $service_amount     = str_replace(',','',$this->post('service_amount'))!= ''? str_replace(',','',$this->post('service_amount')):null;

        if($this->post('sales_id')!=''){

            $sales_id = $this->post('sales_id');   
            
        }else{
            
            $sales_id = $this->m_data->getPrimaryID2(null,'sales','idsales');
        }

        $disease    = json_decode($this->post('json_disease'));
        $drug       = json_decode($this->post('json_drug'));
        $action     = json_decode($this->post('json_action'));
        $drug_alkes = json_decode($this->post('json_drug_alkes'));

        //no resep
        $params = array(
            'idunit' => 12,
            'prefix' => 'RS',
            'table' => 'medical_record',
            'fieldpk' => 'medical_record_id',
            'fieldname' => 'receipt_number',
            'extraparams'=> null,
        );
        
        $receipt_number = $this->m_data->getNextNoArticle($params);
        
        $params = array(
            'idunit' => 12,
            'prefix' => 'RECMED',
            'table' => 'medical_record',
            'fieldpk' => 'medical_record_id',
            'fieldname' => 'medical_record_no',
            'extraparams'=> null,
        );
        $medical_record_no = $this->m_data->getNextNoArticle($params);
        
        //get fee service 
        $qfee = $this->db->get_where('staff',array('staff_id'=>$doctor_id,'group_id'=>5))->row();
       
        $data_medical_record=array(
            "doctor_id" =>$doctor_id,
            "patient_id" =>$patient_id,
            "medical_record_desc" =>$notes,
            "payment_method" => null,
            "medical_record_date" =>$medical_record_date,
            "medical_status" =>1,
            "payment_status" =>1,
            "medicine_status" =>1,
            "medical_record_desc"=>$notes,
            "receipt_number"=>$receipt_number,
            "sales_id"=>$sales_id,
            "service_amount" =>$qfee->{'fee_per_patient'},
            "subtotal" =>$qfee->{'fee_per_patient'},
        );
        
        // print_r($data_medical_record);die;

        if($this->post('medical_record_id') !=''){
            $data_medical_record['medical_record_id']=$this->post('medical_record_id');

            $data_medical_record['usermod'] = 11; 
            $data_medical_record['datemod'] = date('Y-m-d H:m:s');

            $this->db->where('medical_record_id',$this->post('medical_record_id'));
            $this->db->update('medical_record',$data_medical_record);
            
        }else{
            $data_medical_record['medical_record_id']  = $this->m_data->getPrimaryID2($this->post('medical_record_id'),'medical_record','medical_record_id');
            
            $data_medical_record["medical_record_no"] = $medical_record_no;
            $data_medical_record["receipt_number"] = $receipt_number;
            
            $data_medical_record['userin'] = 11; 
            $data_medical_record['datein'] = date('Y-m-d H:m:s'); 

            $this->db->insert('medical_record',$data_medical_record);
            
        }

        //save benefirt type ke tabel pasien
        $this->db->where('patient_id',$patient_id);
        $this->db->update('patient',array('benefit_id_type'=>$benefit_id_type));

        $this->save_disease($disease,$data_medical_record['medical_record_id']);
        $this->save_drug($drug,$data_medical_record['medical_record_id']);
        $this->save_action($action,$data_medical_record['medical_record_id']);
        $this->save_drug($drug_alkes,$data_medical_record['medical_record_id']);
        $this->save_invoice($patient_type,$patient_id,$member_id,$medical_record_date,$sales_id,$notes,$drug,$drug_alkes,$action,$data_medical_record['service_amount']);


        if($this->db->trans_status() === false){
            $this->db->trans_rollback();
            $this->set_response(array('success'=>false,'message'=>'Gagal melakukan penyimpanan data'),REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $this->db->trans_commit();
            $this->set_response(array('success'=>true,'message'=>'Penyimpanan data berhasil'),REST_Controller::HTTP_OK);
        }
    }

    function save_disease($disease,$medical_record_id){

        foreach ($disease as $key => $v) {
            # code...
            $cek = $this->db->get_where('medical_record_disease',array('medical_record_id'=>$medical_record_id,'disease_id'=>$v->{'disease_id'}));
            // print_r($v);
            // die;
            $data_disease = array(
                "medical_record_id" =>$medical_record_id,
                "disease_id" => $v->{'disease_id'},
                "deleted"=>0
            );

            if($cek->num_rows() > 0){
               $this->db->where(array('medical_record_id'=>$medical_record_id,'disease_id'=>$v->{'disease_id'}));
               $this->db->update('medical_record_disease',$data_disease);
            }else{
               $this->db->insert('medical_record_disease',$data_disease);
            }
        }
    }

    function delete_disease_post(){
        $this->db->trans_begin();
        
        if($this->post('medical_record_id')!=''){
           $medical_record_id = $this->post('medical_record_id');
        }else{
           $medical_record_id=null;
        }
        
        $disease_id = $this->post('disease_id');

        $cek = $this->db->get_where('medical_record_disease',array('medical_record_id'=>$medical_record_id,'disease_id'=>$disease_id)); 

        if($cek->num_rows() >0){
            $this->db->where(array('medical_record_id'=>$medical_record_id,'disease_id'=>$disease_id));
            $this->db->delete('medical_record_disease');
        }else{
            $this->set_response(array('success'=>true,'message'=>'Hapus data berhasil'),REST_Controller::HTTP_OK);
        }   

        if($this->db->trans_status() === false){
            $this->db->trans_rollback();
            $this->set_response(array('success'=>false,'message'=>'Gagal saat hapus data'),REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $this->db->trans_commit();
            $this->set_response(array('success'=>true,'message'=>'Hapus data berhasil'),REST_Controller::HTTP_OK);
        }
    }

    function save_drug($drug,$medical_record_id){

        foreach ($drug as $key => $v) {
            # code...
            $cek = $this->db->get_where('medical_record_drug',array('medical_record_id'=>$medical_record_id,'product_id'=>$v->{'product_id'}));
            
            $data_drug = array(
                "medical_record_id" => $medical_record_id,
                "product_id" => $v->{'product_id'},
                "qty" => $v->{'qty'},
                "product_unit_id" => $v->{'product_unit_id'},
                "notes"=>$v->{'notes'},
                "deleted"=>0,
                "subtotal"=>$v->{'subtotal'}
            );

            if($cek->num_rows() > 0){
               $this->db->where(array('medical_record_id'=>$medical_record_id,'product_id'=>$v->{'product_id'}));
               $this->db->update('medical_record_drug',$data_drug);
            }else{
               $this->db->insert('medical_record_drug',$data_drug);
            }
        }
    }

    function delete_drug_post(){
        $this->db->trans_begin();
        
        if($this->post('medical_record_id')!=''){
           $medical_record_id = $this->post('medical_record_id');
        }else{
           $medical_record_id=null;
        }
        
        $product_id = $this->post('product_id');

        $cek = $this->db->get_where('medical_record_drug',array('medical_record_id'=>$medical_record_id,'product_id'=>$product_id)); 

        if($cek->num_rows() >0){
            $this->db->where(array('medical_record_id'=>$medical_record_id,'product_id'=>$product_id));
            $this->db->delete('medical_record_drug');
        }else{
            $this->set_response(array('success'=>true,'message'=>'Hapus data berhasil'),REST_Controller::HTTP_OK);
        }   

        if($this->db->trans_status() === false){
            $this->db->trans_rollback();
            $this->set_response(array('success'=>false,'message'=>'Gagal saat hapus data'),REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $this->db->trans_commit();
            $this->set_response(array('success'=>true,'message'=>'Hapus data berhasil'),REST_Controller::HTTP_OK);
        }
    }

    function save_action($action,$medical_record_id){

        foreach ($action as $key => $v) {
            # code...
            //cek master 
            
            if($v->{'medical_action_id'} == ''){
                
                $action = array(
                    'medical_action_id'=>$this->m_data->getPrimaryID2($v->{'medical_action_id'},'medical_action','medical_action_id'),
                    'medical_action_desc'=>$v->{'medical_action_desc'},
                    'medical_action_name'=>$v->{'medical_action_desc'},
                    "deleted"=>0,
                    "service_fee"=>$v->{'service_fee'}!=''?$v->{'service_fee'}:nullve
                );

                $this->db->insert('medical_action',$action);
            }

            if($v->{'medical_action_id'} != ''){
                $medical_action_id=$v->{'medical_action_id'};
                
                // $get_fee = $this->db->get_where('medical_action',array('medical_action_id',$v->{'medical_action_id'}))->row();
                // $service_fee = $get_fee->service_fee;
            }else{
                $medical_action_id=$action['medical_action_id'];
            }

            $cek = $this->db->get_where('medical_record_action',array('medical_record_id'=>$medical_record_id,'medical_action_id'=>$medical_action_id));
            
            $data_action = array(
                "medical_record_id" => $medical_record_id,
                "medical_action_id" => $medical_action_id,
                // "notes" => $v->{'notes'},
                // "product_unit_id" => $v->{'product_unit_id'},
                "deleted"=>0,
                "service_fee"=>$v->{'service_fee'}!=''?$v->{'service_fee'}:null
            );

            if($cek->num_rows() > 0){
               $this->db->where(array('medical_record_id'=>$medical_record_id,'medical_action_id'=>$v->{'medical_action_id'}));
               $this->db->update('medical_record_action',$data_action);
            }else{
               $this->db->insert('medical_record_action',$data_action);
            }
        }
    }

    function delete_action_post(){
        $this->db->trans_begin();
        
        if($this->post('medical_record_id')!=''){
           $medical_record_id = $this->post('medical_record_id');
        }else{
           $medical_record_id=null;
        }
        
        $medical_action_id = $this->post('medical_action_id');

        $cek = $this->db->get_where('medical_record_action',array('medical_record_id'=>$medical_record_id,'medical_action_id'=>$medical_action_id)); 

        if($cek->num_rows() >0){
            $this->db->where(array('medical_record_id'=>$medical_record_id,'medical_action_id'=>$medical_record_id));
            $this->db->delete('medical_record_action');
        }else{
            $this->set_response(array('success'=>true,'message'=>'Hapus data berhasil'),REST_Controller::HTTP_OK);
        }   

        if($this->db->trans_status() === false){
            $this->db->trans_rollback();
            $this->set_response(array('success'=>false,'message'=>'Gagal saat hapus data'),REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $this->db->trans_commit();
            $this->set_response(array('success'=>true,'message'=>'Hapus data berhasil'),REST_Controller::HTTP_OK);
        }
    }

    function save_invoice($patient_type,$patient_id,$member_id,$medical_record_date,$sales_id,$notes,$drug,$drug_alkes,$action,$service_amount){

        $sales_id = $sales_id;
        $customer_id = $patient_id;
        $invoice_date = $medical_record_date;
        $due_date = $medical_record_date;
        $status = 1;
        $memo = $notes;
        $tax_id = 0;
        $include_tax = 0;
        $shipping_cost = 0;
        
        $data_items=[];
        
        $total=0;
        $total_service_fee=0;
        $i=0;
        
        $x=0;

        foreach ($drug as $key => $value) {
            $data_items[$i]=$value;
            $i++;
        }
        
        $j=$i;

        if(count($drug_alkes)>0){
            //save resep dan Alkes klinik ke tabel salesitem
            foreach ($drug_alkes as $key => $v) {
                $data_items[$j]=$v;
                $j++;
            }
        }

        // print_r($data_items);
        //check sales item before save
        $check = $this->db->get_where('salesitem',array('idsales'=>$sales_id));

        if($check->num_rows()>0){
            foreach ($data_items as $items) {
               
                $r = $check->result_array()[$x++];
                $get_product = $this->db->get_where('product',array('product_id'=>$items->{'product_id'}))->row();
                
                $price = $items->{'subtotal'}*$items->{'qty'};
                $total += $price;
              
                $this->db->where(array('idsales' => $sales_id,'idsalesitem'=>$r['idsalesitem']));
                $this->db->update('salesitem',array(
                    'idsales' => $sales_id,
                    'product_id' => $items->{'product_id'},
                    'qty' => $items->{'qty'}*1,
                    'disc' => 0,                
                    'price' =>  $get_product->{'retail_price_member'},
                    'ratetax' => 0,
                    'total_tax' => 0,
                    'description' => 0,
                    'total'=> $price,
                    'usermod'=> 11,
                    'datemod'=> date('Y-m-d H:i:s'),
                    'deleted' => 0,
                ));
            }

        }else{
           foreach ($data_items as $items) {
               
                $get_product = $this->db->get_where('product',array('product_id'=>$items->{'product_id'}))->row();
                
                $price = $items->{'subtotal'}*$items->{'qty'};
                $total += $price;
             
                $this->db->insert('salesitem',array(
                    'idsales' => $sales_id,
                    'idsalesitem' => $this->m_data->getPrimaryID2(null,'salesitem','idsalesitem'),
                    'product_id' => $items->{'product_id'},
                    'qty' => $items->{'qty'}*1,
                    'disc' => 0,                
                    'price' =>  $get_product->{'retail_price_member'},
                    'ratetax' => 0,
                    'total_tax' => 0,
                    'description' => 0,
                    'total'=> $price,
                    'usermod'=> 11,
                    'datemod'=> date('Y-m-d H:i:s'),
                    'deleted' => 0,
                ));
            } 

        }   

        foreach ($action as $key => $act) {
            
            $total_service_fee += $act->{'service_fee'};
        }

        $total_amount = $total+$total_service_fee+$service_amount;
        
        $data_sales = array(
            'memo'=>$memo,
            'customer_type'=> $patient_type,
            'subtotal'=> $total_amount,
            'freight'=>0,
            'disc'=>0,
            'total'=>$total_amount,
            'tax'=>0,
            'totalamount'=>$total_amount,
            'unpaid_amount'=>$total_amount,
            'paidtoday'=>0,
            'idunit'=>12,      
            'due_date'=>$due_date,
            'invoice_status'=>3,
            'date_sales'=>$invoice_date,
            'invoice_date'=>$invoice_date,
            'include_tax'=>$include_tax,
            'id_payment_term'=>null
        );
       
        $params = array(
            'idunit' => 12,
            'prefix' => 'SO',
            'table' => 'sales',
            'fieldpk' => 'idsales',
            'fieldname' => 'no_sales_order',
            'extraparams'=> null,
        );
        $no_sales_order = $this->m_data->getNextNoArticle($params);
           
        $params = array(
            'idunit' => 12,
            'prefix' => 'INV',
            'table' => 'sales',
            'fieldpk' => 'idsales',
            'fieldname' => 'noinvoice',
            'extraparams'=> null,
        );
        
        $check_sales = $this->db->get_where('sales',array('idsales'=>$sales_id));

        if($check_sales->num_rows() > 0){
            
            $data_sales['order_status']=0;
            $data_sales['idsales'] = $sales_id;
            $data_sales['paidtoday'] = 0;
            $data_sales['datemod'] = date('Y-m-d H:m:s');
            $data_sales['usermod'] = 11;

            $this->db->where('idsales',$sales_id);
            $this->db->update('sales',$data_sales);
            
        }else{

            $invoice_no = $this->m_data->getNextNoArticle($params);
            $data_sales['noinvoice'] = $invoice_no;
            
            $data_sales['order_status']=0;
            $data_sales['no_sales_order'] = $no_sales_order;
            $data_sales['idsales'] = $sales_id;
            $data_sales['paidtoday'] = 0;
            $data_sales['datein'] = date('Y-m-d H:m:s');
            $data_sales['userin'] = 11;

            $this->db->insert('sales',$data_sales);
        }
        
           
    }

    function stock_after_sale($id,$type){
        $this->db->trans_begin();
        
        $sales_info = $this->db->get_where('salesitem', array('idsales' =>$id));
        
        foreach ($sales_info->result() as $key => $v) {
              # code...

            $product_info = $this->db->get_where('product', array('product_id' =>$v->product_id))->result();

            if($product_info[0]->inventory_class_id!=1){
                //bukan barang, lanjut!
                continue;
            }
                
            $product_name = $product_info[0]->product_name;
            $new_stock    = $product_info[0]->stock_available-$v->qty;

            $params = array(
                  'idunit' => $this->post('idunit'),
                  'prefix' => 'STCK',
                  'table' => 'stock_history',
                  'fieldpk' => 'stock_history_id',
                  'fieldname' => 'no_transaction',
                  'extraparams'=> null,
            );

            $notrx = $this->m_data->getNextNoArticle($params);
            if($type==8){
                $type_adjustment=$type;
                $trx_qty=$v->qty;
                $new_stock       = $product_info[0]->stock_available-$v->qty;

                if($product_info[0]->is_purchasable*1==2){

                    $product_balance = $new_stock * $product_info[0]->buy_price;

                }

            }else if($type==1){
                $type_adjustment=$type;
                $trx_qty=$v->qty;
                $new_stock    = $product_info[0]->stock_available-$v->qty;

                if($product_info[0]->is_purchasable*1==2){

                    $product_balance = $new_stock * $product_info[0]->buy_price;

                }

            }else if($type==10){
                $type_adjustment=$type;
                if(isset($return_info) and coun($return_info)>0){
                    $trx_qty      = $return_info->total_qty_return;
                    $new_stock    = $product_info[0]->stock_available-$trx_qty;
                }
                
                $trx_qty=$v->qty;
                $new_stock    = $product_info[0]->stock_available+$v->qty;

                if($product_info[0]->is_purchasable*1==2){

                    $product_balance = $new_stock * $product_info[0]->buy_price;

                }
            }

            $data_stock=array(
                'stock_history_id'=>$this->m_data->getPrimaryID2(null,'stock_history','stock_history_id'),
                'product_id'=>$v->product_id,
                'type_adjustment'=>$type_adjustment,
                'no_transaction'=>$notrx,
                'datein'=>date('Y-m-d H:i'),
                'current_qty'=>$product_info[0]->stock_available,
                'trx_qty'=>$trx_qty,
                'new_qty'=>$new_stock,
                'reference_id'=>$id
            );

           
            //insert stock historis
            $this->db->insert('stock_history',$data_stock); 
            //end insert


            //update stock available 
            $this->db->where('product_id',$v->product_id);
            $this->db->update('product',array(
                'stock_available'=>$new_stock
            ));
            //end update 

        }  

        if($this->db->trans_status()===false){
            $this->db->trans_rollback();
        }else{

            $this->db->trans_commit();
        }
    }

    function delete_medical_post(){

        $this->db->trans_begin();
       
        $postdata = json_decode($this->post('postdata'));

        foreach ($postdata as $id) {
            # code...
            $get_medical = $this->db->get_where('medical_record',array('medical_record_id'=>$id))->row();

            $this->db->where('medical_record_id',$id);
            $this->db->delete('medical_record_disease');
            
            $this->db->where('medical_record_id',$id);
            $this->db->delete('medical_record_action');
            
            $this->db->where('medical_record_id',$id);
            $this->db->delete('medical_record_drug');

            $this->db->where('medical_record_id',$id);
            $this->db->update('medical_record',array('deleted'=>1));

            $this->db->where('idsales',$get_medical->{'sales_id'});
            $this->db->delete('salesitem');

            $this->stock_after_sale($get_medical->{'sales_id'},$type=10);
            
            $this->db->where('idsales',$get_medical->{'sales_id'});
            $this->db->delete('sales');
        }

        if($this->db->trans_status() === false){
            $this->db->trans_rollback();
            $this->set_response(array('success'=>false,'message'=>'Gagal melakukan penyimpanan data'),REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $this->db->trans_commit();
            $this->set_response(array('success'=>true,'message'=>'Penyimpanan data berhasil'),REST_Controller::HTTP_OK);
        }
    }

    function update_penerimaan_resep_post(){

        $this->db->trans_begin();

        $medical_record_id   = $this->post('medical_record_id');
        $medical_record_no   = $this->post('medical_record_no');
        $receipt_number      = $this->post('receipt_number');
        $patient_id          = $this->post('patient_id');
        $doctor_id           = $this->post('doctor_id');
        $member_id           = $this->post('member_id');
        $medical_record_date = $this->post('medical_record_date');
        $patient_type_id     = $this->post('patient_type_id');
        $medicine_status     = $this->post('medicine_status');
        $payment_status      = $this->post('payment_status');
        $json_drug           = json_decode($this->post('json_drug'));
        
        $data_up = array(
            'medical_record_no' => $medical_record_no,
            'receipt_number' => $receipt_number,
            'patient_id' => $patient_id,
            'doctor_id' => $doctor_id,
            'medical_record_date' => $medical_record_date,
            'medicine_status' => $medicine_status,
            'payment_status' => $payment_status,
        );
        
        if($medicine_status*1 == 3 || $medicine_status*1 == 4){
            if($payment_status*1 == 1){
                $this->response(array('success'=>false,'message'=>'Tidak dapat melakukan update data dikarena pasien belum membayar biaya berobat'),REST_Controller::HTTP_BAD_REQUEST);
                return false;
            }
        }

        $this->db->where('medical_record_id',$medical_record_id);
        $this->db->update('medical_record',$data_up);

        // print_r($json_drug);
        $this->save_drug($json_drug,$medical_record_id);

        if($this->db->trans_status() ===false){
            $this->db->trans_rollback();
            $this->response(array('success'=>false,'message'=>'Update data gagal'),REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $this->db->trans_commit();
            $this->response(array('success'=>true,'message'=>'Update data berhasil'),REST_Controller::HTTP_OK);
        }
    }

    function medical_disease_summary_get(){

        $wer = " TRUE and deleted=0";

        $where = " and c.deleted=0";

        if($this->get('startdate')!='null' && $this->get('enddate')!='null'){
              
           $where .= " and (medical_record_date between '".backdate2($this->get('startdate'))."' and '".backdate2($this->get('enddate'))."')";
        }

        $get_disease = $this->db->query("SELECT * FROM disease WHERE $wer");
        
        $data=[];
        $i=0;
        if($get_disease->num_rows()>0){
            foreach ($get_disease->result() as $key => $value) {
                # code...
                $data[$i]['disease_name'] = $value->{'disease_name'};

                $qmed_dis = $this->db->query("SELECT COALESCE(count(c.disease_name)) as medial_disease FROM medical_record a
                                                  join medical_record_disease b on b.medical_record_id =a.medical_record_id
                                                  join disease c on c.disease_id=b.disease_id 
                                                  where c.disease_id=".$value->{'disease_id'}." $where")->row();

                $data[$i]['count_disease'] = $qmed_dis->{'medial_disease'};

                $i++;
                
            }
        }   
        


        $this->response(array('success'=>true,'rows'=>$data),REST_Controller::HTTP_OK);
    }

    function medical_action_summary_get(){
        
        $wer = " TRUE and deleted=0";
        
        $where = " and c.deleted=0";

        if($this->get('startdate')!='null' && $this->get('enddate')!='null'){
             
           $where .= " and (medical_record_date between '".backdate2($this->get('startdate'))."' and '".backdate2($this->get('enddate'))."')";
        }

        $get_action = $this->db->query("SELECT * FROM medical_action WHERE $wer");
        // echo $this->db->last_query();
        $data=[];
        $i=0;
        
        if($get_action->num_rows()>0){

            foreach ($get_action->result() as $key => $act) {
                # code...
                
                $data[$i]['act_name'] = $act->{'medical_action_name'};

                $qmed_dis = $this->db->query("SELECT COALESCE(count(c.medical_action_name)) as medial_act FROM medical_record a
                                              join medical_record_action b on b.medical_record_id =a.medical_record_id
                                              join medical_action c on c.medical_action_id=b.medical_action_id 
                                              where c.medical_action_id=".$act->{'medical_action_id'}." $where")->row();

                $data[$i]['count_act'] = $qmed_dis->{'medial_act'};

                $i++;
            }
        }else{
            $data=[];
        }
        
        // echo $this->db->last_query();

        $this->response(array('success'=>true,'rows'=>$data),REST_Controller::HTTP_OK);
    }

    function drug_usage_summary_get(){

        if($this->get('startdate')!=''){
            $startdate = backdate2($this->get('startdate'));
            // echo $startdate;
        }else{
            $startdate = null;            
        }

        if($this->get('enddate')!=''){
           $enddate = backdate2($this->get('enddate'));

        }else{
           $enddate = null;            
        }
        
        
        $data = $this->dokter->summary_drug_usage($startdate,$enddate);
        // echo $this->db->last_query();
        $this->response(array('success'=>true,'rows'=>$data),REST_Controller::HTTP_OK);
    }

    function pharmacy_receipt_summary_get(){

        if($this->get('startdate')!=''){
            $startdate = backdate2($this->get('startdate'));
            // echo $startdate;
        }else{
            $startdate = null;            
        }

        if($this->get('enddate')!=''){
           $enddate = backdate2($this->get('enddate'));

        }else{
           $enddate = null;            
        }
        
        
        $data = $this->dokter->summary_pharmacy_receipt($startdate,$enddate);
        // echo $this->db->last_query();
        $this->response(array('success'=>true,'rows'=>$data),REST_Controller::HTTP_OK);
    }

    function pharmacy_reports_get(){
        $startdate = $this->get('startdate');
        $enddate   = $this->get('enddate');
        $business_id = $this->get('business_id');
        $benefit_id_type = $this->get('benefit_id');
        
        $query = $this->dokter->pharmacy_putting_data($startdate,$enddate,$business_id,$benefit_id_type);
        
        $data = [];
        $i    = 0;

        $i=0;

        foreach ($query as $key => $v) {
            // print_r($v->patient_id);

            $qp1 = $this->db->query("SELECT 
                                            a.patient_id,a.patient_name,b.patient_name,a.member_id
                                    FROM 
                                            patient a join patient b on a.patient_parent_id=b.patient_id 
                                    where 
                                            TRUE and (a.patient_parent_id=0 or a.patient_parent_id !=0) and a.patient_id=".$v->{'patient_id'});
            if($qp1->num_rows()>0){

                $rqp1 = $qp1->row();
                $v->{'member_name'} = $rqp1->{'patient_name'};
            }else{

                if($v->{'member_id'}!=''){
                    $v->{'member_name'} = $v->{'patient_name'};
                }
            }

            $data[$i] = $v;
            $i++;
        }

        $this->response(array('success'=>true,'rows'=>$data),REST_Controller::HTTP_OK);
    }

    function medical_record_put(){

        $this->db->trans_begin();

        $data = array(
            "memo" => $this->put('memo'),
            "shpping_fee" => cleardot($this->put('shipping_fee')),
            "grand_total" => cleardot($this->put('total')),
            "payment_method" => $this->put('payment_methode'),
            "paid_date" => $this->put('date_payment'),
            "discount_amount"=>cleardot($this->put('diskon')),
            "payment_status" =>$this->put('payment_status')
        ); 
            
        $this->db->where('medical_record_id',$this->put('medical_record_id'));
        $this->db->update('medical_record',$data);
        
        if($this->db->trans_status()===FALSE){

            $this->db->trans_rollback();
            $this->response(array('status'=>false,'message'=>'Update data pendaftaraan gagal'),REST_Controller::HTTP_BAD_REQUEST);
        }else{

            $this->db->trans_commit();
            $this->response(array('status'=>true,'message'=>'Update data pendaftaraan berhasil'),REST_Controller::HTTP_CREATED);
        }
    }
}
?>
