<?php echo $header; ?>
<div id="content">
  <div class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
    <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
    <?php } ?>
  </div>
  <?php if ($error_warning) { ?>
    <div class="warning"><?php echo $error_warning; ?></div>
  <?php } ?>
  <?php if(isset($success)): ?>
    <div class="success"><?php echo $success; ?></div>
  <?php endif; ?>
  <div class="box">
    <div class="heading">
      <h1><img src="view/image/module.png" alt="" /> <?php echo $heading_title; ?></h1>
      <div class="buttons"><a onclick="$('#form').submit();" class="button"><?php echo $button_save; ?></a><a href="<?php echo $cancel; ?>" class="button"><?php echo $button_cancel; ?></a></div>
    </div>
    <div class="content">
      <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
        <table class="list">
          <tbody>
            <tr>
              <td class="left"><label>Upload New PDF: <input type="file" name="pdf_attachment" ></label><a onclick="$('#form').submit();" class="button"><?php echo $button_add_pdf; ?></a></td>
            </tr>
          </tbody>
        </table>
        <table id="module" class="list">
          <thead>
            <tr>
              <td width="200px" class="left"><?php echo $entry_pdf_displayname; ?></td>
              <td class="left"><?php echo $entry_pdf_filename; ?></td>
              <td class="left"><?php echo $entry_num_attached; ?></td>
              <td class="left"><?php echo $entry_products_attached_to; ?></td>
              <td width="200px" class="left"><?php echo $entry_delete_pdf; ?></td>
            </tr>
          </thead>
          <?php $module_row = 0; ?>
          <?php foreach ($product_pdfs as $pdf) { 
            extract($pdf);
            ?>
          <tbody>
            <tr>
              <td width="200px" class="left">
                <input type="text" name="attachments[<?php echo $pdf_id; ?>]" value="<?php echo $display_name; ?>">
              </td>
              <td class="left">
                <span><?php echo $filename; ?></span>
              </td>
              <td class="left">
                <span><?php echo $num_attached; ?></span>
              </td>
              <td class="left">
                  <?php 
                  if( isset($attached_products) && !empty($attached_products) ):
                    foreach ($attached_products as $prod):
                    extract($prod);
                  ?>
                  <p><a href="<?php echo $product_update_url; ?>&product_id=<?php echo $product_id; ?>"><?php echo $product_name; ?></a>&nbsp;<a class="js-remove-pdf-button" href="<?php echo $pdf_remove_action.$unattach_params; ?>">[Unattach]</a></p>
                  <?php endforeach; 
                  ?>
                  <p><a href="<?php echo $product_url; ?>">Attach More Products</a></p>
                <?php
                else:
                ?>
                  <p><a href="<?php echo $product_url; ?>">Attach Some Products</a></p>
                <?php
                endif; 
                ?>
              </td>
              <td width="200px" class="left"><a class="button js-delete-pdf" href="<?php echo $delete_url; ?>&pdf_id=<?php echo $pdf_id; ?>">Delete PDF</a></td>
            </tr>
          </tbody>
          <?php $module_row++; ?>
          <?php } ?>
        </table>
      </form>
    </div>
  </div>
</div>
<script><!--
$('.js-delete-pdf').on('click', function(e){
  var $this = $(this);

  if( confirm('Are you sure you wish to permanantly delete this pdf? Please make sure no products are using it before confirming.') ){
    window.location.href  = $this.attr('href');
  }else{
    return false;
  }
  // remove it from table
  // remove all associactions in the lookup table also.

  e.preventDefault();
});
//--></script>
<script><!--

$('.js-remove-pdf-button').on('click', function(e){
  var $this = $(this);
  var href = $this.attr('href');

  if(confirm('Are you sure you wish to unattach this PDF?')){
    window.location.href  = href;
  }else{
    return false;
  }

  e.preventDefault();
});

//--></script>
<?php echo $footer; ?>
