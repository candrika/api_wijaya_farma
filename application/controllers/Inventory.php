<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Inventory extends MY_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        $this->load->model('m_inventory');
        $this->load->model('m_masterproductlocation');

        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        $this->methods['users_get']['limit'] = 1000; // 500 requests per hour per user/key
        $this->methods['users_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['users_delete']['limit'] = 50; // 50 requests per hour per user/key
    }

    private function upload_image(){

        $config['upload_path']          = './uploads/';
        $config['allowed_types']        = 'gif|jpg|jpeg|png|bmp';
        $config['max_size']             = 1000;
        $config['max_width']            = 1366;
        $config['max_height']           = 768;

        $this->load->library('upload', $config);

        if ( ! $this->upload->do_upload('product_image'))
        {
            // echo $this->upload->display_errors();

            if($this->upload->display_errors()!=='<p>You did not select a file to upload.</p>'){
                return array('success'=>false,'message'=>$this->upload->display_errors());
            } else {
                return array('success'=>true,'file_name'=>null);
            }
                
        }
        else
        {
                $file_name = $this->upload->data()['file_name'];
                return array('success'=>true,'file_name'=>$file_name);
        }
    }

    public function multi_product_post(){
        // $userid = (int) $this->user_data->user_id;
        // $idunit = (int) $this->user_data->idunit;
        // print_r($this->post('data')); die;

        // $datas = json_decode(file_get_contents("php://input"));
        $datas = json_decode($this->post('data'));
        // print_r($datas); die;

        // $datas = json_decode($this->post('datas'));
        $this->db->trans_begin();

        $nums = 1;
        foreach ($datas as $v) {
        
            $product_id = $this->m_data->getPrimaryID2(null,'product', 'product_id');
            // echo $this->db->last_query(); 
            // echo $product_id; die;
            // $this->
            // $qbiz = $this->db->get_where('business',array('business_code'=>$v->{'business_code'},'idunit'=>12));
            // if($qbiz->num_rows()>0){
            //     $r = $qbiz->row();
            //     $business_id = $r->business_id;
            // } else {
            //     $business_id = null;
            // }

            $data = array(
                'product_name' => $v->{'product_name'},
                'inventory_class_id' => $v->{'inventory_class_id'},
                'no_sku' => $v->{'product_no'},
                'no_barcode' => $v->{'no_barcode'},
                'cost_price' => 0,
                'status'=>1,
                'idunit'=>$v->{'idunit'},
                'stock_available'=>$v->{'stock_available'}
            );
            
            if($v->{'retail_price'}!='' && $v->{'retail_price'}!=null){
                $data['is_sellable'] = 2;
                $data['retail_price'] = $v->{'retail_price'};
                // $data['coa_sales_id'] = $this->get_coa_id($v->{'coa_sales_code'});
            } else {
                $data['is_sellable'] = 1;
            }

            if($v->{'buy_price'}!='' && $v->{'buy_price'}!=null){
                $data['is_purchasable'] = 2;
                $data['buy_price'] = $v->{'buy_price'};
                // $data['coa_purchase_id'] = $this->get_coa_id($v->{'coa_purchase_code'});
                // $data['coa_tax_purchase_id'] = $this->post('coa_tax_purchase_id') != '' ? $this->post('coa_tax_purchase_id') : null;
            } else {
                $data['is_purchasable'] = 1;
            }

            $data['product_id'] = $product_id;
            $this->db->insert('product',$data);
            // print_r($data); die;

            $nums++;
        }

        
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();

            $message = [
                    'success' => false,
                    'message' => 'Error while inserting product. Please try again later.'
            ];

            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
        } else {
            $this->db->trans_commit();

            $message = [
                    'success' => true,
                    'message' => ($nums-=1).' Products inserted successfully'
            ];

            $this->set_response($message, REST_Controller::HTTP_OK); // CREATED (201) being the HTTP response code

        }
    }

    function get_coa_id($code){
        $kode = strval($code); 
        $q = $this->db->get_where('account',array("accnumber"=>$kode,"deleted"=>0,"idunit"=>12));
        if($q->num_rows()>0){
            $r = $q->row();
            return $r->idaccount;
        } else {
            return null;
        }
    }

    public function product_post(){

        $userid = (int) $this->post('user_id');
        $product_composition = json_decode($this->post('json'));
        $product_compositionfee = json_decode($this->post('json_compositionfee'));

        if ($userid <= 0){
            $this->response(array('message'=>'userid not found'), REST_Controller::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
        }
        
        //input product baru
        if($this->post('product_id') == ''){
            $cek = $this->db->get_where('product',array('no_sku'=>$this->post('no_sku'),"idunit"=>$this->post('idunit'),'deleted'=>0,'business_id'=>$this->post('business_id')));
        
            if($cek->num_rows() >0){
                $this->response(array('message'=>'Maaf, Kode produk sudah ada di database!'), REST_Controller::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
            }
        }
        

        $this->db->trans_begin();
        
        if($this->post('product_id') == ''){
            //insert
            // $product_id = $this->m_data->getSeqVal('seq_product');
            $product_id = $this->m_data->getPrimaryID($this->post('product_id'),'product','product_id');
            // echo $this->db->last_query(); 
            // echo $product_id;
        } else {
            //update
            $product_id = $this->post('product_id');
        }

         $data = array(
            // 'product_id' => $this->post('product_id') == '' ? $this->m_data->getSeqVal('seq_product') : $this->post('product_id'),
            'product_name' => $this->post('product_name'),
            'business_id'=> $this->post('business_id'),
            'inventory_class_id' => $this->input->post('inventory_class_id'),
            'product_no' => $this->post('product_no') != '' ? $this->post('product_no') : null,
            'idinventorycat' => $this->post('idinventorycat') != '' ? $this->post('idinventorycat') : null,
            'product_type_id' => $this->post('product_type_id') != '' ? $this->post('product_type_id') : null,
            'idbrand' => $this->post('idbrand') != '' ? $this->post('idbrand') : null,
            'product_description' => $this->post('product_description'),
            'cost_price' => $this->post('cost_price') != '' ? cleardot2($this->post('cost_price')) : null,
            'product_unit_id' => $this->post('product_unit_id') != '' ? $this->post('product_unit_id') : null,           
            'stock_available' => $this->post('stock_available') != '' ? cleardot2($this->post('stock_available')) : null,
            'available_on_pos' => $this->post('available_on_pos') != '' ? $this->post('available_on_pos') : null,
            'idsupplier' => $this->post('idsupplier') != '' ? $this->post('idsupplier') : null,
            'no_sku' => $this->post('no_sku') != '' ? $this->post('no_sku') : null,
            'no_barcode' => $this->post('no_barcode') != '' ? $this->post('no_barcode') : null,
            'no_supplier' => $this->post('no_supplier') != '' ? $this->post('no_supplier') : null,
            'status'=>$this->post('status'),
            'location_id'=>$this->post('product_location_id') !='' ? $this->post('product_location_id'):null,
            'idunit'=>$this->post('idunit'),
            'product_id' => $this->m_data->getPrimaryID($this->post('product_id'),'product','product_id'),
            'vendor_id' => $this->m_data->getPrimaryID($this->post('vendor_id'),'product','vendor_id'),
            'coa_inventory_id'=> $this->post('coa_inventory_id') !='' ? $this->post('coa_inventory_id'):null,
            'expired_date'=>$this->post('expired_date') !='' ? backdate2($this->post('expired_date')):null,
        );
    
        if($this->post('is_consignment')=='on'){
            $data['is_consignment'] = $this->post('is_consignment') == 'on' ? 2 : 1;
            $data['consignment_base_price'] = $this->post('consignment_base_price') != '' ? cleardot2($this->post('consignment_base_price')) : null;
            $data['consignment_owner_id'] = $this->post('consignment_owner_id') != '' ? $this->post('consignment_owner_id') : null;
            $data['consignment_owner_type_id'] = $this->post('consignment_owner_type_id') != '' ? $this->post('consignment_owner_type_id') : null;
        } else {
            $data['is_consignment'] = 1;
        }

        if($this->post('is_sellable')=='on'){
            $data['is_sellable'] = $this->post('is_sellable') == 'on' ? 2 : 1;
            $data['wholesale_price'] = $this->post('wholesale_price') != '' ? cleardot2($this->post('wholesale_price')) : null;
            $data['retail_price'] = $this->post('retail_price') != '' ? cleardot2($this->post('retail_price')) : null;
            $data['retail_price_member'] = $this->post('retail_price_member') != '' ? cleardot2($this->post('retail_price_member')) : null;
            $data['coa_sales_id'] = $this->post('coa_sales_id') != '' ? $this->post('coa_sales_id') : null;
            $data['coa_tax_sales_id'] = $this->post('coa_tax_sales_id') != '' ? $this->post('coa_tax_sales_id') : null;
        } else {
            $data['is_sellable'] = 1;
        }

        if($this->post('is_purchasable')=='on'){
            $data['is_purchasable'] = $this->post('is_purchasable') == 'on' ? 2 : 1;
            $data['buy_price'] = $this->post('buy_price') != '' ? cleardot2($this->post('buy_price')) : null;
            
            if($this->post('buy_price') != ''){
                $data['product_balance'] = $data['stock_available']*$data['buy_price'];
            }
            
            $data['coa_purchase_id'] = $this->post('coa_purchase_id') != '' ? $this->post('coa_purchase_id') : null;
            $data['coa_tax_purchase_id'] = $this->post('coa_tax_purchase_id') != '' ? $this->post('coa_tax_purchase_id') : null;
        } else {
            $data['is_purchasable'] = 1;
        }
        
        $upload_image = $this->upload_image();

        // echo count($product_composition);
        if(count($product_composition)>0){
            $this->insert_product_composition($product_composition,$product_id,$userid);
        }

        if(count($product_compositionfee)>0){
            $this->insert_product_compositionfee($product_compositionfee,$product_id,$userid);
        }

        if($upload_image['success']==true && $upload_image['file_name']!=null){
            $data['product_image'] = $upload_image['file_name'];
        }
        
        if($this->post('product_id') == ''){
            //insert
            $this->db->insert('product',$data);

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();

                $message = [
                        'success' => false,
                        'message' => 'Error while inserting product. Please try again later.'
                ];

                $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
            } else {
                $this->db->trans_commit();

                $message = [
                        'success' => true,
                        // 'id' => $data['product_id'], // Automatically generated by the model
                        'message' => 'New Product inserted successfully'
                ];

                $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
            }
        } else {
            //update
            // print_r($data);die;
            $this->db->where('product_id',$product_id);
            $this->db->update('product',$data);
            // $this->db->last_query();
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();

                $message = [
                        'success' => false,
                        'message' => 'Error while updating product. Please try again later.'
                ];

                $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
            } else {
                $this->db->trans_commit();

                $message = [
                        'success' => true,
                        'message' => 'Product updated successfully'
                ];

                $this->set_response($message, REST_Controller::HTTP_OK); // CREATED (201) being the HTTP response code
            }
        }
       

        
    }

    public function product_put()
    {
        $this->db->trans_begin();

        $id = (int) $this->put('id');

        if ($id <= 0){
            $this->response(array('message'=>'parameter not found'), REST_Controller::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
        }

        $data = array(
            // 'product_id' => $this->post('product_id') == '' ? $this->m_data->getSeqVal('seq_product') : $this->post('product_id'),
            'product_name' => $this->post('product_name'),
            'inventory_class_id' => $this->input->post('inventory_class_id'),
            'product_no' => $this->post('product_no') != '' ? $this->post('product_no') : null,
            'idinventorycat' => $this->post('idinventorycat') != '' ? $this->post('idinventorycat') : null,
            'product_type_id' => $this->post('product_type_id') != '' ? $this->post('product_type_id') : null,
            'product_unit_id' => $this->post('product_unit_id') != '' ? $this->post('product_unit_id') : null,
            'idbrand' => $this->post('idbrand') != '' ? $this->post('idbrand') : null,
            'product_description' => $this->post('product_description'),
            'cost_price' => $this->post('cost_price') != '' ? cleardot2($this->post('cost_price')) : null,
            'buy_price' => $this->post('buy_price') != '' ? cleardot2($this->post('buy_price')) : null,
            'wholesale_price' => $this->post('wholesale_price') != '' ? cleardot2($this->post('wholesale_price')) : null,
            'retail_price' => $this->post('retail_price') != '' ? cleardot2($this->post('retail_price')) : null,
            'retail_price_member' => $this->post('retail_price_member') != '' ? cleardot2($this->post('retail_price_member')) : null,
            'stock_available' => $this->post('stock_available') != '' ? $this->post('stock_available') : null,
            'available_on_pos' => $this->post('available_on_pos') != '' ? $this->post('available_on_pos') : null,
            'is_sellable' => $this->post('is_sellable') != '' ? $this->post('is_sellable') : null,
            'is_purchasable' => $this->post('is_purchasable') != '' ? $this->post('is_purchasable') : null,
            'location_id' => $this->post('product_location_id') != '' ? $this->post('product_location_id') : null,
            'idsupplier' => $this->post('idsupplier') != '' ? $this->post('idsupplier') : null,
            'no_sku' => $this->post('no_sku') != '' ? $this->post('no_sku') : null,
            'no_barcode' => $this->post('no_barcode') != '' ? $this->post('no_barcode') : null,
            'no_supplier' => $this->post('no_supplier') != '' ? $this->post('no_supplier') : null,
            'status'=>$this->post('status'),
            'vendor_id' => $this->m_data->getPrimaryID($this->post('vendor_id'),'product','vendor_id'),            
            'idunit'=>$this->user_data->idunit
        );

        $upload_image = $this->upload_image();

        if($upload_image['success']==true && $upload_image['file_name']!=null){
            $data['product_image'] = $upload_image['file_name'];
        }

        $this->db->where('product_id',$id);
        $this->db->update('product',$data);
        
        if($this->db->affected_rows()<=0){
             $message = [
                    'success' => false,
                    'message' => 'id not found.'
            ];

            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();

            $message = [
                    'success' => false,
                    'message' => 'Error while updating product. Please try again later.'
            ];

            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
        } else {
            $this->db->trans_commit();

            $message = [
                    'success' => true,
                    'message' => 'Product updated successfully'
            ];

            $this->set_response($message, REST_Controller::HTTP_OK); // CREATED (201) being the HTTP response code
        }
    }

    public function products_get(){
        $idunit = (int) $this->get('idunit');
        $inventory_class_id = (int) $this->get('inventory_class_id');

        if ($idunit <= 0){
            $this->response(array('message'=>'idunit not found'), REST_Controller::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
        }

        $q = $this->m_inventory->get($this->get('query'),null,$idunit,$inventory_class_id,$this->get('is_sellable'),$this->get('is_purchasable'),$this->get('idinventorycat'),$this->get('product_location_id'),$this->get('business_id'));
        // print_r($q);
        // echo $this->db->last_query();die;
        $num_rows = $q['total'];

            $message = [
                    'success' => true,
                    'numrow'=>$num_rows,
                    'results'=>$num_rows,
                    'rows' => $q['data']
            ];

            $this->set_response($message, REST_Controller::HTTP_OK); 
    }

    public function product_get(){
        $id = (int) $this->get('id');
        $idunit = (int) $this->get('idunit');

        if($id<=0){
             $message = [
                    'success' => false,
                    'message' => 'id not found.'
            ];

            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }

         if($idunit<=0){
             $message = [
                    'success' => false,
                    'message' => 'idunit not found.'
            ];

            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }

        $q = $this->m_inventory->get(null,$id,$idunit);
        if($q['total']<=0){
              $message = [
                    'success' => false,
                    'message' => 'product not found.'
            ];

            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }

        $data = $q['data'];
        if($data[0]['product_image']==null || $data[0]['product_image']==''){
            $data[0]['product_image'] = 'not-available.png';
            $data[0]['product_image_fullpath'] = base_url().'uploads/not-available.png';            
        } else {
            $data[0]['product_image_fullpath'] = base_url().'uploads/'.$data[0]['product_image'];
        }

        $message = [
                'success' => true,
                'data' => $data[0]
        ];

        $this->set_response($message, REST_Controller::HTTP_OK); 
    }

    public function product_delete()
    {
        $this->db->trans_begin();

        $id = (int) $this->delete('id');

        if ($id <= 0){
            $this->response(array('message'=>'parameter not found'), REST_Controller::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
        }

        $this->db->where('product_id',$id);
        $this->db->delete('product');
        
        if($this->db->affected_rows()<=0){
             $message = [
                    'success' => false,
                    'message' => 'id not found.'
            ];

            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();

            $message = [
                    'success' => false,
                    'message' => 'Error while deleting product. Please try again later.'
            ];

            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
        } else {
            $this->db->trans_commit();

            $message = [
                    'success' => true,
                    'message' => 'Product deleted successfully'
            ];

            $this->set_response($message, REST_Controller::HTTP_OK); // CREATED (201) being the HTTP response code
        }
    }

    public function product_types_get(){

        $idunit = (int) $this->get('idunit');

        if ($idunit <= 0){
            $this->response(array('message'=>'idunit not found'), REST_Controller::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
        }

        $data = $this->m_inventory->get_type($this->get('query'),$idunit);
        $num_rows = count($data);
            $message = [
                    'success' => true,
                    'numrow'=>$num_rows,
                    'results'=>$num_rows,
                    'rows' => $data
            ];

            $this->set_response($message, REST_Controller::HTTP_OK); 
    } 

    public function data_stock_get(){


        $id_product  = $this->get('product_id');
        $startdate   = $this->get('startdate');
        $enddate     = $this->get('enddate');
        $idunit      = $this->get('idunit');

        // echo $startdate;
        // echo $enddate;   

        $stock = $this->m_inventory->get_stock($startdate,$enddate,$id_product,$idunit);
        // echo $this->db->last_query();   
        $num_rows=$stock->num_rows();
        $results =$stock->num_rows();
        $rows    =[];
        
        if($stock->num_rows() >0){
            foreach ($stock->result() as $key => $value) {
                # code...
                // $business_name = $this->business_Unitname($value->{'business_id'});
                // print_r($business_name);

                $datein = str_replace(' ', ' @ ', $value->datein);
                $rows[]=array(
                    'stock_history_id'=>$value->stock_history_id,
                    'product_id'=>$value->product_id,
                    'product_name'=>$value->product_name,
                    'datein'=>$datein,
                    'type_adjustment'=>$value->type_adjustment,
                    'no_transaction'=>$value->no_transaction,
                    'current_qty'=>$value->current_qty,
                    'trx_qty'=>$value->trx_qty,
                    'new_qty'=>$value->new_qty,
                    'no_sku'=>$value->no_sku,
                    'no_barcode'=>$value->no_barcode,
                    // 'business_name'=>$business_name 
                );

            }

            $message=array('success' => true,'num_rows'=>$num_rows,'results'=>$results,'rows'=>$rows);
            $this->set_response($message, REST_Controller::HTTP_OK);

        }else{
            $message=array('success' => true,'num_rows'=>$num_rows,'results'=>$results,'rows'=>$rows);
            $this->set_response($message, REST_Controller::HTTP_OK); 
        }

    }

    public function unit_code_get(){
        
        $wer ="";

        if($this->get('product_unit_id')){
            $product_unit_id = $this->get('product_unit_id');
        }else{
            $product_unit_id=null;
        }
        
        $q = $this->db->get_where('product_unit',array('idunit'=>$this->get('idunit'),'deleted'=>0));
        // echo $this->db->last_query();
        $data = array();
        $i=0;
        foreach ($q->result_array() as $key => $value) {
            $data[$i]['product_unit_id'] = $value['product_unit_id'];
            $data[$i]['product_unit_code'] = $value['product_unit_code'];
            $i++;
        }
        $this->response(array('success'=>true,'data'=>$data), REST_Controller::HTTP_OK);
    }

    function export_product_get(){

        $sheet = $this->PhpSpreadsheet->getActiveSheet()->setTitle('Data Produk');
        $style_header = [ 'borderStyle' => $this->PhpSpreadsheetBorder::BORDER_MEDIUM, 'color' => [ 'rgb' => '0033cc' ] ];
      
        // $sheet->setCellValue('A1', 'Data Produk')->getStyle('A1')->getFont()->setBold(true)->setSize(19);
        // $sheet->getStyle('A1')->getBorders()->getBottom()->applyFromArray($style_header);

        //list
        $sheet->setCellValue('A1','Kode Produk')->getStyle('A1')->getFont()->getBold(true);
        $sheet->setCellValue('B1','No. Barcode')->getStyle('B1')->getFont()->getBold(true);
        $sheet->setCellValue('C1','Nama Produk')->getStyle('C1')->getFont()->getBold(true);
        $sheet->setCellValue('D1','Kode Unit Usaha')->getStyle('D1')->getFont()->getBold(true);
        $sheet->setCellValue('E1','Jenis Produk')->getStyle('E1')->getFont()->getBold(true);
        $sheet->setCellValue('F1','Stock')->getStyle('F1')->getFont()->getBold(true);
        $sheet->setCellValue('G1','Harga Beli')->getStyle('G1')->getFont()->getBold(true);
        $sheet->setCellValue('H1','Akun Pembelian')->getStyle('H1')->getFont()->getBold(true);
        $sheet->setCellValue('I1','Harga Non Anggota')->getStyle('I1')->getFont()->getBold(true);
        $sheet->setCellValue('J1','Harga Anggota')->getStyle('J1')->getFont()->getBold(true);
        $sheet->setCellValue('K1','Kode Akun Penjualan')->getStyle('K1')->getFont()->getBold(true);

        //header
        // $sheet->getStyle('A3')->getBorders()->getTop()->applyFromArray($style_header);
        // $sheet->getStyle('B3')->getBorders()->getTop()->applyFromArray($style_header);
        // $sheet->getStyle('C3')->getBorders()->getTop()->applyFromArray($style_header);
        // $sheet->getStyle('D3')->getBorders()->getTop()->applyFromArray($style_header);
        // $sheet->getStyle('E3')->getBorders()->getTop()->applyFromArray($style_header);
        // $sheet->getStyle('F3')->getBorders()->getTop()->applyFromArray($style_header);
        // $sheet->getStyle('G3')->getBorders()->getTop()->applyFromArray($style_header);
        // $sheet->getStyle('H3')->getBorders()->getTop()->applyFromArray($style_header);
        // $sheet->getStyle('I3')->getBorders()->getTop()->applyFromArray($style_header);
        // $sheet->getStyle('J3')->getBorders()->getTop()->applyFromArray($style_header);
        // $sheet->getStyle('K3')->getBorders()->getTop()->applyFromArray($style_header);

        $id = json_decode($this->get('product_id'));
        $i=2;

        
            # code...
            $q = $this->db->query("SELECT 
                                    a.product_type_id,a.business_id,a.product_name,a.no_barcode,a.no_sku,a.buy_price,
                                    a.retail_price,a.retail_price_member,a.stock_available,b.business_code
                                    ,c.accnumber as purchase_acc,d.accnumber as sales_acc,a.inventory_class_id
                                    FROM
                                             product a
                                    JOIN 
                                             business b on b.business_id=a.business_id  
                                    LEFT JOIN 
                                             account c on c.idaccount=a.coa_purchase_id
                                    LEFT JOIN 
                                            account d on d.idaccount=a.coa_sales_id
                                    WHERE   a.idunit=".$this->get('idunit')." and a.deleted=0 GROUP BY product_id,b.business_id,c.accnumber,d.accnumber");
            
            
            if($q->num_rows()>0){
                $rq = $q->result_array();
                foreach ($rq as $key => $d) {
                    $sheet->setCellValue('A'.$i,$d['no_sku']);
                    $sheet->setCellValue('B'.$i,$d['no_barcode']);
                    $sheet->setCellValue('C'.$i,$d['product_name']);
                    $sheet->setCellValue('D'.$i,$d['business_code']);
                    $sheet->setCellValue('E'.$i,$d['inventory_class_id']);
                    $sheet->setCellValue('F'.$i,$d['stock_available']);
                    $sheet->setCellValue('G'.$i,$d['buy_price']);
                    $sheet->setCellValue('H'.$i,$d['purchase_acc']);
                    $sheet->setCellValue('I'.$i,$d['retail_price']);
                    $sheet->setCellValue('J'.$i,$d['retail_price_member']);
                    $sheet->setCellValue('K'.$i,$d['sales_acc']);
          
                    $i++;  
                }
            }

        $file = "data_daftar_produk.xlsx";
        $url_file =base_url().'gen_xlsx/'.$file;

        $this->PhpSpreadsheetXlsx->save('gen_xlsx/'.$file);

        $this->response(array('success'=>true,'excel_url'=>$url_file),REST_Controller::HTTP_OK);
    }

    function export_productList_get(){

        $idunit = $this->get('idunit');
        $inventory_class_id = $this->get('inventory_class_id');
        // $business_id = $this->get('business_id');

        $results = $this->db->query("SELECT 
                                        a.product_type_id,
                                        a.business_id,
                                        a.product_name,
                                        a.no_barcode,
                                        a.no_sku,
                                        a.buy_price,
                                        a.retail_price,
                                        a.stock_available,
                                        a.inventory_class_id,
                                        a.location_id
                                    FROM
                                             product a
                                    LEFT JOIN
                                            product_location e on a.location_id=e.product_location_id

                                    WHERE   a.idunit=".$this->get('idunit')." and a.deleted=0 and a.inventory_class_id=$inventory_class_id GROUP BY product_id");
        
        // print_r($results->result());die();

        $sheet =$this->PhpSpreadsheet->getActiveSheet();
        $sheet->setTitle('Data Produk Stok Opname');
        $style_header = [ 'borderStyle' => $this->PhpSpreadsheetBorder::BORDER_MEDIUM, 'color' => [ 'rgb' => '0033cc' ] ];
        
        //
        $sheet->setCellvalue('A1','No Barang')->getStyle('A1')->getFont()->getBold(true);
        $sheet->setCellvalue('B1','No Barcode')->getStyle('B1')->getFont()->getBold(true);
        $sheet->setCellvalue('C1','Nama Barang')->getStyle('C1')->getFont()->getBold(true);
        $sheet->setCellvalue('D1','Lokasi produk/Rak')->getStyle('D1')->getFont()->getBold(true);
        $sheet->setCellvalue('E1','Stock Terhitung')->getStyle('E1')->getFont()->getBold(true);
        $sheet->setCellvalue('F1','Catatan')->getStyle('G1')->getFont()->getBold(true);
        
        $i = 2;
        //generate cell value
        foreach ($results->result() as $key => $d) {
            # code...
            // print_r($d);
            if($d->{'location_id'}!=''){
                $rak = $this->db->query("SELECT * FROM product_location WHERE deleted=0 and idunit=".$idunit." and status=1 and product_location_id=".$d->{'location_id'})->row();
                
                if(isset($rak->{'location_name'})){
                    $rack_name = $rak->{'location_name'};
                }else{
                    $rack_name = null;
                }    

            }else{
                $rack_name = null;
            }

            $sheet->setCellvalue('A'.$i,$d->{'no_sku'});
            $sheet->setCellvalue('B'.$i,$d->{'no_barcode'});
            $sheet->setCellvalue('C'.$i,$d->{'product_name'});
            $sheet->setCellvalue('D'.$i,$rack_name);

            // if($d->{'location_id'}!=''){
            //     $sheet->setCellvalue('D'.$i,$rak->{'location_name'});
            // }else{
            //     $sheet->setCellvalue('D'.$i,'');

            // }

            $sheet->setCellvalue('E'.$i,'');
            $sheet->setCellvalue('F'.$i,'');
            
            $i++;
        }
        
        
        $file ='data_produk_stok_opname_'.date('Y-m-d').'.xlsx';
        $url_file =base_url().'gen_xlsx/'.$file;
        
        if($results->num_rows() > 0){
            $this->PhpSpreadsheetXlsx->save('gen_xlsx/'.$file);
            $this->response(array('success'=>true,'excel_url'=>$url_file),REST_Controller::HTTP_OK);
        }else{
            
            $this->response(array('success'=>false,'message'=>'Data Produk dari Unit Usaha '.$q->business_name.' Kosong'),REST_Controller::HTTP_BAD_REQUEST);
        }
           
    }

    public function stock_opname_get(){
        $idunit = (int)$this->get('idunit');
        $startdate = str_replace("T00:00:00", '', $this->get('startdate'));
        $enddate = str_replace("T00:00:00", '', $this->get('enddate'));
        $data   = $this->m_inventory->stock_opname($idunit,$startdate,$enddate,$this->get('query'));
        // echo $this->db->last_query();    

        $this->set_response(array('success'=>true,'numrows'=>count($data),'results'=>count($data),'rows'=>$data),REST_Controller::HTTP_OK);  
    }

    public function stock_opnameItems_get(){
        $idunit = $this->get('idunit');
        $stock_opname_id=$this->get('stock_opname_id');
        $product_id=$this->get('product_id');
        if($stock_opname_id){
            $data   = $this->m_inventory->stock_opname_item($idunit,$stock_opname_id,$this->get('query'));
            $this->set_response(array('success'=>true,'numrows'=>count($data),'results'=>count($data),'rows'=>$data),REST_Controller::HTTP_OK);  
        }else{
            $data   = null;
            $this->set_response(array('success'=>false,'numrows'=>count($data),'results'=>count($data),'rows'=>$data),REST_Controller::HTTP_BAD_REQUEST);
        }
        // echo $this->db->last_query();

    }

    public function save_opname_post(){
        $this->db->trans_begin();

        // $coa = json_decode($this->m_data->getIdAccount(37,$this->post('idunit'))); //piutang
        // if(isset($coa->{'success'})){
        //     if(!$coa->{'success'}){
        //         $this->set_response(array(
        //             'success'=>false,
        //             'message'=>$coa->{'message'}
        //         ), REST_Controller::HTTP_BAD_REQUEST); 
        //         return false;
        //     }
        // }

        $record_date = str_replace('T00:00:00','',$this->post('datein')); 
        $status = $this->post('status');
        $stock_opname_items = json_decode($this->post('data_grid'));
        // $coa_costsales_id = $this->post('coa_costsales_id');

        $total_adjust   = 0;
        $total_variance = 0;

        foreach ($stock_opname_items as  $items) {
            # code...
            $total_adjust +=$items->{'adjustment_stock'};
            $total_variance +=$items->{'variance'};
        }
        
        if($this->post('stock_opname_id')!=''){
            $stock_opname_id = $this->post('stock_opname_id');
        }else{
            $stock_opname_id = $this->m_data->getPrimaryID2(null,'stock_opname','stock_opname_id');
        }

        $params = array(
           'idunit' => $this->post('idunit'),
           'prefix' => '#SP',
           'table' => 'stock_opname',
           'fieldpk' => 'stock_opname_id',
           'fieldname' => 'opname_number',
           'extraparams'=> null,
        );

        $opname_number = $this->m_data->getNextNoArticle($params);

        $data_stockOpname = array(
            "stock_opname_id" =>$stock_opname_id,
            "status" =>$status,
            "userin" =>$this->user_data->user_id,
            "record_date" =>$record_date,
            // "coa_costsales_id"=>$coa_costsales_id,
            "datein"=>date('Y-m-d H:m:s'),
            "idunit"=>$this->post('idunit')
        );

        if($this->post('stock_opname_id')!=''){

            if($status==2){
                
                $opname_gets = $this->db->get_where('stock_opname',array('stock_opname_id'=>$this->post('stock_opname_id')))->row();

                foreach ($stock_opname_items as $key => $item) {
                    
                    $cek = $this->db->query("SELECT 
                                                    b.*
                                            FROM    
                                                    stock_opname_item a
                                            INNER JOIN product b ON b.product_id=a.product_id
                                            wHERE
                                                  a.stock_opname_id=".$item->{'stock_opname_id'}.' AND a.product_id='.$item->{'product_id'})->row();

                    // if($cek->coa_inventory_id==''){
                    //      $this->set_response(array('success'=>false,'message'=>'Terjadi kesalahan saat approval, produk atau barang dengan no barang : <b>'.$item->{'no_sku'}.'</b> coa akun persedian tidak boleh kosong'),REST_Controller::HTTP_BAD_REQUEST);
                    //     return false;
                    // }

                    // if($cek->buy_price=='' || $cek->{'buy_price'}==0){
                        
                    //     $this->set_response(array('success'=>false,'message'=>'Terjadi kesalahan saat approval, harga beli pada no barang : <b>'.$item->{'no_sku'}.'</b> tidak boleh 0'),REST_Controller::HTTP_BAD_REQUEST);
                    //     return false;
                    // }                                                  

                }                

                $this->update_stock_product($stock_opname_items,$this->post('idunit'));
                // $idjournal = $this->generate_journal($stock_opname_id,$opname_gets->{'opname_number'},$this->post('idunit'));
                
                // echo $idjournal;

                $data_stockOpname["approved_by"]   = $this->user_data->user_id;
                $data_stockOpname["approved_date"] = date('Y-m-d H:i:s');
                // $data_stockOpname["idjournal_stock_opname"] = $idjournal;

            }
            $data_stockOpname["usermod"]=$this->user_data->user_id;
            $data_stockOpname["datemod"]= date('Y-m-d H:m:s');
            $this->db->where('stock_opname_id',$stock_opname_id);
            $this->db->update('stock_opname',$data_stockOpname);

        }else{

            $data_stockOpname['opname_number']=$opname_number;
            $this->db->insert('stock_opname',$data_stockOpname);        
        }

        foreach ($stock_opname_items as $value) {
            # code...
            
            if($this->post('stock_opname_id')!='' && $value->{'stock_opname_id'}!=''){
                $stock_opname_item_id = $value->{'stock_opname_id'};
            }else{
                $stock_opname_item_id = $data_stockOpname['stock_opname_id'];
            }
            
            $data_stockOpname_items =array(
                'stock_opname_id' => $stock_opname_item_id,
                "product_id" =>$value->{'product_id'},
                "current_stock"=>$value->{'current_stock'},
                "adjustment_stock"=>$value->{'adjustment_stock'},
                "variance" =>$value->{'variance'},
                "notes" =>$value->{'notes'},
                "datein"=>date('Y-m-d H:m:s'),

            );

            if($value->{'stock_opname_id'}!=''){

                $data_stockOpname_items['stock_opname_id'] = $stock_opname_id;
                $this->db->where(array('stock_opname_id'=>$stock_opname_item_id,'product_id'=>$value->{'product_id'}));
                $this->db->update('stock_opname_item',$data_stockOpname_items);

            }else{
                
                $opname_item = $this->db->get_where('stock_opname_item',array('stock_opname_id'=>$stock_opname_item_id,'product_id'=>$value->{'product_id'}));
                
                if($opname_item->num_rows() > 0){
                    $this->db->where(array('stock_opname_id'=>$stock_opname_item_id,'product_id'=>$value->{'product_id'}));
                    $this->db->delete('stock_opname_item');
                }

                $this->db->insert('stock_opname_item',$data_stockOpname_items);
            }
        }
        
        if($this->db->trans_status()===FALSE){
            $this->db->trans_rollback();
            $this->set_response(array('success'=>false,'message'=>'Data stock opname gagal disimpan'),REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $this->db->trans_commit();
            $this->set_response(array('success'=>true,'message'=>'Data stock opname berhasil disimpan'),REST_Controller::HTTP_CREATED);
            
        }
    }

    public function remove_stockOpname_post(){

        $this->db->trans_begin();

        $id = json_decode($this->post('postdata'));

        foreach ($id as $v) {
            # code...
            //cek status approved
            $qcek = $this->db->get_where('stock_opname',array('stock_opname_id'=>$v,'status'=>2));
            
            if($qcek->num_rows()>0){
                $qsp_items = $this->db->query("SELECT a.adjustment_stock,a.product_id,a.variance,b.is_purchasable FROM stock_opname_item a
                                               JOIN product b on b.product_id=a.product_id
                                               wHERE a.stock_opname_id=$v")->result_array();

                foreach ($qsp_items as $key => $value) {
                    # code...
                    if($value['variance']*1 < 0){
                       $rollback_stock = $value['adjustment_stock'] - ($value['variance']); 
                    }else{

                        $rollback_stock = $value['adjustment_stock'] - $value['variance'];
                    }
                    
                    //delete stock historis
                    $this->db->where(array('product_id'=>$value['product_id'],'stock_opname_id'=>$v));
                    $this->db->delete('stock_history');

                    //rollback stock produck

                    $this->db->where('product_id',$value['product_id']);
                    $this->db->update('product',array(
                        'stock_available'=>$rollback_stock
                    ));

                    $this->db->where(array('product_id'=>$value['product_id'],'stock_opname_id'=>$v));
                    $this->db->delete('stock_opname_item');

                }
            }

            $id = $this->db->query("SELECT a.idjournal_stock_opname FROM stock_opname a WHERE a.stock_opname_id=$v")->row();
            
            if(isset($id->{'idjournal_stock_opname'})){
                $this->load->library('journal_lib');

                $this->journal_lib->delete($id->{'idjournal_stock_opname'});
            }

            $this->db->where('stock_opname_id',$v);
            $this->db->update('stock_opname',array('deleted'=>1));
        }

        if($this->db->trans_status()===FALSE){
            $this->db->trans_rollback();
            $this->set_response(array('success'=>false,'message'=>'Data stock opname gagal dihapus'),REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $this->db->trans_commit();
            $this->set_response(array('success'=>true,'message'=>'Data stock opname berhasil dihapus'),REST_Controller::HTTP_OK);
            
        }
    }

    private function update_stock_product($stock_opname_items,$idunit){

        foreach ($stock_opname_items as $value) {
            # code...

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

            $q = $this->db->get_where('product',array('product_id'=>$value->{'product_id'}))->row();

            $price = $q->buy_price;
            
            if($value->{'adjustment_stock'} > $value->{'current_stock'}){
                $type_adjustment = 4;
                $product_balance = $value->{'adjustment_stock'} * $price;
                // $this->m_inventory->journal_stock_minus();
            }

            else if($value->{'adjustment_stock'} < $value->{'current_stock'}){
                $type_adjustment = 5;
                $product_balance = $value->{'adjustment_stock'} * $price;
                // $this->m_inventory->journal_stock_plus();

            }else{
                $product_balance = $value->{'adjustment_stock'} * $price;
                // echo $product_balance;
            }

            $this->db->where('product_id',$value->{'product_id'});
            $this->db->update('product',array('stock_available'=>$value->{'adjustment_stock'},'product_balance'=>$product_balance));
            // echo $this->db->last_query();
            // die;
            if($value->{'variance'}*1 == 0){
                $type_adjustment =null;
            }else{
                $this->db->insert('stock_history',array(
                    'stock_history_id'=>$this->m_data->getPrimaryID2(null,'stock_history','stock_history_id'),
                    "product_id" =>$value->{'product_id'},
                    "type_adjustment" =>$type_adjustment,
                    "no_transaction"=>$notrx,
                    "datein" =>date('Y-m-d H:i:s'),
                    "current_qty" =>$value->{'current_stock'},
                    "trx_qty" =>$value->{'variance'},
                    "new_qty" =>$value->{'adjustment_stock'},
                    "stock_opname_id"=>$value->{'stock_opname_id'},
                ));    
            }            
            
        }
    }

    function generate_journal($stock_opname_id,$opname_number,$idunit){

        $amount = 0;
            
        $q = $this->db->query("SELECT
                                    b.idunit,
                                    A.variance,
                                    b.product_id,
                                --  C.type_adjustment,
                                    b.buy_price 
                                FROM
                                    stock_opname_item
                                    A INNER JOIN product b ON b.product_id = A.product_id
                                --  INNER JOIN stock_history C ON C.product_id = b.product_id 
                                --  AND .stock_opname_id = A.stock_opname_id 
                                WHERE
                                    A.stock_opname_id =$stock_opname_id
                                    AND b.idunit =".$idunit." 
                                GROUP BY
                                    b.product_id,
                                    A.stock_opname_id,
                                    A.variance,
                                    A.current_stock,
                                    A.adjustment_stock
                                ");
        // echo $this->db->last_query();
        foreach ($q->result() as $key => $value) {
            # code...
            $amount += $value->{'variance'}*$value->{'buy_price'};
        }

        $this->m_inventory->save_stock_opname_journal($amount,date('Y-m-d'),$stock_opname_id,$idunit,$opname_number,$this->user_data->user_id);



    }

    function product_list_get(){

        $id    = $this->get('stock_opname_id');
        $query = $this->get('query');

        $data = $this->m_inventory->get_product_list($id,$this->get('idunit'),$query);
        // echo $this->db->last_query();
        $this->set_response(array('success'=>true,'numrows'=>count($data),'results'=>count($data),'rows'=>$data),REST_Controller::HTTP_OK); 
    }

    function delete_stock_opnameItem_post(){
        $this->db->trans_begin();

        $id         = $this->post('id');
        $product_id = $this->post('product_id');

        $this->db->where(array('stock_opname_id'=>$id,'product_id'=>$product_id));
        $this->db->update('stock_opname_item',array('deleted'=>1));

        // echo $this->db->last_query();

        if($this->db->trans_status() === TRUE){
            $this->db->trans_commit();
            $this->set_response(array('success'=>true,'message'=>'deleted successfully'),REST_Controller::HTTP_OK);
        }else{
            $this->db->trans_rollback();
            $this->set_response(array('success'=>false,'message'=>'deleted failed'),REST_Controller::HTTP_BAD_REQUEST);

        }
    }

    function import_stockOpname_post(){

        $this->db->trans_begin();
        // $stock_opname_id = $this->post('stock_opname_id');
        $userid = (int) $this->user_data->user_id;
        $idunit = (int) $this->user_data->idunit;

        $datas = json_decode($this->post('data'));

        // $data_stockOpname = array(
        //     "stock_opname_id" =>$stock_opname_id,
        //     "status" =>1,
        //     "userin" =>$userid,
        //     "datein" =>date('Y-m-d H:m:s'),
        // );

        // $params = array(
        //     'idunit' => $this->user_data->idunit,
        //     'prefix' => '#SP',
        //     'table' => 'stock_opname',
        //     'fieldpk' => 'stock_opname_id',
        //     'fieldname' => 'opname_number',
        //     'extraparams'=> null,
        // );

        // $opname_number = $this->m_data->getNextNoArticle($params);
        // $data_stockOpname['opname_number']=$opname_number;
        // $this->db->insert('stock_opname',$data_stockOpname);

        foreach ($datas as $key => $value) {
            # code...
            $q  = $this->db->query("SELECT * FROM product wHERE no_sku='".$value->{'product_no'}."' and deleted=0 and idunit=$idunit");
            $rq = $q->row();
            
            if($value->{'stock_opname_id'}!=''){
                $stock_opname_id = $value->{'stock_opname_id'};
            }else{
                $stock_opname_id = $this->m_data->getPrimaryID2(null,'stock_opname','stock_opname_id');
            }

            if($value->{'variance'} != ''){

                $variance = $value->{'current_stock'}-$value->{'adjustment_stock'};
            }else{
                $variance = $value->{'variance'};
            }

            $data_opname_item=array(
                'product_id'=>$rq->product_id,
                'stock_opname_id'=>$stock_opname_id,
                'current_stock'=>$value->{'current_stock'},
                'adjustment_stock'=>$value->{'adjustment_stock'},
                'variance'=>$variance,
                "datein" =>date('Y-m-d H:m:s'),
            );

            //cek apakah data items sudah ada
            $qceks = $this->db->query("SELECT * FROM stock_opname_item where product_id=".$rq->product_id." and stock_opname_id=$stock_opname_id");
            if($qceks->num_rows() >0){
                $this->db->where('stock_opname_id',$stock_opname_id,'product_id',$rq->product_id);
                $this->db->update('stock_opname_item',$data_opname_item);
            }else{

                $this->db->insert('stock_opname_item',$data_opname_item);

            }

        }

        if($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $this->set_response(array('success'=>false,'message'=>'Import data stock opname gagal'),REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $this->db->trans_commit();
            $this->set_response(array('success'=>true,'message'=>'Import data stock opname berhasil','data'=>$data_opname_item),REST_Controller::HTTP_CREATED);
        }
    }

    function form_location_get(){
        $id  = $this->get('extraparams'); 
        $sql = $this->m_masterproductlocation->query()." where ".$this->m_masterproductlocation->whereQuery(null,$this->get('idunit'))." and $id";

        $query = $this->db->query($sql);

        $this->set_response(array('success'=>true,'data'=>$query->row()), REST_Controller::HTTP_OK);        
    }

    function delete_produck_location_post(){

        $this->db->trans_begin();
        
        $postdata = json_decode($this->post('postdata'));

        // print_r($id);

        foreach ($postdata as $id) {
            # code...
            $this->db->where('product_location_id',$id);
            $this->db->update('product_location',array('deleted'=>1));       
        }

        if($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $this->set_response(array('success'=>false,'message'=>'Hapus lokasi atau rak gagal'), REST_Controller::HTTP_BAD_REQUEST);

        }else{
            $this->db->trans_commit();
            $this->set_response(array('success'=>true,'message'=>'Hapus lokasi atau rak berhasil'), REST_Controller::HTTP_OK);

        }
    }

    function product_image_get(){

        if($this->get('product_id')==''){
            $data=[];

            $this->response(array('success'=>false,'num_rows'=>count($data),'results'=>count($data),'rows'=>$data),REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $data=$this->m_inventory->product_image($this->get('product_image_id'),$this->get('product_id'),$this->get('idunit'));

            $this->response(array('success'=>true,'num_rows'=>count($data),'results'=>count($data),'rows'=>$data),REST_Controller::HTTP_OK);    
        }     
    } 

    function save_productimage_post(){

        // $this->db

        //inisiasi variabel
        $product_image_id = $this->post('product_image_id');
        $product_id = $this->post('products_id');
        $image_fullsize = $this->post('image_fullsize');
        $imagethumbnail = $this->post('imagethumbnail');
        $image_caption = $this->post('image_caption'); 
        $order_by = $this->post('order_by');

        // echo $product_id;
        
        $config['upload_path']          = './uploads/';
        $config['allowed_types']        = 'gif|jpg|jpeg|png|bmp';
        $config['max_size']             = 1000;
        // $config['max_width']            = 1500;
        // $config['max_height']           = 900;

        $this->load->library('upload', $config);

        if ( ! $this->upload->do_upload('image_fullsize'))
        {
            // echo $this->upload->display_errors();
            $data = array('success'=>false,'message'=>$this->upload->display_errors());  
            $this->response($data, REST_Controller::HTTP_BAD_REQUEST);                      
        }
        else
        {
            $file_name = $this->upload->data()['file_name'];

            $data=array(
                "product_id" =>$product_id,
                "deleted" => 0,
                "image_thumbnail" =>$file_name,
                "image_fullsize" =>$file_name,
                "image_caption" =>$image_caption,
                "order_by" =>$order_by,
            );
            // print_r($data);
            // die;

            if($product_image_id == ''){

                $data['product_image_id'] = $this->m_data->getPrimaryID2(null,'product_image','product_image_id');
                $data["datein"] = date('Y-m-d');
                $data["userin"] = $this->user_data->user_id;
                $this->db->insert('product_image',$data);

            }else{
                
                $data['product_image_id'] = $product_image_id;
                $data["datemod"] = date('Y-m-d');
                $data["usermod"] = $this->user_data->user_id;

                $this->db->where('product_image_id',$product_image_id);
                $this->db->update('product_image',$data);
            }    

            if($this->db->trans_status()===false){
                $this->db->trans_rollback();
               
                $message = array('success'=>false,'message'=>'Gambar gagal disimpan');
                $this->set_response($message, REST_Controller::HTTP_BAD_REQUEST);
            }else{
                $this->db->trans_commit();

                $message = array('success'=>true,'message'=>'Gambar berhasil disimpan','data'=>$data);
                $this->set_response($message, REST_Controller::HTTP_OK);
            }
                        
        }
    }

    function delete_product_image_post(){

        $this->db->trans_begin();

        $arr_data = json_decode($this->post('postdata'));
        foreach ($arr_data as  $id) {
            # code...
            $this->db->where('product_image_id',$id);
            $this->db->update('product_image',array('deleted'=>1));

        }
        
        if($this->db->trans_status()===false){
            $this->db->trans_rollback();
               
            $message = array('success'=>false,'message'=> 'data gagal dihapus');
            $this->response($data, REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $this->db->trans_commit();

            $message = array('success'=>true,'message'=> 'berhasil dihapus');
            $this->response($data, REST_Controller::HTTP_OK);

        }
              
    }

    function data_transfer_get(){
        $idunit = $this->get('idunit');

        $data = $this->m_inventory->data_transfer_stock($idunit,$this->get('transfer_stock_id'),$this->get('query'),str_replace('T00:00:00', '', $this->get('startdate')),str_replace('T00:00:00', '', $this->get('enddate')));

        $arr = array('success'=>true,'num_rows'=>count($data),'results'=>count($data),'rows'=>$data);
        // echo $this->db->last_query();
        $this->response($arr, REST_Controller::HTTP_OK);
    }

    function transfer_stock_detail_get(){
        $idunit = $this->get('idunit');

        if($this->get('transfer_stock_id')!=null){
            $data = $this->m_inventory->data_stock_detail($idunit,$this->get('transfer_stock_id'),$this->get('bussiness_origin_id'),$this->get('bussiness_destination_id'));
            
            $arr = array('success'=>true,'num_rows'=>count($data),'results'=>count($data),'rows'=>$data);
            $this->response($arr, REST_Controller::HTTP_OK);
        }else{
            $data = [];
            
            $arr = array('success'=>false,'num_rows'=>count($data),'results'=>count($data),'rows'=>$data);
            $this->response($arr, REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    public function insert_trans_stock_post(){

        $this->db->trans_begin();

        $transfer_stock_id= $this->post('transfer_stock_id');
        $business_id_origin= $this->post('business_id_origin');
        $business_id_destination= $this->post('business_id_destination');
        $transfer_stock_no= $this->post('transfer_stock_no');
        $transfer_stock_notes= $this->post('transfer_stock_notes');
        $transfer_stock_date= $this->post('transfer_stock_date');
        $transfer_stock_detail= json_decode($this->post('transfer_stock_detail'));

        $data_transfer_stock = array(
            "transfer_stock_no" =>$transfer_stock_no,
            "transfer_stock_date"=> $transfer_stock_date,
            "transfer_stock_notes" =>$transfer_stock_notes,
            "deleted" => 0,
            "bussiness_origin_id" =>$business_id_origin,
            "bussiness_destination_id" =>$business_id_destination,
            "idunit"=> $this->post('idunit'),
        );    

        //insert and update transfer stok
        if($this->post('transfer_stock_id') !=null){
            
            $data_transfer_stock["datemod"] = date('Y-m-d H:m:s');
            $data_transfer_stock["usermod"] = $this->post('user_id');

            $this->db->where('transfer_stock_id',$transfer_stock_id);
            $this->db->update('transfer_stock',$data_transfer_stock);

        }else{

            $data_transfer_stock["transfer_stock_id"] = $this->m_data->getPrimaryID2(null,'transfer_stock','transfer_stock_id');
            $data_transfer_stock["datein"] = date('Y-m-d H:m:s');
            $data_transfer_stock["userin"] =  $this->post('user_id');

            $this->db->insert('transfer_stock',$data_transfer_stock);
        }

        foreach ($transfer_stock_detail as $key => $items) {

            if($this->post('transfer_stock_id') !=''){
                $transfer_stock_id=$this->post('transfer_stock_id');

            }else{
                $transfer_stock_id=$data_transfer_stock["transfer_stock_id"];
            }

            //check origin stock
            $cek_stock = $this->db->query("SELECT * FROM product WHERE no_sku='".$items->{'no_sku'}."' AND business_id=".$data_transfer_stock["bussiness_origin_id"])->row();
            
            if($cek_stock->{'stock_available'}*1 < 0){
                $this->response(array('success'=>false,'message'=>'Maaf terjadi kesalahan saat melakukan mutasi barang pada produk dengan kode barang: <b>'.$cek_stock->{'no_sku'}.'</b>/no barcode: <b>'.$cek_stock->{'no_barcode'}.'</b>'),REST_Controller::HTTP_BAD_REQUEST);
                return false;
            }

            $data_detail= array(
                                "transfer_stock_id" =>$transfer_stock_id,
                                "product_id" =>$items->{'product_id'},
                                "current_qty" =>$items->{'current_qty'},
                                "transfer_qty" =>$items->{'transfer_qty'},
                                "notes" =>$items->{'notes'},
                                "location_origin_id" =>$data_transfer_stock["bussiness_origin_id"],
                                "current_destination_id" =>$data_transfer_stock["bussiness_destination_id"],
                                // "datein" =>date{}
            );

            $cek = $this->db->get_where('transfer_stock_detail',
                                        array("transfer_stock_id" =>$transfer_stock_id,
                                            "product_id" =>$items->{'product_id'}
                                        ));

            if($cek->num_rows()>0){
                
                $this->db->where(array("transfer_stock_id" => $transfer_stock_id,"product_id" =>$items->{'product_id'}));
                $this->db->update('transfer_stock_detail',$data_detail);

            }else{

                $this->db->insert('transfer_stock_detail',$data_detail);
                
            }

            //update stock after transfer

            $cek_product = $this->db->query("SELECT * FROM product WHERE TRUE AND business_id=$business_id_destination AND no_sku='".$items->{'no_sku'}."' and no_barcode='".$items->{'no_barcode'}."'");

            if($cek_product->num_rows()>0){
                //update destination product
                $rcek_product =$cek_product->row();                   
                $new_qty = $rcek_product->{'stock_available'}+$items->{'transfer_qty'};

                $this->db->where(array("business_id" =>$data_transfer_stock["bussiness_destination_id"],"product_id" =>$rcek_product->{'product_id'}));
                $this->db->update('product',
                    array(
                        'stock_available'=>$new_qty
                ));

                $params = array(
                    'prefix' => 'STCK',
                    'fieldpk' => 'stock_history_id',
                    'fieldname' => 'no_transaction',
                    'table' => 'stock_history',
                    'idunit' => $this->post('idunit'),
                    'extraparams'=>null
                );

                 
            }else{
                $get_current_product = $this->db->query("SELECT * FROM product WHERE TRUE AND business_id=$business_id_origin AND product_id=".$items->{'product_id'})->row();
                // print_r($get_current_product);
                $new_qty = $items->{'transfer_qty'};
                
                $this->db->insert('product',
                    array(
                        'product_id'=>$this->m_data->getPrimaryID2(null,'product','product_id'),
                        'product_name' => $get_current_product->{'product_name'},
                        'inventory_class_id' =>$get_current_product->{'inventory_class_id'},
                        'no_sku' => $get_current_product->{'no_sku'} !='' ? $get_current_product->{'no_sku'} : null,
                        'no_barcode' => $get_current_product->{'no_barcode'} !='' ? $get_current_product->{'no_barcode'} : null,
                        'location_id'=>$get_current_product->{'product_location_id'}!='' ? $get_current_product->{'product_location_id'}:null,
                        'idunit'=>$this->post('idunit'),
                        'business_id'=>$business_id_destination,
                        'retail_price_member' => str_replace('.00', '', $get_current_product->{'retail_price_member'})!='' ? str_replace('.00', '', $get_current_product->{'retail_price_member'}):null,
                        'is_sellable' => $get_current_product->{'is_purchasable'}!='' ? $get_current_product->{'is_purchasable'}:null,
                        'is_purchasable' => $get_current_product->{'is_purchasable'}!='' ? $get_current_product->{'is_purchasable'}:null,
                        'is_taxable' => $get_current_product->{'is_taxable'}!='' ? $get_current_product->{'is_taxable'}:null,
                        'buy_price' => str_replace('.00', '', $get_current_product->{'buy_price'})!='' ? str_replace('.00', '', $get_current_product->{'buy_price'}):null,
                        'retail_price'=> str_replace('.00', '',$get_current_product->{'retail_price'}) !='' ? str_replace('.00', '',$get_current_product->{'retail_price'}):null,
                        'product_unit_id' => $get_current_product->{'product_unit_id'}!='' ? $get_current_product->{'product_unit_id'}:null,
                        'coa_inventory_id' => $get_current_product->{'coa_inventory_id'}!='' ? $get_current_product->{'coa_inventory_id'}:null,
                        'location_id' => $get_current_product->{'location_id'}!='' ? $get_current_product->{'location_id'}:null,
                        'is_consignment' => $get_current_product->{'is_consignment'}!='' ? $get_current_product->{'is_consignment'}:null,
                        'coa_sales_id' => $get_current_product->{'coa_sales_id'}!='' ? $get_current_product->{'coa_sales_id'}:null,
                        'coa_purchase_id' => $get_current_product->{'coa_purchase_id'}!='' ? $get_current_product->{'coa_purchase_id'}:null,
                        'product_no' => $get_current_product->{'product_no'}!='' ? $get_current_product->{'product_no'}:null,
                        'product_type_id' => $get_current_product->{'product_type_id'}!='' ? $get_current_product->{'product_type_id'}:null,
                        'idbrand' => $get_current_product->{'idbrand'}!='' ? $get_current_product->{'idbrand'}:null,
                        'product_description' => $get_current_product->{'product_description'}!='' ? $get_current_product->{'product_description'}:null,
                        'cost_price' => $get_current_product->{'cost_price'}!='' ? $get_current_product->{'cost_price'}:null,
                        'wholesale_price' =>$get_current_product->{'wholesale_price'}!='' ? $get_current_product->{'wholesale_price'}:null,
                        'weight' => $get_current_product->{'weight'}!='' ? $get_current_product->{'weight'}:null,
                        'weight_measurement_id' =>$get_current_product->{'coa_purchase_id'}!='' ? $get_current_product->{'coa_purchase_id'}:null, 
                        'product_parent_id' => $get_current_product->{'coa_purchase_id'}!='' ? $get_current_product->{'coa_purchase_id'}:null,
                        'product_image' => $get_current_product->{'product_image'}!='' ? $get_current_product->{'product_image'}:null,
                        'stock_commited' => $get_current_product->{'coa_purchase_id'}!='' ? $get_current_product->{'coa_purchase_id'}:null,
                        'stock_incoming' => $get_current_product->{'stock_commited'}!='' ? $get_current_product->{'stock_commited'}:null,
                        'stock_max_online' => $get_current_product->{'stock_max_online'}!='' ? $get_current_product->{'stock_max_online'}:null,
                        'available_on_pos' =>$get_current_product->{'available_on_pos'}!='' ? $get_current_product->{'available_on_pos'}:null, 
                        'no_supplier' =>$get_current_product->{'no_supplier'}!='' ? $get_current_product->{'no_supplier'}:null,
                        'idsupplier' => $get_current_product->{'idsupplier'}!='' ? $get_current_product->{'idsupplier'}:null,
                        'member_price' =>$get_current_product->{'member_price'}!='' ? $get_current_product->{'member_price'}:null,
                        'idinventorycat' => $get_current_product->{'idinventorycat'}!='' ? $get_current_product->{'idinventorycat'}:null,
                        'coa_tax_sales_id' => $get_current_product->{'idinventorycat'}!='' ? $get_current_product->{'idinventorycat'}:null,
                        'coa_tax_purchase_id' => $get_current_product->{'coa_tax_purchase_id'}!='' ? $get_current_product->{'coa_tax_purchase_id'}:null,
                        'consignment_base_price' =>$get_current_product->{'consignment_base_price'}!='' ? $get_current_product->{'consignment_base_price'}:null, 
                        'consignment_owner_id' => $get_current_product->{'consignment_owner_id'}!='' ? $get_current_product->{'consignment_owner_id'}:null,
                        'consignment_owner_type_id' => $get_current_product->{'coa_purchase_id'}!='' ? $get_current_product->{'coa_purchase_id'}:null,
                        'stock_available'=>$new_qty,
                        'status'=>$get_current_product->{'status'}!='' ? $get_current_product->{'status'}:null,
                        'product_balance'=>$new_qty*$get_current_product->{'buy_price'},
                ));
            }
            
            //update stock product origin
            $get_current_product = $this->db->query("SELECT * FROM product WHERE TRUE AND business_id=$business_id_origin and product_id=".$items->{'product_id'})->row();
            $new_stock = $items->{'current_qty'}-$items->{'transfer_qty'};
            $this->db->where(array("business_id" =>$data_transfer_stock["bussiness_origin_id"],"product_id" =>$get_current_product->{'product_id'}));
            
            $this->db->update('product',
                array(
                    'stock_available'=>$new_stock
            ));
        }
        
        $this->transfer_stock_historis($transfer_stock_detail,$business_id_origin,$business_id_destination,$this->post('idunit'),$transfer_stock_id);

        if($this->db->trans_status()===false){
            $this->db->trans_rollback();
            $this->set_response(array('success'=>false,'message'=>'data gagal disimpan'),REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $this->db->trans_commit();
            $this->set_response(array('success'=>true,'message'=>'data berhasil disimpan'),REST_Controller::HTTP_CREATED);

        }
    }

    function transfer_stock_post(){

        $this->db->trans_begin();

        $postdata = json_decode($this->post('postdata'));

        foreach ($postdata as $id) {
            # code...
            $this->db->where('transfer_stock_id',$id);
            $this->db->update('transfer_stock',array(
                'deleted'=>1
            ));

            $q = $this->db->query("SELECT 
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
                                WHERE transfer_stock_id=$id");

            foreach ($q->result_array() as $key => $value) {
                # code...
                // rollback stock

                //get product origin
                $q2 = $this->db->query("SELECT 
                                              * 
                                        FROM 
                                              product 
                                        WHERE 
                                              business_id=".$value['location_origin_id']." 
                                        AND no_sku='".$value['no_sku']."' 
                                        AND no_barcode='".$value['no_barcode']."'")->row();

                $origin_stock = $q2->{'stock_available'}+$value['transfer_qty'];

                $this->db->where(array('business_id'=>$q2->{'business_id'},'product_id'=>$q2->{'product_id'}));
                $this->db->update('product',array('stock_available'=>$origin_stock,'product_balance'=>$origin_stock*$q2->{'buy_price'}));

                //get product destination
                $q3 = $this->db->query("SELECT 
                                              * 
                                        FROM 
                                              product 
                                        WHERE 
                                              business_id=".$value['current_destination_id']." 
                                        AND no_sku='".$value['no_sku']."' 
                                        AND no_barcode='".$value['no_barcode']."'")->row();

                $destination_stock = $q3->{'stock_available'}-$value['transfer_qty'];

                $this->db->where(array('business_id'=>$q3->{'business_id'},'product_id'=>$q3->{'product_id'}));
                $this->db->update('product',array('stock_available'=>$destination_stock,'product_balance'=>$destination_stock*$q3->{'buy_price'}));

                //delete stock histori perproduct
                $this->db->where(array('type_adjustment'=>11,'product_id'=>$q2->{'product_id'}));
                $this->db->delete('stock_history');

                $this->db->where(array('type_adjustment'=>11,'product_id'=>$q3->{'product_id'}));
                $this->db->delete('stock_history');

            }
        }

        if($this->db->trans_status()===false){
            $this->db->trans_rollback();
            $this->set_response(array('success'=>false,'message'=>'data gagal dihapus'),REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $this->db->trans_commit();
            $this->set_response(array('success'=>true,'message'=>'data berhasil dihapus'),REST_Controller::HTTP_CREATED);

        }
    }

    function transfer_stock_items_post(){

        $this->db->trans_begin();
        
        if($this->post('id')!=null){

            $id = $this->post('id');
            $product_id = $this->post('product_id');
            $no_sku = $this->post('no_sku');
            $location_origin_id = $this->post('location_origin_id');
            $current_destination_id = $this->post('current_destination_id');

            //get detail per items
            $sql  = $this->db->get_where('transfer_stock_detail',array('transfer_stock_id'=>$id,'product_id'=>$product_id));

            if($sql->num_rows() >0 ){

                $q = $sql->row();
           
                //rollback product origin
                $q2 =$this->db->query("SELECT * FROM product WHERE business_id=$location_origin_id and no_sku ='$no_sku'")->row();
               
                $stock_origin = $q2->{'stock_available'} + $q->{'transfer_qty'};

                $this->db->where(array('business_id'=>$location_origin_id,'product_id'=>$q2->{'product_id'}));
                $this->db->update('product',array('stock_available'=>$stock_origin));

                //rollback product destination
                $q3 =$this->db->query("SELECT * FROM product WHERE business_id=$current_destination_id and no_sku ='$no_sku'")->row();

                $stock_dest = $q3->{'stock_available'} - $q->{'transfer_qty'};

                $this->db->where(array('business_id'=>$current_destination_id,'product_id'=>$q3->{'product_id'}));
                $this->db->update('product',array('stock_available'=>$stock_dest));

                //delete items
                $this->db->where(array('transfer_stock_id'=>$id,'product_id'=>$product_id));
                $this->db->delete('transfer_stock_detail');
            }else{
               // echo "string";
               $this->set_response(array('success'=>false,'message'=>'data berhasil dihapus'),REST_Controller::HTTP_BAD_REQUEST);
               return false; 
            }
            
        }   

        if($this->db->trans_status()===false){
                $this->db->trans_rollback();
                $this->set_response(array('success'=>false,'message'=>'data gagal dihapus'),REST_Controller::HTTP_BAD_REQUEST);
        }else{
                $this->db->trans_commit();
                $this->set_response(array('success'=>true,'message'=>'data berhasil dihapus'),REST_Controller::HTTP_CREATED);

        } 
    }

    function transfer_stock_historis($transfer_stock_detail,$location_origin_id,$current_destination_id,$idunit,$transfer_stock_id){

        foreach ($transfer_stock_detail as $key => $v) {
            # code...
            // print_r($transfer_stock_detail);
            $params = array(
               'idunit' => $idunit,
               'prefix' => '#SP',
               'table' => 'stock_history',
               'fieldpk' => 'stock_history_id',
               'fieldname' => 'no_transaction',
               'extraparams'=> null,
            );

            $trx_number = $this->m_data->getNextNoArticle($params);
                
            //mencari data produk asal;
            $q = $this->db->query("SELECT 
                                        A.*,b.product_name 
                                   FROM
                                        transfer_stock_detail
                                        A JOIN product b ON b.business_id = A.location_origin_id 
                                        AND b.product_id = A.product_id 
                                   WHERE
                                        (A.product_id =".$v->{'product_id'}." 
                                        AND transfer_stock_id =".$transfer_stock_id.")
                                        and 
                                        b.idunit=12")->row();
            
            //insert histori produk asal
                $data_histori_origin = array(

                      "stock_history_id"=>$this->m_data->getPrimaryID2(null,'stock_history','stock_history_id'),
                      "product_id"=>$q->{'product_id'},
                      "type_adjustment"=>11,
                      "no_transaction" =>$trx_number,
                      "datein" =>date('Y-m-d H:m:s'),
                      "current_qty" =>$v->{'current_qty'},
                      "trx_qty" =>$v->{'transfer_qty'},
                      "new_qty" =>$v->{'current_qty'}-$v->{'transfer_qty'},
                );

                $this->db->insert('stock_history',$data_histori_origin);
    
            //mencari data produk tujuan;
            $q1 = $this->db->query("SELECT 
                                        business_id,stock_available,product_id 
                                   FROM
                                        product                  
                                   WHERE
                                        business_id =".$current_destination_id." 
                                        AND no_sku ='".$v->{'no_sku'}."'
                                        and 
                                        idunit=12")->row();
            //mencari awal    
            $stock_awal = $q1->{'stock_available'}-$v->{'transfer_qty'};
            $data_histori_destination = array(

                "stock_history_id"=> $this->m_data->getPrimaryID2(null,'stock_history','stock_history_id'),
                "product_id" =>$q1->{'product_id'},
                "type_adjustment" =>11,
                "no_transaction" =>$trx_number,
                "datein"=> date('Y-m-d H:m:s'),
                "current_qty" =>$stock_awal,
                "trx_qty" =>$v->{'transfer_qty'},
                "new_qty" =>$q1->{'stock_available'},
            );

            $this->db->insert('stock_history',$data_histori_destination);
        }
        // die;
    }

    function hapus_post(){
        $this->db->trans_begin();

        $records = json_decode($this->post('postdata'));
        foreach ($records as $id) {
            $this->db->where(array(
                'product_id'=>$id
            ));
            $this->db->update('product',array(
                // 'display'=>0,
                'deleted'=>1
            ));
        }

        if($this->db->trans_status() === false){
            $this->db->trans_rollback();
            $json = array('success'=>false,'message'=>'An unknown error was occured');
        }else{
            $this->db->trans_commit();
            $json = array('success'=>true,'message'=>'The data has been deleted succsessfully');
        }
        echo json_encode($json);
    }

    function bussiness_unit_get(){

        $business_code = $this->get('business_code');

        $resp = $this->rest_client->get('business/datas?business_code='.$business_code.'&idunit=64',[
                    'auth'=>[COOP_APIKEY,''],
                    // 'form_params'=>['business_code'=>$business_code,'idunit'=>64],
                    'http_errors'=>false
                ]);
        $b = $resp->getBody();

        echo $b;
    }

    function compositions_get(){

        $data = [];
        $i    = 0;

        if($this->get('product_id') == ''){
            // echo "string";
            $this->set_response(array('success'=>false,'results'=>count($data),'num_rows'=>count($data),'rows'=>count($data)),REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }

        $q    = $this->m_inventory->product_composition_data($this->get('product_id'),$this->get('composition_type'),$this->get('query'));
        $rows = $q->result();

        foreach ($rows as $key => $value) {
            # code...
            $data[$i] =$value;
            $i++;
        }

        $this->set_response(array('success'=>true,'results'=>count($data),'num_rows'=>count($data),'rows'=>$data),REST_Controller::HTTP_OK);
    }

    function insert_product_composition($product_composition,$product_id,$userid){

        foreach ($product_composition as $key => $v) {
            # code...
            $data =array(
                "product_composition_id" =>$v->{'product_composition_id'},
                "qty"=>$v->{'qty'},
                "product_unit_id" =>$v->{'product_unit_id'},
                "composition_type" =>1,
                "notes"=>$v->{'notes'}
            );

            // print_r($data);
            
            $cek = $this->db->get_where('product_composition',array('product_id'=>$product_id,'product_composition_id'=>$v->{'product_composition_id'}));
            if($cek->num_rows() > 0){
                
                $data['product_id']=$product_id;
                $data['datemod']=date('Y-m-d H:m:s');
                $data['usermod']=$userid;
                // die;
                $this->db->where(array('product_id'=>$product_id,'product_composition_id'=>$v->{'product_composition_id'}));
                $this->db->update('product_composition',$data);
            }else{
            
                $data['product_id']=$product_id;
                $data['datein']=date('Y-m-d H:m:s');
                $data['userin']=$userid;
                $this->db->insert('product_composition',$data);
            }

            //update stock setelah di racik
            $product = $this->db->get_where('product',array('product_id'=>$v->{'product_composition_id'}))->row();

            $new_stock = $product->{'stock_available'}-$v->{'qty'};

            $this->db->where('product_id',$v->{'product_composition_id'});
            $this->db->update('product',array('stock_available'=>$new_stock));
        }
    }

    function insert_product_compositionfee($product_compositionfee,$product_id,$userid){

        foreach ($product_compositionfee as $key => $v) {
            # code...
            $data =array(
                "product_composition_id" =>$v->{'product_composition_id'},
                "qty"=>$v->{'qty'},
                "product_unit_id" =>$v->{'product_unit_id'},
                "composition_type" =>2,
                "fee_amount" =>$v->{'fee_amount'} =='' ? 0 : $v->{'fee_amount'},
                "notes"=>$v->{'notes'}
            );

            // print_r($data);
            
            $cek = $this->db->get_where('product_composition',array('product_id'=>$product_id,'product_composition_id'=>$v->{'product_composition_id'}));
            if($cek->num_rows() > 0){
                
                $data['product_id']=$product_id;
                $data['datemod']=date('Y-m-d H:m:s');
                $data['usermod']=$userid;
                // die;
                $this->db->where(array('product_id'=>$product_id,'product_composition_id'=>$v->{'product_composition_id'}));
                $this->db->update('product_composition',$data);
            }else{
            
                $data['product_id']=$product_id;
                $data['datein']=date('Y-m-d H:m:s');
                $data['userin']=$userid;
                $this->db->insert('product_composition',$data);
            }

            //update stock setelah di racik
            $product = $this->db->get_where('product',array('product_id'=>$v->{'product_composition_id'}))->row();

            $new_stock = $product->{'stock_available'}-$v->{'qty'};

            $this->db->where('product_id',$v->{'product_composition_id'});
            $this->db->update('product',array('stock_available'=>$new_stock));
        }
    }
    function hapus_composition_post(){

        $this->db->trans_begin();
        
        if($this->post('product_id')!=''){
            $product_id = $this->post('product_id');
        }else{
            $product_id = null;
        }

        $product_composition_id = $this->post('product_composition_id');

        $cek = $this->db->get_where('product_composition',array('product_id'=>$product_id,'product_composition_id'=>$product_composition_id,'composition_type'=>$this->post('composition_type')));

        if($cek->num_rows() > 0){

            //rollback stock
            $stok    = $this->db->get_where('product_composition',array('product_id'=>$product_id,'product_composition_id'=>$product_composition_id,'composition_type'=>$this->post('composition_type')))->row();
            $produk = $this->db->get_where('product',array('product_id'=>$product_composition_id))->row();
            
            $update_stock = $stok->{'qty'} + $produk->{'stock_available'};

            $this->db->where('product_id',$product_composition_id); 
            $this->db->update('product',array('stock_available'=>$update_stock)); 

            $this->db->where(array('product_id'=>$product_id,'product_composition_id'=>$product_composition_id,'composition_type'=>$this->post('composition_type')));
            $this->db->delete('product_composition');
        }else{
            $this->set_response(array('status'=>true,'message'=>'Hapus data komposisi obat berhasil'),REST_Controller::HTTP_OK);
        }

        if($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $this->set_response(array('status'=>false,'message'=>'Gagal menghapus data komposisi obat'),REST_Controller::HTTP_BAD_REQUEST);

        }else{
           $this->db->trans_commit();
           $this->set_response(array('status'=>true,'message'=>'Hapus data komposisi obat berhasil'),REST_Controller::HTTP_OK);

        }
    }

    // private function business_Unitname($business_id){

    //     $resp =$this->rest_client->get('business/datas?business_id='.$business_id,[
    //         'auth'=>[COOP_APIKEY,''],
    //         'http_errors'=>false
    //     ]);

    //     // echo $resp->getBody();
    //     $result = json_decode($resp->getBody());
    //     // print_r($result->rows);
    //     return $rows = $result->rows[0]->{'business_name'};

    // }   
}
?>