<?php

class M_staff_type extends CI_Model {

    function tableName() {
        return 'staff_type';
    }

    function pkField() {
        return 'staff_type_id';
    }

    function searchField() {
        $field = "polytpe_name";
        return explode(",", $field);
    }

    function selectField() {
        return "a.staff_type_id,a.staff_type_name,a.deleted,a.userin,a.datein,a.usermod,a.datemod,a.status";
    }
    
    function fieldCek()
    {
        //field yang perlu dicek didatabase apakah sudah ada apa belum
        $f = array(
          'staff_type_name'=>'Staff Type Name'  
        );
        return $f;
    }

    function query() {
        $query = "select " . $this->selectField() . "
                    from " . $this->tableName()." a ";

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
            'staff_type_id' => $this->m_data->getPrimaryID2($this->input->post('staff_type_id'),'staff_type', 'staff_type_id'),
            'staff_type_name' => $this->input->post('staff_type_name'),
            // 'polytpe_desc' => $this->input->post('polytpe_desc'),
            'deleted' =>0,
            'status' => $this->input->post('status'),
        );

        return $data;
    }

}

?>