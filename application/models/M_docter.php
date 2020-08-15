<?php
class M_docter extends CI_Model{
	
	public function datas($group_id,$staff_id=null,$query=null){

		$wer = "a.deleted=0";

		if($group_id!=null){
			
			$wer .=" and a.group_id=$group_id";
		}

		if($staff_id!=null){
			
			$wer .=" and a.staff_id=$staff_id";
		}

		if($query!=null){
			$wer .=" and ((a.staff_number like'%".$query."%' or a.staff_number like '%".strtolower($query)."%' or a.staff_number like '%".ucwords($query)."%' 
				     or a.staff_number like '%".strtoupper($query)."%') or  (a.staff_name like'%".$query."%' or a.staff_name like '%".strtolower($query)."%' 
				     or a.staff_name like '%".ucwords($query)."%' 
				     or a.staff_name like '%".strtoupper($query)."%'))";
		}

		$sql = "SELECT a.staff_id,
					   a.group_id,
					   a.user_id,
					   a.staff_name,
					   a.staff_address,
					   a.staff_mobilephone,
					   a.staff_email,
					   a.staff_whatsapp,
					   a.staff_photo,
					   a.polytpe_id,
					   a.status,
					   a.fee_per_patient,
					   a.account_number,
					   a.account_name,
					   a.bank_name,
					   a.no_identity,
					   a.staff_number,
					   a.location_id,
					   a.staff_type_id,
					   c.location_name,
					   d.polytpe_name,
					   f.staff_type_name
				FROM   staff a
				JOIN sys_group b ON b.group_id = a.group_id
				LEFT JOIN location c ON c.location_id=a.location_id
				LEFT JOIN poly_type d ON d.polytpe_id=a.polytpe_id
				LEFT JOIN sys_user e ON e.user_id=a.user_id
				LEFT JOIN staff_type f ON f.staff_type_id=a.staff_type_id
				WHERE $wer
				ORDER BY a.staff_id DESC";

		$q      = $this->db->query($sql." ".$this->common_lib->build_limit_offset());
		$qtotal = $this->db->query($sql);

		return array('total'=>$qtotal,'data'=>$q);
	}

	public function doctor_schedule($docter_id=null,$schedule_id=null,$query=null){

		$wer = "";

		if($docter_id!=null){
			$wer .= " WHERE a.doctor_id = $docter_id";
		}

		if($query!=null){
			$wer .=" and ((c.day_name like'%".$query."%' or c.day_name like '%".strtolower($query)."%' or c.day_name like '%".ucwords($query)."%' 
				     or c.day_name like '%".strtoupper($query)."%'))";
		}

		if($schedule_id != null){
			$wer .= " and a.schedule_id = $schedule_id";
		}

		$sql = "SELECT 
					  a.schedule_id,
					  a.doctor_id,
					  a.day_id,
					  a.timesheet_1_start,
					  a.timesheet_1_end,
					  a.timesheet_2_start,
					  a.timesheet_2_end,
					  a.timesheet_3_start,
					  a.timesheet_3_end,
					  a.timesheet_4_start,
					  a.timesheet_4_end,
					  a.status,
					  c.day_name
			    FROM 
			    	  doctor_schedule a
			    LEFT  JOIN staff b ON a.doctor_id=b.staff_id
			    INNER JOIN day_name   c ON a.day_id=c.day_id $wer";

		$q = $this->db->query($sql);		    

	    return $q;
	}

	function data_icd($medical_record_id=null,$disease_id=null){

		$wer = "TRUE and a.deleted=0";

		if($medical_record_id!=null){
			$wer .=" and medical_record_id=$medical_record_id";
		}

		if($disease_id!=null){
			$wer .=" and disease_id=$disease_id";
		}

		$sql = "SELECT a.medical_record_id,
					   a.disease_id,
					   a.notes,
					   a.datein,
					   a.userin,
					   a.datemod,
					   a.usermod,
					   a.deleted,
					   b.disease_id,
					   b.disease_code,
					   b.disease_name,
					   b.disease_desc

				FROM   medical_record_disease a
				JOIN   disease b ON b.disease_id=a.disease_id
				WHERE $wer 
				ORDER BY a.medical_record_id DESC";

		$q = $this->db->query($sql);	

		return $q;	
	}

	function data_action($medical_record_id=null,$action_id=null){

		$wer = "TRUE and a.deleted=0";

		if($medical_record_id!=null){
			$wer .=" and medical_record_id=$medical_record_id";
		}

		if($action_id!=null){
			$wer .=" and action_id=$eaction_id";
		}

		$sql = "SELECT a.medical_record_id,
					   a.medical_action_id,
					   a.notes,
					   a.datein,
					   a.userin,
					   a.service_fee,
					   a.datemod,
					   a.usermod,
					   a.deleted,
					   b.medical_action_id,
					   b.medical_action_name,
					   b.medical_action_desc
	
				FROM   medical_record_action a
				JOIN   medical_action b ON b.medical_action_id=a.medical_action_id
				WHERE $wer 
				ORDER BY a.medical_record_id DESC";

		$q = $this->db->query($sql);	

		return $q;	
	}

	// function data_drug($medical_record_id=null,$action_id=null){

	// 	$wer = "TRUE and a.deleted=0";

	// 	if($medical_record_id!=null){
	// 		$wer .=" and medical_record_id=$medical_record_id";
	// 	}

	// 	if($action_id!=null){
	// 		$wer .=" and action_id=$action_id";
	// 	}

	// 	$sql = "SELECT a.medical_record_id,
	// 				   a.product_id,
	// 				   a.product_unit_id,
	// 				   a.notes,
	// 				   a.qty,
	// 				   a.datein,
	// 				   a.userin,
	// 				   a.datemod,
	// 				   a.subtotal,
	// 				   a.usermod,
	// 				   a.deleted,
	// 				   b.product_id,
	// 				   b.retail_price,
	// 				   b.product_name,
	// 				   b.no_sku,
	// 				   b.idinventorycat,
	// 				   c.namecat,
	// 				   d.product_unit_code	
	// 			FROM   medical_record_drug a
	// 			INNER JOIN   product b ON b.product_id=a.product_id
	// 			LEFT  JOIN inventorycat c ON c.idinventorycat = b.idinventorycat
	// 			LEFT  JOIN product_unit d ON a.product_unit_id = d.product_unit_id
	// 			WHERE  $wer and (b.business_id!=13 and b.business_id!=15)
	// 			ORDER BY a.medical_record_id DESC";
	// 	// 10,12
	// 	$q = $this->db->query($sql);
	// 	// $qtotal = $this->db->query($sql);

	// 	return $q;	
	// }

	// function data_drug_alkes($medical_record_id=null,$action_id=null){

	// 	$wer = "TRUE and a.deleted=0";

	// 	if($medical_record_id!=null){
	// 		$wer .=" and medical_record_id=$medical_record_id";
	// 	}

	// 	if($action_id!=null){
	// 		$wer .=" and action_id=$action_id";
	// 	}

	// 	$sql = "SELECT a.medical_record_id,
	// 				   a.product_id,
	// 				   a.product_unit_id,
	// 				   a.notes,
	// 				   a.qty,
	// 				   a.datein,
	// 				   a.userin,
	// 				   a.datemod,
	// 				   a.usermod,
	// 				   a.subtotal,
	// 				   a.deleted,
	// 				   b.product_id,
	// 				   b.product_name,
	// 				   b.no_sku,
	// 				   b.retail_price,
	// 				   b.idinventorycat,
	// 				   c.namecat,
	// 				   d.product_unit_code	
	// 			FROM   medical_record_drug a
	// 			INNER JOIN   product b ON b.product_id=a.product_id
	// 			LEFT  JOIN inventorycat c ON c.idinventorycat = b.idinventorycat
	// 			LEFT  JOIN product_unit d ON a.product_unit_id = d.product_unit_id
	// 			WHERE  $wer and (b.business_id=13 or b.business_id=15)
	// 			ORDER BY a.medical_record_id DESC";
	// 	// 13,15		
	// 	$q = $this->db->query($sql);
	// 	$qtotal = $this->db->query($sql);

	// 	return $q;	
	// }

	function medical_record_drug($medical_record_id=null,$product_id=null,$business_id=null){

		$wer = "TRUE and a.deleted=0";

		if($medical_record_id!=null){
			$wer .=" and medical_record_id=$medical_record_id";
		}

		if($product_id!=null){
			$wer .=" and b.product_id=$product_id";
		}

		if($business_id!=null){

			$arr_buss = explode(',', $business_id);

            $wer .=" and (";
            $i=1;
           
            foreach ($arr_buss as $key => $v) {
                $wer.=" b.business_id=$v";
                
                if($i!=count($arr_buss)){
                    $wer .=" or ";
                }
                
                $i++;
            }
           
            $wer .=")";

		}

		$sql = "SELECT a.medical_record_id,
					   a.product_id,
					   a.product_unit_id,
					   a.notes,
					   a.qty,
					   a.datein,
					   a.userin,
					   a.datemod,
					   a.usermod,
					   a.subtotal,
					   a.deleted,
					   b.product_id,
					   b.product_name,
					   b.no_sku,
					   b.retail_price,
					   b.idinventorycat,
					   c.namecat,
					   d.product_unit_code	
				FROM   medical_record_drug a
				INNER JOIN   product b ON b.product_id=a.product_id
				LEFT  JOIN inventorycat c ON c.idinventorycat = b.idinventorycat
				LEFT  JOIN product_unit d ON a.product_unit_id = d.product_unit_id
				WHERE  $wer
				ORDER BY a.medical_record_id DESC";
		// 13,15		
		$q = $this->db->query($sql);
		$qtotal = $this->db->query($sql);

		return $q;
	}

	function data_medical_record($medical_record_id=null,$patient_id=null,$doctor_id=null,$query=null,$startdate=null,$enddate=null,$medicine_status=null){

		$wer = "TRUE and a.deleted=0";

		if($medical_record_id!=null){
			$wer .= " and a.medical_record_id=$medical_record_id";
		}

		if($patient_id!=null){
			$wer .= " and a.patient_id=$patient_id";
		}

		if($doctor_id!=null){
			$wer .= " and a.doctor_id=$doctor_id";
		}

		if($medicine_status!=null && $medicine_status!=0){
			$wer .= " and a.medicine_status=$medicine_status";
		}

		if($query!=null){
			$wer .=" and ((a.medical_record_no like '%".$query."%' or a.medical_record_no like '%".strtolower($query)."%' 
				     or a.medical_record_no like '%".ucwords($query)."%' or a.medical_record_no like '%".strtoupper($query)."%')
					 or (a.receipt_number like '%".$query."%' or a.receipt_number like '%".strtolower($query)."%' 
				     or a.receipt_number like '%".ucwords($query)."%' or a.receipt_number like '%".strtoupper($query)."%')
				     or (b.patient_name like '%".$query."%' or b.patient_name like '%".strtolower($query)."%' 
				     or b.patient_name like '%".ucwords($query)."%' or b.patient_name like '%".strtoupper($query)."%')
					 or (b.patient_no like '%".$query."%' or b.patient_no like '%".strtolower($query)."%' 
				     or b.patient_no like '%".ucwords($query)."%' or b.patient_no like '%".strtoupper($query)."%')
					 or (d.staff_name like '%".$query."%' or d.staff_name like '%".strtolower($query)."%' 
				     or d.staff_name like '%".ucwords($query)."%' or d.staff_name like '%".strtoupper($query)."%'))";
		}



		if($startdate!=null && $enddate!=null){
			$wer .= " and (medical_record_date between '".str_replace('T00:00:00', '', $startdate)."' and '".str_replace('T00:00:00', '', $enddate)."')";
		}

		$sql = "SELECT a.sales_id,a.medical_record_id,a.patient_id,a.medical_record_desc,a.medical_record_no,
					   a.medical_record_date,a.doctor_id,a.receipt_number,
					   a.service_amount,a.subtotal,
				       a.nurse_id,a.medical_status,a.payment_status,a.medicine_status,a.deleted,
					   b.patient_name,a.medical_record_desc,b.patient_no,
					   case
							when b.patient_type_id = 1 then 'Anggota'
							when b.patient_type_id = 2 then 'Umum'
							when b.patient_type_id = 3 then 'Tertanggung'
							else 'Undefined'
					   end as patient_type,
					   b.patient_type_id,d.staff_name as doctor_name,a.datein,
					   a.payment_status,
					   case
					   		when payment_status =1 then 'Unpaid'
					   		when payment_status =2 then 'Paid'
					   		when payment_status =3 then 'Canceled'
					   		when payment_status =4 then 'Refunded'
					   		else 'Unpaid'
					   end as payment,b.birthday_date,
					   case
					   		when b.benefit_id_type =1 then 'Asuransi Umum'
					   		when b.benefit_id_type =2 then 'Admedika'
					   		when b.benefit_id_type =3 then 'BPJS'
					   		when b.benefit_id_type =4 then 'Kopetri' 
					   end as benefit_id,
					   g.due_date,b.member_id,c.business_name,b.address,b.no_mobile,b.no_tlp,b.np_number,b.remarks,b.divisi,b.benefit_id_type,f.patient_name as member_name
				FROM  
					   medical_record a   
			    LEFT JOIN patient b ON b.patient_id=a.patient_id
			    LEFT JOIN business c on c.business_id=b.business_id
			    LEFT JOIN staff d ON d.staff_id=a.doctor_id and d.group_id=5
			    LEFT JOIN staff e ON e.staff_id=a.nurse_id and e.group_id=4
			    LEFT JOIN patient f ON f.patient_parent_id=b.patient_id
			    -- LEFT JOIN medical_action f ON e.medical_action_id=f.medical_action_id
			    LEFT JOIN sales g ON g.idsales=a.sales_id
			    WHERE $wer 
			    GROUP BY a.patient_id,a.medical_record_id,b.patient_name,b.patient_no,b.patient_type_id,d.staff_name,e.staff_name,b.birthday_date,
			    g.due_date,b.member_id,c.business_name,b.np_number,b.benefit_id_type,f.patient_name,b.remarks,b.divisi,benefit_id,b.address,b.no_mobile,b.no_tlp
			    ORDER BY medical_record_id DESC";

		$q = $this->db->query($sql);

		return $q;     
	}

	function summary_medical_diseases($startdate=null,$enddate=null){

		$wer = " A.deleted=0 ";

		if($startdate!=null && $enddate!=null){
			$wer .= " and (A.medical_record_date between '".$startdate."' and '".$enddate."')";
		}

		$sql = "SELECT
					C.disease_name,COALESCE(NULLIF(count(a.*),null),0) as diagnosis
				FROM
					medical_record
					A 
				INNER JOIN medical_record_disease b ON b.medical_record_id = A.medical_record_id
				INNER JOIN disease C ON C.disease_id = b.disease_id
				WHERE $wer
				GROUP BY C.disease_name";

		$q = $this->db->query($sql);	

		return $q->result();	
	}

	function summary_medical_action($startdate=null,$enddate=null){

		$wer = " A.deleted=0 ";

		if($startdate!=null && $enddate!=null){
			$wer .= " and (A.medical_record_date between '".$startdate."' and '".$enddate."')";
		}


		$sql = "SELECT
					C.medical_action_name,COALESCE(NULLIF(count(a.*),null),0) as diagnosis
				FROM
					medical_record
					A 
					INNER JOIN medical_record_action b ON b.medical_record_id = A.medical_record_id
					INNER JOIN medical_action C ON C.medical_action_id = b.medical_action_id
					WHERE A.deleted=0
					GROUP BY C.medical_action_name";

		$q = $this->db->query($sql);	

		return $q->result();	
	}

	function summary_drug_usage($startdate=null,$enddate=null){

		$wer = " A.deleted=0 ";

		if($startdate!=null && $enddate!=null){
			$wer .= " and (A.medical_record_date between '".$startdate."' and '".$enddate."')";
		}


		$sql = "SELECT
					C.product_name,COALESCE(NULLIF(count(b.*),null),0) as usage
				FROM
					medical_record
					A 
				INNER JOIN medical_record_drug b ON b.medical_record_id = A.medical_record_id
				INNER JOIN product C ON C.product_id = b.product_id
				WHERE $wer
				GROUP BY C.product_name";

		$q = $this->db->query($sql);	

		return $q->result();	
	}

	function summary_pharmacy_receipt($startdate=null,$enddate=null){

		$wer = " A.deleted=0 ";

		if($startdate!=null && $enddate!=null){
			$wer .= " and (A.medical_record_date between '".$startdate."' and '".$enddate."')";
		}


		$sql = "SELECT case
							when medicine_status=1 then 'Menunggu Pembayaran'
							when medicine_status=2 then 'Dalam Proses'
							when medicine_status=3 then 'Sudah Tersedia'
							when medicine_status=4 then 'Sudah Diterima'
							when medicine_status=5 then 'Dibatalkan'
							when medicine_status=5 then 'Retur'
							else 'Dibatalkan'
						end as medical_status,count(A.medicine_status) FROM medical_record A
				where $wer
				GROUP BY A.medicine_status";

		$q = $this->db->query($sql);	

		return $q->result();	
	}

	function pharmacy_putting_data($sd=null,$nd=null,$business_id=null,$benefit_id=null){

		$wer = " TRUE and b.deleted=0";

		if($sd!=null && $nd!=null){

			$wer .= " and (medical_record_date between '".backdate2($sd)."' and '".backdate2($nd)."')";
		}

		if($business_id!=0){

			$wer .= "and c.business_id=$business_id";
		}

		if($benefit_id!=0){

			$wer .= "and c.benefit_id_type=$benefit_id";
		}
	
		$sql = "SELECT
					c.patient_id,
					c.member_id,
					b.receipt_number,
					C.patient_name,
					C.patient_no,
					-- C.patient_name AS patient_parent_id_name,
					C.np_number,
					b.medical_record_date,
					staff_name AS doctor_name,
					COALESCE ( NULLIF ( SUM ( A.subtotal * A.qty ), NULL ), 0 ) as amount
				FROM
					medical_record_drug A 
					JOIN medical_record b ON b.medical_record_id = A.medical_record_id
					JOIN patient C ON b.patient_id = C.patient_id
					JOIN staff d ON d.staff_id = b.doctor_id
					-- LEFT JOIN patient e ON e.patient_parent_id = C.patient_id 
				WHERE
					$wer
				GROUP BY
					b.receipt_number,
					C.patient_name,
					C.patient_no,
					C.patient_id,
					C.member_id,
					C.np_number,
					b.medical_record_date,
					staff_name";

		$q    = $this->db->query($sql)->result();
		
		return $q;			
	}	
}
?>