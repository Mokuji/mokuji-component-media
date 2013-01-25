<?php namespace components\media; if(!defined('TX')) die('No direct access.');

//Make sure we have the things we need for this class.
tx('Component')->check('update');
tx('Component')->load('update', 'classes\\BaseDBUpdates', false);

class DBUpdates extends \components\update\classes\BaseDBUpdates
{
  
  protected
    $component = 'media',
    $updates = array(
      '1.1' => '1.2',
      '1.2' => '1.3',
      '1.3' => '2.0'
    );
  
  //Implement source files capability for images and add files table.
  public function update_to_2_0($current_version, $forced)
  {
    
    if($forced === true){
      tx('Sql')->query('
        DROP TABLE IF EXISTS `#__media_files`
      ');
    }
    
    tx('Sql')->query('
      CREATE TABLE `#__media_files` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `filename` varchar(255) NOT NULL,
        `filesize` int(10) unsigned NOT NULL,
        `dt_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8
    ');
    
    try{
      
      tx('Sql')->query('
        ALTER TABLE `#__media_images`
          ADD `source_file_id` INT UNSIGNED NULL DEFAULT NULL `filename`
      ');
    
    }catch(\exception\Sql $ex){
      //When it's not forced, this is a problem.
      //But when forcing, ignore this.
      if(!$forced) throw $ex;
    }
    
  }
  
  //Add column `filesize` to table #__media_images.  
  public function update_to_1_3($current_version, $forced)
  {
    
    tx('Sql')->query('
      ALTER TABLE `#__media_images`
      ADD COLUMN `filesize` INT(10) NULL AFTER `height`;
    ');
      
  }

  //Add column `file_size` to table #__media_images.  
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

