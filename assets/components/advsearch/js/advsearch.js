/*
 * advsearch 1.0.0 - package AdvSearch - JQuery 1.7.1
 * author: Coroico - www.modx.wangba.fr - 30/12/2011
 *
 * Licensed under the GPL license: http://www.gnu.org/copyleft/gpl.html
 */

// set the folder location to the correct location of advsearch scripts
var _basejs = 'assets/components/advsearch/js/';

// set the loading and the close image to the correct location for you
var _close = _basejs + 'images/close2.png';  // close image
var _closeAlt = 'close search';
var _load = _basejs + 'images/indicator.white.gif'; // loading image
var _loadAlt = 'loading';

// minimum number of characters. Should be coherent with advSearch snippet call
var _minChars = 3;

jQuery(function($) {

    $.fn.reswinUp = function(e) {
        return this.each(function() {
            switch (e) {
                case "showfade":
                    $(this).fadeOut(800).hide(1200);
                    break;
                case "slidefade":
                    $(this).fadeOut(800).slideUp(1200);
                    break;
                case "basic":
                default:
                    $(this).hide();
            }
        });
    };

    $.fn.reswinDown = function(e) {
        return this.each(function() {
            switch (e) {
                case "showfade":
                    $(this).show(800).fadeIn(1200);
                    break;
                case "slidefade":
                    $(this).slideDown(800).fadeIn(1200);
                    break;
                case "basic":
                default:
                    $(this).show();
            }
        });
    };

    function activateSearch() {
        // Each advsearch instance has its own index ias
        for (var ias = 0; ias < advsea.length; ias++) {
            var asv = eval('(' + advsea[ias] + ')');
            activateAsInstance(asv);
        }
    }

    function activateAsInstance(as) {
        if (!as.arh) {
            return false; // empty ajax holder
        }

        // as.asid : advsearch instance id
        // as.arh  : ajax results holder
        // as.cdt  : clear default text

        if (!as.ls) {
            // live search off by default
            as.ls = 0;
        }
        if (!as.ii) {
            // initial display
            as.ii = 'none';
        }
        if (!as.mc) {
            // min chars
            as.mc = _minChars;
        }
        if (!as.sx) {
            // search index
            as.sx = 'search';
        }
        if (!as.ox) {
            // offset index
            as.ox = 'offset';
        }

        as.lt = null;   // livesearch timeout
        as.is = false;  // is searching flag

        var p = as.asid + '_';
        as.px = p; //advsearch instance prefix

        as.si = $('#' + p + 'advsea-search');   // advsearch input field
        var ref = as.si;

        as.se = $('#' + p + 'advsea-select');   // select input field if it exists
        as.sb = "Search";
        if (!as.ls) {
            as.ss = $('#' + p + 'advsea-submit');  // advsearch submit button if it exists
            as.sb = as.ss.attr('value');
            ref = as.ss;
        }

        $('.advsea-close-img').each(function(){
            $(this).remove();
        });
        as.cl = $('<img src="' + _close + '" class="advsea-close-img" alt="' + _closeAlt + '" id="' + p + 'close" />').insertAfter(ref).hide(); // advsearch close img

        $('.advsea-load-img').each(function(){
            $(this).remove();
        });
        as.ld = $('<img src="' + _load + '" class="advsea-load-img" alt="' + _loadAlt + '" id="' + p + 'load" />').insertAfter(ref).hide(); // advsearch load img
        as.rw = $('#' + p + 'advsea-reswin').hide().removeClass('init'); // advsearch results window - hide window

        as.cl.click(function() {
            // adds the closeSearch function to the on click on close image.
            closeSearch(as);
            return false;
        });

        if (!as.ls) {
            // with non livesearch adds the doSearch function to the submit button
            as.ss.click(function() {
                doSearch(as);
                return false;
            });
        } else {
            // with the livesearch mode, adds the doLiveSearch function. Launched after each typed character.
            as.si.keyup(function() {
                doLiveSearch(as);
            });
        }

        if (as.si.length) {
            // add the doSearch function to the input field. Launched after each typed character.
            as.si.keydown(function(e) {
                var keyCode = e.keyCode || e.which;
                if (keyCode === 13) {
                    doSearch(as);
                }
            });
        }

        if ((as.ii !== 'all')) {
            // no results displayed the first time
            return false;
        }
        doSearch(as); // display results
    }

    $.fn.serializeObject = function() {
        var o = {};
        var a = this.serializeArray();
        $.each(a, function() {
            if (o[this.name] !== undefined) {
                if (!o[this.name].push) {
                    o[this.name] = [o[this.name]];
                }
                o[this.name].push(this.value || '');
            } else {
                o[this.name] = this.value || '';
            }
        });
        return o;
    };

    function doLiveSearch(as) {
        if (as.lt) {
            window.clearTimeout(as.lt);
        }
        as.lt = setTimeout(function() {
            doSearch(as);
        }, 400);
    }

    function doSearch(as) {
        var p = as.asid + '_';      // prefix for the instance

        if (!as.ls && as.is)
            return false;  // search already launched

        // search term analysis
        st = '';
        if (as.si.length) {
            // simple search
            st = as.si.val();
        } else if (as.se.length) {  // multiple select input
            sl = new Array();
            as.se.find('option:selected').each(function(i) {
                sl.push($(this).attr('value'));
            }); // get the selected options
            st = sl.join(" "); // concatenation of the selected options
        }
        if (st === as.cdt) {
            st = ''; // box text is equivalent to an empty string
        }
        as.st = st;

        if (as.si.length && (st.length !== 0) && as.ls && (st.length < as.mc)) {
            return false; // liveSearch needs minChars before to start
        }

        // form content as serialized object
        as.fm = JSON.stringify($('#' + p + 'advsea-form').serializeObject());

        // ======================== we start the search
        as.is = true;
        if (!as.ls) {
            as.ss.attr('disabled', 'disabled');  // submit button disabled
        }

        var pars = {
            asid: as.asid,
            asform: as.fm,
            sub: as.sb
        };
        pars[as.sx] = as.st;

        as.cl.hide(); // hide the close button
        as.ld.show(); // show the load button

        $.getJSON(as.arh, pars, function(data) {
            if (data) {
                var ids = '';
                if (data.ids) {
                    ids = data.ids;
                }
                var json = '';
                if (data.json) {
                    json = data.json;
                }
                var html = '';
                if (data.html) {
                    html = data.html;
                }
                as.ppg = parseInt(data.ppg);	// perPage
                as.ofs = parseInt(data.ofs);	// offset
                as.pgt = parseInt(data.pgt);	// paging type
                as.nbr = parseInt(data.nbr);	// nb results
                as.opc = parseFloat(data.opc);	// opacity
                as.eff = data.eff;				// effect

                as.rw.hide();
                as.rw.html(html).css('opacity', as.opc).reswinDown(as.eff);
                if (as.gmp && json) {
                    gmUpdateMap(as.gmp, json);
                }
                if (as.pgt === 1) {
                    initPrevNext(as); // add the prevNext function to the next link
                } else if (as.pgt === 2) {
                    initPageLinks(as); // add the links to the page numbers
                }
            }
            if (!as.ls) {
                as.ss.removeAttr('disabled'); // submit button enabled
            }

            as.ld.hide(); // hide the load button
            as.cl.show(); // show the close button
            as.is = false; // new search allowed
        });
    }

    $.fn.advSearchInit = function(instance) {
        activateAsInstance(instance);
        return this;
    };

    function closeSearch(as) {
        as.rw.reswinUp(as.eff);
        as.cl.hide();
        as.ld.hide();
        if (as.si.length) {
            as.si.val('');
            as.si.prop('placeholder', as.cdt);
        }
        as.is = false;
        if (!as.ls)
            as.ss.removeAttr('disabled'); // enabled the submit button
    }

//============================================== Previous / next functions ==========================

    function initPrevNext(as) {  // add previous & next links after the display of results
        if (as) {
            var next = as.rw.find('.advsea-next a');
            next.attr("href", "javascript:void(0);"); // remove href
            next.click(function() {
                prevNext(as, 1);
            });
            var prev = as.rw.find('.advsea-previous a');
            prev.attr("href", "javascript:void(0);"); // remove href
            prev.click(function() {
                prevNext(as, -1);
            });
        }
    }

    function prevNext(as, dir) { // update of the page of results
        var ofs = as.ofs + (dir * as.ppg);
        var pars = {
            asid: as.asid,
            asform: as.fm,
            sub: as.sb
        };
        pars[as.sx] = as.st;
        pars[as.ox] = ofs;

        $.getJSON(as.arh, pars, function(data) {
            if (data) {
                var ids = '';
                if (data.ids)
                    ids = data.ids;
                var json = '';
                if (data.json)
                    json = data.json;
                var html = '';
                if (data.html)
                    html = data.html;

                as.ofs = parseInt(data.ofs);	// offset
                if (as.gmp && json) {
                    gmUpdateMap(as.gmp, json);
                }

                as.rw.reswinUp(as.eff);
                as.rw.html(html).reswinDown(as.eff);
                initPrevNext(as);
            }
        });
    }

//============================================== Page number links ==========================

    function initPageLinks(as) {  // add link to each page number
        if (as) {
            var links = as.rw.find('.advsea-page a').not('.advsea-current-page a');
            links.each(function() {
                var attr = $(this).attr("href");
                $(this).attr("href", "javascript:void(0);"); // remove href
                var rg = /&offset=([0-9]*)/i;
                var ofs = rg.exec(attr);
                $(this).click(function() {
                    pageLink(as, ofs[1]);
                });
            });
        }
    }

    function pageLink(as, ofs) { // add page link
        var pars = {
            asid: as.asid,
            asform: as.fm,
            sub: as.sb
        };
        pars[as.sx] = as.st;
        pars[as.ox] = ofs;

        $.getJSON(as.arh, pars, function(data) {
            if (data) {
                var ids = '';
                if (data.ids)
                    ids = data.ids;
                var json = '';
                if (data.json)
                    json = data.json;
                var html = '';
                if (data.html)
                    html = data.html;

                as.ofs = parseInt(data.ofs);	// offset
                if (as.gmp && json) {
                    gmUpdateMap(as.gmp, json);
                }

                as.rw.reswinUp(as.eff);
                as.rw.html(html).reswinDown(as.eff);
                initPageLinks(as);
            }
        });
    }

    activateSearch(); // as soon as the DOM is loaded
});