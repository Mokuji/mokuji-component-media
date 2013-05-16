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
    echo $this->section('image', array('create_static_symlink'=>true));
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
    echo $this->section('image');
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
  
  protected function image($options)
  {
    
    //Since we will output an image. Switch to read-only mode for the session to prevent bottlenecks.
    tx('Session')->close();
    
    //Has a direct path been given? (usually by .htaccess)
    if(tx('Data')->get->path->is_set())
    {
      
      //Get the given path.
      $p = tx('Data')->get->path->get();
      
      //Are we getting a cached image?
      if(strpos($p, 'cache/') === 0)
      {
        
        //Create the filename.
        $filename = basename($p);
        
        //Create parameters.
        $resize = Data(array());
        $fit = Data(array());
        $fill = Data(array());
        $crop = Data(array());
        
        //Parse the name for resize parameters.
        $filename = preg_replace_callback('~_resize-(?<width>\d+)-(?<height>\d+)~', function($result)use(&$resize){
          $resize->{0} = $result['width'];
          $resize->{1} = $result['height'];
          return '';
        }, $filename);
        
        //Parse the name for crop parameters.
        $filename = preg_replace_callback('~_crop-(?<x>\d+)-(?<y>\d+)-(?<width>\d+)-(?<height>\d+)~', function($result)use(&$crop){
          $crop->{0} = $result['x'];
          $crop->{1} = $result['y'];
          $crop->{2} = $result['width'];
          $crop->{3} = $result['height'];
          return '';
        }, $filename);
        
        //Use the remaining file name to create a path.
        $path = PATH_COMPONENTS.DS.'media'.DS.'uploads'.DS.'images'.DS.$filename;
        
        //Test if the new path points to an existing file.
        if(!is_file($path)){
          set_status_header(404, sprintf('Image "%s" not found.', $filename));
          exit;
        }
        
      }
      
      //If this is not a cached image, the image just actually does not exist.
      else{
        set_status_header(404, sprintf('Image "%s" not found.', tx('Data')->get->path));
        exit;
      }
      
    }
    
    //No path given. Assume ID has been given and fetch path information from the database.
    else
    {
     
      $image = tx('Sql')->table('media', 'Images')
                ->pk(tx('Data')->get->id)
                ->execute_single();
      
      if($image->is_empty())
        throw new \exception\EmptyResult("Supplied image id was not found. ".tx('Data')->get->id->dump());
      
      $resize = tx('Data')->get->resize->split('/');
      $crop = tx('Data')->get->crop->split('/');
      $filename = $image->filename;
      $path = $image->get_abs_filename();
      
    }
    
    //See if we should create a public symlink to the file.
    if($options->create_static_symlink->is_true()){
      
      $target = PATH_COMPONENTS.DS.'media'.DS.'uploads'.DS.'images'.DS.$p;
      $link = PATH_COMPONENTS.DS.'media'.DS.'links'.DS.'images'.DS.'static-'.$p;
      
      //Ensure the folder for the link and the symlink itself are present.
      @mkdir(dirname($link), 0777, true);
      if(!@symlink($target, $link)){
        tx('Logging')->log('Media', 'Static symlink', 'Creation failed for: '.$target.' -> '.$path);
      }
      
    }
    
    return array(
      'download' => tx('Data')->get->download->is_set(),
      'image' => tx('File')->image()
                  ->use_cache(!tx('Data')->get->no_cache->is_set())
                  ->from_file($path)
                  ->allow_growth(tx('Data')->get->allow_growth->is_set())
                  ->allow_shrink(!tx('Data')->get->disallow_shrink->is_set())
                  ->sharpening(!tx('Data')->get->disable_sharpen->is_set())
                  ->resize($resize->{0}, $resize->{1})
                  ->crop($crop->{0}, $crop->{1}, $crop->{2}, $crop->{3})
    );
    
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
