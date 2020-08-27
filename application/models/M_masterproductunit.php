<?php

class M_masterproductunit extends CI_Model {

    function tableName() {
        return 'product_unit';
    }

    function pkField() {
        return 'product_unit_id';
    }

    function searchField() {
        $field = "product_unit_code,product_unit_name";
        return explode(",", $field);
    }

    function selectField() {
        return "product_unit_id,idunit,product_unit_code,product_unit_name";
    }
    
    function fieldCek()
    {
        //field yang perlu dicek didatabase apakah sudah ada apa belum
        $f = array(
          'product_unit_code'=>'Kode Satuan'  
        );
        return $f;
    }

    function query() {
        $query = "select " . $this->selectField() . "
                    from " . $this->tableName()." a ";

        return $query;
    }

    function whereQuery() {
       
        $wer = " and a.idunit = ".$this->user_data->idunit." ";
         
        return " a.deleted = 0 $wer";
    }

    function orderBy() {
        return "order by product_unit_id ASC";
    }

    function updateField() { 

        // print_r( $this->session->userdata('user_id')); die;
        $data = array(
            'product_unit_id' => $this->m_data->getPrimaryID2($this->input->post('product_unit_id'),'product_unit', 'product_unit_id', $this->user_data->idunit),
            'product_unit_code' => $this->input->post('product_unit_code'),
            'product_unit_name' => $this->input->post('product_unit_name'),
            'idunit' => $this->user_data->idunit,
        );
        return $data;
    }

}

?>