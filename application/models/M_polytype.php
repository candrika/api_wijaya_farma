<?php

class M_polytype extends CI_Model {

    function tableName() {
        return 'poly_type';
    }

    function pkField() {
        return 'polytpe_id';
    }

    function searchField() {
        $field = "polytpe_name";
        return explode(",", $field);
    }

    function selectField() {
        return "a.polytpe_id,a.polytpe_name,a.deleted,a.userin,a.datein,a.usermod,a.datemod,a.location_id,a.status,a.polytpe_desc,b.location_name";
    }
    
    function fieldCek()
    {
        //field yang perlu dicek didatabase apakah sudah ada apa belum
        $f = array(
          'polytpe_name'=>'Polytpe Name'  
        );
        return $f;
    }

    function query() {
        $query = "select " . $this->selectField() . "
                    from " . $this->tableName()." a 
                  join location b on b.location_id=a.location_id";

        return $query;
    }

    function whereQuery($idunit) {
       $wer = "";

       if($idunit!=null){

            $wer .= " and a.idunit = ".$idunit." ";
       }
         
        return " a.deleted = 0 $wer";
    }

    function orderBy() {
        return "";
    }

    function updateField() { 

        $data = array(
            'polytpe_id' => $this->m_data->getPrimaryID2($this->input->post('polytpe_id'),'poly_type', 'polytpe_id'),
            'polytpe_name' => $this->input->post('polytpe_name'),
            'polytpe_desc' => $this->input->post('polytpe_desc'),
            'location_id' => $this->input->post('location_id'),
            'status' => $this->input->post('status'),
            
        );

        return $data;
    }

}

?>