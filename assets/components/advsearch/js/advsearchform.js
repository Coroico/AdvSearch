/*
 * AdvSearch 1.0.0 - AjaxSearchForm
 * author: Coroico - www.modx.wangba.fr - 30/12/2011
 *
 * Licensed under the GPL license: http://www.gnu.org/copyleft/gpl.html
 */

var advsea = new Array();
var ashw;
var ashws = 0;

jQuery(function($) {

    function activateAsfInstance(asv) {
        var p = asv.asid + '_';      // prefix for the instance

        if (asv.hid) {  // help requested
            var hl = $('#' + p + 'advsea-helplink');   // help link
            if (hl)
                hl.click(function() {
                    var pars = {id: asv.hid};
                    $.post('index.php', pars, function(text) {
                        if (!ashws) {
                            ashw = window.open('', 'AdvSearch Help', 'height=500, width=600, top=10, left=10, toolbar=no, menubar=yes, location=no, resizable=yes, scrollbars=yes, status=no');
                            ashw.document.write(text);
                            ashw.onunload = function() {
                                ashws = 0;
                            };
                        }
                        if (window.focus) {
                            ashw.focus();
                        }
                        ashws = 1;
                        return false;
                    });
                    return false;
                });  //= adds the help function to the help link for click event
        }
        if (asv.cdt) {   // clear default requested
            var si = $('#' + p + 'advsea-search');   // search input
            if (si) {
                si.prop('defaultValue', '');
                si.val('');
                si.prop('placeholder', asv.cdt);
            }
        }
    }

    function activateAdvSearchForm() {
        for (var ias = 0; ias < advsea.length; ias++) { //= Each newSearch instance is activated
            var asv = eval('(' + advsea[ias] + ')');
            activateAsfInstance(asv);
        }
    }

    activateAdvSearchForm(); //= as soon as the DOM is loaded

});