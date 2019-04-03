require(['jquery', 'priceBox'], function ($, priceBox) {
    // Before initialise, check we're active
    if (!pureclarityConfig.state.isActive) {
        return; 
    }

    // If search or category page prepare elements
    if (pureclarityConfig.search.isClientSearch && pureclarityConfig.search.DOMSelector != "") {
        var pcContainer = document.createElement('div');
        var wrapper = document.createElement('div');
        $(wrapper).addClass('pureclarity-wrapper');
        $(pcContainer).addClass('pureclarity-container').attr("data-pureclarity", pureclarityConfig.search.dataValue);
        $(pureclarityConfig.search.DOMSelector).wrap(wrapper);
        $(".pureclarity-wrapper").append(pcContainer);
    }

    var Base64 = {
        _keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",
        encode : function (input) {
            var output = "";
            var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
            var i = 0;
            input = Base64._utf8_encode(input);
            while (i < input.length) {
                chr1 = input.charCodeAt(i++);
                chr2 = input.charCodeAt(i++);
                chr3 = input.charCodeAt(i++);
                enc1 = chr1 >> 2;
                enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
                enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
                enc4 = chr3 & 63;
                if (isNaN(chr2)) {
                    enc3 = enc4 = 64;
                } else if (isNaN(chr3)) {
                    enc4 = 64;
                }
                output = output + this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) + this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);
            }
            return output;
        },
        decode : function (input) {
            var output = "";
            var chr1, chr2, chr3;
            var enc1, enc2, enc3, enc4;
            var i = 0;
            input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
            while (i < input.length) {
                enc1 = this._keyStr.indexOf(input.charAt(i++));
                enc2 = this._keyStr.indexOf(input.charAt(i++));
                enc3 = this._keyStr.indexOf(input.charAt(i++));
                enc4 = this._keyStr.indexOf(input.charAt(i++));
                chr1 = (enc1 << 2) | (enc2 >> 4);
                chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
                chr3 = ((enc3 & 3) << 6) | enc4;
                output = output + String.fromCharCode(chr1);
                if (enc3 != 64) {
                    output = output + String.fromCharCode(chr2);
                }
                if (enc4 != 64) {
                    output = output + String.fromCharCode(chr3);
                }
            }
            output = Base64._utf8_decode(output);
            return output;
        },
        _utf8_encode : function (string) {
            string = string.replace(/\r\n/g,"\n");
            var utftext = "";
            for (var n = 0; n < string.length; n++) {
                var c = string.charCodeAt(n);
                if (c < 128) {
                    utftext += String.fromCharCode(c);
                } else if ((c > 127) && (c < 2048)) {
                    utftext += String.fromCharCode((c >> 6) | 192);
                    utftext += String.fromCharCode((c & 63) | 128);
                } else {
                    utftext += String.fromCharCode((c >> 12) | 224);
                    utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                    utftext += String.fromCharCode((c & 63) | 128);
                }
            }
            return utftext;
        },
        _utf8_decode : function (utftext) {
            var string = "";
            var i = 0;
            var c = c1 = c2 = 0;
            while (i < utftext.length ) {
                c = utftext.charCodeAt(i);
                if (c < 128) {
                    string += String.fromCharCode(c);
                    i++;
                } else if ((c > 191) && (c < 224)) {
                    c2 = utftext.charCodeAt(i+1);
                    string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
                    i += 2;
                } else {
                    c2 = utftext.charCodeAt(i+1);
                    c3 = utftext.charCodeAt(i+2);
                    string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
                    i += 3;
                }
            }
            return string;
        }
    }

    var stringify = function (obj) {
        var t = typeof (obj);
        if (t != "object" || obj === null) {
            if (t == "string") {
                obj = '"' + obj + '"'; 
            }
            return String(obj);
        } else {
            if (JSON) {
                return JSON.stringify(obj); 
            }
            var n, v, json = [], arr = (obj && obj.constructor == Array);
            for (n in obj) {
                v = obj[n];
                t = typeof(v);
                if (obj.hasOwnProperty(n)) {
                    if (t == "string") {
                        v = '"' + v + '"'; 
                    } else if (t == "object" && v !== null) {
                        v = $.stringify(v); 
                    }
                    json.push((arr ? "" : '"' + n + '":') + String(v));
                }
            }
            return (arr ? "[" : "{") + String(json) + (arr ? "]" : "}");
        }
    }

    // Initialise form variables
    var inputs = $("[name='form_key']");
    pureclarityConfig.formkey = inputs.length>0?inputs[0].value:pureclarityConfig.formkey;
    var uenc = Base64.encode(document.location.href).replace(/=/g, ",").replace(/\//g, "_");
    var addToCartUrlPrefix = pureclarityConfig.baseUrl + "checkout/cart/add/uenc/" + encodeURIComponent(uenc) + "/product/";

    // Initialise PureClarity
    (function (w, d, s, u, f) {
        w['PureClarityObject'] = f;w[f] = w[f] || function () { 
            (w[f].q = w[f].q || []).push(arguments)
        }
        var p = d.createElement(s), h = d.getElementsByTagName(s)[0];
        p.src = u;p.async=1;h.parentNode.insertBefore(p, h);
    })(window, document, 'script', window.pureclarityConfig.apiUrl, '_pc');

    // Execute login cusotmer details event if needed
    if (pureclarityConfig.customer.triggerLogin) {
        _pc("customer_details", pureclarityConfig.customer.customer);
    }
    
    // Execute tracking events
    
    if (pureclarityConfig.state.isLogout) {
        _pc('customer_logout');
    }

    if (!pureclarityConfig.state.serversideMode) {
        _pc('currency', pureclarityConfig.currency);
        _pc('page_view');
        _pc('callback_event', function (type) {
            require([pureclarityConfig.swatchRenderer], function () {
                if (type == "search") {
                    var $sideBarAdditional = $(".sidebar-additional");
                    $sideBarAdditional.appendTo($("#pc-filter"));
                }
                var items = $("[pureclarity-data-item]");
                for (var i=0; i<items.length; i++) {
                    var $item = $(items[i]);
                    var id = $item.attr("pureclarity-data-item");
                    
                    // Manage Add To Card values
                    var addToCardForms = $item.find("form[data-role='tocart-form']");
                    if (addToCardForms.length>0) {
                        for (var f=0; f<items.length; f++) {
                            var $addToCartForm = $(addToCardForms[f]);
                            var addToCartUrl =  addToCartUrlPrefix + id + "/";
                            $addToCartForm.attr("action",addToCartUrl);
                            var uencInputs = $addToCartForm.find("input[name='uenc']");
                            if (uencInputs.length>0) {
                                var formInputUenc = Base64.encode(addToCartUrl).replace(/=/g, ",");
                                $(uencInputs[0]).val(formInputUenc);
                            }
                            var formKeyInputs = $addToCartForm.find("input[name='form_key']");
                            if (formKeyInputs.length>0) {
                                $(formKeyInputs[0]).val(pureclarityConfig.formkey);
                            }
                        }
                    }

                    // Manage Secondary Action Values
                    var addToLinks = $item.find("[data-role='add-to-links']");
                    if (addToLinks.length>0) {
                        for (var a=0; a<items.length; a++) {
                            var $addToLink = $(addToLinks[a]);

                            // Manage Wish List
                            var wishlist = $addToLink.children(".towishlist");
                            if (wishlist.length>0) {
                                var wishListData = {
                                    action: pureclarityConfig.wishListUrl,
                                    data: {
                                        product: id,
                                        uenc: uenc
                                    }
                                };
                                $(wishlist[0]).attr("data-post", stringify(wishListData));
                            }

                            // Manage Compare
                            var compare = $addToLink.children(".tocompare");
                            if (compare.length>0) {
                                var compareData = {
                                    action: pureclarityConfig.compareUrl,
                                    data: {
                                        product: id,
                                        uenc: uenc
                                    }
                                };
                                $(compare[0]).attr("data-post", stringify(compareData));
                            }
                        }
                    }

                    // Manage Swatches
                    if (pureclarityConfig.showSwatches) {
                        var swatchOptions = $item.find(".swatch-opt");
                        $(swatchOptions).each(function () {
                            var option = $(this);
                            var jsonConfig = option.data().pureclarityJsonconfig;
                            var swatchRenderJson = option.data().pureclaritySwatchrenderjson;
                            if (jsonConfig && swatchRenderJson) {
                                swatchRenderJson.numberToShow = pureclarityConfig.swatchesToShow;
                                option.SwatchRenderer(swatchRenderJson);
                                var priceBoxSelector = "[data-role=priceBox][data-product-id=" + id + "]";
                                $(priceBoxSelector).priceBox({
                                    'priceConfig': {
                                        priceFormat: jsonConfig.priceFormat,
                                        prices: jsonConfig.prices
                                    }
                                });
                            }
                        });
                    }
                }
            });
        });

        if (pureclarityConfig.product) {
            _pc("product_view", { id: pureclarityConfig.product.Id });
        }

        if (pureclarityConfig.order) {
            _pc('order:addTrans', pureclarityConfig.order.transaction);
            for (var i=0; i<pureclarityConfig.order.items.length; i++) {
                _pc('order:addItem', pureclarityConfig.order.items[i]);
            }
            _pc('order:track');
        }
    } else {
        _pc('set_cache_filter', { _size: 2000, requesttype: "both" });
    }

});