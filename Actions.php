<?php namespace components\media; if(!defined('TX')) die('No direct access.');

class Actions extends \dependencies\BaseComponent
{
  
  protected function upload_image()
  {
    
    // Important because otherwise through some client side altering executable files can be uploaded.
    $extension_whitelist = array(
      'jpg', 'jpeg', 'png', 'gif'
    );
    
    // HTTP headers for no cache etc
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    
    // Settings
    $upload_dir = PATH_COMPONENTS.DS.$this->component.DS.'uploads'.DS;
    $target_dir = $upload_dir.'images'.DS;
    $tmp_dir = $target_dir.'inbound'.DS;

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
      die('{"jsonrpc" : "2.0", "error" : {"code": 104, "message": "Invalid file extention \''.$extension.'\'. Valid extensions are: '.implode(', ', $extension_whitelist).'."}, "id" : "id"}');
    }
    
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
      while(file_exists($target_dir.DS.$target_filename));
    
      //Move the file to target directory
      if(!rename($tmp_dir.DS.$filename, $target_dir.DS.$target_filename))
      {
        //If unsuccesful try to delete the tmp file not to create a mess.
        @unlink($tmp_dir.DS.$filename);
        
        die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : null}');
      }
      
      //Store image in database
      $image = $this->model('Images')
        ->filename->set($target_filename)->back()
        ->name->set($filename_raw)->back()
        ->save();
      
      // Return JSON-RPC response
      die('{"jsonrpc" : "2.0", "result" : '.$image->id.', "id" : null}');
    }
    
    else{
      // Return JSON-RPC response
      die('{"jsonrpc" : "2.0", "result" : null, "id" : null}');
    }

  }
  
}
