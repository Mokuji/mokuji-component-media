<?php namespace components\media; if(!defined('TX')) die('No direct access.');
echo load_plugin('plupload');
$plugin = URL_PLUGINS.'plupload/';

//If we need to insert the default html
$image_uploader->use_default_html->is('true', function()use($image_uploader){

  //Insert default html structure
  ?>
  <div id="<?php echo $image_uploader->ids->main; ?>" class="plupload-container">
    <h3 id="<?php echo $image_uploader->ids->header; ?>" class="header"><?php echo $image_uploader->insert_html->header; ?></h3>
    <div id="<?php echo $image_uploader->ids->drop; ?>" class="drop">
      <div id="<?php echo $image_uploader->ids->filelist; ?>" class="filelist"></div>
      <div class="drag-here"><?php echo $image_uploader->insert_html->drop; ?></div>
    </div>
    <div class="buttonHolder">
      <a id="<?php echo $image_uploader->ids->browse; ?>" class="browse" href="#"><?php echo $image_uploader->insert_html->browse; ?></a>
      <?php
      $image_uploader->auto_upload->is('false', function()use($image_uploader){
        ?>
        <a id="<?php echo $image_uploader->ids->upload; ?>" class="upload" href="#"><?php echo $image_uploader->insert_html->upload; ?></a>
        <?php
      });
      ?>
    </div>
  </div>
  <?php
  
});

?>
<script type="text/javascript">

$(function() {
  
  //Define the id's as given by the server.
  var ids = {
    'main'      : '<?php echo $image_uploader->ids->main; ?>',
    'header'    : '<?php echo $image_uploader->ids->header; ?>',
    'drop'      : '<?php echo $image_uploader->ids->drop; ?>',
    'filelist'  : '<?php echo $image_uploader->ids->filelist; ?>',
    'upload'    : '<?php echo $image_uploader->ids->upload; ?>',
    'browse'    : '<?php echo $image_uploader->ids->browse; ?>'
  };
  
  /*=============================================
  ==> Initialize uploader
  =============================================*/
  var uploader_<?php echo $image_uploader->salt; ?> = new plupload.Uploader({
    runtimes : 'html5,flash,html4',
    browse_button : ids.browse,
    container : ids.main,
    <?php
      $image_uploader->ids->drop->is('set', function(){
        echo 'drop_element : ids.drop,';
      });
    ?>
    max_file_size : '<?php echo $image_uploader->max_file_size; ?>',
    chunk_size : '<?php echo min((int)(ini_get('upload_max_filesize')), (int)(ini_get('post_max_size'))); ?>mb',
    url : '<?php echo url('?action=media/upload_image', true); ?>',
    flash_swf_url : '<?php echo $plugin; ?>js/plupload.flash.swf',
    silverlight_xap_url : '<?php echo $plugin; ?>js/plupload.silverlight.xap',
    multi_selection: <?php echo $data->single_file->is_true() ? 'true' : 'false'; ?>,
    filters : [
      <?php
      if($image_uploader->filters->is_parent())
      {
        echo $image_uploader->filters->map(function($filter){
          return '{title : "'.$filter->title.'", extensions : "'.$filter->extensions.'"}';
        })->join(',');
      }
      else{
        echo '{title : "Image files", extensions : "jpg,jpeg,gif,png"}';
      }
      ?>
    ]
  });

  /*=============================================
  ==> Init event
  =============================================*/
  uploader_<?php echo $image_uploader->salt; ?>.bind('Init', function(up, params) {
    <?php echo $image_uploader->callbacks->Init->otherwise('plupload_default_init').'(up, ids, params);'; ?>
  });

  <?php  
  //If we don't use auto_uploading add a click event for the upload button
  $image_uploader->auto_upload->is('false', function()use($image_uploader){
    ?>
    /*=============================================
    ==> Upload button click
    =============================================*/
    $('#<?php echo $image_uploader->ids->upload; ?>').click(function(e) {
      uploader_<?php echo $image_uploader->salt; ?>.start();
      e.preventDefault();
    });
    <?php
  });
  ?>

  uploader_<?php echo $image_uploader->salt; ?>.init();

  /*=============================================
  ==> Files added event
  =============================================*/
  uploader_<?php echo $image_uploader->salt; ?>.bind('FilesAdded', function(up, files) {
    <?php echo $image_uploader->callbacks->FilesAdded->otherwise('plupload_default_files_added').'(up, ids, files);'; ?>
    up.refresh(); // Reposition Flash/Silverlight
    
    <?php
    //If single file mode, remove all but the last file.
    if($data->single_file->is_true()){
      
      ?>
      //Single file mode, remove all but the last file.
      plupload.each(files, function(file){
        if(up.files.length > 1)
          up.removeFile(file);
      });
      <?php
      
    }
    ?>
    
    <?php
    //Autostart if wanted
    if($image_uploader->auto_upload->otherwise(false))
      echo 'up.start();';
    ?>
    
  });

  /*=============================================
  ==> Upload progress event
  =============================================*/
  uploader_<?php echo $image_uploader->salt; ?>.bind('UploadProgress', function(up, file) {
    <?php echo $image_uploader->callbacks->UploadProgress->otherwise('plupload_default_upload_progress').'(up, ids, file);'; ?>
  });

  /*=============================================
  ==> Error event
  =============================================*/
  uploader_<?php echo $image_uploader->salt; ?>.bind('Error', function(up, err) {
    <?php echo $image_uploader->callbacks->Error->otherwise('plupload_default_error').'(up, ids, err);'; ?>
    up.refresh(); // Reposition Flash/Silverlight
  });
  
  /*=============================================
  ==> File uploaded event
  =============================================*/
  uploader_<?php echo $image_uploader->salt; ?>.bind('FileUploaded', function(up, file, response) {
    <?php echo $image_uploader->callbacks->FileUploaded->otherwise('plupload_default_file_uploaded').'(up, ids, file, response);'; ?>
  });
  
  /*=============================================
  ==> Server file id report event
  =============================================*/
  uploader_<?php echo $image_uploader->salt; ?>.bind('ServerFileIdReport', function(up, file_id){
    <?php echo $image_uploader->callbacks->ServerFileIdReport->otherwise('plupload_default_server_file_id_report').'(up, ids, file_id);'; ?>
  });
});
</script>
