<?php

class M_business extends CI_Model {

	function total_investor($business_id){
		$x = $this->db->query("select COALESCE(count(*),0) as total_investor
								from business_investor
								where business_id = $business_id
								group by business_id")->row();
		$total = isset($x->total_investor) ? $x->total_investor : 0;
		return $total;
	}

	function total_capital($business_id){
		//get capital
        $q = $this->db->query("select sum(total_amount) as total_capital
                                from business_investor a
                                join member b ON a.member_id = b.id_member
                                where a.business_id = $business_id");
        if($q->num_rows()>0){
            $r = $q->row();
            $total_capital = intval($r->total_capital);
        } else {
            $total_capital = 0;
        }
        return $total_capital;
	}

	function total_expense($business_id,$startdate=null,$enddate=null){
		 //get expense
		$wer = null;
		if($startdate!=null && $enddate!=null){
			$wer.=" and (a.datetrans between '".$startdate."' and '".$enddate."')";
		}

         $q = $this->db->query("select COALESCE(sum(totalpaid),0) as total_spend_cash
                                from spendmoney a
                                where a.business_id = $business_id and display is null $wer");
        if($q->num_rows()>0){
            $r = $q->row();
            $total_spend_cash = intval($r->total_spend_cash);
        } else {
            $total_spend_cash = 0;
        }
        return $total_spend_cash;
	}

	function omzet($idunit,$business_id=null,$startdate=null,$enddate=null,$business_type=null){
		$total = 0;

		$wer = null;
		if($business_id!=null){
			$wer.= " business_id = $business_id";
		} else {
			$wer.= " business_id is not null";
		}

		if(($startdate!=null && $startdate!='') && ($enddate!=null && $enddate!='')){
			$wer.=" and (datetrans between '".$startdate."' and '".$enddate."')";
		}

		//from cash
		$q = $this->db->query("select COALESCE(sum(total),0) as total_receive_cash
								from receivemoney
								where idunit = $idunit and status = 2 and $wer and display is null")->row();
		$total+=$q->total_receive_cash;
		// echo $this->db->last_query().'<hr>';
		$wer = null;
		if($business_id!=null){
			$wer.= " b.business_id = $business_id";
		} else {
			$wer.= " business_id is not null";
		}

		if(($startdate!=null && $startdate!='') && ($enddate!=null && $enddate!='')){
			$wer.=" and (c.date_sales between '".$startdate."' and '".$enddate."')";
		}

		if($business_type==2){
			//ambil pemasukan dari simpan pinjam
			$data_summary = $this->m_loan->summary($idunit,$startdate,$enddate);
			$total+=$data_summary['total_interest_income'];
		} else {
			//unit bisnis lainnya/sales
			$q = $this->db->query("select COALESCE(sum(a.total),0) as total_sales
									from salesitem a
									join product b ON a.product_id = b.product_id
									join sales c ON a.idsales = c.idsales
									where c.display is null and c.idunit = $idunit and $wer and a.deleted = 0")->row();
			$total+=$q->total_sales;
		}

		

		return $total;
	}

	function shu_member_paid($idunit,$business_id=null,$member_id=null){
		$wer = null;
		if($business_id!=null){
			$wer.= " b.business_id = $business_id";
		} else {
			$wer.= " b.business_id is not null";
		}

		if($member_id!=null){
			$wer.= " and a.id_member = $member_id";
		}

		$q = $this->db->query("select a.shu_generate_id,COALESCE(sum(total_shu),0) as total_paid_shu_member
								from shu_member a
								left join shu_generate b ON a.shu_generate_id =  b.shu_generate_id
								join member c ON a.id_member = c.id_member
								where c.idunit = $idunit and b.status = 2 and $wer
								group by a.shu_generate_id");
		if($q->num_rows()>0){
			$r = $q->row();
			$total_paid_shu_member = intval($r->total_paid_shu_member);
		} else {
			$total_paid_shu_member = 0;
		}
		return $total_paid_shu_member;
	}

	function expense_trx($business_id,$startdate,$enddate){
		$wer = null;
		if($startdate!=null && $enddate!=null){
			$wer.=" and (a.datein between '".$startdate." 00:00:00' and '".$enddate." 23:59:59')";
		}

		$q = $this->db->query("select a.idspendmoney,a.totalpaid,a.memo,a.datein,a.notrans,a.business_id
					from spendmoney a
					where a.business_id = $business_id and a.status = 2 and a.display is null $wer");
		return $q->result_array();
	}

	function income_cash_trx($business_id,$startdate,$enddate){
		$wer = null;
		if($startdate!=null && $enddate!=null){
			$wer.=" and (a.datein between '".$startdate." 00:00:00' and '".$enddate." 23:59:59')";
		}

		$q = $this->db->query("select a.idreceivemoney,a.total,a.memo,a.datein,a.receivefrom,a.notrans
					from receivemoney a
					where a.business_id = $business_id and a.status = 2 and a.display is null $wer");
		return $q->result_array();
	}

	function income_sales_trx($business_id,$startdate,$enddate){
		$wer = null;
		if($startdate!=null && $enddate!=null){
			$wer.=" and (a.datein between '".$startdate." 00:00:00' and '".$enddate." 23:59:59')";
		}

		$q = $this->db->query("select a.idsales,total_sales,a.no_sales_order,a.datein
								from sales a
								join (select a.idsales,sum(a.total) as total_sales
									from salesitem a
									join product b ON a.product_id = b.product_id
									where b.business_id = $business_id and a.deleted = 0 $wer
									group by a.idsales) b ON a.idsales = b.idsales");
		return $q->result_array();
	}
}
?>