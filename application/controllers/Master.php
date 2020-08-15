<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Master extends MY_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        $this->load->model('M_location','lokasi');
        
        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        $this->methods['users_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['users_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['users_put']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['users_delete']['limit'] = 50; // 50 requests per hour per user/key
    }

    function save_form_post(){

        $dir = $this->post('dir');
        $model_name = $this->post('model');

        if($dir !=null){
            $dir = $dir.'/';
        }

        $modelfile = $dir.'m_'.$model_name;

        $this->load->model($modelfile,'datamodel');
        $this->db->trans_begin();
        $id=$this->datamodel->pkField();
       
        if ($this->post($id)!=''){
            $this->db->where($this->datamodel->pkField(),$this->post($id));
            $this->db->update($this->datamodel->tableName(),$this->datamodel->updateField());
        }else{

            $this->db->insert($this->datamodel->tableName(),$this->datamodel->updateField());
        }
        // echo $this->db->last_query();
        if($this->db->trans_status()===false){
            $this->db->trans_rollback();
            $this->response(array('success'=>false,'message'=>'Gagal melakukan penyimpanan data'),REST_Controller::HTTP_BAD_REQUEST);
            
        }else{
            $this->db->trans_commit();    
            $this->response(array('success'=>true,'message'=>'Berhasil melakukan penyimpanan data'),REST_Controller::HTTP_CREATED);
        }
    }

    function form_loader_get(){
        
        $dir = $this->get('dir');
        $model_name = $this->get('model');

        if ($dir != null) {
            $dir = $dir . '/';
        }

        $modelfile = $dir . 'm_' . $model_name;
        $this->load->model($modelfile, 'datamodel');

        if($this->get('idunit')!=''){
            $wer = $this->datamodel->whereQuery($this->get('idunit')). " and ".$this->get('extraparams');

        }else{
            $wer = $this->datamodel->whereQuery($this->get('idunit'))." and ".$this->get('extraparams');

        }
        
        $sql = $this->datamodel->query()." WHERE ".$wer;

        $q = $this->db->query($sql);

        $d = $q->row();

        $this->set_response(array('success'=>true,'data'=>$d),REST_Controller::HTTP_OK);
    }

    function delete_post(){
        $this->db->trans_begin();
        
        $dir = $this->post('dir');
        $model_name = $this->post('model');

        if ($dir != null) {
            $dir = $dir . '/';
        }

        $modelfile = $dir . 'm_' . $model_name;
        $this->load->model($modelfile, 'datamodel');
       
        $postdata = json_decode($this->post('postdata'));

        $primary    = $this->datamodel->pkField();
        $table_name = $this->datamodel->tableName();
        // die;
        foreach ($postdata as $id) {
            
            $this->db->where($primary,$id);
            $this->db->update($table_name,array('deleted'=>1));
            
        }

        if($this->db->trans_status() === false){
            $this->db->trans_rollback();
            $this->set_response(array('success'=>false,'message'=>'Gagal melakukan penghapusan data'),REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $this->db->trans_commit();
            $this->set_response(array('success'=>false,'message'=>'Penghapusan data berhasil'),REST_Controller::HTTP_OK);
        }
    }

    function grid_get(){
        $dir = $this->get('dir');
        $model_name=$this->get('model');
        $idunit=$this->get("idunit");

        if ($dir != null) {
            $dir = $dir . '/';
        }

        $modelfile = $dir . 'm_' . $model_name;
        $this->load->model($modelfile, 'datamodel');
        // echo $modelfile; 
        $where = "";

        if($idunit!=null){
            $where .=" where ".$this->datamodel->whereQuery($idunit);
        }
        // $w = "idunit =$idunit";

        if($this->get('query')!=null){

            $search = $this->datamodel->searchField();
            $where .= " and (";
            $i=1;

            foreach ($search as $key => $v) {
                # code...
                // print_r($v);
                $where .=" $v like '%".$this->get('query')."%'";

                if($i!=count($search)){

                    $where .=" or ";
                }

                $i++;
            }

            $where .=")";
        }

        $sql = $this->datamodel->query().$where;
        $arr=[];
        $query = $this->db->query($sql);

        foreach ($query->result() as $obj) {
            $arr[] = $obj;
        }

        $results = $query->num_rows();
       
        $message =$this->set_response(array('success'=>true,'numrow'=> $query->num_rows(), 'results'=>  $results ,'rows'=> $arr),REST_Controller::HTTP_OK);
        $query->free_result(); 
        // $query->free_result(); 
    } 
}
?>