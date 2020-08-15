<?php

class m_suppliertype extends CI_Model {

    function tableName() {
        return 'supplier_type';
    }

    function pkField() {
        return 'idsuppliertype';
    }

    function searchField() {
        $field = "name";
        return explode(",", $field);
    }

    function selectField() {
        return "a.idsuppliertype,a.name,a.desc,a.idunit,a.status,a.deleted";
    }
    
    function fieldCek()
    {
        //field yang perlu dicek didatabase apakah sudah ada apa belum
        $f = array(
          'idsuppliertype'=>'ID'  
        );
        return $f;
    }

    function query() {
        $query = "select " . $this->selectField() . "
                    from " . $this->tableName()." a ";

        return $query;
    }

    function whereQuery() {
        return null;
    }

    function orderBy() {
        return "";
    }

    function updateField() { 

        // print_r( $this->session->userdata('user_id')); die;
        $data = array(
            'idsuppliertype' => $this->m_data->getPrimaryID($this->input->post('idsuppliertype'),'supplier_type', 'idsuppliertype', $this->session->userdata('idunit')),
            'name' => $this->input->post('name'),
            'desc' => $this->input->post('desc'),
            'status'=>$this->input->post('status'),
            'deleted'=>$this->input->post('deleted'),
            'idunit' => $this->session->userdata('idunit')
        );
        return $data;
    }

}

?>