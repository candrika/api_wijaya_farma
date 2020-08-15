<?php

class m_jenisptkp extends CI_Model {

    function tableName() {
        return 'jenisptkp';
    }

    function pkField() {
        return 'idjenisptkp';
    }

    function searchField() {
        $field = "namaptkp";
        return explode(",", $field);
    }

    function selectField() {
        return "a.idjenisptkp,a.namaptkp,a.deskripsi,a.totalptkp,a.userin,a.datein";
    }
    
    function fieldCek()
    {
        //field yang perlu dicek didatabase apakah sudah ada apa belum
        $f = array(
          'idinventorycat'=>'idinventorycat'  
        );
        return $f;
    }

    function query() {
        $query = "select " . $this->selectField() . "
                    from " . $this->tableName()." a ";

        return $query;
    }

    function whereQuery() {
        return "a.display is null";
    }

    function orderBy() {
        return "";
    }

    function updateField() { 
        $data = array(
            'idjenisptkp' => $this->input->post('idjenisptkp') == '' ? $this->m_data->getSeqVal('seq_master') : $this->input->post('idjenisptkp'),
            // 'idunit' => $this->m_data->getID('unit', 'namaunit', 'idunit', $this->input->post('namaunit')),
            'namaptkp' => $this->input->post('namaptkp'),
            'deskripsi' => $this->input->post('deskripsi'),
            'totalptkp' => $this->input->post('totalptkp')
        );
        return $data;
    }

}

?>