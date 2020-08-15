<?php

class m_potongantype extends CI_Model {

    function tableName() {
        return 'potongantype';
    }

    function pkField() {
        return 'idpotongantype';
    }

    function searchField() {
        $field = "namepotongan";
        return explode(",", $field);
    }

    function selectField() {
        return "a.idpotongantype,a.namepotongan,a.descpotongan,a.userin,a.datein";
    }
    
    function fieldCek()
    {
        //field yang perlu dicek didatabase apakah sudah ada apa belum
        $f = array(
          'idpotongantype'=>'idpotongantype'  
        );
        return $f;
    }

    function query() {
        $query = "select " . $this->selectField() . "
                    from " . $this->tableName()." a ";

        return $query;
    }

    function whereQuery() {
        return "a.display is null and a.idcompany = ".$this->session->userdata('idcompany')."";
    }

    function orderBy() {
        return "";
    }

    function updateField() { 
        $data = array(
            'idpotongantype' => $this->input->post('idpotongantype') == '' ? $this->m_data->getSeqVal('seq_master') : $this->input->post('idpotongantype'),
            // 'idunit' => $this->m_data->getID('unit', 'namaunit', 'idunit', $this->input->post('namaunit')),
            'namepotongan' => $this->input->post('namepotongan'),
            'descpotongan' => $this->input->post('descpotongan'),
            'jenispotongan' => 'Potongan',
            'idcompany' => $this->session->userdata('idcompany')
        );
        return $data;
    }

}

?>