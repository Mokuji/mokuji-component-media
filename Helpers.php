<?php namespace components\media; if(!defined('TX')) die('No direct access.');

class Helpers extends \dependencies\BaseViews
{
  
  protected
    $permissions = array(
      'output_image' => 0
    );
  
  /**
   * Outputs an image based on path input.
   * @param  boolean $options->create_static_symlink Whether or not to create a static symlink to the file being cached.
   * @return void
   */
  public function output_image($options=array())
  {
    
    $options = Data($options);
    
    //Get the given path.
    $p = tx('Data')->get->path->get();
    
    //Are we getting a cached image?
    if(strpos($p, 'cache/') === 0)
    {
      
      //Create the filename.
      $filename = basename($p);
      
      //Create parameters.
      $resize = Data(array());
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
    
    //If this is not a cached image, the path is simple.
    else{
      
      //Create parameters.
      $resize = Data(false);
      $crop = Data(false);
      
      //Create the filename.
      $filename = basename($p);
      
      //Use the file name to create a path.
      $path = PATH_COMPONENTS.DS.'media'.DS.'uploads'.DS.'images'.DS.$filename;
      
      //Test if the new path points to an existing file.
      if(!is_file($path)){
        set_status_header(404, sprintf('Image "%s" not found.', $filename));
        exit;
      }
      
    }
    
    //Since we will output an image. Switch to read-only mode for the session to prevent bottlenecks.
    tx('Session')->close();
    
    $image = tx('File')->image()
      ->use_cache(!tx('Data')->get->no_cache->is_set())
      ->from_file($path)
      ->allow_growth(tx('Data')->get->allow_growth->is_set())
      ->allow_shrink(!tx('Data')->get->disallow_shrink->is_set())
      ->sharpening(!tx('Data')->get->disable_sharpen->is_set());
    
    if(!$resize->is_false())
      $image->resize($resize->{0}, $resize->{1});
    
    if(!$crop->is_false())
      $image->crop($crop->{0}, $crop->{1}, $crop->{2}, $crop->{3});
    
    //See if we should create a public symlink to the file.
    if($options->create_static_symlink->is_true() && $image->has_diverted() === false){
      
      $target = PATH_COMPONENTS.DS.'media'.DS.'uploads'.DS.'images'.DS.$p;
      $link = PATH_COMPONENTS.DS.'media'.DS.'links'.DS.'images'.DS.'static-'.$p;
      
      //Ensure the folder for the link and the symlink itself are present.
      @mkdir(dirname($link), 0777, true);
      if(!@symlink($target, $link)){
        tx('Logging')->log('Media', 'Static symlink', 'Creation failed for: '.$target.' -> '.$path);
      }
      
    }
    
    set_exception_handler('exception_handler_image');
    if(tx('Data')->get->download->is_set())
      $image->download(array('as' => tx('Data')->get->as->otherwise(null)));
    else
      $image->output();
    
  }
  
  /**
   * Clears the cache of static links.
   * @return void
   */
  public function delete_image_links($filename)
  {
    
    //Clean the filename for security reasons.
    $filename = preg_replace('/[^\w\._]+/', '', $filename);
    
    //Get extension and filename.
    $ext_pos = strrpos($filename, '.');
    $filename_raw = substr($filename, 0, $ext_pos);
    $extension = substr($filename, $ext_pos+1);
    
    //Find target directory.
    $dir = PATH_COMPONENTS.DS.$this->component.DS.'links'.DS.'images'.DS;
    
    //Delete the original file link.
    @unlink($dir.'static-'.$filename);
    
    //Find all of it's buddies and kill 'em dead. >:3
    foreach(glob($dir.'static-cache'.DS.$filename_raw.'*.'.$extension) as $buddy){
      @unlink($buddy);
    }
    
  }
  
  public function delete_image($id)
  {
    
    $image = tx('Sql')
      ->table('media', 'Images')
      ->pk($id)
      ->execute_single()
      
      ->is('empty', function(){
        throw new \exception\NotFound('An image with this ID was not found');
      });
    
    //Actually delete the image file and cached versions.
    //And delete from the database.
    $image->delete();
    
  }
  
  public function delete_image_file($filename)
  {
    
    //Clean the filename for security reasons.
    $filename = preg_replace('/[^\w\._]+/', '', $filename);
    
    //Get extension and filename.
    $ext_pos = strrpos($filename, '.');
    $filename_raw = substr($filename, 0, $ext_pos);
    $extension = substr($filename, $ext_pos+1);
    
    //Don't delete the default icon.
    if($filename_raw == 'x_default')
      return;
    
    //Find target directory.
    $dir = PATH_COMPONENTS.DS.$this->component.DS.'uploads'.DS.'images'.DS;
    
    //See if the original is found here.
    if(!is_file($dir.$filename))
      throw new \exception\NotFound('The original image was not found.');
    
    //Delete the original file.
    @unlink($dir.$filename);
    
    //Find all of it's buddies and kill 'em dead. >:3
    foreach(glob($dir.'cache'.DS.$filename_raw.'*.'.$extension) as $buddy){
      @unlink($buddy);
    }
    
  }
  
  public function delete_file($filename)
  {
    
    //Clean the filename for security reasons.
    $filename = preg_replace('/[^\w\._]+/', '', $filename);
    
    //Get extension and filename.
    $ext_pos = strrpos($filename, '.');
    $filename_raw = substr($filename, 0, $ext_pos);
    $extension = substr($filename, $ext_pos+1);
    
    //Find target directory.
    $dir = PATH_COMPONENTS.DS.$this->component.DS.'uploads'.DS.'files'.DS;
    
    //See if the original is found here.
    if(!is_file($dir.$filename))
      throw new \exception\NotFound('The original file was not found.');
    
    //Delete the original file.
    @unlink($dir.$filename);
    
  }
  
  protected function download_remote_image($data)
  {
    
    $data = $data->having('url', 'name')
      ->url->validate('Remote image URL', array('required', 'url'))->back()
      ->name->validate('Image name', array('string', 'not_empty'))->back();
    
    // Settings
    $upload_dir = PATH_COMPONENTS.DS.$this->component.DS.'uploads'.DS;
    $target_dir = $upload_dir.'images'.DS;
    
    // Limit execution time to 30 seconds from here.
    @set_time_limit(30);
    
    //Get image by curl.
    $image = curl_call($data->url);
    
    //Get extension by mime.
    switch($image['type']){
      
      case 'image/jpg':
      case 'image/jpeg':
        $extension = '.jpg';
        break;
      
      case 'image/png':
        $extension = '.png';
        break;
      
      case 'image/gif':
        $extension = '.gif';
        break;
      
      default:
        throw new \exception\InvalidArgument('Invalid image type \''.$image['type'].'\', accepted are: jpg, jpeg, png and gif');
        break;
      
    }
    
    // Create target dirs
    if (!file_exists($upload_dir)){
      @mkdir($upload_dir);
    }
    if (!file_exists($target_dir)){
      @mkdir($target_dir);
    }
    
    //Create unique filename.
    do{
      $filename = tx('Security')->random_string(64);
    }
    while(file_exists($target_dir.$filename.$extension));
    
    //Write data to file.
    $out = fopen($target_dir.$filename.$extension, "wb");
    
    //If opening file failed.
    if(!$out)
      throw new \exception\Exception('Unable to write file in upload directory');
    
    //Write and close.
    fwrite($out, $image['data']);
    fclose($out);
    
    //Store image in database and return that.
    return $this->model('Images')
      ->filename->set($filename.$extension)->back()
      ->name->set($data->name->otherwise($filename))->back()
      ->save();
    
  }
  
}
