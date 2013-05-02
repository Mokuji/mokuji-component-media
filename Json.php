<?php namespace components\media; if(!defined('TX')) die('No direct access.');

class Json extends \dependencies\BaseComponent
{
  
  protected
    $default_permission = 2,
    $permissions = array(
      'get_generate_url' => 0
    );
  
  protected function delete_image($data, $params)
  {
    
    $this->helper('delete_image', $params[0]);
    
  }
  
  public function get_generate_url($data, $params)
  {
    
    return array(
      'url' => $this->table('Images')
        ->pk($params[0])
        ->execute_single()
        ->generate_url($data->filters->as_array(), $data->options->as_array())
        ->output
    );
    
  }
  
}
