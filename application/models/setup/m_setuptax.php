<?php

class m_setuptax extends CI_Model {

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
        return "distinct a.idtax,a.code,a.nametax,a.description,a.rate,a.acccollectedtax as idacccollected,c.accname as acccollected,a.acctaxpaid as idaccpaid,d.accname as accpaid";
    }
    
    function fieldCek()
    {
        //field yang perlu dicek didatabase apakah sudah ada apa belum
        $f = array(
          'code'=>'Kode Pajak'  
        );
        return $f;
    }

    function query() {
        $query = "select " . $this->selectField() . "
                    from " . $this->tableName()." a "
                 . " join account c ON a.acccollectedtax = c.idaccount"
                 . " join account d ON a.acctaxpaid = d.idaccount";

        return $query;
    }

    function whereQuery() {
        return " a.deleted = 0";
    }

    function orderBy() {
        return "";
    }

    function updateField() { 
        $data = array(
            'idtax' => $this->input->post('idtax') == '' ? $this->m_data->getSeqVal('seq_tax') : $this->input->post('idtax'),
            'idtaxtype' => $this->m_data->getID('taxtype', 'nametypetax', 'idtaxtype', $this->input->post('nametypetax')),
            'code' => $this->input->post('code'),
            'nametax' => $this->input->post('nametax'),
            'description' => $this->input->post('description'),
            'rate' => $this->input->post('rate'),
            'acccollectedtax' => $this->input->post('idacccollected'),
            'acctaxpaid' => $this->input->post('idaccpaid')
        );
        return $data;
    }

}

?>