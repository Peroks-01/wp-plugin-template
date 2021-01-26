jQuery().ready(function ($) {
    $('div.theme-actions').each(function () {
        var name = $(this).parents('div.theme').attr('data-slug');
        $(this).append('<a class="button download-theme" href="?download-theme=' + name + '" download>Download</a>');
    });
});