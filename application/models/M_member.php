<?php

class M_member extends CI_Model {

	function profile($id_member){
		$q = $this->db->query("select a.id_member,a.idunit,a.business_id,a.no_member,a.no_id,a.member_name,a.address,a.telephone,a.handphone,a.email,a.website,a.city,a.postcode,a.country,a.birth_location,a.birth_date,a.photo_image,a.sign_image,a.notes,
								b.member_type_name,c.namaunit,
								case when marital_status = 1 then 'Kawin'
								when marital_status = 2 then 'Belum Kawin'
								end as marital_status,
								case when a.status = 1 then 'Pending'
								when a.status = 2 then 'Active'
								when a.status = 3 then 'Inactive'
								end as status,a.activated_date
								from member a
								left join member_type b ON a.id_member_type = b.id_member_type
								join unit c oN a.idunit = c.idunit
								where a.id_member = $id_member order");
		$d = $q->result_array()[0];

		if($d['photo_image']==null){
			$d['photo_image_url'] = base_url().'uploads/images.png';			
		} else {
			$d['photo_image_url'] = base_url().'uploads/'.$d['photo_image'];			
		}

		return $d;
	}
	function check_id($no_member){
		$q = $this->db->query("select no_member,id_member from member where no_member = '".$no_member."' ");
        if($q->num_rows()>0){
            $r = $q->row();
            return array('success'=>true,'id_member'=>$r->id_member,'message'=>'member is valid');
        } else {
            return array('success'=>false,'message'=>'member not found');
        }
	}

	function update_member_detail($data){

		$header = array(
			"nama_ibu_kandung" => $data["nama_ibu_kandung"],
			"alamat_ibu_kandung" => $data["alamat_ibu_kandung"],
			"tgl_lahir_ibukandung" => backdate2($data['tgl_lahir_ibukandung']),
			"nama_ahli_waris" => $data["nama_ahli_waris"],
			"alamat_ibu_kandung"=> $data['alamat_ibu_kandung'],
			"no_id_ahli_waris" => $data["no_id_ahli_waris"],
			"hubungan_ahli_waris" => $data["hubungan_ahli_waris"],
			"notlp_ahli_waris" => $data["notlp_ahli_waris"],
			"no_rekening" => $data["no_rekening"],
			"nama_rekening" => $data["nama_rekening"],
			"nama_bank" => $data["nama_bank"],
			"lahir_ahli_waris"=> $data["lahir_ahli_waris"]
        );

		$this->db->where('id_member',$data['id_member']);
        $this->db->update('member', $header);

        if($this->db->affected_rows()<=0){
            return false;
        } else{
            return true;
        }
	}

	function total_balance_saving($id_member_saving=null,$saving_type=null){
		$wer = null;

		if($id_member_saving!=null){
			$wer.=" and a.id_member_saving = $id_member_saving";
		}

		if($saving_type!=null){
			$wer.=" and b.saving_type = $saving_type";
		} else {
			//simpanan lainnya
			$wer.=" and (b.saving_type = 1 and b.saving_type = 2)";
		}

		$q = $this->db->query("select sum(COALESCE( NULLIF(a.balance,0),0)) as balance
								from member_saving a
								join saving_type b ON a.id_saving_type = b.id_saving_type
								join member c ON a.id_member = c.id_member
								where a.id_member_saving is not null $wer and c.idunit = ".$this->user_data->idunit." ");
		if($q->num_rows()>0){
			return $q->result_array()[0]['balance'];
		} else {
			return false;
		}
	}

	function member_saving($id_member,$saving_type=null,$startdate=null,$enddate=null){
		$wer = null;
		if($saving_type!=null){
			$wer.=" and b.saving_type = $saving_type";
		} else {
			//simpanan lainnya
			$wer.=" and (b.saving_type = 1 and b.saving_type = 2)";
		}
		
		$q = $this->db->query("select a.id_member,a.id_member_saving,a.date_activated,
				COALESCE( NULLIF(a.amount,0),0) as amount,a.no_account,
				COALESCE( NULLIF(c.total_balance,0),0) as balance,b.saving_name
				from member_saving a
				join saving_type b ON a.id_saving_type = b.id_saving_type
				join (
					select id_member_saving,sum(amount) as total_balance
					from member_saving_trx
					where id_member_saving = 358 and deleted = 0 and trx_type = 1 and (trx_date between '".$startdate."' and '".$enddate."')
					group by id_member_saving
				) c ON a.id_member_saving = c.id_member_saving
				where a.id_member = $id_member and a.id_member_saving is not null $wer");
		if($q->num_rows()>0){
			return $q->result_array()[0];
		} else {
			return false;
		}
	}

	function sales($id_member){
		$q = $this->db->query("select count(*) as total_trx,
								COALESCE( NULLIF(sum(paidtoday),0),0) as total_amount
								from sales a
								where id_member = $id_member and a.status = 5 and display is null");
		if($q->num_rows()>0){
			return $q->result_array()[0];
		} else {
			return false;
		}
	}

	function saving_list($id_member,$no_account=null){
		$wer = null;

		if($no_account!=null || $no_account!=''){
			$wer.= " and a.no_account = '".$no_account."' ";
		}
		$q = $this->db->query("select a.id_saving_type,a.no_account,balance,b.saving_name,b.saving_code,case when a.status = 1 then 'Aktif' when a.status = 2 then 'Non Aktif' end as status,a.id_member_saving
							from member_saving a
							join saving_type b ON a.id_saving_type = b.id_saving_type
							where id_member = $id_member $wer and b.deleted = 0
							order by id_saving_type");
		if($q->num_rows()>0){
			return $q->result_array();
		} else {
			return false;
		}
	}

	function saving_detail($id_member_saving){
		$q = $this->db->query("select a.id_member_saving,a.id_saving_type,a.id_member,COALESCE(NULLIF(a.balance ,NULL) , 0 ) as balance,a.date_registered,a.date_activated,a.status,a.approvedby_id,a.amount,a.interest,a.reg_admin_fee,a.startdate,a.enddate,a.opening_notes,a.no_account,a.period,
				b.saving_name,b.saving_type,b.saving_category,c.no_member,c.member_name,d.username as userin,e.username as user_approved,c.photo_signature,c.photo_image,c.address,case when a.status = 1 then 'Aktif' when a.status = 2 then 'Non Aktif' end as status
				from member_saving a
				join saving_type b ON a.id_saving_type = b.id_saving_type
				join member c ON a.id_member = c.id_member
				join sys_user d ON a.userin = d.user_id
				left join sys_user e ON a.approvedby_id = e.user_id
				where a.deleted = 0 and c.idunit = 12 and a.status = 1 and a.id_member_saving = $id_member_saving
				order by a.datein desc");
		return $q->result_array()[0];
	}

	function saving_transaction($id_member,$no_account=null){
		$wer = null;
		if($no_account!=null){
			$wer.=" and e.no_account = '".$no_account."' ";
		}
		$q = $this->db->query("select e.id_member,id_saving_trx,e.no_account,a.trx_destination,a.remarks,a.id_member_saving,a.datein,tellerid,
								approvedby,a.amount,fee_adm,a.status,trx_type,id_saving_type_dest,id_member_dest,remarks,trx_time_type,trx_date,d.saving_name
								from member_saving_trx a 
								join member_saving e ON a.id_member_saving = e.id_member_saving
								join member b ON e.id_member = b.id_member
								left join sys_user c ON a.userin = c.user_id
								join saving_type d ON e.id_saving_type = d.id_saving_type  
								WHERE TRUE AND a.deleted = 0 and e.id_member = $id_member and a.status = 2 $wer
								ORDER BY a.id_saving_trx desc ");
		if($q->num_rows()>0){
			$d = $q->result_array();
			$data = array();
			$i=0;
			foreach ($d as $key => $value) {
				// print_r($value);
				$data[$i] = $value;
				$data[$i]['str_time'] = nicetime($value['trx_date']);
				$i++;
			}
			return $data;
		} else {
			return false;
		}
	}

	function transaction_list($id_member,$no_account=null){
		$wer = null;
		if($no_account!=null){
			$wer.=" and e.no_account = '".$no_account."' ";
		}
		$q = $this->db->query("select e.id_member,id_saving_trx,e.no_account,a.trx_destination,a.remarks,a.id_member_saving,a.datein,tellerid,
								approvedby,a.amount,fee_adm,a.status,trx_type,id_saving_type_dest,id_member_dest,remarks,trx_time_type,trx_date,d.saving_name
								from member_saving_trx a 
								join member_saving e ON a.id_member_saving = e.id_member_saving
								join member b ON e.id_member = b.id_member
								left join sys_user c ON a.userin = c.user_id
								join saving_type d ON e.id_saving_type = d.id_saving_type  
								WHERE TRUE AND a.deleted = 0 and e.id_member = $id_member and a.status = 2 $wer
								ORDER BY a.id_saving_trx desc ");
		if($q->num_rows()>0){
			$d = $q->result_array();
			$data = array();
			$i=0;
			foreach ($d as $key => $value) {
				// print_r($value);
				$data[$i] = $value;
				$data[$i]['str_time'] = nicetime($value['trx_date']);
				$i++;
			}
			return $data;
		} else {
			return false;
		}
	}

	function all_transaction_list($id_member,$no_account=null,$limit=null){
		$wer = null;
		if($no_account!=null){
			$wer.=" and e.no_account = '".$no_account."' ";
		}

		$add_limit = null;
		if($limit!=null){
			$add_limit = "LIMIT $limit";
		}

		//saving trx
		$q = $this->db->query("select e.id_member,id_saving_trx,e.no_account,a.trx_destination,a.remarks,a.id_member_saving,a.datein,tellerid,
								approvedby,a.amount,fee_adm,a.status,trx_type,id_saving_type_dest,id_member_dest,remarks,trx_time_type,trx_date,d.saving_name
								from member_saving_trx a 
								join member_saving e ON a.id_member_saving = e.id_member_saving
								join member b ON e.id_member = b.id_member
								left join sys_user c ON a.userin = c.user_id
								join saving_type d ON e.id_saving_type = d.id_saving_type  
								WHERE TRUE AND a.deleted = 0 and e.id_member = $id_member and a.status = 2 $wer
								ORDER BY a.id_saving_trx desc 
								$add_limit");
		if($q->num_rows()>0){
			$d = $q->result_array();
			$data = array();
			$i=0;
			foreach ($d as $key => $value) {
				// print_r($value);
				// $data[$i] = $value;
				if($value['trx_type']==1){
					$type_name = 'Setor Dana';
					$icon = 'piggy-bank.png';
				} else {
					$type_name = 'Tarik Dana';
					$icon = 'piggy-bank.png';
				}
				$data[$i]['icon'] = $icon; //piggy-bank.png, transfer.png
				$data[$i]['trx_type_name'] = $type_name;
				$data[$i]['trx_name'] = $value['saving_name'];
				$data[$i]['trx_amount'] = $value['amount'];
				$data[$i]['str_time'] = nicetime($value['trx_date']);
				$data[$i]['datein'] = $value['datein'];
				$i++;
			}
		} else {
		}

		//pembelian trx
		$q = $this->db->query("select a.idsales,a.datein,a.no_sales_order,a.totalamount
								from sales a
								where id_member = $id_member
								order by datein desc");
		if($q->num_rows()>0){
			$d = $q->result_array();
			foreach ($d as $key => $value) {
				$data[$i]['icon'] = 'cart.png'; //piggy-bank.png, transfer.png
				$data[$i]['trx_type_name'] = 'Pembelian';
				$data[$i]['trx_name'] = 'Barang';
				$data[$i]['trx_amount'] = $value['totalamount'];
				$data[$i]['str_time'] = nicetime($value['datein']);
				$data[$i]['datein'] = $value['datein'];
				$i++;
			}
			// return $data;
		} else {
			// return false;
		}

		//shu trx
		$q = $this->db->query("select total_modal_member,total_usaha_member,jasa_modal,jasa_usaha,total_shu,a.datein,b.shu_period,b.status
								from shu_member a
								join shu_generate b oN a.shu_generate_id = b.shu_generate_id
								where a.id_member = $id_member and b.status = 2
								order by datein desc");
		if($q->num_rows()>0){
			$d = $q->result_array();
			foreach ($d as $key => $value) {
				$data[$i]['icon'] = 'money.png'; //piggy-bank.png, transfer.png
				$data[$i]['trx_type_name'] = 'Penerimaan';
				$data[$i]['trx_name'] = 'SHU '.$value['shu_period'];
				$data[$i]['trx_amount'] = $value['total_shu'];
				$data[$i]['str_time'] = nicetime($value['datein']);
				$data[$i]['datein'] = $value['datein'];
				$i++;
			}
			// return $data;
		} else {
			// return false;
		}


		usort($data, function($a, $b) {
		  return new DateTime($b['datein']) <=>  new DateTime($a['datein']) ;
		});

		return $data;
	}

	function summary($idunit){

	}

	function memberPending_list($idunit,$id_member=null,$query=null,$status=null){

		$where ='';

		if($idunit !=null){
			$where .="TRUE and a.deleted=0 and a.display is null and a.idunit=$idunit";
		}

		if($query!=null){

			$where .=" and ((a.member_name like '%".$query."%' OR a.member_name like '%".strtolower($query)."%' 
					   OR  a.member_name like '%".ucwords($query)."%' OR  a.member_name like '%".strtoupper($query)."%') or (a.no_member like '%".($query)."%'))";
		}

		if($status!=null){
			$where .=" and a.status=$status";
		}
		// else{
		// 	$where .=" and a.status=1";
		// }

		if($id_member!=null){
			$where .=" and a.id_member=$id_member";
		}
		

		$sql = "SELECT a.id_member,a.idunit,a.no_member,a.no_id, a.member_name,a.user_id,a.address,a.telephone,a.handphone,a.email,a.status,a.datein,
		        -- COALESCE(NULLIF (c.opening_saving_mandatory,NULL), 2) as paid_status,
-- 						d.id_saving_trx,
						a.identity_number_image,a.birth_date,a.familycard_image,a.photo_image
				FROM member a
				INNER JOIN saving_type b on a.idunit=b.idunit
				LEFT JOIN member_saving c on c.id_member=a.id_member and b.id_saving_type=c.id_saving_type 
-- 				LEFT JOIN member_saving_trx d on d.id_member_saving=c.id_member_saving
				WHERE
				$where  
				and
				(b.saving_name='Simpanan Pokok' or b.saving_name='SIMPANAN POKOK' or b.saving_name='simpanan pokok')  
				GROUP BY a.id_member
				-- ,c.opening_saving_mandatory
		        order by a.id_member DESC
		        ";

		$query = $this->db->query($sql)->result_array();
		
		$data=[];
		$i=0;

		foreach ($query as $key => $v) {
		    # code...
			$datein = explode(" ", $v['datein']);
			if(isset($v['datein'])){
				$v['datein'] = date(' d F Y',strtotime($datein[0])).' @ '.$datein[1];
				if($v['status']==null){
					$v['status']=1;
				}	
			}

			$q = $this->db->query("SELECT a.status as paid_status FROM member_saving_trx a
								   JOIN member_saving b on b.id_member_saving=a.id_member_saving
								   JOIN saving_type c ON c.id_saving_type=b.id_saving_type
								   JOIN member d ON d.id_member=b.id_member
								   where (c.saving_name='Simpanan Pokok' or c.saving_name='SIMPANAN POKOK' or c.saving_name='simpanan pokok')  and d.id_member=".$v['id_member']."and d.deleted=0 and d.display is null and a.idunit=".$v['idunit']." 
								   and a.deleted=0")->row();

			if(isset($q->{'paid_status'})){
				$v['paid_status']=$q->{'paid_status'};
			}else{
				$v['paid_status']=1;
			}

			$data[$i]=$v;
			$i++;
		}

		return $data;        
	}

	function saving_member_detail($idunit=null,$id_member){

		$where ="TRUE and a.deleted=0 and d.deleted=0";

		if($idunit!=null){
			$where .=" and a.idunit=$idunit and d.idunit=$idunit";
		}

		if($id_member!=null){
			$where .=" and a.id_member=$id_member";
		}

		$sql = "SELECT d.id_saving_trx,c.id_member_saving,b.id_saving_type,a.id_member,b.saving_name,
				COALESCE(NULLIF (d.amount ,NULL) , 0 ) as amount,c.opening_saving_mandatory,
				d.status,d.trx_date,id_saving_trx
				FROM member a
				INNER JOIN saving_type b on a.idunit=b.idunit
				INNER JOIN member_saving c on c.id_member=a.id_member and c.id_saving_type=b.id_saving_type
				LEFT JOIN member_saving_trx d on d.id_member_saving=c.id_member_saving
				WHERE $where 
				group by a.id_member,b.id_saving_type,d.id_saving_trx,c.id_member_saving
				order by a.id_member ";

		$query = $this->db->query($sql)->result_array();
		
		$data=[];
		$i=0;

		foreach ($query as $key => $value) {
			# code...

			if($value['status']==null){
				$value['status']=1;
			}
			$value['amount']=str_replace('.00', '', $value['amount']);
			$data[$i]=$value;
			$i++;
		}

		return $data;
	}

	function data_member_familly($member_id=null,$idunit,$query=null){

		$wer = " a.deleted=0";

		if($idunit!=null){
			$wer .=" AND b.idunit=$idunit";
		}

		if($member_id!=null){
			$wer .=" AND a.member_id=$member_id";
		}

		if($query!=null){
			$wer .=" AND (a.family_name like '%".$query."%' OR a.family_name like '%".strtolower($query)."%' 
					 OR  a.family_name like '%".ucwords($query)."%' OR  a.family_name like '%".strtoupper($query)."%')";
		}

		$sql = "SELECT 
					   a.member_family_id,
					   a.relationship_type,
					   a.member_id,
					   a.family_name,
					   a.family_address,
					   a.family_phone,
					   a.deleted,
					   a.datein,
					   a.userin,
					   a.datemod,
					   a.usermod
				FROM   
				       member_family a 
				INNER JOIN member b ON b.id_member=a.member_id
				WHERE
				      $wer";
		
		$q = $this->db->query($sql);		      
		// echo $sql;
		$data=[];
		$i=0;

		foreach ($q->result_array() as $key => $value) {
			# code...
			$data[$i] = $value;
			$i++;
		}

		return $data;		      	   
	}
}
?>