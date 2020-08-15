<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Get extends MY_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->methods['users_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['users_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['users_delete']['limit'] = 50; // 50 requests per hour per user/key
    }

    public function doc_number_get(){
          // 'prefix' => 'INV',
          //   'table' => 'sales',
          //   'fieldpk' => 'idsales',
          //   'fieldname' => 'noinvoice',
        $params = array(
            'idunit' => $this->user_data->idunit,
            'prefix' => $this->get('prefix'),
            'table' => $this->get('table'),
            'fieldpk' => $this->get('fieldpk'),
            'fieldname' => $this->get('fieldname'),
            'extraparams'=> null,
        );

        if($this->get('month')!=''){
            $params['month'] = $this->get('month');
        }

        if($this->get('year')!=''){
            $params['year'] = $this->get('year');
        }
        
        // $this->load->library('../controllers/setup');
        $invoice_no = $this->m_data->getNextNoArticle($params);
        $data_sales['noinvoice'] = $invoice_no;

        $this->set_response(array('success'=>true,'doc_number'=>$invoice_no), REST_Controller::HTTP_OK); 
    }

    function data_get(){
        $dir = $this->get('dir');
        $model_name = $this->get('model');
        $unit_id = $this->get('unit');
        $extraparams = $this->get('extraparams');

         if ($dir != null) {
            $dir = $dir . '/';
        }
//        echo $dir;
        $modelfile = $dir . 'm_' . $model_name;
        $this->load->model($modelfile, 'datamodel');

//        $this->load->model('m_' . $data, 'datamodel');
//        $pkfield = $this->datamodel->pkField();
//        $pkfield = explode(",", $pkfield);

        $arrWer = array();
        $arrPerParam = explode(",", $extraparams);

        foreach ($arrPerParam as $value) {
            $p = explode(":", $value);
            if (isset($p[1]))
                $arrWer[$p[0]] = $p[1];
        }

        $arrWer = array();
        if ($extraparams != '') {
            $wer = "";
            $p = explode(',', $extraparams);
            $jum = count($p);
            $i = 1;
            $arrWer = array();
            foreach ($p as $key => $value) {

                $vparam = explode(':', $value);
                if (preg_match('/null/', $vparam[1])) {
                    //null
                } else {
                    $wer .= $vparam[0] . "='$vparam[1]'";
                    if ($vparam[1] != 'undefined') {
                        $arrWer[$vparam[0]] = $vparam[1];
                    }
                }
                $i++;
            }
        } else {
            $wer = null;
        }
        // echo $wer; die;
        
        $jum = count($arrWer);
        $i = 1;
        $wer = "";
        foreach ($arrWer as $key => $value) {
            if ($i < $jum) {
                // echo "DISISNI";
                $wer .= "$key='$value' AND ";
            } else {
                // echo 'a';
                $wer .= "$key='$value'";
            }
            $i++;
            # code...
        }


        if ($wer != '') {
            $sql = $this->datamodel->query() . " WHERE " . $wer;
        } else {
            $sql = $this->datamodel->query();
        }


        $q = $this->db->query($sql);
       // echo $this->db->last_query();
        if ($q->num_rows() > 0) {
            $r = $q->row();
//            var_dump($r);
            $field = $this->datamodel->selectField();
            $field = explode(",", $field);
            $json = "{success: true,data: {";
            foreach ($field as $value) {
                $v = explode(".", $value);
                if (count($v) > 1) {
                    //pake alias.. insert array ke 1
                    //apus spasi
                    // $vas = str_replace(" ", "", $v[1]);
                    $vas = $v[1];

                    //detek alias
                    $vas = explode(" as ", $vas);
                    if (count($vas) > 1) {
                        //pake alias
                        $a = $vas[1];
                        $json .="$vas[1]: \"" . str_replace(array("\r\n", "\n", "\r"), ' ',rtrim($r->$a)) . "\",";
                    } else {
                        $a = $v[1];
                        $json .="$v[1]: \"" . str_replace(array("\r\n", "\n", "\r"), ' ',rtrim($r->$a)) . "\",";
                    }
//                    $json .="$v[1]: \"" . $r->$v[1] . "\",";
                } else {
                    //detek alias
                    $vas = explode(" as ", $value);
                    if (count($vas) > 1) {
                        //pake alias
                        $b = rtrim($vas[1]);
                        $json .="$vas[1]: \"" . $r->$b . "\",";
                    } else {
                       $json .="$value: \"" . str_replace(array("\r\n", "\n", "\r"), ' ',$r->$value) . "\",";                        
                    }
                }
            }
            $json = rtrim($json,",");
            $json .="}}";
        } else {
            $json = json_encode(array('success' => false, 'message' => 'Data tidak detemukan'));
        }

        echo $json;
    }

    function datas_get(){
        $dir = $this->get('dir');
        $model_name = $this->get('model');       
        $key = $this->get('key');
        $extraparams = $this->get('extraparams');
        $page = $this->get('page');
        $user_id = null;
        $unit_id = null;
        $start = $this->get('start')!='' ? $this->get('start') : 0;
        $limit = $this->get('limit')!='' ? $this->get('limit') : null;
        $query = $this->get('query')!='' ? $this->get('query') : null;

    
        // $account = $this->m_user->get_account($key);
        // if(!$account){
        //     echo json_encode(array('success'=>false,'message'=>'key is invalid'));
        //     return false;
        // }

        $user_id = $this->get('user_id');
        $unit_id = $this->get('idunit');

        if ($dir != null) {
            // echo $dir.'/m_'.$model_name;
            $this->load->model($dir . '/m_' . $model_name, 'datamodel');
        } else {
            $this->load->model('m_' . $model_name, 'datamodel');
        }



        $arrWer = array();
        if ($extraparams != '') {
            $wer = "";
            $p = explode(',', $extraparams);
            $jum = count($p);
            $i = 1;
            $arrWer = array();
            foreach ($p as $key => $value) {

                $vparam = explode(':', $value);
                if (preg_match('/null/', $vparam[1])) {
                    //null
                } else {
                    $wer .= $vparam[0] . "='$vparam[1]'";
                    if ($vparam[1] != 'undefined') {
                        $arrWer[$vparam[0]] = $vparam[1];
                    }
                }
                $i++;
            }
        } else {
            $wer = null;
        }

//         print_r($arrWer);
        $jum = count($arrWer);
        $i = 1;
        $wer = "";
        foreach ($arrWer as $key => $value) {
            if ($i < $jum) {
                // echo "DISISNI";
                $wer .= "$key='$value' AND ";
            } else {
                // echo 'a';
                $wer .= "$key='$value'";
            }
            $i++;
            # code...
        }


        if ($page > 1) {
            if ($page == 2) {
                //problem saat clear search box, start-nya hilang
                $start = $limit;
            } else {
                $kali = $page - 1;
                $start = $limit * $kali;
            }
        }

        $w = " WHERE TRUE";

        if ($query!=null) {

            $field = 0;
            $start = 0;

            foreach ($this->datamodel->searchField() as $key => $value) {
                if ($field == 0) {
                    // $w .="(";
                    $w.=" AND ((" . $value . " LIKE '%" . strtoupper($query) . "%') OR (" . $value . " LIKE '%" . strtolower($query) . "%')  OR (" . $value . " LIKE '%" . ucwords(strtolower($query)) . "%')";
                } else {
                    $w.=" OR (" . $value . " LIKE '%" . strtoupper($query) . "%') OR (" . $value . " LIKE '%" . strtolower($query) . "%') OR (" . $value . " LIKE '%" . ucwords(strtolower($query)) . "%')";
                }
                $field++;
            }
            $w .=")";

            if ($extraparams != '' && $wer != '') {
                $w.=" AND $wer ";
            }
        } else if ($extraparams != '' && $wer != '') {
            $w.=" AND $wer ";
        }

        //query tambahan dari model
        if ($this->datamodel->whereQuery($user_id,$unit_id) != "") {
            $w.=" AND " . $this->datamodel->whereQuery($user_id,$unit_id) . " ";
        }

        if ($this->get('startdate') != null && $this->get('enddate') != 'null') {
             $w.=" AND " . $this->datamodel->whereQuery($user_id,$unit_id,$this->get('startdate'),$this->get('enddate')) . " ";
        }

        $orderby = $this->datamodel->orderBy() != "" ? "ORDER BY " . $this->datamodel->orderBy() : null;
        if($limit==''){
            $sql = $this->datamodel->query() . " $w " . $orderby;

        }else{
            $sql = $this->datamodel->query() . " $w " . $orderby . " LIMIT $limit OFFSET $start";

        }

//        $sql= $this->datamodel->query()." $w LIMIT $limit OFFSET $start";
        if($this->get('show_sql')==1){
            echo $sql;   die;
        }
        
        $this->db->limit($start, $limit);
        $query_page = $this->db->query($sql);
        // echo $sql;       
        $arr = array();
        foreach ($query_page->result() as $obj) {
            $arr[] = $obj;
        }

        $query = $this->db->query($this->datamodel->query() . " $w");

        $results = $query->num_rows();
        // echo '{success:true,numrow:' . $query->num_rows() . ',results:' . $results .',rows:' . json_encode($arr) . '}';
        $message =$this->set_response(array('success'=>true,'numrow'=> $query->num_rows(), 'results'=>  $results ,'rows'=> $arr),REST_Controller::HTTP_OK);
        $query->free_result(); 
        $query_page->free_result(); 

        // echo $this->db->last_query();
    }

    public function city_get(){
        $s = $this->get('s');
        $zipcode = strtolower($this->get('zipcode'));
        $limit = $this->get('limit') == '' ? 500 : $this->get('limit');

        $wer = " where true ";

        if($s!=''){
            if(strlen($s)<3){
                 $this->set_response(array('success'=>false,'numrow'=> 0, 'message'=>  'Please enter 3 or more characters'),REST_Controller::HTTP_BAD_REQUEST);
                 return false;
            }
            $wer.= " AND (city_name like '%".$s."%' OR city_name like '%".ucwords($s)."%') ";
                                    // -- where city_name like '%Depo%'
                                    // -- where zip_code like '%236%'
        }

        if($zipcode!=''){
            if(strlen($zipcode)<3){
                 $this->set_response(array('success'=>false,'numrow'=> 0, 'message'=>  'Please enter 3 or more characters'),REST_Controller::HTTP_BAD_REQUEST);
                 return false;
            }
            $wer.= " AND (zip_code like '".$zipcode."%') ";
        }

        $q = $this->db->query("select a.city_id,a.city_name,a.zip_code
                                    from (
                                    select city_id,zip_code,city,sub_district,district,province,concat(province,', ',district,', ',sub_district,', ',city) as city_name
                                    from city) a
                                    $wer
                                    limit $limit");
        $this->set_response(array('success'=>true,'numrow'=> $q->num_rows(), 'results'=>  $q->result_array()),REST_Controller::HTTP_OK);
    }
}

?>