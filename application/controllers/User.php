<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class User extends MY_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        $this->load->model('m_user');

        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        $this->methods['users_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['users_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['users_put']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['users_delete']['limit'] = 50; // 50 requests per hour per user/key
    }

    public function group_post(){
        $this->db->trans_begin();

        $data = array(
            'group_id' => $this->m_data->getPrimaryID2(null,'sys_group', 'group_id'),
            'group_name' => $this->post('group_name'),
             "idunit" => $this->user_data->idunit,
            'description' => $this->post('description')
        );
        $this->db->insert('sys_group',$data);

        //create access
        $qgroup = $this->db->query("select sys_menu_id
                                    from sys_menu
                                    where (display is null or display = 1)");
        foreach ($qgroup->result_array() as $rr) {
            $datag = $rr;
            $datag['group_id'] = $data['group_id'];
            $cek = $this->db->get_where('sys_group_menu',$datag);
            if($cek->num_rows()<=0){
                 $this->db->insert('sys_group_menu',$datag);
            }               
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $json = array('success' => false, 'message' => 'Failed saving data');
            $this->response($json, REST_Controller::HTTP_BAD_REQUEST);
        } else {
            $this->db->trans_commit();            
            $json = array('success' => true, 'message' => 'Data Saved Successfully');
            $this->response($json, REST_Controller::HTTP_CREATED);
        }

        
    }

    public function group_put(){
        $this->db->trans_begin();

        $data = array(
            'group_name' => $this->put('group_name'),
            'idunit' => $this->user_data->idunit,
            'description' => $this->put('description')
        );
        $this->db->where('group_id',$this->put('group_id'));
        $this->db->update('sys_group',$data);

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $json = array('success' => false, 'message' => 'Failed saving data');
            $this->response($json, REST_Controller::HTTP_BAD_REQUEST);
        } else {
            $this->db->trans_commit();            
            $json = array('success' => true, 'message' => 'Data Saved Successfully');
            $this->response($json, REST_Controller::HTTP_CREATED);
        }
    }

    public function group_remove_post(){
        $id = $this->post('id');

        $this->db->trans_begin();

        $data = array(
            'deleted' => 1,
            'usermod' => $this->user_data->user_id,
            'datemod' => date('Y-m-d H:i:s')
        );
        $this->db->where('group_id',$id);
        $this->db->update('sys_group',$data);

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $json = array('success' => false, 'message' => 'Failed deleting data');
            $this->response($json, REST_Controller::HTTP_BAD_REQUEST);
        } else {
            $this->db->trans_commit();            
            $json = array('success' => true, 'message' => 'Data deleted Successfully');
            $this->response($json, REST_Controller::HTTP_CREATED);
        }
    }


    public function url_login_post(){
        $uset = $this->post('uset');

        if ($uset == ''){
            $this->response(array('success'=>false,'message'=>'uset not found'), REST_Controller::HTTP_NOT_FOUND); // BAD_REQUEST (400) being the HTTP response code
        }

        $cek = $this->m_user->cekUser(null,null,$uset);

        $this->response($cek, REST_Controller::HTTP_OK);
    }

    public function member_login_post(){
        $uset = $this->post('uset');
        $userid = $this->post('userid');
        $password = $this->post('password');

        if($uset!=''){
            $userid = null;
            $password = null;
        } else {
            $uset = null;
            if ($userid == ''){
                $this->response(array('success'=>false,'message'=>'userid not found'), REST_Controller::HTTP_NOT_FOUND); // BAD_REQUEST (400) being the HTTP response code
            }

            if ($password == ''){
                $this->response(array('success'=>false,'message'=>'password not found'), REST_Controller::HTTP_NOT_FOUND); // BAD_REQUEST (400) being the HTTP response code
            }
        }

      

        $cek = $this->m_user->cekMember($userid,$password,$uset);
        if(!$cek['success']){
            $this->response($cek, REST_Controller::HTTP_BAD_REQUEST);
        } else {
            $this->response($cek, REST_Controller::HTTP_OK);
        }

        
    }

    public function login_post(){
        $userid = $this->post('userid');
        $password = $this->post('password');

        if ($userid == ''){
            $this->response(array('success'=>false,'message'=>'userid not found'), REST_Controller::HTTP_NOT_FOUND); // BAD_REQUEST (400) being the HTTP response code
        }

        if ($password == ''){
            $this->response(array('success'=>false,'message'=>'password not found'), REST_Controller::HTTP_NOT_FOUND); // BAD_REQUEST (400) being the HTTP response code
        }
        $cek = $this->m_user->cekUser($userid,$password);

        $this->response($cek, REST_Controller::HTTP_OK);
    }

    public function register_post(){
        $email = $this->post('email');
        $password = $this->post('password');
        $password_conf = $this->post('password-conf');

        if($password!=$password_conf){
            $this->response(array('success'=>false,'message'=>'Kata kunci tidak sama'), REST_Controller::HTTP_BAD_REQUEST);
        }

        $q = $this->db->get_where('sys_user',array('email'=>$email));
        if($q->num_rows()>0){
            $this->response(array('success'=>false,'message'=>'Email sudah terdaftar'), REST_Controller::HTTP_BAD_REQUEST);
        }

        $data = array(
            'user_id'=>$this->m_data->getPrimaryID2(null,'sys_user', 'user_id'),
            // 'user_code' => generateRandomString(),
            'password' => $password,
            'email' => $email,
            'group_id' => 2,
            'datein' => date('Y-m-d H:m:s'),
			'api_key'=>'KDI'.generateRandomString(25).base64_encode(date('YmdHms'))
        );

        $this->db->insert('sys_user',$data);

        //insert to employee
        $dataemp = array(
            'idemployee' => $this->m_data->getPrimaryID2(null,'employee', 'idemployee'),
            // 'idunit' => $idunit,
            'idjenisptkp' => 1,
            // 'code' => $r->nik,
            // 'firstname' => $r->realname,
            // 'address' => $r->address,
            // 'telephone' => $r->phone,
            // 'handphone' => $r->phone,
            'email' => $email,
            'keaktifan' => 'Aktif',
            'user_id' => $data['user_id'],
            // 'group_id' => $sys_group_id,
            'is_login' => 1,
            'status'=>1,
            'datein'=>date('Y-m-d H:m:s'),
            'datemod'=>date('Y-m-d H:m:s')
        );
        $this->db->insert('employee',$dataemp);

        if($this->db->affected_rows()>0){
           $this->response(array('success'=>true,'message'=>'Akun berhasil terdaftar. Silahkan masuk sebagai Pengurus Koperasi.'), REST_Controller::HTTP_CREATED);
        } else {
            $this->response(array('success'=>false,'message'=>'Akun gagal terdaftar. Silahkan coba kembali.'), REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    public function change_pass_put(){
        $userid = $this->put('userid');
        $old_password = $this->put('old_password');
        $new_password = $this->put('new_password');

        if ($userid == ''){
            $this->response(array('success'=>false,'message'=>'userid not found'), REST_Controller::HTTP_NOT_FOUND); // BAD_REQUEST (400) being the HTTP response code
        }

        if ($old_password != $new_password){
            $this->response(array('success'=>false,'message'=>'password not same'), REST_Controller::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
        }

        $cek = $this->m_user->change_pass($userid,$new_password);

        $this->response($cek, REST_Controller::HTTP_OK);
    }

    public function load_sysgroup_get()
    {

        $data = $this->m_user->sys_group($this->get('idunit'),$this->get('group_id'));

        if($this->get('group_id')!=''){

            $d = $data->row();

            $this->response(array('success'=>true,'data'=>$d), REST_Controller::HTTP_OK); // OK_SUCCESS (200) being the HTTP response code        
        }
    }
}
?>
