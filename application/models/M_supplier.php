<?php

class m_supplier extends CI_Model {

    function tableName() {
        return 'supplier';
    }

    function pkField() {
        return 'idsupplier';
    }

    function searchField() {
        $field = "namesupplier,code";
        return explode(",", $field);
    }

    function selectField() {
        return "a.idsupplier,a.idpayment,a.idshipping,a.code,a.namesupplier,a.companyaddress,a.companyaddress2,a.companyaddress3,a.companyaddress4,a.shipaddress,a.billaddress,a.telephone,a.handphone,a.fax,a.email,a.website,a.city,a.state,a.postcode,a.country,a.highestpo,a.avgdaypay,a.lastpayment,a.lastpurchase,a.expenseaccount,a.notes,a.userin,a.usermod,a.datein,a.datemod,a.idunit,a.status,a.deleted,a.supplier_type_id,b.supplier_type_name as typename";
    }
    
    function fieldCek()
    {
        //field yang perlu dicek didatabase apakah sudah ada apa belum
        $f = array(
          'code'=>'Kode Supplier'  
        );
        return $f;
    }

    function query() {
        $query = "select " . $this->selectField() . "
                    from " . $this->tableName()." a 
                    left join supplier_type b on b.supplier_type_id = a.supplier_type_id and b.idunit = a.idunit";

        return $query;
    }

    function whereQuery($idunit) {
        $wer = "where TRUE";

        if($idunit!=null){
            $wer .=" and a.idunit=$idunit";
        }

        return $wer;
    }

    function orderBy() {
        return "";
    }

    function updateField() { 
        $data = array(
            'idsupplier' => $this->input->post('idsupplier') == '' ? $this->m_data->getSeqVal('seq_supplier') : $this->input->post('idsupplier'),
            'code' => $this->input->post('code'),
            'namesupplier' => $this->input->post('namesupplier'),
            'companyaddress' => $this->input->post('companyaddress'),
            'companyaddress2' => $this->input->post('companyaddress2'),
            'companyaddress3' => $this->input->post('companyaddress3'),
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
            'supplier_type_id' => $this->input->post('supplier_type_id'),
            'idunit' => $this->input->post('idunit'),
            'status'=>$this->input->post('status'),
            'deleted'=>0,
        );
        return $data;
    }

}

?>