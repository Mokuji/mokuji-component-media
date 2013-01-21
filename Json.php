<?php namespace components\media; if(!defined('TX')) die('No direct access.');

class Json extends \dependencies\BaseComponent
{
  
  protected
    $permissions = array(
      'delete_image' => 2
    );
  
  protected function delete_image($data, $params)
  {
    
    $this->helper('delete_image', $params);
    
  }
  
}
