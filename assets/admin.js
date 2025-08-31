




(function($){
  $(function(){
    console.log("admin.js loaded ✅");

    function updateSelect($field, options){
      $field.empty().append('<option value="">Select...</option>');
      $.each(options, function(id,name){
        $field.append('<option value="'+id+'">'+name+'</option>');
      });
      $field.trigger('change acf/change');
    }

    const $space  = $('[data-name="clickup_space"] select');
    const $folder = $('[data-name="clickup_folder"] select');
    const $portal = $('[data-name="client_portal"] select');

    $space.on('change', function(){
      const spaceId = $(this).val();
      if(!spaceId) return;

      console.log("Space selected:", spaceId);
      $.post(clickup_ajax.ajax_url, {
        action: "get_clickup_folders",
        space_id: spaceId
      })
      .done(response => {
        console.log("Folders response:", response);
        if(response.success){
          updateSelect($folder, response.data);
          updateSelect($portal, {});
        }
      })
      .fail(err => console.error("AJAX error loading folders:", err));
    });

    $folder.on('change', function(){
      const folderId = $(this).val();
      if(!folderId) return;

      console.log("Folder selected:", folderId);
      $.post(clickup_ajax.ajax_url, {
        action: "get_clickup_views",
        folder_id: folderId
      })
      .done(response => {
        console.log("Views response:", response);
        if(response.success){
          updateSelect($portal, response.data);
        }
      })
      .fail(err => console.error("AJAX error loading views:", err));
    });
  });
})(jQuery);
