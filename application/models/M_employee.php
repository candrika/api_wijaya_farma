<?php

class M_employee extends CI_Model {

	function gaji($employee_id,$period=null){
		$wer = '';
		if($period!=null){
			$wer.= " and ('".$period."' between startdate and enddate)";
		}
		$q = $this->db->query("select idtambahangaji,idemployee,tambahantype,startdate,enddate,jumlah,d.namasiklus,keterangan,b.idtambahangajitype,a.idsiklus
			from payroll_tambahangaji a
			join payroll_tambahangajitype b ON a.idtambahangajitype = b.idtambahangajitype
            join siklus d ON a.idsiklus = d.idsiklus
            where a.idemployee = $employee_id and a.deleted = 0 and b.deleted = 0 $wer");
		return $q->result_array();
	}

	function tunjangan($employee_id,$period=null){
		$wer = '';
		if($period!=null){
			$wer.= " and ('".$period."' between a.startdate and a.enddate)";
		}

		 $q = $this->db->query("select idtunjangan,a.idemployee,a.idamounttype,a.namatunjangan,a.multiplier_id,f.tambahantype,e.jumlah as multipler_amount,persen,a.startdate,a.enddate,a.jumlah,c.name as amounttype,d.namasiklus
                                from payroll_tunjangan a 
                                left join payroll_tunjangantype b ON a.idtunjtype = b.idtunjtype
                                left join amounttype c ON a.idamounttype = c.idamounttype
                                join siklus d ON a.idsiklus = d.idsiklus  
                                left join payroll_tambahangaji e ON a.multiplier_id = e.idtambahangaji 
                                left join payroll_tambahangajitype f oN e.idtambahangajitype = f.idtambahangajitype
                                WHERE TRUE AND a.idemployee = $employee_id AND  a.display is null $wer");
          return $q->result_array();
	}

	function potongan($employee_id,$period=null){
		$wer = '';
		if($period!=null){
			$wer.= " and ('".$period."' between a.startdate and a.enddate)";
		}

		$q = $this->db->query("select jenispotongan,b.namepotongan,c.name as amounttype,d.namasiklus,a.idemployee,a.startdate,a.enddate,a.totalpotongan,a.sisapotongan,a.jumlahpotongan,idpotongan,jumlahangsuran,keterangan,idemployee,a.idpotongantype,a.idsiklus
                                from payroll_potongan a 
                                join payroll_potongantype b ON a.idpotongantype = b.idpotongantype
                   				left join amounttype c ON a.idamounttype = c.idamounttype
                    			left join siklus d ON a.idsiklus = d.idsiklus
                                WHERE TRUE AND a.idemployee = $employee_id AND a.display is null $wer");
        return $q->result_array();
	}

	function tunjangan_asuransi($employee_id){
		$q = $this->db->query("select idasuransiemp,a.idasuransi,a.idemployee,b.namapremi,b.deskripsi,b.percentcompany,b.percentemployee
                    from payroll_asuransiemp a 
                    join payroll_asuransi b ON a.idasuransi = b.idasuransi  
                    WHERE TRUE AND a.idemployee= $employee_id");
		return $q->result_array();
	}

	function ptkp($employee_id){
		$q = $this->db->query("select a.idjenisptkp,b.totalptkp,b.namaptkp,b.deskripsi
								from employee a
								join payroll_jenisptkp b ON a.idjenisptkp = b.idjenisptkp
								where idemployee = $employee_id");
		if($q->num_rows()>0){
			return $q->result_array()[0];
		} else {
			return false;
		}
		
	}

	function data($query,$idunit,$employee_id=null){
		$wer = null;

		if($query!=null){
			$wer.= " AND (a.code like '%".$query."%' OR a.code like '%".strtolower($query)."%' OR
						 	a.firstname like '%".ucwords($query)."%' OR a.firstname like '%".strtoupper($query)."%' OR
						 	a.lastname like '%".ucwords($query)."%' OR a.lastname like '%".strtoupper($query)."%')";
		}

		if($employee_id!=null){
			$wer.= " AND a.idemployee = $employee_id ";
		}

		$sql = ("select pegawaitglmasuk,a.business_id,a.idunit,idemployee,code,keaktifan,a.idjenisptkp,tglresign,firstname,lastname,a.address,telephone,handphone,a.fax,a.email,a.website,a.city,a.state,a.postcode,a.country,a.notes,b.group_id,b.group_name,a.status,a.user_id,a.birth_date,a.birth_location,a.is_login,a.no_id,a.marital_status,case when (e.password = '' OR e. password is null) then '0' else '1' end as password_existed
					from employee a
					left join sys_user e ON a.user_id = e.user_id
		            left join sys_group b ON a.group_id = b.group_id             
					where true and a.deleted = 0 and a.idunit = $idunit $wer order by a.idemployee desc");
		// return $q->result_array();
		
		$q = $this->db->query("$sql	".$this->common_lib->build_limit_offset());
		$qtotal = $this->db->query("$sql");

		$data = $q->result_array();
		return array('total'=>$qtotal->num_rows(),'data'=>$data);
	}
}

?>
