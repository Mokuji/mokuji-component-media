<?php namespace components\media; if(!defined('TX')) die('No direct access.');?>
(function($){
  
  //Gather some settings from the server.
  var settings = {
    chunkSize: "<?php echo $data->chunk_size; ?>",
    handleUrl: "<?php echo $data->handle_url; ?>",
    pluginUrl: "<?php echo $data->plugin_url; ?>"
  };
  
  //Set the default options.
  var defaultOptions = {
    
    //Template.
    tmpl: $("<?php echo $data->tmpl; ?>"),
    
    //Configuration options.
    acceptSource: false,
    autoUpload: true,
    drop: true,
    maxFileSize: '10mb',
    singleFile: false,
    filters: [{title : "<?php __($names->component, 'Image files'); ?>", extensions : "jpg,jpeg,gif,png"}],
    
    //Variables for the template.
    contents: {
      header: "<?php echo $data->content->header; ?>",
      drop: "<?php echo $data->content->drop; ?>",
      browse: "<?php echo $data->content->browse; ?>",
      upload: "<?php echo $data->content->upload; ?>"
    },
    
    //Default event callbacks.
    callbacks: {
      
      init: function(up, ids, params){ /* Do nothing */ },
      serverFileIdReport: function(up, ids, file_id){ /* Do nothing */ },
      
      filesAdded: function(up, ids, files, filelist){
        
        //For all the files added, create a row in the file list
        $.each(files, function(i, file)
        {
          
          var size = '';
          if(typeof file.size != 'undefined')
            size = '(' + plupload.formatSize(file.size) + ')';
          
          $('#'+ids.filelist).append(
            '<div id="' + file.id + '" class="file">' +
            file.name + ' <b></b> <i>' + size + '</i>' +
            '</div>'
          );
          
        });
        
      },
      
      uploadProgress: function(up, ids, file){
        //Set the progress.
        $('#' + file.id + " b").html(file.percent + "%");
      },
      
      fileUploaded: function(up, ids, file){
        
        //Set the progress.
        $('#' + file.id + " b").html("100%");
        
        //Slide the file row up.
        $('#' + file.id)
          .delay(1500)
          .slideUp('fast', function(){
            $(this).remove();
            up.refresh();
          });
        
      },
      
      error: function(up, ids, err){
        
        if(typeof err.file == 'undefined')
        {
          
          alert('Plupload error: ['+err.code+'] '+err.message);
          return;
          
        }
        
        //Set the upload progress to failed.
        $('#' + err.file.id + " b")
          .html("Failed");
        
        //Add an error that will slide up after 5 seconds.
        $("<div class=\"error\">[" + err.code + "] " + err.message + (err.file ? " (File: " + err.file.name + ")" : "") + "</div>")
          .appendTo('#'+ids.filelist)
          .delay(5000)
          .slideUp('fast', function(){
            $(this).remove();
          });
        
        //Slide up the file row after 5 seconds.
        $('#' + err.file.id)
          .delay(5000)
          .slideUp('fast', function(){
            $(this).remove();
            up.refresh();
          });
        
      }
      
    } //END - Callbacks
    
  }; //END - Default options
  
  /**
   * Transforms a hidden input field into an image uploader.
   */
  $.fn.txMediaImageUploader = function(options){
    
    $this = $(this);
    
    //Process options.
    var options = $.extend(true, {}, defaultOptions, options);
    
    //When replacing an image, we can't accept source files.
    if(options.replaceImage > 0)
      options.acceptSource = false;
    
    //When accepting any source file, override filters to all files.
    if(options.acceptSource === true)
      options.filters = [];
    
    //Create ID's.
    var date = new Date
      , salt = Math.floor(Math.random()*100000)
      , ids = {
      'main'      : salt+'-main',
      'header'    : salt+'-header',
      'drop'      : salt+'-drop',
      'filelist'  : salt+'-filelist',
      'upload'    : salt+'-upload',
      'browse'    : salt+'-browse'
    };
    
    //Next build the html for the form.
    var $view = options.tmpl.tmpl({
      ids: ids,
      contents: options.contents,
      autoUpload: options.autoUpload
    });
    $this.append($view);

    //Create plupload instance.
    var ie_version = ($.browser.msie ? parseInt($.browser.version, 10) : false);
    var uploader = new plupload.Uploader({
      
      //Fixed settings.
      runtimes: (ie_version <= 9 ? 'html4' : 'html5,flash,html4'),
      browse_button: ids.browse,
      container: ids.main,
      chunk_size: settings.chunkSize,
      url: settings.handleUrl,
      flash_swf_url: settings.pluginUrl+'js/plupload.flash.swf',
      silverlight_xap_url: settings.pluginUrl+'js/plupload.silverlight.xap',
      
      //Configurable at init time.
      drop_element: (options.drop ? ids.drop : null),
      max_file_size: options.maxFileSize,
      multi_selection: !options.singleFile,
      filters: options.filters,
      headers: {
        "x-txmedia-accept-source": options.acceptSource ? '1' : '0',
        "x-txmedia-replace-image": options.replaceImage ? options.replaceImage : 0
      }
      
    });
    
    //Attach event handlers.
    
    //Bind init event.
    uploader.bind('Init', function(up, params){
      options.callbacks.init(up, ids, params);
    });
    
    //When not using auto-upload, bind click event to upload button.
    if(!options.autoUpload){
      $('#'+ids.upload).bind('click', function(e){
        e.preventDefault();
        uploader.start();
      })
    }
    
    //Init plupload.
    uploader.init();
    
    //Bind files added event.
    uploader.bind('FilesAdded', function(up, files){
      
      //Call the callback we set.
      options.callbacks.filesAdded(up, ids, files);
      
      //Reposition Flash/Silverlight.
      up.refresh();
      
      //If single file mode, remove all but the last file.
      // if(options.singleFile)
      // {
        
      //   //Single file mode, remove all but the last file.
      //   plupload.each(files, function(file){
      //     if(up.files.length > 1)
      //       up.removeFile(file);
      //   });
        
      // }
      
      //Autostart if wanted
      if(options.autoUpload) up.start();
      
    });
    
    //Bind upload progress event.
    uploader.bind('UploadProgress', function(up, file) {
      options.callbacks.uploadProgress(up, ids, file);
    });

    //Bind error event.
    uploader.bind('Error', function(up, err) {
      options.callbacks.error(up, ids, err);
      up.refresh(); // Reposition Flash/Silverlight
    });
    
    //Bind file uploaded event.
    uploader.bind('FileUploaded', function(up, file, response) {
      options.callbacks.fileUploaded(up, ids, file, response);
    });
    
    //Bind server file ID report event.
    uploader.bind('ServerFileIdReport', function(up, file_id){
      options.callbacks.serverFileIdReport(up, ids, file_id);
    });
    
    //END - Attach event handlers.
    
  };
  
})(jQuery);
