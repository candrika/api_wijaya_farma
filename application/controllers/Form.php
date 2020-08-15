<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Form extends MY_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->methods['users_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['users_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['users_delete']['limit'] = 50; // 50 requests per hour per user/key
    }

    function save_post(){
    	$this->db->trans_begin();
    	$dir = $this->post('dir');
    	$model = $this->post('model');

        if ($dir != null) {
            $dir = $dir . '/';
        }
        // else{
        //     $dir = null;
        // }

        $modelfile = $dir . 'M_' . $model;
        // echo $modelfile;
        $this->load->model($modelfile, 'datamodel');
        $formstate = 'statusform' . $model;

        $statusform = $this->input->post($formstate);

        $d = $this->datamodel->updateField($this->post('idunit'));
        $fc = $this->datamodel->fieldCek();

        //cek existing data
        $pkfield = $this->datamodel->pkField();
        $pkfield = explode(",", $pkfield);
        $arrWer = array();
        foreach ($d as $key => $value) {
            foreach ($pkfield as $vpk) {
                if ($key == $vpk && $value != null) {
//                    echo $value;
                    $arrWer[$key] = $value;
                }
            }
        }

        // $d = $this->post();
        $d['userin'] = $this->post('idunit');
        $d['datein'] = date('Y-m-d H:m:s');
        $d['usermod'] = $this->post('idunit');
        $d['datemod'] = date('Y-m-d H:m:s');

        $pk_value = $this->post($pkfield[0]);

            if ($fc !== FALSE) {
                //cek udah ada apa belom
                foreach ($d as $key => $value) {

                    foreach ($fc as $keyfc => $valuefc) {
                       // echo $keyfc."==".$key."<br>";
                        if ($keyfc == $key) {

                              //cek tabel tersebut pakai deleted atau tidak
                            $qdeleted = $this->db->query("SELECT column_name 
                                                            FROM information_schema.columns 
                                                            WHERE table_name='".$this->datamodel->tableName()."' and column_name='deleted'")->row();
                            if(isset($qdeleted->column_name)){
                                if($qdeleted->column_name=='deleted')
                                    {
                                        $deleted = true;
                                    } else {
                                         $deleted = false;
                                    }
                                } else {
                                     $deleted = false;
                                }

                            //cek tabel tersebut pakai idunit atau tidak
                            $qidunit = $this->db->query("SELECT column_name 
                                                            FROM information_schema.columns 
                                                            WHERE table_name='".$this->datamodel->tableName()."' and column_name='idunit'")->row();


                            if(isset($qidunit->column_name))
                            {
                                if($qidunit->column_name=='idunit')
                                {   
                                    $validunit = $this->post('idunit') == '' ? $this->post('idunit') : null;

                                    if($deleted)
                                    {
                                        $wer = array($key => $value,'idunit'=>$validunit,'deleted'=>0);
                                    } else {
                                        $wer = array($key => $value,'idunit'=>$validunit);
                                    }
                                    $qcek = $this->db->get_where($this->datamodel->tableName(), $wer);

                                      // echo $this->db->last_query(); die;
                                } else {
                                     if($deleted)
                                    {
                                        $wer = array($key => $value,'deleted'=>0);
                                    } else {
                                        $wer = array($key => $value);
                                    }

                                    $qcek = $this->db->get_where($this->datamodel->tableName(), $wer);
                                }  
                            } else {
                                 $wer = array($key => $value);
                                $qcek = $this->db->get_where($this->datamodel->tableName(), $wer);
                            }

                                                     
                            // echo $this->db->last_query(); $pk_value = $this->post($pkfield[0]);                           
                            if ($qcek->num_rows() > 0) {
                                $rcek = $qcek->row();
                                if($pk_value==$rcek->{$pkfield[0]}){
                                    // echo 'idnya sama gpp';
                                } else {                                    
                                    $json = array('success' => false, 'message' => $valuefc . ' <b>' . $value . '</b> sudah ada di dalam database');
                                    echo json_encode($json);
                                    exit;
                                }
                            }

                            
                        }
                    }
                }
            }

            
            // echo 'asdasd'.$pkfield[0].' '.$pk_value; die;
            if($pk_value==''){
            	//create new
            	 $this->db->insert($this->datamodel->tableName(), $d);
            } else {
            	//update
            	 $this->db->where($pkfield[0], $pk_value);
            	 $this->db->update($this->datamodel->tableName(), $d);
            }

           

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                 $json = array('success' => false, 'message' => 'Data gagal disimpan');
            } else {
                $this->db->trans_commit();
                $json = array('success' => true, 'message' => 'Data berhasil disimpan');
            }

        $this->response($json, REST_Controller::HTTP_OK);
    }

}
?>
