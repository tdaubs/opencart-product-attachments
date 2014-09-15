<?php
class ModelModuleProductPdfs extends Model {
	
	public function getGimmepdfs($product_id) {
		$return_array	=	array();

		$this->load->model('setting/setting');
		$pdf_setting	=	$this->model_setting_setting->getSetting('product_pdfs');
		if( !empty($pdf_setting) && $pdf_setting['product_pdfs_status'] == '1' ){
			$result	=	$this->db->query("SELECT * FROM ".DB_PREFIX."product_to_pdf WHERE product_id = '$product_id' ");
			if($result->num_rows){
				foreach ($result->rows as $row) {
					$q 	=	$this->db->query("SELECT * FROM ".DB_PREFIX."product_pdfs WHERE pdf_id = ".$row['pdf_id'] );
					
					$filename		=	$q->row['filename'];
					$display_name	=	$q->row['display_name'];
					$pdf_id			=	$q->row['pdf_id'];

					$return_array[]	=	array(
						'filename'		=>	$filename,
						'display_name'	=>	$display_name,
						'pdf_id'		=>	$pdf_id
						);
				}
			}
		}
		return $return_array;
	}
	
}
?>