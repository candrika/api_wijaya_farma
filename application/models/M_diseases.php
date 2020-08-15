<?php

class M_diseases extends CI_Model {

    function tableName() {
        return 'disease';
    }

    function pkField() {
        return 'disease_id';
    }

    function searchField() {
        $field = "disease_code";
        return explode(",", $field);
    }

    function selectField() {
        return "disease_id,disease_code,disease_name,disease_desc,userin,datein,usermod,datemod,deleted";
    }
    
    function fieldCek()
    {
        //field yang perlu dicek didatabase apakah sudah ada apa belum
        $f = array(
          'disease_name'=>'Disease Name'  
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
            'disease_id' => $this->m_data->getPrimaryID2($this->input->post('disease_id'),'disease', 'disease_id'),
            'disease_code' => $this->input->post('disease_code'),
            'disease_name' => $this->input->post('disease_name'),
            'disease_desc' => $this->input->post('disease_desc'),
            'deleted' => 0,
        );
        return $data;
    }

}

?>