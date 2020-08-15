<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Common_lib {
    
    function build_limit_offset(){
        $CI = & get_instance();

        $start = $CI->get('start')!='' ? $CI->get('start') : 0;
        $limit = $CI->get('limit')!='' ? $CI->get('limit') : null;
        $query = $CI->get('query')!='' ? $CI->get('query') : null;
        $page = $CI->get('page');
        if ($page > 1) {
            if ($page == 2) {
                //problem saat clear search box, start-nya hilang
                $start = $limit;
            } else {
                $kali = $page - 1;
                $start = $limit * $kali;
            }
        }
        if($limit!='' && $limit>=10){
            $limit_offset = "LIMIT $limit OFFSET $start";

        }else{
            $limit_offset='';            
        }
        
        return $limit_offset;
    }

    function order_by_query($sort,$table_name){
        $CI = & get_instance();
        
        if($sort!=null && $sort!=''){
            $column_name = $sort[0]->{'property'};
            $check_column_name = $CI->db->query("SELECT column_name 
                                            FROM information_schema.columns 
                                            WHERE table_name='".$table_name."' and column_name='".$column_name."' ")->row();
                                            // echo $this->db->last_query();

             $orderby = "ORDER BY ";

             $i=0;
             foreach ($sort as $r) {
                $orderby .= $sort[$i]->{'property'}." ".$sort[$i]->{'direction'}.',';
                $i++;
             }

             $orderby = substr($orderby, 0, -1);
        } else {
             $orderby = '';
        }

        return $orderby;
    }
}