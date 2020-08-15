<?php

class M_memberloan extends CI_Model {

    function tableName() {
        return 'member_loan';
    }

    function pkField() {
        return 'id_member_loan';
    }

    function searchField() {
        $field = "member_name,loan_number";
        return explode(",", $field);
    }

    function selectField() {
        return "a.id_member_loan,a.id_member,a.id_loan_type,a.status,a.proposed_loan,a.length_loan,a.remarks,a.approved_loan,a.approved_length_loan,a.approved_interest_rate,a.other_fee,a.admin_fee,a.loan_cat_id,a.base_installment,a.interest_rate,a.monthly_installment,a.reason_type_id,a.interest_rate,b.no_member,b.member_name,c.loan_name,c.loan_name as loan_type_name,a.remarks as notes,b.address as member_address,b.telephone as member_telp,b.handphone,a.interest_amount,a.approved_interest_amount,next_payment_date,approved_base_installment,approved_monthly_installment,total_install,a.loan_number,a.total_paid,a.total_unpaid";
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
                    from " . $this->tableName()." a 
                    join member b ON a.id_member = b.id_member
                                join loan_type c ON a.id_loan_type = c.id_loan_type
                                left join (select id_member_loan,
                                        count(*) as total_install
                                            from loan_transaction
                                            where status = 2
                                            group by id_member_loan) d ON a.id_member_loan = d.id_member_loan";

        return $query;
    }

    function whereQuery($user_id=null,$unit_id=null,$startdate=null,$enddate=null) {
        $wer = null;

        if($unit_id!=null){
            $wer.= " and b.idunit = $unit_id";
        }

        if($startdate!=null && $enddate!=null){
            $wer.=" and (start_loan between '".backdate2($startdate)." 00:00:00' and '".backdate2($enddate)."  23:59:59' )";
        }

        $business_id = $this->m_data->get_business_id($this->user_data->user_id);
        if($business_id!=null){
            $wer .= " and b.business_id = $business_id";
        }

        return " a.deleted = 0 $wer";
    }

    function orderBy() {
        return " id_member_loan desc";
    }

    function updateField() { 
        $data = array(
            'id_loan_type' => $this->input->post('id_loan_type') == '' ? $this->m_data->getSeqVal('seq_saving_type') : $this->input->post('id_loan_type'),
            "idunit" => $this->session->userdata('idunit'),
            "loan_name" => $this->input->post('loan_name'),
            "loan_desc" => $this->input->post('loan_desc'),
            "interest_period" => $this->input->post('interest_period'),
            "interest_rate" => $this->input->post('interest_rate'),
            "max_length_loan" => $this->input->post('max_length_loan'),
            "min_loan_plafon" => cleardot2($this->input->post('min_loan_plafon')),
            "max_loan_plafon" => cleardot2($this->input->post('max_loan_plafon')),
            "loan_code"  => cleardot2($this->input->post('loan_code')),
            "status" => $this->input->post('status'),
            // "display" => ,
        );
        return $data;
    }

}

?>