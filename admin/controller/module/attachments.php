<?php
class ControllerModuleAttachments extends Controller {
	private $error = array(); 
	
	public function index() {
		$this->language->load('module/attachments');
		$this->load->model('module/attachments');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->data['delete_url'] 			= $this->url->link('module/attachments/delete', 'token=' . $this->session->data['token'], 'SSL');
		$this->data['product_url']			=	$this->url->link('catalog/product', 'token=' . $this->session->data['token'], 'SSL');
		$this->data['product_update_url']	=	$this->url->link('catalog/product/update', 'token=' . $this->session->data['token'], 'SSL');
		$this->data['category_url']			=	$this->url->link('catalog/category', 'token=' . $this->session->data['token'], 'SSL');
		$this->data['category_update_url']	=	$this->url->link('catalog/category/update', 'token=' . $this->session->data['token'], 'SSL');
		$this->data['pdf_remove_action'] 	= $this->url->link('module/attachments/removepdf', 'test=moo&token=' . $this->session->data['token'], 'SSL');

		$this->data['breadcrumbs'] = array();

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => false
   		);

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_module'),
			'href'      => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => ' :: '
   		);
		
   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('module/attachments', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => ' :: '
   		);
		
		$this->data['error_warning']	=	'';
		$this->data['action']	=	'';

		if( isset($_FILES) && !empty($_FILES) && $_FILES['pdf_attachment']['error'] != '4' ){
			$uploaded_pdf	=	$this->model_module_attachments->uploadPdf($_FILES);
		}

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			if( isset($this->request->post['attachments']) ){
				$this->model_module_attachments->updatePdfs($this->request->post['attachments']);
			}
			$this->session->data['success'] = $this->language->get('text_success');				
		}

		$this->data['heading_title']	=	$this->language->get('heading_title');

		$this->data['entry_pdf_displayname']	=	$this->language->get('entry_pdf_displayname');
		$this->data['entry_pdf_filename']		=	$this->language->get('entry_pdf_filename');
		$this->data['entry_num_attached']		=	$this->language->get('entry_num_attached');
		$this->data['entry_products_attached_to']	=	$this->language->get('entry_products_attached_to');
		$this->data['entry_categories_attached_to']	=	$this->language->get('entry_categories_attached_to');
		$this->data['entry_delete_pdf']			=	$this->language->get('entry_delete_pdf');

		$this->data['button_add_pdf']			=	$this->language->get('button_add_pdf');
		$this->data['button_save']				=	$this->language->get('button_save');
		$this->data['button_cancel']			=	$this->language->get('button_cancel');

		if( isset($uploaded_pdf) ) {
			if(is_array($uploaded_pdf) ){
				$upload_error_string	=	'';
				foreach ($uploaded_pdf as $upload_error) {
					$upload_error_string.=$upload_error;
				}
				$this->error['warning']	=	$upload_error_string;
			}else{
				$this->session->data['success'] = 'PDF Successfully Uploaded.';
			}
		}


		if (isset($this->session->data['success']) && $this->data['error_warning'] == '' ) {
			$this->data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		}

		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}
		
		$this->data['attachments']	=	$this->model_module_attachments->getPdfsForAdmin();

		$this->template = 'module/attachments.tpl';
		$this->children = array(
			'common/header',
			'common/footer'
		);
				
		$this->response->setOutput($this->render());

	}

	public function delete(){
		if( $this->request->get['attachment_id'] ){
			$attachment_id	=	$this->request->get['attachment_id'];
			if( !$this->validate() ){
				$this->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
			}else{
				
				if( $q = $this->db->query("SELECT * FROM " . DB_PREFIX . "attachments WHERE attachment_id = '$attachment_id' ") ){
					$file_to_delete	=	DIR_PDFS . $q->row['filename'];
					$this->db->query("DELETE FROM " . DB_PREFIX . "attachments WHERE attachment_id = '$attachment_id' ");
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_attachment WHERE attachment_id = '$attachment_id' ");
					$this->db->query("DELETE FROM " . DB_PREFIX . "category_to_attachment WHERE attachment_id = '$attachment_id' ");
					unlink($file_to_delete);
					$this->session->data['success']	=	"PDF Successfully Deleted. Any previous associations with products have also been removed.";
				}
			}

		}
		$this->redirect($this->url->link('module/attachments', 'token=' . $this->session->data['token'], 'SSL'));
	}

	public function removepdf(){
			
		if( !$this->validate() ){
			$this->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
		}else{
			
			$admin_section = $this->request->get['admin_section'];
			
			if( isset($this->request->get[$admin_section . '_id']) && isset($this->request->get['attachment_id']) ){
				$this->load->model('module/attachments');
				// Detach the pdf from the product

				$this->model_module_attachments->pdfDetach( $this->request->get[$admin_section . '_id'], $this->request->get['attachment_id'], $admin_section );

				// Redirect based on where the user has used the detach function.
				if($admin_section == 'product'){
					$this->redirect($this->url->link('catalog/product/update', 'token=' . $this->session->data['token'] . '&product_id=' . $this->request->get['product_id'], 'SSL'));
				}elseif($admin_section == 'category'){
					$this->redirect($this->url->link('catalog/category/update', 'token=' . $this->session->data['token'] . '&category_id=' . $this->request->get['category_id'], 'SSL'));
				} else {
					$this->redirect($this->url->link('module/attachments', 'token=' . $this->session->data['token'], 'SSL'));
				}
			} else {
				$this->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
			}

		}
	}

	public function install() {
    	$this->load->model('module/attachments');
    	
   		// Make the pdf folder in root if user hasn't already done so.
    	$path_to_pdf	=	 DIR_APPLICATION . '../attachments/';
       	if( !is_dir($path_to_pdf) ){

       		if ( !mkdir($path_to_pdf, 0777) ) {

       			die('Failed to make folder');

       		}  		

    	}

    	// Activate the module status
    	$this->load->model('setting/setting');
    	$this->model_setting_setting->editSetting('attachments', array('attachments_status'=>1));

    	// Create the two database tables for the module.
		$this->model_module_attachments->createTable();   	    	

   	}

   	public function uninstall() {
    	$this->load->model('setting/setting');
    	// Change status setting to 0 so wont be displayed
    	$this->model_setting_setting->editSetting('attachments', array('attachments_status'=>0));
   	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'module/attachments')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		if (!$this->error) {
			return true;
		} else {
			return false;
		}	
	}
}
