
function remove(record) {
    $.ajax({
        type: "POST",
        url: baseurl + "collection/delete",
        data: "id=" + record,
        datatype: 'json',
        success: function(data){
            if(data.type != 'success')
                return;
            row = $('#record-row-'+record).prevUntil('tr.artist-row').last().prev();
            if(row.is('tr.artist-row') == false) {
                row = $('#record-row-'+record).prev();
            }
            text = row.find('.num-artist-records').first();
            num = text.html() - 1;
            if(num > 0)
                text.html(num);
            else
                row.remove();
            $('#record-row-'+record).remove();
            $('.num-user-records').each(function() {
                $(this).html($(this).html() - 1);
            });
        },
        error: function(msg) {
            alert(msg);
        }
    });
}