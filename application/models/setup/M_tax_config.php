<?php

class M_tax_config extends CI_Model {

    function tableName() {
        return 'tax';
    }

    function pkField() {
        return 'idtax';
    }

    function searchField() {
        $field = "nametax";
        return explode(",", $field);
    }

    function selectField() {
        return "a.idtax,a.idunit,a.nametax,a.description,a.rate,a.idunit,a.is_tax,a.coa_tax_sales_id,a.coa_tax_purchase_id,
        b.accname as acc_tax_sales,c.accname as acc_tax_purchace";
    }

    function fieldCek(){
        $f = array(
          'rate'=>'Tarif (%) '  
        );
        return $f;
    }

    function query() {
        $query = "select " . $this->selectField() . "
                 from " . $this->tableName()." a ".
                 "inner join account b on b.idaccount=a.coa_tax_sales_id
                  inner join account c on c.idaccount=a.coa_tax_purchase_id";

        return $query;
    }

    function whereQuery() {
        return "a.deleted = 0 and a.idunit = ".$this->user_data->idunit;
    }

    function orderBy() {
        return " idtax desc";
    }

    function updateField() {
        $data = array(
            'idtax' => $this->m_data->getPrimaryID2($this->input->post($this->pkField()),$this->tableName(),$this->pkField()),
            'nametax' => $this->input->post('nametax'),
            'description' => $this->input->post('description'),
            'rate' => $this->input->post('rate')=='' ? null : $this->input->post('rate'),
            'is_tax' => $this->input->post('is_tax')=='' ? null : $this->input->post('is_tax'),
            'coa_tax_sales_id' => $this->input->post('coa_tax_sales_id')=='' ? null : $this->input->post('coa_tax_sales_id'),
            'coa_tax_purchase_id' => $this->input->post('coa_tax_purchase_id')=='' ? null : $this->input->post('coa_tax_purchase_id'),
            'idunit' => $this->user_data->idunit
        );
        return $data;
    }

}

?>