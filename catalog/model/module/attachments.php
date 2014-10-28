<?php
class ModelModuleAttachments extends Model {
	
	public function get_the_attachments($id, $table="product") {
		$return_array	=	array();

		$this->load->model('setting/setting');
		$pdf_setting	=	$this->model_setting_setting->getSetting('product_pdfs');
		if( !empty($pdf_setting) && $pdf_setting['product_pdfs_status'] == '1' ){
			$result	=	$this->db->query("SELECT * FROM " . DB_PREFIX . $table . "_to_attachment WHERE " . $table . "_id = '$id' ");
			if($result->num_rows){
				foreach ($result->rows as $row) {
					$q 	=	$this->db->query("SELECT * FROM " . DB_PREFIX . "product_pdfs WHERE attachment_id = ".$row['attachment_id'] );
					
					$filename		=	$q->row['filename'];
					$display_name	=	$q->row['display_name'];
					$attachment_id	=	$q->row['attachment_id'];

					$return_array[]	=	array(
						'filename'		=>	$filename,
						'display_name'	=>	$display_name,
						'attachment_id'	=>	$attachment_id
						);
				}
			}
		}
		return $return_array;
	}
	
}
?>
