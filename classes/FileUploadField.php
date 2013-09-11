<?php namespace components\media\classes; if(!defined('TX')) die('No direct access.');

use \dependencies\forms\BaseFormField;

class FileUploadField extends BaseFormField
{
  
  /**
   * Outputs this field to the output stream.
   * 
   * @param array $options An optional set of options to further customize the rendering of this field.
   */
  public function render(array $options=array())
  {
    
    parent::render($options);
    
    //Include uploading module.
    tx('Component')->modules('media')->get_html('file_upload_module');
    
    //Get some values up front.
    $field_id = uniqid($this->form_id.'_file_');
    
    $value = $this->insert_value ? $this->value : '';
    $file = $this->insert_value ? tx('Sql')->table('media', 'Files')->pk($value)->execute_single() : Data();
    $has_file = $file->id->is_set();
    
    ?>
    <div id="<?php echo $field_id; ?>" class="ctrlHolder file-upload-field for_<?php echo $this->column_name; ?>">
      <label><?php __($this->model->component(), $this->title); ?></label>
      <div class="preview-file">
        <?php if($has_file){ ?>
          <a href="<?php echo $file->get_abs_filename(); ?>" target="_blank"><?php echo $file->name; ?></a>
        <?php } ?>
      </div>
      <input type="hidden" class="hidden-file-id" name="<?php echo $this->column_name; ?>" value="<?php echo $value; ?>" />
    </div>
    
    <script type="text/javascript">
    jQuery(function($){
      
      //File replacement.
      var $fileField = $("#<?php echo $field_id; ?>")
        , $previewFile = $fileField.find('.preview-file')
        , $hiddenIdField = $fileField.find('.hidden-file-id');
      var options = {
        maxFileSize: '20mb',
        singleFile: true,
        callbacks: {
          
          serverFileIdReport: function(up, ids, file_id){
            $hiddenIdField.val(file_id);
            $previewFile.append(up.files[0].name+'<br />')
          }
          
        }
      };
      
      //Initialize the uploaders.
      $fileField.txMediaFileUploader(options);
      
    });
    </script>
    
    <?php
    
  }
  
}
