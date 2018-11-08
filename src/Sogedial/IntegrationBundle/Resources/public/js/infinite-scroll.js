//Outils pour bug endwidth IE 11
if (!String.prototype.endsWith) {
    String.prototype.endsWith = function(searchString, position) {
        var subjectString = this.toString();
        if (typeof position !== 'number' || !isFinite(position) || Math.floor(position) !== position || position > subjectString.length) {
            position = subjectString.length;
        }
        position -= searchString.length;
        var lastIndex = subjectString.indexOf(searchString, position);
        return lastIndex !== -1 && lastIndex === position;
    };
}
var endAlreadyCall = false;
var noMoreResult = ($('.item').length < 10 || $('.item').length === 0 || $('.item').length % 10 !== 0) ;
var nextPageNumber = 2;
var isLoading = false;

var path_infinite = window.location.pathname.endsWith("/catalogue");
var societeLabelSplit = window.location.pathname.split("/");
var societeLabel = societeLabelSplit[2];

path_infinite |= window.location.pathname.endsWith("/" + locale + "/bo/client/list/actif");
path_infinite |= window.location.pathname.endsWith("/" + locale + "/bo/client/list/bloque");
path_infinite |= window.location.pathname.endsWith("/" + locale + "/bo/client/list/sans-acces");
path_infinite |= window.location.pathname.endsWith("/" + locale + "/bo/client/list");
path_infinite |= window.location.pathname.endsWith("/" + locale + "/bo/client/search");

path_infinite |= window.location.pathname.endsWith("/" + locale + "/bo/prospects/list/actif");
path_infinite |= window.location.pathname.endsWith("/" + locale + "/bo/prospects/list/bloque");
path_infinite |= window.location.pathname.endsWith("/" + locale + "/bo/prospects/list/sans-acces");
path_infinite |= window.location.pathname.endsWith("/" + locale + "/bo/prospects/list");

path_infinite |= window.location.pathname.endsWith("/" + locale + "/bo/commandes");
path_infinite |= window.location.pathname.endsWith("/" + locale + "/bo/commandes/approved");
path_infinite |= window.location.pathname.endsWith("/" + locale + "/bo/commandes/pending");

path_infinite |= window.location.pathname.endsWith("/" + locale + "/bo/tracking");


var checkIfMoreResultsArePossible = function(){
    var target = $(".scrollable-content");
    var tbody = target.find("tbody")[0];
    var itemsNumbers = countTableRows(tbody.children);

    return ((itemsNumbers < 10)?false:true);
}
var countTableRows = function(tableContent){
    var itemNumbers = 0;
    // Compte le nombre d'items "cibles" dans le HTML qui nous est renvoyé
    // (le plus précis en restant générique pour les produits / clients etc
    // est de compter les tr qui ne sont pas des spacer)
    $.each(tableContent, function(i,el){
        if (el.nodeName === "TR"){
            // Ne pas compter les spacer
            if (!el.classList.contains("spacer")){
                if (el.hasChildNodes()){
                    var crawler = el.childNodes;
                    var hasTh = false; 
                    for (var i = 0; i < crawler.length; i++){
                        if (crawler[i].nodeName === "TH"){
                            hasTh = true;
                            break;       
                        }    
                    };
                    if (!hasTh){
                        itemNumbers++;
                    }
                } else {
                    itemNumbers++;
                }
            }
        }
    });
    return itemNumbers;
}


$(document).ready(function() {

    function clearInput(){
        $( ".select-quantity" ).focus(function() {
            if (this.value === '0'){
                this.value = '';
            }
        });

        $( ".select-quantity" ).focusout(function() {
            if (this.value === ''){
                this.value = '0';
            }
        });
    }

    $('.scrollable-content').scroll(function() {

        var device = detectZoom.device();
        if (device < 1){
            device = 1;
        }

        var loadContent = (Math.floor($(this).scrollTop() + ($(this).innerHeight() * device)) >= $(this)[0].scrollHeight) && !endAlreadyCall;
        if(!window.location.pathname.endsWith("/catalogue")){
            loadContent = loadContent && path_infinite;
        }
        else{
            loadContent = loadContent && path_infinite && !noMoreResult;
        }

        if (loadContent) {
            if (window.lastTitle === undefined || window.lastTitle === null) {
                window.lastTitle = '';
            }

            var splittedPath = window.location.pathname.split('/'); 
            // Split the path to get url and call right route

            if (splittedPath[splittedPath.length - 1] === "tracking"){
                    var url = Routing.generate('sogedial_integration_admin_tracking_clients_load', {
                        'societe': societeLabel,
                        'page': nextPageNumber,
                        '_locale': locale
                    });
            } 
            else if(splittedPath[splittedPath.length - 1] !== "catalogue"){
                status = splittedPath[splittedPath.length - 1];

                // avoid the dash in routing call
                if(status === 'sans-acces'){
                    status = 'SANSLOGIN';
                }

                if(status === 'approved'){
                    status = 'STATUS_APPROVED';
                }

                if(status === 'commandes'){
                    status = 'STATUS_APPROVED';
                }

                if(status === 'pending'){
                    status = 'STATUS_PENDING';
                }

                
                // Call load infinite clients when path is a client page path (including the filters)
                if((splittedPath[splittedPath.length - 1] === "list" && splittedPath[splittedPath.length - 2] === "prospects") || 
                    (splittedPath[splittedPath.length - 2] === "list" && splittedPath[splittedPath.length - 3] === "prospects") ||
                    (splittedPath[splittedPath.length - 2] === "prospects" && window.location.search)) {
                    var url = Routing.generate('sogedial_integration_admin_prospects_load', {
                        'societe': societeLabel,
                        'page': nextPageNumber,
                        'status': status,
                        '_locale': locale
                    });

                    // Call load infinite commande when path is a commande page path (including the filters)
                } else if((splittedPath[splittedPath.length - 1] === "list" && splittedPath[splittedPath.length - 2] === "client") || 
                    (splittedPath[splittedPath.length - 2] === "list" && splittedPath[splittedPath.length - 3] === "client") ||
                    (splittedPath[splittedPath.length - 2] === "client" && window.location.search)) {
                    var url = Routing.generate(url_infinite, {
                        'societe': societeLabel,
                        'page': nextPageNumber,
                        'status': status,
                        '_locale': locale
                    }, {
                        'search': window.location.search
                    });

                    // Call load infinite commande when path is a commande page path (including the filters)
                } else if(splittedPath[splittedPath.length - 1] === "commandes" || splittedPath[splittedPath.length - 2] === "commandes") {
                    var url = Routing.generate('sogedial_integration_admin_commandes_load', {
                        'societe': societeLabel,
                        'status': status,
                        'page': nextPageNumber,
                        '_locale': locale
                    });
                }

            } else {
                
                // Default call catalogue infinite load
                var url = Routing.generate(url_infinite, {
                    'societe': societeLabel,
                    'page': nextPageNumber,
                    '_locale': locale
                }, {
                    'search': window.location.search
                });
            }
            if (!isLoading) {
                if (checkIfMoreResultsArePossible()){
                    $('#loadingDiv').show();
                    $('#endLoadingDiv').hide();
                    isLoading = true;
                    $.ajax({
                        url: url + window.location.search,
                        type: 'GET',
                        dataType: 'html',
                        success: function(code_html, statut) {
                            var tmpHtml = code_html.trim();
                            var items = $.parseHTML(tmpHtml);
                            var itemNumbers = 0;
                            itemNumbers = countTableRows(items);
                            if (itemNumbers > 0){
                                $('#product-list tbody:last-child').append(code_html);
                            }
                            // Si le retour de la requête Ajax est vide ou n'est pas pleine,
                            // c'est qu'il n'y aura pas d'autres résultats.
                            noMoreResult = itemNumbers === 0 || itemNumbers % 10 !== 0;
                            isLoading = false;
                            if(noMoreResult){
                                endAlreadyCall = true;
                                $('#loadingDiv').hide();
                                $('#endLoadingDiv').show();
                                clearInput()
                                dynamicHandlers();                 // pour les lignes qui viennent d'apparaitre
                                dynamicHandlersLink();
                            }
                            else{
                                nextPageNumber = nextPageNumber + 1;
                                $('#loadingDiv').hide();
                                clearInput()
                                dynamicHandlers();                 // pour les lignes qui viennent d'apparaitre
                                dynamicHandlersLink();
                            }
                        },
                        error: function(resultat, statut, erreur) {
                            isLoading = false;
                            $('#loadingDiv').hide();
                            window.location.reload();
                        }
                    });
                }
            }
        }
    });
});