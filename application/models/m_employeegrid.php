<?php

class m_employeegrid extends CI_Model {

    function tableName() {
        return 'employee';
    }

    function pkField() {
        return 'idemployee';
    }

    function searchField() {
        $field = "firstname,lastname";
        return explode(",", $field);
    }

    function selectField() {
        return "pegawaitglmasuk,a.idunit,idemployee,code,keaktifan,namaptkp,tglresign,firstname,lastname,address,telephone,handphone,a.fax,a.email,a.website,a.city,a.state,a.postcode,a.country,a.notes,b.nametype,namaunit";
    }
    
    function fieldCek()
    {
        //field yang perlu dicek didatabase apakah sudah ada apa belum
        $f = array(
          'code'=>'Kode Pegawai'  
        );
        return $f;
    }

    function query() {
        $query = "select " . $this->selectField() . "
                    from " . $this->tableName()." a "
                . "left join employeetype b ON a.idemployeetype = b.idemployeetype "
                . "left join unit c ON a.idunit = c.idunit 
                Left join jenisptkp d ON a.idjenisptkp = d.idjenisptkp";

        return $query;
    }

    function whereQuery() {
        return null;
    }

    function orderBy() {
        return "";
    }

    function updateField() { 
        $data = array(
            'idemployee' => $this->input->post('idemployee') == '' ? $this->m_data->getSeqVal('seq_employee') : $this->input->post('idemployee'),
            'idemployeetype' => $this->m_data->getID('employeetype', 'nametype', 'idemployeetype', $this->input->post('nametype')),
            'idunit' => $this->input->post('idunit'),
            'idjenisptkp' => $this->m_data->getID('jenisptkp', 'namaptkp', 'idjenisptkp', $this->input->post('namaptkp')),
            'code' => $this->input->post('code'),
            'firstname' => $this->input->post('firstname'),
            'lastname' => $this->input->post('lastname'),
            'address' => $this->input->post('address'),
            'telephone' => $this->input->post('telephone'),
//            'telephone' => $this->input->post('telephone'),
            'handphone' => $this->input->post('handphone'),
            'fax' => $this->input->post('fax'),
            'email' => $this->input->post('email'),
            'website' => $this->input->post('website'),
            'city' => $this->input->post('city'),
            'state' => $this->input->post('state'),
            'postcode' => $this->input->post('postcode'),
            'country' => $this->input->post('country'),
            'notes' => $this->input->post('notes'),
            'keaktifan' => $this->input->post('keaktifan'),
            'pegawaitglmasuk' => $this->input->post('pegawaitglmasuk'),
            'tglresign' => $this->input->post('tglresign')=='' ? null : $this->input->post('tglresign')
        );
        return $data;
    }

}

?>