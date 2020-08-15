<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Purchase extends MY_Controller {

	function  __construct(){
		 parent::__construct();

        $this->load->model(array('m_user','m_purchase','m_account'));

        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        $this->methods['users_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['users_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['users_delete']['limit'] = 50; // 50 requests per hour per user/key
	}

	public function save_purchasing_post(){
		
		$coa = json_decode($this->m_data->getIdAccount(26,$this->user_data->idunit)); //penjualan
        if(isset($coa->{'success'})){
            if(!$coa->{'success'}){
                $this->set_response(array(
                    'success'=>false,
                    'message'=>$coa->{'message'}
                ), REST_Controller::HTTP_BAD_REQUEST); 
                return false;
            }
        }
        
        $coa = json_decode($this->m_data->getIdAccount(14,$this->user_data->idunit)); //piutang
        if(isset($coa->{'success'})){
            if(!$coa->{'success'}){
                $this->set_response(array(
                    'success'=>false,
                    'message'=>$coa->{'message'}
                ), REST_Controller::HTTP_BAD_REQUEST); 
                return false;
            }
        }

        $purchase_id_po = $this->post('purchase_id_po');
        $invoice_no = $this->post('invoice_no');
        $customer_id = $this->post('customer_id');
        $invoice_date = $this->post('invoice_date');
        $due_date = $this->post('due_date');
        $status = $this->post('status');
        $memo = $this->post('memo');
        $include_tax = $this->post('include_tax');
        $freight = str_replace(',', '', $this->post('freight'));
        $customer_type = $this->post('customer_type');
        
        $purchase_item= json_decode($this->post('purchase_item'));

        $summary_purchase = $this->summary_purchase_inv($include_tax,$freight,$purchase_item);

        //member
        if( $this->post('customer_type')=='member'){
            // $this->load->model('m_member');
            // $id_member = $this->m_member->check_id($this->post('no_member'))['id_member'];
            $id_member = $this->post('customer_id');
            $idcustomer = $id_member;
        } else {
            $id_member = null;
            $idcustomer = $this->post('customer_id');
        }

            $data_purchase = array(
            // 'idtax'=>$tax_id,
            'id_member'=> $id_member == '' ? null : $id_member,
            'idcustomer'=> $idcustomer == '' ? null : $idcustomer,
            'customer_type'=> $this->post('customer_type')=='member' ? 1 : 2,
            'subtotal'=> $summary_purchase['sub_total'],
            'freight'=>$summary_purchase['shipping_cost'],
            'disc'=>$summary_purchase['total_disc'],
            'total'=>$summary_purchase['total'],
            // 'dpp'=>round($dpp),
            'tax'=>round($summary_purchase['total_tax']),
            'totalamount'=>$summary_purchase['grand_total'],
            'idunit'=>$this->user_data->idunit,
           
            'due_date'=>$due_date,
            'invoice_status'=>$status,
            'date_purchase'=>$invoice_date,
            'invoice_date'=>$invoice_date,
            'include_tax'=>$include_tax=='false' ? 0 : 1
        );

        $purchase_id = $this->post('purchase_id');
        if($purchase_id==''){
            $purchase_id = $this->m_data->getPrimaryID2(null,'purchase','purchase_id');

            //no order
            $params = array(
                'idunit' => $this->user_data->idunit,
                'prefix' => 'PO',
                'table' => 'purchase',
                'fieldpk' => 'purchase_id',
                'fieldname' => 'invoice_no',
                'extraparams'=> null,
            );
            // $this->load->library('../controllers/setup');
            $no_purchase_order = $this->m_data->getNextNoArticle($params);
            //end no order

            if($invoice_no==''){
                //start no invoice
                $params = array(
                    'idunit' => $this->user_data->idunit,
                    'prefix' => 'PINV',
                    'table' => 'purchase',
                    'fieldpk' => 'purchase_id',
                    'fieldname' => 'invoice_no',
                    'extraparams'=> null,
                );
                // $this->load->library('../controllers/setup');
                $invoice_no = $this->m_data->getNextNoArticle($params);
                $data_purchase['invoice_no'] = $invoice_no;
                //end no invoice    
            }
            

            // echo $this->db->last_query();
            $data_purchase['invoice_no'] = $no_purchase_order;
            $data_purchase['purchase_id'] = $purchase_id;
            $data_purchase['paidtoday'] = 0;
            $data_purchase['unpaid_amount'] = $data_purchase['totalamount'];
            $data_purchase['datein'] = date('Y-m-d H:m:s');
            $data_purchase['userin'] = $this->user_data->user_id;

            $this->db->insert('purchase',$data_purchase);
        } else {
            $data_purchase['datemod'] = date('Y-m-d H:m:s');
            $data_purchase['usermod'] = $this->user_data->user_id;
            $this->db->where('idsales',$purchase_id);
            $this->db->update('sales',$data_purchase);
        }

        $this->update_sales_item($purchase_id,$sales_item);

        //create journal
        // $idjournal = $this->m_sales->jurnal_sales_invoice($invoice_date,'Sales Invoice #'.$invoice_no,$data_purchase['totalamount'],$this->user_data->idunit,$this->user_data->user_id);
        $this->db->where('idsales',$purchase_id);
        $this->db->update('sales',array(
                'idjournal'=>$idjournal
            ));

        $this->log_sales_journal(1,$idjournal,$purchase_id);
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

     private function summary_purchase_inv($include_tax,$shipping_cost,$purchase_item){

        $sub_total = 0;
        $total_disc = 0;
        $dpp = 0;
        $total_tax = 0;
        $total_sales = 0;

        foreach ($purchase_item as $key => $v) {
            $total = $v->{'qty'}*$v->{'price'};
            $discount = ($total/100)*$v->{'disc'};
            $tax = $total*($v->{'rate'}/100);

            $total_disc+=$discount;
            $total_tax+=$tax;
            $sub_total+=$total;

            if($include_tax==1){
                $total_per_row = ($total - $discount);
            } else {
                $total_per_row = ($total - $discount)+$tax;
            }
        }

        $total = ($sub_total-$total_disc)+$shipping_cost;
        if($include_tax==1){
            $grand_total = $total;
        } else {
            $grand_total = $total+$tax;
        }

        $data = array(
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
        // print_r($Purchase_item);
        $data = $this->summary_purchase_inv($include_tax,$shipping_cost,$Purchase_item);

        $this->set_response($data, REST_Controller::HTTP_OK); 

	}
}
?>