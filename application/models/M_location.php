<?php

class M_location extends CI_Model {

    function tableName() {
        return 'location';
    }

    function pkField() {
        return 'location_id';
    }

    function searchField() {
        $field = "location_name";
        return explode(",", $field);
    }

    function selectField() {
        return "location_id,location_name,deleted,userin,datein,usermod,datemod,status";
    }
    
    function fieldCek()
    {
        //field yang perlu dicek didatabase apakah sudah ada apa belum
        $f = array(
          'location_name'=>'Location Name'  
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

       if($idunit!=null){

            $wer .= " and a.idunit = ".$idunit." ";
       }
         
        return " a.deleted = 0 $wer";
    }

    function orderBy() {
        return "";
    }

    function updateField() { 

        // print_r( $this->session->userdata('user_id')); die;
        $data = array(
            'location_id' => $this->m_data->getPrimaryID2($this->input->post('location_id'),'location', 'location_id'),
            'location_name' => $this->input->post('location_name'),
            'status' => $this->input->post('status'),
            'userin' => $this->input->post('userid'),
            'datein' => date('Y-m-d H:m:s'),
            'usermod' => $this->input->post('userid'),
            'datemod' => date('Y-m-d H:m:s'),
            'idunit' => $this->input->post('idunit'),
            'deleted'=>0
        );
        return $data;
    }

}

?>