/*
 * advsearch 1.0.0 - package AdvSearch - jQuery 1.10.2
 * author:  Coroico - www.revo.wangba.fr - 15/05/2012
 *          goldsky - goldsky@virtudraft.com - 23/12/2013
 *
 * Licensed under the GPL license: http://www.gnu.org/copyleft/gpl.html
 */

jQuery(function($) {
    // minimum number of characters. Should be coherent with advSearch snippet call
    var _minChars = 3;
    var blockHistoryEvent = false;
    var gMapMarkers = [];
    var markersArray = [];
    var gMapHolder;
    var searchTracker = [];

    // Google Map
    $.fn.advSearchGMap = function(advInstance, options) {
        var mapObj = this;
        var gMap = null;
        var _this = this;

        var defaults = $.extend({
            zoom: 5,
            centerLat: 0,
            centerLon: 0
        });

        var settings = $.extend({}, settings, defaults, options);

        _this.initialize = function() {
            var mapOptions = {
                zoom: settings.zoom
            };

            gMap = new google.maps.Map(mapObj.get(0), mapOptions);

            if (!gMapMarkers || gMapMarkers.length === 0) {
                if ((settings.centerLat === 0) && (settings.centerLon === 0)) {
                    var initialLocation = new google.maps.LatLng(0, 0);
                    var browserSupportFlag = new Boolean();

                    // https://developers.google.com/maps/articles/geolocation
                    // Try W3C Geolocation (Preferred)
                    if (navigator.geolocation) {
                        browserSupportFlag = true;
                        navigator.geolocation.getCurrentPosition(function(position) {
                            initialLocation = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
                            gMap.setCenter(initialLocation);
                        }, function() {
                            handleNoGeolocation(browserSupportFlag);
                        });
                    }
                    // Browser doesn't support Geolocation
                    else {
                        browserSupportFlag = false;
                        handleNoGeolocation(browserSupportFlag);
                    }

                    function handleNoGeolocation(errorFlag) {
                        if (errorFlag === true) {
                            alert("Geolocation service failed.");
                        } else {
                            alert("Your browser doesn't support geolocation.");
                        }
                        gMap.setCenter(initialLocation);
                    }
                } else {
                    gMap.setCenter(new google.maps.LatLng(settings.centerLat, settings.centerLon));
                }
            } else {
                var bounds = new google.maps.LatLngBounds();
                var as = JSON.parse(advInstance);
                $.each(gMapMarkers, function(index, item) {
                    var markerOptions = {
                        position: item['position'],
                        map: gMap,
                        title: item['title'],
                        urlID: item['urlID']
                    };

                    var marker = new google.maps.Marker(markerOptions);

                    google.maps.event.addListener(marker, 'click', function(e) {
                        $.ajax({
                            url: as.gmpWin,
                            cache: false,
                            data: {
                                urlID: item['urlID']
                            },
                            'dataType': 'html',
                            'success': function(data) {
                                var infowindow = new google.maps.InfoWindow({
                                    content: data
                                });
                                infowindow.open(gMap, marker);
                            }
                        });
                    });

                    markersArray.push(marker);
                    bounds.extend(item['position']);
                });
                gMap.fitBounds(bounds);
            }

            // save the object for cleaning service
            gMapHolder = gMap;

            return gMap;
        };

        _this.getMarkers = function() {
            return markersArray;
        };

        _this.getOptions = function() {
            return this.settings;
        };

        _this.setOptions = function(settings) {
            this.settings = $.extend({}, this.settings, settings);
        };

        return _this;
    };

    $.fn.advSearchInit = function(as) {
        as.ss = $('#' + as.asid + '_' + 'advsea-submit');  // advsearch submit button if it exists
        if (as.ss) {
            as.ss.unbind();  // detach existing function
        }
        as.cl = $(as.aci);  // advsearch close button if it exists
        if (as.cl) {
            as.cl.unbind();  // detach existing function
        }
        activateAsInstance(as);
        return this;
    };

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

    /**
     * activate search instances
     * @param object opt options
     * @returns {undefined}
     */
    function activateSearch(opt) {
        // Each advsearch instance has its own index ias
        for (var ias = 0; ias < advsea.length; ias++) {
            var asv = eval('(' + advsea[ias] + ')');
            asv.hstx = false;
            if (opt && opt.hst) {
                asv.hstx = true;
            }
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
        if (!as.pax) {
            // page index
            as.pax = 'page';
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

        $('.advsea-close-img').each(function() {
            $(this).remove();
        });
        as.cl = $(as.aci).addClass('advsea-close-img').insertAfter(ref).hide(); // advsearch close img
        $('.advsea-load-img').each(function() {
            $(this).remove();
        });
        as.ld = $(as.ali).addClass('advsea-load-img').insertAfter(ref).hide(); // advsearch load img
        as.rw = $('#' + p + 'advsea-reswin').hide().removeClass('init'); // advsearch results window - hide window

        as.cl.click(function() {
            // adds the closeSearch function to the on click on close image.
            closeSearch(as);
            return false;
        });

        if (!as.ls) {
            // with non livesearch adds the doSearch function to the submit button
            as.ss.click(function() {
                return doSearch(as);
//                return false;
            });
        } else {
            // with the livesearch mode, adds the doLiveSearch function. Launched after each typed character.
            as.si.keyup(function() {
                return doLiveSearch(as);
            });
        }

        if (as.si.length) {
            // add the doSearch function to the input field. Launched after each typed character.
            as.si.keydown(function(e) {
                var keyCode = e.keyCode || e.which;
                if (keyCode === 13) {
                    e.preventDefault();
                    return doSearch(as);
                }
            });
        }

        if ((as.ii !== 'all')) {
            // no results displayed the first time
            return false;
        }

        return doSearch(as); // display results
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

    function setGMapMarkers(as, json) {
        if (typeof (google) !== 'object') {
            console.log('Missing google object');
            return;
        }
        gMapMarkers = []; // reset data
        // reset existing markers
        if (markersArray) {
            for (var i in markersArray) {
                markersArray[i].setMap(null);
            }
            markersArray.length = 0;
        }

        $.each(json, function(index, item) {
            if (!item[as['gmpLt']] || !item[as['gmpLn']]) {
                return;
            }
            var options = {
                position: new google.maps.LatLng(item[as['gmpLt']], item[as['gmpLn']]),
                title: item[as['gmpTtl']],
                urlID: item['id']
            };
            gMapMarkers.push(options);
            // check existing gMap's instance
            if (gMapHolder) {
                var marker = new google.maps.Marker(options);
                marker.setMap(gMapHolder);
                markersArray.push(marker);
            }
        });
    }

    function doLiveSearch(as) {
        if (as.lt) {
            window.clearTimeout(as.lt);
        }
        as.lt = window.setTimeout(function() {
            return doSearch(as);
        }, 400);
    }

    function doSearch(as) {
        var p = as.asid + '_';      // prefix for the instance

        if (!as.ls && as.is) {
            return false;  // search already launched
        }

        // search term analysis
        var st = '';
        if (as.si.length) {
            // simple search
            st = as.si.val();
        } else if (as.se.length) {  // multiple select input
            var sl = new Array();
            as.se.find('option:selected').each(function(i) {
                sl.push($(this).attr('value'));
            }); // get the selected options
            st = sl.join(" "); // concatenation of the selected options
        }
        if (st === as.cdt) {
            st = ''; // box text is equivalent to an empty string
        }
        as.st = st;

        if ((as.ii !== 'all') && (st.length < as.mc)) {
            return false;
        }

        // ======================== we start the search
        as.is = true;
        if (!as.ls) {
            as.ss.attr('disabled', 'disabled');  // submit button disabled
        }

        var pars = {
            asid: as.asid,
            sub: as.sb
        };
        pars[as.sx] = as.st;

        if (as.hst) {
            var uri = new URI(document.location.href),
                    uriQuery = uri.query(true);
        }
        if (as.hst && as.hstx && !as.nav) {
            if (uriQuery[as.pax]) {
                pars[as.pax] = uriQuery[as.pax];
            }
        } else if (typeof (as.pag) === 'number' && as.pag > 0) {
            pars[as.pax] = as.pag;
        } else {
            pars[as.pax] = 1;
        }

        if (typeof(pars[as.pax]) === 'undefined' || pars[as.pax] === 'undefined') {
            pars[as.pax] = 1;
        }

        // form content as serialized object
        var formDom = $('#' + p + 'advsea-form');
        var formVals = formDom.serializeObject();

        /**
         * Override form values if this is initiated from URI
         *
         * @param mixed as.hst  history is enabled
         * @param bool  as.hstx browser event is initiated
         * @param boold as.nav  pagination link is initiated
         */
        if (as.hst && !as.hstx && !as.nav) {
            if (searchTracker.length === 0) {
                $.extend(formVals, uriQuery)
            } else {
                formVals = $.extend({}, uriQuery, formVals);
            }
        }

        as.fm = JSON.stringify(formVals);
        as.pag = (as.fm !== searchTracker[searchTracker.length - 1]) ? 1 : parseInt(as.pag);	// page

        pars['asform'] = as.fm;

        as.cl.hide(); // hide the close button
        as.ld.show(); // show the load button
        as.rw.css('opacity', as.opc / 2);

        return $.getJSON(as.arh, pars, function(data) {
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
                as.pag = parseInt(data.pag);	// page
                as.pgt = parseInt(data.pgt);	// paging type
                as.nbr = parseInt(data.nbr);	// nb results
                as.opc = parseFloat(data.opc);	// opacity
                as.eff = data.eff;				// effect
                as.cdf = data.cdf;				// clearDefault

                as.rw.hide();
                as.rw.html(html).css('opacity', as.opc).reswinDown(as.eff);

                if (as.gmp && json) {
                    var jsonObj = JSON.parse(json);
                    setGMapMarkers(as, jsonObj);
                }
                if (as.pgt === 1) {
                    initPageType1(as);
                } else if (as.pgt === 2) {
                    initPageType2(as);
                } else if (as.pgt === 3) {
                    initPageType3(as);
                }
            }
            if (!as.ls) {
                as.ss.removeAttr('disabled'); // submit button enabled
            }
            as.ld.hide(); // hide the load button
            as.cl.show(); // show the close button
            as.is = false; // new search allowed
            if (as.hst) {
                blockHistoryEvent = true;
                setHistory(as, pars);
            }

            searchTracker.push(JSON.stringify(formDom.serializeObject()));
        });
    }

    function closeSearch(as) {
        as.rw.reswinUp(as.eff);
        as.cl.hide();
        as.ld.hide();
        if (as.si.length) {
            $('#' + as.px + 'advsea-form')[0].reset();
            as.si.prop('placeholder', as.cdt);
            History.pushState({}, document.title, document.location.origin + document.location.pathname);
        }
        as.is = false;
        if (!as.ls) {
            as.ss.removeAttr('disabled'); // enabled the submit button
        }
    }

//============================================== Previous / next functions ==========================

    function initPageType1(as) {  // add previous & next links after the display of results
        if (as) {
            var next = as.rw.find('.advsea-next a');
            next.attr("href", "javascript:void(0);"); // remove href
            next.click(function() {
                prevNext(as, 1);
                return false;
            });
            var prev = as.rw.find('.advsea-previous a');
            prev.attr("href", "javascript:void(0);"); // remove href
            prev.click(function() {
                prevNext(as, -1);
                return false;
            });
        }

    }

//============================================== Page number links ==========================

    function initPageType2(as) {  // add link to each page number
        if (as) {
            var links = as.rw.find('.advsea-page a').not('.advsea-current-page a');
            links.each(function() {
                var attr = $(this).attr("href");
                $(this).attr("href", "javascript:void(0);"); // remove href
                var rg = /&page=([0-9]*)/i;
                var pag = rg.exec(attr);
                $(this).click(function() {
                    pageLink(as, pag[1]);
                    return false;
                });
            });
        }

    }

//============================ Previous / next + Page number links ==========================

    function initPageType3(as) {
        if (as) {
            var links = as.rw.find('.advsea-page a').not('.advsea-current-page a');
            links.each(function() {
                var attr = $(this).attr("href");
                $(this).attr("href", "javascript:void(0);"); // remove href
                var rg = /&page=([0-9]*)/i;
                var pag = rg.exec(attr);
                $(this).click(function() {
                    pageLink(as, pag[1]);
                    return false;
                });
            });
            var next = as.rw.find('.advsea-next a');
            next.attr("href", "javascript:void(0);"); // remove href
            next.click(function() {
                prevNext(as, 1);
                return false;
            });
            var prev = as.rw.find('.advsea-previous a');
            prev.attr("href", "javascript:void(0);"); // remove href
            prev.click(function() {
                prevNext(as, -1);
                return false;
            });
        }
    }

//======================================== links generators ==========================

    function prevNext(as, dir) { // update of the page of results
        as.pag = as.pag - 0 + dir; // typecasting
        blockHistoryEvent = true;
        as.nav = 1;
        return doSearch(as);
    }

    function pageLink(as, pag) { // add page link
        as.pag = pag - 0; // typecasting
        blockHistoryEvent = true;
        as.nav = 1;
        return doSearch(as);
    }

//============================================== history.js ==========================

    var History = window.History;

    function setHistory(as, pars) {
        if (!History || !History.enabled || as.ii !== 'all' || as.hstx) {
            return;
        }
        var href = buildUrl(as, pars);
        if (href !== document.location.href) {
            History.pushState(pars, document.title, href);
        }
        return href;
    }

    function buildUrl(as, pars) {
        var asformArr = new Array();
        var parseForm = JSON.parse(as.fm);
        var uri = new URI(document.location.href),
                uriQuery = uri.query(true);
        var newUri = $.extend({}, uriQuery, parseForm, {sub: as.sb});
        if (typeof(newUri[as.pax]) !== 'undefined' || newUri[as.pax] !== 'undefined') {
            newUri[as.pax] = pars[as.pax];
        } else {
            newUri[as.pax] = 1;
        }
        $.each(newUri, function(index, item) {
            $.merge(asformArr, [index + '=' + item]);
        });
        var asformStr = "?" + asformArr.join('&');
        return document.location.origin + document.location.pathname + asformStr;
    }

    if (History && History.enabled) {
        History.Adapter.bind(window, 'statechange', function() {
            if (!blockHistoryEvent) {
                activateSearch({hstx: 1});
            } else {
                // resetting value
                blockHistoryEvent = false;
            }
        });
    }

    activateSearch(); // as soon as the DOM is loaded

});