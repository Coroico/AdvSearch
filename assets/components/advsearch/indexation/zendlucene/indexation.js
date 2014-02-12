function submitForm() {
    var idsDom = $('#ids'),
            ids = idsDom.val(),
            sitesIDDom = $('#site_id'),
            siteID = sitesIDDom.val(),
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
        url: 'getids.php',
        data: {
            ids: ids,
            siteId: siteID
        },
        dataType: 'json'
    }).done(function(data) {
        if (data.success === false) {
            consoleDom.html('<p>' + data.message + '</p>');
            consoleDom.show();
        } else if (data.success === true) {
            $('#total').text(data.total);
            $('#totalProgress').attr('aria-valuemax', data.total);
            $('#totalProgress').attr('aria-valuenow', 0);
            $('#output').val('======== Indexation starting ... ========\r\n');
            $.each(data.object, function(index, item) {
                return indexing(siteID, item).promise().done(function() {
                    if (data.object[data.total - 1] === item) {
                        setTimeout(function(){
                            var output = $('#output').val();
                            $('#output').val(output + '\r\n' + '======== Completed! ========');
                        }, 500);
                    }
                });
            });
        }
    });
}

function indexing(siteID, id) {
    var output, curValue, newValue, curWidth,
            consoleDom = $('#errorLog'),
            totalValue = $('#totalProgress').attr('aria-valuemax');

    return $.ajax({
        cache: false,
        url: 'indexing.php',
        data: {
            id: id,
            siteId: siteID
        },
        dataType: 'json'
    }).done(function(data) {
        if (data.success === false) {
            consoleDom.html('<p>' + data.message + '</p>');
            consoleDom.show();
        } else if (data.success === true) {
            $.each(data.object, function(index, item) {
                output = $('#output').val();
                $('#output').val(output + '\r\n' + item);
                $('#output').scrollTop($("#output")[0].scrollHeight);
                curValue = $('#totalProgress').attr('aria-valuenow');
                newValue = curValue - 0 + 1;
                $('#totalProgress').attr('aria-valuenow', newValue);
                curWidth = newValue / totalValue * 100;
                $('#totalProgress').css('width', curWidth + '%');
            });
        }
    });
}