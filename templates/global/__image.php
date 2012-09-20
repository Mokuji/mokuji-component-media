<?php namespace components\media; if(!defined('TX')) die('No direct access.');

set_exception_handler('exception_handler_image');
if($image->error->is_set())
  die($image->error);
elseif($image->download->get() === true)
  $image->image->get()->download();
else
  $image->image->get()->output();
