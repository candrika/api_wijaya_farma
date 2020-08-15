<?php

class m_customerGrid extends CI_Model {

    function tableName() {
        return 'customer';
    }

    function pkField() {
        return 'idcustomer';
    }

    function searchField() {
        $field = "namecustomer,nocustomer";
        return explode(",", $field);
    }

    function selectField() {
        return "idcustomer,nocustomer,a.idcustomertype,namecustomer,address,shipaddress,billaddress,telephone,handphone,fax,email,website,city,state,postcode,country,notes,b.namecustype,a.status";
    }
    
    function fieldCek()
    {
        //field yang perlu dicek didatabase apakah sudah ada apa belum
        $f = array(
          'nocustomer'=>'Kode Konsumen'  
        );
        return $f;
    }

    function query() {
        $query = "select " . $this->selectField() . "
                    from " . $this->tableName()." a "
                . "left join customertype b ON a.idcustomertype = b.idcustomertype";

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
            'idcustomer' => $this->input->post('idcustomer') == '' ? $this->m_data->getSeqVal('seq_customer') : $this->input->post('idcustomer'),
            'idcustomertype' => $this->input->post('idcustomertype'),
            'nocustomer' => $this->input->post('nocustomer'),
            'namecustomer' => $this->input->post('namecustomer'),
            'address' => $this->input->post('address'),
            'shipaddress' => $this->input->post('shipaddress'),
            'billaddress' => $this->input->post('billaddress'),
            'telephone' => $this->input->post('telephone'),
            'handphone' => $this->input->post('handphone'),
            'fax' => $this->input->post('fax'),
            'email' => $this->input->post('email'),
            'website' => $this->input->post('website'),
            'city' => $this->input->post('city'),
            'state' => $this->input->post('state'),
            'postcode' => $this->input->post('postcode'),
            'country' => $this->input->post('country'),
            'notes' => $this->input->post('notes'),
            'status'=>$this->input->post('status'),
            'idunit'=>$this->user_data->idunit,
        );
        return $data;
    }

}

?>