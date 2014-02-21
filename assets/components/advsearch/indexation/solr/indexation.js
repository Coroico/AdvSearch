function submitForm() {
    var idsDom = $('#ids'),
            ids = idsDom.val(),
            sitesIDDom = $('#site_id'),
            siteID = sitesIDDom.val(),
            configFileDom = $('#config_file'),
            configFile = configFileDom.val(),
            includeTVsDom = $('#include_tvs'),
            includeTVs = includeTVsDom.val(),
            processTVsDom = $('#process_tvs'),
            processTVs = processTVsDom.val(),
            limit = $('#limit').val(),
            consoleDom = $('#errorLog'),
            imageLoader = $('#imageLoader'),
            totalDom = $('#total'),
            outputDom = $('#output'),
            submitBtn = $('#submit-btn');

    totalDom.text('');
    outputDom.text('');
    consoleDom.parent().hide();

    idsDom.parent().removeClass('has-error');
    if (ids.length === 0) {
        idsDom.parent().addClass('has-error');
    }

    sitesIDDom.parent().removeClass('has-error');
    if (siteID.length === 0) {
        sitesIDDom.parent().addClass('has-error');
    }

    configFileDom.parent().removeClass('has-error');
    if (configFile.length === 0) {
        configFileDom.parent().addClass('has-error');
    }

    if (ids.length === 0 || siteID.length === 0) {
        return false;
    }

    submitBtn.prop('disabled', true);
    imageLoader.parent().show();

    $.ajax({
        cache: false,
        url: 'writeindex.php',
        data: {
            ids: ids,
            siteId: siteID,
            config_file: configFile,
            include_tvs: includeTVs,
            process_tvs: processTVs,
            limit: limit
        },
        dataType: 'json'
    }).done(function(data) {
        if (data.success === false) {
            consoleDom.html('<p>' + data.message + '</p>');
            consoleDom.parent().show();
        } else if (data.success === true) {
            totalDom.text(data.total);
            outputDom.text(data.message);
        }
        submitBtn.prop('disabled', false);
        imageLoader.parent().hide();
    });
}