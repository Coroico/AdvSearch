function submitForm(nextStart) {
    var idsDom = $('#ids'),
            ids = idsDom.val(),
            sitesIDDom = $('#site_id'),
            siteID = sitesIDDom.val(),
            configFileDom = $('#config_file'),
            configFile = configFileDom.val(),
            includeTVsDom = $('#include_tvs'),
            includeTVs = includeTVsDom.prop('checked'),
            processTVsDom = $('#process_tvs'),
            processTVs = processTVsDom.prop('checked'),
            loopDom = $('#loop'),
            loop = loopDom.prop('checked'),
            start = $('#start').val(),
            limit = $('#limit').val(),
            resetDom = $('#reset'),
            reset = resetDom.prop('checked'),
            errorContinueDom = $('#error_continue'),
            errorContinue = errorContinueDom.prop('checked'),
            breakLoopDom = $('#break_loop'),
            consoleDom = $('#errorLog'),
            imageLoader = $('#imageLoader'),
            totalDom = $('#total'),
            outputDom = $('#output'),
            submitBtn = $('#submit-btn'),
            nextStart = nextStart - 0 || 0;

    // initial running
    if (nextStart === 0) {
        totalDom.text('');
        outputDom.text('');
        consoleDom.text('');

        if (start) {
            nextStart = start;
        }
    }

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
    imageLoader.parent().css('visibility', 'visible');

    $.ajax({
        cache: false,
        url: 'writeindex.php',
        data: {
            ids: ids,
            siteId: siteID,
            config_file: configFile,
            include_tvs: includeTVs,
            process_tvs: processTVs,
            loop: loop,
            errorContinue: errorContinue,
            start: nextStart,
            limit: limit,
            reset: reset
        },
        dataType: 'json'
    }).done(function(data) {
        var breakLoop = breakLoopDom.prop('checked');
        if (data.success === false) {
            consoleDom.append(data.message);
            if (loop && !breakLoop && errorContinue && data.total && data.nextStart && (data.total - 0 > data.nextStart - 0)) {
                setTimeout(function() {
                    submitForm(data.nextStart);
                }, 1000);
            }
        } else if (data.success === true) {
            totalDom.text(data.total);
            outputDom.append(data.message);
            if (loop && !breakLoop && data.total && data.nextStart && (data.total - 0 > data.nextStart - 0)) {
                setTimeout(function() {
                    $('#start').val(data.nextStart);
                    submitForm(data.nextStart);
                }, 1000);
            }
        }
        submitBtn.prop('disabled', false);
        imageLoader.parent().css('visibility', 'hidden');
    });
}