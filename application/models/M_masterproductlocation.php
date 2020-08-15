<?php

class M_masterproductlocation extends CI_Model {

    function tableName() {
        return 'product_location';
    }

    function pkField() {
        return 'product_location_id';
    }

    function searchField() {
        $field = "location_name";
        return explode(",", $field);
    }

    function selectField() {
        return "product_location_id,location_name,notes,deleted,datein,usermod,userin,datein,usermod,status";
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

    function whereQuery($userid,$idunit) {
       
        $wer = " and a.idunit = ".$idunit." ";
         
        return " a.deleted = 0 $wer";
    }

    function orderBy() {
        return "";
    }

    function updateField($idunit) { 

        // print_r( $this->session->userdata('user_id')); die;
        $data = array(
            'product_location_id' => $this->m_data->getPrimaryID2($this->input->post('product_location_id'),'product_location', 'product_location_id', $idunit),
            'location_name' => $this->input->post('location_name'),
            'notes' => $this->input->post('notes'),
            'deleted' => 0,
            'status' => $this->input->post('status'),
            'userin' => $this->input->post('userin'),
            'usermod' => $this->input->post('usermod'),
            'datemod' => $this->input->post('datemod'),
            'idunit' => $idunit,
        );
        return $data;
    }

}

?>