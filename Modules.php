<?php namespace components\media; if(!defined('TX')) die('No direct access.');

class Modules extends \dependencies\BaseViews
{
  
  protected
    $default_permission = 2,
    $permissions = array(
      'image_uploader' => 0,
      'image_upload_module' => 0,
      'file_upload_module' => 0
    );
  
  protected function image_uploader($options)
  {
    
    //Create resulting settings
    $valueset = Data();
    
    //Insert a salt into the valueset
    $valueset->salt = tx('Security')->random_string(20);
    
    //See if we use auto uploading
    $valueset->auto_upload->set($options->auto_upload->is_true());
    
    //Set the max file size
    $valueset->max_file_size->set($options->max_file_size->otherwise('10mb'));
    
    //See if we're using the default html
    $options->insert_html->is('set')
      
      ->success(function()use($options, $valueset){
        //When using default html, generate the ID's
        $valueset->use_default_html->set(true);
        $valueset->ids->set(array(
          'main' => $valueset->salt.'-container',
          'header' => $valueset->salt.'-header',
          'drop' => $valueset->salt.'-drop',
          'filelist' => $valueset->salt.'-filelist',
          'upload' => $valueset->salt.'-upload',
          'browse' => $valueset->salt.'-browse'
        ));
        $valueset->insert_html->set(array(
          'header' => 'Upload images',
          'drop' => 'Drop images here.',
          'browse' => 'Browse',
          'upload' => 'Upload'
        ));
        $valueset->insert_html->merge($options->insert_html);
      })
      
      ->failure(function()use($options, $valueset){
        //If default html is not used a lot of ids should be defined.
        $valueset->use_default_html->set(false);
        $options->ids
          ->main->validate('$options->ids->main', array('required', 'string', 'no_html'))->back()
          ->filelist->validate('$options->ids->filelist', array('required', 'string', 'no_html'))->back()
          ->browse->validate('$options->ids->browse', array('required', 'string', 'no_html'))->back()
          ->is($valueset->auto_upload->is_false(), function($ids){
            $ids->upload->validate('$options->ids->upload', array('required', 'string', 'no_html'))->back();
          });
        
        $options->ids->moveto($valueset->ids);
      });
    
    //Validate callbacks that have been provided
    $options->callbacks->each(function($callback)use($valueset){
      $callback->validate('$options->callbacks->'.$callback->key(), array('string', 'no_html', 'javascript_variable_name'))->back();
      $callback->moveto($valueset->callbacks->{$callback->key()});
    });
    
    return $valueset;
    
  }
  
  protected function image_upload_module($options)
  {
    
    //Write to output buffer so it only gets included once.
    tx('Ob')->script('media_image_upload_js');
    
    //Plupload plugin.
    echo load_plugin('plupload');
    
    //The image upload script (section).
    ?><script type="text/javascript" src="<?php echo url('?section=media/image_upload_js',1); ?>"></script><?php
    
    //End of output buffer section.
    tx('Ob')->end();
    
  }
  
  protected function file_upload_module($options)
  {
    
    //Write to output buffer so it only gets included once.
    tx('Ob')->script('media_file_upload_js');
    
    //Plupload plugin.
    echo load_plugin('plupload');
    
    //The image upload script (section).
    ?><script type="text/javascript" src="<?php echo url('?section=media/file_upload_js',1); ?>"></script><?php
    
    //End of output buffer section.
    tx('Ob')->end();
    
  }
  
}
