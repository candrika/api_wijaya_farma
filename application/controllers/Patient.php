<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Patient extends MY_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        $this->load->model('M_patient','pasien');
        
        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        $this->methods['users_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['users_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['users_put']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['users_delete']['limit'] = 50; // 50 requests per hour per user/key
    }

    public $obj;

    function datas_get(){

        $idunit          = $this->get('idunit');
        $patient_id      = $this->get('patient_id');
        $query           = $this->get('query');
        $id_member       = $this->get('id_member');
        $patient_type_id = $this->get('patient_type_id');
        $business_id     = $this->get('business_id');
        
        $d = $this->pasien->data($idunit,$patient_id,$query,$id_member,$patient_type_id,$business_id);
        
        $q = $d['data']->result();
        $num_rows = $d['total']->num_rows();
        $i=0;
        $d=[]; 

        foreach ($q as $key => $value) {
            # code...
            $qp = $this->db->query("SELECT 
                                            a.patient_id,a.patient_name,b.patient_name as polish_name,a.member_id,b.business_id
                                            -- ,c.business_name
                                     FROM 
                                            patient a 
                                            join patient b on a.patient_parent_id=b.patient_id 
                                            -- join business c on c.business_id=b.business_id 
                                     where 
                                            TRUE and a.patient_parent_id !=0 and a.patient_id=".$value->{'patient_id'}." ");
            // echo $this->db->last_query();
            if($qp->num_rows()>0){
                // print_r($qp->row());
                $row = $qp->row();
                $value->{'polis'} = $row->{'polish_name'};
                // $value->{'business_name'} = $row->{'business_name'};
                // echo $value->{'business_name'};
            }else{
                // echo "xxx";
                if($value->{'member_id'}!=''){
                    $value->{'polis'} = $value->{'patient_name'};
                }
            }
        
            $d[$i] = $value; 
            $i++;
        }

        // $message=array('success' =>true,'num_rows'=>count($d),'results'=>count($d),'rows'=>$d);
        $this->set_response(array('success'=>true,'numrows'=>$num_rows,'results'=>$num_rows,'rows'=>$d),REST_Controller::HTTP_OK); 
    }

    function photo_get(){

        $patient_id = $this->put('patient_id');
        $id_member  = $this->get('id_member');

        //start query
        $wer = "deleted=0";

        if($id_member!=null){
            $wer .= " and member_id=$id_member";
        }

        if($patient_id !=null){
            $wer .= " and patient_id=$patient_id";
        }

        $d = $this->db->query("SELECT patient_photo FROM patient WHERE $wer");
        $q = $d->row();
        //end

        $this->set_response(array('success'=>true,'rows'=>$q),REST_Controller::HTTP_OK);

    }

    function data_get(){

        $idunit     = $this->get('idunit');
        $patient_id = $this->get('patient_id');
        $query      = $this->get('query'); 
        $id_member  = $this->get('id_member');
        $patient_no  = $this->get('patient_no');

        $d = $this->pasien->data($idunit,$patient_id,$query,$id_member);

        $q = $d['data']->row();

        $this->set_response(array('success'=>true,'rows'=>$q),REST_Controller::HTTP_OK);
    }

    function save_patient_post(){

        $this->db->trans_begin();
       
        $patient_name = $this->post('patient_name') !='' ? $this->post('patient_name'):null;
        $status = $this->post('status') !='' ? $this->post('status'):null;
        $id_member = $this->post('member_id') !='' ? $this->post('member_id'):null;
        $patient_no = $this->post('patient_no') !='' ? $this->post('patient_no'):null;
        $patient_type_id = $this->post('patient_type_id') !='' ? $this->post('patient_type_id'):null;
        $no_id = $this->post('no_id') !='' ? $this->post('no_id'):null;
        $no_tlp = $this->post('no_tlp') !='' ? $this->post('no_tlp'):null;
        $no_mobile = $this->post('no_mobile') !='' ? $this->post('no_mobile'):null;
        $email = $this->post('email') !='' ? $this->post('email'):null; 
        $address = $this->post('address') !='' ? $this->post('address'):null;
        $birthday_date = backdate($this->post('birthday_date')) !='' ? backdate($this->post('birthday_date')):null;
        $remarks = $this->post('remarks') !='' ? $this->post('remarks'):null;
        $gender_type = $this->post('gender_type') !='' ? $this->post('gender_type'):null;
        $division= $this->post('division') !='' ? $this->post('division'):null;
        $np_number=$this->post('np_number') !='' ? $this->post('np_number'):null;
        $business_id=$this->post('business_id') !='' ? $this->post('business_id'):null;


        $data_pastient = array(
            'patient_name' => $patient_name,
            'status' => $status,
            'member_id' => $id_member,
            'patient_no' => $patient_no,
            'patient_type_id' => $patient_type_id,
            'no_id' => $no_id,
            'no_tlp' => $no_tlp,
            'no_mobile' => $no_mobile,
            'email' => $email, 
            'address' => $address,
            'country'=>'Jakarta',
            'birthday_date'=>$birthday_date,
            'remarks'=>$remarks,
            'divisi'=>$division,
            'np_number'=>$np_number,
            'gender_type'=>$gender_type,
            'business_id'=>$business_id
        );
        
        // print_r($data_pastient);

        if($this->post('patient_id') !=''){
            
            $this->db->where('patient_id',$this->post('patient_id'));
            $data_pastient['datemod'] = date('Y-m-d H:m:s');
            $data_pastient['usermod'] =  $this->post('user_id');
            $this->db->update('patient',$data_pastient);

        }else{

            $data_pastient['patient_id'] = $this->m_data->getPrimaryID2($this->post('patient_id'),'patient', 'patient_id');
            $data_pastient['datein'] = date('Y-m-d H:m:s');
            $data_pastient['userin'] =  $this->post('user_id');
            $this->db->insert('patient',$data_pastient);

        }

        if($this->db->trans_status() ===false){
            $this->db->trans_rollback();
            $this->set_response(array('success'=>false,'message'=>'Gagal saat menyimpan data pasien'),REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $this->db->trans_commit();
            $this->set_response(array('success'=>true,'message'=>'Data pasien berhasil disimpan'),REST_Controller::HTTP_OK);
        }
    }

    function remove_post(){

        $this->db->trans_begin();
        
        $id = json_decode($this->post('postdata')); 

        foreach ($id as $v) {
            
            $this->db->where('patient_id',$v);
            $this->db->update('patient',array('deleted'=>1));
        }

        if($this->db->trans_status()===false){
            $this->db->trans_rollback();
            $this->set_response(array('success'=>false,'message'=>'Gagal saat menyimpan data pasien'),REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $this->db->trans_commit();
            $this->set_response(array('success'=>false,'message'=>'Data pasien berhasil disimpan'),REST_Controller::HTTP_OK);
        }
    }

    function sync_member_get(){
        $this->db->trans_begin();
        
        $i=0;
        
        //sync data anggota
        $response = $this->rest_client->get('member/members?',[
            'auth'=>[COOP_APIKEY,''],
            'http_errors'=>false
        ]);

        $b    = json_decode($response->getBody());
        $rows = $b->rows;

        foreach ($rows as $key => $v) {
            
            $data_pastient = array(
                'patient_name' => $v->{'member_name'},
                'status' => $v->{'status'},
                'member_id' => $v->{'id_member'},
                'patient_no' => $v->{'no_member'},
                'patient_type_id' => 1,
                'no_id' => $v->{'no_id'},
                'no_tlp' => $v->{'telephone'},
                'no_mobile' =>$v->{'handphone'},
                'email' => $v->{'email'}, 
                'address' => $v->{'address'},
                'datein'=>$v->{'datein'},
                'country'=>'Jakarta',
                'birthday_date'=>$v->{'birth_date'},
                'remarks'=>$v->{'notes'},
                'divisi'=>$v->{'notes'},
                'np_number'=>$v->{'no_member'}
            );

            $data_pastient['patient_id'] = $this->m_data->getPrimaryID2(null,'patient','patient_id');
            $cross_check = $this->db->get_where('patient',array('member_id'=>$v->{'id_member'}));

            if($cross_check->num_rows() >0){

                $rcross_check = $cross_check->row();
                // print_r($rcross_check);
                if($rcross_check->{'patient_name'}!=$v->{'member_name'}){

                    $this->db->where('member_id',$rcross_check->{'member_id'});
                    $this->db->update('patient',array('patient_name'=>$v->{'member_name'}));
                }elseif ($rcross_check->{'address'}!=$v->{'address'}) {
                                
                    $this->db->where('member_id',$rcross_check->{'member_id'});
                    $this->db->update('patient',array('address'=>$v->{'address'}));
                }elseif ($rcross_check->{'no_id'}!=$v->{'no_id'}) {

                    $this->db->where('member_id',$rcross_check->{'member_id'});
                    $this->db->update('patient',array('no_id'=>$v->{'no_id'}));
                }elseif ($rcross_check->{'birthday_date'}!=$v->{'birth_date'}) {
                                
                    $this->db->where('member_id',$rcross_check->{'member_id'});
                    $this->db->update('patient',array('birthday_date'=>$v->{'birth_date'}));
                }elseif ($rcross_check->{'no_mobile'}!=$v->{'handphone'}) {

                    $this->db->where('member_id',$rcross_check->{'member_id'});
                    $this->db->update('patient',array('no_mobile'=>$v->{'handphone'}));
                }elseif ($rcross_check->{'email'}!=$v->{'email'}) {
                                
                    $this->db->where('member_id',$rcross_check->{'member_id'});
                    $this->db->update('patient',array('email'=>$v->{'email'}));
                }elseif ($rcross_check->{'divisi'}!=$v->{'notes'}) {
                                
                    $this->db->where('member_id',$rcross_check->{'member_id'});
                    $this->db->update('patient',array('divisi'=>$v->{'notes'}));
                }elseif ($rcross_check->{'remarks'}!=$v->{'notes'}) {
                                
                    $this->db->where('member_id',$rcross_check->{'member_id'});
                    $this->db->update('patient',array('remarks'=>$v->{'notes'}));
                }elseif ($rcross_check->{'business_id'}!=$v->{'business_id'}) {
                                
                    $this->db->where('member_id',$rcross_check->{'member_id'});
                    $this->db->update('patient',array('business_id'=>$v->{'business_id'}));                            
                }elseif ($rcross_check->{'np_number'}!=$v->{'no_member'}) {
                    $this->db->where('member_id',$rcross_check->{'member_id'});
                    $this->db->update('patient',array('np_number'=>$v->{'no_member'}));      
                }
                           
            }else{
                $data_pastient['patient_id'] = $this->m_data->getPrimaryID2(null,'patient','patient_id');
                $this->db->insert('patient',$data_pastient);
            }    
        }

        $response1 = $this->rest_client->get('member/member_family?',[
            'auth'=>[COOP_APIKEY,''],
            'http_errors'=>false
        ]);

        $b1    = json_decode($response1->getBody());
        $rows1 = $b1->rows;

        foreach ($rows1 as $key => $vr) {
            
            $q2 = $this->db->get_where('patient',array('member_id'=>$vr->{'member_id'}))->row();
            
            $params = array(
                'idunit' => 12,
                'prefix' => 'PSN',
                'table' => 'patient',
                'fieldpk' => 'patient_id',
                'fieldname' => 'patient_no',
                'extraparams'=> null,
            );
            
            $patient_no = $this->m_data->getNextNoArticle($params);

            $data_pastient = array(
                'patient_name' => $vr->{'family_name'},
                'status' => 2,
                'patient_no' => $patient_no,
                'patient_type_id' => 3,
                'no_id' => null,
                'no_tlp' => $vr->{'family_phone'},
                'no_mobile' =>$vr->{'family_phone'},
                'email' => null, 
                'address' => $vr->{'family_address'},
                'datein'=>date('Y-m-d H:m:s'),
                'country'=>null,
                'birthday_date'=>null,
                'remarks'=>null,
                'divisi'=>null,
                'patient_parent_id'=>$q2->{'patient_id'},
                'relationship_type'=>$q2->{'relationship_type'}
            );

            $qp1 = $this->db->query("SELECT 
                                            a.patient_id,a.patient_name,b.patient_name as member_name,a.address,
                                            a.no_mobile,a.no_tlp,a.relationship_type,a.business_id
                                     FROM 
                                            patient a,patient b
                                     where 
                                            b.patient_id<>a.patient_id and (a.patient_parent_id=b.patient_id and a.patient_parent_id !=0)");
            // echo $this->db->last_query();
            if($qp1->num_rows()>$i++){
                
                $rqp = $qp1->row();
                // print_r($rqp);
                if($rqp->{'patient_name'}!=$vr->{'family_name'}){
                    
                    $this->db->where('patient_id',$rqp->{'patient_id'});
                    $this->db->update('patient',array('patient_name'=>$vr->{'family_name'}));

                }elseif ($rqp->{'address'}!=$vr->{'family_address'}) {
                     
                    $this->db->where('patient_id',$rqp->{'patient_id'});
                    $this->db->update('patient',array('address'=>$vr->{'family_address'}));
                    
                }elseif ($rqp->{'no_tlp'}!=$vr->{'family_phone'}) {
                     
                    $this->db->where('patient_id',$rqp->{'patient_id'});
                    $this->db->update('patient',array('no_tlp'=>$vr->{'family_phone'}));

                }elseif ($rqp->{'no_mobile'}!=$vr->{'family_phone'}) {
                     
                    $this->db->where('patient_id',$rqp->{'patient_id'});
                    $this->db->update('patient',array('no_mobile'=>$vr->{'family_phone'}));     
                }elseif ($rqp->{'relationship_type'}!=$vr->{'relationship_type'}) {
                    # code...

                    $this->db->where('patient_id',$rqp->{'patient_id'});
                    $this->db->update('patient',array('relationship_type'=>$vr->{'relationship_type'})); 
                }elseif ($rqp->{'relationship_type'}=='' || $rqp->{'relationship_type'}!=$vr->{'relationship_type'}) {
                    # code...
                    $this->db->where('patient_id',$rqp->{'patient_id'});
                    $this->db->update('patient',array('relationship_type'=>$vr->{'relationship_type'}));
                }elseif ($rqp->{'business_id'}=='' || $rqp->{'business_id'}!=$vr->{'business_id'}) {
                    $this->db->where('patient_id',$rqp->{'patient_id'});
                    $this->db->update('patient',array('business_id'=>$vr->{'business_id'}));
                }else{

                    // $this->set_response(array('success'=>false,'message'=>'Tidak ada data anggota baru'),REST_Controller::HTTP_BAD_REQUEST);
                }

            }else{
                $data_pastient['patient_id'] = $this->m_data->getPrimaryID2(null,'patient','patient_id');
                $this->db->insert('patient',$data_pastient);
            }
        }
        
        if($this->db->trans_status()===false){
            $this->db->trans_rollback();
            $this->set_response(array('success'=>false,'message'=>'Failed while synchronous patient data!'),REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $this->db->trans_commit();
            $this->set_response(array('success'=>false,'message'=>'Patient data synchronous successfully.'),REST_Controller::HTTP_OK);
        }    
    }


    function summary_get(){
        
        $wer = "";

        if($this->get('startdate')!=null && $this->get('enddate')!=null){
            $wer .= " and (a.medical_record_date between '". backdate2($this->get('startdate'))." 00:00:00' and '".backdate2($this->get('enddate'))." 23:23:59')";
        }
        
        $q = $this->db->get_where('business',array('deleted'=>0));

        $data = [];
        $i=0;
        // $j=0;
        foreach ($q->result() as $key => $value) {
            
            $data[$i]['unit']     = $value->{'business_name'};

            $q_patient = $this->db->query("SELECT COALESCE
                                                (
                                                COUNT ( A.medical_record_no )) as count 
                                            FROM
                                                medical_record
                                                A JOIN patient b ON b.patient_id = A.patient_id
                                                LEFT JOIN business c on c.business_id=b.business_id
                                                WHERE b.business_id=".$value->{'business_id'}." $wer and A.deleted=0")->row();

            $data[$i]['patient_count'] = $q_patient->{'count'};

            $i++;
            // echo $this->db->last_query();

        }

        $q_umum = $this->db->query("SELECT COALESCE(NULLIF(count(a.medical_record_no),null),0) as count_umum,COALESCE(NULLIF((c.business_name),null),'Pasien Umum') as umum FROM medical_record a
                                        JOIN patient b on b.patient_id=a.patient_id 
                                        LEFT JOIN business c on c.business_id = b.business_id
                                        WHERE b.business_id is  null $wer and patient_parent_id =0 and a.deleted=0 GROUP BY c.business_name");
        // print_r($q_umum->row());
        if($q_umum->num_rows()>0){
            $r_umum = $q_umum->row();

            $data[$i]['unit']          = $r_umum->{'umum'};
            $data[$i]['patient_count'] = $r_umum->{'count_umum'};    
        }

        $q_tertanggung = $this->db->query("SELECT COALESCE(NULLIF(count(a.medical_record_no),null),0) as count_tertanggung,COALESCE(NULLIF((c.business_name),null),'Pasien Tertanggung') as tertanggung FROM medical_record a
                                        JOIN patient b on b.patient_id=a.patient_id 
                                        LEFT JOIN business c on c.business_id = b.business_id
                                        WHERE b.business_id is  null $wer and patient_parent_id !=0 and a.deleted=0  GROUP BY c.business_name");
        
        if($q_tertanggung->num_rows()>0){
            $r_tertanggung = $q_tertanggung->row();

            $data[$i+1]['unit']          = $r_tertanggung->{'tertanggung'};
            $data[$i+1]['patient_count'] = $r_tertanggung->{'count_tertanggung'};    
        }

        $this->response(array('success'=>true,'rows'=>$data),REST_Controller::HTTP_OK);
    }

    function billing_report_summary_get(){

        $q = $this->pasien->data_billing_report($this->get('startdate'),$this->get('enddate'),$this->get('business_id'),$this->get('provider'));

        $this->response(array('status'=>true,'num_rows'=>count($q),'rows'=>$q),REST_Controller::HTTP_OK);
    }

}
?>