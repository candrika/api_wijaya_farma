<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class System extends MY_Controller {

    function __construct(){
        parent::__construct();
        $this->load->model('m_business');

        $this->methods['users_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['users_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['users_delete']['limit'] = 50; // 50 requests per hour per user/key
    }

    public function get_group_access_post(){
    	 $data = array(
                'group_id'=>$this->post('group_id'),
                'sys_menu_id'=>$this->post('sys_menu_id')
            );
        $qcek = $this->db->get_where('sys_group_menu',$data);
        if($qcek->num_rows()>0)
        {
            $json = array('success' => true, 'value' => true);
        } else {
            $json = array('success' => true, 'value' => false);
        }
        $this->response($json, REST_Controller::HTTP_OK);       
    }

    public function group_access_post(){
    	 $visible = $this->post('visible');

    	  $data = array(
                'group_id'=>$this->post('group_id'),
                'sys_menu_id'=>$this->post('sys_menu_id')
            );

    	 if($visible=='true'){
    	 	//grant access
    	 	$cek = $this->db->get_where('sys_group_menu',$data);
    	 	if($cek->num_rows()<=0){
    	 		$this->db->insert('sys_group_menu',$data);
    	 	}        	
    	 } else {
    	 	//discard access
    	 	$this->db->where($data);
    	 	$this->db->delete('sys_group_menu');
    	 }

    	if($this->db->affected_rows()>0)
        {
            $json = array('success' => true, 'message' => 'grant access successfully');
        } else {
            $json = array('success' => false, 'message' => 'grant access failed');
        }
        $this->response($json, REST_Controller::HTTP_OK);       
    }

}
?>
