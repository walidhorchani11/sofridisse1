var $product_search_tree = $("#product-search-tree");
var $product_search_form = $("#assortiment-product-search-form");
var $product_search_input = $("#assortiment-product-search-input");
var $submit_button = $('#assortiment-submit-button');
var $products_assortiment_amount = $("#products-assortiment-amount");

// var fetch_by_valeur_assortiment_url = "sogedial_integration_admin_fetch_client_assortiment_tree"
var fetch_url = "sogedial_integration_admin_fetch_client_assortiment_tree";
var submit_url = "sogedial_integration_admin_submit_client_assortiment";
var back_url = "sogedial_integration_admin_client_assortiments"

function getParameterByName() {
    return window.location.href.split("/")[6];
}

fetch_url_params = {
    "codeClient": getParameterByName(),
    "_locale": locale,
    "valeurAssortiment": (!valeur)?null:valeur
};

back_url_params = {
   "id" : code_client,
   "_locale": locale
}

function data_submit(selectedNodes){
    if (typeof valeur === 'undefined') {
        valeur = false;
    }

    return {
        "mode": mode,
        "valeurAssortiment" : valeur,
        codeClient: getParameterByName('codeClient'),
        nodes: selectedNodes,
        assortiment_nom: $("#assortiment_nom").val()
    };
}