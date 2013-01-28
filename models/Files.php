<?php namespace components\media\models; if(!defined('TX')) die('No direct access.');

class Files extends \dependencies\BaseModel
{
  
  protected static
    $table_name = 'media_files';
  
  public function get_abs_filename()
  {
    
    return PATH_COMPONENTS.DS.'media'.DS.'uploads'.DS.'files'.DS.$this->filename;
    
  }
  
  public function delete()
  {
    
    //Delete the files.
    tx('Component')->helpers('media')->_call('delete_file', array($this->filename));
    
    return parent::delete();
    
  }
  
}
