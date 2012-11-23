<?php namespace components\media\models; if(!defined('TX')) die('No direct access.');

class Images extends \dependencies\BaseModel
{

  protected static
    $table_name = 'media_images';

  public function get_abs_filename()
  {
    
    return PATH_COMPONENTS.DS.'media'.DS.'uploads'.DS.'images'.DS.$this->filename;
    
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
  *     - no_cache
  *     - allow_growth
  *     - disallow_shrink
  */
  public function generate_url($filters=array(), $options=array())
  {
    
    //Extract raw values.
    raw($filters, $options);
    
    //Create a Rectangle to represent this Image.
    $R = new \dependencies\Rectangle($this->__get('width'), $this->__get('height'));
    
    //Image location.
    $fullname = $this->__get('filename')->get();
    $dot = strrpos($fullname, '.');
    $name = substr($fullname, 0, $dot);
    $ext = substr($fullname, $dot+1);
    $url = '/site/components/media/uploads/images/'.(empty($filters) ? '' : 'cache/').$name;
    
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
      $url .= "_resize-{$R->width()}-{$R->height()}";
      
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
      
      $url .= "_crop-{$x}-{$y}-{$width}-{$height}";
      
    }
    
    //Add extension and create query string from options.
    $url .= ".$ext".(empty($options) ? '' : '?').implode('&', $options);
    
    //Return a URL object.
    return url($url, true);

  }

}
