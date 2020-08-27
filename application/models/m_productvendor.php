<?php

class m_productvendor extends CI_Model {

    function tableName() {
        return 'product_vendor';
    }

    function pkField() {
        return 'vendor_id';
    }

    function searchField() {
        $field = "vendor_code,vendor_name";
        return explode(",", $field);
    }

    function selectField() {
        return "a.vendor_id,a.vendor_code,a.vendor_name,a.description,a.status,a.deleted";
    }
    
    function fieldCek()
    {
        //field yang perlu dicek didatabase apakah sudah ada apa belum
        $f = array(
          'vendor_name'=>'Nama Vendor'  
        );
        return $f;
    }

    function query() {
        $query = "select " . $this->selectField() . "
                    from " . $this->tableName()." a ";

        return $query;
    }

    function whereQuery($idunit) {
        $where="TRUE and a.deleted=0";

        if($idunit!=null){
            $where .=" and a.idunit=$idunit";
        }

        return $where;
    }

    function orderBy() {
        return "";
    }

    function updateField() { 

        $data = array(
            'vendor_id' => $this->m_data->getPrimaryID($this->input->post('vendor_id'),'product_vendor', 'vendor_id', $this->user_data->idunit),
            'vendor_code' => $this->input->post('vendor_code'),
            'vendor_name' => $this->input->post('vendor_name'),
            'idunit' => $this->user_data->idunit,
            'description'=>$this->input->post('description'),
            'status'=> $this->input->post('status'),
        );
        
        return $data;
    }

}

?>