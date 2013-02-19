<?php namespace components\media\classes; if(!defined('TX')) die('No direct access.');

use \dependencies\forms\BaseFormField;

class ImageUploadField extends BaseFormField
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
    tx('Component')->modules('media')->get_html('image_upload_module');
    
    //Get some values up front.
    $field_id = uniqid($this->form_id.'_image_');
    
    $value = $this->insert_value ? $this->value : '';
    $image = $this->insert_value ? tx('Sql')->table('media', 'Images')->pk($value)->execute_single() : Data();
    $has_image = $image->id->is_set();
    $filters = isset($options['image_preview_filters'][$this->column_name]) && is_array($options['image_preview_filters'][$this->column_name]) ?
      $options['image_preview_filters'][$this->column_name] : array('resize_width'=>250);
    
    ?>
    <div id="<?php echo $field_id; ?>" class="ctrlHolder image-upload-field for_<?php echo $this->column_name; ?>">
      <label><?php __($this->model->component(), $this->title); ?></label>
      <div class="preview-image-background">
        <img class="preview-image"
            <?php if($has_image){ ?>
              src="<?php echo $image->generate_url($filters); ?>"
            <?php } ?>
          />
      </div>
      <input type="hidden" class="hidden-image-id" name="<?php echo $this->column_name; ?>" value="<?php echo $value; ?>" />
    </div>
    
    <script type="text/javascript">
    jQuery(function($){
      
      //Image replacement.
      var $imageField = $("#<?php echo $field_id; ?>")
        , $previewImage = $imageField.find('.preview-image')
        , $hiddenIdField = $imageField.find('.hidden-image-id');
      var options = {
        maxFileSize: '20mb',
        singleFile: true,
        callbacks: {
          
          serverFileIdReport: function(up, ids, file_id){
            $hiddenIdField.val(file_id);
            $.rest('GET', "<?php echo url('?rest=media/generate_url/',1); ?>"+file_id, {
              filters: <?php echo Data($filters)->as_json(); ?>
            })
            .done(function(result){
              $previewImage.attr("src", result.url).show();
            });
          }
          
        }
      };
      
      //Initialize the uploaders.
      $imageField.txMediaImageUploader(options);
      
    });
    </script>
    
    <?php
    
  }
  
}
