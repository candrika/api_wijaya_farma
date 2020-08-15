<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Member extends MY_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        $this->load->model('m_member');
        $this->load->model('m_saving');
        $this->load->model('m_business');
        $this->load->model('m_loan');
        

        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        $this->methods['users_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['users_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['users_delete']['limit'] = 50; // 50 requests per hour per user/key
    }

    public function business_get(){
        $idunit = $this->get('idunit');
        $member_id = $this->get('id_member');
        $business_id = $this->get('business_id');

        $wer = null;
        if($business_id!=null){
            $wer.=" and a.business_id = ".intval($business_id)."";
        }

        $q = $this->db->query("select a.business_id,a.business_name,a.business_desc
                                from business a
                                where a.business_id IN (select business_id
                                            from business_investor
                                            where member_id = $member_id)
                                and a.deleted = 0 and a.idunit =  $idunit $wer");
        if($q->num_rows()>0){
            $r = $q->result_array();

            $i=0;
            foreach ($r as $key => $value) {
                $data[$i] = $value;
               //get capital
                $q = $this->db->query("select sum(total_amount) as total_capital
                                        from business_investor a
                                        join member b ON a.member_id = b.id_member
                                        where b.idunit = $idunit and member_id = $member_id and a.business_id = ".$value['business_id']." ");
                if($q->num_rows()>0){
                    $r = $q->row();
                    $data[$i]['total_capital'] = intval($r->total_capital);
                } else {
                    $data[$i]['total_capital'] = 0;
                }

                //paid shu member
                $data[$i]['paid_shu_member'] = $this->m_business->shu_member_paid($idunit,$value['business_id'],$member_id);
                $i++;
            }
            

             

            $res = array('success'=>true,'data'=>$data);
            $this->response($res, REST_Controller::HTTP_OK);        
        } else {
            $data = array('success'=>true,'data'=>null);
            $this->response($data, REST_Controller::HTTP_OK);        
        }
    }

    public function upload_signature_post(){
        $id_member = $this->post('id_member');

        $config['upload_path']          = './uploads/';
        $config['allowed_types']        = 'gif|jpg|jpeg|png|bmp';
        $config['max_size']             = 1000;
        $config['max_width']            = 1500;
        $config['max_height']           = 900;

        $this->load->library('upload', $config);

        if ( ! $this->upload->do_upload('photo_signature'))
        {
            // echo $this->upload->display_errors();
            $data = array('success'=>false,'message'=>$this->upload->display_errors());  
            $this->response($data, REST_Controller::HTTP_BAD_REQUEST);                      
        }
        else
        {
                $file_name = $this->upload->data()['file_name'];

                $this->db->where('id_member',$id_member);
                $this->db->update('member',array(
                        'photo_signature'=>$file_name
                    ));

                $data = array('success'=>true,'file_name'=>$file_name);
                $this->response($data, REST_Controller::HTTP_OK);        
        }
    }

    function upload_photo($id_member){
        $config['upload_path']          = './uploads/';
        $config['allowed_types']        = 'gif|jpg|jpeg|png|bmp';
        $config['max_size']             = 1400;
        // $config['max_width']            = 900;
        // $config['max_height']           = 1500;

        $this->load->library('upload', $config);

        if ( ! $this->upload->do_upload('photo_image'))
        {
            // echo $this->upload->display_errors();
            return array('success'=>false,'message'=>$this->upload->display_errors());                
        }
        else
        {
                $file_name = $this->upload->data()['file_name'];

                $this->db->where('id_member',$id_member);
                $this->db->update('member',array(
                        'photo_image'=>$file_name
                    ));

                return array('success'=>true,'file_name'=>$file_name);
        }
    }

    public function signature_get(){
        $id_member = $this->get('id_member');

        $q = $this->db->get_where('member',array('id_member'=>$id_member));
        if($q->num_rows()>0){
            $r = $q->row();
            $d = array('success'=>true,'photo_signature'=>$r->photo_signature);
            $this->response($d, REST_Controller::HTTP_OK);        
        } else {
            $d = array('success'=>false,'message'=>'Data not found');
            $this->response($d, REST_Controller::HTTP_NOT_FOUND);  
        }        
    }

    public function identity_get(){
          $id_member = $this->get('id_member');

        $q = $this->db->get_where('member',array('id_member'=>$id_member));
        if($q->num_rows()>0){
            $r = $q->row();
            $d = array('success'=>true,'photo_identity'=>base_url().'uploads/'.$r->identity_number_image);
            $this->response($d, REST_Controller::HTTP_OK);        
        } else {
            $d = array('success'=>false,'message'=>'Data not found');
            $this->response($d, REST_Controller::HTTP_NOT_FOUND);  
        }        
    }


    public function upload_photo_post(){
        // $photo_image = $this->post('photo_image');
        $image = $this->upload_photo($this->post('id_member'));
        $this->response($image, REST_Controller::HTTP_OK);        
    }

    public function check_post(){
        $no_member = $this->post('no_member');

        if($no_member==''){
            $this->response(array('success'=>false,'message'=>'userid not found'), REST_Controller::HTTP_BAD_REQUEST);
        }

        $v = $this->m_member->check_id($no_member);
        if($v['success']){
            $this->response(array('success'=>true,'id_member'=>$v['id_member'],'message'=>'member is valid'), REST_Controller::HTTP_OK);
        } else {
            $this->response(array('success'=>false,'message'=>'member not found'), REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function member_detail_put(){
        
        $m = $this->m_member->update_member_detail($this->put());

        if($m){
            $this->response(array('success'=>true,'message'=>'member detail updated'), REST_Controller::HTTP_CREATED);
        } else {
            $this->response(array('success'=>false,'message'=>'failed to updating member detail'), REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    public function detail_get(){
        $no_member = $this->get('no_member');
        $member_id = $this->get('member_id');

        if($no_member!=''){
            $wer = " no_member = '".$no_member."' ";
        } else if($member_id!=''){
            $wer = " id_member = '".$member_id."' ";
        } else {
            $this->response(array('success'=>false,'message'=>'id member cannot be null'), REST_Controller::HTTP_BAD_REQUEST); 
        }

        $q = $this->db->query("select id_member from member where $wer and idunit = ".$this->user_data->idunit." ");
        if($q->num_rows()>0){
            $r = $q->row();
            $m = $this->m_member->profile($r->id_member);
            $this->response(array('success'=>true,'data'=>$m), REST_Controller::HTTP_OK);
        } else {
            $this->response(array('success'=>false,'message'=>'no_member not found'), REST_Controller::HTTP_NOT_FOUND); 
        }      
    }

    public function saving_get(){
        //detail data member saving
         $no_account = $this->get('no_account');

        if ($no_account==''){
            $this->response(array('success'=>false,'message'=>'no_account cannot be null'), REST_Controller::HTTP_BAD_REQUEST); 
        }

        $q = $this->db->query("select a.id_member_saving,b.idunit,b.email,b.handphone
            from member_saving a
            join member b ON a.id_member = b.id_member
            where a.no_account = '".$no_account."' ");
        


        if($q->num_rows()>0){
            $r = $q->row();
            $m = $this->m_saving->saving_detail($r->id_member_saving,$r->idunit);
            // echo $this->db->last_query(); die;
            $m['email'] = $r->email;
            $m['handphone'] = $r->handphone;
            $this->response(array('success'=>true,'data'=>$m), REST_Controller::HTTP_OK);
        } else {
            $this->response(array('success'=>false,'message'=>'no_account not found'), REST_Controller::HTTP_NOT_FOUND); 
        }      
    }

    public function saving_list_get(){       
         $no_account = $this->get('no_account');

         $no_member = $this->get('no_member');
        $member_id = $this->get('member_id');

        if($no_member!=''){
            $wer = " no_member = '".$no_member."' ";
        } else if($member_id!=''){
            $wer = " id_member = '".$member_id."' ";
        } else {
            $this->response(array('success'=>false,'message'=>'id member cannot be null'), REST_Controller::HTTP_BAD_REQUEST); 
        }

        $q = $this->db->query("select id_member from member where $wer and idunit = ".$this->user_data->idunit." and deleted = 0");
        if($q->num_rows()>0){
            $r = $q->row();
            $m = $this->m_member->saving_list($r->id_member,$no_account);
            // echo $this->db->last_query(); die;
            $this->response(array('success'=>true,'numrow'=>count($m),'results'=>count($m),'data'=>$m), REST_Controller::HTTP_OK);
        } else {
            $this->response(array('success'=>false,'message'=>'no_member not found'), REST_Controller::HTTP_NOT_FOUND); 
        }      
    }

    public function loan_get(){
        $this->load->model('m_loan');

        $id_member = $this->get('id_member');
        $id_member_loan = $this->get('id_member_loan');
        $status = $this->get('status');

        if ($id_member==''){
            $this->response(array('success'=>false,'message'=>'id_member cannot be null'), REST_Controller::HTTP_BAD_REQUEST); 
        }

        $q = $this->m_loan->data($id_member_loan,$status,$id_member,$this->user_data->idunit);
        $numrow = count($q['data']);
        $datagrid = array('success'=>true,'numrow'=>$numrow,'results'=>$numrow,'rows'=>$q['data']);
        $this->response($datagrid, REST_Controller::HTTP_OK);   
    }

     public function loan_payment_get(){
        $this->load->model('m_loan');

        $id_member_loan = $this->get('id_member_loan');
        $status = $this->get('status');

        if ($id_member_loan==''){
            $this->response(array('success'=>false,'message'=>'id_member_loan cannot be null'), REST_Controller::HTTP_BAD_REQUEST); 
        }

        $q = $this->m_loan->payment_trx(null,$status ,null,$id_member_loan);
        $numrow = count($q['data']);
        $datagrid = array('success'=>true,'numrow'=>$numrow,'results'=>$numrow,'rows'=>$q['data']);
        $this->response($datagrid, REST_Controller::HTTP_OK);   
    }

    public function loan_paid_get(){
        $this->load->model('m_loan');

        $id_member_loan = $this->get('id_member_loan');

        if ($id_member_loan==''){
            $this->response(array('success'=>false,'message'=>'id_member_loan cannot be null'), REST_Controller::HTTP_BAD_REQUEST); 
        }

        $q = $this->m_loan->payment_trx(null,null,null,$id_member_loan);
        $numrow = count($q['data']);
        $datagrid = array('success'=>true,'numrow'=>$numrow,'results'=>$numrow,'rows'=>$q['data']);
        $this->response($datagrid, REST_Controller::HTTP_OK);   
    }

    public function saving_transaction_get(){
         $no_member = $this->get('no_member');
         $no_account = $this->get('no_account');

        if ($no_member==''){
            $this->response(array('success'=>false,'message'=>'no_member cannot be null'), REST_Controller::HTTP_BAD_REQUEST); 
        }

        $q = $this->db->query("select id_member from member where no_member = '".$no_member."' and idunit = ".$this->user_data->idunit." ");
        if($q->num_rows()>0){
            $r = $q->row();
            $m = $this->m_member->saving_transaction($r->id_member,$no_account);
            $this->response(array('success'=>true,'data'=>$m), REST_Controller::HTTP_OK);
        } else {
            $this->response(array('success'=>false,'message'=>'no_member not found'), REST_Controller::HTTP_NOT_FOUND); 
        }      
    }

    public function transaction_list_get(){
         $no_member = $this->get('no_member');
         $no_account = $this->get('no_account');

        if ($no_member==''){
            $this->response(array('success'=>false,'message'=>'no_member cannot be null'), REST_Controller::HTTP_BAD_REQUEST); 
        }

        $q = $this->db->query("select id_member from member where no_member = '".$no_member."' ");
        if($q->num_rows()>0){
            $r = $q->row();
            $m = $this->m_member->transaction_list($r->id_member,$no_account);
            $this->response(array('success'=>true,'data'=>$m), REST_Controller::HTTP_OK);
        } else {
            $this->response(array('success'=>false,'message'=>'no_member not found'), REST_Controller::HTTP_NOT_FOUND); 
        }      
    }

    public function all_transaction_list_get(){
        $no_member = $this->get('no_member');
        $member_id = $this->get('member_id');

        if($no_member!=''){
            $wer = " no_member = '".$no_member."' ";
        } else if($member_id!=''){
            $wer = " id_member = '".$member_id."' ";
        } else {
            $this->response(array('success'=>false,'message'=>'id member cannot be null'), REST_Controller::HTTP_BAD_REQUEST); 
        }

         $no_account = $this->get('no_account');
         $limit = $this->get('limit');

        $q = $this->db->query("select id_member from member where $wer and idunit = '".$this->user_data->idunit."' ");
        if($q->num_rows()>0){
            $r = $q->row();
            $m = $this->m_member->all_transaction_list($r->id_member,$no_account,$limit);
            $this->response(array('success'=>true,'data'=>$m), REST_Controller::HTTP_OK);
        } else {
            $this->response(array('success'=>false,'message'=>'no_member not found'), REST_Controller::HTTP_NOT_FOUND); 
        }      
    }

     public function summary_get(){
        $idunit = (int) $this->get('idunit');

        if ($idunit <= 0){
            $this->response(array('success'=>false,'message'=>'id coop not found'), REST_Controller::HTTP_BAD_REQUEST); 
        }

        $aktif = 0;
        $pasif = 0;

        $q = $this->db->get_where('member',array('status'=>2,'idunit'=>$idunit,'deleted'=>0));
        foreach ($q->result() as $r) {
            //cek saving
            $qs = $this->db->query("select b.id_member,date_part('day',age(now(),a.datein)) as total_days
                                    from member_saving_trx a
                                    join member_saving b ON a.id_member_saving = b.id_member_saving
                                    join member c oN b.id_member = c.id_member
                                    where b.id_member = ".$r->id_member." and c.idunit = $idunit");
            if($qs->num_rows()>0){
                $r = $qs->row();
                if($r->total_days>30){
                    $pasif++;
                } else {
                    $aktif++;
                }
            } else {
                $pasif++;
            }
            
            //cek loan
        }

        // $d = $this->m_member->summary($idunit);
        $this->set_response(array(
                'aktif'=>$aktif,
                'pasif'=>$pasif
            ), REST_Controller::HTTP_OK); 

    }

    function remove_get($id){

        $this->db->trans_start(); 

        $this->db->where('id_member',$id);
        $this->db->update('member',array(
                'display'=>0
        ));

        $this->db->query("UPDATE member_saving_trx set deleted = 1
                            where id_saving_trx IN (select a.id_saving_trx
                                from member_saving_trx a
                                join member_saving b ON a.id_member_saving = b.id_member_saving
                                where b.id_member = $id) ");

        $this->db->trans_complete(); 

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();

            $this->set_response(array(
                'success'=>false,
                'message'=>'Data gagal dihapus'
            ), REST_Controller::HTTP_BAD_REQUEST); 
        } 
        else {
            $this->db->trans_commit();
            $this->set_response(array(
                'success'=>true,
                'message'=>'Data berhasil dihapus'
            ), REST_Controller::HTTP_OK); 
        }

         
    }

    function rollback_closed_member_get(){
        $this->db->trans_start(); 

        $this->load->library('journal_lib');

        $member_id = $this->get('member_id');

        $q = $this->db->get_where('member_closed',array('member_id'=>$member_id,'status'=>1));
        if($q->num_rows()>0){
            $r = $q->row();

            //rollback saving
            $qsaving = $this->db->query("select id_member_saving,balance
                                        from member_closed_saving
                                        where member_closed_id = ".$r->member_closed_id." " );
            foreach ($qsaving->result() as $rsaving) {
                
                $qsaving_closed = $this->db->query("select *
                                                    from member_saving_trx
                                                    where id_member_saving = ".$rsaving->id_member_saving." and member_closed_id = ".$r->member_closed_id." ");

                if($qsaving_closed->num_rows()>0){
                    $rsaving_closed = $qsaving_closed->row();
                    if($rsaving_closed->idjournal!=null){
                        $this->journal_lib->delete($rsaving_closed->idjournal);
                    }

                    //insert balance
                    $this->db->where('id_member_saving',$rsaving->id_member_saving);
                    $this->db->update('member_saving',
                        array(
                            'status'=>1,
                            'member_closed_id'=>null,
                            'datemod'=>date('Y-m-d H:m:s'),
                            'balance'=>$rsaving->balance
                        )
                    );
                    // print_r( array(
                    //         'status'=>1,
                    //         'member_closed_id'=>null,
                    //         'datemod'=>date('Y-m-d H:m:s'),
                    //         'balance'=>$rsaving->balance
                    //     ));

                    //delete transaction
                    $this->db->where('id_saving_trx',$rsaving_closed->id_saving_trx);
                    $this->db->delete('member_saving_trx');
                }                       
                
                // echo $this->db->last_query().' ';

            }

            // echo 'stop'; die;
            //rollbback loan
            $qloan = $this->db->query("select *
                                        from member_closed_loan
                                        where member_closed_id = ".$r->member_closed_id." ");
            if($qloan->num_rows()>0){
                $rloan = $qloan->row();

                $qloantrx = $this->db->query("select loan_trx_id
                                            from loan_transaction
                                            where id_member_loan = ".$rloan->id_member_loan." and (member_closed_id = ".$r->member_closed_id." OR payment_channel is null)
                                            order by installment_stage");
                // echo $this->db->last_query(); die;

                foreach ($qloantrx->result() as $r) {
                    // var_dump($r);
                     $this->m_loan->remove_payment_trx($r->loan_trx_id);
                }

                //recalculate loan
                $q2 = $this->db->query("select sum(due_amount) as total_unpaid
                                    from loan_transaction 
                                    where id_member_loan = ".$rloan->id_member_loan." and status = 1");
               if($q2->num_rows()>0){
                    $r2 = $q2->row();

                    if($r2->total_unpaid>0 && $r2->total_unpaid!=null){

                        $qpaid = $this->db->query("select COALESCE(sum(payment_amount),0) as total_paid
                                                            from loan_transaction 
                                                            where id_member_loan = ".$rloan->id_member_loan." and status = 2")->row();

                        //update status & balance
                        $this->db->where('id_member_loan',$rloan->id_member_loan);
                        $this->db->update('member_loan',array(
                                'status'=>4,
                                'total_unpaid'=>$r2->total_unpaid,
                                'total_paid'=>$qpaid->total_paid
                            ));
                    }
               }
            }
        }

        $this->db->where('id_member',$member_id);
        $this->db->update('member',array(
                'member_closed_id'=>null,
                'journal_closed_id'=>null,
                'status'=>2//active
            ));

        $this->db->where('member_id',$member_id);
        $this->db->update('member_closed',array(
                'datemod'=>date('Y-m-d H:m:s'),
                'status'=>3//canceled
            ));

         if($this->db->trans_status()===false){
            $this->db->trans_rollback();
            $this->response(array('success'=>false,'message'=>'Pembatalan gagal diproses'), REST_Controller::HTTP_BAD_REQUEST);
        } else {
            $this->db->trans_commit();
            $this->response(array('success'=>true,'message'=>'Pembatalan telah berhasil diproses'), REST_Controller::HTTP_OK);
        }
    }
    

    function close_post(){

        //clossing member

        $this->db->trans_start(); 

        $id_member = $this->post('id_member');
        $member_closed_id = $this->m_data->getPrimaryID2(null,'member_closed','member_closed_id');

        $data_header = array(
            "member_closed_id" => $member_closed_id,
            "member_id" => $this->post('id_member'),
            "close_date" => backdate2($this->post('close_date')),
            "memo" =>  $this->post('memo'),
            "status" => 2,
            "datein" => date('Y-m-d H:m:s'),
            "userin" => $this->user_data->user_id,
            "datemod" => date('Y-m-d H:m:s'),
            "usermod" => $this->user_data->user_id,
            "total_saving_balance" =>  clearnumberic($this->post('total_saving_balance')),
            "total_withdrawal" =>  clearnumberic($this->post('total_withdrawal')),
            "total_left_balance" =>  clearnumberic($this->post('total_left_balance')),
            "total_loan_payment" =>  clearnumberic($this->post('total_loan_payment')),
            "total_balance_to_withdraw" =>  clearnumberic($this->post('total_balance_to_withdraw'))
        );

        // $this->db->insert('member_closed',$data_header);

        //saving
        $data_saving = json_decode($this->post('data_saving'));
        foreach ($data_saving as $v) {
            if($v->{'id_member_saving'}!=''){
                 $ds = array(
                    "close_saving_id" => $this->m_data->getPrimaryID2(null,'member_closed_saving','close_saving_id'),
                    "member_closed_id" => $member_closed_id,
                    "id_member_saving" => $v->{'id_member_saving'},
                    "balance" => $v->{'balance'},
                    "withdrawal_amount" => $v->{'withdrawal_amount'},
                    "datemod" => date('Y-m-d H:m:s')
                );
                $this->db->insert('member_closed_saving',$ds);

                $this->close_saving($v->{'id_member_saving'},$v->{'withdrawal_amount'},$data_header['close_date'],$member_closed_id);
            }           
        }

        //loan
        $data_loan = json_decode($this->post('data_loan'));
        foreach ($data_loan as $v) {
            $ds = array(
                "close_loan_id" => $this->m_data->getPrimaryID2(null,'member_closed_loan','close_loan_id'),
                "member_closed_id" => $member_closed_id,
                "id_member_loan" => $v->{'id_member_loan'},
                "total_unpaid" => $v->{'total_unpaid'},
                "total_paid" => $v->{'total_unpaid'},
                "datemod" => date('Y-m-d H:m:s')
            );
            $this->db->insert('member_closed_loan',$ds);

            $this->m_loan->full_payment($v->{'id_member_loan'},$data_header['close_date'],$member_closed_id);
        }

        $this->db->where('id_member',$id_member);
        $this->db->update('member',array(
                'member_closed_id'=>$member_closed_id,
                'status'=>4//stopped
            ));

         if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();

            $this->set_response(array(
                'success'=>false,
                'message'=>'Penutupan anggota gagal'
            ), REST_Controller::HTTP_BAD_REQUEST); 
        } else {
            $this->db->trans_commit();
            $this->set_response(array(
                'success'=>true,
                'message'=>'Penutupan anggota berhasil'
            ), REST_Controller::HTTP_OK); 
        }
    }

    function test_close_post(){

        $member_closed_id = $this->m_data->getPrimaryID2(null,'member_closed','member_closed_id');

        $clossing_data_header = array(
            "member_closed_id" => $member_closed_id,
            "member_id" => $this->post('id_member'),
            "close_date" => backdate2($this->post('close_date')),
            "memo" =>  $this->post('memo'),
            "status" => 2,
            "datein" => date('Y-m-d H:m:s'),
            "userin" => $this->user_data->user_id,
            "datemod" => date('Y-m-d H:m:s'),
            "usermod" => $this->user_data->user_id,
            "total_saving_balance" =>  clearnumberic($this->post('total_saving_balance')),
            "total_withdrawal" =>  clearnumberic($this->post('total_withdrawal')),
            "total_left_balance" =>  clearnumberic($this->post('total_left_balance')),
            "total_loan_payment" =>  clearnumberic($this->post('total_loan_payment')),
            "total_balance_to_withdraw" =>  clearnumberic($this->post('total_balance_to_withdraw'))
        );

        //journal header
        $date = date('Y-m-d');
        $amount = intval(0);
        $tgl = explode("-", $date);
        $idunit = $this->user_data->idunit;
        $memo = 'Penutupan Anggota XXX';
        $userin = $this->user_data->user_id;

        $idjournal = $this->m_data->getPrimaryID2(null,'journal','idjournal');

        $header_journal = array(
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
        print_r($header_journal);
        // $this->db->insert('journal', $d);
        //end journal header

        //buald debit journal data/saving product
        $data_saving = json_decode($this->post('data_saving'));
        foreach ($data_saving as $v) {
            if($v->{'id_member_saving'}!=''){
                 $ds = array(
                    "close_saving_id" => $this->m_data->getPrimaryID2(null,'member_closed_saving','close_saving_id'),
                    "member_closed_id" => $member_closed_id,
                    "id_member_saving" => $v->{'id_member_saving'},
                    "balance" => $v->{'balance'},
                    "withdrawal_amount" => $v->{'withdrawal_amount'},
                    "datemod" => date('Y-m-d H:m:s')
                );

                $amount = $v->{'withdrawal_amount'};

                 //coa credit id from saving product
                 $qcoa = $this->db->query("select a.id_member_saving,b.saving_name,b.credit_coa,c.accname,c.accnumber
                                            from member_saving a
                                            join saving_type b oN a.id_saving_type = b.id_saving_type
                                            join account c ON b.credit_coa = c.idaccount and c.idunit = b.idunit
                                            where id_member_saving = ".$v->{'id_member_saving'});
                 if($qcoa->num_rows()>0){
                    $coa = $qcoa->row();

                    $curBalanceD = $this->m_account->getCurrBalance($coa->credit_coa, $idunit);
                    $newBalanceD = $curBalanceD - ($amount);
                    $ditem = array(
                        'idjournal' => $idjournal,
                        'idaccount' => $coa->credit_coa,
                        'debit' => ($amount),
                        'credit' => 0,
                        'lastbalance' => $curBalanceD,
                        'currbalance' => $newBalanceD
                    );
                    print_r($ditem);
                    // $this->db->insert('journalitem', $ditem);
                    // $this->m_account->saveNewBalance($coa_ar_id, $newBalanceD, $idunit,$userin);
                    // $this->m_account->saveAccountLog($idunit,$coa_ar_id,0,($amount),$date,$idjournal,$userin);
                 }
                // $this->db->insert('member_closed_saving',$ds);

                // $this->close_saving($v->{'id_member_saving'},$v->{'withdrawal_amount'},$data_header['close_date'],$member_closed_id);
            }           
        }
        //end build debit journal data/saving product

        if($clossing_data_header['total_loan_payment']>0){
            //ada pembayaran sisa pinjaman
            if($clossing_data_header['total_balance_to_withdraw']>0){
                ///ada sisa saldo buat dikembalikan/jadi tambah coa hutang
                $this->member_close_balance_plus();
            } else {
                //ga ada sisa saldo yang bisa ditarik anggota
                $this->member_close_with_loan();
            }
        } else {
            //tidak ada pinjaman
            if($clossing_data_header['total_balance_to_withdraw']>0){
                ///ada sisa saldo buat dikembalikan/jadi tambah coa hutang
                $this->member_close_balance_plus();
            }
        }


    }

    function member_close_balance_plus(){

    }

    function member_close_with_loan(){

    }

    function close_saving($id_member_saving,$withdraw_amount,$withdraw_date,$member_closed_id){
         $data = array(
            // 'id_saving_trx' => $this->post('id_saving_trx') == '' ? $this->m_data->getSeqVal('seq_saving_history') : $this->post('id_saving_trx'),
            'id_saving_trx' => $this->m_data->getPrimaryID2($this->post('id_saving_trx'),'member_saving_trx','id_saving_trx'),
            "idunit" => $this->user_data->idunit,
            "id_member_saving"=>$id_member_saving,
            "tellerid" => $this->user_data->user_id, 
            "userin" => $this->user_data->user_id, 
            // "approvedby" => $this->input->post('approvedby'),
            "amount"  => $withdraw_amount,
            "trx_type"  => 2,
            "trx_date" => $withdraw_date,
            "status" => 2,
            "datein" => date('Y-m-d H:m:s'),
            "member_closed_id"=>$member_closed_id,
            "remarks"=>'Penutupan Keanggotaan'
            // "trx_channel"=>1 //teller
            // "display" => ,
        );

        $this->db->insert('member_saving_trx',$data);

        $date_arr = explode('-', $withdraw_date);

        $detail = $this->m_saving->transaction($data['id_saving_trx']);

         //cash out
        $memo = 'Penutupan Simpanan, '.$detail->saving_name.', '.$detail->member_name;
        $params = array(
            'prefix' => 'WTHD',
            'fieldpk' => 'id_saving_trx',
            'fieldname' => 'trx_number',
            'table' => 'member_saving_trx',
            'extraparams' => 'and trx_type = 2',
            'idunit' => $this->user_data->idunit,
            'year'=>  substr($date_arr[0], 2),
            'month'=>$date_arr[1]
        );
        $noref = $this->m_data->getNextNoArticle($params);
        $idjournal = $this->m_journal->withdraw_saving($this->user_data->idunit, $detail->credit_coa, $detail->debit_coa, $memo, $noref, $withdraw_date, $withdraw_amount,0,$this->user_data->user_id);

        $this->db->where('id_saving_trx',$data['id_saving_trx']);
        $this->db->update('member_saving_trx',array(
                'idjournal'=>$idjournal,
                'trx_number'=>$noref
        ));

        //cash out
        $q = $this->db->get_where('member_saving',array('id_member_saving'=>$id_member_saving))->row();        
        $new_balance = $q->balance-$withdraw_amount;
        $this->db->where('id_member_saving',$id_member_saving);
        $this->db->update('member_saving',array(
                'balance'=>$new_balance,
                'member_closed_id'=>$member_closed_id,
                'status'=>5//closed
        ));
    }

    function closed_data_get(){
        $id_member = $this->get('id_member');
        if($id_member==''){
            $this->response(array('success'=>false,'message'=>'userid not found'), REST_Controller::HTTP_BAD_REQUEST);
        }

        $q = $this->db->get_where('member_closed',array('member_id'=>$id_member,'status'=>2));
        if($q->num_rows()>0){
            $this->response(array('success'=>true,'data'=>$q->result_array()[0]), REST_Controller::HTTP_OK);
        } else {
            $this->response(array('success'=>true,'data'=>false), REST_Controller::HTTP_OK);
        }
        
    }

    function close_loan($id_member_loan){

    }

    function change_password_put(){
        $this->db->trans_begin();

        $id_member = $this->put('id_member');
        $current_password = $this->put('current_password');
        $new_password = $this->put('new_password');
        $repeat_new_password = $this->put('repeat_new_password');

        if($current_password==''){
            $this->response(array('success'=>false,'message'=>'Mohon masukan password saat ini.'), REST_Controller::HTTP_BAD_REQUEST);
            return false;
        } else {
            $qmember = $this->db->query("select b.password,b.user_id
                                        from member a
                                        join sys_user b ON a.user_id = b.user_id
                                        where a.id_member = ".$id_member." and b.password = '".$current_password."' ");
            if($qmember->num_rows()<=0){
                 $this->response(array('success'=>false,'message'=>'Password saat ini salah.'), REST_Controller::HTTP_BAD_REQUEST);
                 return false;
            } else {
                $rmember = $qmember->row();
            }
        }

        if($new_password!=$repeat_new_password){
            $this->response(array('success'=>false,'message'=>'Password baru tidak sama.'), REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }

        $this->db->where('user_id',$rmember->user_id);
                $this->db->update('sys_user',array(
                    'password'=>$new_password,
                    'datemod'=>date('Y-m-d H:i:s')
                ));

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();

            $this->set_response(array(
                'success'=>false,
                'message'=>'Password gagal disimpan'
            ), REST_Controller::HTTP_BAD_REQUEST); 
        } 
        else {
            $this->db->trans_commit();
            $this->set_response(array(
                'success'=>true,
                'message'=>'Password berhasil diperbaharui'
            ), REST_Controller::HTTP_OK); 
        }
    }

    function profile_put(){
        $this->db->trans_begin();

        $id_member = $this->put('id_member');
        $member_name = $this->put('member_name');
        $email = str_replace(' ', '', $this->put('email'));
        $handphone = str_replace(' ', '', $this->put('handphone'));
        $birth_location = $this->put('birth_location');
        $birth_date = $this->put('birth_date');
        $address = $this->put('address');

        $data = array(
            'member_name'=>$member_name,
            'birth_location'=>$birth_location,
            'birth_date'=>$birth_date,
            'address'=>$address,
            'datemod'=> date('Y-m-d H:i:s')
        );

        //cek current data
        $qmember = $this->db->query("select idunit,user_id,no_member,no_id,handphone,email,is_staff
                                    from member
                                    where id_member = ".$id_member." ");
        if($qmember->num_rows()<=0){
             $this->response(array('success'=>false,'message'=>'Data anggota tidak ditemukan'), REST_Controller::HTTP_BAD_REQUEST);
             return false;
        }
        $rmember = $qmember->row();

        if($rmember->email!=$email){
            //ganti email, cek dulu udah ada apa belum
            $qcekemail = $this->db->query("select email
                                    from member
                                    where id_member != ".$id_member." and email = '".$email."' ");
            if($qcekemail->num_rows()>0){
                 $this->response(array('success'=>false,'message'=>'Alamat email sudah terdaftar. Mohon masukan alamat email lain.'), REST_Controller::HTTP_BAD_REQUEST);
                 return false;
            } else {
                $data['email'] = $email;

                $this->db->where('user_id',$rmember->user_id);
                $this->db->update('sys_user',array(
                    'email'=>$email,
                    'datemod'=>date('Y-m-d H:i:s')
                ));
            }
        }

        if($rmember->handphone!=$handphone){
            //ganti handphone, cek dulu udah ada apa belum
             $qcekphone = $this->db->query("select handphone
                                    from member
                                    where id_member != ".$id_member." and handphone = '".$handphone."' ");
            if($qcekphone->num_rows()>0){
                 $this->response(array('success'=>false,'message'=>'Nomor handphone sudah terdaftar. Mohon masukan nomor handphone lain.'), REST_Controller::HTTP_BAD_REQUEST);
                 return false;
            } else {
                $data['handphone'] = $handphone;

                $this->db->where('user_id',$rmember->user_id);
                $this->db->update('sys_user',array(
                    'phone'=>$handphone,
                    'datemod'=>date('Y-m-d H:i:s')
                ));
            }
        }

        $this->db->where('id_member',$id_member);
        $this->db->update('member',$data);

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
                'message'=>'Profil berhasil diperbaharui'
            ), REST_Controller::HTTP_OK); 
        }
    }

    function save_import_data_post(){
        $this->db->trans_begin();

        $data_user=[
                    "user_id"=>$this->m_data->getPrimaryID2(null,'sys_user','user_id'),
                    "username"=>$this->post('member_name'),
                    "password"=>$this->post('password'),
                    "email"=>$this->post('email'),
                    "idunit"=>$this->post('idunit'),
                    "phone"=>$this->post('handphone'),
                    "realname"=>$this->post('member_name'),
                    "api_key"=>'KPR_API'.generateRandomString(25).base64_encode(date('YmdHms'))
        ];

        $this->db->insert('sys_user',$data_user);

        $data=["id_member"=>$this->post("id_member"),
               "user_id"=>$data_user['user_id'],
               "no_member" =>$this->post('no_member'),
               "member_name"=>$this->post('member_name'),
               "address"=>$this->post('address'),
               "handphone"=>$this->post('handphone'),
               "email"=>$this->post('email'),
               "idunit"=>$this->post('idunit'),
               "status"=>2,
               "userin"=>$this->post('userin'),
               "online_access"=>1,
               "marital_status"=>1,
               "datein"=>date('Y-m-d H:m:s'),
               "business_id"=>$this->post('business_id'),

               // "userin"=>$this->user_data->user_id,
        ];
        // print_r($data);die;
        $this->db->insert('member',$data);

        if($this->db->affected_rows() >0){
           
           $this->db->trans_commit();
           // echo $this->db->insert_id();
           $this->gen_member_saving($data['id_member']);
           $this->response(array("status"=>true,"message"=>"data berhasil di import","data"=>$data),REST_Controller::HTTP_CREATED);
           
           
        }else{
            
            $this->db->trans_rollback();
            $this->response(array("status"=>true,"message"=>"data gagal di import","data"=>$data),REST_Controller::HTTP_BAD_REQUEST);
        }    

        // echo $this->db->last_query();
    }

    function gen_member_saving($id){

        //delete empty value in last row
        $cek_empty = $this->db->get_where('member',array('email'=>null,'no_member'=>null, 'id_member' =>$id));
        
        if($cek_empty->num_rows()>0){
            $rcek_empty = $cek_empty->row();
            $id_empty_reccord = $rcek_empty->id_member;

            //act to delete empty record insert
            $this->db->where('id_member',$id_empty_reccord);
            $this->db->delete('member');
        }

        $savingType = $this->db->get_where('saving_type',array('idunit'=>$this->user_data->idunit));

        foreach ($savingType->result_array() as $key => $value) {
            # code...                
            $d=array(
                    'id_member_saving' => $this->m_data->getPrimaryID2(null,'member_saving', 'id_member_saving'),
                    'id_saving_type' => $value['id_saving_type'],
                    'id_member' =>$id,
                    'date_registered' => date('Y-m-d H:m:s'),
                    'date_activated' => date('Y-m-d H:m:s'),
                    'no_account' =>rand(11111111111, 99999999999),
                    'status' => 1,
                    'userin' =>$this->user_data->user_id,
                    'datein' => date('Y-m-d H:m:s'),
            );

            $this->db->insert('member_saving',$d);
            
        }
        
    }

    function export_member_get(){

        $sheet =$this->PhpSpreadsheet->getActiveSheet();
        $sheet->setTitle('Data Anggota');
        $style_header = [ 'borderStyle' => $this->PhpSpreadsheetBorder::BORDER_MEDIUM, 'color' => [ 'rgb' => '0033cc' ] ];

        // $sheet->setCellvalue('A1','Data Anggota')->getStyle('A1')->getFont()->setBold(true)->setSize(19);
        // $sheet->getStyle('A1')->getBorders()->getBottom()->applyFromArray($style_header);

        //list
        // $sheet->setCellvalue('A6','Daftar Anggota')->getStyle('A6')->getFont()->setBold(true)->setSize(14);
        $sheet->setCellvalue('A1','No Anggota')->getStyle('A1')->getFont()->getBold(true);
        $sheet->setCellvalue('B1','Nama Lengkap')->getStyle('B1')->getFont()->getBold(true);
        $sheet->setCellvalue('C1','Alamat Email')->getStyle('C1')->getFont()->getBold(true);
        $sheet->setCellvalue('D1','No Handphone')->getStyle('D1')->getFont()->getBold(true);
        $sheet->setCellvalue('E1','Alamat')->getStyle('E1')->getFont()->getBold(true);
        $sheet->setCellvalue('F1','Password Akun')->getStyle('F1')->getFont()->getBold(true);
        
        // //header
        // $sheet->getStyle('A1')->getBorders()->getTop()->applyFromArray($style_header);
        // $sheet->getStyle('B1')->getBorders()->getTop()->applyFromArray($style_header);
        // $sheet->getStyle('C1')->getBorders()->getTop()->applyFromArray($style_header);
        // $sheet->getStyle('D1')->getBorders()->getTop()->applyFromArray($style_header);
        // $sheet->getStyle('E1')->getBorders()->getTop()->applyFromArray($style_header);
        // $sheet->getStyle('F1')->getBorders()->getTop()->applyFromArray($style_header);

        // $id =json_decode($this->get('id'));
        $i=2;
        // $data=[];
        // foreach ($id as $v) {
        //     # code...
            $q    = $this->db->query("select 
                                  a.no_member,a.member_name,a.email,a.handphone,a.address,b.password 
                                  from member a
                                  inner join sys_user b ON b.user_id=a.user_id and b.idunit=a.idunit
                                  where b.idunit=".$this->get('idunit')." and a.deleted=0 and a.display is null and status=2");

            // echo $this->db->last_query();
            
            if($q->num_rows()>0){
                $d = $q->result_array();
                foreach ($d as $key => $v) {
                    $sheet->setCellvalue('A'.$i,$v['no_member']);
                    $sheet->setCellvalue('B'.$i,$v['member_name']);
                    $sheet->setCellvalue('C'.$i,$v['email']);
                    $sheet->setCellvalue('D'.$i,$v['handphone']);
                    $sheet->setCellvalue('E'.$i,$v['address']);
                    $sheet->setCellvalue('F'.$i,'');
                    
                    $i++;
                }             
            }
            
        // }
        
        $file ='data_anggota.xlsx';
        $file_url=base_url().'gen_xlsx/'.$file;
        $this->PhpSpreadsheetXlsx->save('gen_xlsx/'.$file); 

        $this->response(array('success'=>true,'excel_url'=>$file_url,'data'=>$d), REST_Controller::HTTP_OK);
    }

    function delete_member_post(){

        $this->db->trans_begin();

        $id = json_decode($this->post('id_member'));
        
        //get member data
        foreach ($id as $key => $v) {
            # code...
            $member = $this->db->get_where('member',array('id_member'=>$v))->row();
            // print_r($member->user_id);

            //sys_user
           
            $this->db->where(array('user_id'=>$member->user_id,'idunit'=>$this->user_data->idunit));
            $this->db->delete('sys_user');
            
            //get id saving type    
            $saving_type = $this->db->get_where('saving_type',array('idunit'=>$this->user_data->idunit))->result_array();   
           
            //delete member saving
            foreach ($saving_type as $key => $x) {
                # code...
                  $this->db->where(array('id_saving_type'=>$x['id_saving_type'],'id_member'=>$v));
                  $this->db->update('member_saving',array(
                    'deleted'=>1,
                    // 'display'=>1

                  ));
                  
                  // echo $this->db->last_query();
            }
            
            // delete display
            $this->db->where('id_member',$v);
            $this->db->update('member',array(
                'display'=>0,
                'deleted'=>1
            ));
            //cek member saving trx
            $qcek = $this->db->query("SELECT a.* FROM member_saving_trx a
                                      inner join member_saving b on a.id_member_saving=b.id_member_saving
                                      inner join saving_type c on b.id_saving_type=c.id_saving_type
                                      inner join member d on b.id_member=d.id_member
                                      where a.idunit=".$this->user_data->idunit." and d.id_member=$v");
            //delete member saving trx
            if($qcek->num_rows() > 0){
                $this->db->query("UPDATE member_saving_trx set deleted = 1
                            where id_saving_trx IN (select a.id_saving_trx
                                from member_saving_trx a
                                join member_saving b ON a.id_member_saving = b.id_member_saving
                                where b.id_member = $v) ");
            }
             

            // echo $this->db->last_query();
            
        }

        if($this->db->trans_status() ===false){

            $this->db->trans_rollback();
            $this->response(array("status"=>false,"message"=>"data gagal di dihapus"),REST_Controller::HTTP_BAD_REQUEST);
           
        }else{

           $this->db->trans_commit();
           $this->response(array("status"=>true,"message"=>"data berhasil di hapus"),REST_Controller::HTTP_CREATED);
      
        }   
    }


    function base64ToImage($base64_string, $output_file) {
        $file = fopen($output_file, "wb");

        $data = explode(',', $base64_string);

        fwrite($file, base64_decode($data[1]));
        fclose($file);

        return $output_file;
    }

    public function enrollment_post(){
        //receive member enrollment
        $this->db->trans_begin();

        $unit_id = $this->post('unit_id');
        $email = $this->post('email');
        $handphone = $this->post('handphone');
        $fullname = $this->post('fullname');
        $address = $this->post('address');
        $birth_date = $this->post('birth_date');
        $password = $this->post('password');
        $password_confirm = $this->post('password_confirm');
        $identity_file_b64 = $this->post('identity_file_b64');

        $q = $this->db->get_where('sys_group',array('idunit'=>$unit_id,'member_group'=>1))->row();
        $group_id = $q->group_id;

        if($unit_id==''){
            $this->set_response(array(
                'success'=>false,
                'message'=>'Unit ID cannot be null'
            ), REST_Controller::HTTP_BAD_REQUEST); 
            return false;
        }

        //validate email
         $q = $this->db->get_where('member',array('deleted'=>0,'email'=>$email));
        if($q->num_rows()>0){
            $this->response(array("success"=>false,"message"=>'alamat email '.$email.' sudah terdaftar'),REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }
        //end validate email

        //validate email
         $q = $this->db->get_where('sys_user',array('deleted'=>0,'phone'=>$handphone));
        if($q->num_rows()>0){
            $this->response(array("success"=>false,"message"=>'Nomor handphone '.$handphone.' sudah terdaftar'),REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }
        //end validate email

        // $member_password = rand(111111,999999);
        if($password==''){
            $this->response(array("success"=>false,"message"=>"Password tidak boleh kosong"),REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }

        if($password!=$password_confirm){
            $this->response(array("success"=>false,"message"=>"Password tidak sama"),REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }

        $member_password = $password;

        //create user
        $user_id = $this->m_data->getPrimaryID2(null,'sys_user','user_id');

        $data_user = array(
                'user_id' => $user_id,
                'email' => $email,
                'phone' => $handphone,
                'idunit'  => $unit_id
        );
        $data_user['group_id'] = $group_id;
        $data_user['password'] = $member_password;
        $data_user['api_key'] = 'KDI_API'.generateRandomString(25).base64_encode(date('YmdHms'));
        $this->db->insert('sys_user',$data_user);

        // $identity_file = generateRandomString(25).'.jpg';
        $identity_file = date('YmsHms').'_'.$user_id.'.jpg';
        $this->base64ToImage($identity_file_b64, './uploads/'.$identity_file);

        $data = array(
            'id_member' => $this->m_data->getPrimaryID(NULL,'member','id_member') ,
            'idunit' =>$unit_id,
            'member_name' => $fullname,
            'address' => $address,
            'handphone' => $handphone,
            'email' => $email,
            'birth_date' => $birth_date,
            'is_staff' => 2,
            'online_access' => 1,
            'status' => 1,
            'user_id'=>$user_id,
            'datein'=>date('Y-m-d H:m:s'),
            'identity_number_image'=>$identity_file
        );
        $this->db->insert('member',$data);

        if($this->db->trans_status() ===false){
            $this->db->trans_rollback();
            $this->response(array("success"=>false,"message"=>"Terjadi kesalahan. Mohon hubungi tim Kodi.id x"),REST_Controller::HTTP_BAD_REQUEST);
           
        } else {
           $this->db->trans_commit();
           $this->response(array("success"=>true,"message"=>"Terima kasih, pengajuan anggota telah berhasil terkirim dan sedang dalam moderasi oleh pengurus Koperasi. Informasi login telah dikirim ke email yang didaftarkan."),REST_Controller::HTTP_CREATED);
      
        } 
    }

    function new_member_approval_get(){
        $d = $this->m_member->memberPending_list($this->get('idunit'),$this->get('id_member'),$this->get('query'),$this->get('status'));
        // echo $this->db->last_query();
        $this->response(array('status'=>true,'num_row'=>count($d),'results'=>count($d),'rows'=>$d),REST_Controller::HTTP_OK);
    }

    function saving_member_list_get(){
        $d = $this->m_member->saving_member_detail($this->get('idunit'),$this->get('id_member'));
        // echo $this->db->last_query();
        $this->response(array('status'=>true,'num_row'=>count($d),'results'=>count($d),'rows'=>$d),REST_Controller::HTTP_OK);
    }

    function approval_member_post(){

        $this->db->trans_begin();
        
        //get name unit/ name coop
        $qunit = $this->db->get_where('unit',array('idunit'=>$this->user_data->idunit))->row();

        $status=$this->post('Status');
        $id_member=$this->post('id_member');
        $user_id=$this->post('user_id');

        if($status==1){
            $notes=$this->post('notes');

        }elseif ($status==3) {
            $notes=$this->post('notes_rejection');
        }else{
            $notes='';
        }
        
        $savingTrx=json_decode($this->post('saving_grid'));
        
        //cek member aktif
        $act_member = $this->db->query("SELECT a.* FROM member a 
                                                WHERE a.idunit=".$this->user_data->idunit." 
                                                AND a.status=2 and a.deleted=0 and a.display is null and id_member= $id_member");
    
        //cek payment status
        if($status==2){
            $cek=$this->db->query("select b.saving_name,d.amount from member_saving a 
                                             join saving_type b on b.id_saving_type=a.id_saving_type
                                             join member c on c.id_member=a.id_member 
                                             join member_saving_trx d on d.id_member_saving=a.id_member_saving
                                             where a.opening_saving_mandatory =1 
                                             and c.idunit=".$this->user_data->idunit." 
                                             and c.id_member=$id_member and d.status= 1
                                             and a.deleted=0
                                             GROUP BY c.id_member,b.id_saving_type,d.amount");
            if($cek->num_rows()>0){
                $this->set_response(array('status'=>false,'message'=>'Anggota belum dapat disetujui karena belum membayar simpanan yang wajib dibayarkan pertama kali'),REST_Controller::HTTP_BAD_REQUEST);
                return false;
            }
        }

        //cek reject status
        $cek_reject_status = $this->db->get_where('member',array('status' =>3,'id_member'=>$id_member));
    
        if($cek_reject_status->num_rows() >0){
            $rcek_reject_status =$cek_reject_status->row();
            $name = $rcek_reject_status->member_name;

            $this->set_response(array('status'=>false,'message'=>'Maaf, tidak bisa mengubah status pangejuan anggota atas nama '.$name.' dikarenakan pengajuan tersebut telah ditolak,'),REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }

        //get member info 
        $q  = $this->db->get_where('member',array('id_member'=>$id_member,'deleted'=>0,'display'=>null))->row();
        $to_email = $q->email;
        $to_name  = $q->member_name;

        //set email sander
        $from_email = "noreply@kodi.id";
        $from_name  = "KODI";
        // echo $this->db->last_query();
            
        if($status==2){
            $date_activated = date('Y-m-d');
            $no_anggota     = rand(11111111,99999999);
        }else{
            $date_activated = null;
            $no_anggota     = null;
        }

        //update status
        $this->db->where('id_member',$id_member);
        $this->db->update('member',array('status'=>$status,'notes'=>$notes,'activated_date'=>$date_activated,'no_member'=>$no_anggota));

       //saving member TRX
        foreach ($savingTrx as $v) {
            
            if($v->{'id_member_saving'}==''){
                $id_member_saving=$this->m_data->getPrimaryID2($v->{'id_member_saving'},'member_saving','id_member_saving');
            }else{
                $id_member_saving=$v->{'id_member_saving'};
            }

            if($v->{'id_member_saving'}==''){
                $dataSavingMember = array(
                    'id_member_saving'=>$this->m_data->getPrimaryID2($v->{'id_member_saving'},'member_saving','id_member_saving'),
                    'id_saving_type'=>$v->{'id_saving_type'},
                    'id_member'=>$v->{'id_member'},
                    'date_registered' => date('Y-m-d H:m:s'),
                    'userin'=>$this->user_data->user_id,
                    'datein'=>date('Y-m-d H:m:s'),
                    'status' => 1,
                    'opening_saving_mandatory'=>$v->{'opening_saving_mandatory'},
                    'no_account' =>rand(11111111111, 99999999999)   
                );
                
                $this->db->insert('member_saving',$dataSavingMember);

            }

            if($v->{'id_saving_trx'}==''){
                $id_saving_trx=$this->m_data->getPrimaryID2($v->{'id_saving_trx'},'member_saving_trx','id_saving_trx');
            }else{
                $id_saving_trx=$v->{'id_saving_trx'};
            }   
            
            $data = array(
              "idunit" => $this->user_data->idunit,
              'id_saving_trx' => $id_saving_trx,  
              "id_member_saving"=>$id_member_saving,
              "amount"  => cleardot2($v->{'amount'}),
              "trx_type"  => 1, //cash in
              "status" => $v->{'status'},
              "trx_channel"=>1,
              "trx_time_type" => 1
            );

            if($v->{'id_saving_trx'}==''){
                $this->db->insert('member_saving_trx',$data);

            }else{
                $this->db->where('id_saving_trx',$v->{'id_saving_trx'});
                $this->db->update('member_saving_trx',$data);

            }
            
            // print_r($dataSavingMember);
            // print_r($data);
            // die;              
        }
        
        //get saving must pay
        $saving_must_pay = $this->db->query("select b.saving_name,d.amount from member_saving a 
                                             join saving_type b on b.id_saving_type=a.id_saving_type
                                             join member c on c.id_member=a.id_member 
                                             join member_saving_trx d on d.id_member_saving=a.id_member_saving
                                             where a.opening_saving_mandatory =1 and c.idunit=".$this->user_data->idunit." 
                                             and c.id_member=$id_member 
                                             and a.deleted =0
                                             GROUP BY c.id_member,b.id_saving_type,d.amount");
            
        
        if($saving_must_pay->num_rows() >0){
            
            if($act_member->num_rows() > 0){
                $this->set_response(array('status'=>false,'message'=>'Maaf anggota status sudah aktif tidak bisa dirubah'),REST_Controller::HTTP_BAD_REQUEST);
                return false;
            }

            if($status==1){

                $content = "
                            Halo $to_name,<br><br>
                            Terima kasih telah melakukan pendaftaran sebagai anggota koperasi di ".$qunit->namaunit.".<br>
                            Untuk langkah berikutnya, mohon untuk melakukan pembayaran sebagai berikut:
                            <br><br><br>";
                               
                foreach ($savingTrx as $pay) {
                    # code...
                    if($pay->{'opening_saving_mandatory'}==1){
                        $content .="<b>".$pay->{'saving_name'}."</b>&nbsp;Sebesar&nbsp;<b>Rp.&nbsp;".number_format($pay->{'amount'})."</b><br>";                
                    }
                }

                $content .= "<br><br>
                             Instruksi Pembayaran:<br>  
                             $notes
                             <br><br><br>
                             Terima Kasih,<br>
                             Salam Revolusi Koperasi!";
               
            }

        }
        
        if($status==2){
            //get username and pass member
            $q_user  = $this->db->query("SELECT a.email,a.password,b.no_member 
                                         FROM sys_user a
                                         join member b on b.user_id=a.user_id
                                         where b.id_member=$id_member")->row();

            $content = "<br>
                        Halo $to_name
                        <br><br>Terima kasih telah melakukan pendaftaran sebagai anggota koperasi di ".$qunit->namaunit."
                        <br><br>Anda sekarang telah resmi terdaftar sebagai anggota 
                        <br>".$qunit->namaunit." dan kamu dapat login sebagai anggota koperasi di <a href='http://app.kodi.id'>http://app.kodi.id</a> atau dengan aplikasi Kodi Mobile 
                        <br> yang bisa Kamu unduh di: https://play.google.com/store/apps/details?id=com.kodi_sni.app<br><br>
                        berikut username dan password anda:<br>
                        <b>Username :&nbsp;".$q_user->email."</b>
                        <br>
                        <b>Password :&nbsp;".$q_user->password."</b>
                        <br>
                        Silahkan gunakan username dan password login sebagai anggota
                        <br><br><br>
                        Terima Kasih,<br>
                        Salam Revolusi Koperasi!
                        ";
        }

        if($status==3){
            $content = "<br>Halo $to_name<br><br>
                        Terima kasih telah melakukan pendaftaran sebagai anggota Koperasi di ".$qunit->namaunit.".<br><br>
                        Mohon maaf, pengajuan anda belum dapat disetujui karena:&nbsp;<b>$notes</b>.<br><br>
                        Terima Kasih,<br>
                        Salam Revolusi Koperasi!";

        }

        //init subject
        $subject="Pendaftaran Anggota Baru ".$qunit->namaunit;
     
        $sending_email =send_mail_approval($from_email,$from_name,$to_email,$to_name,$content,$subject);
        
        // echo $sending_email;

        $res = json_decode($sending_email);
       
        if($this->db->affected_rows() >0){
            $this->db->trans_commit();
            $this->set_response(array('status'=>true,'message'=>'Data pengajuan anggota berhasil disimpan'),REST_Controller::HTTP_CREATED);
        }else{
            $this->db->trans_rollback();
            $this->set_response(array('status'=>true,'message'=>'Data pengajuan anggota gagal disimpan'),REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    public function register_step1_post(){
        //receive member enrollment
        $this->db->trans_begin();

        $unit_id = $this->post('unit_id');
        $email = $this->post('email');
        $handphone = $this->post('handphone');
        $fullname = $this->post('fullname');
        $address = $this->post('address');
        $birth_date = $this->post('birth_date');
        $zipcode = $this->post('zipcode');
        $password = $this->post('password');
        $password_confirm = $this->post('password_confirm');

         if($unit_id==''){
            $this->set_response(array(
                'success'=>false,
                'message'=>'Unit ID cannot be null'
            ), REST_Controller::HTTP_BAD_REQUEST); 
            return false;
        }

        $q = $this->db->get_where('sys_group',array('idunit'=>$unit_id,'member_group'=>1))->row();
        // echo $this->db->last_query(); die;
        $group_id = $q->group_id;

       

        if($zipcode==''){
            $this->set_response(array(
                'success'=>false,
                'message'=>'Kode POS alamat tinggal tidak boleh kosong'
            ), REST_Controller::HTTP_BAD_REQUEST); 
            return false;
        }

        if($handphone==''){
            $this->set_response(array(
                'success'=>false,
                'message'=>'Nomor handphone tidak boleh kosong'
            ), REST_Controller::HTTP_BAD_REQUEST); 
            return false;
        }

        //validate email
         $q = $this->db->get_where('member',array('deleted'=>0,'email'=>$email));
        if($q->num_rows()>0){
            $this->response(array("success"=>false,"message"=>'alamat email '.$email.' sudah terdaftar'),REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }
        //end validate email

        //validate email
         $q = $this->db->get_where('sys_user',array('deleted'=>0,'phone'=>$handphone));
        if($q->num_rows()>0){
            $this->response(array("success"=>false,"message"=>'Nomor handphone '.$handphone.' sudah terdaftar'),REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }
        //end validate email

        // $member_password = rand(111111,999999);
        if($password==''){
            $this->response(array("success"=>false,"message"=>"Password tidak boleh kosong"),REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }

        if($password!=$password_confirm){
            $this->response(array("success"=>false,"message"=>"Password tidak sama"),REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }

        $member_password = $password;

        //create user
        $user_id = $this->m_data->getPrimaryID2(null,'sys_user','user_id');

        $data_user = array(
                'user_id' => $user_id,
                'email' => $email,
                'phone' => $handphone,
                'idunit'  => $unit_id
        );
        $data_user['group_id'] = $group_id;
        $data_user['password'] = $member_password;
        $data_user['api_key'] = 'KDI_API'.generateRandomString(25).base64_encode(date('YmdHms'));
        $this->db->insert('sys_user',$data_user);

        // $identity_file = generateRandomString(25).'.jpg';
        // $this->base64ToImage($identity_file_b64, './uploads/'.$identity_file);

        $data = array(
            'id_member' => $this->m_data->getPrimaryID(NULL,'member','id_member') ,
            'idunit' =>$unit_id,
            'member_name' => $fullname,
            'address' => $address,
            'handphone' => $handphone,
            'email' => $email,
            'birth_date' => $birth_date,
            'is_staff' => 2,
            'online_access' => 1,
            'status' => 1,
            'user_id'=>$user_id,
            'datein'=>date('Y-m-d H:m:s')
            // 'identity_number_image'=>$identity_file
        );
        $this->db->insert('member',$data);

        if($this->db->trans_status() ===false){
            $this->db->trans_rollback();
            $this->response(array("success"=>false,"message"=>"Terjadi kesalahan. Mohon hubungi tim Kodi.id x"),REST_Controller::HTTP_BAD_REQUEST);
           
        } else {
           $this->db->trans_commit();
           $this->response(array("success"=>true,"message"=>"Data profile berhasil terdaftar","member_id"=>intval($data['id_member'])),REST_Controller::HTTP_CREATED);
      
        } 
    }

    public function register_step2_post(){
        //receive ektp
        $this->db->trans_begin();

        $member_id = $this->post('member_id');
        $identity_file_b64 = $this->post('identity_file_b64');

        if($identity_file_b64==''){
            $this->response(array("success"=>false,"message"=>'identity_file cannot be null'),REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }

        if($member_id==''){
            $this->response(array("success"=>false,"message"=>'member_id cannot be null'),REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }

        //validate member_id
         $q = $this->db->get_where('member',array('deleted'=>0,'id_member'=>$member_id));
        if($q->num_rows()<=0){
            $this->response(array("success"=>false,"message"=>'member_id cannot be found'),REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }
        //end validate member_id


        // $identity_file = generateRandomString(25).'.jpg';
        $identity_file = date('YmsHms').'_'.$member_id.'.jpg';
        $this->base64ToImage($identity_file_b64, './uploads/'.$identity_file);

        $data = array(
            'identity_number_image'=>$identity_file
        );
        $this->db->where('id_member',$member_id);
        $this->db->update('member',$data);

        if($this->db->trans_status() ===false){
            $this->db->trans_rollback();
            $this->response(array("success"=>false,"message"=>"Terjadi kesalahan. Mohon hubungi tim Kodi.id x"),REST_Controller::HTTP_BAD_REQUEST);
           
        } else {
           $this->db->trans_commit();
           $this->response(array("success"=>true,"message"=>"Berkas e-ktp berhasil diunggah","member_id"=>intval($member_id)),REST_Controller::HTTP_CREATED);
      
        } 
    }

    public function register_step3_post(){
        //upload selfie
        $this->db->trans_begin();

        $member_id = $this->post('member_id');
        $photo_file_b64 = $this->post('photo_file_b64');

        if($photo_file_b64==''){
            $this->response(array("success"=>false,"message"=>'photo_file_b64 cannot be null'),REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }

        if($member_id==''){
            $this->response(array("success"=>false,"message"=>'member_id cannot be null'),REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }

        //validate member_id
         $q = $this->db->get_where('member',array('deleted'=>0,'id_member'=>$member_id));
        if($q->num_rows()<=0){
            $this->response(array("success"=>false,"message"=>'member_id cannot be found'),REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }
        //end validate member_id


        $file_name = date('YmsHms').'_'.$member_id.'.jpg';
        $this->base64ToImage($photo_file_b64, './uploads/'.$file_name);

        $data = array(
            'photo_image'=>$file_name
        );
        $this->db->where('id_member',$member_id);
        $this->db->update('member',$data);

        if($this->db->trans_status() ===false){
            $this->db->trans_rollback();
            $this->response(array("success"=>false,"message"=>"Terjadi kesalahan. Mohon hubungi tim Kodi.id x"),REST_Controller::HTTP_BAD_REQUEST);
           
        } else {
           $this->db->trans_commit();
           $this->response(array("success"=>true,"message"=>"Berkas foto diri berhasil diunggah","member_id"=>intval($member_id)),REST_Controller::HTTP_CREATED);
      
        } 
    }

    public function register_step4_post(){
        //upload kk
        $this->db->trans_begin();

        $member_id = $this->post('member_id');
        $familycard_file_b64 = $this->post('familycard_file_b64');

        if($familycard_file_b64==''){
            // $this->response(array("success"=>false,"message"=>'familycard_file_b64 cannot be null'),REST_Controller::HTTP_BAD_REQUEST);
            // return false;
            $familycard_file_b64 = null;
        }

        if($member_id==''){
            $this->response(array("success"=>false,"message"=>'member_id cannot be null'),REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }

        //validate member_id
         $q = $this->db->get_where('member',array('deleted'=>0,'id_member'=>$member_id));
        if($q->num_rows()<=0){
            $this->response(array("success"=>false,"message"=>'member_id cannot be found'),REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }
        //end validate member_id

        if($familycard_file_b64==null){
            $file_name = null;
        } else {
            $file_name = date('YmsHms').'_'.$member_id.'.jpg';
            $this->base64ToImage($familycard_file_b64, './uploads/'.$file_name);
        }
       

        $data = array(
            'familycard_image'=>$file_name
        );
        $this->db->where('id_member',$member_id);
        $this->db->update('member',$data);

        $simpok = $this->create_simpok_new_member($member_id);
        if(!$simpok['success']){
            $this->db->trans_rollback();
            $this->response(array("success"=>false,"message"=>$simpok['message']),REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }

        $url_payment = $simpok['url_payment'];

        if($this->db->trans_status() ===false){
            $this->db->trans_rollback();
            $this->response(array("success"=>false,"message"=>"Terjadi kesalahan. Mohon hubungi tim Kodi.id x"),REST_Controller::HTTP_BAD_REQUEST);
           
        } else {
           $this->db->trans_commit();

           if($file_name==null){
            $message = "Silahkan lanjut ke proses berikutnya";
           } else {
            $message = "Berkas kartu keluarga berhasil diunggah";
           }

           $this->response(array(
                "success"=>true,
                "message"=>$message,
                "content"=>"Untuk menjadi Anggota Aktif, Anda akan dikenakan biaya sebesar Rp. ".number_format($simpok['amount']).". Klik tombol Selanjutnya untuk melakukan pembayaran",
                "url_payment"=>$simpok['url_payment'],
                "member_id"=>intval($member_id)),
           REST_Controller::HTTP_CREATED);
      
        } 
    }

    function create_simpok_new_member($member_id){
        //get simpok
        $q = $this->db->query("select b.saving_name,b.setoran_tetap,b.id_saving_type,b.idunit
                            from member a
                            join saving_type b ON b.idunit = a.idunit
                            where a.id_member = ".$member_id." and b.saving_type = 1");
        if($q->num_rows()>0){
            $r = $q->row();

            $cek = $this->db->get_where('member_saving',array(
                'id_saving_type' => $r->id_saving_type,
                'id_member' => $member_id,
                'deleted'=>0
            ));
            if($cek->num_rows()>0){
                $rcek = $cek->row();

                //cek dulu transaksi savingya udah ada apa belum
                $cek_saving_trx = $this->db->get_where('member_saving_trx',array(
                    'id_member_saving' => $rcek->id_member_saving,
                    'deleted'=>0
                ));
                if($cek_saving_trx->num_rows()>0){
                    $value = array('success' => false, 'message' => 'Pengajuan anggota sudah dilakukan');
                    return $value;
                } else {
                    //create trx saving
                    $ret = $this->create_trx_simpok_new_member($rcek->id_member_saving,$member_id,$r->setoran_tetap,$r->idunit);
                    return $ret;
                }
                
            } else {

                $data = array(
                    'id_member_saving' => $this->m_data->getPrimaryID2(null,'member_saving', 'id_member_saving'),
                    'id_saving_type' => $r->id_saving_type,
                    'id_member' => $member_id,
                    'date_registered' => date('Y-m-d H:m:s'),
                    // 'no_account' => $no_account,
                    'startdate' => date('Y-m-d'),
                    'amount' => $r->setoran_tetap,
                    'status' => 3 //pending
                );

                $va['data'] = array(
                        // 'bank_code'=>'BNI',
                        // 'name'=>'',
                        'expiration_date'=>'2099-'.date('m-d'),
                        // 'id_va'=>rand(11111,99999),
                        'account_number'=>rand(11111,99999)
                    );

                // $data['bank_code'] = $va['data']['bank_code'];
                // $data['description'] = $va['data']['name'];
                $data['expiration_date'] = $va['data']['expiration_date'];
                // $data['id_va'] = $va['data']['id_va'];
                $data['no_account'] = $data['id_member_saving'].$va['data']['account_number'];

                $this->db->insert('member_saving',$data);

                if($this->db->affected_rows()>0){
                    $ret = $this->create_trx_simpok_new_member($data['id_member_saving'],$member_id,$data['amount'],$r->idunit);
                    return $ret;
                } else {
                    $value = array('success' => false, 'message' => 'Pengajuan anggota gagal dilakukan. ERR_CODE:NREG02');
                    return $value;
                }
            }
        }
    }

    function create_trx_simpok_new_member($id_member_saving,$member_id,$amount,$idunit){
         $data = array(
              "idunit" => $idunit,
              "id_saving_trx" => $this->m_data->getPrimaryID2(null,'member_saving_trx','id_saving_trx'),  
              "id_member_saving"=>$id_member_saving,
              "id_member_dest"=>1,
              "trx_destination"=>1,
              "amount"  => $amount,
              "fee_adm" => 0,
              "trx_type"  => 1, //cash in
              "remarks" => 'New member registration fee',
              "trx_date" => date('Y-m-d H:m:s'),
              "status" => 1, //pending
              "trx_channel"=>1,
              "trx_time_type" => 1
        );

        $external_id = 'MRG_'.$member_id.'_'.$data['id_saving_trx'];
        $data['external_id_nusafin'] = $external_id;

        $this->db->insert('member_saving_trx',$data);
        if($this->db->affected_rows()>0){
            $ret = $this->create_invoice_new_member($member_id,$data['amount'],$data['id_saving_trx'],$data['external_id_nusafin']);
            return $ret;
        } else {
            $value = array('success' => false, 'message' => 'Pengajuan anggota gagal dilakukan. ERR_CODE:NREG03');
            return $value;
        }
    }

    function get_nusafin_key($member_id){
         $qmember = $this->db->query("select b.saving_name,b.setoran_tetap,b.id_saving_type,c.namaunit,a.email,a.member_name,a.idunit
                                        from member a
                                        join saving_type b ON b.idunit = a.idunit
                                        join unit c ON b.idunit = c.idunit
                                        where a.id_member = ".$member_id." and b.saving_type = 1")->row();

        $nusafin_key = $this->m_data->get_nusafin_key($qmember->idunit);

        if(!$nusafin_key){
            $value = array('success' => false, 'message' => 'Pengajuan anggota gagal dilakukan. ERR_CODE:NREG05.');
            return $value;
        } else {
            $api_key = ENVIRONMENT == 'development' ? $nusafin_key['api_key_dev'] : $nusafin_key['api_key_live'];
        }

        return $api_key;
    }

    function create_invoice_new_member($member_id,$amount,$id_saving_trx,$external_id){
        

        $qmember = $this->db->query("select b.saving_name,b.setoran_tetap,b.id_saving_type,c.namaunit,a.email,a.member_name,a.idunit
                                        from member a
                                        join saving_type b ON b.idunit = a.idunit
                                        join unit c ON b.idunit = c.idunit
                                        where a.id_member = ".$member_id." and b.saving_type = 1")->row();

        $memo = 'Biaya pendaftaran anggota baru '.$qmember->namaunit.' atas nama '.$qmember->member_name;

        $api_key = $this->get_nusafin_key($member_id);

        $response = $this->rest_client_nusafin->request('POST', 'customer/create_invoice2',[
            'auth' => [$api_key,''],
            'form_params' => [
                'external_id' => $external_id,
                'amount' => $amount,
                'invoice_date' => date('Y-m-d'),
                'due_date' => date('Y-m-d'),
                'memo' => $memo,
                'email' => $qmember->email,
                'fullname' => $qmember->member_name,
                'send_mail' => 1
            ],
            'http_errors' => false
        ]);
        $code = $response->getStatusCode();
        $body = json_decode($response->getBody());
        // var_dump($body);
        if($code==200){
            

            // $this->db->where('id_member',$member_id);
            // $this->db->update('member',array(
            //     'external_id_nusafin'=>$external_id
            // ));

            // print_r($body);
            $value = array('success' => true, 'message' => 'Payment request created successfully','url_payment'=>$body->{'url_invoice'}.'?embed=yes','amount'=>$amount);
            return $value;
        } else {
            // var_dump($response);
            $value = array('success' => false, 'message' => 'Pengajuan anggota gagal dilakukan. ERR_CODE:NREG04. '.$body->message);
            return $value;
        }
    }

    function registration_check_get(){
        $member_id = $this->get('member_id');

        if($member_id==''){
            $this->response(array("success"=>false,"message"=>'member_id cannot be null'),REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }

        // $q = $this->db->get_where('member',array('id_member'=>$member_id));
        $q = $this->db->query("select a.* ,b.id_member_saving,c.amount,c.external_id_nusafin
                                from member a
                                left join member_saving b ON a.id_member = b.id_member
                                left join member_saving_trx c ON b.id_member_saving = c.id_member_saving and c.idunit = a.idunit
                                where a.id_member = ".$member_id."");
                                // echo $this->db->last_query(); die;
        if($q->num_rows()>0){
            $r = $q->row();

            $completed = true;
            $step = 6; //finished

            //cek ektp - step 2
            if($r->identity_number_image==null || $r->identity_number_image==''){
                $completed = false;
                $step = 2;
                $this->response(array("success"=>$completed,"step"=>$step,"message"=>"Pendaftaran belum selesai"),REST_Controller::HTTP_OK); 
                return false;
            }

            //cek selfi - step 3
            if($r->photo_image==null || $r->photo_image==''){
                $completed = false;
                $step = 3;
                 $this->response(array("success"=>$completed,"step"=>$step,"message"=>"Pendaftaran belum selesai"),REST_Controller::HTTP_OK); 
                return false;
            }

            //cek kk - step 4
            if($r->familycard_image==null || $r->familycard_image==''){
                $completed = false;
                
                if($r->external_id_nusafin!=null || $r->external_id_nusafin!=''){
                    //udah ada invoice dari nusafin / skip upload kk
                    $api_key = $this->get_nusafin_key($member_id);

                    $response = $this->rest_client_nusafin->request('GET', 'invoice/status?external_id='.$r->external_id_nusafin,[
                        'auth' => [$api_key,''],
                        'http_errors' => false
                    ]);
                    $code = $response->getStatusCode();
                    // echo $code; die;
                    $body = json_decode($response->getBody());
                    //  print_r($body); die;
                    if($code==200){
                        if($body->{'data'}->{'status'}!=='PAID'){
                            $completed = false;
                            $step = 5;
                            $this->response(array("success"=>$completed,"step"=>$step,"message"=>"Maaf, Akun anda belum aktif. Mohon untuk melakukan pembayaran biaya pendaftaran terlebih dahulu.","url_payment"=>$body->{'data'}->{'payment_url'}."?embed=yes","due_amount"=>intval($body->{'data'}->{'total_amount'})),REST_Controller::HTTP_OK); 
                            return false;
                        } else {
                            $completed = true;
                        }
                    } else {
                        $completed = false;
                        $step = 5;
                         $this->response(array("success"=>$completed,"step"=>$step,"message"=>"Pendaftaran belum selesai (2)"),REST_Controller::HTTP_OK); 
                        return false;
                    }
                } else {
                    $step = 4;
                     $this->response(array("success"=>$completed,"step"=>$step,"message"=>"Pendaftaran belum selesaix ".$r->external_id_nusafin),REST_Controller::HTTP_OK); 
                }
                
                return false;
            }

            if($r->external_id_nusafin==null || $r->external_id_nusafin==''){
                $completed = false;
                $step = 4;
                 $this->response(array("success"=>$completed,"step"=>$step,"message"=>"Pendaftaran belum selesai"),REST_Controller::HTTP_OK); 
                return false;
            } else {
                //cek bayar - step 5
                $api_key = $this->get_nusafin_key($member_id);
                // echo $r->external_id_nusafin; die;
                // $response = $this->rest_client_nusafin->request('GET', 'invoice/status?external_id=1089',[
                    // echo $r->external_id_nusafin; die;
                $response = $this->rest_client_nusafin->request('GET', 'invoice/status?external_id='.$r->external_id_nusafin,[
                    'auth' => [$api_key,''],
                    'http_errors' => false
                ]);
                $code = $response->getStatusCode();
                // echo $code; die;
                $body = json_decode($response->getBody());
                //  print_r($body); die;
                if($code==200){
                    if($body->{'data'}->{'status'}!=='PAID'){
                        $completed = false;
                        $step = 5;
                        $this->response(array("success"=>$completed,"step"=>$step,"message"=>"Maaf, Akun anda belum aktif. Mohon untuk melakukan pembayaran biaya pendaftaran terlebih dahulu.","url_payment"=>$body->{'data'}->{'payment_url'}."?embed=yes","due_amount"=>intval($body->{'data'}->{'total_amount'})),REST_Controller::HTTP_OK); 
                        return false;
                    } else {
                        $completed = true;
                    }
                } else {
                    $completed = false;
                    $step = 5;
                     $this->response(array("success"=>$completed,"step"=>$step,"message"=>"Pendaftaran belum selesai (2)"),REST_Controller::HTTP_OK); 
                    return false;
                }
            }

            

            if($completed){
                $this->response(array("success"=>$completed,"step"=>$step,"message"=>"Pendaftaran sudah selesai"),REST_Controller::HTTP_OK); 
            }
            
            return false;
        } else {
            $this->response(array("success"=>false,"message"=>'member_id cannot be found'),REST_Controller::HTTP_BAD_REQUEST);
            return false;
        }    
    }

    function member_familly_get(){

        $member_id = $this->get('member_id');

        if($member_id!=null){
            $data = $this->m_member->data_member_familly($member_id,$this->user_data->idunit,$this->get('query'));

            $this->set_response(array("success"=>true,"num_rows"=>count($data),"results"=>count($data),"rows"=>$data),REST_Controller::HTTP_OK);
        }else{
            $data = [];
            
            $this->set_response(array("success"=>false,"num_rows"=>count($data),"results"=>count($data),"rows"=>$data),REST_Controller::HTTP_BAD_REQUEST);
        }
        
    }

    public function save_member_familly_post()
    {
        $this->db->trans_begin();

        $data_member_familly = array(
            "member_id" => $this->post("member_id"),
            "family_name" => $this->post("family_name"),
            "family_address" => $this->post("family_address"),
            "family_phone" => $this->post("family_phone"),
            "relationship_type" => $this->post("relationship_type"),
            "deleted" => 0,
        );

        if($this->post('member_family_id') != ''){

            $data_member_familly["datemod"] = date('Y-m-d H:m:s');
            $data_member_familly["usermod"] = $this->user_data->user_id;

            $this->db->where("member_family_id",$this->post('member_family_id'));
            $this->db->update("member_family",$data_member_familly);

        }else{
            
            $data_member_familly["datein"] = date('Y-m-d H:m:s');
            $data_member_familly["userin"] = $this->user_data->user_id;
            
            $data_member_familly["member_family_id"] = $this->m_data->getPrimaryID2(null,'member_family','member_family_id');
            // $data_member_familly["relationship_type"] = ;

            $this->db->insert("member_family",$data_member_familly);
        }

        if($this->db->affected_rows() >0 ){
            $this->db->trans_commit();
            $json = array(
                'status'=>true,
                'message'=>'Data keluarga berhasil disimpan'  
            );
            $this->response($json,REST_Controller::HTTP_OK);
        }else{
            $this->db->trans_rollback();           
            $json = array(
                'status'=>false,
                'message'=>'Data keluarga gagal disimpan'  
            );
            $this->response($json,REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    function remove_member_familly_post(){

        $this->db->trans_begin();        

        $id = $this->post('id');

        $this->db->where('member_family_id',$id);
        $this->db->update('member_family',array(
            'deleted'=>1
        ));

        if($this->db->affected_rows() >0 ){
            $this->db->trans_commit();
            $json = array(
                'status'=>true,
                'message'=>'Hapus data keluarga berhasil'  
            );

            $this->response($json,REST_Controller::HTTP_OK);

        }else{
            $this->db->trans_rollback();           
            $json = array(
                'status'=>false,
                'message'=>'Hapus data keluarga gagal'  
            );

            $this->response($json,REST_Controller::HTTP_BAD_REQUEST);
        }

    }

    function update_relation_familly_put(){

        $this->db->trans_begin();        

        $relationship_type=$this->put('relationship_type');
        $member_family_id=$this->put('member_family_id');

        $this->db->where('member_family_id',$member_family_id);
        $this->db->update('member_family',array(
            'relationship_type'=>$relationship_type
        ));

        if($this->db->affected_rows() >0 ){
            $this->db->trans_commit();
            $json = array(
                'status'=>true,
                'message'=>'Hapus data keluarga berhasil'  
            );

            $this->response($json,REST_Controller::HTTP_OK);

        }else{
            $this->db->trans_rollback();           
            $json = array(
                'status'=>false,
                'message'=>'Hapus data keluarga gagal'  
            );

            $this->response($json,REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    function update_plafon_post(){
        $this->db->trans_begin();

        $this->db->where('id_member',$this->post('id_member'));
        $this->db->update('member',array('max_installment_quota'=>cleardot2($this->post('max_installment_quota'))));

        if($this->db->trans_status() === false){
            $this->db->trans_rollback();
            $this->set_response(array('success'=>false,'message'=>'Gagal melakukan update plafon'),REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $this->db->trans_commit();
            $this->set_response(['success'=>true,'message'=>'Update plafon berhasil'],REST_Controller::HTTP_OK);
        }

        // echo $this->db->last_query();
    }

    function loan_summary_get(){

        $idunit = $this->user_data->idunit;
        $member_id =$this->get('mid');
        if($idunit==''){
              $this->set_response(array(
                'success'=>false,
                'message'=>'idunit not found'
            ), REST_Controller::HTTP_BAD_REQUEST); 
        }

        $d = $this->m_loan->loan_summary($idunit,$member_id);
        // echo $this->db->last_query();
        if($d['total_angsuran_berjalan']==0){
            $total_angsuran = $d['total_angsuran'];
        }else{
            $total_angsuran = $d['total_angsuran_berjalan'];

        }

        $rows =array(
            'plafon_angsuran_maksimal'=>number_format($d['max_installment_quota']),
            'total_angsuran'=>number_format($total_angsuran),
            'jumlah_pinjaman_berjalan'=>number_format($d['jumlah_pinjaman_berjalan']),
            'sisa_plafon'=>number_format($d['max_installment_quota']-$d['total_angsuran']),   
        );

        $data = array(
            'success'=>true,
            'data'=>$rows
        );

        $this->set_response($data,REST_Controller::HTTP_OK);
    }

    function forgot_password_post(){
        $userid = $this->post('userid');

        if($userid==''){
            $this->response(array('success'=>false,'message'=>'User ID cannot be null'), REST_Controller::HTTP_BAD_REQUEST);
        }

        $qcek = $this->db->query("select no_member,member_name,email,user_id
                                    from member
                                    where email = '".strtolower($userid)."' and deleted = 0");
        if($qcek->num_rows()>0){
            $r = $qcek->row();

            $token = generateRandomString();
            // $content = "request new password";
            $data_fp = array(
                'member_name'=>$r->member_name,
                'url'=>FRONT_END_URL.'member/create_password?pid='.$token
            );
            $content = $this->load->view('mail/user_forgot_pass_mail',$data_fp,TRUE);

            //insert log
            $date = new DateTime();
            $date->modify('+1 day');

            $this->db->insert('forgot_pass',array(
                'forgot_id'=>$this->m_data->getPrimaryID2(null,'forgot_pass','forgot_id'),
                'user_id'=>$r->user_id,
                'token'=>$token,
                'expired_date'=>$date->format('Y-m-d H:m:s'),
                'datein'=>date('Y-m-d H:m:s')
            ));

            $response = $this->rest_nusamail->request('POST', 'mail/send',[
                // 'auth' => [NUSAFIN_API_KEY,''],
                'form_params' => [
                    // 'email' => $this->session->userdata('member_data')
                    'from_mail' => 'noreply@kodi.id',
                    'from_name' => 'Koperasi Digital',
                    'subject' => 'Permintaan Password KODI Baru',
                    'to_mail' => $r->email,
                    'to_name' => $r->member_name,
                    'content' => $content,
                    'api_key_id' => 5
                ],
                'http_errors' => true
            ]);
            $code = $response->getStatusCode();
            if($code==200){
                $body = json_decode($response->getBody());
                if($body->success){
                    $this->response(array('success'=>true,'message'=>'Intruksi pemulihan Password akun anggota Anda sudah dikirimkan melalui Email terdaftar. Terima Kasih'), REST_Controller::HTTP_OK);
                } else {
                    $this->response(array('success'=>false,'message'=>'Gagal memproses. Mohon ulangi lagi beberapa saat lagi. (1)'), REST_Controller::HTTP_BAD_REQUEST);
                }
                // print_r($body);
                 
            } else {
                $this->response(array('success'=>false,'message'=>'Gagal memproses. Mohon ulangi lagi beberapa saat lagi. (2)'), REST_Controller::HTTP_BAD_REQUEST);
            }

           
        } else {
            $this->response(array('success'=>false,'message'=>'Email tidak terdaftar'), REST_Controller::HTTP_BAD_REQUEST);
        }
        
    }

    function forgot_password_mail()  {
        $this->load->view('user_forgot_pass_mail');
    }
}
?>
