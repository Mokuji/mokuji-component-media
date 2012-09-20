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
  *   $options
  *     - disable_sharpen
  *     - download
  *     - no_cache
  *     - allow_growth
  *     - disallow_shrink
  */
  public function generate_url($filters=array(), $options=array())
  {

    $filters = Data($filters);
    $options = Data($options);

    //Image ID
    $url = "?section=media/image&id={$this->id}";

    //Resize
    if($filters->resize_width->get('int') > 0 || $filters->resize_height->get('int') > 0){
      $url .= "&resize={$filters->resize_width}/{$filters->resize_height}";
    }

    //Crop
    if($filters->crop_x->get('int') != 0 || $filters->crop_y->get('int') != 0 ||
      $filters->crop_width->get('int') != 0 || $filters->crop_height->get('int') != 0)
    {

      $url .= "&crop={$filters->crop_x}/{$filters->crop_y}/".
              "{$filters->crop_width}/{$filters->crop_height}";

    }

    //Go over the options
    $options->each(function($option)use(&$url){
      switch($option->get()){
        case 'disable_sharpen':
          $url .= '&disable_sharpen';
          break;
        case 'download':
          $url .= '&download';
          break;
        case 'no_cache':
          $url .= '&no_cache';
          break;
        case 'allow_growth':
          $url .= '&allow_growth';
          break;
        case 'disallow_shrink':
          $url .= '&disallow_shrink';
          break;
        default:
          throw new \exception\InvalidArgument('$options[\''.$option.'\']');
      }
    });

    return url($url, true);

  }

}
