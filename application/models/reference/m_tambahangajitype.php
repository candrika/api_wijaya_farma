<?php

class m_tambahangajitype extends CI_Model {

    function tableName() {
        return 'tambahangajitype';
    }

    function pkField() {
        return 'idtambahangajitype';
    }

    function searchField() {
        $field = "tambahantype";
        return explode(",", $field);
    }

    function selectField() {
        return "a.idtambahangajitype,a.idunit,a.tambahantype,a.deskripsi,a.userin,a.datein,b.namaunit";
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
                 . "join unit b ON a.idunit = b.idunit";

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
            'idtambahangajitype' => $this->input->post('idtambahangajitype') == '' ? $this->m_data->getSeqVal('seq_tambahangajitype') : $this->input->post('idtambahangajitype'),
            'idunit' => $this->m_data->getID('unit', 'namaunit', 'idunit', $this->input->post('namaunit')),
            'tambahantype' => $this->input->post('tambahantype'),
            'deskripsi' => $this->input->post('deskripsi')
        );
        return $data;
    }

}

?>