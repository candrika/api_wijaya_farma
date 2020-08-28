<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Purchase extends MY_Controller {

	function  __construct(){
		 parent::__construct();

        $this->load->model(array('m_user','m_purchase','m_account','preferences/m_tax'));

        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        // $this->load->library();
        $this->methods['users_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['users_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['users_delete']['limit'] = 50; // 50 requests per hour per user/key
	}

    private function check_coa($coa_id,$idunit){
        $q = $this->db->get_where('account',array('idaccount'=>$coa_id,'idunit'=>$idunit));
        if($q->num_rows()>0){
            return true;
        } else {
            return false;
        }
    }

    private function chech_tax($tax_id){

        $response =array(
            'success'=>true
        );

        if($tax_id!=null && $tax_id!=''){
            $q = $this->m_tax->get($tax_id);
            
            if(isset($q['data'][0])){
                $data= $q['data'][0];
                if($data['is_tax_ppn']==1){
                    
                    if($data['coa_ppn_purchase_id']==0||$data['coa_ppn_purchase_id']==''||$data['coa_ppn_purchase_id']==null){
                    
                        $response=array(
                            'success'=>false,
                            'message'=>'Akun Pajak pembelian PPN belum ditentukan'
                        );
                    }else{
                    
                        $check = $this->check_coa($data['coa_ppn_purchase_id'],$data['idunit']);

                        if(!$check){
                            $response =  array('success'=>false,
                                                'message'=>'Akun pajak penjualan PPN salah'
                            );
                        }
                    }
                }

                if($data['is_tax_pph23']==1){
                    
                    if($data['coa_pph23_purchase_id']==0||$data['coa_pph23_purchase_id']==''||$data['coa_pph23_purchase_id']==null){
                    
                        $response=array(
                            'success'=>false,
                            'message'=>'Akun Pajak pembelian PPN belum ditentukan'
                        );
                    }else{
                    
                        $check = $this->check_coa($data['coa_pph23_purchase_id'],$data['idunit']);

                        if(!$check){
                            $response =  array('success'=>false,
                                                'message'=>'Akun pajak dibayar dimuka (PPH) salah'
                            );
                        }
                    }
                }   

            }else{
                
                $response = array(
                    'success'=>false,
                    'message'=>'Data pajak terpilih tidak ditemukan'    
                );
            }
        }

        return $response;
    }

	public function save_purchasing_post(){
		
        // $coa = json_decode($this->m_data->getIdAccount(14,$this->post('idunit'))); //hutang
        // if(isset($coa->{'success'})){
        //     if(!$coa->{'success'}){
        //         $this->set_response(array(
        //             'success'=>false,
        //             'message'=>$coa->{'message'}
        //         ), REST_Controller::HTTP_BAD_REQUEST); 
        //         return false;
        //     }
        // }

        $purchase_id_po = $this->post('purchase_id_po');
        // $invoice_no = $this->post('invoice_no');
        $customer_id = $this->post('idcustomer');
        $customerPurchaseOrder = $this->post('customerPurchaseOrder');
        $invoice_date = backdate2($this->post('invoice_date'));
        $due_date = backdate2($this->post('due_date'));
        $status = $this->post('status');
        $memo = $this->post('memo');
        $invoice_no = $this->post('noinvoicePurchasOrder');
        $include_tax = $this->post('include_tax')==false ? 0 : 1;
        $freight = str_replace(',', '', $this->post('freight'));
        $customer_type = $this->post('customer_type');
        $tax_id  = $this->post('tax_id') =='' ? null : $this->post('tax_id');

        // echo $freight;
        $purchase_item= json_decode($this->post('purchase_item'));

        //cek coa hutang pajak
        // $coa_tax = $this->chech_tax($tax_id);
        // if(!$coa_tax['success']){
        //         $this->set_response(array(
        //                'success'=>false,
        //                 'message'=>$coa['message']
        //         ), REST_Controller::HTTP_OK); 
        //         return false;
        // }
        // echo $include_tax;
        
        $summary_purchase = $this->summary_purchase_inv($include_tax,$freight,$purchase_item,$tax_id);
        // print_r($summary_purchase);
        // die;
        //member
        if( $this->post('customer_type')=='member'){
            // $this->load->model('m_member');
            // $id_member = $this->m_member->check_id($this->post('no_member'))['id_member'];
            $id_member =$this->post('customer_id');
            $idcustomer = $id_member;
        } else {
            $id_member = null;
            $idcustomer =$this->post('customer_id');     
        }

            $data_purchase = array(
            'idtax'=>$tax_id,
            'id_member'=> $id_member == '' ? null : $id_member,
            'idcustomer'=> $idcustomer == '' ? null : $idcustomer,
            'customer_type'=> $this->post('customer_type')=='member' ? 1 : 2,
            'subtotal'=> $summary_purchase['sub_total'],
            'freight'=>$freight==''?null:$freight,
            'disc'=>$summary_purchase['total_disc'],
            'total'=>$summary_purchase['total'],
            'tax'=>round($summary_purchase['total_tax']),
            'totalamount'=>$summary_purchase['grand_total'],
            'unpaid_amount'=>$summary_purchase['grand_total'],
            'idunit'=>$this->post('idunit'),
            'due_date'=>$due_date,
            'comments'=>$memo,
            'invoice_status'=>$status,
            'date_purchase'=>$invoice_date,
            'invoice_date'=>$invoice_date,
            'include_tax'=>$include_tax
        );
        // print_r($data_purchase);    
        // die();    
        $total_before_tax = $summary_purchase['total'];
    
        // $purchase_id = $this->post('purchase_id');
        if($purchase_id_po==''){
            // die;
            $purchase_id_po = $this->m_data->getPrimaryID2(null,'purchase','purchase_id');

            //no order
            $params = array(
                'idunit' => $this->post('idunit'),
                'prefix' => 'PO',
                'table' => 'purchase',
                'fieldpk' => 'purchase_id',
                'fieldname' => 'no_purchase_order',
                'extraparams'=> null,
            );
            // $this->load->library('../controllers/setup');
            $no_purchase_order = $this->m_data->getNextNoArticle($params);
            //end no order

            // echo $this->db->last_query();
            $data_purchase['invoice_no'] = $invoice_no;
            $data_purchase['no_purchase_order'] = $no_purchase_order;
            $data_purchase['purchase_id'] = $purchase_id_po;
            $data_purchase['paidtoday'] = 0;
            $data_purchase['unpaid_amount'] = $data_purchase['totalamount'];
            $data_purchase['datein'] = date('Y-m-d H:m:s');
            $data_purchase['userin'] = $this->post('user_id');

            $this->db->insert('purchase',$data_purchase);

        } else {
            $data_purchase['invoice_no'] = $invoice_no;
            $data_purchase['datemod'] = date('Y-m-d H:m:s');
            $data_purchase['usermod'] = $this->post('user_id');
            $this->db->where('purchase_id',$purchase_id_po);
            $this->db->update('purchase',$data_purchase);

            if($purchase_id_po!=''){
                $this->load->library('journal_lib');

                $q = $this->db->get_where('purchase',array('purchase_id'=>$purchase_id_po))->row();
                if($q->idjournal!=null){
                    $this->journal_lib->delete($q->idjournal);
                }                       
            }
        }
        $this->save_puchase_items($purchase_id_po,$purchase_item,$this->post('user_id'));

        $get_supplier = $this->m_purchase->data($this->post('idunit'),$purchase_id_po);
        // die;
        //create journal
        //PAJAK
        // $idjournal = null;
        // if($tax_id!='' && $tax_id!=null){
        //     $qtax = $this->m_tax->get($tax_id);
        //     $qtax = $qtax['data'][0];
            
        //     if($qtax['is_tax_ppn']==1 && $qtax['is_tax_pph23']==0){
        //         //PPN SAJA 
        //         $idjournal = $this->m_purchase->jurnal_purchase_invoice_ppn($invoice_date,'Purchase Order for '.$get_supplier[0]['namecustomer'].' #'.$get_supplier[0]['no_purchase_order'],$total_before_tax,$data_purchase['totalamount'],$this->post('idunit'),$this->post('user_id'),$purchase_id_po,$tax_id);
        //     }

        //     if($qtax['is_tax_ppn']==0 && $qtax['is_tax_pph23']==1){
        //         //PPH SAJA 
        //         $idjournal = $this->m_purchase->jurnal_purchase_invoice_pph($invoice_date,'Purchase Order for '.$get_supplier[0]['namecustomer'].' #'.$get_supplier[0]['no_purchase_order'],$total_before_tax,$data_purchase['totalamount'],$this->post('idunit'),$this->post('user_id'),$purchase_id_po,$tax_id);
        //     }

        //     if($qtax['is_tax_ppn']==1 && $qtax['is_tax_pph23']==1){
        //         //PPN dan PPH 
        //         $idjournal = $this->m_purchase->jurnal_purchase_invoice_ppn_n_pph($invoice_date,'Purchase Order for '.$get_supplier[0]['namecustomer'].' #'.$get_supplier[0]['no_purchase_order'],$total_before_tax,$data_purchase['totalamount'],$this->post('idunit'),$this->post('user_id'),$purchase_id_po,$tax_id);
        //     }

        //     // [is_tax_ppn] => 0    [is_tax_pph23] => 0

        //     if($qtax['is_tax_ppn']==0 && $qtax['is_tax_pph23']==0){
        //         //Non Pajak 
        //         $idjournal = $this->jurnal_purchase_invoice($invoice_date,'Purchase Order for '.$get_supplier[0]['namecustomer'].' #'.$get_supplier[0]['no_purchase_order'],$total_before_tax,$data_purchase['totalamount'],$this->post('idunit'),$this->post('user_id'),$purchase_id_po,$tax_id);
                    
        //     }

        // }else{
        //     $idjournal = $this->jurnal_purchase_invoice($invoice_date,'Purchase Order for '.$get_supplier[0]['namecustomer'].' #'.$get_supplier[0]['no_purchase_order'],$total_before_tax,$data_purchase['totalamount'],$this->post('idunit'),$this->post('user_id'),$purchase_id_po,$tax_id);
        // }
        // //END PAJAK
        
        // $this->db->where('purchase_id',$purchase_id_po);
        // $this->db->update('purchase',array(
        //             'idjournal'=>$idjournal     
        // ));

        //end journal

        $this->db->trans_complete(); 
        
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();

            $this->set_response(array(
                'success'=>false,
                'message'=>'Data gagal disimpan'
            ), REST_Controller::HTTP_BAD_REQUEST); 
        } 
        else {
            $this->db->trans_commit();
            $this->set_response(array(
                'success'=>true,
                'message'=>'Data berhasil disimpan'
            ), REST_Controller::HTTP_OK); 
        }

	}

     private function summary_purchase_inv($include_tax,$shipping_cost,$purchase_item,$tax_id){

        $sub_total = 0;
        $total_disc = 0;
        $dpp = 0;
        $total_tax = 0;
        $total_sales = 0;
        $rate_tax = 0;
        $nametax=null;
        $is_tax_ppn=null;
        $is_tax_pph23=null;
        $coa_ppn_rate=0;
        $coa_pph23_rate=0;

        if($tax_id!=null && $tax_id!=''){
            
            $url = $this->rest_client;
            $response = $url->get('preferences/tax?key='.COOP_APIKEY.'&id='.$tax_id,[
                'auth'=>[COOP_APIKEY,''],
                'form_params'=>array('id'=>$tax_id),
                'http_errors'=>true
            ]);

            $b = json_decode($response->getBody());
            $q = $b->rows[0];
            // print_r($q);
            $nametax  = $q->{'nametax'};

            $rate_tax = $q->{'tax_rate'};
            
            $is_tax_ppn=$q->{'is_tax_ppn'};
            $is_tax_pph23=$q->{'is_tax_pph23'};

            $coa_ppn_rate=$q->{'coa_ppn_rate'};
            $coa_pph23_rate=$q->{'coa_pph23_rate'};
        }

        foreach ($purchase_item as $key => $v) {
            $total = $v->{'qty'}*$v->{'price'};
            $discount = ($total/100)*$v->{'disc'};

            $total_disc+=$discount;

            if((isset($is_tax_ppn) && isset($is_tax_pph23)) && ($is_tax_ppn==1 && $is_tax_pph23==1)){
               // echo $total; 
               $ppn_tax          = $total*($coa_ppn_rate/100);
               // $total_tax_pnn   += $total+$ppn_tax; 
               $pph23_tax        = $total*($coa_pph23_rate/100);
               // $total_tax_pph23 += $pph23_tax;

               $tax        = $ppn_tax+$pph23_tax;
               $total_tax += $tax;
               // echo $tax;
            }

            if ((isset($is_tax_ppn) && isset($is_tax_pph23)) && ($is_tax_ppn==1 && $is_tax_pph23==0)) {
                $tax = $total*($coa_ppn_rate/100);
                $total_tax+=$tax;
            
            }if((isset($is_tax_ppn) && isset($is_tax_pph23)) && ($is_tax_ppn==0 && $is_tax_pph23==1)){
                $tax = $total*($coa_pph23_rate/100);
                $total_tax+=$tax;
            }
            
            if((isset($is_tax_ppn) && isset($is_tax_pph23)) && ($is_tax_ppn==0 && $is_tax_pph23==0)){
                $tax = $total*($rate_tax/100);
                $total_tax+=$tax;
            }

            $sub_total+=$total;

            // if($include_tax==1){
            //     $total_per_row = ($total - $discount);
            // } else {
            //     $total_per_row = ($total - $discount)+$tax;
            // }
        }

        $total = ($sub_total-$total_disc)+$shipping_cost;
        if($include_tax==1){
            $grand_total = $total;
        } else {

            if((isset($is_tax_ppn) && isset($is_tax_pph23)) && ($is_tax_ppn==1 && $is_tax_pph23==0)){
                $grand_total = $total+$total_tax;
            }

            elseif((isset($is_tax_ppn) && isset($is_tax_pph23)) && ($is_tax_ppn==0 && $is_tax_pph23==1)){
                $grand_total = $total;

            }

            elseif((isset($is_tax_ppn) && isset($is_tax_pph23)) && ($is_tax_ppn==1 && $is_tax_pph23==1)){
                $grand_total = $total+$ppn_tax-$pph23_tax;
            }

            elseif((isset($is_tax_ppn) && isset($is_tax_pph23)) && ($is_tax_ppn==0 && $is_tax_pph23==0)){
                // $tax = $total*($rate_tax/100);
                 $grand_total = $total+$total_tax;
            }else{
                $grand_total = $total;
            }
        }

        $data = array(
            'nametax'=>$nametax,
            'is_tax_ppn'=>$is_tax_ppn,
            'is_tax_pph23'=>$is_tax_pph23,
            'coa_ppn_rate' => $coa_ppn_rate,
            'coa_pph23_rate' => $coa_pph23_rate,
            'sub_total'=>$sub_total,
            'total_disc'=>$total_disc,
            'shipping_cost'=>$shipping_cost,
            'total'=>$total,
            'total_tax'=>$total_tax,
            'grand_total'=>$grand_total
        );

        return $data;
    }    

	public function summary_purchase_inv_post(){

		$include_tax = intval($this->post('include_tax'));
        $shipping_cost = intval($this->post('shipping_cost'));
        $Purchase_item = json_decode($this->post('Purchase_item'));
        $tax_id = intval($this->post('tax_id'));

        $data = $this->summary_purchase_inv($include_tax,$shipping_cost,$Purchase_item,$tax_id);

        $this->set_response($data, REST_Controller::HTTP_OK); 

	}

    public function save_puchase_items($purchase_id,$purchase_item,$user_id){

        foreach ($purchase_item as $key => $value) {
            # code...
            $price_item = $value->qty*$value->price;

            $disc = $price_item*$value->disc/100;
            
            $items=array(
                  "purchase_id" =>$purchase_id,
                  "qty" =>$value->qty,
                  "price" =>$value->price,
                  "disc" =>$value->disc,
                  "total" =>($price_item-$disc),
                  "ratetax" =>$value->rate,
                  "deleted" =>0,
                  "product_id"=>$value->product_id,
                  "description" =>$value->desc,
                  "total_tax" => 0,
            );

            if($value->purchase_item_id==''||$value->purchase_item_id==null){
                $items['purchase_item_id']=$this->m_data->getPrimaryID2(null,'purchase_item','purchase_item_id');
                $items['userin'] = $user_id;
                $items['datein'] = date('Y-m-d H:i:s');
                $this->db->insert('purchase_item',$items);
            }else{
                $items['usermod'] = $user_id;
                $items['datemod'] = date('Y-m-d H:i:s');
                $this->db->where('purchase_item_id', $value->purchase_item_id);
                $this->db->update('purchase_item', $items);
            }
        }
    }

    public function update_price_by($purchase_id_po,$last_poId){
       
        $new_price = null;
        //pembelian terakhir
        $q1 = $this->db->get_where('purchase',array('purchase_id'=>$last_poId,'idunit'=>$this->post('idunit')));
        $num_old_po = count($q1->row());

        //pembelian terbaru
        $q2 =$this->db->get_where('purchase',array('purchase_id'=>$purchase_id_po,'idunit'=>$this->post('idunit')));
        $num_new_po = count($q2->row());

        $total_po = $num_new_po+$num_old_po;

    }

    public function data_get(){

        $idunit      = $this->get('idunit');
        $option      = $this->get('option');
        $purchase_id = $this->get('purchase_id');

        $d = $this->m_purchase->data($idunit,$purchase_id,$option,$this->get('startdate'),$this->get('enddate'),$this->get('query'),$this->get('status'));
       
        $message=array('success' =>true,'num_rows'=>count($d),'results'=>count($d),'rows'=>$d);
        // echo $this->db->last_query();
        $this->set_response($message, REST_Controller::HTTP_OK); 
    }

    public function summary_get(){
        $idunit = $this->get('idunit');

        if ($idunit <= 0){
            $this->response(array('success'=>false,'message'=>'id coop not found'), REST_Controller::HTTP_BAD_REQUEST); 
        }

        $d = $this->m_purchase->summary($idunit);
        // $message=array('success' =>true,'num_rows'=>count($d),'results'=>count($d),'rows'=>$d);
        $this->set_response($d, REST_Controller::HTTP_OK); 
    }

    function jurnal_purchase_invoice($date,$memo,$before_tax,$amount,$idunit,$userin,$purchase_id,$tax_id){
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
    

        //hutang pajak - credit
        // https://www.online-pajak.com/jurnal-ppn
        $qtax = $this->db->query("select c.coa_ap_id,sum(total_tax) as total_tax,c.nametax
                                    from purchase_item a
                                    join purchase b ON a.purchase_id = b.purchase_id
                                    join tax c ON a.ratetax = c.rate and c.idunit = b.idunit and c.deleted = 0
                                    where a.purchase_id = $purchase_id and a.ratetax!=0
                                    GROUP BY c.coa_ap_id,c.nametax");
        // echo $this->db->last_query();
        // die;
        $total_tax =0;
        foreach ($qtax->result() as $r) {
            $idaccount = $r->coa_ap_id;
            // $amount = $r->total_tax;
            $total_tax+=$r->total_tax;
            $curBalanceK = $this->m_account->getCurrBalance($idaccount, $idunit);
            $newBalanceK = $curBalanceK + $r->total_tax;

            $ditem = array(
                'idjournal' => $idjournal,
                'idaccount' => $idaccount,
                'debit' => 0,
                'credit' => $r->total_tax,
                'lastbalance' => $curBalanceK,
                'currbalance' => $newBalanceK
            );
            $this->db->insert('journalitem', $ditem);
            $this->m_account->saveNewBalance($idaccount, $newBalanceK, $idunit,$userin);
            $this->m_account->saveAccountLog($idunit,$idaccount,$r->total_tax,0,$date,$idjournal,$userin);
        }

        //Hutang - credit
        $idaccount = $this->m_data->getIdAccount(14, $idunit);
        $amount = $amount-$total_tax;
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

    function remove_post(){

        $v = $this->m_purchase->remove($this->post('id'),$this->post('user_id'),$this->post('idunit'));
        if($v['success']){
            $this->response($v, REST_Controller::HTTP_OK);
        } else {
            $this->response($v, REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    function save_payment_post(){
        $this->db->trans_begin();
        $data = json_decode($this->post('datagrid'));
       
        $date_payment = backdate2($this->post('date_payment'));

        $idaccount = $this->post('idaccount');
        $notes = $this->post('notes');
       
        $total_payment = 0;

        foreach ($data as $v) {
            # code...
            // print_r($v);
            $cek  = $this->db->query("select paidtoday, unpaid_amount from purchase where purchase_id=".$v->{'purchase_id'});
            
            if($cek->num_rows()>0){
                
                $rcek = $cek->row();
                
                $new_unpaid = $rcek->unpaid_amount-$v->{'pay_amount'};
                $paidtoday  = $rcek->paidtoday+$v->{'pay_amount'};

                if($new_unpaid <=0){

                    $invoice_status=5;
                }else{
                    $invoice_status=4;
                }
                // echo $v->{'pay_amount'};
                // die;
       
                $items_pay=array(
                    'purchase_id'=>$v->{'purchase_id'},
                    'invoice_status'=>$invoice_status,
                    'unpaid_amount'=>$new_unpaid,
                    'paidtoday'=>$paidtoday
                );

                $this->db->where('purchase_id',$v->{'purchase_id'});
                $this->db->update('purchase',$items_pay);
            

                // $idjournal = $this->jornal_purchase_payment($v->{'purchase_id'},$v->{'pay_amount'},$idaccount,$date_payment);
                // $this->log_sales_journal(2,$idjournal,$value->{'idsales'},$idaccount);

                // $this->db->where('purchase_id',$v->{'purchase_id'});
                // $this->db->update('purchase',array(
                //       'idjournal_do'=>$idjournal  
                // ));
                $total_payment+=$v->{'pay_amount'};
            }
        }

        if($this->db->trans_status()===false){
            $this->db->trans_rollback();
            $message=array('status'=>true,'message'=>'Data pembayaran gagal tersimpan. Mohon coba kembali.');
            $this->set_response($message,REST_Controller::HTTP_BAD_REQUEST);

        }else{
            $this->db->trans_commit();
            $message=array('status'=>true,'message'=>'Data pembayaran berhasil tersimpan');
            $this->set_response($message,REST_Controller::HTTP_OK);

        }
    }

    public function jornal_purchase_payment($purchase_id,$pay_amount,$idaccount,$date_payment){

        $q = $this->db->query("select idjournal,no_purchase_order,idtax,include_tax from purchase where purchase_id=$purchase_id")->row();

        //coa
        $qcoa =  $this->m_data->getIdAccount(14, $this->post('idunit'));

        //coa debit = kas
        $coa_debit = $qcoa;

        //coa credit = piutang
        $coa_credit = $idaccount;
        
        $get_supplier = $this->m_purchase->data($this->post('idunit'))[0];
        

        return $this->create_jurnal_purchase_payment($purchase_id,$date_payment,'Purchase Payment for '.$get_supplier['namecustomer'].' #'.$q->no_purchase_order,$pay_amount,$this->post('idunit'),$this->post('user_id'),$coa_debit,$coa_credit,$q->idtax,$q->include_tax);
    }

    public function create_jurnal_purchase_payment($purchase_id,$date,$memo,$amount,$idunit,$userin,$coa_debit,$coa_credit,$tax_id,$include_tax){
        $tgl = explode("-", $date);
        
        $idjournal = $this->m_data->getPrimaryID2(null,'journal', 'idjournal');

        $d = array(
            'idjournal' => $idjournal,
            'idjournaltype' => 10, //piutang
            'nojournal' => rand(11111,99999),
//                    name character varying(225),
            'datejournal' => $date,
            'memo' => $memo,
            'totaldebit' => $amount,
            'totalcredit' => $amount,
//                    'totaltax' double precision,
//                    isrecuring boolean,
            'year' => $tgl[0],
            'month' => $tgl[1],
//                    display integer,
            'userin' => $userin,
            'usermod' =>$userin,
            'datein' => date('Y-m-d H:m:s'),
            'datemod' => date('Y-m-d H:m:s'),
            'idunit' => $idunit
        );
        $this->db->insert('journal', $d);

        $total_pajak=0;
        $totalamount=0;
     
        if($tax_id!='' &&$tax_id!=null){
            // echo "string";
            $qtax =$this->m_tax->get($tax_id);
            if(isset($qtax['data'][0])){
                $data_tax = $qtax['data'][0];
                if($data_tax['is_tax_ppn']==1 && $data_tax['is_tax_pph23']==0){
                    //pajak ppn
                    if($include_tax==1){
                        $idaccount   = $data_tax['coa_ppn_purchase_id'];
                        $tax_amount  = $amount*($data_tax['coa_ppn_rate']/100);
                        $total_pajak += $tax_amount;
                        $totalamount = $amount+$tax_amount;
                    }
                    if($include_tax==0){
                        $totalamount = $amount;
                    }
                    
                }

                if($data_tax['is_tax_ppn']==0 && $data_tax['is_tax_pph23']==1){
                    //pajak pph
                    if($include_tax==1){
                        $idaccount   = $data_tax['coa_ppn_purchase_id'];
                        $tax_amount  = $amount*($data_tax['coa_pph23_rate']/100);
                        $total_pajak += $tax_amount;
                        $totalamount = $amount-$tax_amount;
                    }
  
                    if ($include_tax==0) {
                        $totalamount = $amount;
                    }
                    
                }

                if($data_tax['is_tax_ppn']==1 && $data_tax['is_tax_pph23']==1){
                    //pajak pph23_n_ppn
                     if($include_tax==1){
                        $idaccount      = $data_tax['coa_ppn_purchase_id'];
                        $tax_ppn        = $amount*($data_tax['coa_ppn_rate']/100);
                        $pph23_tax      = $amount*($data_tax['coa_pph23_rate']/100);
                        $totalamount    = $amount+$tax_ppn-$pph23_tax;
                    }

                    if($include_tax==0){
                        $totalamount = $amount;
                    }
                } 

                if($data_tax['is_tax_ppn']==0 && $data_tax['is_tax_pph23']==0){
                    //pajak pph23_n_ppn
                     if($include_tax==1){
                        $totalamount = $amount;
                    }

                    if($include_tax==0){
                        $totalamount = $amount;
                    }
                }  
            }
        }else{
            $totalamount = $amount;
        }

        //debit -piutang 
        $idaccount = $coa_debit;
        $curBalanceD = $this->m_account->getCurrBalance($idaccount, $idunit);
        $newamount      = $totalamount;
        $newBalanceD = $curBalanceD - $totalamount;
        $ditem = array(
            'idjournal' => $idjournal,
            'idaccount' => $idaccount,
            'debit' => $newamount,
            'credit' => 0,
            'lastbalance' => $curBalanceD,
            'currbalance' => $newBalanceD
        );
        // print_r($ditem);
        // die;
        
        $this->db->insert('journalitem', $ditem);
        $this->m_account->saveNewBalance($idaccount, $newBalanceD, $idunit,$userin);
        $this->m_account->saveAccountLog($idunit,$idaccount,0,$newamount,$date,$idjournal,$userin);


        //credit - kas
        $idaccount = $coa_credit;
        $curBalanceK = $this->m_account->getCurrBalance($idaccount, $idunit);
        $newBalanceK = $curBalanceK - $totalamount;

        $ditem = array(
            'idjournal' => $idjournal,
            'idaccount' => $idaccount,
            'debit' => 0,
            'credit' => $totalamount,
            'lastbalance' => $curBalanceK,
            'currbalance' => $newBalanceK
        );
        $this->db->insert('journalitem', $ditem);
        $this->m_account->saveNewBalance($idaccount, $newBalanceK, $idunit,$userin);
        $this->m_account->saveAccountLog($idunit,$idaccount,$totalamount,0,$date,$idjournal,$userin);

        return $idjournal;
    }

    public function data_purchase_list_get(){

        $idunit = $this->get('idunit');
        $option = $this->get('option');
        // $query  = $this->get('query');

        $d = $this->m_purchase->data_purchase_list($idunit,null,$option,$this->get('startdate'),$this->get('enddate'),$this->get('query'));
       
        $message=array('success' =>true,'num_rows'=>count($d),'results'=>count($d),'rows'=>$d);
        $this->set_response($message, REST_Controller::HTTP_OK); 
    }

    public function data_purchase_item_list_get(){

        $idunit = $this->get('idunit');
        $id     = $this->get('id');  
        $d = $this->m_purchase->data_purchase_items($idunit,$id);
        $data=[];

        if(count($d)>0){
            foreach ($d as $key => $value) {
                # code...
                // print_r($value);
                $tolal_price=$value['price']*$value['qty'];
                $data[]=array(
                    'purchase_id'=>$value['purchase_id'],
                    'product_id'=>$value['product_id'],
                    'purchase_item_id'=>$value['purchase_item_id'],
                    'qty'=>$value['qty'],
                    'total'=>$tolal_price,
                    'product_name'=>$value['product_name'],
                    'description'=>$value['description'],
                    'idunit'=>$value['idunit'],
                    'price'=>$value['price']
                );
            }
        }

        $message=array('success' =>true,'num_rows'=>count($data),'results'=>count($data),'rows'=>$data);
        $this->set_response($message, REST_Controller::HTTP_OK);                
    }

    public function purchase_item_get(){

        $idunit      = $this->get('idunit');
        $except_id   = json_decode($this->get('except_id'));  
        $purchase_id = $this->get('id');
        $query       = $this->get("query");

        // echo $purchase_id;
        if ($purchase_id < 0){
            $this->response(array('success'=>false,'message'=>'purchase_id not found'), REST_Controller::HTTP_BAD_REQUEST); 
        }

        $data = $this->m_purchase->item_data($purchase_id,$idunit,$except_id,$query);
        // echo $this->db->last_query();
        $num_rows = count($data);
            $message = [
                    'success' => true,
                    'numrow'=>$num_rows,
                    'results'=>$num_rows,
                    'rows' => $data
            ];

        $this->set_response($message, REST_Controller::HTTP_OK);
    }

    public function save_purchase_return_post(){

        $Purchase_return_id = $this->post('Purchase_return_id');
        $purchase_id        = $this->post('Purchase_id');
        $return_date        = $this->post('date_return');
        $statusform_return  = $this->post('statusform_return');
        $return_item        = json_decode($this->post('return_item'));

        $total_qty_return = 0;
        $total_amount_return = 0;

        $cek = $this->db->query("select count(*) as total_purchase_receipt from purchase_receipt where purchase_id=$purchase_id")->row();
        
        if($cek->total_purchase_receipt <= 0){
            $this->set_response(array(
                'success'=>false,
                'message'=>'Belum ada barang yang diterima, proses tidak dapat diteruskan'
            ), REST_Controller::HTTP_OK); 
            return false;
        }
        // ;
        foreach ($return_item as $v) {
            # code...
           $total_qty_return += $v->{'qty_retur'};
           $total_amount_return += $v->{'total_return'};
        }

        $data = array(
            "purchase_id" => $purchase_id,
            "status" => 2,
            "idunit"=>$this->post('idunit'),
            "date_return" => backdate2($this->post('return_date')),
            "memo"=> $this->post('memo'),
            "total_qty_return" => $total_qty_return,
            "total_amount_return" => $total_amount_return,
            "datein" => date('Y-m-d H:m:s'),
            "userin" => $this->post('user_id'),
            "datemod"  => date('Y-m-d H:m:s'),
            "usermod"  => $this->post('user_id'),
        );  

        //save purchace return
        if($Purchase_return_id!='' && $Purchase_return_id!=null && $Purchase_return_id!=0){
            //update
            $data['purchase_return_id']=$Purchase_return_id;
            $this->db->where('purchase_return_id',$Purchase_return_id);
            $this->db->update('purchase_return',$data);

            if($Purchase_return_id!=''){
                $this->load->library('journal_lib');

                $q = $this->db->get_where('purchase_return',array('purchase_return_id'=>$Purchase_return_id))->row();
                if($q->journal_id!=null){
                    $this->m_purchase->rollback($q->journal_id);
                }  

                $data['no_return'] = $q->{'no_return'};                     
            }

        } else {
            //insert
            $data['purchase_return_id'] = $this->m_data->getPrimaryID2(null,'purchase_return', 'purchase_return_id');

            $params = array(
                'idunit' => $this->post('idunit'),
                'prefix' => 'PR',
                'table' => 'purchase_return',
                'fieldpk' => 'purchase_return_id',
                'fieldname' => 'no_return',
                'extraparams'=> null,
            );
            // $this->load->library('../controllers/setup');
            $noarticle = $this->m_data->getNextNoArticle($params);
            $data['no_return'] = $noarticle;

            $this->db->insert('purchase_return',$data);
        }

        //save purchace return
        foreach ($return_item as $v) {
            # code...
            if($v->{'qty_retur'}!=0){
                $ditem=array(
                        "purchase_return_id"=>$data['purchase_return_id'],    
                        "purchase_item_id" => $v->{'purchase_item_id'},
                        "qty_purchase" => $v->{'qty_purchase'},
                        "qty_retur" => $v->{'qty_retur'},
                        "notes" => $v->{'notes'},
                        "datemod" => date('Y-m-d H:m:s')
                    );

                if($v->{'purchase_return_item_id'}==''){
                    $ditem['purchase_return_item_id'] = $this->m_data->getPrimaryID2(null,'purchase_return_item', 'purchase_return_item_id');
                    $this->db->insert('purchase_return_item',$ditem);
                }else {
                    $ditem['purchase_return_item_id'] = $v->{'purchase_return_item_id'};
                    $this->db->where('purchase_return_item_id',$ditem['purchase_return_item_id']);
                    $this->db->update('purchase_return_item',$ditem);
                }
            }
        }  

        $q  = $this->db->get_where('purchase',array('purchase_id'=>$purchase_id))->row();
        
        // print_r($q);
        
        // if($q->idtax!=''){
        //     $qtax =$this->m_tax->get($q->{'idtax'});
            
        //     if(isset($qtax['data'][0])){

        //         $data_tax = $qtax['data'][0];
        //         if($data_tax['is_tax_ppn']==1 && $data_tax['is_tax_pph23']==0){
        //             $idjournal = $this->m_purchase->journal_return_ppn($data['date_return'],'purchase return #'.$data['no_return'],$total_amount_return,$this->post('idunit'),$this->post('user_id'), $data['purchase_return_id'],$q->{'idtax'});
        //         }

        //         if($data_tax['is_tax_ppn']==0 && $data_tax['is_tax_pph23']==1){
        //             $idjournal = $this->m_purchase->journal_return_pph23($data['date_return'],'purchase return #'.$data['no_return'],$total_amount_return,$this->post('idunit'),$this->post('user_id'), $data['purchase_return_id'],$q->{'idtax'});

        //         }

        //         if($data_tax['is_tax_ppn']==1 && $data_tax['is_tax_pph23']==1){
        //             $idjournal = $this->m_purchase->journal_return_ppnAndpph23($data['date_return'],'purchase return #'.$data['no_return'],$total_amount_return,$this->post('idunit'),$this->post('user_id'), $data['purchase_return_id'],$q->{'idtax'});

        //         }

        //         if($data_tax['is_tax_ppn']==0 && $data_tax['is_tax_pph23']==0){
        //             $idjournal = $this->m_purchase->journal_return_non_pajak($data['date_return'],'purchase return #'.$data['no_return'],$total_amount_return,$this->post('idunit'),$this->post('user_id'), $data['purchase_return_id'],$q->{'idtax'});

        //         }

        //     }   
        // }else{
        //         $idjournal = $this->m_purchase->journal_return_non_pajak($data['date_return'],'purchase return #'.$data['no_return'],$total_amount_return,$this->post('idunit'),$this->post('user_id'), $data['purchase_return_id'],$q->{'idtax'});

        // }

        // $this->db->where('purchase_return_id',$data['purchase_return_id']);
        // $this->db->update('purchase_return',array(
        //             'journal_id'=>$idjournal     
        // ));         

        if($this->db->trans_status() === false){
            $this->db->trans_rollback();
            $json = array('success'=>false,'message'=>'Retur Pembelian gagal tersimpan. Mohon coba kembali.');
            $this->set_response($json, REST_Controller::HTTP_BAD_REQUEST); 
        }else{

            if($statusform_return=='edit'){
                $this->edit_return_stock($data['purchase_return_id'],$type=7);
            }else{
                $this->m_purchase->after_return_purchace($data['purchase_return_id'],$type=7,$this->post('idunit'));

            }

            $this->db->trans_commit();
            $json = array('success'=>true,'message'=>'Retur Pembelian berhasil tersimpan');
            $this->set_response($json, REST_Controller::HTTP_CREATED); 
        }       
    }

    public function data_purchase_return_get(){

        $startdate = str_replace("T00:00:00", "", $this->get('startdate'));
        $enddate = str_replace("T00:00:00", "", $this->get('enddate'));
        $idunit=$this->get('idunit');
        $purchase_return_id=$this->get('id');
        $query= $this->get('query');

        $data = $this->m_purchase->data_purchase_return($idunit,$purchase_return_id,$startdate,$enddate,$query);
        $num_rows = count($data);
            $message = [
                    'success' => true,
                    'numrow'=>$num_rows,
                    'results'=>$num_rows,
                    'rows' => $data
            ];
        $this->set_response($message, REST_Controller::HTTP_OK); 
    }

    public function remove_purchase_return_post(){

        $v = $this->m_purchase->remove_purchase_return($this->post('id'),$this->post('idunit'));
        if($v['success']){
            $this->response($v, REST_Controller::HTTP_OK);
        } else {
            $this->response($v, REST_Controller::HTTP_OK);
        }
    }

    public function data_purchase_receipt_get(){
        
        $startdate = str_replace("T00:00:00", "", $this->get('startdate'));
        $enddate = str_replace("T00:00:00", "", $this->get('enddate'));
        $idunit=$this->get('idunit');
        $purchase_receipt_id=$this->get('id');
        $query=$this->get('query');

        $data = $this->m_purchase->data_purchase_receipt($idunit,$purchase_receipt_id,$startdate,$enddate,$query);
        $num_rows = count($data);
            $message = [
                    'success' => true,
                    'numrow'=>$num_rows,
                    'results'=>$num_rows,
                    'rows' => $data
            ];
        $this->set_response($message, REST_Controller::HTTP_OK);   
    }

    public function save_purchase_receipt_post(){
        $this->db->trans_begin();

        $purchase_receive_id= $this->post('purchase_receive_id');
        $purchase_id= $this->post('purchase_id');
        $no_purchase_receipt= $this->post('no_purchase_receipt');
        $receive_date= str_replace('T00:00:00', '', $this->post('receive_date'));
        $receipt_item=  json_decode($this->post('receipt_item'));
        $statusform_receive=$this->post('statusform_receive');
        $memo=$this->post('memo');

        $total_amount_receive = 0;
        $total_qty_receive=0;
        $total_rest_qty=$this->post('total_rest_qty');
        // echo $total_rest_qty;
        // die;
        foreach ($receipt_item as $v) {
            # code...
            $total_qty_receive += $v->{'qty_received'};
            $total_amount_receive  += $v->{'total_received'};
        }
        
        if($total_rest_qty==0){
            $status=3;
        }else if($total_rest_qty>0){
            $status=2;   
        }
      


        $data=array(
            "purchase_id"=>$purchase_id,
            "receipt_date"=>$receive_date,
            "total_received"=>$total_amount_receive,
            "memo"=>$memo,
            "status"=>$status
        );

        if($purchase_receive_id!='' || $purchase_receive_id!=null ||  $purchase_receive_id!=0){
            if($statusform_receive=='input'){
                $qty = $this->db->query("select total_qty_received,total_rest_qty from purchase_receipt where purchase_receipt_id=$purchase_receive_id")->row();
                $data["total_qty_received"]=$qty->total_qty_received+$qty->total_rest_qty;
                $data["total_rest_qty"]=$total_rest_qty;
                
            }else if($statusform_receive=='edit'){
                $data["total_qty_received"]=$total_qty_receive;
                $data["total_rest_qty"]=$total_rest_qty;
            }
         
            $data['purchase_receipt_id']=$purchase_receive_id;
            $this->db->where('purchase_receipt_id',$purchase_receive_id);
            $this->db->update('purchase_receipt',$data);

            /*if($purchase_receive_id!=''){
                $this->load->library('journal_lib');

                $q = $this->db->get_where('purchase_receipt',array('purchase_receipt_id'=>$purchase_receive_id))->row();
                if($q->idjournal_receive!=null){
                    $this->journal_lib->delete($q->idjournal_receive);
                }                       
            }*/

        }else{
            $data["total_qty_received"]=$total_qty_receive;
            $data["total_rest_qty"]=$total_rest_qty;
            $data['purchase_receipt_id'] = $this->m_data->getPrimaryID2(null,'purchase_receipt', 'purchase_receipt_id');
            $data['purchase_receipt_no'] = $no_purchase_receipt;
            $this->db->insert('purchase_receipt',$data);
        }

        foreach ($receipt_item as $value) {
            # code...
            $data_item=array(
                  "purchase_item_id"=>$value->{'purchase_item_id'},
                  "purchase_receipt_id"=>$data['purchase_receipt_id'],
                  "qty_received"=>$value->{'qty_received'},
                  "notes"=>$value->{'notes'},
                  "datemod"=>date('Y-m-d H:i'),
            );


            if($value->{'purchase_receipt_item_id'}==''){
                $data_item['purchase_receipt_item_id'] = $this->m_data->getPrimaryID2(null,'purchase_receipt_item', 'purchase_receipt_item_id');
                $this->db->insert('purchase_receipt_item',$data_item);
            }else {
                $data_item['purchase_receipt_item_id'] = $value->{'purchase_receipt_item_id'};
                $this->db->where('purchase_receipt_item_id',$data_item['purchase_receipt_item_id']);
                $this->db->update('purchase_receipt_item',$data_item);
            }

        }

        // if($total_rest_qty==0){
        //     $status=3;
        //     // $idjournal =$this->db->query("SELECT idjournal_receive FROM purchase_receipt WHERE purchase_receipt_id=".$data['purchase_receipt_id'])->row(); 
           
        //     if($idjournal->{'idjournal_receive'}){
        //         // $this->rollback_balance($idjournal->{'idjournal_receive'});
        //     }

        // }else if($total_rest_qty>0){
        //     $status=2;
            
        //     // $this->m_purchase->jurnal_purchase_receipt($receive_date,'Purchase receipt #'.$no_purchase_receipt,$total_amount_receive,$this->post('idunit'),$this->post('user_id'),$purchase_id);
        // }

        if($this->db->affected_rows() > 0){
            
            if($statusform_receive=='edit'){
                // echo "string";
                $this->edit_receipt_stock($data['purchase_receipt_id'],$type=2);
                $this->metode_average($data['purchase_receipt_id']);
                // die;
            }else{
                // echo "xxxx";
                $this->m_purchase->after_receipt_purchace($data['purchase_receipt_id'],$type=2,$this->post('idunit'));
                $this->metode_average($data['purchase_receipt_id']);
                // die;
            }

            
            $this->db->trans_commit();
            $json = array('success'=>true,'message'=>'Penerimaan Barang berhasil tersimpan');
            $this->set_response($json, REST_Controller::HTTP_CREATED);  
        
        }else{

            $this->db->trans_rollback();
            $json = array('success'=>false,'message'=>'Penerimaan Barang gagal tersimpan. Mohon coba kembali.');
            $this->set_response($json, REST_Controller::HTTP_BAD_REQUEST);
        }  
    }

    public function remove_purchase_receipt_post(){

        $v = $this->m_purchase->remove_purchase_receipt($this->post('id'),$this->post('idunit'));

        if($v['success']){
            $this->response($v, REST_Controller::HTTP_OK);
        } else {
            $this->response($v, REST_Controller::HTTP_OK);
        }
    }

    public function data_form_receipt_get(){

        $params = array(
                'idunit' => $this->post('idunit'),
                'prefix' => 'GR',
                'table' => 'purchase_receipt',
                'fieldpk' => 'purchase_receipt_id',
                'fieldname' => 'purchase_receipt_no',
                'extraparams'=> null,
            );
            // $this->load->library('../controllers/setup');
        $no_purchase_receipt = $this->m_data->getNextNoArticle($params);
        
       
        $d =$this->m_purchase->data($this->post('idunit'),$this->get('id'));
        // echo $this->db->last_query();
        $data=[];
        $i=0;

        foreach ($d as $value) {
            # code...        
            $value['suppier_name'] = $value['namecustomer'];
             
            $value['no_purchase_receipt']=$no_purchase_receipt;
            $data[$i]=$value;

             $i++;
        }

        $message = [
                    'success' => true,
                    'numrow'=>count($d),
                    'results'=>count($d),
                    'rows' => $data
        ];

        $this->set_response($message, REST_Controller::HTTP_OK);         
    } 

    public function data_purchase_receipt_items_get(){

        $d =$this->m_purchase->data_purchase_items($this->get('idunit'),$this->get('id'));
        $data=[];
        $i=0;

        foreach ($d as $value) {
            # code...
            // print_r($value);
            if($value['receipt_status']==2){
                $value['qty_received']=$value['qty_received'];
            }else{
                $value['qty_received']=$value['qty'];
            }
            
            $value['total_received']=$value['price']*$value['qty_received'];
            $data[$i]=$value;

            $i++;
        }

        $message = [
                    'success' => true,
                    'numrow'=>count($d),
                    'results'=>count($d),
                    'rows' => $data
        ];

        $this->set_response($message, REST_Controller::HTTP_OK);   
    }

    public function generate_no_po_get(){

        $params = array(
                'idunit' => $this->get('idunit'),
                'prefix' => 'PO',
                'table' => 'purchase',
                'fieldpk' => 'purchase_id',
                'fieldname' => 'no_purchase_order',
                'extraparams'=> null,
        );
        
        $no_purchase_order = $this->m_data->getNextNoArticle($params);
        $data_purchase['no_purchase_order']=$no_purchase_order;

        $this->set_response(array('success'=>true,'doc_number'=>$no_purchase_order), REST_Controller::HTTP_OK);        
    }

    public function form_data_return_get(){

        $id = $this->get('id');

        $q = $this->m_purchase->data_purchase_return($this->get('idunit'),$id);

        $this->set_response(array('success'=>true,'results'=>count($q),'rows'=>$q),REST_Controller::HTTP_OK);
    } 

    public function purchase_return_items_get(){

        $purchase_return_id = $this->get('id');
        // echo  $purchase_return_id;
        $q = $this->m_purchase->purchase_return_items($purchase_return_id);

        $this->set_response(array('success'=>true,'results'=>count($q),'rows'=>$q),REST_Controller::HTTP_OK); 
    }

    function edit_return_stock($id,$type){
        $stock_return = $this->m_purchase->stock_return_get($id,$type);
            
        foreach ($stock_return->result_array() as $key => $value) {
            # code...
            $product =  $this->db->get_where('product',array('product_id'=> $value['product_id']))->row();
         
            $stock_rollback = $product->stock_available+$value['trx_qty'];
            $this->db->where('product_id',$value['product_id']);
            $this->db->update('product',array('stock_available'=>$stock_rollback));
           
            $this->db->where('stock_history_id',$value['stock_history_id']);
            $this->db->delete('stock_history');
        }

        $this->m_purchase->after_return_purchace($id,$type);
    }

    function edit_receipt_stock($id,$type){

        $qstock = $this->m_purchase->stock_receipt_get($id,$type);
        $stock_rollback = null;

        foreach ($qstock->result_array() as $key => $vstock) {
            print_r($vstock);
            $product =  $this->db->get_where('product',array('product_id'=> $vstock['product_id']))->row();
            die;      
            $stock_rollback = $product->stock_available-$vstock['trx_qty'];
            $this->db->where('product_id',$vstock['product_id']);
            $this->db->update('product',array('stock_available'=>$stock_rollback));

            $this->db->where('stock_history_id',$vstock['stock_history_id']);
            $this->db->delete('stock_history');
        }

        $this->m_purchase->after_receipt_purchace($id,$type);                
    }

    function metode_average($recepit_id){
        
        $total_received = null;
        $total = null; 
        // $avg   = null;

        $q =$this->db->query("SELECT a.product_id,c.qty_received,b.price,d.status FROM product a 
                              INNER join purchase_item b on b.product_id=a.product_id
                              INNER join purchase_receipt_item c on c.purchase_item_id=b.purchase_item_id
                              INNER join purchase_receipt d on c.purchase_receipt_id=d.purchase_receipt_id
                              where a.idunit=".$this->post('idunit')." and d.purchase_receipt_id=$recepit_id");
        $rq = $q->result_array();

       
        foreach ($rq as $key => $value) {
            # code...
            $q1 = $this->db->query("SELECT a.product_id,sum(c.qty_received) as qty_received,sum(b.price*qty_received) as total_received FROM product a 
                                    INNER join purchase_item b on b.product_id=a.product_id
                                    INNER join purchase_receipt_item c on c.purchase_item_id=b.purchase_item_id
                                    INNER join purchase_receipt d on c.purchase_receipt_id=d.purchase_receipt_id
                                    where a.idunit=".$this->post('idunit')." and a.product_id=".$value['product_id']." GROUP BY a.product_id");
            
            foreach ($q1->result() as $key => $v) {
                # code...
                $average = $v->{'total_received'}/$v->{'qty_received'};
                
                $data_buyprice = array('buy_price'=> $average);

                if($value['status']==3){
                    $q2 = $this->db->query("SELECT a.* FROM product a   
                                            where a.idunit=".$this->post('idunit')." 
                                            and a.product_id=".$value['product_id'])->row();                  
                    
                    $balance = $average*$q2->{'stock_available'};
                    $data_buyprice['product_balance']=$balance;
                }
                //update harga beli
                $this->db->where('product_id',$value['product_id']);
                $this->db->update('product',$data_buyprice);
            }
        }
    }
    
    private function rollback_balance($idjournal){

        $q = $this->db->get_where('journalitem',array('idjournal'=>$idjournal));
            
        foreach ($q->result() as $key => $v) {
            # code...
            //get account detail
            $account = $this->db->query("SELECT balance,idaccounttype FROM account WHERE idaccount=".$v->{'idaccount'}." and idunit=".$this->post('idunit'))->row();
            
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
                'idunit'=>$this->post('idunit')      
            ));

            $this->db->update('account',array(
                'balance'=>$newbalance
            ));
        }
    }
}
?>