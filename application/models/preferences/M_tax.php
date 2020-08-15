<?php
class M_tax extends CI_Model{

	function save_tax_config($idtax=null,$data){

		if($idtax!='' && $idtax!=null){
			//update
			 $this->db->where('idtax',$idtax);
			 $this->db->update('tax',$data);
		} else {
			//insert
			$data['idtax'] = $this->m_data->getPrimaryID2(null,'tax', 'idtax');
		 	$this->db->insert('tax',$data);
		}

	}

	function get($id=null,$query=null){
		$wer = null;

		if($id!=null){
			$wer.= " and a.idtax = $id";
		}

		$sql = "select a.idtax,a.idunit,a.code,a.nametax,a.description,coa_ppn_rate,coa_pph23_rate,is_tax_ppn,is_tax_pph23,
								a.coa_ppn_sales_id,b.accnumber as coa_ppn_sales_number,b.accname as coa_ppn_sales_name,
								a.coa_ppn_purchase_id,c.accnumber as coa_ppn_purchase_number,c.accname as coa_ppn_purchase_name,
								a.coa_pph23_sales_id,d.accnumber as coa_pph23_sales_number,d.accname as coa_pph23_sales_name,
								a.coa_pph23_purchase_id,e.accnumber as coa_pph23_purchase_number,e.accname as coa_pph23_purchase_name
								from tax a 
								left join account b ON a.coa_ppn_sales_id = b.idaccount and a.idunit = b.idunit
								left join account c ON a.coa_ppn_purchase_id = c.idaccount and a.idunit = c.idunit
								left join account d ON a.coa_pph23_sales_id = d.idaccount and a.idunit = d.idunit
								left join account e ON a.coa_pph23_purchase_id = e.idaccount and a.idunit = e.idunit
								where a.idunit = ".$this->user_data->idunit." and a.deleted = 0 $wer";

		$q = $this->db->query("$sql	".$this->common_lib->build_limit_offset());
		$qtotal = $this->db->query("$sql");

		$data = array();
		$i=0;
		foreach ($q->result_array() as $key => $value) {
			$data[$i] = $value;
			if($value['coa_ppn_rate']==null && $value['coa_pph23_rate']==null){
				$data[$i]['tax_rate'] = 0;
			} else {
				$data[$i]['tax_rate'] = $value['coa_ppn_rate'] + $value['coa_pph23_rate'];
			}
			
			// $data[$i]['product_name_with_code'] = $value['no_sku'].' - '.$value['product_name'];
			$i++;
		}

		return array('total'=>$qtotal->num_rows(),'data'=>$data);
	}
}

?>