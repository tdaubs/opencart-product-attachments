<?php
class ModelModuleAttachments extends Model {


	// Get list of pdfs that are attached to a product. (used in admin product form)
	public function get_the_attachments($id, $table='product') {
		$return_array	=	array();

		$this->load->model('setting/setting');
		$pdf_setting	=	$this->model_setting_setting->getSetting('attachments');
		if( !empty($pdf_setting) && $pdf_setting['attachments_status'] == '1' ){
		
			$result	=	$this->db->query("SELECT * FROM " . DB_PREFIX . $table . "_to_attachment WHERE " . $table . "_id = '$id' ");
			
			// Have we found any instances of the product_id in the product_to_pdf table?
			if($result->num_rows){
				foreach ($result->rows as $row) {
					// If any are found, grab details of each pdf and add to the return_array.
					$q 	=	$this->db->query("SELECT * FROM " . DB_PREFIX . "attachments WHERE attachment_id = ".$row['attachment_id'] );
					
					$filename			=	$q->row['filename'];
					$display_name		=	$q->row['display_name'];
					$attachment_id		=	$q->row['attachment_id'];
					$unattach_params	=	"&" . $table . "_id=$id&attachment_id=$attachment_id";
					

					$return_array[]	=	array(
						'filename'			=>	$filename,
						'display_name'		=>	$display_name,
						'attachment_id'		=>	$attachment_id,
						'id'				=>	$id,
						'unattach_params'	=>	$unattach_params
						);
				}
			}
		}

		return $return_array;
	}

	// Used in Product Page to build the <select> list //getPdfSelectList
	public function get_unattached_attachments($id, $table='product') {
		$return_array	=	array();

		$this->load->model('setting/setting');

		$pdf_setting	=	$this->model_setting_setting->getSetting('attachments');

		if( !empty($pdf_setting) && $pdf_setting['attachments_status'] == '1' ){
		
			$result	=	$this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "attachments ");
			
			if($result->num_rows){

				foreach ($result->rows as $row):
					
					$attachment_id	=	$row['attachment_id'];

					$query	=	$this->db->query("SELECT * FROM " . DB_PREFIX . $table . "_to_attachment WHERE attachment_id = '$attachment_id' AND " . $table . "_id = '$id' ");

					// If the Current pdf is already attached, dont include in the <select> list.
					if( $query->num_rows == 0 ){
						$return_array[]	=	array(
							'filename'			=>	$row['filename'],
							'display_name'		=>	$row['display_name'],
							'attachment_id'		=>	$attachment_id
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

		$pdf_setting	=	$this->model_setting_setting->getSetting('attachments');

		if( !empty($pdf_setting) && $pdf_setting['attachments_status'] == '1' ){
		
			// Grab all pdfs we have available
			$result	=	$this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "attachments ");
			
			if($result->num_rows){

				// Run through each pdf
				foreach ($result->rows as $row) {
					
					$filename		=	$row['filename'];
					$display_name	=	$row['display_name'];
					$attachment_id	=	$row['attachment_id'];

					// Query for all products with links to current pdf
					$query_products			=	$this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_attachment WHERE attachment_id = '$attachment_id' ");
					$query_categories		=	$this->db->query("SELECT * FROM " . DB_PREFIX . "category_to_attachment WHERE attachment_id = '$attachment_id' ");
					$num_products_attached	=	$query_products->num_rows;
					$num_categories_attached=	$query_categories->num_rows;

					// Get all products attached to this pdf and store as array
					$attached_products	=	$this->getAttachedProducts($attachment_id);
					// Get all categories attached to this pdf and store as array
					$attached_categories=	$this->getAttachedProducts($attachment_id, 'category');

					$return_array[]	=	array(
						'filename'			=>	$filename,
						'display_name'		=>	$display_name,
						'attachment_id'		=>	$attachment_id,
						'num_products_attached'		=>	$num_products_attached,
						'num_categories_attached'	=>	$num_categories_attached,
						'attached_products'	=>	$attached_products,
						'attached_categories' => $attached_categories
						);					
				}

			}

		}

		return $return_array;
	}

	public function updatePdfs($data){
		foreach ($data as $key => $value) {
			#$value	=	mysql_real_escape_string($value);
			$query	=	$this->db->query("UPDATE ".DB_PREFIX."attachments SET display_name = '$value' WHERE attachment_id = '$key'");
		}
	}

	public function pdfAttach($id, $attachment_id, $table='product'){

		$check_exists	=	$this->db->query("SELECT * FROM " . DB_PREFIX . $table . "_to_attachment WHERE " . $table . "_id = '$id' AND attachment_id = '$attachment_id' ");

		if( $check_exists->num_rows > 0 ){
			return false;
		}else{
			$this->db->query("INSERT INTO " . DB_PREFIX . $table . "_to_attachment SET " . $table . "_id = '$id', attachment_id = '$attachment_id' ");
			return true;
		}

	}

	public function pdfDetach($id, $attachment_id, $table='product'){
		$this->db->query("DELETE FROM " . DB_PREFIX . $table . "_to_attachment WHERE " . $table . "_id = '$id' AND attachment_id = '$attachment_id' ");	
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
		$q = $this->db->query("SELECT * FROM " . DB_PREFIX . "attachments WHERE filename = '" . $name . "'");
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
			if(move_uploaded_file($tmp_name, DIR_PDFS . $name) ){
				$this->db->query("INSERT INTO " . DB_PREFIX . "attachments SET filename = '$name', display_name = '$name' ");
				return true;
			}
		}else{
			return $errors;
		}
	}

	public function getAttachedProducts($attachment_id, $table='product'){
		$attachment_id	=	(int)$attachment_id;
		$return_array	=	array();
		// Run an ugly query to grab data needed.
		$query	=	$this->db->query("SELECT * FROM " . DB_PREFIX . $table . "_to_attachment 
										LEFT JOIN " . DB_PREFIX . $table . " ON " . DB_PREFIX . $table . "_to_attachment." . $table . "_id = " . DB_PREFIX . $table . "." . $table . "_id 
										LEFT JOIN " . DB_PREFIX . $table . "_description ON " . DB_PREFIX . $table . "." . $table . "_id = " . DB_PREFIX . $table . "_description." . $table . "_id
										WHERE " . DB_PREFIX . $table . "_to_attachment.attachment_id = '$attachment_id'");
		// If we have results... PROCEED!!!
		if( $query->num_rows > 0 ){
			foreach ($query->rows as $row) {
				$product_name	=	$row['name'];
				$id				=	$row[$table . '_id'];

				$return_array[]	=	array(
					'product_name'		=>	$product_name,
					'id'				=>	$id,
					'attachment_id'		=>	$attachment_id,
					'unattach_params'	=>	"&" . $table . "_id=$id&attachment_id=$attachment_id&admin_section=" . $table . "&redirect_to_module=true"
					);
			}
		}
		return $return_array;
	}

	// Create the needed tables on install. These do NOT, repeat NOT, get deleted on uninstall
	public function createTable(){
		$query = $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "attachments (attachment_id INT(11) AUTO_INCREMENT, filename VARCHAR(255), display_name VARCHAR(255), PRIMARY KEY (attachment_id))");
		$query = $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "product_to_attachment (product_to_attachment_id INT(11) AUTO_INCREMENT, product_id INT(11), attachment_id INT(11), PRIMARY KEY (product_to_attachment_id))");
		$query = $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "category_to_attachment (category_to_attachment_id INT(11) AUTO_INCREMENT, category_id INT(11), attachment_id INT(11), PRIMARY KEY (category_to_attachment_id))");
	}

}
?>
