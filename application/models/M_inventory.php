	<?php

class M_inventory extends CI_Model {

	function get($query=null,$id=null,$idunit=null,$inventory_class_id=null,$is_sellable=null,$is_purchasable=null,$idinventorycat=null,$product_location_id=null,$business_id=null){
		$wer = null;

		if($query!=null){
			$wer.= " AND (a.product_name like '%".$query."%' OR a.product_name like '%".strtolower($query)."%' OR
						 	a.product_name like '%".ucwords($query)."%' OR a.product_name like '%".strtoupper($query)."%' OR
						 	a.no_barcode like '%".ucwords($query)."%' OR a.no_barcode like '%".strtoupper($query)."%' OR
						 	a.no_sku like '%".ucwords($query)."%' OR a.no_sku like '%".strtoupper($query)."%')";
		}

		if($id!=null){
			$wer.= " AND a.product_id = $id ";
		}

		
		if($inventory_class_id!=null){
			// echo "string";
			$wer.= " AND a.inventory_class_id = $inventory_class_id ";
		}

		if($is_sellable !=null){
			
			$wer.= " AND a.is_sellable = $is_sellable ";

		}

		if($is_purchasable !=null){
				
			$wer.= " AND a.is_purchasable = $is_purchasable ";

		}

		if($idinventorycat !=null){
				
			$wer.= " AND a.idinventorycat = $idinventorycat ";

		}

		if($product_location_id !=null){
				
			$wer.= " AND a.location_id = $product_location_id ";
		}

		if($business_id !=null){
			$value = explode(',',$business_id);
			$x = 1;
			$wer .= " AND (";
			
			foreach ($value as $key => $v) {
				$wer.= " a.business_id = $v";
				
				if($x!=count($value)){
					$wer .=" OR ";
				}			
				$x++;
			}	
		
			$wer .=")";

		}

		$sql = ("SELECT a.product_id,a.product_name,a.product_no,a.product_description,
				a.cost_price,a.buy_price,a.wholesale_price,a.retail_price,a.weight,a.product_image,
				a.stock_available,a.stock_commited,a.stock_incoming,a.stock_max_online,a.status,
				a.available_on_pos,a.only_for_member,a.no_sku,a.no_barcode,a.no_supplier,a.is_sellable,
				a.is_purchasable,a.is_taxable,
				b.namecat,b.idinventorycat,c.brand_name,d.namesupplier,a.idsupplier,
				a.idunit,a.idbrand,a.product_type_id,a.member_price,
				a.retail_price as price,wholesale_price_member,retail_price_member,inventory_class_id,
				case
					when inventory_class_id = 1 then 'Barang'
					when inventory_class_id = 2 then 'Jasa'
					else 'Undefined'
				end as inventory_class_name,a.business_id,
			    -- e.business_name,
				coa_sales_id,f.accname as coa_sales_name,
				coa_purchase_id,g.accname as coa_purchase_name,
				coa_tax_sales_id,
				coa_tax_purchase_id,is_consignment,
				consignment_base_price,consignment_owner_id,
				-- consignment_owner_type_id,
				a.expired_date,
			    a.product_unit_id,a.product_balance,a.coa_inventory_id,
			    k.accname as coa_inventory_name,a.location_id,l.location_name,j.product_unit_name,j.product_unit_code
				from product a
				left join inventorycat b ON a.idinventorycat = b.idinventorycat
				left join brand c ON a.idbrand = c.idbrand
				left join supplier d ON a.idsupplier = d.idsupplier
				left join account f ON a.coa_sales_id = f.idaccount and a.idunit = f.idunit
				left join account g ON a.coa_purchase_id = g.idaccount and a.idunit = g.idunit
				left join product_unit j ON a.product_unit_id = j.product_unit_id
				left join account k on a.coa_inventory_id=k.idaccount and a.idunit = k.idunit
				left join product_location l on a.location_id=l.product_location_id 
				where true and a.deleted = 0 and a.idunit = $idunit $wer order by a.product_id desc");

		$q = $this->db->query("$sql	".$this->common_lib->build_limit_offset());
		$qtotal = $this->db->query("$sql");

		$data = array();
		$i=0;
		foreach ($q->result_array() as $key => $value) {
			
			$data[$i] = $value;
			$data[$i]['product_name_with_code'] = $value['no_sku'].' - '.$value['product_name'];
			$i++;
		}

		return array('total'=>$qtotal->num_rows(),'data'=>$data);
	}

	function get_type($query=null,$idunit){
		$wer = null;

		if($query!=null){
			$wer.= " AND (a.product_type_name like '%".$query."%' OR a.product_type_name like '%".strtolower($query)."%' OR
						 	a.product_type_name like '%".ucwords($query)."%' OR a.product_type_name like '%".strtoupper($query)."%')";
		}

		// if($id!=null){
		// 	$wer.= " AND a.product_type_desc = $product_type_desc ";
		// }

		$q = $this->db->query("select a.product_type_id,product_type_name,product_type_desc
			from product_type a
			where true and a.idunit = $idunit and a.deleted = 0 $wer");
		return $q->result_array();
	}

	function get_stock($startdate,$enddate,$id_product,$idunit){
		
		if($startdate!=null && $enddate!=null && $id_product!=null){
			$filter = " where b.idunit=".$idunit." and (datein between '".$this->date_filter($startdate)." 00:00:00 ' and '".$this->date_filter($enddate)." 23:59:59') and a.product_id=$id_product";
		}else if($startdate==null && $enddate==null && $id_product!=null){
			$filter = " where b.idunit=".$idunit." and a.product_id=$id_product";
		}else if($startdate!=null && $enddate!=null && $id_product==null){
			$filter = " where b.idunit=".$idunit." and (datein between '".$this->date_filter($startdate)." 00:00:00 ' and '".$this->date_filter($enddate)." 23:59:59')";
		}else{
			$filter = " where b.idunit=".$idunit;
		}

		$q = $this->db->query("SELECT a.stock_history_id,a.product_id,a.type_adjustment,a.no_transaction,a.datein,a.notes,
							  a.datein,a.current_qty,a.trx_qty,a.new_qty,b.product_name,b.no_sku,b.no_barcode,b.business_id
							  from stock_history a 
							  join product b ON b.product_id=a.product_id
							  -- join business c on b.business_id=c.business_id 
							  $filter and type_adjustment !=10 order by stock_history_id DESC");
		return $q;
	}

	function remove_stock_history($reference_id){
		$this->db->where('reference_id',$reference_id);
		$this->db->delete('stock_history');
	}

	function date_filter($d){

		$tgl=explode('T', $d);

		return backdate2($tgl[0]);
	}

	function stock_opname($idunit,$startdate=null,$enddate=null,$query=null){
		$where = "TRUE AND a.deleted=0 and a.idunit=$idunit";

		if($startdate != null && $enddate != null){
			$where .=" AND (a.datein between '".$startdate." 00:00:00'  and '".$enddate." 23:59:59')";
		}

		if($query!=null){
			$where .=" AND (a.opname_number like '%".$query."%' OR a.opname_number like '%".strtolower($query)."%' OR
						 	a.opname_number like '%".ucwords($query)."%' OR a.opname_number like '%".strtoupper($query)."%')";
		}

		$sql = "SELECT a.opname_number,a.stock_opname_id,sum(b.adjustment_stock) as adjustment_stock, 
				sum(b.variance) as variance,a.userin,a.approved_by,a.datein,
				c.realname as userin_name,a.status,d.realname as approved_name
				FROM stock_opname a
				LEFT JOIN stock_opname_item b ON b.stock_opname_id=a.stock_opname_id
				LEFT JOIN sys_user c ON c.user_id=a.userin
				LEFT JOIN sys_user d ON d.user_id=a.approved_by
				LEFT JOIN product g ON g.product_id=b.product_id 
				where g.idunit=$idunit and $where
				GROUP BY a.stock_opname_id,userin_name,a.userin,a.approved_by,approved_name,a.opname_number
				ORDER BY a.stock_opname_id DESC";

		$q  = $this->db->query($sql);

		$i=0;
		$data=[];
		foreach ($q->result_array() as $key => $value) {
			# code...
			$datein = explode(' ', $value['datein']);
			$value['datein'] = $datein[0];
			$data[$i] = $value;
			$i++;

		}

		return $data;
	}

	function stock_opname_item($idunit,$stock_opname_id=null,$query=null){

		$where = "TRUE and a.deleted=0 and b.idunit=$idunit";

		if($stock_opname_id!=null){
			$where .= " AND a.stock_opname_id=$stock_opname_id";
		}

		if($query!=null){
			$where .=" AND ((b.no_sku like '%".$query."%' OR b.no_sku like '%".strtolower($query)."%' OR
						 	b.no_sku like '%".ucwords($query)."%' OR b.no_sku like '%".strtoupper($query)."%') OR (b.no_barcode like '%".$query."%' OR b.no_barcode like '%".strtolower($query)."%' OR
						 	b.no_barcode like '%".ucwords($query)."%' OR b.no_barcode like '%".strtoupper($query)."%') OR (b.product_name like '%".$query."%' OR b.product_name like '%".strtolower($query)."%' OR
						 	b.product_name like '%".ucwords($query)."%' OR b.product_name like '%".strtoupper($query)."%'))";
		}

		$sql = "SELECT b.product_id,b.no_sku,b.no_barcode,a.stock_opname_id,b.stock_available,a.current_stock,b.product_name,c.location_name,
				a.adjustment_stock, a.variance,a.notes,b.retail_price
			    FROM stock_opname_item a
				INNER JOIN product b ON b.product_id=a.product_id
				LEFT JOIN product_location c ON c.product_location_id=b.location_id
				where $where";

		$q  = $this->db->query($sql);

		$i=0;
		$data=[];
		foreach ($q->result_array() as $key => $value) {
			# code...
			$data[$i] = $value;
			$i++;

		}

		return $data;
	}

	function get_product_list($id=null,$idunit,$query=null){

		$where = "";

		if ($idunit!=null) {
			# code...
			$where .="a.idunit=$idunit and a.deleted=0";
		}

		if($id!=null){
			$where .= " and product_id not in (SELECT a.product_id FROM stock_opname_item a where true and stock_opname_id=$id and a.deleted=0)";
		}
		// else{
		// 	$where .= " and product_id in (SELECT a.product_id FROM stock_opname_item a where true and a.deleted=0)";
		// }

		if($query!=null){
			$where .=" AND ((a.no_sku like '%".$query."%' OR a.no_sku like '%".strtolower($query)."%' OR
						 	a.no_sku like '%".ucwords($query)."%' OR a.no_sku like '%".strtoupper($query)."%') OR (a.no_barcode like '%".$query."%' OR a.no_barcode like '%".strtolower($query)."%' OR
						 	a.no_barcode like '%".ucwords($query)."%' OR a.no_barcode like '%".strtoupper($query)."%') OR (a.product_name like '%".$query."%' OR a.product_name like '%".strtolower($query)."%' OR
						 	a.product_name like '%".ucwords($query)."%' OR a.product_name like '%".strtoupper($query)."%'))";
		}

		$sql = "SELECT a.product_id,a.product_name,b.location_name,a.product_no,a.product_description,a.cost_price,
				a.buy_price,a.retail_price,a.stock_available,a.stock_commited,a.stock_incoming,a.stock_max_online,a.no_sku,
				a.no_barcode,a.product_balance,b.product_location_id
				FROM product a
				LEFT JOIN product_location b ON b.product_location_id=a.location_id
				where $where 
				-- and a.inventory_class_id=1
				-- 	GROUP BY b.product_id,a.stock_opname_id,a.variance,a.current_stock,a.adjustment_stock,c.type_adjustment
				";

		$q   = $this->db->query($sql);

		$data=[];
		$i=0;

		foreach ($q->result_array() as $key => $value) {
			# code...
			$data[$i] = $value;
			$i++;
		}

		return $data;
	}


	function save_stock_opname_journal($amount,$date,$stock_opname_id,$idunit,$opname_number,$userin){

		$tgl = explode("-", $date);	
		$date_reverse =$tgl[2].'-'.$tgl[1].'-'.$tgl[0];
		$idjournal = $this->m_data->getPrimaryID2(null,'journal','idjournal');

		$d = array(
            'idjournal' => $idjournal,
            'idjournaltype' => 6,
            'nojournal' => $opname_number,
            'datejournal' => $date,
            'memo' => "Jurnal Stok Opname tanggal $opname_number",
            'totaldebit' => $amount,
            'totalcredit' => $amount,
            'year' => $tgl[0],
            'month' => $tgl[1],
            'userin' => $userin,
            'usermod' =>$userin,
            'datein' => date('Y-m-d H:m:s'),
            'datemod' => date('Y-m-d H:m:s'),
            'idunit' => $idunit
        );
        
        $this->db->insert('journal', $d);
		
		//Akun persedian		 
		$total =0;
		$q = $this->db->query("SELECT b.idunit,a.variance ,b.product_id,b.buy_price , 
							   (buy_price*variance) as total,c.type_adjustment,b.coa_inventory_id
							   FROM stock_opname_item a
                               INNER JOIN product b on b.product_id=a.product_id
                               INNER JOIN stock_history c on c.product_id=b.product_id and c.stock_opname_id=a.stock_opname_id 
                               INNER JOIN account d on d.idaccount=b.coa_inventory_id
                               WHERE a.stock_opname_id=$stock_opname_id and b.idunit=$idunit
                               GROUP BY b.product_id,a.stock_opname_id,c.type_adjustment,b.coa_inventory_id,variance");

		foreach ($q->result() as $key => $value) {
			# code...
			$total +=$value->{'total'};

			if($value->{'type_adjustment'}==4){
				
				//debit - persediaan
				$idaccount = $value->{'coa_inventory_id'};
				$amount = $value->{'total'};
	           
				$curBalanceD = $this->m_account->getCurrBalance($idaccount, $idunit);
	            $newBalanceD = $curBalanceD + $amount;
	           
	            $ditem = array(
				     'idjournal' => $idjournal,
	                'idaccount' => $idaccount,
	                'debit' => $amount,
	                'credit' => 0,
	                'lastbalance' => $curBalanceD,
	                'currbalance' => $newBalanceD
	            );
	            $this->db->insert('journalitem', $ditem);
	            $this->m_account->saveNewBalance($idaccount, $newBalanceD, $idunit,$userin);
	            $this->m_account->saveAccountLog($idunit,$idaccount,0,$amount,$date,$idjournal,$userin);
			}
			//kredit - persedian
			if($value->{'type_adjustment'}==5){
				$idaccount = $value->{'coa_inventory_id'};
				$amount = $value->{'total'}*(-1);
				
				$curBalanceK = $this->m_account->getCurrBalance($idaccount, $idunit);
	            $newBalanceK = $curBalanceK - $amount;

	            $ditem = array(
		            'idjournal' => $idjournal,
		            'idaccount' => $idaccount,
		            'debit' => 0,
		            'credit' => $amount,
		            'lastbalance' => $curBalanceK,
		            'currbalance' => $newBalanceK
		        );
		        $this->db->insert('journalitem', $ditem);
		        $this->m_account->saveNewBalance($idaccount, $newBalanceK, $idunit,$userin);
		        $this->m_account->saveAccountLog($idunit,$idaccount,$amount,0,$date,$idjournal,$userin);

			}
		}

		//Akun penyesuaian persediaan
		$idaccount = $this->m_data->getIdAccount(37, $idunit);
        $amount = $total;

		if($total < 0){
			//Penyesuaian persedian - debit
			$amount = $total*(-1);
			$curBalanceD = $this->m_account->getCurrBalance($idaccount, $idunit);
	        $newBalanceD = $curBalanceD + $amount;
	           
	        $ditem = array(
				'idjournal' => $idjournal,
	            'idaccount' => $idaccount,
	            'debit' => $amount,
	            'credit' => 0,
	            'lastbalance' => $curBalanceD,
	            'currbalance' => $newBalanceD,
	        );
	        $this->db->insert('journalitem', $ditem);
	        $this->m_account->saveNewBalance($idaccount, $newBalanceD, $idunit,$userin);
	        $this->m_account->saveAccountLog($idunit,$idaccount,0,$amount,$date,$idjournal,$userin);
		}else{
			// echo $amount;
			//Penyesuaian persedian - kredit
			$curBalanceK = $this->m_account->getCurrBalance($idaccount, $idunit);
	       	// echo $this->db->last_query();
	        $newBalanceK = $curBalanceK - $amount;
	        // echo $newBalanceK;

	        $ditem = array(
				'idjournal' => $idjournal,
	            'idaccount' => $idaccount,
	            'debit' => 0,
	            'credit' => $amount,
	            'lastbalance' => $curBalanceK,
	            'currbalance' => $newBalanceK
	        );
	        $this->db->insert('journalitem', $ditem);
	        $this->m_account->saveNewBalance($idaccount, $newBalanceK, $idunit,$userin);
	        $this->m_account->saveAccountLog($idunit,$idaccount,$amount,0,$date,$idjournal,$userin);
		}

		// return $idjournal;
		$this->db->where('stock_opname_id',$stock_opname_id);
		$this->db->update('stock_opname',array(
			'idjournal_stock_opname'=>$idjournal
		));
	}

	function product_image($product_image_id=null,$product_id=null,$idunit){

		$wer="";

		if($idunit!=null){

			$wer .=" b.idunit=$idunit";
		}

		if($product_image_id!=null){
			$wer .=" and a.product_image_id=$product_image_id";	
		}

		if($product_id!=null){
			$wer .=" and a.product_id=$product_id";	
		}		

		$sql ="SELECT 
					 a.product_image_id,
					 a.product_id,
					 a.image_thumbnail,
					 a.image_fullsize,
					 a.image_caption,
					 a.order_by
				FROM 
					 product_image	a
			    INNER JOIN product b ON b.product_id=a.product_id
			    WHERE
			    	 $wer and a.deleted=0";
		$data=[];
		$i=0;

		$q = $this->db->query($sql);

		foreach ($q->result_array() as $key => $value) {
			# code...
			$data[$i]=$value;
			$i++;
		}	    	 

		return $data;
	}

	function data_transfer_stock($idunit,$transfer_stock_id=null,$query=null,$startdate=null,$enddate=null){

		$wer="";

		if($idunit){
			$wer .="a.deleted=0 and a.idunit=$idunit";
		}

		// $transfer_stock_id=null,$query=null,$startdate=null,$enddate=null
		if($transfer_stock_id!=null){
			$wer .=" AND a.transfer_stock_id=$transfer_stock_id";
		}

		if($query!=null){
			$wer .=" AND (a.transfer_stock_no like '%".$query."%' OR a.transfer_stock_no like '%".strtolower($query)."%' OR
						 	a.transfer_stock_no like '%".ucwords($query)."%' OR a.transfer_stock_no like '%".strtoupper($query)."%')";
		}

		if($startdate != null && $enddate != null){
			$wer .=" AND (a.transfer_stock_date between '$startdate' and '$enddate')";
		}

		$sql = "SELECT 
					 a.transfer_stock_id,
					 a.transfer_stock_no,
					 a.transfer_stock_date,
					 a.transfer_stock_notes,
					 a.status,
					 a.deleted,
					 a.datein,
					 a.userin,
					 a.datemod,
					 a.usermod,
					 a.bussiness_origin_id,
					 a.bussiness_destination_id,
					 COALESCE(NULLIF (sum(b.transfer_qty), NULL), 0) as transfer_qty
				FROM 
					 transfer_stock a 
			    LEFT JOIN transfer_stock_detail b ON b.transfer_stock_id=a.transfer_stock_id
			    WHERE $wer
			    group by a.transfer_stock_id
			    order by a.transfer_stock_id desc";

		$q = $this->db->query($sql);		    
		$data =[];
		$i = 0;	    

		foreach ($q->result_array() as $key => $value) {
			# code...
			$data[$i] = $value;
			$i++;
	    }	    

	    return $data;
	}

	function data_stock_detail($idunit,$transfer_stock_id){
		$wer="";

		if($idunit){
			$wer .="b.idunit=$idunit";
		}

		// $transfer_stock_id=null,$query=null,$startdate=null,$enddate=null
		if($transfer_stock_id!=null){
			$wer .="AND a.transfer_stock_id=$transfer_stock_id";
		}

		$sql = "SELECT 
					  a.transfer_qty,
					  a.transfer_stock_id ,
					  a.product_id ,
					  a.current_qty ,
					  a.transfer_qty ,
					  a.notes,
					  a.location_origin_id ,
					  a.current_destination_id,
					  b.product_name,
					  b.no_sku,
					  b.no_barcode 
				FROM  
					 transfer_stock_detail a 
			    INNER JOIN product b ON b.product_id=a.product_id
			    WHERE $wer
			    order by a.transfer_stock_id desc";

		$q = $this->db->query($sql);		    
		$data =[];
		$i = 0;	    

		foreach ($q->result_array() as $key => $value) {
			# code...
			$data[$i] = $value;
			$i++;
	    }	    

	    return $data;
	}

	function product_composition_data($product_id=null,$composition_type=null,$query=null){

		$wer = " TRUE";

		if($product_id!=null){
			$wer .=" and A.product_id=$product_id";
		}

		if($composition_type!=null){
			$wer .=" and A.composition_type=$composition_type";
		}

		if($query!=null){
			$wer .=" and (B.no_sku like '%".$query."%' or B.no_sku like '%".strtolower($query)."%' 
				         or B.no_sku like '%".ucwords($query)."%' or B.no_sku like '%".strtoupper($query)."%')";
		}

        $q = $this->db->query("SELECT 
                                    A.product_id,
									A.product_composition_id,
									A.composition_no,
									A.qty,
									A.product_unit_id,
									A.notes,
									A.datein,
									A.userin,
									A.datemod,
									A.usermod,
									B.no_sku,
									B.product_name,
									C.product_unit_code,
									A.fee_amount
                                FROM
                                    product_composition
                                    A 
                                    INNER JOIN product B ON B.product_id = A.product_composition_id
                                    LEFT JOIN product_unit C ON C.product_unit_id = A.product_unit_id and B.product_unit_id=C.product_unit_id
                                WHERE
                                $wer
                                ORDER BY product_composition_id ASC
                                ");

		return $q;
	}

	function get_business_Unitname($business_id){

		$resp =$this->rest_client->get('business/datas?business_id='.$business_id,[
			'auth'=>[COOP_APIKEY,''],
			'http_errors'=>false
		]);

		// echo $resp->getBody();
		$result = json_decode($resp->getBody());
		// print_r($result->rows);
		return $rows = $result->rows[0]->{'business_name'};

	}
}
?>