<?php namespace components\media\models; if(!defined('TX')) die('No direct access.');

tx('Component')->load('media', 'classes\\ImageUploadField', false);

use \dependencies\RelationType;

class Images extends \dependencies\BaseModel
{
  
  protected static
    $table_name = 'media_images',
    
    $relation_preferences = array(
      RelationType::ForeignKey => '\\components\\media\\classes\\ImageUploadField'
    );
  
  public function get_abs_filename()
  {
    
    return PATH_COMPONENTS.DS.'media'.DS.'uploads'.DS.'images'.DS.$this->filename;
    
  }
  
  public function get_source_file()
  {
    return tx('Sql')->table('media', 'Files')
      ->pk($this->source_file_id)
      ->execute_single();
  }

  /**
  * Generates a url for an image with the supplied arguments.
  *
  * You can set filters using the keys listed and supplying an integer value.
  * You can set options by supplying the listed strings to enable them.
  * Arguments:
  *   $filters
  *     - resize_width => (int)
  *     - resize_height => (int)
  *     - crop_x => (int)
  *     - crop_y => (int)
  *     - crop_width => (int)
  *     - crop_height => (int)
  *     - fill_width => (int)
  *     - fill_height => (int)
  *     - fit_width => (int)
  *     - fit_height => (int)
  *   $options
  *     - disable_sharpen
  *     - download
  *     - no_source_file
  *     - no_cache
  *     - allow_growth
  *     - disallow_shrink
  */
  public function generate_url($filters=array(), $options=array())
  {
    
    //Extract raw values.
    raw($filters, $options);
    
    //Public image?
    $public = $this->is_public->get('boolean');
    
    //Downloading original has an exception, because it could be a file.
    if(empty($filters) && isset($options['download']) && $options['download'] === true){
      
      //In the case of downloading the original, it's possible a source file is defined.
      //Use that instead of the image file, unless it's denied by the options.
      if($this->source_file_id->is_set() && !(isset($options['no_source_file']) && $options['no_source_file'] === true))
        return url('?section=media/file&id='.$this->source_file_id.'&download=1', true);
      
    }
    
    //Create a Rectangle to represent this Image.
    $R = new \dependencies\Rectangle($this->__get('width'), $this->__get('height'));
    
    //Image location.
    $fullname = $this->__get('filename')->get();
    $dot = strrpos($fullname, '.');
    $name = substr($fullname, 0, $dot);
    $ext = substr($fullname, $dot+1);
    $generated_name = (empty($filters) ? '' : 'cache/').$name;
    
    //Translate a "fit" option to resize parameters.
    if(array_try($filters, 'fit_width', 0) > 0 || array_try($filters, 'fit_height', 0) > 0)
    {
      
      //Make it fit.
      $R->fit(array_try($filters, 'fit_width', 0), array_try($filters, 'fit_height', 0));
      
      //Did it resize?
      if($R->width() !== $this->__get('width')->get('int')
      || $R->height() !== $this->__get('height')->get('int')){
        $filters['resize_width'] = $R->width();
        $filters['resize_height'] = $R->height();
      }
      
    }
    
    //Translate a "fill" option to resize and crop parameters.
    elseif(array_try($filters, 'fill_width', 0) > 0 && array_try($filters, 'fill_height', 0) > 0)
    {
      
      //Make it fit.
      $R->contain($filters['fill_width'], $filters['fill_height'], true);
      
      //Did it resize?
      if($R->width() !== $this->__get('width')->get('int') || $R->height() !== $this->__get('height')->get('int')){
        $filters['resize_width'] = $R->width();
        $filters['resize_height'] = $R->height();
      }
      
      //Find out if we need to do a crop.
      if($R->width() > $filters['fill_width'] || $R->height() > $filters['fill_height']){
        
        //See how much needs to be cropped.
        $hDiff = $R->width() - $filters['fill_width'];
        $vDiff = $R->height() - $filters['fill_height'];
        
        //Based on that, find the coordinates we need to start our crop from.
        $x = floor($hDiff / 2);
        $y = floor($vDiff / 2);
        
        //Since we already know the width and height, create the crop filter.
        $filters['crop_x'] = $x;
        $filters['crop_y'] = $y;
        $filters['crop_width'] = $filters['fill_width'];
        $filters['crop_height'] = $filters['fill_height'];
        if(!in_array('allow_growth', $options)) $options[] = 'allow_growth';
        
      }
      
    }
    
    //Reset the Rectangle used for the above 2 images.
    $R->set_width($this->__get('width'))->set_height($this->__get('height'));
    
    //Resize.
    if(array_try($filters, 'resize_width', 0) > 0 || array_try($filters, 'resize_height', 0) > 0)
    {
      
      //Strict resize?
      if(array_try($filters, 'resize_width', 0) > 0 && array_try($filters, 'resize_height', 0) > 0){
        $R->set_width($filters['resize_width'])->set_height($filters['resize_height'])->round();
      }
      
      //Auto-resize based on width?
      if(array_try($filters, 'resize_width', 0) > 0){
        $R->set_width($filters['resize_width'], true)->round();
      }
      
      //Auto-resize based on height?
      elseif(array_try($filters, 'resize_height', 0) > 0){
        $R->set_height($filters['resize_height'], true)->round();
      }
      
      //Generate this part of the URL.
      $generated_name .= "_resize-{$R->width()}-{$R->height()}";
      
    }
    
    //Crop.
    if(array_try($filters, 'crop_x', 0) > 0
    || array_try($filters, 'crop_y', 0) > 0
    || array_try($filters, 'crop_width', 0) > 0
    || array_try($filters, 'crop_height', 0) > 0)
    {
      
      $x = array_try($filters, 'crop_x', 0);
      $y = array_try($filters, 'crop_y', 0);
      $width = array_try($filters, 'crop_width', 0);
      $height = array_try($filters, 'crop_height', 0);
      
      $width = ($width > 0 ? $width : ($this->__get('width')->get('int') - $x));
      $height = ($height > 0 ? $height : ($this->__get('height')->get('int') - $y));
      
      $generated_name .= "_crop-{$x}-{$y}-{$width}-{$height}";
      
    }
    
    //Add extension and create query string from options.
    $generated_name .= ".$ext";
    
    //Create versions of the name.
    $url = '/site/components/media/links/images/'.
      ($public ? (isset($options['download']) ? 'download-static' : 'static') : 'dynamic-'.tx('Session')->id).'-'.$generated_name.
      (empty($options) ? '' : '?').implode('&', $options);
      
    $link = PATH_COMPONENTS.DS.'media'.DS.'links'.DS.'images'.DS.
      ($public ? 'static' : 'dynamic-'.tx('Session')->id).'-'.$generated_name;
    
    if(!$public){
      tx('Data')->session->media->image_access->{$generated_name}->set(tx('Session')->id);
    }elseif($public && !is_link($link)){
      tx('Data')->session->media->image_symlink_permission->{$generated_name}->set(tx('Session')->id);
    }
    
    //Return a URL object.
    return url($url, true);

  }
  
  public function delete()
  {
    
    //Delete the image links and files.
    tx('Component')->helpers('media')->_call('delete_image_links', array($this->filename));
    tx('Component')->helpers('media')->_call('delete_image_file', array($this->filename));
    
    //Delete the source file if applicable.
    $this->source_file->not('empty', function($source){
      $source->delete();
    });
    
    return parent::delete();
    
  }

}
