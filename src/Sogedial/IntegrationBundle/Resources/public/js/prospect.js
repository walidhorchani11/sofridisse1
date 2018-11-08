var $product_search_tree = $("#product-search-tree");
var $product_search_form = $("#prospect-product-search-form");
var $product_search_input = $("#prospect-product-search-input");
var $submit_button = $('#prospect-submit-button');
var $products_assortiment_amount = $("#products-assortiment-amount");

var fetch_url = "sogedial_integration_admin_fetch_prospect_assortiment_tree";
var submit_url = "sogedial_integration_admin_submit_prospect_assortiment";
var back_url = "sogedial_integration_admin_mesprospects"

/**
 * Easily retrieve query string parameters.
 * Source: https://stackoverflow.com/questions/901115/how-can-i-get-query-string-values-in-javascript
 *
 * @param {string} name Name of the query string parameter whose value must be fetched.
 * @param {string} [url] Optional url.
 */
function getParameterByName(name, url) {
    var searchParams = new URLSearchParams(window.location.search);

    return searchParams.get(name);
}

fetch_url_params = {
    "codeProspect": getParameterByName('codeProspect'),
    "_locale": locale
};

back_url_params = {
    "_locale": locale
 }

function data_submit(selectedNodes){
    return {
        codeProspect: getParameterByName('codeProspect'),
        nodes: selectedNodes
    };
}