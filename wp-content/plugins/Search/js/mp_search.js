jQuery(document).ready(function($) {
    $('#mp-search').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: mp_ajax.ajax_url,
                dataType: 'json',
                data: {
                    action: 'mp_search_suggestions',
                    term: request.term
                },
                success: function(data) {
                    response(data);
                }
            });
        },
        minLength: 1
    });
});
