<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Sales extends MY_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        $this->load->model(array('m_user','m_sales','m_account','m_inventory','preferences/m_tax'));
        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
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

    private function recheck_tax_sales($tax_id){
        $resp = array(
            'success'=>true
        );

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
            if(isset($q)){
                $data = $q;
                if($data->{'is_tax_ppn'}==1){
                    //cek ada coanya gak
                    if($data->{'coa_ppn_sales_id'}==0 || $data->{'coa_ppn_sales_id'}=='' || $data->{'coa_ppn_sales_id'}==null){
                         $resp = array(
                            'success'=>false,
                            'message'=>'Akun pajak penjualan PPN belum ditentukan'
                        );
                    } else {
                        $cek = $this->check_coa($data->{'coa_ppn_sales_id'},$data->{'idunit'});
                        if(!$cek){
                            $resp = array(
                                'success'=>false,
                                'message'=>'Akun pajak penjualan PPN salah'
                            );
                        }
                    }
                }

                if($data->{'is_tax_pph23'}==1){
                    //cek ada coanya gak
                    if($data->{'coa_pph23_sales_id'}==0 || $data->{'coa_pph23_sales_id'}=='' || $data->{'coa_pph23_sales_id'}==null){
                         $resp = array(
                            'success'=>false,
                            'message'=>'Akun pajak dibayar dimuka (PPH) belum ditentukan'
                        );
                    } else {
                        $cek = $this->check_coa($data->{'coa_pph23_sales_id'},$data->{'idunit'});
                        if(!$cek){
                            $resp = array(
                                'success'=>false,
                                'message'=>'Akun pajak dibayar dimuka (PPH) salah'
                            );
                        }
                    }
                }
            } else {
                $resp = array(
                    'success'=>false,
                    'message'=>'Data pajak terpilih tidak ditemukan'
                );
            }
        }
// print_r($resp);
        return $resp;
    }

    function log_sales_journal($type,$idjournal,$idsales,$coa_debit=null,$coa_credit=null){
            $this->db->insert('sales_journal',array(
                "sales_journal_id" => $this->m_data->getPrimaryID2(null,'sales_journal','sales_journal_id'),
                "idsales" => $idsales,
                "idjournal" => $idjournal,
                "typejournal" => $type,
                "userin" => $this->user_data->user_id,
                "datein" => date('Y-m-d H:m:s'),
                "coa_debit_id" => $coa_debit,
                "coa_credit_id" => $coa_credit
            ));
    }

    private function product_validation($sales_item){
        $valid = array(
            'success'=>true,
            'message'=>'ok'
        );
        foreach ($sales_item as $key => $v) {
            $q = $this->db->get_where('product',array('product_id'=>$v->{'product_id'},'idunit'=>$this->post('idunit'),'deleted'=>0));
            if($q->num_rows()<=0){
                 $valid = array(
                    'success'=>false,
                    'message'=>'Product ID: '.$v->{'product_id'}.' not found'

                );
                 break;
            }
        }

        return $valid;
    }


    private function calc_sales_invoice_recap($include_tax,$shipping_cost,$sales_item,$tax_id=null){

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
            // $q = $this->m_tax->get($tax_id);
            // print_r($q);
            $url = $this->rest_client;

            $response = $url->get('preferences/tax?key='.COOP_APIKEY.'&id='.$tax_id,[
                'auth'=>[COOP_APIKEY,''],
                'form_params'=>array('id'=>$tax_id),
                'http_errors'=>true
            ]);

            $b = json_decode($response->getBody());
            $q = $b->rows[0];
            // die;
            $nametax  = $q->{'nametax'};
            
            $rate_tax = $q->{'tax_rate'};
            
            $is_tax_ppn=$q->{'is_tax_ppn'};
            $is_tax_pph23=$q->{'is_tax_pph23'};

            $coa_ppn_rate=$q->{'coa_ppn_rate'};
            $coa_pph23_rate=$q->{'coa_pph23_rate'};
        }

        foreach ($sales_item as $key => $v) {
            $total = $v->{'qty'}*$v->{'price'};
            $discount = ($total/100)*$v->{'disc'};
            // $tax = $total*($v->{'rate'}/100);
            $tax = $total*($rate_tax/100);

            if((isset($is_tax_ppn) && isset($is_tax_pph23)) && ($is_tax_ppn==1 && $is_tax_pph23==1)){
               $ppn_tax          = $total*($coa_ppn_rate/100);
               $pph23_tax        = $total*($coa_pph23_rate/100);
             
               $tax        = $ppn_tax+$pph23_tax;
               $total_tax  = $tax;
            }

            if ((isset($is_tax_ppn) && isset($is_tax_pph23)) && ($is_tax_ppn==1 && $is_tax_pph23==0)) {
                $tax = $total*($coa_ppn_rate/100);
                // echo $total;
                // $total_tax+=$tax;
            
            }if((isset($is_tax_ppn) && isset($is_tax_pph23)) && ($is_tax_ppn==0 && $is_tax_pph23==1)){
                $tax = $total*($coa_pph23_rate/100);
                // echo $tax.' ';
                // $total_tax+=$tax;
            }
            
            if((isset($is_tax_ppn) && isset($is_tax_pph23)) && ($is_tax_ppn==0 && $is_tax_pph23==0)){
                $tax = $total*($rate_tax/100);
                // $total_tax+=$tax;
            }

            $total_disc+=$discount;
            // $total_tax+=$tax;
            $sub_total+=$total;

            // if($include_tax==1){
                $total_per_row = ($total - $discount);
            // } else {
            //     $total_per_row = ($total - $discount)+$tax;
            // }
        }
        // echo $total_disc.' '.$total_tax.' '.$sub_total;
        // echo $total_tax.' '; 
        $total_tax_ppn = 0;
        $total_tax_pph = 0;

        if((isset($is_tax_ppn) && isset($is_tax_pph23)) && $is_tax_ppn==1 && $is_tax_pph23==1) {
            //ppn dan pph23
            $total_tax_ppn = $total*($q->{'coa_ppn_rate'}/100);
            $total_tax_pph = $total*($q->{'coa_pph23_rate'}/100);
        } else if((isset($is_tax_ppn) && isset($is_tax_pph23)) && $is_tax_ppn==1 && $is_tax_pph23==0) {
            //ppn
            $total_tax_ppn = $total*($q->{'coa_ppn_rate'}/100);
        } else if((isset($is_tax_ppn) && isset($is_tax_pph23)) && $is_tax_ppn==0 && $is_tax_pph23==1) {
            //ppn
            $total_tax_pph = $total*($q->{'coa_pph23_rate'}/100);
        }

        $total_tax = $total_tax_ppn+$total_tax_pph;

        $total = ($sub_total-$total_disc)+$shipping_cost;
        if($include_tax==1 || $include_tax==true){

            $grand_total = $total;
        } else {
            // echo "string";

            if((isset($is_tax_ppn) && isset($is_tax_pph23)) && ($is_tax_ppn==1 && $is_tax_pph23==0)){
                //ppn
                $grand_total = $total+$total_tax_ppn;
            }

            elseif((isset($is_tax_ppn) && isset($is_tax_pph23)) && ($is_tax_ppn==0 && $is_tax_pph23==1)){
                //pph23
                $grand_total = $total;

            }

            elseif((isset($is_tax_ppn) && isset($is_tax_pph23)) && ($is_tax_ppn==1 && $is_tax_pph23==1)){
                //ppn+pph
                $grand_total = $total+$total_tax_ppn-$total_tax_pph;
            }

            elseif((isset($is_tax_ppn) && isset($is_tax_pph23)) && ($is_tax_ppn==0 && $is_tax_pph23==0)){
                //non tax
                // $tax = $total*($rate_tax/100);
                $grand_total = $total;
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
            'total_tax_ppn'=>$total_tax_ppn,
            'total_tax_pph'=>$total_tax_pph,
            'total_tax'=>$total_tax,
            'grand_total'=>$grand_total
        );

        return $data;
    }

    public function calc_sales_invoice_recap_post(){
        $include_tax = intval($this->post('include_tax'));
        $tax_id = intval($this->post('tax_id'));
        $shipping_cost = intval($this->post('shipping_cost'));
        $sales_item = json_decode($this->post('sales_item'));
        
        $data = $this->calc_sales_invoice_recap($include_tax,$shipping_cost,$sales_item,$tax_id);

        $this->set_response($data, REST_Controller::HTTP_OK); 
    }

    function save_invoice_post(){

        $this->db->trans_start(); 

        //cek coanya dulu
        // $coa = json_decode($this->m_data->getIdAccount(30, $this->post('idunit'))); //penjualan
        // if(isset($coa->{'success'})){
        //     if(!$coa->{'success'}){
        //         $this->set_response(array(
        //             'success'=>false,
        //             'message'=>$coa->{'message'}
        //         ), REST_Controller::HTTP_BAD_REQUEST); 
        //         return false;
        //     }
        // }
        
        // $coa = json_decode($this->m_data->getIdAccount(24, $this->post('idunit'))); //piutang
        // if(isset($coa->{'success'})){
        //     if(!$coa->{'success'}){
        //         $this->set_response(array(
        //             'success'=>false,
        //             'message'=>$coa->{'message'}
        //         ), REST_Controller::HTTP_BAD_REQUEST); 
        //         return false;
        //     }
        // }

       
        $sales_item = json_decode($this->post('sales_item'));
        $sales_id = $this->post('sales_id');
        $id_payment_term = $this->post('id_payment_term');
        $customer_id = $this->post('customer_id');
        $invoice_date = backdate2($this->post('invoice_date'));
        $due_date = backdate2($this->post('due_date'));
        $status = $this->post('status');
        $memo = $this->post('memo');
        $tax_id = $this->post('tax_id') == '' ? null : $this->post('tax_id');
        $include_tax = $this->post('include_tax')=='false' ? 0 : 1;
        $shipping_cost = str_replace(',', '', $this->post('freight'));

        // $check_coa = $this->recheck_tax_sales($tax_id);
        // // print_r($check_coa); die;
        // if(!$check_coa['success']){
        //     $this->set_response(array(
        //             'success'=>false,
        //             'message'=>$check_coa['message']
        //     ), REST_Controller::HTTP_BAD_REQUEST); 
        //     return false;
        // }

        //cek coa sales by produk
        // foreach ($sales_item as $v) {
            
        //     $cek = $this->db->get_where('product',array('product_id'=>$v->{'product_id'}))->row();
        //     if($cek->coa_sales_id==null){
        //         $this->set_response(array(
        //                     'success'=>false,
        //                     'message'=>'Akun penjualan untuk produk <b>'.$cek->product_name.'</b> belum ditentukan'
        //             ), REST_Controller::HTTP_BAD_REQUEST); 
        //             return false;
        //         }  
        // }

        $recap_sales = $this->calc_sales_invoice_recap($include_tax,$shipping_cost,$sales_item,$tax_id);

        //member
        if( $this->post('customer_type')==1){
            // $this->load->model('m_member');
            // $id_member = $this->m_member->check_id($this->post('no_member'))['id_member'];
            $id_member = $this->post('customer_id');
            $idcustomer = $id_member;
        } else {
            $id_member = null;
            $idcustomer = $this->post('customer_id');
        }

        $data_sales = array(
            'id_member'=> $id_member == '' ? null : $id_member,
            'idcustomer'=> $idcustomer == '' ? null : $idcustomer,
            'id_payment_term'=> $id_payment_term == '' ? null : $id_payment_term,
            'memo'=>$memo,
            'customer_type'=> $this->post('customer_type'),
            'subtotal'=> $recap_sales['sub_total'],
            'freight'=>$recap_sales['shipping_cost'],
            'disc'=>$recap_sales['total_disc'],
            'total'=>$recap_sales['total'],
            'tax'=>round($recap_sales['total_tax']),
            'totalamount'=>$recap_sales['grand_total'],
            'idunit'=>$this->post('idunit'),
            
            'due_date'=>$due_date,
            'invoice_status'=>$status,
            'date_sales'=>$invoice_date,
            'invoice_date'=>$invoice_date,
            'include_tax'=>$include_tax,
            'tax_id'=>$tax_id
        );
        $total_before_tax = $recap_sales['total'];
        // print_r($recap_sales); 
        // print_r($data_sales); 
        // die;

        $sales_id = $this->post('sales_id');
        if($sales_id==''){
            $sales_id = $this->m_data->getPrimaryID2(null,'sales','idsales');

            //no order
            $params = array(
                'idunit' => $this->post('idunit'),
                'prefix' => 'SO',
                'table' => 'sales',
                'fieldpk' => 'idsales',
                'fieldname' => 'no_sales_order',
                'extraparams'=> null,
            );
            // $this->load->library('../controllers/setup');
            $no_sales_order = $this->m_data->getNextNoArticle($params);
            //end no order

            //start no invoice
            $params = array(
                'idunit' => $this->post('idunit'),
                'prefix' => 'INV',
                'table' => 'sales',
                'fieldpk' => 'idsales',
                'fieldname' => 'noinvoice',
                'extraparams'=> null,
            );
            // $this->load->library('../controllers/setup');
            $invoice_no = $this->m_data->getNextNoArticle($params);
            $data_sales['noinvoice'] = $invoice_no;
            //end no invoice

            // echo $this->db->last_query();
            $data_sales['order_status']=0;
            $data_sales['no_sales_order'] = $no_sales_order;
            $data_sales['idsales'] = $sales_id;
            $data_sales['paidtoday'] = 0;
            $data_sales['unpaid_amount'] = $data_sales['totalamount'];
            $data_sales['datein'] = date('Y-m-d H:m:s');
            $data_sales['userin'] = $this->user_data->user_id;

            $this->db->insert('sales',$data_sales);
            
        } else {
            $invoice_no = $this->post('invoice_no');
            if($this->post('order_status')!=''){
                $data_sales['order_status']=$this->post('order_status');

            }else{
                $data_sales['order_status']=null;                
            }

            $data_sales['noinvoice'] = $invoice_no;
            $data_sales['paidtoday'] = 0;
            $data_sales['unpaid_amount'] = $data_sales['totalamount'];
            $data_sales['datemod'] = date('Y-m-d H:m:s');
            $data_sales['usermod'] = $this->user_data->user_id;
            
            $this->db->where('idsales',$sales_id);
            $this->db->update('sales',$data_sales);
            
        }
        
        $this->update_sales_item($sales_id,$sales_item);

        if($sales_id!=''){
            $this->load->library('journal_lib');

            $q = $this->db->get_where('sales',array('idsales'=>$sales_id))->row();
            if($q->idjournal!=null){
                $this->journal_lib->delete($q->idjournal);
            }                       
        }

        //PAJAK
        // $idjournal = null;
        // // $idjournal = $this->m_sales->jurnal_sales_invoice($invoice_date,'Sales Invoice #'.$invoice_no,$data_sales['totalamount'],$this->post('idunit'),$this->user_data->user_id,$sales_id);
        // if($tax_id!='' && $tax_id!=null){
        //     $qtax = $this->m_tax->get($tax_id);
        //     $qtax = $qtax['data'][0];
        //     if($qtax['is_tax_ppn']==1 && $qtax['is_tax_pph23']==0){
        //         //PPN SAJA 
        //         $idjournal = $this->m_sales->jurnal_sales_invoice_ppn($invoice_date,'Sales Invoice #'.$invoice_no,$total_before_tax,$data_sales['totalamount'],$this->post('idunit'),$this->user_data->user_id,$sales_id,$tax_id,$data_sales['noinvoice'],$data_sales['include_tax']);
        //     }

        //     if($qtax['is_tax_ppn']==0 && $qtax['is_tax_pph23']==1){
        //         //PPH SAJA 
        //         $idjournal = $this->m_sales->jurnal_sales_invoice_pph($invoice_date,'Sales Invoice #'.$invoice_no,$total_before_tax,$data_sales['totalamount'],$this->post('idunit'),$this->user_data->user_id,$sales_id,$tax_id,$data_sales['noinvoice']);
        //     }

        //     if($qtax['is_tax_ppn']==1 && $qtax['is_tax_pph23']==1){
        //         //PPN dan PPH 
        //         $idjournal = $this->m_sales->jurnal_sales_invoice_ppn_n_pph($invoice_date,'Sales Invoice #'.$invoice_no,$total_before_tax,$data_sales['totalamount'],$this->post('idunit'),$this->user_data->user_id,$sales_id,$tax_id,$data_sales['noinvoice']);
        //     }

        //     if($qtax['is_tax_ppn']==0 && $qtax['is_tax_pph23']==0){
        //             //NON TAX
        //             $idjournal = $this->m_sales->jurnal_sales_invoice_notax($invoice_date,'Sales Invoice #'.$invoice_no,$total_before_tax,$data_sales['totalamount'],$this->post('idunit'),$this->user_data->user_id,$sales_id,$data_sales['noinvoice']);
        //     }
        // } else {
        //     //NON TAX
        //     $idjournal = $this->m_sales->jurnal_sales_invoice_notax($invoice_date,'Sales Invoice #'.$invoice_no,$total_before_tax,$data_sales['totalamount'],$this->post('idunit'),$this->user_data->user_id,$sales_id,$data_sales['noinvoice']);
        // }
        //END PAJAK

        // $this->db->where('idsales',$sales_id);
        // $this->db->update('sales',array(
        //         'idjournal'=>$idjournal
        //     ));

        // $this->log_sales_journal(1,$idjournal,$sales_id);
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

            $this->stock_after_sale($sales_id,$type=1);
            $this->db->trans_commit();
            $this->set_response(array(
                'success'=>true,
                'message'=>'Data berhasil disimpan'
            ), REST_Controller::HTTP_OK); 
        }
    }

    // function calc_sales_tax_get(){
    //     $sales_id = $this->get('sales_id');
    //     $q = $this->db->query("select ratetax,total_tax,b.idunit
    //                             from salesitem a
    //                             join sales b ON a.idsales = b.idsales
    //                             where a.idsales = $sales_id");
    //     foreach ($q->result() as $r) {
    //         if($r->ratetax>0){
    //                 $qtax = $this->db->query("select a.idtax,a.code,a.nametax,a.rate,a.coa_ap_id
    //                                     from tax a
    //                                     where a.idunit = ".$r->idunit."  and a.status = 1 and a.deleted = 0 and a.rate = ".$r->ratetax);
    //                if($qtax->num_rows()>0){
    //                     $rtax = $qtax->row();
    //                     $data = array(
    //                         'sales_id'=>$sales_id,
    //                         'tax_id'=>$rtax->idtax
    //                     );

    //                     $qcek = $this->db->get_where('sales_calc_tax_tmp',$data);
    //                     if($qcek->num_rows()>0){
    //                         $rcek = $qcek->row();

    //                         $this->db->where($data);
    //                         $this->db->update('sales_calc_tax_tmp',array(
    //                                 'ratetax'=>$rtax->rate,
    //                                 'total_tax'=>($rcek->total_tax+$r->total_tax),
    //                                 'sales_id'=>$sales_id,
    //                                 'tax_id'=>$rtax->idtax                            
    //                             ));
    //                     } else {
    //                          $this->db->insert('sales_calc_tax_tmp',array(
    //                                 'ratetax'=>$rtax->rate,
    //                                 'total_tax'=>$r->total_tax,
    //                                 'sales_id'=>$sales_id,
    //                                 'tax_id'=>$rtax->idtax                            
    //                             ));
    //                     }
    //                }
    //         }
           

    //     }

       
    // }

    function update_sales_item($sales_id,$item_sales){

        //delete dulu baru insert ulang
        $this->db->where('idsales', $sales_id);
        $this->db->delete('salesitem');

        foreach ($item_sales as $value) {
            $price_item = $value->{'qty'}*$value->{'price'};
            $rate = isset($value->{'rate'}) ? $value->{'rate'} : 0;
            $tax = $price_item*($rate/100);

            $item = array(
                // 'idsalesitem' => $value->idsalesitem,
                'idsales' => $sales_id,
                'product_id' => $value->{'product_id'},
                'qty' => $value->{'qty'},
                'disc' => $value->{'disc'},                
                'price' => $value->{'price'},
                'ratetax' => isset($value->{'rate'}) ? $value->{'rate'} : null,
                'total_tax' => $tax,
                'description' => $value->{'desc'},
                'total'=> ($price_item-$value->{'disc'})+$tax,
                'deleted' => 0
            );

            // if($value->{'idsalesitem'}=='' || $value->{'idsalesitem'}==null){
                $item['idsalesitem'] = $this->m_data->getPrimaryID2(null,'salesitem','idsalesitem');
            // } else {
            //     $item['idsalesitem'] = $value->{'idsalesitem'};
            // }

                $item['userin'] = $this->user_data->user_id;
                $item['datein'] = date('Y-m-d H:i:s');
                $item['usermod'] = $this->user_data->user_id;
                $item['datemod'] = date('Y-m-d H:i:s');
                $this->db->insert('salesitem', $item);
            // } else {
            //     $item['usermod'] = $this->user_data->user_id;
            //     $item['datemod'] = date('Y-m-d H:i:s');
            //     $this->db->where('idsalesitem', $value->{'idsalesitem'});
            //     $this->db->update('salesitem', $item);
            // }
          
            
        }
    }

    function tax_rate($tax_id){
        $q = $this->db->query("select rate
                                    from tax
                                    where idtax = $tax_id")->row();
        return $q->rate;
    }

    public function remove_post(){  
        $v = $this->m_sales->remove($this->post('id'),$this->user_data->user_id);
        $this->stock_after_sale($this->post('id'),$type=10);

        // $this->m_inventory->remove_stock_history($this->delete('id'));

        if($v['success']){
            $this->response($v, REST_Controller::HTTP_OK);
        } else {
            $this->response($v, REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    public function remove_return_post(){
        $v = $this->m_sales->remove_return($this->post('id'),$this->user_data->user_id,$this->post('idunit'));
    
        if($v['success']){
            $this->response($v, REST_Controller::HTTP_OK);
        } else {
            $this->response($v, REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    public function remove_return_delete(){
        $v = $this->m_sales->remove_return($this->delete('id'),$this->delete('user_id'),$this->delete('idunit'));
        
        if($v['success']){
            $this->response($v, REST_Controller::HTTP_OK);
        } else {
            $this->response($v, REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    public function item_return_datas_get(){
        $sales_return_id = $this->get('id');
        $idunit = $this->post('idunit');

        if ($sales_return_id == null){
            $this->response(array('success'=>false,'message'=>'sales_return_id not found'), REST_Controller::HTTP_BAD_REQUEST); 
        }

        $data = $this->m_sales->return_item_data($sales_return_id,$idunit);
        $num_rows = count($data);
            $message = [
                    'success' => true,
                    'numrow'=>$num_rows,
                    'results'=>$num_rows,
                    'rows' => $data
            ];

        $this->set_response($message, REST_Controller::HTTP_OK); 
    }

    public function item_datas_get(){
        $sales_id = $this->get('id');
        $except_id = json_decode($this->get('except_id')); //show data except this sales_item_id (array)
        $idunit = $this->get('idunit');

        if ($sales_id == null){
            $this->response(array('success'=>false,'message'=>'sales_id not found'), REST_Controller::HTTP_BAD_REQUEST); 
        }

        $data = $this->m_sales->item_data($sales_id,$idunit,$except_id);
        $num_rows = count($data);
            $message = [
                    'success' => true,
                    'numrow'=>$num_rows,
                    'results'=>$num_rows,
                    'rows' => $data
            ];

        $this->set_response($message, REST_Controller::HTTP_OK); 
    }

    function paymet_medical_get(){

        $startdate = str_replace("T00:00:00", "", $this->get('startdate'));
        $enddate = str_replace("T00:00:00", "", $this->get('enddate'));
        $idunit = 12;
        $id = $this->get('id');

        if ($idunit <= 0){
            $this->response(array('success'=>false,'message'=>'id coop not found'), REST_Controller::HTTP_BAD_REQUEST); 
        }

        $d = $this->m_sales->data_payment_medical($id,$idunit,$startdate,$enddate,$this->get('medical_record_id'),$this->get('payment_status'),$this->get('query'));
        // echo $this->get('invoice_status');        
        // echo $this->db->last_query();

        $message = [
            'success' => true,
            'numrow'=>$d['total'],
            'results'=>$d['total'],
            'rows' => $d['data']
        ];

        $this->set_response($message, REST_Controller::HTTP_OK); 
    }

    public function datas_get(){
        $startdate = str_replace("T00:00:00", "", $this->get('startdate'));
        $enddate = str_replace("T00:00:00", "", $this->get('enddate'));
        $idunit = 12;
        $id = $this->get('id');

        if ($idunit <= 0){
            $this->response(array('success'=>false,'message'=>'id coop not found'), REST_Controller::HTTP_BAD_REQUEST); 
        }

        $data = $this->m_sales->data($id,$idunit,null,$startdate,$enddate,$this->get('option'),$this->get('status'),$this->get('is_order_request'),$this->get('customer_type'),$this->get('query'));
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

    public function return_data_get(){
        $sales_retur_id = $this->get('id');
        $idunit = 12;

        $data = $this->m_sales->return_data($sales_retur_id,$idunit,null,null);
        $message = [
                'success' => true,
                'data' => $data[0]
        ];

        $this->set_response($message, REST_Controller::HTTP_OK); 
    }

    public function return_datas_get(){
        $startdate = str_replace("T00:00:00", "", $this->get('startdate'));
        $enddate = str_replace("T00:00:00", "", $this->get('enddate'));
        $sales_retur_id = $this->get('id');
        $idunit = $this->get('idunit');

        $data = $this->m_sales->return_data($sales_retur_id,$idunit,$startdate,$enddate);
        $num_rows = count($data);
            $message = [
                    'success' => true,
                    'numrow'=>$num_rows,
                    'results'=>$num_rows,
                    'rows' => $data
            ];

        $this->set_response($message, REST_Controller::HTTP_OK); 
    }

    public function new_post(){

        $params = array(
            'idunit' => $this->post('idunit'),
            'prefix' => 'SO',
            'table' => 'sales',
            'fieldpk' => 'idsales',
            'fieldname' => 'no_sales_order',
            'extraparams'=> null,
        );
        // $this->load->library('../controllers/setup');
        $noarticle = $this->m_data->getNextNoArticle($params);
            
        $items = json_decode($this->post('item_sales'));

        //cek coa sales by produk
        // foreach ($items as $v) {
            
        //     $cek = $this->db->get_where('product',array('product_id'=>$v->{'product_id'}))->row();
        //     if($cek->coa_sales_id==null){
        //         $this->set_response(array(
        //                     'success'=>false,
        //                     'message'=>'Akun penjualan untuk produk <b>'.$cek->product_name.'</b> belum ditentukan'
        //             ), REST_Controller::HTTP_BAD_REQUEST); 
        //             return false;
        //         }  
        // }

        $this->db->trans_begin();

        $idsales = $this->m_data->getPrimaryID2($this->post('idsales'),'sales', 'idsales', $this->post('idunit'));

        // $idtax = (int) $this->post('idtax');
        $freight = 0;

        /*
        format nomor yang diterima. 320.200,00
        */
        // if($this->post('include_tax') == 2){

        // } else {
            // ? 1 : 0
        // }

        // if($idtax==2){
        //     //non tax
        //     $total_dpp = 0;
        //     $tax = 0;
        // } else {
        //     $total_dpp = clearnumberic($this->post('total_dpp'));
        //     $tax = clearnumberic($this->post('tax'));
        // }

        //member
        if( $this->post('customer_type')=='member'){
            // $this->load->model('m_member');
            // $id_member = $this->m_member->check_id($this->post('no_member'))['id_member'];
            $id_member = $this->post('id_member');
            $idcustomer = $id_member;
        } else {
            $id_member = null;
            $idcustomer = $this->post('customer_id');
        }
        
        //non member

        $header = array(
            'no_sales_order' => $noarticle,
            'idsales' => $idsales,
            'id_member'=> $id_member == '' ? null : $id_member,
            'idcustomer'=> $idcustomer == '' ? null : $idcustomer,
            'customer_type'=> $this->post('customer_type')=='member' ? 1 : 2,
            // 'idcustomer' => $this->post('idcustomer'),         
            // 'idtax' => $idtax,
            // 'shipaddress'=> $this->post('shipaddress'),
            'subtotal' => request_number($this->post('subtotal')),
            'disc' => request_number($this->post('discount')),
            // 'total_dpp' => $total_dpp,
            // 'freight'=> $freight,
            'totalamount' => request_number($this->post('total_amount')),
            'other_fee'=> request_number($this->post('other_fee')),
            // 'tax' => $tax,
            // 'include_tax'=> $this->post('include_tax'),
            'comments' => $this->post('memo') ? : null,
            'paidtoday'=> $this->post('paidtoday'),
            'payment_amount'=> request_number($this->post('payment_amount')),
            'change_amount'=> request_number($this->post('change_amount')),
            'unpaid_amount'=> 0,
            'idpayment'=> 1, //tunai
            'type' => 2,             
            'status'  => 5, //paid
            'userin'=> $this->user_data->user_id,
            'idunit'  => $this->post('idunit'),
            'pos_payment_type_id'=>$this->post('payment_type'),
            'source'=>2,
            'order_status'=>6,//completed
            'datein'=>date('Y-m-d H:m:s'),
            'date_sales'=>date('Y-m-d H:m:s'),

        );
        // print_r($header);
        // die;    
        $this->db->insert('sales', $header);

        /*
            data example : [{"product_id":"12","qty":"35","disc":"0","price":"12000"},{"product_id":"11","qty":"2","disc":"0","price":"10000"}]
        */
       // print_r(expression)

        foreach ($items as $value) {
            $item = array(
                // 'idsalesitem' => $value->idsalesitem,
                'idsales' => $header['idsales'],
                'product_id' => $value->product_id,
                'qty' => $value->qty,
                'disc' => $value->disc,
                'price' => $value->price,
                'total'=> $value->qty*$value->price-$value->disc,
                'deleted' => 0
            );

            // $q_seq = $this->db->query("select nextval('seq_purchaseitem')");
            $idsalesitem = $this->m_data->getPrimaryID2($this->post('salesitem'),'salesitem', 'idsalesitem', $this->post('idunit'));
            $item['idsalesitem'] =  $idsalesitem;
            $item['userin'] = $this->user_data->user_id;
            $item['datein'] = date('Y-m-d H:i:s');
            $this->db->insert('salesitem', $item);
        }

      

        if($this->db->trans_status() === false){
            $this->db->trans_rollback();
            $json = array('success'=>false,'message'=>'Penjualan gagal tersimpan. Mohon coba kembali.');
            $this->set_response($json, REST_Controller::HTTP_BAD_REQUEST); 
        }else{
            $this->db->trans_commit();

            //history stock
            $this->stock_after_sale($header['idsales'],$type=8);
            //end

            //buat journal
            $invoice_status = 5; //paid
            // $journal = $this->m_sales->jurnal_sales(date('Y-m-d'),'Sales #'.$noarticle,$header['totalamount'],$header['idunit'],$header['userin'],$idsales);

            // $this->db->where('idsales',$idsales);
            // $this->db->update('sales',array(
            //         'idjournal'=>$journal['idjournal']
            // ));
            // $header['idjournal'] = $journal['idjournal'];


            $json = array('success'=>true,'message'=>'Penjualan berhasil tersimpan','id'=>$idsales);
            $this->set_response($json, REST_Controller::HTTP_CREATED); 
        }

    }

    public function summary_get(){
        $idunit = 12;
        $startdate = str_replace("T00:00:00", "", $this->get('startdate'));
        $enddate = str_replace("T00:00:00", "", $this->get('enddate'));

        if ($idunit <= 0){
            $this->response(array('success'=>false,'message'=>'id coop not found'), REST_Controller::HTTP_BAD_REQUEST); 
        }

        $d = $this->m_sales->summary($idunit,$startdate,$enddate);
        $this->set_response($d, REST_Controller::HTTP_OK); 

    }

    public function invoice_get(){
        $idunit = $this->post('idunit');
        $option = $this->get('option');
        $startdate = str_replace("T00:00:00", "", $this->get('startdate'));
        $enddate = str_replace("T00:00:00", "", $this->get('enddate'));
        $status =$this->get('status');
        $customer_type =$this->get('customer_type');
       
        $d = $this->m_sales->invoice_data($idunit,null,$option,$startdate,$enddate,$status,$customer_type);
        $this->set_response($d, REST_Controller::HTTP_OK); 
    }

    public function save_payment_post(){
        $this->db->trans_begin();

        $items = json_decode($this->post('datagrid'));
        $date_payment = backdate2($this->post('date_payment'));
        
        $idaccount = $this->post('idaccount');
        $notes = $this->post('notes');

        $total_payment = 0;

        foreach ($items as $value) {
            //get current balance
            $qs = $this->db->query("select paidtoday,unpaid_amount from sales where idsales = ".$value->{'idsales'}." ");

            if($qs->num_rows()>0){
                $rs = $qs->row();

                $new_unpaid = $rs->unpaid_amount-$value->{'pay_amount'};
                $paidtoday = $rs->paidtoday+$value->{'pay_amount'};

                if($new_unpaid<=0){
                    $invoice_status = 5; //paid
                } else {
                    $invoice_status = 4; //partially paid
                }

                $item = array(
                    'idsales' => $value->{'idsales'},
                    'invoice_status'=>$invoice_status,
                    'unpaid_amount'=>$new_unpaid,
                    'paidtoday'=>$paidtoday
                );

                $item['usermod'] = $this->user_data->user_id;
                $item['datemod'] = date('Y-m-d H:i:s');

                $this->db->where('idsales', $value->{'idsales'});
                $this->db->update('sales', $item);

                $cek_diagnosa_payment = $this->db->get_where('medical_record',array('sales_id'=>$value->{'idsales'},'deleted'=>0));

                if($cek_diagnosa_payment->num_rows() > 0){

                    $rdiagnosa = $cek_diagnosa_payment->row();

                    $this->db->where(array('medical_record_id'=>$rdiagnosa->{'medical_record_id'},'sales_id'=>$value->{'idsales'})); 
                    $this->db->update('medical_record',array('payment_status'=>2)); 
                }
            }

            //create journal
            // $idjournal = $this->create_journal_sales_payment($value->{'idsales'},$value->{'pay_amount'},$idaccount,$date_payment);
            // $this->log_sales_journal(2,$idjournal,$value->{'idsales'},$idaccount);
           
            $total_payment+=$value->{'pay_amount'};
        }

      if($this->db->trans_status() === false){
            $this->db->trans_rollback();
            $json = array('success'=>false,'message'=>'Data pembayaran gagal tersimpan. Mohon coba kembali.');
            $this->set_response($json, REST_Controller::HTTP_BAD_REQUEST); 
        }else{
            $this->db->trans_commit();

            //history stock

            $json = array('success'=>true,'message'=>'Data pembayaran berhasil tersimpan');
            $this->set_response($json, REST_Controller::HTTP_CREATED); 
        }
        
    }

    function create_journal_sales_payment($idsales,$pay_amount,$idaccount,$date_payment){
        $q = $this->db->query("select idjournal,noinvoice from sales where idsales = $idsales")->row();

        //get coa
        $qcoa = $this->db->query("select a.idaccount,accname
                                    from journalitem a
                                    join account b ON a.idaccount = b.idaccount
                                    where idjournal = ".$q->idjournal." and credit = 0 -- debit/piutang")->row();

        //coa debit = kas
        $coa_debit = $idaccount;
        //coa credit = piutang
        $coa_credit = $qcoa->idaccount;

        return $this->m_sales->jurnal_sales_payment($date_payment,'Payment Sales Invoice #'.$q->noinvoice,$pay_amount,$this->post('idunit'),$this->user_data->user_id,$coa_debit,$coa_credit);
    }

    function get_member(){

    }

    function stock_after_sale($id,$type){
       //get sales item infon
        $salesinfo  = "SELECT a.* FROM salesitem a
                        INNER JOIN sales b on b.idsales=a.idsales
                        WHERE b.idsales=$id";
        //query data 
        $q = $this->db->query($salesinfo)->result();                    
        foreach ($q as $key => $value) {
            # code...
            $product = $this->db->get_where('product',array('product_id'=>$value->product_id))->row();
            
            //check product type
            if($product->inventory_class_id !=1){
                continue;
            }

            //making prefix no
            $params=array(
                'idunit' => $this->post('idunit'),
                'prefix' => 'STCK',
                'table' => 'stock_history',
                'fieldpk' => 'stock_history_id',
                'fieldname' => 'no_transaction',
                'extraparams'=> null,
            );

            $no = $this->m_data->getNextNoArticle($params);

            //declarated
            $currStock = $product->stock_available;
            $trxStock  = $value->qty;

            if($type==8){
                //for sales chase
                $newStock = $currStock-$trxStock;      
            }

            else if($type==10){
                //for sales cancelation or deleting sales
                $newStock = $currStock+$trxStock;
            }

            if($product->is_purchasable==2){
                $product_balance = $newStock*$product->buy_price;
            }

            $dataStock=array(
                'stock_history_id'=>$this->m_data->getPrimaryID2(null,'stock_history','stock_history_id'),
                'product_id'=>$value->product_id,
                'type_adjustment'=>$type,
                'no_transaction'=>$no,
                'datein'=>date('Y-m-d H:i'),
                'current_qty'=>$currStock,
                'trx_qty'=>$trxStock,
                'new_qty'=>$newStock,
                'reference_id'=>$id
            );

            //insert stock historis
            $this->db->insert('stock_history',$dataStock); 
            //end insert    
        }

        if($this->db->trans_status()===false){
            $this->db->trans_rollback();
        }else{

            $this->db->trans_commit();
        }
    }
   

     public function save_return_post(){
        $this->db->trans_begin();

        $sales_return_id = $this->post('sales_return_id');
        $sales_id = $this->post('sales_id');
        $data_item = json_decode($this->post('return_item'));

        $total_qty_return = 0;
        $total_amount_return = 0;
        foreach ($data_item as $v) {
            $total_qty_return += $v->{'qty_retur'};
            $total_amount_return += $v->{'total_return'};
        }

        $data = array(
            // "sales_return_id" int4 NOT NULL,
            "sales_id" => $sales_id,
            "status" => 2,
            "idunit"=>$this->post('idunit'),
            // "memo" varchar(255) COLLATE "default",
            "date_return" => backdate2($this->post('return_date')),
            "memo"=> $this->post('memo'),
            "total_qty_return" => $total_qty_return,
            "total_amount_return" => $total_amount_return,
            "datein" => date('Y-m-d H:m:s'),
            "userin" => $this->user_data->user_id,
            "datemod"  => date('Y-m-d H:m:s'),
            "usermod"  => $this->user_data->user_id,
            // "no_return" varchar(150) COLLATE "default",
            // "journal_id" int4,
        );

        if($sales_return_id!='' && $sales_return_id!=null && $sales_return_id!=0){
            //update
            // remove dulu baru insert lagi

            $data['sales_return_id'] = $sales_return_id;
            // $data['no_return'] = $noarticle;

            // $this->db->where('sales_return_id',$sales_return_id);
            // $this->db->update('sales_return',$data);
            $v = $this->m_sales->remove_return($sales_return_id,$this->user_data->user_id);
        
            if($v['success']){
            }
        } else {
            //insert
            $data['sales_return_id'] = $this->m_data->getPrimaryID2(null,'sales_return', 'sales_return_id');

          
        }

        $params = array(
            'idunit' => $this->post('idunit'),
            'prefix' => 'SR',
            'table' => 'sales_return',
            'fieldpk' => 'sales_return_id',
            'fieldname' => 'no_return',
            'extraparams'=> null,
        );
        // $this->load->library('../controllers/setup');
        $noarticle = $this->m_data->getNextNoArticle($params);
        $data['no_return'] = $noarticle;

        $this->db->insert('sales_return',$data);

        //insert return item
        foreach ($data_item as $v) {
            if($v->{'qty_retur'}!=0){
                $ditem = array(
                    // "sales_return_item_id" int4 NOT NULL,
                    "sales_return_id" =>  $data['sales_return_id'],
                    "sales_item_id" => $v->{'idsalesitem'},
                    "qty_sale" => $v->{'qty_sale'},
                    "qty_retur" => $v->{'qty_retur'},
                    "notes" => $v->{'notes'},
                    "datemod" => date('Y-m-d H:m:s')
                );
                // if($v->{'sales_return_item_id'}==''){
                    $ditem['sales_return_item_id'] = $this->m_data->getPrimaryID2(null,'sales_return_item', 'sales_return_item_id');
                    $this->db->insert('sales_return_item',$ditem);
                // } else {
                //     $ditem['sales_return_item_id'] = $v->{'sales_return_item_id'};
                //     $this->db->where('sales_return_item_id',$ditem['sales_return_item_id']);
                //     $this->db->update('sales_return_item',$ditem);
                // }
            } 

        }

        if($this->db->trans_status() === false){
            $this->db->trans_rollback();
            $json = array('success'=>false,'message'=>'Retur Penjualan gagal tersimpan. Mohon coba kembali.');
            $this->set_response($json, REST_Controller::HTTP_BAD_REQUEST); 
        }else{
            $this->m_sales->stock_after_return($data['sales_return_id'],$type=6,$this->post('idunit'));
            $this->db->trans_commit();
            $json = array('success'=>true,'message'=>'Retur Penjualan berhasil tersimpan');
            $this->set_response($json, REST_Controller::HTTP_CREATED); 
        }
    }

    public function generate_no_invoice_get(){
        $params = array(
            'idunit' => $this->get('idunit'),
            'prefix' => 'INV',
            'table' => 'sales',
            'fieldpk' => 'idsales',
            'fieldname' => 'noinvoice',
            'extraparams'=> null,
        );
        // $this->load->library('../controllers/setup');
        $invoice_no = $this->m_data->getNextNoArticle($params);
        $data_sales['noinvoice'] = $invoice_no;

        $this->set_response(array('success'=>true,'doc_number'=>$invoice_no), REST_Controller::HTTP_OK); 
    }

    public function sales_perpoduct_get(){

        $startdate = $this->get('startdate');
        $enddate = $this->get('enddate');

        $data = $this->m_sales->data_sales_perpoduct($startdate,$enddate);

        $this->set_response(array('success'=>true,'num_rows'=>count($data),'results'=>count($data),'rows'=>$data),REST_Controller::HTTP_OK);
    }

    public function sales_aging_get(){

        $startdate = $this->get('startdate');
        $enddate = $this->get('enddate');

        $data = $this->m_sales->data_sales_aging($startdate,$enddate);

        $this->set_response(array('success'=>true,'num_rows'=>count($data),'results'=>count($data),'rows'=>$data),REST_Controller::HTTP_OK);
    }

    public function import_sales_post(){

        $data = $this->post('data');
        $idunit=$this->user_data->idunit;

        $params = array(
            'idunit' => $idunit,
            'prefix' => 'SO',
            'table' => 'sales',
            'fieldpk' => 'idsales',
            'fieldname' => 'no_sales_order',
            'extraparams'=> null,
        );
        // $this->load->library('../controllers/setup');
        $noarticle = $this->m_data->getNextNoArticle($params);

        $start = 1;
        while (isset($data[$start])) {
            $d = $data[$start];
               
            $cek = $this->db->get_where('product',array('no_sku'=>$d['0'],'idunit'=>$idunit,'deleted'=>0))->row();
              
            $start++;
        }    

        $this->db->trans_begin();

        $idsales = $this->m_data->getPrimaryID2($this->post('idsales'),'sales', 'idsales', $idunit);
        $freight = 0;
        $totalamount = 0;

        /*
        format nomor yang diterima. 320.200,00
        */
        $start = 1;
        while (isset($data[$start])) {
            $d = $data[$start];
            $totalamount +=$d['3'];

            $start++;
        }
        
        //query buyer name
        $header = array(
            'no_sales_order' => $noarticle,
            'idsales' => $idsales,
            'customer_type'=> 1,
            'subtotal' => request_number($totalamount),
            'disc' => 0,
            'totalamount' => request_number($totalamount),
            'other_fee'=> request_number($this->post('other_fee')),
            'comments' =>'import sales',
            'paidtoday'=> $totalamount,
            'unpaid_amount'=> 0,
            'idpayment'=> 1, //tunai
            'type' => 2,             
            'status'  => 5, //paid
            'userin'=> $this->user_data->user_id,
            'idunit'  => $idunit,
            'pos_payment_type_id'=>$this->post('payment_type'),
            'source'=>2,
            'datein'=>date('Y-m-d H:m:s'),
            'date_sales'=>remove_slash($this->post('date_sales')),
            'order_status'=>6,//completed 
        );

        $this->db->insert('sales', $header);

        $start = 1;
        while (isset($data[$start])) {
            $d = $data[$start];
            
            //query product
            $p = $this->db->get_where('product',array('no_sku'=>$d['0']));
            $r = $p->row();

            $item = array(
                'idsales' => $header['idsales'],
                'product_id' => $r->product_id,
                'qty' => $d['2'],
                'disc' => 0,
                'price' => $r->retail_price,
                'total'=> $d['3'],
                'deleted' => 0
            );

            $idsalesitem = $this->m_data->getPrimaryID2($this->post('salesitem'),'salesitem', 'idsalesitem', $idunit);
            $item['idsalesitem'] =  $idsalesitem;
            $item['userin'] = $this->user_data->user_id;
            $item['datein'] = date('Y-m-d H:i:s');

            $this->db->insert('salesitem', $item);
            $start++;
           
        }
 
        if($this->db->trans_status() === false){
            $this->db->trans_rollback();
            $json = array('success'=>false,'message'=>'Penjualan gagal tersimpan. Mohon coba kembali.');
            $this->set_response($json, REST_Controller::HTTP_BAD_REQUEST); 
        }else{
            $this->db->trans_commit();

            //history stock
            $this->stock_after_sale($header['idsales'],$type=8);
            //end

            //buat journal
            $invoice_status = 5; //paid
            // $journal = $this->m_sales->jurnal_sales(date('Y-m-d'),'Sales #'.$noarticle,$header['totalamount'],$header['idunit'],$header['userin'],$idsales);

            // $this->db->where('idsales',$idsales);
            // $this->db->update('sales',array(
            //         'idjournal'=>$journal['idjournal']
            // ));
            // $header['idjournal'] = $journal['idjournal'];


            $json = array('success'=>true,'message'=>'Penjualan berhasil tersimpan');
            $this->set_response($json, REST_Controller::HTTP_CREATED); 
        }
    }

    public function consig_byproduct_get(){
        $startdate = $this->get('startdate')!='' ? str_replace("T00:00:00", "", $this->get('startdate')) : null;
        $enddate = $this->get('enddate')!='' ? str_replace("T00:00:00", "", $this->get('enddate')) : null;

        $qprod = $this->db->query("select a.product_id,a.product_name,a.retail_price,a.buy_price,a.no_sku,a.retail_price_member,a.consignment_base_price,a.consignment_owner_id,a.consignment_owner_type_id,
                    case
                        when consignment_owner_type_id = 1 then h.member_name
                        when consignment_owner_type_id = 2 then i.namesupplier
                        else 'Undefined'
                    end as owner_name,
                    case
                        when consignment_owner_type_id = 1 then 'Anggota'
                        when consignment_owner_type_id = 2 then 'Non Anggota'
                        else 'Undefined'
                    end as owner_type_name
                    from product a
                    left join member h ON a.consignment_owner_id = h.id_member
                    left join supplier i ON a.consignment_owner_id = i.idsupplier
                    where a.deleted = 0 and a.idunit = ".$this->post('idunit')." and a.is_consignment != 0");
        
        $data = array();
        $i=0;
        foreach ($qprod->result() as $r) {
            $data[$i]['product_id'] = intval($r->product_id);
            $data[$i]['no_sku'] = $r->no_sku;
            $data[$i]['ownership'] = $r->owner_name;
            $data[$i]['ownership_type'] = $r->owner_type_name;
            $data[$i]['product_name'] = $r->product_name;
            $data[$i]['consignment_base_price'] = intval($r->consignment_base_price);

            $wer = null;
            if($startdate!='' && $enddate!=''){
                $wer.= "  and (b.date_sales between '".$startdate."' and '".$enddate."')";
            }

            $qsales = $this->db->query("select COALESCE(sum(a.qty),0) as total_qty,COALESCE(sum(a.total),0) as total_sales_amount
                                        from salesitem a
                                        join sales b ON a.idsales = b.idsales
                                        where a.product_id = ".$r->product_id." and b.display is null and b.status = 5")->row();
            $data[$i]['total_qty'] = intval($qsales->total_qty);
            $data[$i]['total_sales_amount'] = intval($qsales->total_sales_amount);
            $data[$i]['gross_margin'] = $qsales->total_sales_amount-($r->consignment_base_price*$qsales->total_qty);
        }

        $num_rows = count($data);
            $message = [
                    'success' => true,
                    'numrow'=>$num_rows,
                    'results'=>$num_rows,
                    'rows' => $data
            ];

        $this->set_response($message, REST_Controller::HTTP_OK); 
    }

    public function request_post(){
        // print_r($this->post());
        $this->db->trans_start(); 

        $tax_data = $this->m_data->tax_config();
        if(!$tax_data['success']){
            $this->set_response(array('success'=>false,'message'=>'Maaf, tidak dapat memproses pembelian. Err Code: TAX_UNDEFINED'), REST_Controller::HTTP_BAD_REQUEST); 
            return false;
        } else {
            $tax_id = $tax_data['tax_sales_id'];
        }
        // $tax_id = $this->post('tax_id') =='' ? null : $this->post('tax_id');

        $sales_item = json_decode($this->post('items'));
        $shipping_cost = $this->post('shipping_fee');
        $include_tax = 0;

        $product_valid = $this->product_validation($sales_item);
        if(!$product_valid['success']){
            $this->set_response($product_valid, REST_Controller::HTTP_BAD_REQUEST); 
            return false;
        }

        $recap_sales = $this->calc_sales_invoice_recap($include_tax,$shipping_cost,$sales_item,$tax_id);
        $total_before_tax = $recap_sales['total'];
        // print_r($recap_sales); die;

        $data_sales = array(
            'subtotal'=> $recap_sales['sub_total'],
            'freight'=>$recap_sales['shipping_cost'],
            'disc'=>$recap_sales['total_disc'],
            'total'=>$recap_sales['total'],
            // 'dpp'=>round($dpp),
            'tax'=>round($recap_sales['total_tax']),
            'totalamount'=>$recap_sales['grand_total'],
            'idunit'=>$this->post('idunit'),
            'due_date'=>date('Y-m-d', strtotime('Y-m-d'. ' + 7 days')),           
            'id_payment_term'=>1,
            'invoice_status'=>1,
            'date_sales'=>date('Y-m-d'),
            'invoice_date'=>date('Y-m-d'),
            'date_quote'=>date('Y-m-d'),
            'include_tax'=>0,
            'tax_id'=>$tax_id
        );

        if($this->post('member_id')!=''){
            //member
            $data_sales['id_member'] = $this->post('member_id');
            $data_sales['idcustomer'] = $this->post('member_id');
            $data_sales['customer_type'] = 1;
        } else {
            //non member
            $data_sales['id_member'] = null;
            $data_sales['idcustomer'] = null;
            $data_sales['customer_type'] = 2;
        }

         //no order
        $params = array(
            'idunit' => $this->post('idunit'),
            'prefix' => 'SO',
            'table' => 'sales',
            'fieldpk' => 'idsales',
            'fieldname' => 'no_sales_order',
            'extraparams'=> null,
        );
        // $this->load->library('../controllers/setup');
        $no_sales_order = $this->m_data->getNextNoArticle($params);
        $data_sales['no_sales_order'] = $no_sales_order;
        //end no order

        //start no invoice
        $params = array(
            'idunit' => $this->post('idunit'),
            'prefix' => 'INV',
            'table' => 'sales',
            'fieldpk' => 'idsales',
            'fieldname' => 'noinvoice',
            'extraparams'=> null,
        );
        // $this->load->library('../controllers/setup');
        $invoice_no = $this->m_data->getNextNoArticle($params);
        $data_sales['noinvoice'] = $invoice_no;

        $data_sales['memo'] = 'New Sales Request #'.$no_sales_order;

        $sales_id = $this->m_data->getPrimaryID2(null,'sales','idsales');
        $data_sales['order_status'] = 1; //menunggu konfirmasi
        $data_sales['idsales'] = $sales_id;
        $data_sales['paidtoday'] = 0;
        $data_sales['unpaid_amount'] = $data_sales['totalamount'];
        $data_sales['datein'] = date('Y-m-d H:m:s');
        $data_sales['invoice_date'] = date('Y-m-d');
        $data_sales['userin'] = $this->user_data->user_id;

        $this->db->insert('sales',$data_sales);

        $this->update_sales_item($sales_id,$sales_item);

         //PAJAK
        $idjournal = null;
        // $idjournal = $this->m_sales->jurnal_sales_invoice($invoice_date,'Sales Invoice #'.$invoice_no,$data_sales['totalamount'],$this->post('idunit'),$this->user_data->user_id,$sales_id);
        if($tax_id!='' && $tax_id!=null){
            $qtax = $this->m_tax->get($tax_id);
            $qtax = $qtax['data'][0];
            if($qtax['is_tax_ppn']==1 && $qtax['is_tax_pph23']==0){
                //PPN SAJA 
                $idjournal = $this->m_sales->jurnal_sales_invoice_ppn($data_sales['invoice_date'],'Sales Invoice #'.$invoice_no,$total_before_tax,$data_sales['totalamount'],$this->post('idunit'),$this->user_data->user_id,$sales_id,$tax_id,$data_sales['noinvoice']);
            }

            if($qtax['is_tax_ppn']==0 && $qtax['is_tax_pph23']==1){
                //PPH SAJA 
                $idjournal = $this->m_sales->jurnal_sales_invoice_pph($data_sales['invoice_date'],'Sales Invoice #'.$invoice_no,$total_before_tax,$data_sales['totalamount'],$this->post('idunit'),$this->user_data->user_id,$sales_id,$tax_id,$data_sales['noinvoice']);
            }

            if($qtax['is_tax_ppn']==1 && $qtax['is_tax_pph23']==1){
                //PPN dan PPH 
                $idjournal = $this->m_sales->jurnal_sales_invoice_ppn_n_pph($data_sales['invoice_date'],'Sales Invoice #'.$invoice_no,$total_before_tax,$data_sales['totalamount'],$this->post('idunit'),$this->user_data->user_id,$sales_id,$tax_id,$data_sales['noinvoice']);
            }
        }
        //END PAJAK

        $this->db->where('idsales',$sales_id);
        $this->db->update('sales',array(
                'idjournal'=>$idjournal
            ));

         // print_r($data_sales); die;
        $this->db->trans_complete(); 

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();

            $this->set_response(array(
                'success'=>false,
                'message'=>'Pemesanan gagal diproses'
            ), REST_Controller::HTTP_BAD_REQUEST); 
        } else {
            $this->db->trans_commit();
            $this->set_response(array(
                'success'=>true,
                'message'=>'Pemesanan berhasil diproses'
            ), REST_Controller::HTTP_OK); 
        }
    }

    public function set_orderStatus_post(){

        $this->db->trans_begin();

        //cek pesanan atau bukan
        $cek = $this->db->query("select order_status from sales where idsales=".$this->post('idsales')." ")->row();
        // echo $this->db->last_query();
        if($cek->order_status !=''){
            //echo "string";
            $order_status = $this->post('order_status');
            $idsales      = $this->post('idsales');


            $this->db->where('idsales',$idsales);
            $this->db->update('sales',array(
                'order_status'=>$order_status
            ));
            
        }else{
            // echo "string";
            $this->set_response(array('success'=>false,'message'=>'Penjualan bukan dari pesanan'),REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }

        
        if($this->db->trans_status()===FALSE){
            $this->db->trans_rollback();
        }else{
            $this->db->trans_commit();
            echo $this->db->last_query();
        }
    }

    function sales_cash_summary_get(){

        if($this->get('startdate')!=''){
            $startdate = backdate2($this->get('startdate'));
            // echo $startdate;
        }else{
            $startdate = null;            
        }

        if($this->get('enddate')!=''){
           $enddate = backdate2($this->get('enddate'));

        }else{
           $enddate = null;            
        }
        
        
        $data = $this->m_sales->sales_cash_summary($startdate,$enddate);
        // echo $this->db->last_query();
        $this->response(array('success'=>true,'rows'=>$data),REST_Controller::HTTP_OK);
    }

    function sales_cash_detail_get(){
        if($this->get('startdate')!=''){
            $startdate = backdate2($this->get('startdate'));
            // echo $startdate;
        }else{
            $startdate = null;            
        }

        if($this->get('enddate')!=''){
           $enddate = backdate2($this->get('enddate'));

        }else{
           $enddate = null;            
        }
        
        
        $data = $this->m_sales->sales_cash_detail($startdate,$enddate);
        // echo $this->db->last_query();
        $this->response(array('success'=>true,'rows'=>$data),REST_Controller::HTTP_OK);
    }

    function sales_unpaid_summary_get(){

        if($this->get('startdate')!=''){
            $startdate = backdate2($this->get('startdate'));
            // echo $startdate;
        }else{
            $startdate = null;            
        }

        if($this->get('enddate')!=''){
           $enddate = backdate2($this->get('enddate'));

        }else{
           $enddate = null;            
        }
        
        
        $data = $this->m_sales->sales_unpaid_summary($startdate,$enddate);
        // echo $this->db->last_query();
        $this->response(array('success'=>true,'rows'=>$data),REST_Controller::HTTP_OK);
    }

    function sales_unpaid_detail_get(){
        if($this->get('startdate')!=''){
            $startdate = backdate2($this->get('startdate'));
            // echo $startdate;
        }else{
            $startdate = null;            
        }

        if($this->get('enddate')!=''){
           $enddate = backdate2($this->get('enddate'));

        }else{
           $enddate = null;            
        }
        
        
        $data = $this->m_sales->sales_unpaid_detail($startdate,$enddate);
        // echo $this->db->last_query();
        $this->response(array('success'=>true,'rows'=>$data),REST_Controller::HTTP_OK);
    }

    function upddate_medic_payment_put(){

        $this->db->trans_begin();
        
        $check = $this->db->get_where('sales',array('idsales'=>$this->put('sales_id')));

        if($check->num_rows()>0){

            $row = $check->row();

            if($this->put('invoice_status')==5){
                
                $unpaid_amount  = $row->{'unpaid_amount'}-cleardot($this->put('subtotal'));
                $paidtoday      = $row->{'paidtoday'}+cleardot($this->put('total'));

            }

            // else if($this->put('invoice_status')==2){
                
            //     $unpaid_amount  = $row->{'unpaid_amount'}+cleardot($this->put('subtotal'));
            //     $paidtoday  = $row->{'paidtoday'}-cleardot($this->put('subtotal'));

            // }
            else{
                
                $unpaid_amount  = cleardot($this->put('subtotal'));
                $paidtoday      = 0;

            }
            
            $grand_total    = cleardot($this->put('total'));
            $status         = $this->put('invoice_status');
            $payment_method = $this->put('payment_methode');
            $shipping_cost  = $this->put('shipping_fee');
            $disc = $this->put('diskon');

            $data_sales = array(
                'unpaid_amount'=>$unpaid_amount,
                'totalamount'=>$grand_total,
                'paidtoday'=>$paidtoday,
                'freight'=>$shipping_cost,
                'disc'=>$disc,
                'invoice_status'=>$status,
                'payment_method'=>$payment_method,
                'memo'=>$this->put('memo'),
                'payment_amount'=>$this->put('payment_amount')
            );
            
            
            $this->db->where('idsales',$this->put('sales_id'));
            $this->db->update('sales',$data_sales);
            
            if($this->db->trans_status() === false){

                $this->db->trans_rollback();
                $this->response(array('status'=>false,'message'=>'Update data penjualan gagal'),REST_Controller::HTTP_BAD_REQUEST);
            }else{

                $this->db->trans_commit();
                
                if($this->put('invoice_status')==5){
                    $this->stock_after_sale($this->put('sales_id'),$type=1);

                }

                $this->response(array('status'=>true,'message'=>'Update data penjualan berhasil'),REST_Controller::HTTP_CREATED);
            }
        }
    }
}
?>