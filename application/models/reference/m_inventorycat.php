<?php

class m_inventorycat extends CI_Model {

    function tableName() {
        return 'inventorycat';
    }

    function pkField() {
        return 'idinventorycat';
    }

    function searchField() {
        $field = "namecat,description";
        return explode(",", $field);
    }

    function selectField() {
        return "a.idinventorycat,a.namecat,a.description,a.userin,a.datein";
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
            'idinventorycat' => $this->input->post('idinventorycat') == '' ? $this->m_data->getSeqVal('seq_master') : $this->input->post('idinventorycat'),
            // 'idunit' => $this->m_data->getID('unit', 'namaunit', 'idunit', $this->input->post('namaunit')),
            'namecat' => $this->input->post('namecat'),
            'description' => $this->input->post('description')
        );
        return $data;
    }

}

?>