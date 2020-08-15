<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Remove extends MY_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->methods['users_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['users_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['users_delete']['limit'] = 50; // 50 requests per hour per user/key
    }

    function data_post(){
    	$this->db->trans_begin();
    	$dir = $this->post('dir');
    	$model = $this->post('model');
    	// $id = $this->post('id');

        if ($dir != null) {
            $dir = $dir . '/';
        }

        $modelfile = $dir . 'm_' . $model;
        $this->load->model($modelfile, 'datamodel');

        $pkfield = $this->datamodel->pkField();
        // print_r($pkfield);
        // $d = $this->datamodel->updateField($this->post());
        $fc = $this->datamodel->fieldCek();

        $remove_data = json_decode($this->post('remove_data'));

        foreach ($remove_data as $v) {
        	$this->db->where($pkfield, $v);
        	$this->db->update($this->datamodel->tableName(),array('deleted'=>1));
        	// echo $this->db->last_query();
        }
       

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $json = array('success' => false, 'message' => 'Data gagal dihapus');
        } else {
            $this->db->trans_commit();
            $json = array('success' => true, 'message' => 'Data berhasil dihapus');
        }

        $this->response($json, REST_Controller::HTTP_OK);
    }

}
?>