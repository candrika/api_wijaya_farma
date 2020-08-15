<?php
class M_nurse extends CI_Model{
	
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
					   a.account_number,
					   a.account_name,
					   a.bank_name,
					   a.no_identity,
					   a.staff_number,
					   a.location_id,
					   a.staff_type_id,
					   c.location_name,
					   d.polytpe_name
				FROM   staff a
				JOIN sys_group b ON b.group_id = a.group_id
				LEFT JOIN location c ON c.location_id=a.location_id
				LEFT JOIN poly_type d ON d.polytpe_id=a.polytpe_id
				LEFT JOIN sys_user e ON e.user_id=a.user_id
				WHERE $wer
				ORDER BY a.staff_id DESC";

		$q = $this->db->query($sql." ".$this->common_lib->build_limit_offset());
		$qtotal = $this->db->query($sql);

		return array('total'=>$qtotal,'data'=>$q);
	}
}
?>