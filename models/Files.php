<?php namespace components\media\models; if(!defined('TX')) die('No direct access.');

tx('Component')->load('media', 'classes\\FileUploadField', false);

use \dependencies\RelationType;

class Files extends \dependencies\BaseModel
{
  
  protected static

    $table_name = 'media_files',

    $relation_preferences = array(
      RelationType::ForeignKey => '\\components\\media\\classes\\FileUploadField'
    );



  public function get_abs_filename()
  {
    
    return PATH_COMPONENTS.DS.'media'.DS.'uploads'.DS.'files'.DS.$this->filename;
    
  }
  
  public function delete()
  {
    
    //Delete the files.
    tx('Component')->helpers('media')->delete_file($this->filename);
    
    return parent::delete();
    
  }
  
}
