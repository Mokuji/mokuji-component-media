<?php namespace components\media; if(!defined('TX')) die('No direct access.');

class Helpers extends \dependencies\BaseViews
{

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
