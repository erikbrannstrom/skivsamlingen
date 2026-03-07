$(function() {
    $('form.add-to-collection').submit(function(e) {
        var title = $(this).find('input[name="record_title"]').val();
        if (!confirm('Lägg till ' + title + ' i din samling?')) {
            e.preventDefault();
        }
    });
});
