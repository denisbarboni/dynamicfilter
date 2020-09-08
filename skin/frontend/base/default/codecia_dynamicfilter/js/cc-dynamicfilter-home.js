// jQuery(document).ready(function () {
//     var $j = jQuery.noConflict();
//
//     function isiPhone(){
//         return (
//             (navigator.platform.indexOf("iPhone") != -1) ||
//             (navigator.platform.indexOf("iPod") != -1)
//         );
//     }
//
//     $j("#dynamicFilter .cc-dynamic-filter select").val('');
//     $j("#dynamicFilter .cc-dynamic-filter select").removeClass('selected');
//
//     if ($j(window).width() < 768) {
//         if ($j('div.cc-dynamic-filter select').length <= 2) {
//             $j('.cc-dynamic-filter .cc-btn-more').hide();
//         }
//         $j('.cc-filters-grid .cc-more').first().prev().addClass('cc-more');
//         $j('.cc-filters-grid .cc-more').first().prev().addClass('cc-more');
//     }
//
//     if ($j('div.cc-dynamic-filter select').length <= 4) {
//         $j('.cc-dynamic-filter .cc-btn-more').hide();
//     }
//
//     $j('#dynamicFilter .cc-btn-more').click(function () {
//
//         if ($j(this).hasClass('cc-plus-filter')) {
//
//             $j(this).removeClass('cc-plus-filter');
//             $j(this).addClass('cc-minus-filter');
//
//             $j('.cc-dynamic-filter .cc-more').fadeIn(500);
//         } else {
//
//             $j(this).removeClass('cc-minus-filter');
//             $j(this).addClass('cc-plus-filter');
//
//             $j('.cc-dynamic-filter .cc-more').fadeOut(500);
//         }
//     });
//
//     function loadingAjax() {
//
//         $j('#dynamicFilter .ajax-request').toggle();
//         $j('#dynamicFilter .cc-select-border').toggleClass('loading-request');
//         $j('#dynamicFilter .cc-btn-filter').toggleClass('wait-request');
//     }
//
//     function getSelectsValues() {
//         var selectedFilters = new Array();
//         var unselectedFilters = new Array();
//         var i = 0;
//         var j = 0;
//         $j('div.cc-dynamic-filter select').each(function () {
//             var code = $j(this).attr('name').split('-')[2];
//             if ($j(this).val() == '') {
//                 unselectedFilters[j] = code;
//                 j++;
//             }else{
//                 selectedFilters[i] = [code, $j(this).val()];
//                 i++;
//             }
//         });
//
//         return [selectedFilters, unselectedFilters];
//     }
//
//     function getSelectCategory() {
//         var optionSelected = '';
//
//         if ($j("div.cc-dynamic-filter select[id='select-filter-subcategory_id-[subcategory_id]']").length) {
//
//             optionSelected = $j("div.cc-dynamic-filter select[id='select-filter-subcategory_id-[subcategory_id]'] option:selected");
//
//             if (optionSelected.val() != '') {
//                 return optionSelected.attr('data-url');
//             }
//
//         }
//
//         if ($j("div.cc-dynamic-filter select[id='select-filter-category_id-[category_id]']").length) {
//
//             optionSelected = $j("div.cc-dynamic-filter select[id='select-filter-category_id-[category_id]'] option:selected");
//
//             if (optionSelected.val() != '') {
//                 return optionSelected.attr('data-url');
//             }
//         }
//
//         return 'dynamic-filter.html';
//     }
//
//     $j('#dynamicFilter .cc-actions .btn-search button.cc-btn-filter').click(function () {
//         var params = '';
//
//         if ($j(this).hasClass('wait-request')) {
//
//             return false;
//         }
//
//         loadingAjax();
//
//         if (false){
//             //var valuesFilters = getSelectsValues();
//             //valuesFilters = valuesFilters[0];
//             //$j.each(valuesFilters, function (k, v) {
//             //    if (url != '') {
//             //        url += '&';
//             //    }
//             //    url += 'rb_' + v[0] + '=' + v[1];
//             //});
//             //if (url != '') {
//             //    window.location.href = 'searchanise/result?' + url;
//             //}
//         }else{
//             var valuesFilters = getSelectsValues();
//             var category = getSelectCategory();
//
//             console.log('valuesFilters', valuesFilters);
//             console.log('category', category);
//
//             valuesFilters = valuesFilters[0];
//             console.log('valuesFilters2', valuesFilters);
//
//             $j.each(valuesFilters, function (k, v) {
//
//                 console.log('v', v);
//                 console.log('v', v[0]);
//
//                 if (v[0] == 'category_id' || v[0] == 'subcategory_id') {
//                     return;
//                 }
//
//                 console.log('teste');
//
//                 if (params != '') {
//                     params += '&';
//                 }
//
//                 params += v[0] + '=' + v[1];
//             });
//
//             console.log('params', params);
//
//             if (params != '' || category != 'dynamic-filter.html') {
//                 var url = category + '?' + params;
//                 if (category == 'dynamic-filter.html') {
//                     url += '&dynamic_filter=1';
//                 }
//                 // console.log(url);
//                 window.location.href = url;
//             }
//         }
//     });
//
//     $j('#dynamicFilter .cc-actions button.cc-btn-clean-filters').click(function () {
//         $j("#dynamicFilter .cc-dynamic-filter select").val('');
//         $j("#dynamicFilter .cc-dynamic-filter select").removeClass('selected');
//         $j("#dynamicFilter .cc-dynamic-filter select").first().trigger('change');
//     });
//
//     $j('div.cc-dynamic-filter select').change(function () {
//         console.log('aaaa');
//         var filter = getSelectsValues();
//         console.log('filter', filter);
//         loadingAjax();
//
//         if ($j(this).hasClass('selected')) {
//             console.log('1');
//             if ($j(this).val() == '') {
//                 console.log('2');
//                 $j("#dynamicFilter .cc-actions button.cc-btn-clean-filters").trigger('click');
//             } else {
//                 console.log('3');
//                 filter[0] = [[$j(this).attr('name').split('-')[2], $j(this).val()]];
//                 filter[1] = [];
//                 $j.each($j('div.cc-dynamic-filter select').not($j(this)), function () {
//                     filter[1].push($j(this).attr('name').split('-')[2]);
//                 });
//             }
//         }
//
//         if ($j(this).val() == '') {
//             console.log('4', $j(this).prev());
//             $j(this).prev().show();
//             $j(this).removeClass('selected');
//         } else {
//             console.log('5', $j(this).prev());
//             $j(this).prev().hide();
//             $j(this).addClass('selected');
//         }
//
//         $j.ajax({
//             url: 'dynamicfilter/filter/connectfilters',
//             type: "POST",
//             data: {
//                 selectedFilters: filter[0],
//                 filters: filter[1]
//             },
//             async: true,
//             dataType: 'json'
//         }).done(function (data) {
//             var selectElement;
//
//             console.log('data', data);
//
//             if (data['filtersWithoutResult'].length) {
//                 $j.each(data['filtersWithoutResult'], function (k, v) {
//
//                     selectElement = $j("div.cc-dynamic-filter select[name^='select-filter-"+ v +"']");
//                     selectElement.val('');
//                     selectElement.prev().show();
//                     selectElement.removeClass('selected');
//
//                     if(!isiPhone()){
//                         console.log('N');
//                         selectElement.find('option').not(':first').hide();
//                     }else{
//                         console.log('S');
//                         selectElement.find('option').not(':first').remove();
//                     }
//
//                     if (selectElement.length){
//                         selectElement.parent().parent().fadeOut(500);
//                     }
//                 });
//             }
//
//             if (data['filtersWithResult']) {
//                 $j.each(data['filtersWithResult'], function (k, v) {
//
//                     selectElement = $j("div.cc-dynamic-filter select[name^='select-filter-"+ k +"']");
//                     if (selectElement.length){
//
//                         selectElement.val('');
//                         selectElement.prev().show();
//                         selectElement.removeClass('selected');
//
//                         if(!isiPhone()){
//                             console.log('M');
//                             selectElement.find('option').not(':first').hide();
//                         }else{
//                             console.log('N');
//                             selectElement.find('option').not(':first').remove();
//                         }
//
//                         $j.each(v, function (a, b) {
//
//                             if (!(b.items_found == false || typeof b.items_found == 'undefined')) {
//                                 b.items_found = ' ('+b.items_found+')';
//                             } else {
//                                 b.items_found = '';
//                             }
//
//                             var optionFound = selectElement.find("option[value='"+b.value+"']");
//                             var changeNumber = $j.trim(optionFound.text()).split('(');
//
//                             changeNumber[1] = b.items_found;
//                             optionFound.text(changeNumber[0] + ' ' + changeNumber[1]);
//
//                             if(!isiPhone()){
//                                 optionFound.show();
//                                 console.log('O');
//
//                             }else{
//                                 console.log('P', b.label );
//                                 console.log('B', b);
//
//                                 selectElement.append($j('<option>', {
//                                     value: b.value,
//                                     text: b.label + b.items_found,
//                                 }).attr('data-url', b.data_url));
//                             }
//
//                         });
//
//                         if (selectElement.parent().parent().hasClass('cc-more')) {
//                             if ($j('.cc-dynamic-filter .cc-more').hasClass('cc-minus-filter')) {
//                                 selectElement.parent().parent().fadeIn(500);
//                             }
//                         } else {
//                             selectElement.parent().parent().fadeIn(500);
//                         }
//                     }
//                 });
//             }
//
//             loadingAjax();
//         }).fail(function(jqXHR, textStatus) {
//
//             console.log("Dynamic Filter Request Fail: " + textStatus);
//
//             loadingAjax();
//         });
//     });
// });
//
