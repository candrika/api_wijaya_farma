<?php

class m_tunjangantype extends CI_Model {

    function tableName() {
        return 'tunjangantype';
    }

    function pkField() {
        return 'idtunjtype';
    }

    function searchField() {
        $field = "nametunj";
        return explode(",", $field);
    }

    function selectField() {
        return "a.idtunjtype,a.idunit,a.nametunj,a.desctunj,a.userin,a.datein";
    }
    
    function fieldCek()
    {
        //field yang perlu dicek didatabase apakah sudah ada apa belum
        $f = array(
          'idtunjtype'=>'idtunjtype'  
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
            'idtunjtype' => $this->input->post('idtunjtype') == '' ? $this->m_data->getSeqVal('seq_master') : $this->input->post('idtunjtype'),
            'idunit' => $this->m_data->getID('unit', 'namaunit', 'idunit', $this->input->post('namaunit')),
            'nametunj' => $this->input->post('nametunj'),
            'desctunj' => $this->input->post('desctunj'),
            'idcompany' => $this->session->userdata('idcompany')
        );
        return $data;
    }

}

?>