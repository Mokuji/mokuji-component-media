<?php namespace components\media; if(!defined('TX')) die('No direct access.');

class Sections extends \dependencies\BaseViews
{

  protected function image()
  {
    
    $image = tx('Sql')->table('media', 'Images')
              ->pk(tx('Data')->get->id)
              ->execute_single();
    
    if($image->is_empty())
      throw new \exception\EmptyResult("Supplied image id was not found. ".tx('Data')->get->id->dump());
    
    $resize = tx('Data')->get->resize->split('/');
    $fit = tx('Data')->get->fit->split('/');
    $fill = tx('Data')->get->fill->split('/');
    $crop = tx('Data')->get->crop->split('/');
    
    return array(
      'download' => tx('Data')->get->download->is_set(),
      'image' => tx('File')->image()
                  ->use_cache(!tx('Data')->get->no_cache->is_set())
                  ->from_file($image->get_abs_filename())
                  ->allow_growth(tx('Data')->get->allow_growth->is_set())
                  ->allow_shrink(!tx('Data')->get->disallow_shrink->is_set())
                  ->sharpening(!tx('Data')->get->disable_sharpen->is_set())
                  ->resize($resize->{0}, $resize->{1})
                  ->fit($fit->{0}, $fit->{1})
                  ->fill($fill->{0}, $fill->{1})
                  ->crop($crop->{0}, $crop->{1}, $crop->{2}, $crop->{3})
    );
    
  }

  protected function image_abs()
  {
    
    throw new \exception\Deprecated();
    
  }

}
