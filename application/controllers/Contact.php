<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Contact extends MY_Controller {

    function __construct(){
        parent::__construct();
        $this->load->model('m_business');

        $this->methods['users_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['users_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['users_delete']['limit'] = 50; // 50 requests per hour per user/key
    }

    public function type_get(){
    	$q = $this->db->get_where('customertype',array('idunit'=>$this->user_data->idunit,'deleted'=>0));
    	$data = array();
    	$i=0;
    	foreach ($q->result_array() as $key => $value) {
    		$data[$i]['contact_id'] = $value['idcustomertype'];
    		$data[$i]['contact_name'] = $value['namecustype'];
    		$i++;
    	}
    	$this->response(array('success'=>true,'data'=>$data), REST_Controller::HTTP_OK);
    }

}
?>