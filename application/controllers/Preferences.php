<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Preferences extends MY_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        $this->load->model(array('preferences/m_tax','m_data'));

        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        $this->methods['users_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['users_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['users_delete']['limit'] = 50; // 50 requests per hour per user/key
    }

    public function tax_default_get(){
        $tax_data = $this->m_data->tax_config();

        // $qcek = $this->db->query("select default_tax_sales_id,default_tax_purchase_id,b.coa_ppn_sales_id,b.coa_pph23_sales_id,b.coa_ppn_purchase_id,b.coa_pph23_purchase_id,
        //                             b.nametax as nametax_sales,
        //                             b.coa_ppn_rate as ppn_rate_sales,
        //                             b.coa_pph23_rate as pph23_rate_sales,
        //                             c.nametax as nametax_purchase,
        //                             c.coa_ppn_rate as ppn_rate_purchase,
        //                             c.coa_pph23_rate as pph23_rate_purchase
        //                             from unit a
        //                             join tax b ON a.default_tax_sales_id = b.idtax
        //                             join tax c ON a.default_tax_purchase_id = c.idtax
        //                             left join account d ON b.coa_ppn_sales_id = d.idaccount and d.idunit = a.idunit
        //                             left join account e ON b.coa_pph23_sales_id = e.idaccount and e.idunit = a.idunit
        //                             where a.idunit = ".$this->user_data->idunit." and b.deleted = 0");
        if(!$tax_data['success']){
            $this->set_response($tax_data,REST_Controller::HTTP_BAD_REQUEST);
            return false;
        } else {
            $message = [
                'success' => true,
                'tax_sales_id'=>(int) $tax_data['tax_sales_id'],
                'tax_sales_name'=>$tax_data['tax_sales_name'],
                'tax_sales_rate'=>$tax_data['tax_sales_rate'],
                'tax_purchase_id'=>$tax_data['tax_purchase_id'],
                'tax_purchase_name'=>$tax_data['tax_purchase_name'],
                'tax_purchase_rate'=>$tax_data['tax_purchase_rate']
            ];
            $this->set_response($message,REST_Controller::HTTP_OK);
        }
    
    }

    public function tax_sales_put(){
        $this->db->trans_begin();
        $tax_id = $this->put('tax_id');

        $qcek = $this->db->get_where('tax',array('idtax'=>$tax_id,'deleted'=>0,'idunit'=>$this->user_data->idunit));
        if($qcek->num_rows()<=0){
            $message=array('success'=>false,'message'=>'tax id not found');
            $this->set_response($message,REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }

        $data = array(     
            'default_tax_sales_id' => $tax_id
        );
        $this->db->where('idunit',(int) $this->user_data->idunit);
        $this->db->update('unit',$data);

        if($this->db->trans_status()===false){
            $this->db->trans_rollback();
            $message=array('success'=>true,'message'=>'Pengaturan pajak gagal tersimpan. Mohon coba kembali.');
            $this->set_response($message,REST_Controller::HTTP_BAD_REQUEST);

        }else{
            $this->db->trans_commit();
            $message=array('success'=>true,'message'=>'Pengaturan pajak berhasil tersimpan');
            $this->set_response($message,REST_Controller::HTTP_CREATED);

        }
    }

    public function tax_purchase_put(){
        $this->db->trans_begin();
        $tax_id = $this->put('tax_id');

        $qcek = $this->db->get_where('tax',array('idtax'=>$tax_id,'deleted'=>0,'idunit'=>$this->user_data->idunit));
        if($qcek->num_rows()<=0){
            $message=array('success'=>false,'message'=>'tax id not found');
            $this->set_response($message,REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }

        $data = array(     
            'default_tax_purchase_id' => $tax_id
        );
        $this->db->where('idunit',(int) $this->user_data->idunit);
        $this->db->update('unit',$data);

        if($this->db->trans_status()===false){
            $this->db->trans_rollback();
            $message=array('success'=>true,'message'=>'Pengaturan pajak gagal tersimpan. Mohon coba kembali.');
            $this->set_response($message,REST_Controller::HTTP_BAD_REQUEST);

        }else{
            $this->db->trans_commit();
            $message=array('success'=>true,'message'=>'Pengaturan pajak berhasil tersimpan');
            $this->set_response($message,REST_Controller::HTTP_CREATED);

        }
    }

    public function tax_post(){

        $this->db->trans_begin();

         $data = array(            
            'idunit' => (int) $this->user_data->idunit,
            'nametax'=> $this->post('tax_name'),
            'is_tax_ppn'=> $this->post('is_tax_ppn') =='' ? NULL : $this->post('is_tax_ppn'),
            'coa_ppn_rate'=>$this->post('coa_ppn_rate') =='' ? NULL : $this->post('coa_ppn_rate'),
            'coa_ppn_sales_id'=>$this->post('coa_ppn_sales_id') =='' ? NULL : $this->post('coa_ppn_sales_id'),
            'coa_ppn_purchase_id'=>$this->post('coa_ppn_purchase_id') =='' ? NULL : $this->post('coa_ppn_purchase_id'),
            'is_tax_pph23'=>$this->post('is_tax_pph23') =='' ? NULL : $this->post('is_tax_pph23'),
            'coa_pph23_rate'=>$this->post('coa_pph23_rate') =='' ? NULL : $this->post('coa_pph23_rate'),
            'coa_pph23_sales_id'=>$this->post('coa_pph23_sales_id') =='' ? NULL : $this->post('coa_pph23_sales_id'),
            'coa_pph23_purchase_id'=>$this->post('coa_pph23_purchase_id') =='' ? NULL : $this->post('coa_pph23_purchase_id')
        );
        $this->m_tax->save_tax_config(null,$data);

        if($this->db->trans_status()===false){
            $this->db->trans_rollback();
            $message=array('success'=>true,'message'=>'Pengaturan pajak gagal tersimpan. Mohon coba kembali.');
            $this->set_response($message,REST_Controller::HTTP_BAD_REQUEST);

        }else{
            $this->db->trans_commit();
            $message=array('success'=>true,'message'=>'Pengaturan pajak berhasil tersimpan');
            $this->set_response($message,REST_Controller::HTTP_CREATED);

        }
    }

    public function tax_put(){

        $this->db->trans_begin();
        // echo $this->put('tax_id'); die;
         $data = array(            
            'idunit' => (int) $this->user_data->idunit,
            'nametax'=> $this->put('tax_name'),
            'is_tax_ppn'=> $this->put('is_tax_ppn') =='' ? NULL : $this->put('is_tax_ppn'),
            'coa_ppn_rate'=>$this->put('coa_ppn_rate') =='' ? NULL : $this->put('coa_ppn_rate'),
            'coa_ppn_sales_id'=>$this->put('coa_ppn_sales_id') =='' ? NULL : $this->put('coa_ppn_sales_id'),
            'coa_ppn_purchase_id'=>$this->put('coa_ppn_purchase_id') =='' ? NULL : $this->put('coa_ppn_purchase_id'),
            'is_tax_pph23'=>$this->put('is_tax_pph23') =='' ? NULL : $this->put('is_tax_pph23'),
            'coa_pph23_rate'=>$this->put('coa_pph23_rate') =='' ? NULL : $this->put('coa_pph23_rate'),
            'coa_pph23_sales_id'=>$this->put('coa_pph23_sales_id') =='' ? NULL : $this->put('coa_pph23_sales_id'),
            'coa_pph23_purchase_id'=>$this->put('coa_pph23_purchase_id') =='' ? NULL : $this->put('coa_pph23_purchase_id')
        );
        $this->m_tax->save_tax_config($this->put('tax_id'),$data);

        if($this->db->trans_status()===false){
            $this->db->trans_rollback();
            $message=array('success'=>true,'message'=>'Pengaturan pajak gagal tersimpan. Mohon coba kembali.');
            $this->set_response($message,REST_Controller::HTTP_BAD_REQUEST);

        }else{
            $this->db->trans_commit();
            $message=array('success'=>true,'message'=>'Pengaturan pajak berhasil tersimpan');
            $this->set_response($message,REST_Controller::HTTP_CREATED);

        }
    }

    public function tax_get(){
        $idunit = (int) $this->user_data->idunit;
        $tax_id = (int) $this->get('id');

        $q = $this->m_tax->get($tax_id,$this->get('query'));

        $num_rows = $q['total'];

        $message = [
                'success' => true,
                'numrow'=>$num_rows,
                'results'=>$num_rows,
                'rows' => $q['data']
        ];

        $this->set_response($message, REST_Controller::HTTP_OK); 
    
    }

}
?>