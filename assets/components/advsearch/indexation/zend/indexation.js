function submitIds() {
    var idsDom = $('#ids'),
            ids = idsDom.val(),
            sitesIDDom = $('#site_id'),
            siteID = sitesIDDom.val(),
            consoleDom = $('#errorLog'),
            totalDom = $('#total'),
            outputDom = $('#output'),
            imageLoader = $('#imageLoader'),
            submitBtn = $('#submit-btn');

    consoleDom.text('');

    idsDom.parent().removeClass('has-error');
    if (ids.length === 0) {
        idsDom.parent().addClass('has-error');
    }

    sitesIDDom.parent().removeClass('has-error');
    if (siteID.length === 0) {
        sitesIDDom.parent().addClass('has-error');
    }

    submitBtn.prop('disabled', true);
    imageLoader.parent().css('visibility', 'visible');

    var preRecordIds = $('input[name=preRecordIds]:checked', '#getIds').val();
    $.ajax({
        cache: false,
        url: 'writeids.php',
        data: {
            ids: ids,
            siteId: siteID,
            preRecordIds: preRecordIds
        },
        dataType: 'json'
    }).done(function(data) {
        if (data.success === false) {
            consoleDom.append(data.message);
        } else if (data.success === true) {
            totalDom.text(data.total);
            outputDom.append(data.message);
        }
        submitBtn.prop('disabled', false);
        imageLoader.parent().css('visibility', 'hidden');
    });

}
var globalLimit = 0;
function submitForm(nextStart) {
    var sitesIDDom = $('#site_id'),
            siteID = sitesIDDom.val(),
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
            submitBtn = $('#submit-btn');

    // initial running
    if (typeof(nextStart) === 'undefined') {
        totalDom.text('');
        outputDom.text('');
        consoleDom.text('');

        if (start) {
            nextStart = start;
        }
        globalLimit = limit;
    }

    sitesIDDom.parent().removeClass('has-error');
    if (siteID.length === 0) {
        sitesIDDom.parent().addClass('has-error');
    }

    if (siteID.length === 0) {
        return false;
    }

    submitBtn.prop('disabled', true);
    imageLoader.parent().css('visibility', 'visible');

    $.ajax({
        cache: false,
        url: 'writeindex.php',
        data: {
            siteId: siteID,
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
        resetDom.prop('checked', false);
        var breakLoop = breakLoopDom.prop('checked');
        if (data.success === false) {
            if (data.total) {
                totalDom.text(data.total);
            }
            consoleDom.append(data.message);
            $('#limit').val(1);
            $('#start').val(data.nextStart);
            if (loop && !breakLoop && errorContinue && data.total) {
                setTimeout(function() {
                    submitForm(data.nextStart);
                }, 1000);
            }
        } else if (data.success === true) {
            totalDom.text(data.total);
            outputDom.append(data.message);
            $('#limit').val(globalLimit);
            $('#start').val(data.nextStart);
            if (loop && !breakLoop && data.total) {
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