var dynamicHandlers = function(){};
var dynamicHandlersLink = function(){};


$(document).ready(function () {


  // Ajout et configuration de la scrollbar de la sidebar
  var $menu = $(".main-menu");
  var menuScrollbarSettings = {
    autoHideScrollbar: true,
    mouseWheel:{ scrollAmount: 200 },
    scrollbarPosition: "inside",
    scrollButtons: { enable: true },
    scrollInertia: 300,
    theme: "light-3"
  }
  /*
  Thomas: mise en commentaire de ce code car fait planter le javascript
  if ($menu.length) {
      $menu.mCustomScrollbar(menuScrollbarSettings);
  }
  */

  // Création d'une fonction de debounce
  var waitForFinalEvent = (function () {
    var timers = {};
    return function (callback, ms, uniqueId) {
      if (!uniqueId) {
        uniqueId = "Don't call this twice without a uniqueId";
      }
      if (timers[uniqueId]) {
        clearTimeout (timers[uniqueId]);
      }
      timers[uniqueId] = setTimeout(callback, ms);
    };
  })();


    function setTimeoutCatalogueFicheProduitPanier(){
        // Catalogue
        setTimeout(function () {
            $('#cat-basket-price').load(document.URL + ' #cat-basket-price');
            $('#cat-basket-nb-product').load(document.URL + ' #cat-basket-nb-product');
        } ,2000);

        // Fiche Produit
        setTimeout(function () {
            $('#product-basket-nb-products').load(document.URL + ' #product-basket-nb-products');
            $('#product-basket-price').load(document.URL + ' #product-basket-price');
        } ,2000);

        // Panier
        setTimeout(function () {
            $('#panier-basket-price').load(document.URL + ' #panier-basket-price');
            $('#panier-basket-item-unit').load(document.URL + ' #panier-basket-item-unit');
            $('#panier-basket-price_cat').load(document.URL + ' #panier-basket-price_cat');
        } ,2000);
    }

    function timerFire() {
        var pendingReferences = {};
        for (var r in requestsReference) {
            if (requestsReference[r].pending) {
                pendingReferences[r] = requestsReference[r].quantite;
                requestsReference[r].pending = false;
            }
        }

        $.post(Routing.generate(routeMOQCommand, {
            '_locale': locale
        }), {
            references: pendingReferences
        }).done();

        for (var r in requestsReference) {
            requestsReference[r].pending = false
            if (requestsReference[r].quantite === 0) {
                delete requestsReference[r]
            }
        }
    }

    function setView(lignecommande, nb){
        setTimeoutCatalogueFicheProduitPanier();

        clearTimeout(window.requestTimer);
        window.requestTimer = setTimeout(timerFire, 2000);

        requestsReference[lignecommande] = {
            "quantite": nb,
            "pending": true
        };
    }

    function round(x){
        return Math.round(x * 100)/100;
    }

    var ruleItems = $('.unique-rule-container');

    var productsQuantity = [];
    var productsPcb = [];
    var productsPrice = [];
    var productsWeight = [];
    var productsPallet = [];
    var tablesQuantity = [];
    var othersQuantities = [];
    var nbRules = ruleItems.length;
    var routeMOQValid = "sogedial_integration_admin_moq_valid";
    var locale = $('html').attr('lang');
    var routeMOQCommand = "sogedial_integration_admin_moq_commande";

    for (var k = 0; k < nbRules; k++) {
        var Item = ruleItems.eq(k).find('.moq-card-product-container');
        var ItemInfo = Item.find('.moq-card-product-info-container');
        var nbItem = Item.length;
        productsQuantity.push([]);
        for (var i = 0; i < nbItem; i++) {
            quantityInput = Item.eq(i).find('.quantityForm');

            productsQuantity[k].push([]);
            var nbQuantityInput = quantityInput.length;

            productsPcb[i] = parseInt(ItemInfo.eq(i).data('pcb'));
            productsPrice[i] = parseFloat(ItemInfo.eq(i).data('price'));
            productsWeight[i] = parseFloat(ItemInfo.eq(i).data('weight'));
            productsPallet[i] = parseFloat(ItemInfo.eq(i).data('pallet'));
            if(ItemInfo.eq(i).data('othersquantities')){
                othersQuantities.push(parseInt(ItemInfo.eq(i).data('othersquantities')));
            } else {
                othersQuantities.push(0);
            }
            for(var j = 0; j < nbQuantityInput; j++){
                productsQuantity[k][i][parseInt(quantityInput.eq(j).data('trid'))] = parseInt(quantityInput.eq(j).find(".quantity").val());

                quantityInput.eq(j).find('.plus').click(function () {
                    var nb = parseInt($(this).prev('.quantity').attr("value"));
                    nb += 1;

                    totalProduct = $(this).parents(".moq-card-product-container").find(".totalProductPackage");
                    totalLine = parseInt(totalProduct.html());
                    totalProduct.html(totalLine + 1);

                    globalTotal = $(this).parents(".moq-card-mix-container").find(".global_total_package");
                    totalRule = parseInt(globalTotal.html());
                    globalTotal.html(totalRule + 1);

                    pcb = parseInt($(this).parents(".moq-card-product-container").find('.moq-card-product-info-container').data("pcb"));
                    price = parseFloat($(this).parents(".moq-card-product-container").find('.moq-card-product-info-container').data("price"));
                    weight = parseFloat($(this).parents(".moq-card-product-container").find('.moq-card-product-info-container').data("weight"));
                    pallet = parseFloat($(this).parents(".moq-card-product-container").find('.moq-card-product-info-container').data("pallet"));

                    totalProductUC = $(this).parents(".moq-card-product-container").find(".totalProductUC");
                    totalProductUC.html(parseInt(totalProductUC.html()) + pcb);

                    globalPriceTotal = $(this).parents(".moq-card-mix-container").find(".global_total_price");
                    totalPriceRule = parseFloat(globalPriceTotal.html());
                    globalPriceTotal.html(round(totalPriceRule + pcb * price));

                    totalProductPrice = $(this).parents(".moq-card-product-container").find(".totalProductPrice");
                    totalProductPrice.html(round(parseFloat(totalProductPrice.html()) + (parseFloat(pcb) * price)));

                    totalProductWeight = $(this).parents(".moq-card-product-container").find(".totalProductWeight");
                    totalProductWeight.html(round(parseFloat(totalProductWeight.html()) + (weight)));

                    globalWeightTotal = $(this).parents(".moq-card-mix-container").find(".global_total_weight");
                    totalWeightRule = parseFloat(globalWeightTotal.html());
                    globalWeightTotal.html(round(totalWeightRule + weight));

                    if(pallet > 0){
                        totalProductPallet = $(this).parents(".moq-card-product-container").find(".totalProductPallet");
                        totalProductPallet.html(round(parseInt(totalProduct.html())/pallet));

                        globalPalletTotal = $(this).parents(".moq-card-mix-container").find(".global_total_pallet");
                        totalPalletRule = parseFloat(globalPalletTotal.html());
                        globalPalletTotal.html(round(parseInt(globalTotal.html())/pallet));
                    }

                    globalUCTotal = $(this).parents(".moq-card-mix-container").find(".global_total_uc");
                    totalUCRule = parseInt(globalUCTotal.html());
                    globalUCTotal.html(totalUCRule + pcb);

                    parentDiv = $(this).parents(".quantityForm");

                    table = parentDiv.data("tableid");
                    line = parentDiv.data("trid");
                    column = parentDiv.data("tdid");
                    productsQuantity[table][line][column] = parseInt(nb);

                    setTimeoutCatalogueFicheProduitPanier();
                    $(this).prev('.quantity').attr("value", nb).val(nb);
                    var lignecommande = parentDiv.data('code');
                    setView(lignecommande, nb);

                    // Trigger total rule amount change, for instance to calculate the new ratio based on this value.
                    var $currentRuleContainer = $(this).parents(".unique-rule-container");
                    var currentRuleId = $currentRuleContainer.data('uniqueruleid');
                    $currentRuleContainer.trigger('ruleAmountChanged', currentRuleId);
                });

                quantityInput.eq(j).find('.minus').click(function(){
                    var nb = parseInt($(this).next('.quantity').attr("value"));
                    if (nb > 0) {
                        nb -= 1;

                        parentDiv = $(this).parents("div.quantityForm");

                        totalProduct = $(this).parents(".moq-card-product-container").find(".totalProductPackage");
                        totalLine = parseInt(totalProduct.html());
                        totalProduct.html(totalLine - 1);

                        pcb = parseInt($(this).parents(".moq-card-product-container").find('.moq-card-product-info-container').data("pcb"));
                        price = parseFloat($(this).parents(".moq-card-product-container").find('.moq-card-product-info-container').data("price"));
                        weight = parseFloat($(this).parents(".moq-card-product-container").find('.moq-card-product-info-container').data("weight"));
                        pallet = parseFloat($(this).parents(".moq-card-product-container").find('.moq-card-product-info-container').data("pallet"));

                        totalProductUC = $(this).parents(".moq-card-product-container").find(".totalProductUC");
                        totalProductUC.html(parseInt(totalProductUC.html()) - pcb);

                        totalProductPrice = $(this).parents(".moq-card-product-container").find(".totalProductPrice");
                        totalProductPrice.html(round(parseFloat(totalProductPrice.html()) - (pcb * price)));

                        globalTotal = $(this).parents(".moq-card-mix-container").find(".global_total_package");
                        totalRule = parseInt(globalTotal.html());
                        globalTotal.html(totalRule - 1);

                        globalUCTotal = $(this).parents(".moq-card-mix-container").find(".global_total_uc");
                        totalUCRule = parseFloat(globalUCTotal.html());
                        globalUCTotal.html(totalUCRule - pcb);

                        globalPriceTotal = $(this).parents(".moq-card-mix-container").find(".global_total_price");
                        totalPriceRule = parseFloat(globalPriceTotal.html());
                        globalPriceTotal.html(round(totalPriceRule - (pcb * price)));

                        totalProductWeight = $(this).parents(".moq-card-product-container").find(".totalProductWeight");
                        totalProductWeight.html(round(parseFloat(totalProductWeight.html()) - (weight)));

                        globalWeightTotal = $(this).parents(".moq-card-mix-container").find(".global_total_weight");
                        totalWeightRule = parseFloat(globalWeightTotal.html());
                        globalWeightTotal.html(round(totalWeightRule - weight));

                        if(pallet > 0){
                            totalProductPallet = $(this).parents(".moq-card-product-container").find(".totalProductPallet");
                            totalProductPallet.html(round(parseInt(totalProduct.html())/pallet));
                            globalPalletTotal = $(this).parents(".moq-card-mix-container").find(".global_total_pallet");
                            totalPalletRule = parseFloat(globalPalletTotal.html());
                            globalPalletTotal.html(round(parseInt(globalTotal.html())/pallet));
                        }

                        table = parentDiv.data("tableid");
                        line = parentDiv.data("trid");
                        column = parentDiv.data("tdid");
                        productsQuantity[table][line][column] = parseInt(nb);

                        $(this).next('.quantity').attr("value", nb).val(nb);
                        var lignecommande = $(this).parents(".quantityForm").data('code');
                        setTimeoutCatalogueFicheProduitPanier();
                        $(this).attr("value", nb);
                        setView(lignecommande, nb);

                        // Trigger total rule amount change, for instance to calculate the new ratio based on this value.
                        var $currentRuleContainer = $(this).parents(".unique-rule-container");
                        var currentRuleId = $currentRuleContainer.data('uniqueruleid');
                        $currentRuleContainer.trigger('ruleAmountChanged', currentRuleId);
                    }
                });

                quantityInput.eq(j).find(".quantity").on("change", function(){
                    var Parent = $(this).parents('.moq-card-product-info-container');
                    var nb = parseInt($(this).val());
                    if(nb == NaN){
                        nb = 0;
                    }

                    parentDiv = $(this).parents(".quantityForm");

                    table = parentDiv.data("tableid");
                    line = parentDiv.data("trid");
                    column = parentDiv.data("tdid");

                    productsQuantity[table][line][column] = nb;
                    //var sumLine = othersQuantities[line + table] + productsQuantity[table][line].reduce(function(pv, cv) { return pv + cv; }, 0);
                    var sumLine = productsQuantity[table][line].reduce(function(pv, cv) { return pv + cv; }, 0);

                    var sumRule = 0;
                    var sumRuleUC = 0;
                    var sumRulePrice = 0;
                    var sumRuleWeight = 0;
                    var sumRulePallet = 0;
                    var tableLength = productsQuantity[table].length;
                    for(var s = 0; s < tableLength; s++){
                        //sum = othersQuantities[s] + productsQuantity[table][s].reduce(function(pv, cv) { return pv + cv; }, 0);
                        sum = productsQuantity[table][s].reduce(function(pv, cv) { return pv + cv; }, 0);
                        sumRule += sum;
                        sumRuleUC += productsPcb[s] * sum;
                        sumRulePrice += productsPrice[s] * productsPcb[s] * sum;
                        sumRuleWeight += productsWeight[s] * sum;
                        if(productsPallet[s] > 0){
                            sumRulePallet += sum / productsPallet[s];
                        }
                    }

                    pcb = parseInt($(this).parents(".moq-card-product-container").find('.moq-card-product-info-container').data("pcb"));
                    price = parseFloat($(this).parents(".moq-card-product-container").find('.moq-card-product-info-container').data("price"));
                    weight = parseFloat($(this).parents(".moq-card-product-container").find('.moq-card-product-info-container').data("weight"));
                    pallet = parseFloat($(this).parents(".moq-card-product-container").find('.moq-card-product-info-container').data("pallet"));

                    othersQuantitiesHtml = parseInt($(this).parents(".moq-card-product-container").find(".others_quantities").html());

                    totalProductUC = $(this).parents(".moq-card-product-container").find(".totalProductUC").html(sumLine * pcb);
                    totalProductPrice = $(this).parents(".moq-card-product-container").find(".totalProductPrice").html(round(sumLine * pcb * price));
                    totalProductWeight = $(this).parents(".moq-card-product-container").find(".totalProductWeight").html(round(sumLine * weight));
                    if(pallet > 0){
                        totalProductPallet = $(this).parents(".moq-card-product-container").find(".totalProductPallet").html(round(sumLine / pallet));
                    }
                    totalProduct = $(this).parents(".moq-card-product-container").find(".totalProductPackage").html(sumLine);

                    globalTotal = $(this).parents(".moq-card-mix-container").find(".global_total_package").html(sumRule);
                    globalUCTotal = $(this).parents(".moq-card-mix-container").find(".global_total_uc").html(sumRuleUC);
                    globalPriceTotal = $(this).parents(".moq-card-mix-container").find(".global_total_price").html(round(sumRulePrice));
                    globalWeightTotal = $(this).parents(".moq-card-mix-container").find(".global_total_weight").html(round(sumRuleWeight));
                    globalPalletTotal = $(this).parents(".moq-card-mix-container").find(".global_total_pallet").html(round(sumRulePallet));
                    var lignecommande = $(this).parents(".quantityForm").data('code');
                    setTimeoutCatalogueFicheProduitPanier();
                    $(this).attr("value", nb);
                    setView(lignecommande, nb);

                    // Trigger total rule amount change, for instance to calculate the new ratio based on this value.
                    var $currentRuleContainer = $(this).parents(".unique-rule-container");
                    var currentRuleId = $currentRuleContainer.data('uniqueruleid');
                    $currentRuleContainer.trigger('ruleAmountChanged', currentRuleId);
                });
            }
        }

        // Since a var definition is used in a for loop, it will be hoisted, and using k in a separate function will fetch the latest value reached by k in the loop instead of the current value during runtime.
        // Therefore, to use it in an exterior function or a listener, it must be wrapped in an anonymous function capturing its value at runtime.
        (function (k) {
            // Set up a listener for each rule's current amount, so as to be able to update some information as soon as it changes (like the rule's ratio).
            // We must select the right type of amount : global amount if it is a mix rule, local product's amount for other types (whilst selecting the amount corresponding to the rule's unit).
            var $currentRuleContainer = ruleItems.eq(k);
            $currentRuleContainer.on('ruleAmountChanged', function (e, ruleTableId) {
                // We have to do this stuff since global variables are a PITA to use otherwise...
                $realCurrentRuleContainer = $('[data-uniqueruleid="' + ruleTableId + '"]');

                // Get the expected rule amount (the rule's validation shall be refused or warned upon if this amount has not been reached yet).
                var expectedRuleAmount = $realCurrentRuleContainer.data('rulenb');

                // Get the current rule amount.
                var currentRuleAmount = $realCurrentRuleContainer.find('[class^="global_total"]').html();
                if (currentRuleAmount === undefined) {
                    currentRuleAmount = $realCurrentRuleContainer.find('[class^="totalProduct"]').last().html();
                }

                // Get the reference to the ratio DOM node.
                var uniqueRuleRatio = $realCurrentRuleContainer.find('.moq-card-rule-ratio');
                setRatio(uniqueRuleRatio, currentRuleAmount, expectedRuleAmount);
            });

            $('#rule_' + k).click(function () {
                ruleCommandeLineDiv = $(this).parents('.unique-rule-container');
                ruleUnit = ruleCommandeLineDiv.data('ruleunit');
                ruleNb = ruleCommandeLineDiv.data('rulenb');
                globalTotal = getTotalByUnit(ruleUnit);
                quantityForms = ruleCommandeLineDiv.find('.quantityForm');

                if(ruleUnit === 'p' && globalTotal == 0) {
                    displayModalRuleFailPallet(quantityForms, k);
                } else if (ruleNb > globalTotal) {
                    displayModalRuleFail(quantityForms, k);
                } else {
                    validRule(quantityForms, k);
                }
            });
        })(k);
    }

    function setRatio(ratioObject, currentRuleAmount, expectedRuleAmount) {
        var ratio = Math.floor((currentRuleAmount / expectedRuleAmount) * 100);
        ratioObject.html(ratio);
        ratioContainer = ratioObject.parent();
        setRatioStatus(ratioContainer, ratio);
    }

    function setRatioStatus(ratioContainer, ratio) {
        var ratioStatus;
        if (ratio < 80) {
            ratioStatus = "insufficient";
        } else if (ratio >= 80 && ratio < 100) {
            ratioStatus = "needs-validation";
        } else {
            ratioStatus = "valid";
        }
        ratioContainer.attr("data-ratiostatus", ratioStatus);
    }

    function getTotalLineOrTotal(globalId, lineId){
        var globalContent = ruleCommandeLineDiv.find('.global_total_' + globalId).html();

        if(globalContent === undefined){
            var lineContent = ruleCommandeLineDiv.find('.totalProduct' + lineId).html();
            if(lineContent === undefined){
                return undefined;
            }

            globalContent = parseFloat(lineContent);
        } else {
            globalContent = parseFloat(globalContent);
        }

        var ruleOthersQuantities = parseInt(ruleCommandeLineDiv.find('.moq-card-product-info-container').data('othersquantities'));

        if(globalId === 'uc'){
            var pcb = parseInt(ruleCommandeLineDiv.find('.moq-card-product-info-container').data('pcb'));
            globalContent += ruleOthersQuantities * pcb;
        } else if(globalId === 'package') {
            globalContent += ruleOthersQuantities; 
        } else {
            var price = parseFloat(ruleCommandeLineDiv.find('.moq-card-product-info-container').data(globalId));
            globalContent += ruleOthersQuantities * price;
        }

        return globalContent;
    }

    function getTotalByUnit(ruleUnit){
        switch(ruleUnit){
            //unite
            case 'u':
                return getTotalLineOrTotal('uc', 'UC');
            //colis
            case 'c':
                return getTotalLineOrTotal('package', 'Package');
            //euros
            case 'e':
                return getTotalLineOrTotal('price', 'Price');
            //kg
            case 'k':
                return getTotalLineOrTotal('weight', 'Weight');
            //palette
            case 'p':
                return getTotalLineOrTotal('pallet', 'Pallet');
            default:
                return undefined;
        }
    }

    function validRule(quantityForms, ruleId){
        requestsReference = {};
        quantityFormsLen = quantityForms.length;
        var nbReferences = 0;

        for (var i = 0; i < quantityFormsLen; i++){
            if($(quantityForms[i]).data('code') !== undefined){
                nbReferences++;
                requestsReference[$(quantityForms[i]).data('code')] = {
                    "quantite": $(quantityForms[i]).find('.quantity').val(),
                    "pending": true
                };
            }
        }

        timerFireValidMOQ();
        visualValidation(ruleId, nbReferences);
    }

    // This is used to visually notify the admin when he validates a MOQ rule.
    function visualValidation(ruleId, nbReferences) {
        setCounter(nbReferences);
        disableValidationButton(ruleId);
        disableInputs(ruleId);
        validateStatus(ruleId);
    }

    function setCounter(nbReferences){
        var customerStatusCounters = $(".customer-status span");

        setterCounter = function(customElements, append){
            contentHTML = customElements.innerHTML;
            contentHTML = parseInt(contentHTML.substr(1, contentHTML.length -2)) + append;
            customElements.innerHTML = "(" + contentHTML + ")";
        }
        setterCounter(customerStatusCounters[0], nbReferences);
        setterCounter(customerStatusCounters[1], -1 * nbReferences);
    }

    /**
     * Fetch the MOQ rule's container based on its HTML ID.
     * @param {*} ruleId HTML ID of the rule.
     */
    function getMoqRuleContainer(ruleId) {
        return $('#rule_' + ruleId).parents('.unique-rule-container');
    }

    function disableValidationButton(ruleId) {
        $('#rule_' + ruleId).addClass('disabled');
    }

    function disableInputs(ruleId) {
        var $container = getMoqRuleContainer(ruleId);
        // Search for the validation status elements.
        var $inputs = $container.find('.quantityForm');

        $inputs.each(function () {
            // This only disables clics, and does not prevent access to the fields via the keyboard.
            $(this).addClass('disabled');
            // That does it though.
            $(this).children('input').first().attr('disabled', true);
        })
    }

    function validateStatus(ruleId) {
        var $container = getMoqRuleContainer(ruleId);
        // Search for the validation status elements.
        var $validationStatus = $container.find('[data-validated="0"]');

        $validationStatus.each(function () {
            // Switch from unvalidated to validated status.
            $(this).attr('data-validated', 1);
            // Switch displayed status text to validated.
            $(this).find('.moq-card-validation-status').html('<i class="fa fa-check-circle" aria-hidden="true"></i>&nbsp;&nbsp;validé');
        });
    }

    function timerFireValidMOQ(){
        var pendingReferences = {};
        for (var r in requestsReference) {
            if (requestsReference[r].pending) {
                pendingReferences[r] = requestsReference[r].quantite;
                requestsReference[r].pending = false;
            }
        }

        $.post(Routing.generate(routeMOQValid, {
            '_locale': locale
        }), {
            references: pendingReferences
        }).done();

        for (var r in requestsReference) {
            requestsReference[r].pending = false
            if (requestsReference[r].quantite === 0) {
                delete requestsReference[r]
            }
        }
    }

    function displayModalRule(quantityForms, ruleId, content){
        $.dialog({
            type: 'confirm',
            buttonText: {
                ok: 'Valider',
                cancel: 'Annuler'
            },
            showTitle:false,
            contentHtml: content,
            onClickOk: function () {
                validRule(quantityForms, ruleId);
            }
        });
    }

    function displayModalRuleFailPallet(quantityForms, ruleId){
        content = 'Information pallete indisponible.<br/>'+
            'Souhaitez-vous quand même valider les lignes de commande ?';
        return displayModalRule(quantityForms, ruleId, content);
    }

    function displayModalRuleFail(quantityForms, ruleId)
    {
        var $ruleContainer = $('[data-uniqueruleid="' + ruleId + '"]');
        var ratio = $ruleContainer.find('.moq-card-rule-ratio').html();
        content = 'Vous n\'avez pas atteint la quantité minimum (ratio : ' + ratio + ' %).<br/>'+
            'Souhaitez-vous quand même valider les lignes de commande ?';
        return displayModalRule(quantityForms, ruleId, content);
    }

    //function updateSumProducts() {
    //    var sumProducts = 0;
    //    Item = $('div.item');
    //    var nbItem = $('div.item').length;
    //    for (var i = 0; i < nbItem; i++) {
    //        sumProducts += Number(Item.eq(i).find('.quantity').attr("value"));
    //    }
    //    $(".nb-products").html(sumProducts + " produits").attr("data-nbproducts", sumProducts);
    //}

    // Hide/show submenu
    $('.menu-scroll > ul > li > a').click(function () {
        if ($(this).hasClass('sub-open')) {
            $(this).next('.submenu').slideUp();
            $(this).removeClass('sub-open');
        //return false;
        }
        else {
            $(this).next('.submenu').slideDown();
            $(this).addClass('sub-open');
            //return false;
        }
    });

    // Adjust height menu
    var contentHeight = $('.content').height();
    var sectionHeight = $('section').height();
    var asideHeight = $('aside').height();
    var bodyHeight = $('body').height();

    /*if (sectionHeight < asideHeight) {
        $('section').css('height', asideHeight);
    }

    if (contentHeight < asideHeight) {
        $('.content').css('height', 'auto');
    }

    if (bodyHeight > contentHeight) {
        $('.content').css('height', bodyHeight);
    }

    if (bodyHeight > sectionHeight) {
        $('section').css('height', bodyHeight);
    }*/

    //  Third Level Submenu
    $('.submenu > li > a').click(function () {
        $(".subsubmenu li > a").removeClass('subsub-open-active');
        if ($(this).hasClass('subsub-open')) {
            $(this).next('.subsubmenu').slideUp();
            $(this).removeClass('subsub-open');
            return false;
        }
        else {
            $('html,body').animate({scrollTop: $(this).offset().top}, 'slow');
            $(this).next('.subsubmenu').slideDown();
            $(this).addClass('subsub-open');
            return false;
        }
    });

    $(".subsubmenu li > a").click(function () {
        $(".subsubmenu li > a").removeClass('subsub-open-active');
        if ($(this).hasClass('subsub-open-active')) {
            $(this).removeClass('subsub-open-active');
            return false;
        }
        else {
            $(this).addClass('subsub-open-active');
        }
    });

    $(".submenu li > a").click(function () {
        $(".submenu li > a").removeClass('subsub-open');
        if ($(this).hasClass('subsub-open')) {
            $(this).removeClass('subsub-open');
        }
        else {
            $(this).addClass('subsub-open');
        }
    });

    // Click product table
    $('.table-cell.link').click(function () {
        var href = $(this).parents('.table-row').find("a.link").attr("href");
        if (href) {
            window.location = href;
        }
    });
    $('td.link').click(function () {
        var href = $(this).parents('tr').find("a.link").attr("href");
        if (href) {
            window.location = href;
        }
    });

    // Click product table
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

     // Init sidebar (adapt sidebar to device)
    function initSidebar(){
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

    // Responsive Menu for tablet (portrait)
    var menuIsOpen = (($(window).width() > 991) ? false : true);
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

    // Adaptive product type width Dashboard
    var nbBlock = $('#next-delivery').children('.next-delivery').length;

    if (nbBlock > 0) {
        if (nbBlock === 1) {
            $('.next-delivery').width("99%");
        }
        else if (nbBlock === 2) {
            $('.next-delivery').width("49%");
        }
    }

    $(window).resize(function () {
        waitForFinalEvent(function(){
            initSidebar();
        }, 500, "onResizeEventStringId");
    });
    $('#loadingDiv').hide();
    $('#endLoadingDiv').hide();
});