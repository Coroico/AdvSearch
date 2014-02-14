function submitForm() {
    var idsDom = $('#ids'),
            ids = idsDom.val(),
            sitesIDDom = $('#site_id'),
            siteID = sitesIDDom.val(),
            configFileDom = $('#config_file'),
            configFile = configFileDom.val(),
            includeTVsDom = $('#include_tvs'),
            includeTVs = includeTVsDom.val(),
            processTVsDom = $('#config_file'),
            processTVs = processTVsDom.val(),
            consoleDom = $('#errorLog');

    consoleDom.hide();
    idsDom.parent().removeClass('has-error');
    if (ids.length === 0) {
        idsDom.parent().addClass('has-error');
    }

    sitesIDDom.parent().removeClass('has-error');
    if (siteID.length === 0) {
        sitesIDDom.parent().addClass('has-error');
    }

    if (ids.length === 0 || siteID.length === 0) {
        return false;
    }

    $.ajax({
        cache: false,
        url: 'writeindex.php',
        data: {
            ids: ids,
            siteId: siteID,
            config_file: configFile,
            include_tvs: includeTVs,
            process_tvs: processTVs
        },
        dataType: 'json'
    }).done(function(data) {
        if (data.success === false) {
            consoleDom.html('<p>' + data.message + '</p>');
            consoleDom.addClass('text-danger');
            consoleDom.show();
        } else if (data.success === true) {
            $('#total').text(data.total);
            $('#output').val(data.message);
        }
    });
}