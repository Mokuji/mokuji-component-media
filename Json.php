<?php namespace components\media; if(!defined('TX')) die('No direct access.');

class Json extends \dependencies\BaseComponent
{
  
  protected
    $permissions = array(
      'delete_image' => 2
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
