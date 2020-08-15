<?php

class m_customertype extends CI_Model {

    function tableName() {
        return 'customertype';
    }

    function pkField() {
        return 'idcustomertype';
    }

    function searchField() {
        $field = "namecustype,description";
        return explode(",", $field);
    }

    function selectField() {
        return "a.idcustomertype,a.namecustype,a.description,a.idunit,a.status,a.deleted";
    }
    
    function fieldCek()
    {
        //field yang perlu dicek didatabase apakah sudah ada apa belum
        $f = array(
          'namecustype'=>'Customer Name'  
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

        // print_r( $this->session->userdata('user_id')); die;
        $data = array(
            'idcustomertype' => $this->m_data->getPrimaryID($this->input->post('idcustomertype'),'customertype', 'idcustomertype', $this->user_data->idunit),
            'namecustype' => $this->input->post('namecustype'),
            'description' => $this->input->post('description'),
            'idunit' => $this->user_data->idunit,
            'status'=> $this->input->post('status'),
            // 'deleted'=> $this->input->post('deleted'),
        );
        return $data;
    }

}

?>