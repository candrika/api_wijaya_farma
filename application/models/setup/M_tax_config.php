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
        return "a.idtax,a.idunit,a.nametax,a.description,a.rate,a.idunit";
    }

    function fieldCek(){
        $f = array(
          'rate'=>'Tarif (%) '  
        );
        return $f;
    }

    function query() {
        $query = "select " . $this->selectField() . "
                    from " . $this->tableName()." a";

        return $query;
    }

    function whereQuery() {
        return "a.deleted = 0 and a.idunit = ".$this->user_data->idunit;
    }

    function orderBy() {
        return " idtax desc";
    }

    function updateField($post_data) {
        $data = array(
            // 'idtax' => $this->m_data->getID('bussinestype', 'namebussines', 'idbussinestype', $this->input->post('namebussines')),
            'idtax' => $this->m_data->getPrimaryID2($post_data[$this->pkField()],$this->tableName(),$this->pkField()),
            'nametax' => $post_data['nametax'],
            'description' => $post_data['description'],
            'rate' => $post_data['rate']=='' ? null : $post_data['rate'],
            'coa_ap_id' => $post_data['coa_ap_id']=='' ? null : $post_data['coa_ap_id'],
            'coa_cash_id' => $post_data['coa_cash_id']=='' ? null : $post_data['coa_cash_id'],
            'coa_expense_id' => $post_data['coa_expense_id']=='' ? null : $post_data['coa_expense_id'],
            'idunit' => $this->user_data->idunit
        );
        return $data;
    }

}

?>