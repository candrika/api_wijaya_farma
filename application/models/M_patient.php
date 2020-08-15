<?php

class M_patient extends CI_Model{
	
	function data($idunit,$patient_id=null,$query=null,$id_member=null,$patient_type_id=null,$business_id=null){

		$wer = "TRUE and a.deleted=0";

		if($id_member!=null) {
			$wer .= " and a.member_id=$id_member";
		}

		if($patient_id!=null){
			$wer .= " and a.patient_id=$patient_id";
		}

		if($query!=null){
			$wer .=" and ((a.patient_no like'%".$query."%' or a.patient_no like '%".strtolower($query)."%' or a.patient_no like '%".ucwords($query)."%' 
				     or a.patient_no like '%".strtoupper($query)."%') or  (a.patient_name like'%".$query."%' or a.patient_name like '%".strtolower($query)."%' 
				     or a.patient_name like '%".ucwords($query)."%' 
				     or a.patient_name like '%".strtoupper($query)."%'))";
		}

		if($patient_type_id!=null){
			$wer .= " and a.patient_type_id=$patient_type_id";
		}

		if($business_id!=null){

			$wer .=" and a.business_id=$business_id";
		}

		$sql = "SELECT a.patient_id,a.birthday_date,a.no_tlp,a.no_mobile,a.address,a.email,a.no_id,a.patient_photo,a.patient_type_id,
				       a.country,a.datein,a.id_type,a.patient_name,a.remarks,a.member_id,a.patient_no,a.status,a.patient_no,a.gender_type,a.np_number,a.divisi,
				       	case
					   		when a.patient_type_id =1 then 'Anggota'
					   		when a.patient_type_id =2 then 'Pasien Umum'
					   		when a.patient_type_id =3 then 'Tertanggung'
					   		else 'Pasien Umum'
					   end as patient_type,b.business_name,b.business_id,a.patient_parent_id
					-- ,c.patient_name as parent_name
				FROM   patient a
				left join business b ON b.business_id=a.business_id
				-- inner join patient  c ON a.patient_parent_id=c.patient_parent_id   
				WHERE $wer
				GROUP BY a.patient_id,b.business_name,b.business_id,a.patient_parent_id
				-- ,c.patient_name
				ORDER BY patient_id desc";
		// echo $sql;		
		$q = $this->db->query("$sql ".$this->common_lib->build_limit_offset());
		$qtotal = $this->db->query("$sql");
		
		
		return array('total' =>$qtotal,'data'=>$q);
	}

	function summary($startdate=null,$enddate=null){
		$wer = "";

		if($startdate!=null && $enddate!=null){
			$wer .= " and (c.medical_record_date between '".$startdate." 00:00:00' and '".$enddate." 23:23:59')";
		}
		
		$q = $this->db->get_where('business',array('deleted'=>0));

		$data = [];
		$i=0;

		foreach ($q->result() as $key => $value) {
			
			$data[$i]['unit']     = $value->{'business_name'};

			$q_patient = $this->db->query("SELECT COALESCE
												(
												NULLIF (COUNT ( A.patient_name ),NULL),0) AS count
												-- COALESCE ( NULLIF ( b.business_name, NULL ), 'Pasien Umum' ) AS name_unit 
											FROM
												patient
												A LEFT JOIN business b ON A.business_id = b.business_id
												JOIN medical_record C ON C.patient_id = A.patient_id
											WHERE b.business_id=".$value->{'business_id'}." $wer
											GROUP BY
												b.business_name")->row();

			$data[$i]['patient_count'] = $q_patient->{'count'};

			$i++;
			// echo $this->db->last_query();

		}

		return $data;
	}

	function data_billing_report($startdate=null,$enddate=null,$business_id=0,$provider=0){

		$wer = "TRUE and a.deleted=0";

		if($startdate!=null && $enddate!=null){

			$wer .= " and (a.medical_record_date between '".backdate2($startdate)." 00:00:00' and '".backdate2($enddate)." 23:59:59')";
		}

		if($business_id!=0){
			$wer .=" and b.business_id=$business_id";
		}

		if($provider!=0){
			$wer .=" and b.benefit_id_type=$provider";
		}

		$q = $this->db->query("SELECT 
									a.medical_record_id,a.medical_record_no,a.medical_record_date,
									b.patient_name,b.np_number,b.member_id,b.patient_id,a.service_amount,b.relationship_type
									-- case
									-- 	when b.relationship_type = 1 then 'Suami/Istri'
									-- 	when b.relationship_type = 2 then 'Anak'
									-- 	when b.relationship_type = 3 then 'Lainnya'
									-- 	else 'Karyawan'
									-- end as relationship_type_status
							   FROM 
							   		medical_record a
							   JOIN patient b on b.patient_id=a.patient_id
							   WHERE $wer	
							   ");
		$data = [];
		$i    = 0;
		$val = null;

		foreach ($q->result() as $key => $v) {
			# code...
			$v->{'service_amount'} = $v->{'service_amount'}*1;

			$qp1 = $this->db->query("SELECT 
                                            a.patient_id,a.patient_name,b.patient_name,b.member_id
                                     FROM 
                                            patient a join patient b on a.patient_parent_id=b.patient_id 
                                     where 
                                            TRUE and (a.patient_parent_id=0 or a.patient_parent_id !=0) and a.patient_id=".$v->{'patient_id'});
            if($qp1->num_rows()>0){

                $rqp1 = $qp1->row();
                $v->{'member_name'} = $rqp1->{'patient_name'};
            }else{

                if($v->{'member_id'}!=''){
                    $v->{'member_name'} = $v->{'patient_name'};
                }
            }

            if($v->{'relationship_type'}==1){

            	$v->{'status'} ='Suami/Istri';
            }elseif($v->{'relationship_type'}==2){

            	$v->{'status'} ='Anak';
            }elseif($v->{'relationship_type'}==3) {

				$v->{'status'} ='Lainnya';
            }else{

            	if($v->{'member_id'}!=''){
					$v->{'status'} ='Karyawan';
            	}else{
					$v->{'status'} ='Pasien Umum';
            	}
            }
            
            if($v->{'member_id'}!=''){
                $v->{'no_member'} = $v->{'np_number'};
            }

            $v->{'diagnosa'}    = $this->disease($v->medical_record_id);
            $v->{'act_fee'}     = $this->medic_act($v->medical_record_id);
            $v->{'drug_fee'}    = $this->medic_drug($v->medical_record_id);
            $v->{'total_medical_amount'} =  $v->{'act_fee'}+$v->{'drug_fee'}+$v->{'service_amount'};


            $data[$i] = $v;
            $i++;
		}

		return $data;
	}

	function disease($medical_record_id){

        $val =null;
        
        $disease = $this->db->query("SELECT 
                                               a.disease_name,b.medical_record_id
                                         FROM 
                                                disease a 
                                         join medical_record_disease b on b.disease_id=a.disease_id
                                         WHERE b.medical_record_id=$medical_record_id");

        // print_r($disease->result());
        foreach ($disease->result() as $key => $v) {
            $val .= $v->{'disease_name'}.',';
        }

        return substr($val, 0,-1);
    }

    function medic_act($medical_record_id){

        $service_fee = 0;

        $disease = $this->db->query("SELECT 
                                               a.*
                                         FROM 
                                                medical_record_action a 
                                         -- join medical_record_disease b on b.disease_id=a.disease_id
                                         WHERE a.medical_record_id=$medical_record_id");

        // print_r($disease->result());
        foreach ($disease->result() as $key => $v) {
            $service_fee += $v->{'service_fee'};
        }

        return $service_fee;
    }

    function medic_drug($medical_record_id){

        $sub_total = 0;

        $disease = $this->db->query("SELECT 
                                               a.*
                                         FROM 
                                                medical_record_drug a 
                                         -- join medical_record_disease b on b.disease_id=a.disease_id
                                         WHERE a.medical_record_id=$medical_record_id");

        // print_r($disease->result());
        foreach ($disease->result() as $key => $v) {
            $sub_total += $v->{'subtotal'}*$v->qty;
        }

        return $sub_total;
    }
}
?>