<?php namespace components\media; if(!defined('TX')) die('No direct access.');

class Helpers extends \dependencies\BaseViews
{
  
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
