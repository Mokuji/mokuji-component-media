<?php namespace components\media; if(!defined('TX')) die('No direct access.');

class Sections extends \dependencies\BaseViews
{
  
  protected
    $default_permission = 2,
    $permissions = array(
      'link_image_static' => 0,
      'link_image_dynamic' => 0,
      'image_upload_tmpl' => 0,
      'file_upload_tmpl' => 0,
      'image_upload_js' => 0,
      'file_upload_js' => 0,
      'image' => 0,
      'file' => 0
    );
  
  public function link_image_static()
  {
    
    $path = tx('Data')->get->path->get();
    
    //We're here because .htaccess rewrote. No symlink exists yet for this image.
    //Check if we have permission to make a symlink.
    if(tx('Data')->session->media->image_symlink_permission->{$path}->get() !== tx('Session')->id){
      tx('Logging')->log('Media', 'Static image', 'No permissions to create link '.$path);
      set_status_header(403, 'Access denied');
      exit;
    }
    
    //Output image with the flag to create a symlink.
    echo $this->helper('output_image', array('create_static_symlink'=>true));
    tx('Data')->session->media->image_symlink_permission->{$path}->un_set();
    exit;
    
  }
  
  public function link_image_dynamic()
  {
    
    $path = tx('Data')->get->path->get();
    tx('Logging')->log('Media', 'Dynamic image', $path);
    
    //Does the path start with our session ID?
    $sid = tx('Session')->id;
    if(strpos($path, $sid.'-') === 0){
      
      //Strip it from the path.
      $path = substr($path, strlen($sid)+1);
      
    }
    
    //Deny access if it doesn't.
    else{
      set_status_header(403, 'Access denied');
      exit;
    }
    
    if(tx('Data')->session->media->image_access->{$path}->get() !== tx('Session')->id){
      set_status_header(403, 'Access denied');
      exit;
    }
    
    tx('Data')->get->merge(array('path'=>$path));
    echo $this->helper('output_image');
    exit;
    
  }
  
  protected function image_upload_tmpl()
  {
    return array();
  }

  protected function file_upload_tmpl()
  {
    return array();
  }
  
  protected function image_upload_js($options)
  {
    return array(
      
      'tmpl' => str_replace(array("\n", "\r", '"'), array(' ', '', '\"'), $this->section('image_upload_tmpl')),
      
      'content' => array(
        'header' => __($this->component, 'Upload images', true),
        'drop' => __($this->component, 'Drop images here', true),
        'browse' => __($this->component, 'Browse', true),
        'upload' => __($this->component, 'Upload', true)
      ),
      
      'chunk_size' => min((int)(ini_get('upload_max_filesize')), (int)(ini_get('post_max_size'))).'mb',
      'handle_url' => url('?action=media/upload_image', true),
      'plugin_url' => URL_PLUGINS.'plupload/'
      
    );
  }
    
  protected function file_upload_js($options)
  {
    return array(
      
      'tmpl' => str_replace(array("\n", "\r", '"'), array(' ', '', '\"'), $this->section('file_upload_tmpl')),
      
      'content' => array(
        'header' => __($this->component, 'Upload files', true),
        'drop' => __($this->component, 'Drop files here', true),
        'browse' => __($this->component, 'Browse', true),
        'upload' => __($this->component, 'Upload', true)
      ),
      
      'chunk_size' => min((int)(ini_get('upload_max_filesize')), (int)(ini_get('post_max_size'))).'mb',
      'handle_url' => url('?action=media/upload_file', true),
      'plugin_url' => URL_PLUGINS.'plupload/'
      
    );
  }
  
  protected function image($options){
    throw new \exception\Deprecated('Direct image outputting is no longer supported. Please use generate_url() on an Images model.');
  }
  
  protected function file($data)
  {
    
    //File info from DB.
    $file_info = tx('Sql')->table('media', 'Files')
      ->pk(tx('Data')->get->id)
      ->execute_single()
      ->is('empty', function(){
        throw new \exception\EmptyResult("Supplied file id was not found. ".tx('Data')->get->id->dump());
      });
    
    //File handle.
    $file = tx('File')->file()
      ->from_file($file_info->get_abs_filename());
    
    //See if we need to output download headers.
    if(tx('Data')->get->download->is_set())
      $file->download(array('as' => tx('Data')->get->as->otherwise(null)));
    
    //Otherwise, output the file contents.
    else $file->output();
      
  }
  
}
