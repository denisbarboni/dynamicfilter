jQuery(document).ready(function () {
    var $j = jQuery.noConflict();

    if ($j("div.cc-dynamic-filter select[id='select-filter-category_id-[category_id]']").length) {

        $j("div.cc-dynamic-filter .cc-more:first").removeClass('cc-more');

        if (!$j("div.cc-dynamic-filter .cc-more").length) {

            $j('#dynamicFilter .cc-btn-more').hide();
        }
    }

    $j('div.cc-dynamic-filter').hide();

    // $j("div.cc-dynamic-filter select[id='select-filter-category_id-[category_id]']").parent().parent().remove();
    // $j("div.cc-dynamic-filter select[id='select-filter-subcategory_id-[subcategory_id]']").parent().parent().remove();

    setTimeout(function () {
        setSelectsValuesByUrl();
        $j('div.cc-dynamic-filter').slideDown(1000);
    }, 1000);

    function getUrlParameter() {
        var sPageURL = window.location.search.substring(1), sURLVariables = sPageURL.split('&');
        return sURLVariables;
    }

    function setSelectsValuesByUrl() {
        var urlParams = getUrlParameter();
        
        $j.each(urlParams, function (k, v) {
            var code = v.split('=')[0];
            var selectValue = v.split('=')[1];
            var selectElement = $j("div.cc-dynamic-filter select[name^='select-filter-"+ code +"']");
            if (selectElement.length == 1) {
                selectElement.val(selectValue);
                connectFilters(selectElement);
            }
        });
    }

    if ($j(window).width() < 768) {
        if ($j('div.cc-dynamic-filter select').length <= 2) {
            $j('.cc-dynamic-filter .cc-btn-more').hide();
        }
        $j('.cc-filters-grid .cc-more').first().prev().addClass('cc-more');
        $j('.cc-filters-grid .cc-more').first().prev().addClass('cc-more');
    }
    if ($j('div.cc-dynamic-filter select').length <= 4) {
        $j('.cc-dynamic-filter .cc-btn-more').hide();
    }

    $j('#dynamicFilter .cc-btn-more').click(function () {

        $j('.cc-dynamic-filter .cc-more').fadeToggle(500);

        $j(this).toggleClass('cc-plus-filter');
        $j(this).toggleClass('cc-minus-filter');
    });

    function loadingAjax() {

        $j('#dynamicFilter .ajax-request').toggle();
        $j('#dynamicFilter .cc-select-border').toggleClass('loading-request');
        $j('#dynamicFilter .cc-btn-filter').toggleClass('wait-request');
    }

    function getSelectsValues() {
        var selectedFilters = new Array();
        var unselectedFilters = new Array();
        var i = 0;
        var j = 0;
        $j('div.cc-dynamic-filter select').each(function () {
            var code = $j(this).attr('name').split('-')[2];
            if ($j(this).val() == '') {
                unselectedFilters[j] = code;
                j++;
            }else{
                selectedFilters[i] = [code, $j(this).val()];
                i++;
            }
        });

        return [selectedFilters, unselectedFilters];
    }

    function getSelectCategory() {
        var optionSelected = '';

        if ($j("div.cc-dynamic-filter select[id='select-filter-subcategory_id-[subcategory_id]']").length) {

            optionSelected = $j("div.cc-dynamic-filter select[id='select-filter-subcategory_id-[subcategory_id]'] option:selected");

            if (optionSelected.val() != '') {
                return optionSelected.attr('data-url');
            }

        }

        if ($j("div.cc-dynamic-filter select[id='select-filter-category_id-[category_id]']").length) {

            optionSelected = $j("div.cc-dynamic-filter select[id='select-filter-category_id-[category_id]'] option:selected");

            if (optionSelected.val() != '') {
                return optionSelected.attr('data-url');
            }
        }

        return 'dynamic-filter.html';
    }

    $j('div.cc-dynamic-filter select').change(function () {
        var params = '';
        var valuesFilters = getSelectsValues();
        var category = getSelectCategory();

        loadingAjax();

        valuesFilters = valuesFilters[0];

        $j.each(valuesFilters, function (k, v) {

            if (v[0] == 'category_id' || v[0] == 'subcategory_id') {
                return;
            }

            if (params != '') {
                params += '&';
            }

            params += v[0] + '=' + v[1];
        });

        if (params != '' || category != 'dynamic-filter.html') {
            var url = category + '?' + params;
            if (category == 'dynamic-filter.html') {
                url += '&dynamic_filter=1';
            }
            // console.log(url);
            window.location.href = url;
        }
    });

    $j('div.cc-dynamic-filter button.cc-btn-clean-filters').click(function () {
        $j("#dynamicFilter .cc-dynamic-filter select").val('');
        $j("#dynamicFilter .cc-dynamic-filter select").removeClass('selected');
        $j("#dynamicFilter .cc-dynamic-filter select").first().trigger('change');
    });

    function connectFilters(elm) {
        var filter = getSelectsValues();
        var selectedFilters = filter[0];
        var filters = filter[1];

        if (elm.val() == '') {
            elm.prev().show();
            elm.removeClass('selected');
        } else {
            elm.prev().hide();
            elm.addClass('selected');
        }

        $j.ajax({
            type: 'POST',
            url: 'dynamicfilter/filter/connectfilters',
            data: {selectedFilters, filters},
            dataType: 'json',
            async: false }).done( function (data) {
            console.log('Dynamic Filter Request success: ', true);
            var selectElement;

            if (data['filtersWithoutResult'].length) {

                $j.each(data.filtersWithoutResult, function (k, v) {
                    // console.log(k, v);
                    selectElement = $j("div.cc-dynamic-filter select[name^='select-filter-"+ v +"']");
                    // console.log(selectElement);
                    if (selectElement.length){
                        selectElement.parent().parent().slideUp(1000);
                    }
                });
            }
            // console.log(data.filtersWithResult);
            if (data['filtersWithResult']) {

                $j.each(data.filtersWithResult, function (k, v) {
                    // console.log(k, v);
                    selectElement = $j("div.cc-dynamic-filter select[name^='select-filter-"+ k +"']");
                    if (selectElement.length){
                        selectElement.find('option').not(':first').hide();
                        $j.each(v, function (a, b) {
                            // console.log(a, b);

                            if (!(b.items_found == false || typeof b.items_found == 'undefined')) {
                                b.items_found = ' ('+b.items_found+')';
                            } else {
                                b.items_found = '';
                            }
                            var optionFound = selectElement.find("option[value='"+b.value+"']");
                            var changeNumber = $j.trim(optionFound.text()).split('(');

                            changeNumber[1] = b.items_found;
                            optionFound.text(changeNumber[0] + ' ' + changeNumber[1]);
                            optionFound.show();

                            // selectElement.append($j('<option>', {
                            //     value: b.value,
                            //     text: b.label
                            // }));
                        });

                        if (selectElement.parent().parent().hasClass('cc-more')) {
                            if ($j('.cc-dynamic-filter .cc-more').hasClass('cc-minus-filter')) {
                                selectElement.parent().parent().fadeIn(500);
                            }
                        } else {
                            selectElement.parent().parent().fadeIn(500);
                        }
                    }
                });
            }
        }).fail( function (jqXHR, textStatus) {

            console.log("Dynamic Filter Request failed: " + textStatus);
        });
    }
});