var dynamicHandlers = function(){};
var dynamicHandlersLink = function(){};

$(document).ready(function() {
    // Add format function to Number's prototype
    // Source: https://stackoverflow.com/questions/149055/how-can-i-format-numbers-as-money-in-javascript
    Number.prototype.formatMoney = function(c, d, t) {
        var n = this,
            c = isNaN(c = Math.abs(c)) ? 2 : c,
            d = d == undefined ? "." : d,
            t = t == undefined ? " " : t,
            s = n < 0 ? "-" : "",
            i = String(parseInt(n = Math.abs(Number(n) || 0).toFixed(c))),
            j = (j = i.length) > 3 ? j % 3 : 0;
        return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
    };

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

    var masterEnterprise = false;
    switch(window.masterEnterprise){
        case 'sofrigu': case 'Sofrigu':
            masterEnterprise = 1; break;
        case 'sofridis': case 'Sofridis':
            masterEnterprise = 2; break;
        case 'sofriber': case 'Sofriber':
            masterEnterprise = 3; break;
        default : masterEnterprise = 0;
    }

    // Ajout et configuration de la scrollbar de la sidebar
    var $menuCatalogue = $("#menuCatalogueChild");
    var menuScrollbarSettingsSlimScrollbar = {
      color: '#FFFFFF',
      distance: '5px',
      height: '100%',
      size: '6px',
      railVisible: true,
      wheelStep: 10,
      opacity: 0.6
    };
    focusSearch();          // mettre le focus sur la barre de recherche sans faire le collapse

    if ($menuCatalogue.length) {
        $menuCatalogue.slimScroll(menuScrollbarSettingsSlimScrollbar);
    }

    // Création d'une fonction de debounce
    var waitForFinalEvent = (function() {
        var timers = {};
        return function(callback, ms, uniqueId) {
            if (!uniqueId) {
                uniqueId = "Don't call this twice without a uniqueId";
            }
            if (timers[uniqueId]) {
                clearTimeout(timers[uniqueId]);
            }
            timers[uniqueId] = setTimeout(callback, ms);
        };
    })();

    function getLineItemMinimalOrder(priceArray){
        var qty_min_min = -1;

        // Remarque : contrairement au PHP, l'ordre n'est PAS garanti
        for (var qty_min_string in priceArray) {
            if (priceArray.hasOwnProperty(qty_min_string)) {
                var qty_min = parseInt(qty_min_string);
                if (qty_min_min === -1 || qty_min < qty_min_min)
                {
                    qty_min_min = qty_min;
                }
            }
        }

        return qty_min_min;
    }

    /**
     * Limite de stock d'un produit
     * @param {int} nb (ce qu'on veut ajouter (va de 0 à stockEffectif + 1))
     * @param {int} stockEffectif (la limite max qu'on peut commander)
     * @param {int} minimalOrder (contante dans un contexte )
     */
    function restrictToLimits(nb, stockEffectif, minimalOrder, adjustment, element){
        if( nb === '' || nb === 0 ){
            // element.find('.select-minus').addClass('disabled');
            if (stockEffectif > 0){
                element.find('.select-plus').removeClass('disabled');
            }
            return 0;                                       // 0 est toujours une valeur autorisée
        } else{
            element.find('.select-minus').removeClass('disabled');
        }

        var return_val = nb;
        if( masterEnterprise !== 1 ){
            return_val = Math.min(nb, stockEffectif);       // il n'y a pas de limite max pour Sofrigu
            if (return_val===stockEffectif){
                element.find('.select-plus').addClass('disabled')
            } else{
                element.find('.select-plus').removeClass('disabled')
            }
        }

        // la limit min s'applique à tout le monde
        if (return_val < minimalOrder)
        {
            if (adjustment === -1)                          // cas spécial : bouton "-" => zéro
            {
                return 0;
            }
            return minimalOrder;                            // bouton "+" ou saisie directe => minimalOrder
        }
        return return_val;
    }

    /**
     * In some case, we will not use maximum limit (like in mode precommande)
     */
    function hasLimit(){
        return preCommandeMode === false;
    }

    function change(Parent, adjustment) {
        var Reference = Parent.data("reference");
        var moq = parseInt(Parent.data("moq"));
        var unitPriceArray = Parent.data("priceunit");
        var pcb = Parent.data("pricepcb");
        var Stock = Parent.data("stock");
        var StockLivre = Parent.data("efcommandeencours");

        Stock = (Stock === "") ? 0 : parseInt(Stock);
        StockLivre = (StockLivre === "") ? 0 : parseInt(StockLivre);
        var StockEffectif = Stock - StockLivre;

        var inputElement = Parent.find('input');
        var old_nb = parseInt(inputElement.attr("value"));

        if (adjustment !== 0) {                                 // pas exécuté dans le cas de "change", mais uniquement sur "+" et "-"
            focusSearch();
        }

        var nb = parseInt(inputElement.val());
        if (isNaN(nb)) {                                        // protection contre la saisie de texte dans le cas de "change"
            nb = 0;
        }
        if(adjustment === 1 && moq > 0){
            nb = moq
            Parent.data("moq", 0);
        } else {
            nb = nb + adjustment;                                   // la valeur souhaitée = soit celle dans le champ de saisie, soit la même +/- 1
        }

        if (nb < 0) {                                           // capte le "-" si déjà à zéro, mais aussi les cas de saisie directe des valeurs négatives
            nb = 0;
        }


        if (!pcb) {                                             // des produits avec des pcb invalides...
            pcb = 1;
        }

        var minimalOrder = getLineItemMinimalOrder(unitPriceArray);

        if(hasLimit()){
            nb = restrictToLimits(nb, StockEffectif, minimalOrder, adjustment, Parent);          // restreint le nb à [min .. max] | 0
        }
        // on sauvegarde la valeur pour une utilisation lors de prochain changement
        // + on sette la valeur affichée (utile même dans le cas de "change" : si le stock est dépassé, on remplace par une valeur plus petite)

        // cette ligne doit être avant la "nb === old_nb" pour gérer le cas suivant : déjà à limite, on re-saisit une valeur > la limite => besoin de remettre la valeur limite

        inputElement.attr("value", nb).val(nb);

        if (nb === old_nb) {                                    // si rien n'a changé, pas la peine de rafraichir les données et reinitialiser le timer
            return;
        }

        clearTimeout(window.requestTimer);
        window.requestTimer = setTimeout(timerFire, 2000);
        requestsReference[Reference] = {
            "quantite": nb,
            "pending": true
        };

        setView(Parent, nb, old_nb, unitPriceArray, pcb);
        updateWeightVolumeCurrentOrder();
        toggleMoreStockButton(Parent.find('.more_stock'), nb, StockEffectif);      // les deux sont soit en colis, soit en unités, pas besoin de multiplier
    }

    /**
     * On click button "(+)"
     *
     * @param {*} Parent
     */
    function plus(Parent){
        return change(Parent, 1);

        /*
        var moqNew = Parent.data("moqnew");

        if(moqNew == "" && preCommandeMode !== false){
            var moq = parseInt(Parent.data("moq"));

            if(moq === 0){
                var codeProduit = Parent.data("reference");

                displayModalMOQClient(Parent, codeProduit, codeClient, 1);
            } else {

                return change(Parent, 1);
            }
        } else {
            return change(Parent, 1);
        }
        */
    }

    /**
     * On click button "(-)"
     *
     * @param {*} Parent
     */
    function minus(Parent){
        return change(Parent, -1);

        /*
        var moqNew = Parent.data("moqnew");

        if(moqNew == "" && preCommandeMode !== false){
            var moq = parseInt(Parent.data("moq"));

            if(moq === 0){
                var codeProduit = Parent.data("reference");

                displayModalMOQClient(Parent, codeProduit, codeClient, -1);
            } else {
                return change(Parent, -1);
            }
        } else {
            return change(Parent, -1);
        }
        */
    }

    /**
     * On set content field
     *
     * @param {*} Parent
     */
    function set(Parent){
        return change(Parent, 0);

        /*
        var moqNew = Parent.data("moqnew");

        if(moqNew == "" && preCommandeMode !== false){
            var moq = parseInt(Parent.data("moq"));

            if(moq === 0){
                var codeProduit = Parent.data("reference");

                displayModalMOQClient(Parent, codeProduit, codeClient, 0);
            } else {
                return change(Parent, 0);
            }
        } else {
            return change(Parent, 0);
        }
        */
    }


    /**
     * Click product table
     */
    dynamicHandlersLink = function(){
        $('.table-cell.link').off('click').click(function() {
            var href = $(this).parents('.table-row').find("a.link").attr("href");
            if (href) {
                window.location = href;
            }
        });
        $('td.link').off('click').click(function() {
            var href = $(this).parents('tr').find("a.link").attr("href");
            if (href) {
                window.location = href;
            }
        });
    }

    dynamicHandlers = function(){
        Item = $('.item');
        var nbItem = Item.length;
        for (var i = 0; i < nbItem; i++) {
            Item.eq(i).attr("id", "item" + i);
            var quantite = parseInt(Item.eq(i).find('input').val());
            var Reference = Item.eq(i).data("reference");   // référence complète (préfixée avec le code de société à 3 chiffres)
            if (quantite > 0) {
                requestsReference[Reference] = { "quantite": quantite };
            }

            /* Click on button : + de stock */
            Item.eq(i).find('.more_stock').off('click').click(function() {
                var Parent = $(this).parents('.item');
                var pcb = Parent.data("pricepcb");
                var labelProduit = Parent.data("label");
                var codeProduit = Parent.data("shortref");
                var codePromotion = Parent.data("code");
                var Stock = Parent.data("stock");
                var StockInit = Parent.data("init");
                var StockLivre = Parent.data("efcommandeencours");
                var StockFacture = Parent.data("efcommandefacture");

                StockInit = (StockInit === "") ? 0 : parseInt(StockInit);
                StockLivre = (StockLivre === "") ? 0 : parseInt(StockLivre);
                StockFacture = (StockFacture === "") ? 0 : parseInt(StockFacture);

                if(masterEnterprise === 2){
                    displayModalMoreStockButton(labelProduit, codeProduit, StockInit / pcb, Stock - StockLivre, codePromotion, StockLivre, StockFacture);
                }
            });

            // Click on "+"
            Item.eq(i).find('.select-plus').off('click').click(function() {
                plus($(this).parents('.item'));
            });

            // Click on "-"
            Item.eq(i).find('.select-minus').off('click').click(function() {
                minus($(this).parents('.item'));
                //change($(this).parents('.item'), -1);
            });

            // Input Change
            Item.eq(i).find(".select-quantity").off("change").on("change", function() {
                set($(this).parents('.item'));
            });
        }
    }

    dynamicHandlers();

    function updateWeightVolumeCurrentOrder(){
        poidsVolumeDisplay = $("#poidsVolumeCurrent");
        ambientWeight = $("#ambientWeight");
        ambientVolume = $("#ambientVolume");
        positiveColdWeight = $("#positiveColdWeight");
        positiveColdVolume = $("#positiveColdVolume");
        negativeColdWeight = $("#negativeColdWeight");
        negativeColdVolume = $("#negativeColdVolume");

        if(!(
                poidsVolumeDisplay.length > 0 ||
                ambientWeight.length > 0 ||
                ambientVolume.length > 0 ||
                positiveColdWeight.length > 0 ||
                positiveColdVolume.length > 0 ||
                negativeColdWeight.length > 0 ||
                negativeColdVolume.length > 0
            )
        ){
            return false;
        }

        url = Routing.generate('SogedialSite_getCurrentOrderWeightVolume', {
            '_locale': locale,
        });

        $.get( url, function( data ) {
            if (poidsVolumeDisplay.length > 0)
            {
                poidsVolumeDisplay.html(data.poidsTotal+" KG / "+data.volumeTotal+" m3");
            }

            if (ambientWeight.length > 0)
            {
                ambientWeight.html(data.poidsAmbient);
            }

            if (ambientVolume.length > 0)
            {
                ambientVolume.html(data.volumeAmbient);
            }

            if (positiveColdWeight.length > 0)
            {
                positiveColdWeight.html(data.poidsPositiveCold);
            }

            if (positiveColdVolume.length > 0)
            {
                positiveColdVolume.html(data.volumePositiveCold);
            }

            if (negativeColdWeight.length > 0)
            {
                negativeColdWeight.html(data.poidsNegativeCold);
            }

            if (negativeColdVolume.length > 0)
            {
                negativeColdVolume.html(data.volumeNegativeCold);
            }
        });
    }

    // Remarque : old_nb !== nb pour le bon fonctionnement de compte de produits (subTotalRow)
    function setView(Parent, nb, old_nb, priceUnit, pcb) {

        var total = getLineItemTotal(nb, pcb, priceUnit);
        var old_total = getLineItemTotal(old_nb, pcb, priceUnit);
        var delta_value = total - old_total;
        // mise à jour du total de la ligne
        Parent.find('.catalogue-panier.total_price').html(total.formatMoney(2) + "&#8239;&euro;");         // catalogue et panier
        Parent.find('.fiche-produit.total_price').html(total.formatMoney(2) + "&#8239;&euro; HT");         // fiche produit

        // mise à jour du sous-total (si dans le panier)
        var temperatures = {'SEC' : 'ambient', 'FRAIS' : 'positiveCold', 'SURGELE' : 'negativeCold'};
        var temperature = Parent.data("temperature");
        var subTotalRow = null;
        if (temperatures[temperature])
        {
            subTotalRow = $('#'+temperatures[temperature]);
        }

        // n'existe pas en dehors du panier
        if (subTotalRow) {
            var subTotalCount = subTotalRow.data("count");
            var subTotal = subTotalRow.data("subtotal");

            if (old_nb === 0) {     // notez que old_nb est garanti !== nb
                subTotalCount++;
            } else if (nb === 0) {
                subTotalCount--;
            }

            subTotal = Math.round((subTotal + delta_value)*100)/100;                  // pour éviter "-0.00"

            subTotalRow.data("count", subTotalCount);
            subTotalRow.data("subtotal", subTotal);

            subTotalRow.find(".item-unit").html(subTotalCount + " produit" + (subTotalCount>1 ? "s" : ""));
            subTotalRow.find(".total_price").html(subTotal.formatMoney(2) + "&#8239;&euro; HT*");

            updateDisplaySubTotal(subTotalRow);
            updateWeightVolumeCurrentOrder();
        }

        // les opérations sur le panier (basket bar)
        var found_basket = $('#cat-basket-price span');
        if (found_basket.length > 0)                                // absent dans le dashboard
        {
            // mise a jour du total du panier
            var prev_value = parseFloat(found_basket.html().replace(/ /g, ''));
            var total_basket = Math.round((prev_value + delta_value)*100)/100;                  // pour éviter "-0.00"
            $('#cat-basket-price span').html(total_basket.formatMoney(2));

            // activation / désactivation du bouton de finalisation de commande
            if (total_basket == 0) {
                $("#basket #order").addClass("disabled");
            } else {
                $("#basket #order").removeClass("disabled");
            }

            // mise a jour du nombre de produits dans le panier
            if (temperatures[temperature])
            {
                var basketCounter = $('#basket .select-'+temperatures[temperature]);
                var basketCount = basketCounter.data("count");

                if (old_nb === 0) {     // notez que old_nb est garanti !== nb
                    basketCount++;
                } else if (nb === 0) {
                    basketCount--;
                }

                basketCounter.data("count", basketCount);

                // var basketCount = 0;
                // for (var r in requestsReference) {
                //     if (requestsReference[r].quantite > 0) {            // il peut y avoir des produits dans le panier avec la quantité zéro, on ne les compte pas
                //         basketCount++;
                //     }
                // }

                basketCounter.find(".nb-products").html(basketCount + "&nbsp;produit" + ((basketCount > 1) ? "s" : ""));
            }
        }
        updateWeightVolumeCurrentOrder();
    }

    function timerFire() {
        var pendingReferences = {};
        var empty = true;

        for (var r in requestsReference) {
            if (requestsReference[r].pending) {
                empty = false;
                pendingReferences[r] = requestsReference[r].quantite;
                requestsReference[r].pending = false;
            }
        }

        if(empty === true){
            return false;
        }

        $.post(Routing.generate('SogedialSite_editToCurrentOrder', {
            '_locale': locale,
        }), {
            references: pendingReferences
        }).done();

        for (var r in requestsReference) {
            requestsReference[r].pending = false
            if (requestsReference[r].quantite === 0) {
                delete requestsReference[r]
            }
        }
        updateWeightVolumeCurrentOrder();
    }

    $(window).bind('beforeunload', function() {
        if (typeof Routing !== 'undefined') {
            clearTimeout(window.requestTimer);
            timerFire();
        }
    });

    /**
     * (depend du parametre masterEnterprise dans MultiSiteService)
     * Prix de plusieurs produits
     * Prend en compte les tarifs degressifs
     * @param {int} qty
     * @param {int} pcb
     * @param {Object} priceArray
     */
    // L'équivalent back-office : CatalogueManagerService.php:getLineItemTotal()
    function getLineItemTotal(qty, pcb, priceArray){

        var selected_price = 0;
        var qty_min_max = -1;

        // Remarque : contrairement au PHP, l'ordre n'est PAS garanti
        for (var qty_min_string in priceArray) {
            if (priceArray.hasOwnProperty(qty_min_string)) {
                var qty_min = parseInt(qty_min_string);
                if (qty_min <= qty)
                {
                    // le palier potentiellement applicable
                    if (qty_min > qty_min_max)
                    {
                        // on n'applique que les paliers supérieurs
                        selected_price = priceArray[qty_min];
                        qty_min_max = qty_min;
                    }
                }
            }
        }

        var result = selected_price * qty;        // zéro possible si quantité < quantité minimale requise (le début du premier palier)

        // if(!($this->multisite->hasFeature('vente-par-unite'))) {
        if (masterEnterprise !== 1) {       // Sofrigu
            // ici, "quantité" dans le panier = colis => besoin de multiplier par pcb (avec ou sans tarif degressif)
            result = result * pcb;
        }

        return result;
    }


    // adjustment = 1 pour "+", -1 pour "-" et 0 pour "change"

    function displayModalMoreStockButton(labelProduit, codeProduit, StockInitFicheProduit, StockFicheProduit, codePromotion, StockLivre, StockFacture) {
        $.dialog({
            type: 'confirm',
            buttonText: {
                ok: 'Envoyer',
                cancel: 'Annuler'
            },
            showTitle:true,
            overlayClose:true,
            titleText:'Demande de stock engagement supplémentaire pour le produit<br/>«&nbsp;' + labelProduit + '&nbsp;» (' + codeProduit + ')',
            contentHtml: 
            '<table>' +
            '<tr class="dialog-table-row"><td>Stock engagement initial (colis) :</td><td>' + (StockInitFicheProduit) + '</td></tr>' +
            '<tr class="dialog-table-row"><td>Stock facturé(colis) :</td><td>' + StockFacture + '</td></tr>' +
            '<tr class="dialog-table-row"><td>Stock engagement restant(colis) :</td><td>' + StockFicheProduit + '</td></tr>' +
            '<tr class="dialog-table-row"><td>Demande de stock supplémentaire(colis) :</td><td> <input type="number" class="dialog-input" min="0" name="ef_demande" id="demande_stock_supp" autofocus/></td></tr>' +
            '</table>',
            onClickOk: function() {
                var demande = $("#demande_stock_supp").val();

                $.post(Routing.generate('sogedial_integration_stock_engagement_update', {
                    'id': codePromotion,
                    '_locale': locale
                }), {
                    'stock_engagement_demande': demande
                }).done();
                toastr.success("Votre demande a été prise en compte.");

                return false;
            }
        });
    }

    function displayModalMOQClient(Parent, codeProduit, codeClient, opFlag) {
        return $.dialog({
            type: 'confirm',
            buttonText: {
                ok: 'Envoyer',
                cancel: 'Annuler'
            },
            showTitle:true,
            titleText:"Vous n'avez pas saisi de quantité minimum pour ce produit.",
            contentHtml:
            '<table style="width:350px;margin: auto; text-align:left;">' +
            '<tr><td>Saisie de quantité minimale(colis) :</td><td> <input type="number" style="width:55px;border: 1px solid grey" value="1" min="1" name="ef_demande" id="demande_moq" autofocus/></td></tr>' +
            '</table>',
            onClickCancel: function(){
                if(opFlag == 0){
                    Parent.find(".select-quantity").val(0);
                }
            },
            onClickOk: function() {
                var demande = $("#demande_moq").val();

                $.post(Routing.generate('sogedial_integration_moq_client_update', {
                    '_locale': locale
                }), {
                    'code_produit': codeProduit,
                    'code_client': codeClient,
                    'moq_client_demande': demande
                }).done();

                Parent.data("moqnew", demande);
                Parent.data("moq", demande);

                change(Parent, 1);
                return demande;
            }
        });
    }

    function toggleMoreStockButton(btnMoreStock, nb, StockEffectif){

        // nous autorisons le dépassement uniquement sur Sofrigu, ainsi il est important d'afficher l'avertissement sur
        // la quantité == le stock pour les autres cas
        masterCondition = (nb >= StockEffectif);

        if (masterEnterprise === 1){
            masterCondition = (nb > StockEffectif);     // pas >=
        }
        if(masterCondition){
            btnMoreStock.show();
        } else {
            btnMoreStock.hide();
        }
    }

    if (parseFloat($('#cat-basket-price span').html()) === 0) {
        $("#basket #order").addClass("disabled");
    }
    $("#basket nb-products").val(Object.keys(requestsReference).length);            // bug dans le selector. A enlever cette ligne ?

    // At the very beginning of the page's loading, expend the current submenu and subsubmenu currently selected
    var $level1Category = $('.sub-open');
    var $level2Category = $('.subsub-open');
    var $level3Category = $('.subsub-open-active');
    $level1Category.next().slideDown();
    $level2Category.next().slideDown(400, function () {
    if($level3Category && $level3Category.position()){
        var $level3CategoryTopOffset = $level3Category.position().top;
        $menuCatalogue.slimScroll({
          scrollTo: $level3CategoryTopOffset+"px"
        });
    }
    });

    // Hide/show submenu
    $('.menu-scroll > ul > li > a').click(function () {
        if ($(this).hasClass('sub-open')) {
            // If the clicked submenu item was already open, just close it.
            $(this).removeClass('sub-open');
            $(this).next().slideUp();
        } else {
            // Otherwise, close the previously opened submenu item (if there is one open) and open the one that has just been clicked on.
            var openMenuItem = $('.sub-open');
            if (openMenuItem.length) {
                openMenuItem.removeClass('sub-open');
                openMenuItem.next().slideUp();
            }
            $(this).addClass('sub-open');
            $(this).next().slideDown();
        }
    });

    //  Hide/show subsubmenu
    $('.submenu > li > a').click(function () {
        if ($(this).hasClass('subsub-open')) {
            // If the clicked subsubmenu item was already open, just close it.
            $(this).removeClass('subsub-open');
            $(this).next().slideUp();
        } else {
            // Otherwise, close the previously opened subsubmenu item (if there is one open) and open the one that has just been clicked on.
            var openMenuItem = $('.subsub-open');
            if (openMenuItem.length) {
                openMenuItem.removeClass('subsub-open');
                openMenuItem.next().slideUp();
            }
            $(this).addClass('subsub-open');
            $(this).next().slideDown();
        }
    });

    // Visually change the active subsubsub-category.
    // This is useful when only the content refreshes through AJAX calls, and the sidebar doesn't reload.
    $('.subsubmenu > li > a').click(function () {
        if (!$(this).hasClass('subsub-open-active')) {
            $('subsub-open-active').removeClass('subsub-open-active');
            $(this).addClass('subsub-open-active');
        }
    });


    dynamicHandlersLink();

    function updateDisplaySubTotal(jqObj){
        if (jqObj.data("count") !== 0)
        {
            jqObj.removeClass("hidden");
        }
        else
        {
            jqObj.addClass("hidden");
        }
    }

    function updateDisplayOrderSubtotals(){
        updateDisplaySubTotal($('#ambient'));
        updateDisplaySubTotal($('#positiveCold'));
        updateDisplaySubTotal($('#negativeCold'));
    }

    updateDisplayOrderSubtotals();


    // Close header warning (on basket page for instance) when the cross sign is clicked
    $('#warning-header-close').click(function () {
        $("#warning-header").slideUp();
    });

    var menuIsOpen;

    // Init sidebar (adapt sidebar to device)
    function initSidebar(){
        menuIsOpen = (($(window).width() > 991) ? false : true);
        var width = $(window).width();
        var logo = $('#logo');
        var sideBar = $('#menuCatalogue');
        if (!sideBar.length){
            sideBar = $('.main-menu')
        }
        var marginLeft = (width < 991)?-274:0;
        logo.css({'margin-left':marginLeft});
        sideBar.css({'margin-left':marginLeft});
    }
    
    initSidebar();

    function toggleSideBar(){
        var $sidebar = $('#menuCatalogue');
        var $logo = $('#logo');
        var marginLeft = 0;
        if (!$sidebar.length) {
            $sidebar = $('.main-menu');
        }
        if (!menuIsOpen) {
            marginLeft = -274;
        }
            $logo.animate({"margin-left":marginLeft},500);
            $sidebar.animate({ "margin-left": marginLeft}, 500);

        menuIsOpen = !menuIsOpen;
    }

    $('#menu').click(function() {
        // Get sidebar. Try catalogue-style sidebar first, then dashboard-style if not found.
        toggleSideBar()
    });

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


    // Adaptive product type width Dashboard
    var nbBlock = $('#next-delivery').children('.next-delivery').length;
    if (nbBlock > 0) {
        if (nbBlock === 1) {
            $('.next-delivery').width("99%");
        } else if (nbBlock === 2) {
            $('.next-delivery').width("49%");
        }
    }
    $(window).resize(function() {
        waitForFinalEvent(function() {
            initSidebar();
        }, 500, "onResizeEventStringId");
    });
    $('#loadingDiv').hide();
    $('#endLoadingDiv').hide();

    $('#infobar-table-logout').click(function () {
        var $infobarLogoutDropdown = $('#infobar-logout-dropdown');
        $infobarLogoutDropdown.fadeToggle(400, 'swing', function () {
            $infobarLogoutDropdown.toggleClass('hidden');
        });
    });

    $('#infobar-table-assortiments').click(function () {
        var $infobarLogoutDropdown = $('#infobar-assortiments-dropdown');
        $infobarLogoutDropdown.fadeToggle(400, 'swing', function () {
            $infobarLogoutDropdown.toggleClass('hidden');
        });
    });

    updateWeightVolumeCurrentOrder();
});