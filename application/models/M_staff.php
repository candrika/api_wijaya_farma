<?php
class M_staff extends CI_Model{
	
	public function datas($group_id,$staff_id=null,$query=null){

		$wer = " TRUE and A.deleted=0 and ( A.group_id != 4 AND A.group_id != 5 )";
		
		if($query!=null){
			$wer .=" and ((a.staff_number like'%".$query."%' or a.staff_number like '%".strtolower($query)."%' 
					 or a.staff_number like '%".ucwords($query)."%' 
					 or a.staff_number like '%".strtoupper($query)."%') or  (a.staff_name like'%".$query."%' or a.staff_name like '%".strtolower($query)."%' 
				     or a.staff_name like '%".ucwords($query)."%' 
				     or a.staff_name like '%".strtoupper($query)."%'))";
		}
		
		$sql= "SELECT A.staff_id,A.group_id,A.user_id,
					  A.staff_name,A.staff_address,A.staff_mobilephone,
					  A.staff_email,A.staff_whatsapp,A.staff_photo,
					  A.staff_number,A.deleted,b.email,
					  b.PASSWORD,C.group_name,A.status
				FROM
						staff
						A JOIN sys_user b ON b.user_id = A.user_id 
						AND b.group_id = A.group_id
						JOIN sys_group C ON b.group_id = C.group_id 
						AND C.group_id = A.group_id 
				WHERE 
						$wer";

		$q      = $this->db->query($sql." ".$this->common_lib->build_limit_offset());
		$qtotal = $this->db->query($sql);
		
		return array('total'=>$qtotal,'data'=>$q);
	}
}
?>