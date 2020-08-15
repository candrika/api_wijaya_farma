<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Staff extends MY_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        // $this->load->model(array('m_user','m_sales','m_account','m_inventory','preferences/m_tax'));
        $this->load->model('m_staff');
        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        $this->methods['users_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['users_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['users_delete']['limit'] = 50; // 50 requests per hour per user/key
    }

    function datas_get()
    {
        $idunit = (int) $this->get('idunit');
        $Staff_id = (int) $this->get('staff_id');

        if ($idunit <= 0){
            $this->response(array('message'=>'idunit not found'), REST_Controller::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
        }

        $q = $this->m_staff->datas($this->get('query'),$idunit,$Staff_id);

        $d = $q['data']->result();
        $num_rows = $q['total']->num_rows();

        $i=0;
        $data=[]; 

        foreach ($d as $key => $value) {
            # code...

            $data[$i] = $value; 
            $i++;
        }

        // print_r($data);
        $this->set_response(array('success'=>true,'numrows'=>$num_rows,'results'=>$num_rows,'rows'=>$data),REST_Controller::HTTP_OK); 
    }

    public function save_profile_post(){
        $this->db->trans_start(); 

        // $user_id = $this->post('user_id');
        $birth_date = str_replace(' ', '', $this->post('birth_date'));

        $data = array(
            'idunit' => $this->user_data->idunit,
            'idjenisptkp' => $this->post('idjenisptkp')=='' ? null : $this->post('idjenisptkp'),
            'business_id' => $this->post('business_id')=='' ? null : $this->post('business_id'),
            'code' => $this->post('code'),
            'firstname' => $this->post('firstname'),
            'birth_date' => $birth_date!='' ? backdate2($birth_date) : null,
            'birth_location' => $this->post('birth_location'),
            'address' => $this->post('address'),
            'telephone' => $this->post('telephone'),
            'handphone' => $this->post('handphone'),
            'fax' => $this->post('fax'),
            'email' => $this->post('email'),
            'website' => $this->post('website'),
            'city' => $this->post('city'),
            'state' => $this->post('state'),
            'postcode' => $this->post('postcode'),
            'country' => $this->post('country'),
            'notes' => $this->post('notes'),
            'keaktifan' => $this->post('keaktifan'),
            'is_login'=>$this->post('is_login'),
            'group_id'=>$this->post('group_id'),
            'marital_status'=>$this->post('marital_status')=='' ? null : $this->post('marital_status'),
            'no_id'=>$this->post('no_id')
        );

        if($this->post('idemployee')==''){
            
            //insert

            if($this->post('is_login')==1){
                //create user/update
                //cek empty password field
                if($this->post('password')==''){
                    $this->set_response(array('success'=>false,'message'=>'Maaf password tidak boleh kosong!'),REST_Controller::HTTP_OK);
                    return false;    
                    //cek email di sys_user
                    // $c_user = $this->db->query("select a.* from sys_user a
                    //                             join employee b on b.user_id=a.user_id
                    //                             where b.idemployee=".$this->post('idemployee')." ");

                    // if($c_user->num_rows()>0){

                    // }else{
                    //     $this->set_response(array('success'=>false,'message'=>'Maaf password tidak boleh kosong!'),REST_Controller::HTTP_OK);
                    //     return false;    
                    // }
                }
            }

            if($this->check_email($data['email'])){
                $this->db->trans_rollback();
                
                $this->set_response(array(
                    'success'=>false,
                    'message'=>'Alamat email sudah terdaftar. Mohon gunakan alamat email yang lain.'
                ), REST_Controller::HTTP_OK); 
                return false;

            } else {
                $data['userin'] = $this->user_data->user_id;
                $data['datein'] = date('Y-m-d H:m:s');
                $data['idemployee'] = $this->m_data->getPrimaryID2($this->post('idemployee'),'employee', 'idemployee');
            }
            
              //sys_user
            if($this->post('is_login')==1){
                //create user/update
                $update_user_account = $this->update_user_account($user_id,$this->post('password'),$this->post('group_id'),$data['idemployee'],$data['email']);
                $data['user_id'] = $update_user_account['user_id'];
            }
            
            $this->db->insert('employee',$data);

           
        } else {
            if($this->post('password')!==''){
                // echo 'asda'; die;
                //passwordnya keisi. cek dulu udah dibuatin user apa belum
                $qcek = $this->db->get_where('employee',array('idemployee'=>$this->post('idemployee')))->row();
                // echo $this->db->last_query(); die;
                
                // if($this->post('password')==''){
                //     $get_password_existed = $this->db->query('select a.* from sys_user a
                //                                              join employee b on b.user_id=a.user_id where idemployee='.$this->post('idemployee'))->row();
                //     $password = $get_password_existed->password; 
                // }else{
                // $password = $this->post('password');
                // }

                if($qcek->user_id==null){
                    // $update_user_account = $this->update_user_account($user_id,$this->post('password'),$this->post('group_id'),$this->post('idemployee'),$data['email']);
                }
                //  else {
                //     $qcek_user = $this->db->get_where('sys_user',array('user_id'=>$qcek->user_id));
                //     if($qcek_user->num_rows()>0){
                //         $this->db->where('user_id',$qcek->user_id);
                //         $this->db->update('sys_user',array('password'=>$password));
                //     } else {
                //         $this->update_user_account('',$password,$this->post('group_id'),$this->post('idemployee'),$data['email']);
                //     }
                //     // echo 'bbb';
                    
                // }
            }

            //update
            $data['usermod'] = $this->user_data->user_id;
            $data['datemod'] = date('Y-m-d H:m:s');

            $qcek = $this->db->get_where('employee',array('idemployee'=>$this->post('idemployee')))->row();
            // $qcek_user = $this->db->get_where('sys_user',array('user_id'=>$qcek->user_id));
            if($qcek->user_id!=null){
                $update_user_account = $this->update_user_account($user_id,'',$this->post('group_id'),$this->post('idemployee'),$data['email']);
                if(!$update_user_account['success']){
                    $this->set_response(array(
                        'success'=>false,
                        'message'=>$update_user_account['message']
                    ), REST_Controller::HTTP_BAD_REQUEST); 
                    return false;
                }
                // $data['user_id'] = $update_user_account['user_id'];
            } else {
                //belum ada usernya
                 $update_user_account = $this->update_user_account($user_id,$this->post('password'),$this->post('group_id'),$this->post('idemployee'),$data['email']);
                 if(!$update_user_account['success']){
                     $this->set_response(array(
                        'success'=>false,
                        'message'=>$update_user_account['message']
                    ), REST_Controller::HTTP_BAD_REQUEST); 
                    return false;
                 } 
                //  print_r($update_user_account);
                 $data['user_id'] = $update_user_account['user_id'];
            }
            
            $this->db->where('idemployee',$this->post('idemployee'));
            $this->db->update('employee',$data);
        }

        if($this->post('idemployee')!=''){
            //cek referensi ke employee
            $qcek = $this->db->query("select idemployee FROM member where idemployee = ".$this->post('idemployee')." ");
            if($qcek->num_rows()>0){
                $rcek = $qcek->row();

                 $data = array(
                     'member_name' => $data['firstname'],
                     // 'lastname' => $this->input->post['lastname'],
                     'address' => $data['address'],
                     'telephone' => $data['telephone'],
                     'handphone' => $data['handphone'],
                     'fax' => $data['fax'],
                     'email' => $data['email'],
                     'website' => $data['website'],
                     'city' => $data['city'],
                     'state' => $data['state'],
                     'postcode' => $data['postcode'],
                     'country' => $data['country'],
                     'birth_date'=> backdate2($data['birth_date']),
                     'birth_location'=> $data['birth_location']
                );
                $this->db->where('idemployee',$this->post('idemployee'));
                $this->db->update('member',$data);
            }
        }



        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();

            $this->set_response(array(
                'success'=>false,
                'message'=>'Data gagal disimpan'
            ), REST_Controller::HTTP_BAD_REQUEST); 
        } 
        else {
            $this->db->trans_commit();

            $this->set_response(array(
                'success'=>true,
                'message'=>'Data berhasil disimpan'
            ), REST_Controller::HTTP_OK); 
        }
    }

    private function check_email($email){
        $q = $this->db->get_where('employee',array('email'=>$email,'deleted'=>0));
        if($q->num_rows()>0){
            return true;
        } else {
            return false;
        }
    }

    private function update_user_account($user_id,$password,$group_id,$idemployee,$email){
        // if($user_id=='' || $user_id==null){
            // $user_id = $this->m_data->getPrimaryID2(null,'sys_user','user_id');
        // }        
        $return_value = array(
            'success'=>true,
            'user_id'=>null,
            'message'=>'Success'
        );

        $data = array(
                'user_id' => $user_id,
                'group_id' => $group_id,
                'email' => $email,
                'idunit' => $this->user_data->idunit
        );

        if($password!=''){
            $data['password'] = $password;
        }

        if($user_id=='' || $user_id==null){
            //get api_key kodi
            $cek_mail = $this->db->get_where('sys_user',array('email'=>$email,'deleted'=>0));
            if($cek_mail->num_rows()>0){
                //ada
                $return_value = array(
                    'success'=>false,
                    'message'=>'Email sudah terdaftar'
                );
                return $return_value;
            }

            $api_key  = 'KRA_'.generateRandomString(25).base64_encode(date('YmdHms'));           
            $user_id = $this->m_data->getPrimaryID2(null,'sys_user','user_id');
            $data['user_id'] =  $user_id;
            $data['api_key'] = $api_key;
            $data['userin']  = $this->user_data->user_id;
            $data['datein']  = date('Y-m-d H:m:s');
            $this->db->insert('sys_user',$data);

            $return_value = array(
                'success'=>true,
                'user_id'=>$user_id,
                'message'=>'User berhasil terdaftar'
            );
            return $return_value;
        } else {
            //current email address
            $qcek = $this->db->get_where('sys_user',array('user_id'=>$user_id))->row();
            if(isset($qcek->email)){
                 if($qcek->email!=$email){
                    //ganti email, cek dulu udah ada apa belum emailnya
                    $cek_mail = $this->db->get_where('sys_user',array('email'=>$email,'deleted'=>0));

                    if($cek_mail->num_rows()>0){
                        //ada
                        $return_value = array(
                            'success'=>false,
                            'message'=>'Email sudah terdaftar'
                        );
                        return $return_value;
                    }
                }

           
                $data['usermod'] = $this->user_data->user_id;
                $data['datemod'] = date('Y-m-d H:m:s');
                if($this->post('password')!='' && $qcek->password==null){
                     $data['password'] = $this->post('password');
                }
                // print_r($data); die;
                $this->db->where('user_id',$user_id);
                $this->db->update('sys_user',$data);
            } else {

                //ternyata usernya belom ada
                $api_key  = 'KRA_'.generateRandomString(25).base64_encode(date('YmdHms'));           
                $user_id = $this->m_data->getPrimaryID2(null,'sys_user','user_id');
                $data['user_id'] =  $user_id;
                $data['api_key'] = $api_key;
                $data['userin']  = $this->user_data->user_id;
                $data['datein']  = date('Y-m-d H:m:s');
                $data['password'] = $password;
                $this->db->insert('sys_user',$data);
            }
           
        }

        $qcek = $this->db->get_where('userunit',array('user_id'=>$user_id));
        if($qcek->num_rows()>0)
        {
             $this->db->where('user_id',$user_id);
             $this->db->update('userunit',array(
                    'idunit' => $this->user_data->idunit
                ));
        } else {
             $this->db->insert('userunit',array(
                    'idunit' => $this->user_data->idunit,
                    'user_id' => $user_id
                ));
        }

        $this->db->where('idemployee',$idemployee);
        $this->db->update('employee',array('user_id'=>$user_id));

        return $return_value;
    }

    public function allowance_get(){
        $id = $this->get('id');
        $idunit = $this->user_data->idunit;

        if($id==''){
              $this->set_response(array(
                'success'=>false,
                'message'=>'idtunjangan is not found'
            ), REST_Controller::HTTP_BAD_REQUEST); 
        }

        // $q = $this->m_saving->summary($idunit);
        $q = $this->db->query("select idtunjangan,idemployee,persen,namatunjangan,startdate,enddate,jumlah,idamounttype,d.namasiklus,multiplier_id,a.idsiklus,CASE WHEN idamounttype = 1 THEN 'Prosentase' WHEN idamounttype = 2 THEN 'Nilai Tetap' ELSE '00' END AS amounttype_name
                    from payroll_tunjangan a 
                    join siklus d ON a.idsiklus = d.idsiklus 
                    WHERE a.idtunjangan = $id and a.idunit = $idunit");

        $data = array(
                'success'=>true,
                'data'=>$q->result_array()[0]
            );
        $this->set_response($data, REST_Controller::HTTP_OK); 
    }

    function delete_employee_post(){

        $this->db->trans_start();

        $id_employee = json_decode($this->post('postdata'));


        //get user id from table employee
        foreach ($id_employee as $v) {
            # code...
            $cek     = $this->db->get_where('employee',array('idemployee'=>$v))->row();
            $user_id = $cek->user_id;

            //cek jumlah minimal pengurus

            $num_employee = $this->db->get_where('employee',array('idunit'=>$this->user_data->idunit,'deleted'=>0));
            // echo $num_employee->num_rows();
            // die();
            if($num_employee->num_rows() == 1){
                // echo $num_employee->num_rows();
                $this->set_response(array('success'=>false,'message'=>'Gagal menghapus pengurus, karena data pengurus tidak boleh kosong'),REST_Controller::HTTP_BAD_REQUEST);
                return false;
            }

            //delete view 
            $this->db->where('idemployee',$v);
            $this->db->update('employee',array(
                'deleted'=>1
            ));


            //delete sys_user
            $this->db->where('user_id',$user_id);
            $this->db->update('sys_user',array(
                'deleted'=>1,
                'display'=>1
            ));

        }
    
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();

            $this->set_response(array(
                'success'=>false,
                'message'=>'Data gagal dihapus'
            ), REST_Controller::HTTP_BAD_REQUEST); 
        } 
        else {
            $this->db->trans_commit();
            $this->set_response(array(
                'success'=>true,
                'message'=>'Data berhasil dihapus'
            ), REST_Controller::HTTP_OK); 
        }
    }

    function data_get(){
        $idunit = (int) $this->user_data->idunit;
        $employee_id = (int) $this->get('eid');

        if ($idunit <= 0){
            $this->response(array('message'=>'idunit not found'), REST_Controller::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
        }

        $q = $this->m_employee->data($this->get('query'),$idunit,$employee_id);
        if(isset($q['data'][0])){
            $message = [
                    'success' => true,
                    'data' => $q['data'][0]
            ];
            $this->set_response($message, REST_Controller::HTTP_OK); 
        } else {
            $message = [
                    'success' => false,
                    'message' => 'Data not found'
            ];
            $this->set_response($message, REST_Controller::HTTP_BAD_REQUEST); 
        }
    }
}    
?>    