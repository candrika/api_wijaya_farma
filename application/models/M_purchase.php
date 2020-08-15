<?php

class M_purchase extends CI_Model {

	function data($idunit=null,$purchase_id=null,$option=null,$stardate=null,$enddate=null,$query=null,$status=null){
		$wer = '';

		if($idunit!=null){
			$wer.=" AND a.idunit = $idunit ";
		}

		if($purchase_id!=null){
			$wer.=" AND a.purchase_id = $purchase_id ";
		}

		if($option!=null){
			if($option=='unpaid'){
				$wer.=" AND (a.unpaid_amount != 0 and a.unpaid_amount >=0)";
			}			
		}

		if($query!=null){
			$wer.="AND (a.no_purchase_order like '%".$query."%' OR a.no_purchase_order like '%".strtolower($query)."%' OR
						 	a.no_purchase_order like '%".ucwords($query)."%' OR a.no_purchase_order like '%".strtoupper($query)."%')";
		}

		if($stardate!=null && $enddate!=null){
			$wer.=" AND (a.date_purchase between '".$stardate."' and '".$enddate."') ";
		}

		if($status!=null && $status!=0){
			$wer.=" AND a.invoice_status=$status";
		}

		$q = $this->db->query("SELECT a.purchase_id,a.idpayment,a.invoice_no,a.idtax,a.idcustomer,a.subtotal,a.freight,a.tax,a.disc,a.totalamount,a.customer_type,
								a.paidtoday,a.unpaid_amount,a.comments,a.userin,a.datein,a.status as status,a.idunit,a.date_purchase,a.no_purchase_order,
								a.include_tax,a.total_dpp,a.pos_payment_type_id,a.unpaid_amount,a.id_member,a.comments,a.total,
								d.namaunit,
								COALESCE(NULLIF(a.other_fee ,NULL) , 0 ) as other_fee,c.code,c.namesupplier,c.companyaddress,c.telephone,c.handphone,
								a.invoice_date,a.due_date,a.invoice_status,g.status as status_purchase_receipt,g.purchase_receipt_id,g.memo as memo_receipt,g.receipt_date
								from purchase a
								join supplier c ON c.idsupplier=a.idcustomer
								join unit d ON a.idunit = d.idunit
								left join purchase_receipt g ON g.purchase_id=a.purchase_id
								where true and (a.display is null or a.display = 1) and a.invoice_no is not null $wer
								order by a.purchase_id desc");
		
		$r = $q->result_array();
		$data = array();
		$i=0;
		foreach ($r as $key => $value) {
			
			$data[$i] = $value;
			$i++;
		}
		return $data;
	}

	function summary($idunit){

		//start invoice
		$q = $this->db->query("select COALESCE(NULLIF(count(*),null),0) as total_invoice
								from purchase
								where invoice_no is not null and (display != 0 OR display is null) and idunit = $idunit and idcustomer is not null")->row();
		$total_invoice = $q->total_invoice;

		$q = $this->db->query("select COALESCE(NULLIF(sum(paidtoday), null), 0) as total_paid,
								COALESCE(NULLIF(sum(unpaid_amount), null), 0) as unpaid_amount
								from purchase
								where invoice_no is not null and (display != 0 OR display is null) and idunit = $idunit and idcustomer is not null")->row();
		$total_invoice_paid = $q->total_paid;
		$total_invoice_unpaid = $q->unpaid_amount;

		$data = array(
				'total_invoice'=>$total_invoice,
				'total_invoice_paid'=>$total_invoice_paid,
				'total_invoice_unpaid'=>$total_invoice_unpaid
		);

		return $data;
	}

	function remove($id,$user_id,$idunit){
		$purchase_id = $id;

		$q = $this->db->get_where('purchase',array('purchase_id'=>$id,'userin'=>$user_id));
		if($q->num_rows()>0){
			$r = $q->row();
			
			// $idjournal = $r->idjournal;
			
			//prosess menghapus retur dan roll back stock yang retur
			$qretur = $this->db->query("select * from purchase_return where purchase_id=$id");
			if($qretur->num_rows() >0){
				
				$r2  = $qretur->row();
				$id_return = $r2->purchase_return_id;
				
				$stock_return = $this->stock_return_get($id,$type=7,$idunit);
				// echo $this->db->last_query();
				// die;
				// print_r($stock_return->result());
				if($stock_return !=null){
					// echo "string";
					foreach ($stock_return->result() as $key => $v2) {
						//get stock product
						// print_r($v2);
						$qst1 = $this->db->get_where('product',array('product_id'=>$v2->product_id))->row();

						//roll back stock
						 $type_adjustment=$type;
			             $trx_qty=$v2->trx_qty;
			             $new_stock    = $qst1->stock_available+$v2->trx_qty;

		           		// die;
			            //delete stock historis
			            $this->db->where('stock_history_id',$v2->stock_history_id); 
			            $this->db->delete('stock_history'); 
			            
			            // $this->db->insert('stock_history',$data_stock_return); 
			            //end insert
			           

			            //update stock available 
			            $this->db->where('product_id',$qst1->product_id);
			            $this->db->update('product',array(
			                'stock_available'=>$new_stock
			            ));
			            //end update 
					}
				}
				// die;
				//delete journal purchase order
				// $this->rollback($r2->journal_id);

				//delete purchase return items
				$this->db->where('purchase_return_id',$r2->{'purchase_return_id'});
				$this->db->delete('purchase_return_item');
				
				//delete return				
				$this->db->where('purchase_id',$id);
				$this->db->delete('purchase_return');
			}
			
			//prosess menghapus penerimaan dan roll back stock penerimaan
			$qreceipt =$this->db->query("select * from purchase_receipt where purchase_id=$id");
			if($qreceipt->num_rows() >0){
			
				$purchase_receipt_id =$qreceipt->row()->purchase_receipt_id; 
				//get stock receipt
				$stock_receipt = $this->stock_receipt_get($purchase_receipt_id,$type=2,$idunit);
				// echo $this->db->last_query();
				// die;
				if($stock_receipt !=null){
					foreach ($stock_receipt->result() as $key => $v3) {
						# code...
						// print_r($v3);
						// die;
						$qst2 = $this->db->get_where('product',array('product_id'=>$v3->product_id))->row();

						//roll back stock receipt
						// $type_adjustment=$type;
		                $trx_qty=$v3->trx_qty;
		                $new_stock    = $qst2->stock_available-$trx_qty; 


		                //start delete
		                 $this->db->where('stock_history_id',$v3->stock_history_id); 
			             $this->db->delete('stock_history'); 
		               
		                //end delete

				        //update stock available 
			            $this->db->where('product_id',$qst2->product_id);
			            $this->db->update('product',array(
			                'stock_available'=>$new_stock
			            ));
			            //end update 

					}
				}
			
				$qreceipt_items = $this->db->query("select * from purchase_receipt_item where purchase_receipt_id=".$purchase_receipt_id)->result();
				foreach ($qreceipt_items as $key => $va) {
					# code...
					// print_r($va);
					// die;
					//DELETE RECEIPT ITEMS
					$this->db->where('purchase_receipt_id',$va->purchase_receipt_id);
					$this->db->delete('purchase_receipt_item');
					
					$purchase_receipt = $this->db->get_where('purchase_receipt',array('purchase_id'=>$id))->row();
					// print_r($purchase_receipt);
					//DELETE JOURNAL
					if($purchase_receipt->idjournal_receive!=''){
						// echo "string";
						// $this->db->where('idjournal',$purchase_receipt->idjournal_receive);
			   //          $this->db->delete('journalitem');

			   //          $this->db->where('idjournal',$purchase_receipt->idjournal_receive);
			   //          $this->db->delete('journal');
					}
					//DELETE RECEIPT 						
					$this->db->where('purchase_id',$id);
					$this->db->delete('purchase_receipt');
				}

				
			}

			// die;
			
			//delete journal purchase order
			// $this->journal_lib->delete($idjournal);		
			// if($r->idjournal_do!=null){
			// 	$this->rollback($r->idjournal_do);		
			// }
			
			$this->db->where(array('purchase_id'=>$id,'userin'=>$user_id));			
			$this->db->delete('purchase_item');

			$this->db->where(array('purchase_id'=>$id,'userin'=>$user_id));			
			$this->db->delete('purchase');			

			$ret = array('success'=>true,'message'=>'purchase data successfully has been deleted'); 
		} else {
			$ret = array('success'=>false,'message'=>'failed while removing purchase data '); 
		}

		return $ret;
	}

	public function data_purchase_list($idunit=null,$purchase_id=null,$option=null,$stardate=null,$enddate=null,$query=null){
		
		$wer = '';

		if($idunit!=null){
			$wer.=" AND a.idunit = $idunit ";
		}

		if($purchase_id!=null){
			$wer.=" AND a.purchase_id = $purchase_id ";
		}

		if($query!=null){
			$wer.="AND (a.no_purchase_order like '%".$query."%' OR a.no_purchase_order like '%".strtolower($query)."%' OR
						 	a.no_purchase_order like '%".ucwords($query)."%' OR a.no_purchase_order like '%".strtoupper($query)."%')";
		}

		$q = $this->db->query("SELECT a.purchase_id,a.idpayment,a.invoice_no,a.idtax,a.idcustomer,a.subtotal,a.freight,a.tax,a.disc,a.totalamount,a.paidtoday,a.unpaid_amount,a.comments,a.userin,a.datein,a.status,a.idunit,a.date_purchase,a.no_purchase_order,a.include_tax,a.total_dpp,a.pos_payment_type_id,a.unpaid_amount,a.id_member,
						d.namaunit,
						COALESCE(NULLIF(a.other_fee ,NULL) , 0 ) as other_fee,a.invoice_date,a.due_date,a.invoice_status
						from purchase a
						join unit d ON a.idunit = d.idunit
						join purchase_receipt g ON g.purchase_id=a.purchase_id
						where true and (a.display is null or a.display = 1) and a.invoice_no is not null $wer AND a.purchase_id NOT IN (select purchase_id from purchase_return)
						order by a.purchase_id desc");
		
		return $q->result_array();
	}

	public function data_purchase_items($idunit,$id){

		$q = $this->db->query("SELECT a.product_id,a.purchase_id,a.purchase_item_id,a.qty,b.product_name,b.product_no,a.total,
							   a.price,a.description,b.idunit,c.status as receipt_status,c.total_rest_qty,c.purchase_receipt_id,
							   d.purchase_receipt_item_id,d.qty_received,d.notes 
							   FROM 
							   		purchase_item a
							   join 
							   		product b ON b.product_id=a.product_id
							   left join 
							   		purchase_receipt c ON c.purchase_id=a.purchase_id
							   left join 
							   		purchase_receipt_item d ON d.purchase_item_id=a.purchase_item_id
							   where 
							   		b.idunit=$idunit and a.purchase_id=$id");

		return $q->result_array();	
	}

	public function item_data($purchase_id,$idunit,$except_id=null,$query){
		$wer = null;

		if($except_id!=null){
	       
	        if(count($except_id)>0){
	            $wer = " and a.purchase_item_id NOT IN (";
	            foreach ($except_id as $v) {
	                $wer.="$v,";
	            }
	            $wer = rtrim($wer,",");
	            $wer.= ")";
	        }
		}
		if($query!=null){
				$wer="AND (c.product_name like '%".$query."%' OR c.product_name like '%".strtolower($query)."%' OR
						 	c.product_name like '%".ucwords($query)."%' OR c.product_name like '%".strtoupper($query)."%')";
		}

		$q = $this->db->query("select a.purchase_item_id,a.purchase_id,a.product_id,c.product_name,c.product_no,a.price,a.qty,a.ratetax,a.total_tax,a.disc,a.total,a.description,c.no_sku
									from purchase_item a
									join purchase b ON a.purchase_id = b.purchase_id
									join product c ON a.product_id = c.product_id
									where a.purchase_id = $purchase_id and b.idunit = $idunit $wer");
		$r = $q->result_array();
		$data = array();
		$i=0;
		foreach ($r as $key => $value) {

			$data[$i] = $value;
			$i++;
		}
		return $data;
	}

	public function data_purchase_return($idunit,$purchase_return_id,$startdate=null,$enddate=null,$query=null){
		$wer="";

		if($purchase_return_id!=''){
			$wer.=" and a.purchase_return_id=$purchase_return_id";
		}

		else if($startdate!=null && $enddate!=null){
			$wer.="AND (a.date_return between '".$startdate."' and '".$enddate."') ";
		}

		if($query!=null){
			$wer.="AND (a.no_return like '%".$query."%' OR a.no_return like '%".strtolower($query)."%' OR
						 	a.no_return like '%".ucwords($query)."%' OR a.no_return like '%".strtoupper($query)."%')";
		}


		$q = $this->db->query("select a.purchase_return_id,a.purchase_id,a.no_return,b.no_purchase_order,a.memo,
							   b.date_purchase,b.due_date,a.date_return,a.total_qty_return,a.total_amount_return,
							   b.idcustomer,b.customer_type,b.include_tax,c.namesupplier,c.companyaddress,c.telephone,c.handphone
							   from purchase_return a
							   join purchase b ON b.purchase_id=a.purchase_id
							   left join supplier c on c.idsupplier=b.idcustomer
							   where b.idunit=$idunit $wer order by purchase_return_id DESC");

		$r = $q->result_array();
		$data = array();
		$i=0;
		foreach ($r as $key => $value) {
			
			$data[$i] = $value;
			$i++;
		}
		return $data;
	}	

	function remove_purchase_return($id){
		
		$q = $this->db->get_where('purchase_return',array('purchase_return_id'=>$id));
		$this->after_return_purchace($id,$type=10,$statusform_return=null);
		if($q->num_rows()>0){
			$r = $q->row();
			
			// $this->rollback($r->journal_id);
			
			$this->db->where('purchase_return_id',$id);
			$this->db->delete('purchase_return_item');

			$this->db->where('purchase_return_id',$id);
			$this->db->delete('purchase_return');
			
			$ret = array('success'=>true,'message'=>'purchase return data was removed'); 
		} else {
			$ret = array('success'=>false,'message'=>'failed while removing purchase return data'); 
		}

		return $ret;
	}

	public function data_purchase_receipt($idunit,$purchase_receipt_id,$startdate,$enddate,$query){

		$wer=null;

		if($purchase_receipt_id!=''){

			$wer.= " AND purchase_receipt_id=$purchase_receipt_id";
		}

		else if($startdate!='' && $enddate!=''){
			$wer.= " AND (a.receipt_date between '".$startdate."' and '".$enddate."') ";
		}

		if($query!=null){
			$wer.="AND (a.purchase_receipt_no like '%".$query."%' OR a.purchase_receipt_no like '%".strtolower($query)."%' OR
						 	a.purchase_receipt_no like '%".ucwords($query)."%' OR a.purchase_receipt_no like '%".strtoupper($query)."%')";
		}

		$q = $this->db->query(" SELECT 
									a.purchase_receipt_id,a.purchase_id,a.status as status_purchase_receipt,a.purchase_receipt_no,b.no_purchase_order,a.memo,b.date_purchase,a.total_received,
									a.receipt_date,b.idcustomer,b.customer_type,b.id_member,
									c.namesupplier,c.companyaddress,c.telephone,c.handphone,
									a.receipt_date,a.total_qty_received,
									a.total_rest_qty
							    FROM 
									purchase_receipt a
								JOIN 
									purchase b ON b.purchase_id=a.purchase_id
								LEFT JOIN
									supplier c ON c.idsupplier=b.idcustomer
								WHERE
									b.idunit=$idunit $wer 
								ORDER BY a.purchase_receipt_id DESC ");



		$r = $q->result_array();
		$data = array();
		$i=0;
		
		foreach ($r as $key => $value) {
			
			$data[$i] = $value;
			$i++;
		}
		return $data;
	}

	function remove_purchase_receipt($id,$idunit){
		
		$q = $this->db->get_where('purchase_receipt',array('purchase_receipt_id'=>$id));
		if($q->num_rows()>0){
			// $idjournal = $r->idjournal;
			$this->after_receipt_purchace($id,$type=10,$receipt_item=null,$statusform_receive=null,$idunit);

			// $this->m_purchase->rollback($q->journal_id);

			$this->db->where('purchase_receipt_id',$id);
			$this->db->delete('purchase_receipt_item');

			$this->db->where('purchase_receipt_id',$id);
			$this->db->delete('purchase_receipt');
			

			// $this->m_journal->delete($idjournal,$user_id);
			$ret = array('success'=>true,'message'=>'purchase receipt data was removed'); 
		} else {
			$ret = array('success'=>false,'message'=>'failed while removing purchase receipt data'); 
		}

		return $ret;
	}
	
	function after_receipt_purchace($id,$type,$idunit){

        $this->db->trans_begin();
        
        $purchase_receipt_info = $this->purchase_receipt_items($id,$idunit);
    
        foreach ($purchase_receipt_info as $key => $v) {
             # code...
        	$product_info  = $this->db->get_where('product',array('product_id' => $v['product_id']))->result();    
            
            //generate stock number
            $params = array(
                  'idunit' => $idunit,
                  'prefix' => 'STCK',
                  'table' => 'stock_history',
                  'fieldpk' => 'stock_history_id',
                  'fieldname' => 'no_transaction',
                  'extraparams'=> null,
            );

            $notrx = $this->m_data->getNextNoArticle($params);
             if($type==2){
            	
            	$stock_cals = $v['qty']-$v['qty_received'];
            	
            	$type_adjustment = $type;
                $trx_qty         = $v['qty_received'];

                if($stock_cals > 0){
                	$new_stock = $product_info[0]->stock_available+$v['qty_received'];		            		

	            }else{
	            	
	            	$qhis = $this->db->query("SELECT sum(new_qty-trx_qty) as stock FROM stock_history where type_adjustment =2 and reference_id=".$v['purchase_receipt_id'])->row();
	            	
	            	// print_r($qhis);

	            	if(count($qhis->{'stock'}) >0){
	            		// echo "xxasa";
	            		$stock = $qhis->stock;
	            		$new_stock   = $v['qty_received']+$stock; 
	            		// die;
	            	}else{
	            		// echo "string";
	            		$new_stock   = $v['qty_received']+$product_info[0]->stock_available; 
	            		// die;
	            	}
	            	
	            }
            	
            }

            else if($type==10){
            	$type_adjustment=$type;
                $trx_qty=$v['qty_received'];
                $new_stock    = $product_info[0]->stock_available-$v['qty_received'];
            }

            $data_stock=array(
                'stock_history_id'=>$this->m_data->getPrimaryID2(null,'stock_history','stock_history_id'),
                'product_id'=>$v['product_id'],
                'type_adjustment'=>$type_adjustment,
                'no_transaction'=>$notrx,
                'datein'=>date('Y-m-d H:i'),
                'current_qty'=>$product_info[0]->stock_available,
                'trx_qty'=>$trx_qty,
                'new_qty'=>$new_stock,
                'reference_id'=>$v['purchase_receipt_id']
            );
                       
            	
		        $this->db->insert('stock_history',$data_stock); 
		   
	            $this->db->where('product_id',$v['product_id']);
	            $this->db->update('product',array(
	                'stock_available'=>$new_stock
	            ));
	             
        }  

        if($this->db->affected_rows()>0){
            $this->db->trans_commit();

        }else{
            $this->db->trans_rollback();

        }

    }

    public function after_return_purchace($id,$type,$idunit){

        $this->db->trans_begin();
        
        $purchase_return_info = $this->purchase_return_items($id);
        
        foreach ($purchase_return_info as $key => $v) {
            // code...
            // print_r($v);
            // die;
            //get product info
            $product_info  = $this->db->get_where('product',array('product_id' => $v['product_id']))->result();    

            //generate stock number
            $params = array(
                  'idunit' => $idunit,
                  'prefix' => 'STCK',
                  'table' => 'stock_history',
                  'fieldpk' => 'stock_history_id',
                  'fieldname' => 'no_transaction',
                  'extraparams'=> null,
            );

            $notrx = $this->m_data->getNextNoArticle($params);
             if($type==7){
            
                $type_adjustment = $type;
                $trx_qty         = $v['qty_retur'];
                $new_stock       = $product_info[0]->stock_available-$v['qty_retur'];
               
            }

            else if($type==10){
            	$type_adjustment=$type;
                $trx_qty=$v['qty_retur'];
                $new_stock    = $product_info[0]->stock_available+$v['qty_retur'];
            }

            $data_stock=array(
                'stock_history_id'=>$this->m_data->getPrimaryID2(null,'stock_history','stock_history_id'),
                'product_id'=>$v['product_id'],
                'type_adjustment'=>$type_adjustment,
                'no_transaction'=>$notrx,
                'datein'=>date('Y-m-d H:i'),
                'current_qty'=>$product_info[0]->stock_available,
                'trx_qty'=>$trx_qty,
                'new_qty'=>$new_stock,
                'reference_id'=>$v['purchase_id']
            );


	            //insert stock historis
	            $this->db->insert('stock_history',$data_stock); 
	            //end insert
	            // echo $this->db->last_query();

	            //update stock available 
	            $this->db->where('product_id',$v['product_id']);
	            $this->db->update('product',array(
	                'stock_available'=>$new_stock
	            ));
	            //end update 
        	
        }  

        if($this->db->trans_status()===false){
            $this->db->trans_rollback();
        }else{

            $this->db->trans_commit();
        }

    }

    public function purchase_return_items($purchase_return_id){
    	$where= "";

    	if($purchase_return_id!=''){
    		$where = " WHERE purchase_return_id=$purchase_return_id";
    	}

    	$q = $this->db->query("SELECT 
    								a.purchase_return_id,a.purchase_return_item_id,a.purchase_item_id,a.qty_purchase,a.qty_retur,a.notes,
    								b.purchase_id,c.product_id,c.no_sku,c.product_name,d.total,b.price
    						   FROM 
    						   		purchase_return_item a
    						   JOIN 
    						   		purchase_item b ON b.purchase_item_id=a.purchase_item_id
    						   	JOIN
    						   		product c ON c.product_id=b.product_id
    						   	JOIN 
    						   		purchase d ON d.purchase_id=b.purchase_id	
    						   	
    						   	$where");
    	$i=0;
    	$data=[];
    	foreach ($q->result_array() as $key => $value) {
    		# code...
    		$value['total_retur'] =$value['price']*$value['qty_retur'];
    		$data[$i]=$value;
    		$i++;
    	}

    	return $data;
    }

    public function purchase_receipt_items($purchase_receipt,$idunit){
    	$where= "";

    	if($purchase_receipt!=''){
    		$where = " WHERE a.purchase_receipt_id=$purchase_receipt";
    	}

    	$q = $this->db->query("SELECT 
    								a.purchase_receipt_id,a.purchase_receipt_item_id,a.purchase_item_id,a.qty_received,a.notes,
    								b.purchase_id,b.qty,c.product_id,c.no_sku,c.product_name,d.total,b.price,a.rest_qty
    						   FROM 
    						   		purchase_receipt_item a
    						   JOIN 
    						   		purchase_item b ON b.purchase_item_id=a.purchase_item_id
    						   	JOIN
    						   		product c ON c.product_id=b.product_id
    						   	JOIN 
    						   		purchase d ON d.purchase_id=b.purchase_id	
    						   	
    						   	$where");
    	$i=0;
    	$data=[];
    	foreach ($q->result_array() as $key => $value) {
    		# code...
    		// $value['total_retur'] =$value['price']*$value['qty_retur'];
    		$data[$i]=$value;
    		$i++;
    	}

    	return $data;
    }

    function stock_receipt_get($id,$type,$idunit){

    	$q = $this->db->query(" SELECT 
    								  A.* 
								FROM
									stock_history
									A JOIN purchase_receipt b ON A.reference_id = b.purchase_receipt_id
									JOIN purchase_item C ON C.purchase_id = b.purchase_id
									JOIN product d ON d.product_id = C.product_id 
								WHERE
									b.purchase_receipt_id = $id 
									AND A.type_adjustment = $type 
									AND d.idunit = $idunit");

    	return $q;
    }

    function stock_return_get($id,$type,$idunit){

    	$q = $this->db->query(" SELECT 
    								  A.* 
								FROM
									stock_history
									A JOIN purchase_return b ON A.reference_id = b.purchase_id
									JOIN purchase_item C ON C.purchase_id = b.purchase_id
									JOIN product d ON d.product_id = C.product_id 
								WHERE
									b.purchase_id = $id 
									AND A.type_adjustment = $type 
									AND d.idunit =$idunit");

    	return $q;
    }

    function jurnal_purchase_invoice_ppn($date,$memo,$before_tax,$amount,$idunit,$userin,$purchase_id,$tax_id){
    	$tgl = explode("-", $date);
        
        $idjournal = $this->m_data->getPrimaryID2(null,'journal', 'idjournal');

        $d = array(
            'idjournal' => $idjournal,
            'idjournaltype' => 10, //piutang
            'nojournal' => rand(11111,99999),
            'datejournal' => $date,
            'memo' => $memo,
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

        //debit - pembelian
        $q=$this->db->query("select sum(a.total-COALESCE(nullif(a.total_tax,NULL))) as total_purchase,b.coa_purchase_id,c.accname
                from purchase_item a
                join product b ON a.product_id=b.product_id
                join account c ON b.coa_purchase_id=c.idaccount and c.idunit=b.idunit
                where purchase_id=$purchase_id
                group by b.coa_purchase_id,c.accname");
        foreach ($q->result() as $key => $value) {
            # code...
            $idaccount = $value->coa_purchase_id;
            $amount = $value->total_purchase;
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
    

        //pajak 
        $total_pajak=0;

        if($tax_id!='' &&$tax_id!=null){
        	$qtax =$this->m_tax->get($tax_id);
        	if(isset($qtax['data'][0])){
        		$data_tax = $qtax['data'][0];
        		if($data_tax['is_tax_ppn']==1 && $data_tax['is_tax_pph23']==0){
        			//pajak ppn
        			$idaccount   = $data_tax['coa_ppn_purchase_id'];
        			$tax_amount  = $before_tax*($data_tax['coa_ppn_rate']/100);
        			$total_pajak += $tax_amount;
        			$curBalanceK = $this->m_account->getCurrBalance($idaccount, $idunit);
        			$newBalanceK = $curBalanceK - $tax_amount;


	 				$ditem = array(
		                'idjournal' => $idjournal,
		                'idaccount' => $idaccount,
		                'debit' => $tax_amount,
		                'credit' => 0,
		                'lastbalance' => $curBalanceK,
		                'currbalance' => $newBalanceK
	           	 	);

	            	$this->db->insert('journalitem', $ditem);
	            	$this->m_account->saveNewBalance($idaccount, $newBalanceK, $idunit,$userin);
	            	$this->m_account->saveAccountLog($idunit,$idaccount,0,$tax_amount,$date,$idjournal,$userin);

        		}
        	}
        }

        //Hutang - credit
        $idaccount = $this->m_data->getIdAccount(14, $idunit);
        $amount = $amount+$total_pajak;
        $curBalanceK = $this->m_account->getCurrBalance($idaccount, $idunit);
        $newBalanceK = $curBalanceK + $amount;

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

        return $idjournal;
    }

    function jurnal_purchase_invoice_pph($date,$memo,$before_tax,$amount,$idunit,$userin,$purchase_id,$tax_id){
    	$tgl = explode("-", $date);
        
        $idjournal = $this->m_data->getPrimaryID2(null,'journal', 'idjournal');

        $d = array(
            'idjournal' => $idjournal,
            'idjournaltype' => 10, //piutang
            'nojournal' => rand(11111,99999),
            'datejournal' => $date,
            'memo' => $memo,
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

        //debit - pembelian
        $q=$this->db->query("select sum(a.total-COALESCE(nullif(a.total_tax,NULL))) as total_purchase,b.coa_purchase_id,c.accname
                from purchase_item a
                join product b ON a.product_id=b.product_id
                join account c ON b.coa_purchase_id=c.idaccount and c.idunit=b.idunit
                where purchase_id=$purchase_id
                group by b.coa_purchase_id,c.accname");
        foreach ($q->result() as $key => $value) {
            # code...
            $idaccount = $value->coa_purchase_id;
            $amount = $value->total_purchase;
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
    

        //pajak
        $total_pajak=0;

        if($tax_id!='' &&$tax_id!=null){
        	$qtax =$this->m_tax->get($tax_id);
        	if(isset($qtax['data'][0])){
        		$data_tax = $qtax['data'][0];
        		if($data_tax['is_tax_ppn']==0 && $data_tax['is_tax_pph23']==1){
        			//pajak pph
        			$idaccount   = $data_tax['coa_pph23_purchase_id'];
        			$tax_amount  = $before_tax*($data_tax['coa_pph23_rate']/100);
        			$total_pajak += $tax_amount;
        			$curBalanceK = $this->m_account->getCurrBalance($idaccount, $idunit);
        			$newBalanceK = $curBalanceK + $tax_amount;


	 				$ditem = array(
		                'idjournal' => $idjournal,
		                'idaccount' => $idaccount,
		                'debit' =>0 ,
		                'credit' =>  $tax_amount,
		                'lastbalance' => $curBalanceK,
		                'currbalance' => $newBalanceK
	           	 	);
	           	 	
	            	$this->db->insert('journalitem', $ditem);
	            	$this->m_account->saveNewBalance($idaccount, $newBalanceK, $idunit,$userin);
	            	$this->m_account->saveAccountLog($idunit,$idaccount,$tax_amount,0,$date,$idjournal,$userin);

        		}
        	}
        }

        //Hutang - credit
        $idaccount = $this->m_data->getIdAccount(14, $idunit);
        $amount = $amount-$total_pajak;
        $curBalanceK = $this->m_account->getCurrBalance($idaccount, $idunit);
        $newBalanceK = $curBalanceK + $amount;

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

        return $idjournal;
    }

    function jurnal_purchase_invoice_ppn_n_pph($date,$memo,$before_tax,$amount,$idunit,$userin,$purchase_id,$tax_id){
        	$tgl = explode("-", $date);
        
        $idjournal = $this->m_data->getPrimaryID2(null,'journal', 'idjournal');

        $d = array(
            'idjournal' => $idjournal,
            'idjournaltype' => 10, //piutang
            'nojournal' => rand(11111,99999),
            'datejournal' => $date,
            'memo' => $memo,
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

        //debit - pembelian
        $q=$this->db->query("select sum(a.total-COALESCE(nullif(a.total_tax,NULL))) as total_purchase,b.coa_purchase_id,c.accname
                from purchase_item a
                join product b ON a.product_id=b.product_id
                join account c ON b.coa_purchase_id=c.idaccount and c.idunit=b.idunit
                where purchase_id=$purchase_id
                group by b.coa_purchase_id,c.accname");
        foreach ($q->result() as $key => $value) {
            # code...
            $idaccount = $value->coa_purchase_id;
            $amount = $value->total_purchase;
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
    

        //pajak - PPN Masukan
        $total_ppn=0;

        if($tax_id!='' &&$tax_id!=null){
        	$qtax =$this->m_tax->get($tax_id);
        	if(isset($qtax['data'][0])){
        		$data_tax = $qtax['data'][0];
        		if($data_tax['is_tax_ppn']==1 && $data_tax['is_tax_pph23']==1){
        			//pajak ppn
        			$coa_ppn   = $data_tax['coa_ppn_purchase_id'];
        			$tax_amount  = $before_tax*($data_tax['coa_ppn_rate']/100);
        			$total_ppn += $tax_amount;
        			$curBalanceK = $this->m_account->getCurrBalance($coa_ppn, $idunit);
        			$newBalanceK = $curBalanceK - $tax_amount;


	 				$ditem = array(
		                'idjournal' => $idjournal,
		                'idaccount' => $coa_ppn,
		                'debit' => $tax_amount,
		                'credit' => 0,
		                'lastbalance' => $curBalanceK,
		                'currbalance' => $newBalanceK
	           	 	);

	            	$this->db->insert('journalitem', $ditem);
	            	$this->m_account->saveNewBalance($coa_ppn, $newBalanceK, $idunit,$userin);
	            	$this->m_account->saveAccountLog($idunit,$coa_ppn,0,$tax_amount,$date,$idjournal,$userin);

        		}
        	}
        }
		
		$total_pph23=0;

        if($tax_id!='' &&$tax_id!=null){
        	$qtax =$this->m_tax->get($tax_id);
        	if(isset($qtax['data'][0])){
        		$data_tax = $qtax['data'][0];
        		if($data_tax['is_tax_ppn']==1 && $data_tax['is_tax_pph23']==1){
        			//pajak pph
        			$coa_pph23   = $data_tax['coa_pph23_purchase_id'];     			
        			$tax_amount  = $amount*($data_tax['coa_pph23_rate']/100);
        			$total_pph23 += $tax_amount;
        		}
        	}		
		}
		
		//Hutang - credit
	    $idaccount = $this->m_data->getIdAccount(14, $idunit);
	    $amount = ($amount+$total_ppn)-$total_pph23;
	    $curBalanceK = $this->m_account->getCurrBalance($idaccount, $idunit);
	    $newBalanceK = $curBalanceK + $amount;

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


		//pajak - PPH pasal 23
    	$curBalanceK = $this->m_account->getCurrBalance($coa_pph23, $idunit);
        $newBalanceK = $curBalanceK + $tax_amount;

	 	$ditem = array(
		    'idjournal' => $idjournal,
		    'idaccount' => $coa_pph23,
		    'debit' =>0 ,
		    'credit' =>  $tax_amount,
		    'lastbalance' => $curBalanceK,
		    'currbalance' => $newBalanceK
	    );
	           	 	
	    $this->db->insert('journalitem', $ditem);
	    $this->m_account->saveNewBalance($coa_pph23, $newBalanceK, $idunit,$userin);
	    $this->m_account->saveAccountLog($idunit,$coa_pph23,$tax_amount,0,$date,$idjournal,$userin);

        return $idjournal;
    }

    function jurnal_purchase_receipt($date,$memo,$amount,$idunit,$userin,$purchase_id){
    	$tgl = explode("-", $date);
        
        $idjournal = $this->m_data->getPrimaryID2(null,'journal', 'idjournal');

        $d = array(
            'idjournal' => $idjournal,
            'idjournaltype' => 10, //piutang
            'nojournal' => rand(11111,99999),
            'datejournal' => $date,
            'memo' => $memo,
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

        //hutang dagang - debit
	    $idaccount = $this->m_data->getIdAccount(14, $idunit);
	    $amount = $amount;
	    $curBalanceK = $this->m_account->getCurrBalance($idaccount, $idunit);
	    $newBalanceK = $curBalanceK - $amount;

	    $ditem = array(
			'idjournal' => $idjournal,
			'idaccount' => $idaccount,
			'debit' => $amount,
			'credit' => 0,
			'lastbalance' => $curBalanceK,
			'currbalance' => $newBalanceK
	    );
	    $this->db->insert('journalitem', $ditem);
	    $this->m_account->saveNewBalance($idaccount, $newBalanceK, $idunit,$userin);
	    $this->m_account->saveAccountLog($idunit,$idaccount,0,$amount,$date,$idjournal,$userin);


        //Pembelian - credit
        $q=$this->db->query("select d.coa_purchase_id,e.accname,(a.qty_received*d.buy_price) as total_received
                from purchase_receipt_item a
                join purchase_receipt b ON b.purchase_receipt_id=a.purchase_receipt_id
                join purchase_item c ON c.purchase_id=b.purchase_id
                join product  d ON d.product_id=c.product_id
                join account e ON d.coa_purchase_id=e.idaccount and d.idunit=e.idunit
                where c.purchase_id=$purchase_id
                group by d.coa_purchase_id,e.accname,a.qty_received,d.buy_price");
        foreach ($q->result() as $key => $value) {
            # code...
            $idaccount = $value->coa_purchase_id;    
            $curBalanceD = $this->m_account->getCurrBalance($idaccount, $idunit);
            $newBalanceD = $curBalanceD - $value->total_received;
            $ditem = array(
                'idjournal' => $idjournal,
                'idaccount' => $idaccount,
                'debit' => 0,
                'credit' => $value->total_received,
                'lastbalance' => $curBalanceD,
                'currbalance' => $newBalanceD
            );
            $this->db->insert('journalitem', $ditem);
            $this->m_account->saveNewBalance($idaccount, $newBalanceD, $idunit,$userin);
            $this->m_account->saveAccountLog($idunit,$idaccount,$value->total_received,0,$date,$idjournal,$userin);
        }
        
        $this->db->where('purchase_id',$purchase_id);
        $this->db->update('purchase_receipt',array('idjournal_receive' =>$idjournal));
    }

    function journal_return_ppn($date,$memo,$amount,$idunit,$userin,$purchase_return_id,$tax_id){
		$tgl = explode("-", $date);
        
        $idjournal = $this->m_data->getPrimaryID2(null,'journal', 'idjournal');

        $d = array(
            'idjournal' => $idjournal,
            'idjournaltype' => 10, //piutang
            'nojournal' => rand(11111,99999),
            'datejournal' => $date,
            'memo' => $memo,
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
        $total_tax =0;

        if($tax_id !='' && $tax_id !=null){
	    	$qtax =$this->m_tax->get($tax_id);
            
            if(isset($qtax['data'][0])){
			    $data_tax = $qtax['data'][0];
			    
			    if($data_tax['is_tax_ppn']==1 && $data_tax['is_tax_pph23']==0){
			    	$tax_amount = $amount*($data_tax['coa_ppn_rate']/100);
			    	$total_tax += $tax_amount;

			    	//hutang dagang - debit
				    $idaccount = $this->m_data->getIdAccount(14, $idunit);
				    $amount = $amount+$tax_amount;
				    $curBalanceK = $this->m_account->getCurrBalance($idaccount, $idunit);
				    $newBalanceK = $curBalanceK - $amount;

				    $ditem = array(
						'idjournal' => $idjournal,
						'idaccount' => $idaccount,
						'debit' => $amount,
						'credit' => 0,
						'lastbalance' => $curBalanceK,
						'currbalance' => $newBalanceK
				    );
				    $this->db->insert('journalitem', $ditem);
				    $this->m_account->saveNewBalance($idaccount, $newBalanceK, $idunit,$userin);
				    $this->m_account->saveAccountLog($idunit,$idaccount,0,$amount,$date,$idjournal,$userin);

				    //pajak - kredit
				    $idaccount   = $data_tax['coa_ppn_purchase_id'];	
        			$curBalanceK = $this->m_account->getCurrBalance($idaccount, $idunit);
        			$newBalanceK = $curBalanceK - $tax_amount;


	 				$ditem = array(
		                'idjournal' => $idjournal,
		                'idaccount' => $idaccount,
		                'debit' => 0,
		                'credit' => $tax_amount,
		                'lastbalance' => $curBalanceK,
		                'currbalance' => $newBalanceK
	           	 	);

	            	$this->db->insert('journalitem', $ditem);
	            	$this->m_account->saveNewBalance($idaccount, $newBalanceK, $idunit,$userin);
	            	$this->m_account->saveAccountLog($idunit,$idaccount,$tax_amount,0,$date,$idjournal,$userin);	
			    }
            }
	    }
        

	    //pembelian -kredit
	    $q=$this->db->query("select d.coa_purchase_id,e.accname,(a.qty_retur*d.buy_price) as total_retur
                from purchase_return_item a
                join purchase_return b ON b.purchase_return_id=a.purchase_return_id
                join purchase_item c ON c.purchase_id=b.purchase_id
                join product  d ON d.product_id=c.product_id
                join account e ON d.coa_purchase_id=e.idaccount and d.idunit=e.idunit
                where b.purchase_return_id=$purchase_return_id
                group by d.coa_purchase_id,e.accname,a.qty_retur,d.buy_price");
	    
	    foreach ($q->result() as $key => $v) {
	    	# code...
	    	$idaccount = $v->coa_purchase_id;    
            $curBalanceD = $this->m_account->getCurrBalance($idaccount, $idunit);
            $newBalanceD = $curBalanceD - $v->total_retur;
            $ditem = array(
                'idjournal' => $idjournal,
                'idaccount' => $idaccount,
                'debit' => 0,
                'credit' => $v->total_retur,
                'lastbalance' => $curBalanceD,
                'currbalance' => $newBalanceD
            );
            $this->db->insert('journalitem', $ditem);
            $this->m_account->saveNewBalance($idaccount, $newBalanceD, $idunit,$userin);
            $this->m_account->saveAccountLog($idunit,$idaccount,$v->total_retur,0,$date,$idjournal,$userin);	
	    }

	    return $idjournal;
    }

    function journal_return_pph23($date,$memo,$amount,$idunit,$userin,$purchase_return_id,$tax_id){
		$tgl = explode("-", $date);
        
        $idjournal = $this->m_data->getPrimaryID2(null,'journal', 'idjournal');

        $d = array(
            'idjournal' => $idjournal,
            'idjournaltype' => 10, //piutang
            'nojournal' => rand(11111,99999),
            'datejournal' => $date,
            'memo' => $memo,
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
        $total_tax =0;

        if($tax_id !='' && $tax_id !=null){
	    	$qtax =$this->m_tax->get($tax_id);
            
            if(isset($qtax['data'][0])){
			    $data_tax = $qtax['data'][0];
			    
			    if($data_tax['is_tax_ppn']==0 && $data_tax['is_tax_pph23']==1){
			    	$tax_amount = $amount*($data_tax['coa_pph23_rate']/100);
			    	$total_tax += $tax_amount;

			    	//hutang dagang - debit
				    $idaccount = $this->m_data->getIdAccount(14, $idunit);
				    $amount = $amount-$tax_amount;
				    $curBalanceK = $this->m_account->getCurrBalance($idaccount, $idunit);
				    $newBalanceK = $curBalanceK - $amount;

				    $ditem = array(
						'idjournal' => $idjournal,
						'idaccount' => $idaccount,
						'debit' => $amount,
						'credit' => 0,
						'lastbalance' => $curBalanceK,
						'currbalance' => $newBalanceK
				    );
				    $this->db->insert('journalitem', $ditem);
				    $this->m_account->saveNewBalance($idaccount, $newBalanceK, $idunit,$userin);
				    $this->m_account->saveAccountLog($idunit,$idaccount,0,$amount,$date,$idjournal,$userin);

				    //pajak - kredit
				    $idaccount   = $data_tax['coa_pph23_purchase_id'];	
        			$curBalanceK = $this->m_account->getCurrBalance($idaccount, $idunit);
        			$newBalanceK = $curBalanceK - $tax_amount;


	 				$ditem = array(
		                'idjournal' => $idjournal,
		                'idaccount' => $idaccount,
		                'debit' => $tax_amount,
		                'credit' => 0,
		                'lastbalance' => $curBalanceK,
		                'currbalance' => $newBalanceK
	           	 	);

	            	$this->db->insert('journalitem', $ditem);
	            	$this->m_account->saveNewBalance($idaccount, $newBalanceK, $idunit,$userin);
	            	$this->m_account->saveAccountLog($idunit,$idaccount,0,$tax_amount,$date,$idjournal,$userin);	
			    }
            }
	    }
        

	    //pembelian -kredit
	    $q=$this->db->query("select d.coa_purchase_id,e.accname,(a.qty_retur*d.buy_price) as total_retur
                from purchase_return_item a
                join purchase_return b ON b.purchase_return_id=a.purchase_return_id
                join purchase_item c ON c.purchase_id=b.purchase_id
                join product  d ON d.product_id=c.product_id
                join account e ON d.coa_purchase_id=e.idaccount and d.idunit=e.idunit
                where b.purchase_return_id=$purchase_return_id
                group by d.coa_purchase_id,e.accname,a.qty_retur,d.buy_price");
	    
	    foreach ($q->result() as $key => $v) {
	    	# code...
	    	$idaccount = $v->coa_purchase_id;    
            $curBalanceD = $this->m_account->getCurrBalance($idaccount, $idunit);
            $newBalanceD = $curBalanceD - $v->total_retur;
            $ditem = array(
                'idjournal' => $idjournal,
                'idaccount' => $idaccount,
                'debit' => 0,
                'credit' => $v->total_retur,
                'lastbalance' => $curBalanceD,
                'currbalance' => $newBalanceD
            );
            $this->db->insert('journalitem', $ditem);
            $this->m_account->saveNewBalance($idaccount, $newBalanceD, $idunit,$userin);
            $this->m_account->saveAccountLog($idunit,$idaccount,$v->total_retur,0,$date,$idjournal,$userin);	
	    }

	    return $idjournal;
    }

    function journal_return_ppnAndpph23($date,$memo,$amount,$idunit,$userin,$purchase_return_id,$tax_id){
		$tgl = explode("-", $date);
        
        $idjournal = $this->m_data->getPrimaryID2(null,'journal', 'idjournal');

        $d = array(
            'idjournal' => $idjournal,
            'idjournaltype' => 10, //piutang
            'nojournal' => rand(11111,99999),
            'datejournal' => $date,
            'memo' => $memo,
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
        $total_tax_pph23=0;
        $total_tax_ppn=0;

        if($tax_id !='' && $tax_id !=null){
	    	$qtax =$this->m_tax->get($tax_id);
            
            if(isset($qtax['data'][0])){
			    $data_tax = $qtax['data'][0];
			    
			    if($data_tax['is_tax_ppn']==1 && $data_tax['is_tax_pph23']==1){
			    	$tax_amount_pph23 = $amount*($data_tax['coa_pph23_rate']/100);
			    	$tax_amount_ppn = $amount*($data_tax['coa_ppn_rate']/100);
			    	
			    	$total_tax_pph23 += $tax_amount_pph23;
			    	$total_tax_ppn += $tax_amount_ppn;

			    	//hutang dagang - debit
				    $idaccount = $this->m_data->getIdAccount(14, $idunit);
				    $amount = ($amount+$tax_amount_ppn)-$tax_amount_pph23;
				    $curBalanceK = $this->m_account->getCurrBalance($idaccount, $idunit);
				    $newBalanceK = $curBalanceK - $amount;

				    $ditem = array(
						'idjournal' => $idjournal,
						'idaccount' => $idaccount,
						'debit' => $amount,
						'credit' => 0,
						'lastbalance' => $curBalanceK,
						'currbalance' => $newBalanceK
				    );
				    $this->db->insert('journalitem', $ditem);
				    $this->m_account->saveNewBalance($idaccount, $newBalanceK, $idunit,$userin);
				    $this->m_account->saveAccountLog($idunit,$idaccount,0,$amount,$date,$idjournal,$userin);

				    //pajak PPH 23 - Debit
				    $idaccount   = $data_tax['coa_pph23_purchase_id'];	
        			$curBalanceK = $this->m_account->getCurrBalance($idaccount, $idunit);
        			$newBalanceK = $curBalanceK - $tax_amount_pph23;


	 				$ditem = array(
		                'idjournal' => $idjournal,
		                'idaccount' => $idaccount,
		                'debit' => $tax_amount_pph23,
		                'credit' => 0,
		                'lastbalance' => $curBalanceK,
		                'currbalance' => $newBalanceK
	           	 	);

	            	$this->db->insert('journalitem', $ditem);
	            	$this->m_account->saveNewBalance($idaccount, $newBalanceK, $idunit,$userin);
	            	$this->m_account->saveAccountLog($idunit,$idaccount,0,$tax_amount_pph23,$date,$idjournal,$userin);	

	            	//pajak PPN - kredit
				    $idaccount   = $data_tax['coa_ppn_purchase_id'];	
        			$curBalanceK = $this->m_account->getCurrBalance($idaccount, $idunit);
        			$newBalanceK = $curBalanceK - $tax_amount_ppn;


	 				$ditem = array(
		                'idjournal' => $idjournal,
		                'idaccount' => $idaccount,
		                'debit' => 0,
		                'credit' => $tax_amount_ppn,
		                'lastbalance' => $curBalanceK,
		                'currbalance' => $newBalanceK
	           	 	);

	            	$this->db->insert('journalitem', $ditem);
	            	$this->m_account->saveNewBalance($idaccount, $newBalanceK, $idunit,$userin);
	            	$this->m_account->saveAccountLog($idunit,$idaccount,$tax_amount_ppn,0,$date,$idjournal,$userin);
			    }
            }
	    }
        

	    //pembelian -kredit
	    $q=$this->db->query("select d.coa_purchase_id,e.accname,(a.qty_retur*d.buy_price) as total_retur
                from purchase_return_item a
                join purchase_return b ON b.purchase_return_id=a.purchase_return_id
                join purchase_item c ON c.purchase_id=b.purchase_id
                join product  d ON d.product_id=c.product_id
                join account e ON d.coa_purchase_id=e.idaccount and d.idunit=e.idunit
                where b.purchase_return_id=$purchase_return_id
                group by d.coa_purchase_id,e.accname,a.qty_retur,d.buy_price");
	    
	    foreach ($q->result() as $key => $v) {
	    	# code...
	    	$idaccount = $v->coa_purchase_id;    
            $curBalanceD = $this->m_account->getCurrBalance($idaccount, $idunit);
            $newBalanceD = $curBalanceD - $v->total_retur;
            $ditem = array(
                'idjournal' => $idjournal,
                'idaccount' => $idaccount,
                'debit' => 0,
                'credit' => $v->total_retur,
                'lastbalance' => $curBalanceD,
                'currbalance' => $newBalanceD
            );
            $this->db->insert('journalitem', $ditem);
            $this->m_account->saveNewBalance($idaccount, $newBalanceD, $idunit,$userin);
            $this->m_account->saveAccountLog($idunit,$idaccount,$v->total_retur,0,$date,$idjournal,$userin);	
	    }

	    return $idjournal;
    }

    function journal_return_non_pajak($date,$memo,$amount,$idunit,$userin,$purchase_return_id,$tax_id){
		$tgl = explode("-", $date);
        
        $idjournal = $this->m_data->getPrimaryID2(null,'journal', 'idjournal');

        $d = array(
            'idjournal' => $idjournal,
            'idjournaltype' => 10, //piutang
            'nojournal' => rand(11111,99999),
            'datejournal' => $date,
            'memo' => $memo,
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
        // $total_tax =0;


		//hutang dagang - debit
		$idaccount = $this->m_data->getIdAccount(14, $idunit);
		$amount = $amount;
		$curBalanceK = $this->m_account->getCurrBalance($idaccount, $idunit);
		$newBalanceK = $curBalanceK - $amount;

		$ditem = array(
			'idjournal' => $idjournal,
			'idaccount' => $idaccount,
			'debit' => $amount,
			'credit' => 0,
			'lastbalance' => $curBalanceK,
			'currbalance' => $newBalanceK
		);

		$this->db->insert('journalitem', $ditem);
		$this->m_account->saveNewBalance($idaccount, $newBalanceK, $idunit,$userin);
		$this->m_account->saveAccountLog($idunit,$idaccount,0,$amount,$date,$idjournal,$userin);
        

	    //pembelian -kredit
	    $q=$this->db->query("select d.coa_purchase_id,e.accname,(a.qty_retur*d.buy_price) as total_retur
                from purchase_return_item a
                join purchase_return b ON b.purchase_return_id=a.purchase_return_id
                join purchase_item c ON c.purchase_id=b.purchase_id
                join product  d ON d.product_id=c.product_id
                join account e ON d.coa_purchase_id=e.idaccount and d.idunit=e.idunit
                where b.purchase_return_id=$purchase_return_id
                group by d.coa_purchase_id,e.accname,a.qty_retur,d.buy_price");
	    // print_r();
	    foreach ($q->result() as $key => $v) {
	    	# code...
	    	$idaccount = $v->coa_purchase_id;    
            $curBalanceD = $this->m_account->getCurrBalance($idaccount, $idunit);
            $newBalanceD = $curBalanceD - $v->total_retur;
            $ditem = array(
                'idjournal' => $idjournal,
                'idaccount' => $idaccount,
                'debit' => 0,
                'credit' => $v->total_retur,
                'lastbalance' => $curBalanceD,
                'currbalance' => $newBalanceD
            );
            $this->db->insert('journalitem', $ditem);
            $this->m_account->saveNewBalance($idaccount, $newBalanceD, $idunit,$userin);
            $this->m_account->saveAccountLog($idunit,$idaccount,$v->total_retur,0,$date,$idjournal,$userin);	
	    }

	    return $idjournal;
    }

    function rollback($journal_id){
    	$this->db->trans_begin();

        //get journal
        $q = $this->db->get_where('journal',array('idjournal'=>$journal_id));
        
        if($q->num_rows() >0){
        	
        	$r = $q->row();

        	$this->db->where('idjournal',$r->idjournal);
        	$this->db->delete('accountlog');

        	$q2 = $this->db->get_where('journalitem',array('idjournal'=>$r->{'idjournal'}));
        	
        	foreach ($q2->result() as $key => $v) {
        		# code...
        		//get account detail
        		$account = $this->db->query("SELECT balance,idaccounttype FROM account WHERE idaccount=".$v->{'idaccount'}." and idunit=".$this->user_data->idunit)->row();
        		// print_r($account);

        		if(isset($account->{'balance'})){
        			$currbalance = $account->{'balance'};
        			$trx_amount = $v->debit == 0 ? $v->credit : $v->debit;
        			
        			$newbalance = $currbalance+$trx_amount;
        			// echo $newbalance;
        		}else{
        			$currbalance = 0;
        			$newbalance  = 0;
        		}

        		$this->db->where(array(
        			'idaccount'=>$v->{'idaccount'},
                    'idunit'=>$this->user_data->idunit		
        		));

        		$this->db->update('account',array(
                    'balance'=>$newbalance
                ));
        	}
        	// die;
        	$this->db->where('idjournal',$journal_id);
            $this->db->delete('journalitem');

            $this->db->where('idjournal',$journal_id);
            $this->db->delete('journal');

            if ($this->db->trans_status() === FALSE)
            {
                $this->db->trans_rollback();
                // $json = array('success'=>false,'message'=>'hapus jurnal gagal');
            }
            else
            {
                $this->db->trans_commit();
                // $json = array('success'=>true,'message'=>'hapus jurnal berhasil');
            }
                
        }
    }
}

?>