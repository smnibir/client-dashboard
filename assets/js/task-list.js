jQuery(document).ready(function($){
    let offset = 0;
    function loadTasks(){
        $.post(clickup_ajax.ajax_url, {
            action: 'load_clickup_tasks',
            nonce: clickup_ajax.nonce,
            offset: offset
        }, function(response){
            $('#task-list-container').append(response.html);
            offset += response.count;
            if (!response.has_more) $('#load-more-tasks').hide();
        }, 'json');
    }

    $('#load-more-tasks').on('click', function(){
        loadTasks();
    });

    loadTasks();
});