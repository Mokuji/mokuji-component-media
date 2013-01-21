<?php namespace components\media; if(!defined('TX')) die('No direct access.');?>
<script type="text/x-jquery-tmpl">
  <div id="${ids.main}" class="plupload-container">
    <h3 id="${ids.header}" class="header">${contents.header}</h3>
    <div id="${ids.drop}" class="drop">
      <div id="${ids.filelist}" class="filelist"></div>
      <div class="drag-here">${contents.drop}</div>
    </div>
    <div class="buttonHolder">
      <a id="${ids.browse}" class="browse" href="#">${contents.browse}</a>
      {{if !autoUpload}}<a id="${ids.upload}" class="upload" href="#">${contents.upload}</a>{{/if}}
    </div>
  </div>
</script>
