<?php namespace components\media; if(!defined('TX')) die('No direct access.');

//Make sure we have the things we need for this class.
tx('Component')->check('update');
tx('Component')->load('update', 'classes\\BaseDBUpdates', false);

class DBUpdates extends \components\update\classes\BaseDBUpdates
{
  
  protected
    $component = 'media',
    $updates = array(
      '1.1' => '1.2'
    );
  
  public function update_to_1_2($current_version, $forced)
  {
    
    tx('Sql')->query('
      ALTER TABLE `#__media_images`
      CHANGE COLUMN `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT FIRST,
      ADD COLUMN `width` INT(10) UNSIGNED NOT NULL AFTER `filename`,
      ADD COLUMN `height` INT(10) UNSIGNED NOT NULL AFTER `width`
    ');
      
  }
  
  public function install_1_1($dummydata, $forced)
  {
    
    if($forced === true){
      tx('Sql')->query('DROP TABLE IF EXISTS `#__media_images`');
    }
    
    tx('Sql')->query('
      CREATE TABLE `#__media_images` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `filename` varchar(255) NOT NULL,
        `dt_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
      ) ENGINE=MyISAM  DEFAULT CHARSET=utf8
    ');
    
  }
  
}

