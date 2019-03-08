define(['jquery', 'priceBox'], function ($, priceBox) {
    // Before initialise, check we're active
    if (!pureclarityConfig.state.isActive) {
        return; 
    }

    return {
        push: function (event, value) {
            if (window['PureClarityObject'] && typeof window['PureClarityObject'].push === 'function') {
                window['PureClarityObject'].push([event, value]);
            } else if (typeof _pc === 'function') {
                _pc(event, value);
            }
        },
        sectionUpdated: function(dataId, section){
            var cookieSections = $.cookieStorage.get('section_data_ids');
            return dataId && cookieSections &&
                   cookieSections[section] != dataId;
        }
    }

});