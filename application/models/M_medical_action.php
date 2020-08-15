<?php

class M_medical_action extends CI_Model {

    function tableName() {
        return 'medical_action';
    }

    function pkField() {
        return 'medical_action_id';
    }

    function searchField() {
        $field = "medical_action_name";
        return explode(",", $field);
    }

    function selectField() {
        return "medical_action_id,medical_action_name,medical_action_desc,userin,datein,usermod,datemod,deleted,service_fee";
    }
    
    function fieldCek()
    {
        //field yang perlu dicek didatabase apakah sudah ada apa belum
        $f = array(
          'medical_action_name'=>'Action Name'  
        );
        return $f;
    }

    function query() {
        $query = "select " . $this->selectField() . "
                    from " . $this->tableName()." a ";

        return $query;
    }

    function whereQuery($idunit) {
       $wer = "";
         
       return " a.deleted = 0 $wer";
    }

    function orderBy() {
        return "";
    }

    function updateField() { 

        // print_r( $this->session->userdata('user_id')); die;
        $data = array(
            'medical_action_id' => $this->m_data->getPrimaryID2($this->input->post('medical_action_id'),'medical_action', 'medical_action_id'),
            'medical_action_name' => $this->input->post('medical_action_name'),
            'medical_action_desc' => $this->input->post('medical_action_desc'),
            'service_fee' => str_replace('.', '', $this->input->post('service_fee')),
            'deleted' => 0,
        );
        return $data;
    }

}

?>