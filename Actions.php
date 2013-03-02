<?php namespace components\media; if(!defined('TX')) die('No direct access.');

class Actions extends \dependencies\BaseComponent
{
  
  protected function upload_image()
  {
    
    // Important because otherwise through some client side altering executable files can be uploaded.
    $extension_whitelist = array(
      'jpg', 'jpeg', 'png', 'gif'
    );
    
    // Do we accept all files?
    $http_headers = apache_request_headers();
    $accept_any_source_file = isset($http_headers['x-txmedia-accept-source']) ? $http_headers['x-txmedia-accept-source'] == 1 : false;
    $is_source_file = false;
    $replace_image = isset($http_headers['x-txmedia-replace-image']) ? intval($http_headers['x-txmedia-replace-image']) : false;
    
    //Output info about this upload.
    tx('Logging')->log('Media', 'Image upload handler', 'Accept any? '.($accept_any_source_file ? 'Yes.' : 'No.'));
    tx('Logging')->log('Media', 'Image upload handler', 'Replacing? '.($replace_image ? 'Yes '.$replace_image.'.' : 'No.'));
    
    // HTTP headers for no cache etc
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    // Set memory limit to infinite
    ini_set('memory_limit', '-1');
    
    // Limit execution time to 5 minutes
    @set_time_limit(5*60);
    
    // Get parameters
    $chunk = tx('Data')->request->chunk->get('int');
    $chunks = tx('Data')->request->chunks->get('int');
    $filename = tx('Data')->request->name->get('string');

    // Clean the filename for security reasons
    $filename = preg_replace('/[^\w\._]+/', '', $filename);
    
    // Get extension and filename
    $ext_pos = strrpos($filename, '.');
    $filename_raw = substr($filename, 0, $ext_pos);
    $extension = substr($filename, $ext_pos+1);

    // Check the extension is in the whitelist
    if(!in_array(strtolower($extension), $extension_whitelist))
    {
      
      if($accept_any_source_file){
        $is_source_file = true;
      }
      
      else{
        die('{"jsonrpc" : "2.0", "error" : {"code": 104, "message": "Invalid file extention \''.$extension.'\'. Valid extensions are: '.implode(', ', $extension_whitelist).'."}, "id" : "id"}');
      }
      
    }
    
    // Find target directory
    $upload_dir = PATH_COMPONENTS.DS.$this->component.DS.'uploads'.DS;
    $target_dir = $upload_dir.($is_source_file ? 'files' : 'images').DS;
    $tmp_dir = $target_dir.'inbound'.DS;
    
    // Create target dirs
    if (!file_exists($upload_dir)){
      @mkdir($upload_dir);
    }
    if (!file_exists($target_dir)){
      @mkdir($target_dir);
    }
    if (!file_exists($tmp_dir)){
      @mkdir($tmp_dir);
    }

    // Look for the content type header
    $content_type = '';
    if(tx('Data')->server->HTTP_CONTENT_TYPE->is_set()){
      $content_type = tx('Data')->server->HTTP_CONTENT_TYPE->get();
    }

    if(tx('Data')->server->CONTENT_TYPE->is_set()){
      $content_type = tx('Data')->server->CONTENT_TYPE->get();
    }

    // Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
    if (strpos($content_type, "multipart") !== false)
    {
    
      if(tx('Data')->files->file->tmp_name->is_set() && is_uploaded_file(tx('Data')->files->file->tmp_name))
      {
        // Open temp file
        $out = fopen($tmp_dir.DS.$filename, $chunk == 0 ? "wb" : "ab");
        if ($out)
        {
          // Read binary input stream and append it to temp file
          $in = fopen(tx('Data')->files->file->tmp_name, "rb");
          
          if($in)
          {
            while ($buff = fread($in, 4096)){
              fwrite($out, $buff);
            }
          }
          else
          {
            die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : null}');
          }
          
          fclose($in);
          fclose($out);
          @unlink(tx('Data')->files->file->tmp_name);
        }
        else
        {
          die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : null}');
        }
      }
      else
      {
        die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file. '.
          'Error: '.file_upload_error_message(tx('Data')->files->file->error).'"}, "id" : null}');
      }
    }
    else
    {
      // Open temp file
      $out = fopen($tmp_dir.DS.$filename, $chunk == 0 ? "wb" : "ab");

      if($out)
      {
        // Read binary input stream and append it to temp file
        $in = fopen("php://input", "rb");

        if($in)
        {
          while ($buff = fread($in, 4096)){
            fwrite($out, $buff);
          }
        }
        else
        {
          die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : null}');
        }

        fclose($in);
        fclose($out);
      }
      else
      {
        die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : null}');
      }
    }

    //If this is the last chuck or only chunk.
    if($chunks == 0 || $chunks -1 == $chunk)
    {

      // //Get filesize, width and height.
      // $width = imagesx(tx('Data')->files->file->tmp_name);
      // $height = imagesy(tx('Data')->files->file->tmp_name);
      // $filesize = filesize(tx('Data')->files->file->tmp_name);
      
      //Create unique file name
      do{
        $target_filename = tx('Security')->random_string(64).'.'.$extension;
      }
      while(file_exists($target_dir.$target_filename));

      //Move the file to target directory
      if(!rename($tmp_dir.DS.$filename, $target_dir.$target_filename))
      {
        //If unsuccesful try to delete the tmp file not to create a mess.
        @unlink($tmp_dir.$filename);
        
        die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : null}');
      }
      
      // Store information in the database
      
      if($is_source_file)
      {
        
        $info = tx('File')->file()->from_file($target_dir.$target_filename);
        
        //Store file in database
        $file = $this->model('Files')
          ->set(array(
            'name' => $filename,
            'filename' => $target_filename,
            'filesize' => $info->get_filesize()
          ))
          ->save();
        
        //Find out where to get default preview images.
        $preview_file_source_dir = PATH_COMPONENTS.DS.$this->component.DS.'includes'.DS.'icons'.DS;
        $preview_file_dir = $upload_dir.'images'.DS;
        if (!file_exists($preview_file_dir)){
          @mkdir($preview_file_dir);
        }
        
        #TODO: Specify this based on the source file type.
        $preview_file = 'x_default.png';
        
        //If the preview icon is not in the uploaded images folder, copy it from the includes folder.
        //This because of a directory traversal restriction in the filename database field.
        if(!is_file($preview_file_dir.$preview_file))
          if(!@copy($preview_file_source_dir.$preview_file, $preview_file_dir.$preview_file))
            die('{"jsonrpc" : "2.0", "error" : {"code": 105, "message": "Could not create preview image for file based on \''.$preview_file.'\'."}, "id" : null}');
        
        
        $info = tx('File')->image()->from_file($preview_file_dir.$preview_file);
        
        //Store image in database
        $image = $this->model('Images')
          ->set(array(
            'name' => $filename,
            'filename' => $preview_file,
            'width' => $info->get_width(),
            'height' => $info->get_height(),
            'filesize' => $info->get_filesize(),
            'source_file_id' => $file->id
          ))
          ->save();
        
      }
      
      // When not a source file...
      else
      {
        
        //Possibly replace another image?
        if($replace_image){
          
          $image = $this->table('Images')
            ->pk($replace_image)
            ->execute_single();
          
          //First delete the old image and it's cache though.
          //But only if it's not one of the placeholder images.
          if(substr($image->filename->get(), 0, 2) !== 'x_')
            tx('Component')->helpers('media')->call('delete_image_file', array($image->filename));
          
        }
        
        else{
          
          //Only when uploading a fresh image should this be set.
          $image = $this->model('Images')
            ->set(array(
              'name' => $filename
            ));
            
        }
        
        //Store image info in database
        $info = tx('File')->image()->from_file($target_dir.$target_filename);
        $image
          ->merge(array(
            'filename' => $target_filename,
            'width' => $info->get_width(),
            'height' => $info->get_height(),
            'filesize' => $info->get_filesize()
          ))
          ->save();
        
      }
      
      // Return JSON-RPC response
      die('{"jsonrpc" : "2.0", "result" : '.$image->id.', "id" : null}');
      
    }
    
    else{
      // Return JSON-RPC response
      die('{"jsonrpc" : "2.0", "result" : null, "id" : null}');
    }

  }

  protected function upload_file()
  {
    
    // Important because otherwise through some client side altering executable files can be uploaded.
    $extension_whitelist = array(
      'jpg',
      'jpeg',
      'png',
      'gif',
      'pdf',
      'doc',
      'docx',
      'xls',
      'ods',
      'jpg',
      'jpeg'
    );
    
    // Do we accept all files?
    $http_headers = apache_request_headers();
    $accept_any_source_file = isset($http_headers['x-txmedia-accept-source']) ? $http_headers['x-txmedia-accept-source'] == 1 : false;
    
    //Output info about this upload.
    tx('Logging')->log('Media', 'File upload handler', 'Accept any? '.($accept_any_source_file ? 'Yes.' : 'No.'));
    
    // HTTP headers for no cache etc
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    // Set memory limit to infinite
    ini_set('memory_limit', '-1');
    
    // Limit execution time to 5 minutes
    @set_time_limit(5*60);
    
    // Get parameters
    $chunk = tx('Data')->request->chunk->get('int');
    $chunks = tx('Data')->request->chunks->get('int');
    $filename = tx('Data')->request->name->get('string');

    // Clean the filename for security reasons
    $filename = preg_replace('/[^\w\._]+/', '', $filename);
    
    // Get extension and filename
    $ext_pos = strrpos($filename, '.');
    $filename_raw = substr($filename, 0, $ext_pos);
    $extension = substr($filename, $ext_pos+1);

    // Check the extension is in the whitelist
    if(!in_array(strtolower($extension), $extension_whitelist)){
      die('{"jsonrpc" : "2.0", "error" : {"code": 104, "message": "Invalid file extention \''.$extension.'\'. Valid extensions are: '.implode(', ', $extension_whitelist).'."}, "id" : "id"}');
    }
    
    // Find target directory
    $upload_dir = PATH_COMPONENTS.DS.$this->component.DS.'uploads'.DS;
    $target_dir = $upload_dir.'files'.DS;
    $tmp_dir = $target_dir.'inbound'.DS;
    
    // Create target dirs
    if (!file_exists($upload_dir)){
      @mkdir($upload_dir);
    }
    if (!file_exists($target_dir)){
      @mkdir($target_dir);
    }
    if (!file_exists($tmp_dir)){
      @mkdir($tmp_dir);
    }

    // Look for the content type header
    $content_type = '';
    if(tx('Data')->server->HTTP_CONTENT_TYPE->is_set()){
      $content_type = tx('Data')->server->HTTP_CONTENT_TYPE->get();
    }

    if(tx('Data')->server->CONTENT_TYPE->is_set()){
      $content_type = tx('Data')->server->CONTENT_TYPE->get();
    }

    // Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
    if (strpos($content_type, "multipart") !== false)
    {
    
      if(tx('Data')->files->file->tmp_name->is_set() && is_uploaded_file(tx('Data')->files->file->tmp_name))
      {
        // Open temp file
        $out = fopen($tmp_dir.DS.$filename, $chunk == 0 ? "wb" : "ab");
        if ($out)
        {
          // Read binary input stream and append it to temp file
          $in = fopen(tx('Data')->files->file->tmp_name, "rb");
          
          if($in)
          {
            while ($buff = fread($in, 4096)){
              fwrite($out, $buff);
            }
          }
          else
          {
            die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : null}');
          }
          
          fclose($in);
          fclose($out);
          @unlink(tx('Data')->files->file->tmp_name);
        }
        else
        {
          die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : null}');
        }
      }
      else
      {
        die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file. '.
          'Error: '.file_upload_error_message(tx('Data')->files->file->error).'"}, "id" : null}');
      }
    }
    else
    {
      // Open temp file
      $out = fopen($tmp_dir.DS.$filename, $chunk == 0 ? "wb" : "ab");

      if($out)
      {
        // Read binary input stream and append it to temp file
        $in = fopen("php://input", "rb");

        if($in)
        {
          while ($buff = fread($in, 4096)){
            fwrite($out, $buff);
          }
        }
        else
        {
          die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : null}');
        }

        fclose($in);
        fclose($out);
      }
      else
      {
        die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : null}');
      }
    }

    //If this is the last chuck or only chunk.
    if($chunks == 0 || $chunks -1 == $chunk)
    {

      //Create unique file name
      do{
        $target_filename = tx('Security')->random_string(64).'.'.$extension;
      }
      while(file_exists($target_dir.$target_filename));

      //Move the file to target directory
      if(!rename($tmp_dir.DS.$filename, $target_dir.$target_filename))
      {
        //If unsuccesful try to delete the tmp file not to create a mess.
        @unlink($tmp_dir.$filename);
        
        die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : null}');
      }
      
      $info = tx('File')->file()->from_file($target_dir.$target_filename);

      //Store file in database
      $file = $this->model('Files')
        ->set(array(
          'name' => $filename,
          'filename' => $target_filename,
          'filesize' => $info->get_filesize()
        ))
        ->save();
      
      // Return JSON-RPC response
      die('{"jsonrpc" : "2.0", "result" : '.$file->id.', "id" : null}');

    }
    
    else{
      // Return JSON-RPC response
      die('{"jsonrpc" : "2.0", "result" : null, "id" : null}');
    }

  }
  
}
