<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Setup extends MY_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        $this->load->model('m_loan');

        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        $this->methods['users_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['users_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['users_delete']['limit'] = 50; // 50 requests per hour per user/key
    }

    function wizard_post(){
        $data = $this->post();

        if(count($data)<=0){
             $this->response(array('success'=>false,'message'=>'belum ada data'), REST_Controller::HTTP_BAD_REQUEST);
        }

        $insert = array(
            "user_id" => $data['userid'],
            "datein" => date('Y-m-d H:m:s'),
            "realname" => isset($data['realname']) ? $data['realname'] : null,
            "address" => isset($data['address']) ? $data['address'] : null,
            "email" => isset($data['email']) ? $data['email'] : null,
            "handphone" => isset($data['handphone']) ? $data['handphone'] : null,
            "coop_name" => isset($data['coop_name']) ? $data['coop_name'] : null,
            "coop_year" => isset($data['coop_year']) ? $data['coop_year'] : null,
            "coop_num_member_id" => isset($data['coop_num_member_id']) ? $data['coop_num_member_id'] : null,
            "coop_nolicence" => isset($data['coop_nolicence']) ? $data['coop_nolicence'] : null,
            "coop_address" => isset($data['coop_address']) ? $data['coop_address'] : null,
            "coop_telp" => isset($data['coop_telp']) ? $data['coop_telp'] : null,
            "coop_type_kons" => isset($data['coop_type_kons']) ? $data['coop_type_kons'] : null,
            "coop_type_simpin" => isset($data['coop_type_simpin']) ? $data['coop_type_simpin'] : null,
            "coop_type_jasa" => isset($data['coop_type_jasa']) ? $data['coop_type_jasa'] : null,
            "coop_type_prod" => isset($data['coop_type_prod']) ? $data['coop_type_prod'] : null,
            "coop_type_lainnya" => isset($data['coop_type_lainnya']) ? $data['coop_type_lainnya'] : null
        );

        $this->db->join('coop_num_member','coop_num_member.coop_num_member_id = wizard.coop_num_member_id');
        $q = $this->db->get_where('wizard',array('user_id'=>$data['userid']));
        if($q->num_rows()>0){
            $this->db->where('user_id',$data['userid']);
            $this->db->update('wizard',$insert);
        } else {
            $this->db->insert('wizard',$insert);
        }

        $selesai = true;
        // foreach ($q->result_array() as $v) {
        //     print_r($v);
        //     foreach ($v as $key => $value) {
        //        if($key!='coop_type_kons' && $key!='coop_type_simpin' && $key!='coop_type_jasa' && $key!='coop_type_prod' && $key!='coop_type_lainnya'){
        //              if($value==null){
        //                  $selesai = false;
        //               }
        //           }
        //     }

        // }

        $jenis ='';
        if($insert['coop_type_kons']=='on'){
            $jenis.="Koperasi Konsumen";
        }

        if($insert['coop_type_simpin']=='on'){
            $jenis.=", Koperasi Simpan Pinjam";
        }

        if($insert['coop_type_jasa']=='on'){
            $jenis.=", Koperasi Jasa";
        }

        if($insert['coop_type_prod']=='on'){
            $jenis.=", Koperasi Produksi";
        }

        if($insert['coop_type_lainnya']=='on'){
            $jenis.=", Lainnya";
        }


        $jenis = ltrim($jenis, ', ');

        if($jenis==''){
            $selesai = false;
        }

        if($selesai){

            //apply coop data
            // $this->apply_coop($q->result_array()[0]);
            $this->apply_coop($data);

            $this->response(array('success'=>true,'message'=>'selesai','jenis'=>$jenis,'data'=>$q->result_array()[0]), REST_Controller::HTTP_OK);
        } else {
            if(isset($q->result_array()[0])){
                $data = $q->result_array()[0];
            } else {
                $data = null;
            }
            $this->response(array('success'=>false,'message'=>'belum selesai','jenis'=>$jenis,'data'=>$data), REST_Controller::HTTP_OK);
        }
    }

    function apply_coop($data){

        $q = $this->db->get_where('sys_user',array('user_id'=>$data['userid']));
        if($q->num_rows()>0){
            $r = $q->row();

            if($r->idunit==null){
                    //belum ada data koperasi
                    // $idunit =  $this->m_data->getSeqVal('seq_unit');
                    $idunit = $this->m_data->getPrimaryID2(null,'unit','idunit');

                    $insert = array(
                        'idunit' => $idunit,
                        'namaunit' => $data['coop_name'],
                        'alamat' => $data['coop_address'],
                        'telp' => $data['coop_telp'],
                        'email' => $data['email'] == '' ? null : $data['email'],
                        'year_establised'=>$data['coop_year'] == '' ? null : $data['coop_year'],
                        'nolisence'=>$data['coop_nolicence'] == '' ? null : $data['coop_nolicence'],
                        'coop_num_member_id'=>$data['coop_num_member_id'],
                        'curfinanceyear' => date('Y'),
                        'lastmonthfinanceyear' => 12,
                        'conversionmonth' => date('m'),
                        'numaccperiod' => 12,
                        'datein'=>date('Y-m-d H:m:s'),
                        'api_key'=>'KPR_API'.generateRandomString(25).base64_encode(date('YmdHms'))
                    );

                    $this->db->insert('unit',$insert);

                    $this->db->where('user_id',$data['userid']);
                    $this->db->update('sys_user',array('idunit'=>$idunit));

                    $this->create_user_group($idunit,$data['userid']);
                    $this->create_coa($idunit);
                    $this->create_saving($idunit);
                    // $this->create_coa_link($idunit);
                    $this->create_tax_code($idunit);
                    $this->create_coa_unit($idunit);
                    $this->create_unit_usaha($idunit);
                    $this->create_loan_type($idunit);
                    $this->create_customer_type($idunit);
                    $this->create_payroll_data($idunit);
                    $this->create_shu_share($idunit);
                    $this->create_location($idunit);
                    $this->create_product_unit($idunit);
                    $this->create_subscription_fee($idunit);
            }
             
        }

       
    }

    function create_default_record_get(){
        $idunit = $this->get('idunit');
        $user_id = $this->get('user_id');

        // $this->create_user_group($idunit,$user_id);
        // $this->create_coa($idunit);
        // $this->create_saving($idunit);
        // $this->create_coa_link($idunit);
        // $this->create_tax_code($idunit);
        // $this->create_coa_unit($idunit);
        // $this->create_unit_usaha($idunit);
        $this->create_loan_type($idunit);
        // $this->create_customer_type($idunit);
    }

    function create_payroll_data($idunit){
        $q = $this->db->get_where('payroll_tambahangajitype', array('idunit' => 0));
        foreach ($q->result_array() as $r) {
            $d = $r;
            $d['idunit'] = $idunit;
            $d['idtambahangajitype'] = $this->m_data->getPrimaryID2(null, 'payroll_tambahangajitype', 'idtambahangajitype');
            $this->db->insert('payroll_tambahangajitype', $d);
        }

        $q = $this->db->get_where('payroll_potongantype', array('idunit' => 0));
        foreach ($q->result_array() as $r) {
            $d = $r;
            $d['idunit'] = $idunit;
            $d['idpotongantype'] = $this->m_data->getPrimaryID2(null, 'payroll_potongantype', 'idpotongantype');
            $this->db->insert('payroll_potongantype', $d);
        }

        $q = $this->db->get_where('payroll_asuransi', array('idunit' => 0));
        foreach ($q->result_array() as $r) {
            $d = $r;
            $d['idunit'] = $idunit;
            $d['idasuransi'] = $this->m_data->getPrimaryID2(null, 'payroll_asuransi', 'idasuransi');
            $this->db->insert('payroll_asuransi', $d);
        }
    }

    function recreate_location_get(){
        $q = $this->db->get_where('location', array('idunit' => 0));
        foreach ($q->result_array() as $r) {
            $d = $r;

            $qunit = $this->db->get('unit');
            foreach ($qunit->result() as $runit) {
                if($runit->idunit!=0){
                     $d['idunit'] = $runit->idunit;
                    $d['location_id'] = $this->m_data->getPrimaryID2(null, 'location', 'location_id');
                    $this->db->insert('location', $d);
                }
               
            }
           
        }
    }

    function create_location($idunit){
        $q = $this->db->get_where('location', array('idunit' => 0));
        foreach ($q->result_array() as $r) {
            $d = $r;
            $d['idunit'] = $idunit;
            $d['location_id'] = $this->m_data->getPrimaryID2(null, 'location', 'location_id');
            $this->db->insert('location', $d);
        }
    }

    function create_customer_type($idunit){
        $q = $this->db->get_where('customertype', array('idunit' => 0));
        foreach ($q->result_array() as $r) {
            $d = $r;
            $d['idunit'] = $idunit;
            $d['idcustomertype'] = $this->m_data->getPrimaryID2(null, 'customertype', 'idcustomertype');
            $this->db->insert('customertype', $d);
        }
    }

    function generate_api_key_get(){
         $q = $this->db->get('sys_user');
         foreach ($q->result() as $r) {
            $this->db->where('user_id',$r->user_id);
            $this->db->update('sys_user',array(
                    'api_key'=>'KPR_API'.generateRandomString(25).base64_encode(date('YmdHms'))
                ));
         }
    }

    function create_user_group($idunit,$user_id){
        $qunit = $this->db->get_where('unit',array('idunit'=>$idunit));
        foreach ($qunit->result() as $r) {
            $group_id_member = null;
           //create user group
            $qgroup = $this->db->get_where('sys_group',array('idunit'=>0)); //default
            foreach ($qgroup->result_array() as $rr) {
                $datag = $rr;
                $group_id = $datag['group_id'];
                $datag['userin'] = null;
                $datag['usermod'] = null;
                $datag['datein'] = date('Y-m-d H:m:s');
                $datag['datemod'] = date('Y-m-d H:m:s');
                $datag['idunit'] = $idunit;
                if($r->idunit!=0){
                    $datag['group_id'] = $this->m_data->getPrimaryID2(null,'sys_group', 'group_id');
                    $this->db->insert('sys_group',$datag);

                    if($group_id==10){
                        //anggota koperasi
                        $group_id_member = $datag['group_id'];
                    }
                }
               
            }

            //get default user group
            $qdefault = $this->db->get_where('sys_group',array('idunit'=>$idunit,'default'=>1))->row(); //default
            $sys_group_id_default = $qdefault->group_id;

            //insert menu by group access rights
            // $qgroup = $this->db->get_where('sys_group_menu',array('group_id'=>2)); //default
            $qgroup = $this->db->query("select sys_menu_id
                                    from sys_menu
                                    where (display is null or display = 1)");
            foreach ($qgroup->result_array() as $rr) {
                $datag = $rr;
                $datag['group_id'] = $sys_group_id_default;
                $cek = $this->db->get_where('sys_group_menu',$datag);
                if($cek->num_rows()<=0){
                     $this->db->insert('sys_group_menu',$datag);
                }
               
            }

            $this->db->where(array('user_id'=>$user_id,'group_id'=>2));
            $this->db->update('sys_user',array('group_id'=>$sys_group_id_default));

            $this->create_employee_data($user_id,$sys_group_id_default,$idunit);
        } 
    }

    function recreate_shu_share_get(){
        $idunit = $this->get('idunit');
        $query = $this->db->get_where('shu_share',array('unit_id'=>0));

        $qcek = $this->db->get_where('shu_share',array('unit_id'=>$idunit));
        if($qcek->num_rows()<=0){
            foreach ($query->result_array() as $rr) {
                $datag = $rr;
                $datag['unit_id'] = $idunit;
                $datag['shu_share_id'] = $this->m_data->getPrimaryID2(null,'shu_share', 'shu_share_id');
                $this->db->insert('shu_share',$datag);                        
            }
        }
        
    }


    function create_shu_share_get(){
        // $idunit = $this->get('idunit');

        $qunit = $this->db->get('unit');
        foreach ($qunit->result() as $runit) {
            $idunit = $runit->idunit;
            if($idunit!=0){
                $query = $this->db->get_where('shu_share',array('unit_id'=>0));

                foreach ($query->result_array() as $rr) {
                    $datag = $rr;
                    $datag['unit_id'] = $idunit;
                    $datag['shu_share_id'] = $this->m_data->getPrimaryID2(null,'shu_share', 'shu_share_id');
                    $this->db->insert('shu_share',$datag);                        
                }
            }
            
        }
        
    }

    function create_shu_share($idunit){
        // $idunit = $this->get('idunit');
        $query = $this->db->get_where('shu_share',array('unit_id'=>0));

        $qcek = $this->db->get_where('shu_share',array('unit_id'=>$idunit));
        if($qcek->num_rows()<=0){
            foreach ($query->result_array() as $rr) {
                $datag = $rr;
                $datag['unit_id'] = $idunit;
                $datag['shu_share_id'] = $this->m_data->getPrimaryID2(null,'shu_share', 'shu_share_id');
                $this->db->insert('shu_share',$datag);                        
            }
        }
    }

    function create_employee_data($user_id,$sys_group_id,$idunit){
         $r = $this->db->get_where('sys_user',array('user_id'=>$user_id))->row();

         $data = array(
            'idemployee' => $this->m_data->getPrimaryID2(null,'employee', 'idemployee'),
            'idunit' => $idunit,
            'idjenisptkp' => 1,
            // 'code' => $r->nik,
            'firstname' => $r->realname,
            'address' => $r->address,
            'telephone' => $r->phone,
            'handphone' => $r->phone,
            'email' => $r->email,
            'keaktifan' => 'Aktif',
            'user_id' => $user_id,
            'group_id' => $sys_group_id,
            'is_login' => 1,
            'status'=>1,
            'datein'=>date('Y-m-d H:m:s'),
            'datemod'=>date('Y-m-d H:m:s')
        );
        $this->db->insert('employee',$data);
    }

    function default_coa_link_data($index){
        $data[3]['idaccount'] = 1042; // Laba Ditahan / Retained Earnings
        $data[5]['idaccount'] = 785; // Akun Penerimaan Angsuran Pinjaman           
        $data[9]['idaccount'] = 786; //  Akun Penerimaan Penjualan   penerimaan kas atas transaksi tunai dari kasir      
        $data[14]['idaccount'] = 1008; // Hutang Usaha    transaksi pembelian secara kredit       
        $data[15]['idaccount'] = 786; // Kas Pembayaran Hutang Pembelian   
        $data[18]['idaccount'] = 1124; //  SHU Perdagangan Berjalan            
        $data[19]['idaccount'] = 1125; // SHU Simpan Pinjam Berjalan          
        $data[24]['idaccount'] = 812; // Piutang Usaha   Akun yang mencatat piutang atas penjualan      
        $data[30]['idaccount'] = 1044; //  Penjualan   Akun pencatatan transaksi penjualan 

        $coa = isset($data[$index]) ? $data[$index]['idaccount'] : null;
        return $coa;
    }

    function recreate_coa_unit_get(){
        $idlinked = $this->get('idlinked') == '' ? null : $this->get('idlinked');

        // $idunit = $this->get('idunit');
        $qunit = $this->db->get('unit');
        foreach ($qunit->result() as $runit) {
            $idunit = $runit->idunit;

            if($idlinked==null){
                $this->db->where('idunit',$idunit);
                $this->db->delete('linkedaccunit');

                $q = $this->db->get_where('linkedacc',array('display'=>null));
                foreach ($q->result() as $r) {
                    $data = array(
                        'idunit'=>$idunit,
                        'idlinked'=>$r->idlinked
                    );            

                    $cek = $this->db->get_where('linkedaccunit',array('idunit'=>$idunit,'idlinked'=>$r->idlinked));
                    if($cek->num_rows()>0){
                    } else {
                        $data['idaccount'] = $this->default_coa_link_data($r->idlinked);
                        $this->db->insert('linkedaccunit',$data);
                    }
                
                }
            } else {
                //additional linked account
                $data = array(
                        'idunit'=>$idunit,
                        'idlinked'=>$idlinked
                    );        
                $data['idaccount'] = null;
                $this->db->insert('linkedaccunit',$data);
            }
            
            
        }

        
    }

     function create_coa_unit($idunit){

           $q = $this->db->get_where('linkedacc',array('display'=>null));
            foreach ($q->result() as $r) {
                $data = array(
                    'idunit'=>$idunit,
                    'idlinked'=>$r->idlinked
                );            

                $cek = $this->db->get_where('linkedaccunit',array('idunit'=>$idunit,'idlinked'=>$r->idlinked));
                if($cek->num_rows()>0){
                } else {
                    // $data['tax_unit_id'] = $this->m_data->getPrimaryID2('','tax_unit', 'tax_unit_id');
                    $data['idaccount'] = $this->default_coa_link_data($r->idlinked);
                    $this->db->insert('linkedaccunit',$data);
                }
            
            }
    }

    function gen_coa_unit_get(){
        $qunit = $this->db->get('unit');
        foreach ($qunit->result() as $runit) {
            $idunit = $runit->idunit;

           $q = $this->db->get_where('linkedacc',array('display'=>null));
            foreach ($q->result() as $r) {
                $data = array(
                    'idunit'=>$idunit,
                    'idlinked'=>$r->idlinked
                );            

                $cek = $this->db->get_where('linkedaccunit',array('idunit'=>$idunit,'idlinked'=>$r->idlinked));
                if($cek->num_rows()>0){
                } else {
                    // $data['tax_unit_id'] = $this->m_data->getPrimaryID2('','tax_unit', 'tax_unit_id');
                    $this->db->insert('linkedaccunit',$data);
                }
            
            }
        }
        
    }


    function gen_tax_code_get(){
        $qunit = $this->db->get('unit');
        foreach ($qunit->result() as $runit) {
            $idunit = $runit->idunit;

           $q = $this->db->get('tax');
            foreach ($q->result() as $r) {
                $data = array(
                    'idunit'=>$idunit,
                    'idtax'=>$r->idtax,
                    // 'coa_collection_id'=>,
                    // 'coa_paysource_id'=>,
                    'rate'=>$r->rate,
                    'datein'=>date('Y-m-d H:m:s'),
                    // 'userin'=>,
                    'datemod'=>date('Y-m-d H:m:s'),
                    // 'usermod'=>,
                    'status'=>1,
                    'deleted'=>0
                );
            

                $cek = $this->db->get_where('tax_unit',array('idunit'=>$idunit,'idtax'=>$r->idtax));
                if($cek->num_rows()>0){
                    $this->db->where('idunit',$idunit);
                    $this->db->where('idtax',$r->idtax);
                    $this->db->update('tax_unit',$data);
                } else {
                    $data['tax_unit_id'] = $this->m_data->getPrimaryID2('','tax_unit', 'tax_unit_id');
                    $this->db->insert('tax_unit',$data);
                }
            
            }
        }
        
    }

    function recreate_tax_code_get(){
        $qunit = $this->db->get('unit');
        foreach ($qunit->result() as $runit) {
            if($runit->idunit!=0){
                $this->db->where('idunit',$runit->idunit);
                $this->db->delete('tax');
            }

            $q = $this->db->get_where('tax',array('idunit'=>0));
            foreach ($q->result_array() as $r) {
                 if($runit->idunit!=0){
                    $data = $r;
                    $data['idtax'] = $this->m_data->getPrimaryID2(null,'tax', 'idtax');
                    $data['idunit'] = $runit->idunit;
                    $this->db->insert('tax',$data);
                }
            }
        }
        
    }

    function create_tax_code($idunit){
        $q = $this->db->get_where('tax',array('idunit'=>0));
        foreach ($q->result_array() as $r) {
            // if($r['idunit']!=0){
                $data = $r;
                $data['idtax'] = $this->m_data->getPrimaryID2(null,'tax', 'idtax');
                $data['idunit'] = $idunit;
                $this->db->insert('tax',$data);
            // }
        }
    }

    function create_coa_link($idunit){ //disabled
        $q = $this->db->get('linkedacc');
        foreach ($q->result() as $r) {
            $qcek = $this->db->get_where('linkedaccunit',array('idunit'=>$idunit,'idlinked'=>$r->idlinked));
            if($qcek->num_rows()>0)
            {

            } else {
                $this->db->insert('linkedaccunit',array('idunit'=>$idunit,'idlinked'=>$r->idlinked));
            }                
        }
    }

    function gen_unit_usaha_get(){
        $q = $this->db->get('unit');
        foreach ($q->result() as $r) {
            $qcek = $this->db->get_where('business',array('idunit'=>$r->idunit));
            if($qcek->num_rows()>0){
                //udah ada
            } else {
                //belum ada, generate unit usaha default
                $r = $this->create_unit_usaha($r->idunit);
                print_r($r);
            }
        }
    }

    function create_loan_type($idunit){
        $this->db->trans_begin();

        $qloan = $this->db->query("select business_id from business where business_type = 2 and idunit = $idunit")->row();

        //create loan type by default
        $q = $this->db->get_where('loan_type', array('idunit' => 0));
        foreach ($q->result_array() as $r) {
            $d = $r;
            $d['id_loan_type'] = $this->m_data->getPrimaryID(null,'loan_type','id_loan_type');
            $d['idunit'] = $idunit;
            $d['business_id'] = $qloan->business_id;
            // $d['startdate'] = date('Y-m-d');
            $d['datein'] = date('Y-m-d H:m:s');
            $d['datemod'] = date('Y-m-d H:m:s');
            // $d = array(
            //     "id_loan_type" => $this->m_data->getPrimaryID(null,'loan_type','id_loan_type'),
            //     "business_name" => $r->business_name,
            //     "business_desc" => $r->business_desc,
            //     "startdate" => date('Y-m-d'),
            //     "datein" =>  date('Y-m-d H:m:s'),
            //     "datemod" =>  date('Y-m-d H:m:s'),
            //     "idunit" => $idunit
            // );            
           
            $this->db->insert('loan_type', $d);
        }

        if($this->db->trans_status() === false){
            $this->db->trans_rollback();
            $json = array('success'=>false,'message'=>$this->db->last_query());
        }else{
            $this->db->trans_commit();
            $json = array('success'=>true);
        }

        return $json;
    }

    function regenerate_unit_code_get(){
        $q = $this->db->get('unit');
        foreach ($q->result() as $r) {
            if($r->idunit!=0){
                $this->db->where(array('idunit' => $r->idunit));
                $this->db->update('business',array('business_code'=>null));
                
                $q2 = $this->db->get_where('business', array('idunit' => $r->idunit));
                foreach ($q2->result() as $r2) {
                    $this->db->where('business_id',$r2->business_id);
                    $this->db->update('business', array(
                            "business_code"=>$this->generate_unit_code($r->idunit)
                    ));
                }
            }            
        }
    }

    function test_generate_unit_code_get(){
        // $params = array(
        //         'idunit' => $this->user_data->idunit,
        //         // 'idunit' => $idunit,
        //         'prefix' => 'BIZ',
        //         'table' => 'business',
        //         'fieldpk' => 'business_id',
        //         'fieldname' => 'business_code',
        //         'extraparams'=> null,
        //         'digit'=>3
        // );
        
        // $code = $this->m_data->getNextCode($this->user_data->idunit);
        echo $this->generate_unit_code($this->user_data->idunit);
    }

    function generate_unit_code($idunit){
        $params = array(
                // 'idunit' => $this->user_data->idunit,
                'idunit' => $idunit,
                'prefix' => 'BIZ',
                'table' => 'business',
                'fieldpk' => 'business_id',
                'fieldname' => 'business_code',
                'extraparams'=> null,
                'digit'=>3
        );
        
        $code = $this->m_data->getNextCode($params);
        return $code;
    }

    function create_unit_usaha($idunit){
        $this->db->trans_begin();

        //create unit usaha by default
        $q = $this->db->get_where('business', array('idunit' => 0));
        foreach ($q->result() as $r) {

            $d = array(
                "business_id" => $this->m_data->getPrimaryID(null,'business','business_id'),
                "business_name" => $r->business_name,
                "business_desc" => $r->business_desc,
                "business_type" => $r->business_type,
                "startdate" => date('Y-m-d'),
                "datein" =>  date('Y-m-d H:m:s'),
                "datemod" =>  date('Y-m-d H:m:s'),
                "idunit" => $idunit,
                "business_code"=>$this->generate_unit_code($idunit)
            );            
           
            $this->db->insert('business', $d);
        }

        if($this->db->trans_status() === false){
            $this->db->trans_rollback();
            $json = array('success'=>false,'message'=>$this->db->last_query());
        }else{
            $this->db->trans_commit();
            $json = array('success'=>true);
        }

        return $json;
    }

    function create_saving($idunit){
        $this->db->trans_begin();

        //create produk simpanan by default
        $q = $this->db->get_where('saving_type', array('template' => 1));
        foreach ($q->result_array() as $r) {
            $d = $r;
            $d['id_saving_type'] = $this->m_data->getPrimaryID($this->input->post('id_saving_type'),'saving_type','id_saving_type');
            $d['idunit'] = $idunit;
            $d['template'] = 0;
            $d['userin'] = 99;
            $d['datein'] = date('Y-m-d H:m:s');
            $d['usermod'] = 99;
            $d['datemod'] = date('Y-m-d H:m:s');
           
            $this->db->insert('saving_type', $d);
        }

        if($this->db->trans_status() === false){
            $this->db->trans_rollback();
            $json = array('success'=>false,'message'=>$this->db->last_query());
        }else{
            $this->db->trans_commit();
            $json = array('success'=>true);
        }

        return $json;
    }

    function recreate_coa_get(){
        $idunit = $this->get('idunit');

        $q = $this->db->get_where('account', array('idunit' => 0, 'active' => 'TRUE','display'=>null));
        foreach ($q->result() as $r) {
            $qcek = $this->db->get_where('account',array('idaccount'=>$r->idaccount,'idunit'=>$idunit));
            if($qcek->num_rows()<=0){
                 $d = array(
                    'idaccount' => $r->idaccount,
                    'idaccounttype' => $r->idaccounttype,
                    'idaccounttmp' => $r->idaccount,
                    'idclassificationcf' => $r->idclassificationcf,
    //                'idaccount' bigint NOT NULL DEFAULT nextval('seq_account'::regclass),
                    'idaccounttype' => $r->idaccounttype,
    //                'idlinked' =>$this->input->post('description')
                    'idparent' => $r->idparent,
                    'accnumber' => $r->accnumber,
                    'accname' => $r->accname,
    //                'tax' =>$this->input->post('description')
                    'balance' => 0,
                    'active' => $r->active,
                    'description' => $r->description,
                    'idpos' => $r->idpos,
                    'lock' => $r->lock,
                    'permanent' => $r->permanent,
                    'idunit' => $idunit
                );
                $d['userin'] = 'systemwizard';
                $d['datein'] = date('Y-m-d H:m:s');
                $d['usermod'] = 'systemwizard';
                $d['datemod'] = date('Y-m-d H:m:s');

                $this->db->insert('account', $d);
            }
        }
    }

    function create_coa($idunit){
        $this->db->trans_begin();

        //create default akun untuk user baru dari idunit=99/template
        $q = $this->db->get_where('account', array('idunit' => 0, 'active' => 'TRUE','display'=>null));
        foreach ($q->result() as $r) {
            // $qid = $this->db->query("select nextval('seq_account') as id")->row();

            $d = array(
                'idaccount' => $r->idaccount,
                'idaccounttype' => $r->idaccounttype,
                'idaccounttmp' => $r->idaccount,
                'idclassificationcf' => $r->idclassificationcf,
//                'idaccount' bigint NOT NULL DEFAULT nextval('seq_account'::regclass),
                'idaccounttype' => $r->idaccounttype,
//                'idlinked' =>$this->input->post('description')
                'idparent' => $r->idparent,
                'accnumber' => $r->accnumber,
                'accname' => $r->accname,
//                'tax' =>$this->input->post('description')
                'balance' => 0,
                'active' => $r->active,
                'description' => $r->description,
                'idpos' => $r->idpos,
                'lock' => $r->lock,
                'permanent' => $r->permanent,
                'idunit' => $idunit
            );
            $d['userin'] = 'systemwizard';
            $d['datein'] = date('Y-m-d H:m:s');
            $d['usermod'] = 'systemwizard';
            $d['datemod'] = date('Y-m-d H:m:s');

            $this->db->insert('account', $d);

            $dl = array(
                    'idaccount'=>$r->idaccount,
                    'idunit'=>$idunit
            );

            // biaya angkut pembelian
            if($r->idaccount==52)
            {
                
                $qcek = $this->db->get_where('linkedaccunit',$dl);
                if($qcek->num_rows()>0)
                {                    
                    $this->db->where($dl);
                    $dl['idlinked'] = 17;
                    $this->db->update('linkedaccunit',$dl);
                } else {
                    $dl['idlinked'] = 17;
                    $this->db->insert('linkedaccunit',$dl);
                }
                
            }
            // hutang usaha
            if($r->idaccount==32)
            {
                $qcek = $this->db->get_where('linkedaccunit',$dl);
                if($qcek->num_rows()>0)
                {                    
                    $this->db->where($dl);
                    $dl['idlinked'] = 14;
                    $this->db->update('linkedaccunit',$dl);
                } else {
                    $dl['idlinked'] = 14;
                    $this->db->insert('linkedaccunit',$dl);
                }
            }
            // kas
            if($r->idaccount==6)
            {
                $qcek = $this->db->get_where('linkedaccunit',$dl);
                if($qcek->num_rows()>0)
                {                    
                    $this->db->where($dl);
                    $dl['idlinked'] = 15;
                    $this->db->update('linkedaccunit',$dl);
                } else {
                    $dl['idlinked'] = 15;
                    $this->db->insert('linkedaccunit',$dl);
                }
            }
            // laba ditahan
            if($r->idaccount==42)
            {
                $qcek = $this->db->get_where('linkedaccunit',$dl);
                if($qcek->num_rows()>0)
                {                    
                    $this->db->where($dl);
                    $dl['idlinked'] = 3;
                    $this->db->update('linkedaccunit',$dl);
                } else {
                    $dl['idlinked'] = 3;
                    $this->db->insert('linkedaccunit',$dl);
                }
            }
            // laba periode berjalan
            if($r->idaccount==43)
            {
                $qcek = $this->db->get_where('linkedaccunit',$dl);
                if($qcek->num_rows()>0)
                {                    
                    $this->db->where($dl);
                    $dl['idlinked'] = 4;
                    $this->db->update('linkedaccunit',$dl);
                } else {
                    $dl['idlinked'] = 4;
                    $this->db->insert('linkedaccunit',$dl);
                }
            }
            // pph21
            if($r->idaccount==717)
            {
                $qcek = $this->db->get_where('linkedaccunit',$dl);
                if($qcek->num_rows()>0)
                {                    
                    $this->db->where($dl);
                     $dl['idlinked'] = 22;
                    $this->db->update('linkedaccunit',$dl);
                } else {
                     $dl['idlinked'] = 22;
                    $this->db->insert('linkedaccunit',$dl);
                }
            }
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $json = array('success' => false, 'message' => 'Rekening perkiraan gagal dibuat');
        } else {
            $this->db->trans_commit();
            $json = array('success' => true, 'message' => 'Rekening perkiraan berhasil dibuat');
        }

    }

    function recreate_product_unit_get(){
        $qunit = $this->db->get('unit');
        foreach ($qunit->result() as $r) {
            if($r->idunit!=0){
                $this->create_product_unit($r->idunit);
            }
        }
    }


    function create_product_unit($idunit){
        $query = $this->db->get_where('product_unit',array('idunit'=>0));

        $qcek = $this->db->get_where('product_unit',array('idunit'=>$idunit));
        if($qcek->num_rows()<=0){
            foreach ($query->result_array() as $rr) {
                $datag = $rr;
                $datag['idunit'] = $idunit;
                $datag['product_unit_id'] = $this->m_data->getPrimaryID2(null,'product_unit', 'product_unit_id');
                $this->db->insert('product_unit',$datag);                        
            }
        }
    }

    function set_member_group_get(){
        $q = $this->db->query("update sys_group set member_group = 1 where group_name = 'Anggota Koperasi'");
    }

    function create_subscription_fee($idunit){
        $sb = $this->db->get('subscription');
       foreach ($sb->result() as $rr) {
            $datag['idunit'] = $idunit;
            $datag['subscription_id'] = $rr->subscription_id;
            $datag['min_member'] = $rr->min_member;
            $datag['max_member'] = $rr->max_member;
            $datag['price_per_unit'] = $rr->price_per_unit;
            // $datag['subscription_unit_id'] = $this->m_data->getPrimaryID2(null,'subscription_unit', 'subscription_unit_id');
            $this->db->insert('subscription_unit',$datag);                        
       }
    }

     function create_default_subscription_fee_get(){
        $q = $this->db->get('unit');
        foreach ($q->result() as $r) {
             if($r->idunit!=0){
               $sb = $this->db->get('subscription');
               foreach ($sb->result() as $rr) {
                    $datag['idunit'] = $r->idunit;
                    $datag['subscription_id'] = $rr->subscription_id;
                    $datag['min_member'] = $rr->min_member;
                    $datag['max_member'] = $rr->max_member;
                    $datag['price_per_unit'] = $rr->price_per_unit;
                    // $datag['subscription_unit_id'] = $this->m_data->getPrimaryID2(null,'subscription_unit', 'subscription_unit_id');
                    $this->db->insert('subscription_unit',$datag);                        
               }
            }
        }
    }

    function create_group_menu_get(){
        $this->db->trans_begin();

        $idunit = $this->get('idunit');
        $qgroup = $this->db->query("select *
                                        from sys_group
                                        where idunit = ".$idunit." and deleted = 0 and status = 1");
        foreach ($qgroup->result() as $r) {
             //create access
        $qgroup = $this->db->query("select sys_menu_id
                                    from sys_menu
                                    where (display is null or display = 1)");
        foreach ($qgroup->result_array() as $rr) {
            $datag = $rr;
            $datag['group_id'] = $r->group_id;
                $cek = $this->db->get_where('sys_group_menu',$datag);
                if($cek->num_rows()<=0){
                     $this->db->insert('sys_group_menu',$datag);
                }               
            }

        }

       

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $json = array('success' => false, 'message' => 'Failed saving data');
            $this->response($json, REST_Controller::HTTP_BAD_REQUEST);
        } else {
            $this->db->trans_commit();            
            $json = array('success' => true, 'message' => 'Data Saved Successfully');
            $this->response($json, REST_Controller::HTTP_CREATED);
        }
    }

    function generate_memberSaving_get(){

        // $this->db->trans_begin();

        $idunit = $this->get('idunit');
        
        //get saving type
        $vasType = $this->db->get_where('saving_type',array('idunit'=>$idunit));
        foreach ($vasType->result_array() as $key => $value) {
            # code...
            $member =$this->db->query("SELECT a.id_member,member_name FROM member a
                                       where idunit=$idunit");

            if($member->num_rows() >0){

                $data    = $member->result_array();

                foreach ($data as $key => $v) {
                    # code...
                    // print_r($v);
                    $d=array(
                                'id_member_saving' => $this->m_data->getPrimaryID2(null,'member_saving', 'id_member_saving'),
                                'id_saving_type' => $value['id_saving_type'],
                                'id_member' =>$v['id_member'],
                                'date_registered' => date('Y-m-d H:m:s'),
                                'no_account' =>rand(11111111111, 99999999999),
                                'status' => 1,
                                'userin' =>$this->user_data->user_id,
                                'datein' => date('Y-m-d H:m:s'),
                    );

                    $cek = $this->db->query('SELECT a.* 
                                             FROM member_saving a
                                             WHERE a.id_saving_type='.$value['id_saving_type'].' and id_member='.$v['id_member'].' and id_member_saving in 
                                             (SELECT id_member_saving from member_saving where  a.id_saving_type='.$value['id_saving_type'].' and id_member='.$v['id_member'].')');
                    // echo $this->db->last_query();
                    if($cek->num_rows()>0){
                            echo "string";
                            $json = array('success' => false, 'message' => 'Data sudah ada');
                            $this->response($json,REST_Controller::HTTP_BAD_REQUEST);
                            return false;
                        
                    } else {
                        // die;
                        $this->db->insert('member_saving',$d);

                    }

                }
            }
        }
    
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $json = array('success' => false, 'message' => 'Failed saving data');
            $this->response($json, REST_Controller::HTTP_BAD_REQUEST);
        } else {
            $this->db->trans_commit();            
            $json = array('success' => true, 'message' => 'Data Saved Successfully');
            $this->response($json, REST_Controller::HTTP_CREATED);
        }
    }

    function create_apiKey_get(){
        $this->db->trans_begin();

        $q = $this->db->get_where("sys_user",array('api_key'=>null));

        // echo $this->db->last_query();

        if($q->num_rows() >0){
            $this->db->where('api_key',null);
            $this->db->update('sys_user',array(
                 'api_key'=>'KPR_API'.generateRandomString(25).base64_encode(date('YmdHms'))
            ));

            // echo $this->db->last_query();
        }

        if($this->db->affected_rows() >0){
            $this->db->trans_commit();            
            $json = array('success' => true, 'message' => 'API KEY Successfully generate');
            $this->response($json, REST_Controller::HTTP_CREATED);
        }else{
            $this->db->trans_rollback();
            $json = array('success' => false, 'message' => 'API KEY failed to generate');
            $this->response($json, REST_Controller::HTTP_BAD_REQUEST);
        }
    }
}
?>