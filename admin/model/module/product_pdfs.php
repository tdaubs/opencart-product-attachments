<?php
class ModelModuleProductPdfs extends Model {

	// Get list of pdfs that are attached to a product. (used in admin product form)
	public function getProductPdfs($product_id) {
		$return_array	=	array();

		$this->load->model('setting/setting');
		$pdf_setting	=	$this->model_setting_setting->getSetting('product_pdfs');
		if( !empty($pdf_setting) && $pdf_setting['product_pdfs_status'] == '1' ){
		
			$result	=	$this->db->query("SELECT * FROM ".DB_PREFIX."product_to_pdf WHERE product_id = '$product_id' ");
			
			// Have we found any instances of the product_id in the product_to_pdf table?
			if($result->num_rows){
				foreach ($result->rows as $row) {
					// If any are found, grab details of each pdf and add to the return_array.
					$q 	=	$this->db->query("SELECT * FROM ".DB_PREFIX."product_pdfs WHERE pdf_id = ".$row['pdf_id'] );
					
					$filename			=	$q->row['filename'];
					$display_name		=	$q->row['display_name'];
					$pdf_id				=	$q->row['pdf_id'];
					$unattach_params	=	"&product_id=$product_id&pdf_id=$pdf_id";

					$return_array[]	=	array(
						'filename'			=>	$filename,
						'display_name'		=>	$display_name,
						'pdf_id'			=>	$pdf_id,
						'product_id'		=>	$product_id,
						'unattach_params'	=>	$unattach_params
						);
				}
			}
		}

		return $return_array;
	}

	// Used in Product Page to build the <select> list
	public function getPdfSelectList($product_id) {
		$return_array	=	array();

		$this->load->model('setting/setting');

		$pdf_setting	=	$this->model_setting_setting->getSetting('product_pdfs');
		if( !empty($pdf_setting) && $pdf_setting['product_pdfs_status'] == '1' ){
		
			$result	=	$this->db->query("SELECT DISTINCT * FROM ".DB_PREFIX."product_pdfs ");
			
			if($result->num_rows){

				foreach ($result->rows as $row):
					
					$pdf_id	=	$row['pdf_id'];

					$query	=	$this->db->query("SELECT * FROM ".DB_PREFIX."product_to_pdf WHERE pdf_id = '$pdf_id' AND product_id = '$product_id' ");

					// If the Current pdf is already attached, dont include in the <select> list.
					if( $query->num_rows == 0 ){
						$filename		=	$row['filename'];
						$display_name	=	$row['display_name'];

						$return_array[]	=	array(
							'filename'			=>	$filename,
							'display_name'		=>	$display_name,
							'pdf_id'			=>	$pdf_id
							);
					}

				endforeach;
			}

		}

		return $return_array;
	}

	// The list of PDFs in the admin (with attached products / number of attachments)
	public function getPdfsForAdmin() {
		$return_array	=	array();

		$this->load->model('setting/setting');

		$pdf_setting	=	$this->model_setting_setting->getSetting('product_pdfs');
		if( !empty($pdf_setting) && $pdf_setting['product_pdfs_status'] == '1' ){
		
			// Grab all pdfs we have available
			$result	=	$this->db->query("SELECT DISTINCT * FROM ".DB_PREFIX."product_pdfs ");
			
			if($result->num_rows){

				// Run through each pdf
				foreach ($result->rows as $row) {
					
					// Store vars for smarter array display
					$filename		=	$row['filename'];
					$display_name	=	$row['display_name'];
					// Store the pdf_id
					$pdf_id			=	$row['pdf_id'];
					// Query for all products with links to current pdf
					$query			=	$this->db->query("SELECT * FROM ".DB_PREFIX."product_to_pdf WHERE pdf_id = '$pdf_id' ");
					// Store the number of attaches
					$num_attached	=	$query->num_rows;
					// Get all products attached to this pdf and store as array
					$attached_products	=	$this->getAttachedProducts($pdf_id);

					$return_array[]	=	array(
						'filename'			=>	$filename,
						'display_name'		=>	$display_name,
						'pdf_id'			=>	$pdf_id,
						'num_attached'		=>	$num_attached,
						'attached_products'	=>	$attached_products
						);					
				}

			}

		}

		return $return_array;
	}

	public function updatePdfs($data){
		foreach ($data as $key => $value) {
			#$value	=	mysql_real_escape_string($value);
			$query	=	$this->db->query("UPDATE ".DB_PREFIX."product_pdfs SET display_name = '$value' WHERE pdf_id = '$key'");
		}
	}

	public function pdfAttach($product_id, $pdf_id){
		$check_exists	=	$this->db->query("SELECT * FROM ".DB_PREFIX."product_to_pdf WHERE product_id = '$product_id' AND pdf_id = '$pdf_id' ");
		if( $check_exists->num_rows > 0 ){
			return;
		}else{
			$this->db->query("INSERT INTO ".DB_PREFIX."product_to_pdf SET product_id = '$product_id', pdf_id = '$pdf_id' ");
		}
	}

	public function pdfDetach($product_id, $pdf_id){
		$this->db->query("DELETE FROM ".DB_PREFIX."product_to_pdf WHERE product_id = '$product_id' AND pdf_id = '$pdf_id' ");	
		return;	
	}

	public function uploadPdf( $files_to_upload ){	

		// Default to allow file to upload
		$allow_upload	=	TRUE;
		$name			=	$files_to_upload['pdf_attachment']['name'];
		$type			=	$files_to_upload['pdf_attachment']['type'];
		$tmp_name		=	$files_to_upload['pdf_attachment']['tmp_name'];
		$size			=	$files_to_upload['pdf_attachment']['size'];
		$error			=	$files_to_upload['pdf_attachment']['error'];
		$allowed_ext	=	array('application/pdf');

		$errors			=	array();

		// Check if filename already exists
		$q = $this->db->query("SELECT * FROM ".DB_PREFIX."product_pdfs WHERE filename = '" . $name . "'");
		if( $q->num_rows > 0 ){
			$allow_upload	=	FALSE;
			$errors[]		=	"Filename already exists. Please rename or upload another.";
		}

		// Check if correct file type is being uploaded
		if(!in_array($type, $allowed_ext)){
			$allow_upload	=	FALSE;
			$errors[]		=	"Wrong File Type (PDF type aloud only).";
		}

		// Check if no errors
		if($error != '0'){
			$allow_upload	=	FALSE;
			$errors[]		=	"Error whilst uploading. Please try again.";
		}

		// If its all okay, upload this bad boy!!!
		if( $allow_upload && empty($errors) ){				
			if(move_uploaded_file($tmp_name, DIR_PDFS.$name) ){
				$this->db->query("INSERT INTO ".DB_PREFIX."product_pdfs SET filename = '$name', display_name = '$name' ");
				return true;
			}
		}else{
			return $errors;
		}
	}

	public function getAttachedProducts( $pdf_id ){
		$pdf_id	=	(int)$pdf_id;
		$return_array	=	array();
		// Run an ugly query to grab data needed.
		$query	=	$this->db->query("SELECT * FROM ".DB_PREFIX."product_to_pdf 
										LEFT JOIN ".DB_PREFIX."product ON ".DB_PREFIX."product_to_pdf.product_id = ".DB_PREFIX."product.product_id 
										LEFT JOIN ".DB_PREFIX."product_description ON ".DB_PREFIX."product.product_id = ".DB_PREFIX."product_description.product_id
										WHERE ".DB_PREFIX."product_to_pdf.pdf_id = '$pdf_id'");
		// If we have results... PROCEED!!!
		if( $query->num_rows > 0 ){
			foreach ($query->rows as $row) {
				$product_name	=	$row['name'];
				$product_id		=	$row['product_id'];

				$return_array[]	=	array(
					'product_name'		=>	$product_name,
					'product_id'		=>	$product_id,
					'pdf_id'			=>	$pdf_id,
					'unattach_params'	=>	"&product_id=$product_id&pdf_id=$pdf_id"
					);
			}
		}
		return $return_array;
	}

	// Create the needed tables on install. These do NOT, repeat NOT, get deleted on uninstall
	public function createTable(){
		$query = $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "product_pdfs (pdf_id INT(11) AUTO_INCREMENT, filename VARCHAR(255), display_name VARCHAR(255), PRIMARY KEY (pdf_id))");
		$query = $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "product_to_pdf (product_to_pdf_id INT(11) AUTO_INCREMENT, product_id INT(11), pdf_id INT(11), PRIMARY KEY (product_to_pdf_id))");
	}

}
?>