<?php namespace components\media; if(!defined('TX')) die('No direct access.');

class Json extends \dependencies\BaseComponent
{
  
  protected
    $permissions = array(
      'delete_image' => 2
    );
  
  
  protected function delete_image($data, $params)
  {
    
    $image = tx('Sql')
      ->table('media', 'Images')
      ->pk($params->{0})
      ->execute_single()
      
      ->is('empty', function(){
        throw new \exception\NotFound('An image with this ID was not found');
      });
    
    #TODO actually delete the image file and cached versions.
    
    $image->delete();
    
  }
  
}
