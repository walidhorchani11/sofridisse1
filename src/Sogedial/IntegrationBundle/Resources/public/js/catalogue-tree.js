//$(function() {
    // Store common variables
    var checked_nodes = [];
    var $search_tree_all_opened = false;
    var $search_tree_all_checked = false;
    var $search_tree_first_level_opened = false;
    var $search_tree_second_level_opened = false;
    var prospectsList = [];

    function addStylingClasses(){
        $('#product-search-tree').find("li").each(function(){
            if ($(this)[0].hasAttribute('aria-level')){
                var ariaLevel = $(this).attr('aria-level');
                $(this).addClass("js-tree-li-level-" + ariaLevel);

                $(this).children('ul').each(function(){
                    $(this).addClass('js-tree-ul-level-' + ariaLevel);
                })
                // Catch and specify class for anchors
                $(this).children('i').each(function(){
                    $(this).addClass("js-tree-i-level-" + ariaLevel);    
                })

                $(this).children('a').each(function(){
                    $(this).addClass("js-tree-a-level-" + ariaLevel);    
                })
                $("div[data-jstreetable=\""+ $(this)[0].id +"\"]").addClass("js-tree-col-level-" + ariaLevel)
            }
        })
    }
    if ($submit_button.attr('id') === "prospect-submit-button"){
        $.ajax({
            type:"GET",
            url: Routing.generate(
                "sogedial_get_prospects_with_same_enseigne",
                {
                    "codeProspect": getParameterByName('codeProspect'),
                    "_locale": locale
                }
            ),
            success: function(results){
                $.each(results.data, function (i, item) {
                    $('#choose-prospect-assortiment').append($('<option>', { 
                        value: item.code,
                        text : item.nom
                    }));
                });
            }
        })
     } 
    else {
        $.ajax({
            type:"GET",
            url: Routing.generate(
                "sogedial_get_clients_assortiments_with_same_enseigne",
                {
                    "codeClient": getParameterByName(),
                    "_locale": locale
                }
            ),
            success: function(results){
                $.each(results.data, function (i, item) {
                    $('#choose-client-assortiment').append($('<option>', { 
                        value: item.valeurAssortiment,
                        text : item.nomClient+" - "+ item.nomAssortiment
                    }));
                });
            }
        })
    }

    $product_search_tree.bind("after_open.jstree", function () {
        addStylingClasses();
    });

    // Add the right scrollable content classes to the jstree table wrapper
    // Prevent priority on the common scroll system over the fixed header
    // option for the jstree. 
    $product_search_tree.bind("ready.jstree",function(){
        $('.jstree-table-wrapper').addClass("scrollable-content");
        $('.jstree-table-wrapper').addClass("scrollable-content-assortiment");
        if ($submit_button.attr('id') === "prospect-submit-button"){
            $('.jstree-table-wrapper').addClass("scrollable-content-assortiment-prospect");
        } else {
            $('.jstree-table-wrapper').addClass("scrollable-content-assortiment");
        }
        addStylingClasses();
        toggleOpenAllSectors($("#toggle-open-first-level"));
    })

    $product_search_tree.bind("search.jstree", function (nodes, str, res) {
        if (str.nodes.length===0) {
            $('#error-search-message').text("Aucun résultat trouvé");
        } else {
            $('#error-search-message').text("");
        }
    })

    $product_search_tree
        .on("changed.jstree", function(e, data) {
            checked_nodes = data.instance.get_top_checked(
                "full"
            );
            // Count number of selected products..
            var amount_of_products = data.instance.get_bottom_checked().length;
            // Update the DOM node's amount of products.
            $("#products-assortiment-amount").text(amount_of_products);
            addStylingClasses();
        })
        .jstree({
            core: {
                data: {
                    url: Routing.generate(
                        fetch_url,
                        fetch_url_params
                    ),
                },
                expand_selected_onload: false
            },
            // Define possible node types and their architecture.
            "types": {
                "default": {
                  "icon": "fa fa-folder-open",
                  "max_depth": -1,
                  "valid_children": [""],
                },
                "#": {
                    "icon": "fa fa-folder-open",
                    "max_depth": 5,
                    "valid_children": ["secteur"],
                },
                "secteur": {
                  "icon": "fa fa-folder-open",
                  "max_depth": 4,
                  "valid_children": ["rayon"],
                },
                "rayon": {
                  "icon": "fa fa-folder-open",
                  "max_depth": 3,
                  "valid_children": ["famille"],
                },
                "famille": {
                  "icon": "fa fa-folder-open",
                  "max_depth": 2,
                  "valid_children": ["sousFamille"],
                },
                "sousFamille": {
                  "icon": "fa fa-folder-open",
                  "max_depth": 1,
                  "valid_children": ["product"],
                },
                "produit": {
                  "icon": "fa fa-file",
                  "max_depth": 0,
                  "valid_children": ["none"],
                }
            },
            plugins: ["checkbox", "search", "types", "table"],
            table: {
                columns: [
                  {width: '40%', header: "Nombre de produits sélectionnés : <span id='products-assortiment-amount'>0</span>", headerClass:"jstree-table-header-custom",columnClass:"jstree-table-cell-custom"},
                  {width: "20%", header: "Code produit", value: "code", headerClass:"jstree-table-header-custom",columnClass:"jstree-table-cell-custom"},
                  {width: "20%", header: "Marque", value: "trademark", headerClass:"jstree-table-header-custom",columnClass:"jstree-table-cell-custom"},
                  {width: "10%", value: "marketingCode", header: "Code Marketing",headerClass:"jstree-table-header-custom",columnClass:"jstree-table-cell-custom"},
                  {width: "10%", value: "price", header: "Prix",format:function(v){if(!v){return}else{return((v)+"€");}},headerClass:"jstree-table-header-custom",columnClass:"jstree-table-cell-custom"}
                ],
                resizable: false,
                draggable: false,
                contextmenu: false,
                fixedHeader:true,
                headerContextMenu:true,
            }
        });

    // TODO: réactiver cette partie lorsqu'on pourra relier la recherche à la base de données : on recherche n'importe quel produit, même s'il n'a pas encore été lazily loaded, et on doit pouvoir peupler l'arbre avec toutes les noeuds manquants, jusqu'au produit lui-même.

    // If the search input is submitted, parse the tree for results matching the input's value.
    $product_search_form.submit(function(e) {
        e.preventDefault();
        if ($product_search_input.val().length >= 3 ){
            $('#error-search-message').text("");
            $product_search_tree
                .jstree(true)
                .search($product_search_input.val(), false);
        } else {
            $('#error-search-message').text("Veuillez entrer 3 caractères minimum");
        }
    });

    // If the search input is cleared, clear the search results in the tree (and hide nodes that got revealed only because of this search).
    $product_search_input.on("input", function() {
        $('#error-search-message').text("");
        if ($product_search_input.val() === "") {
            $product_search_tree.jstree(true).clear_search();
        }
    });

    // Handle assortiment's submit.
    $submit_button.on("click", function (e) {
        e.preventDefault();
        var selectedNodes = getPluckedNodesWithIdAndType(checked_nodes);
        var dataSubmit = {"data" : JSON.stringify(data_submit(selectedNodes))};
        if (selectedNodes.length === 0) {
            window.alert("Vous ne pouvez pas soumettre un assortiment vide.");
            return;
        }
        var inputLibelleAssortiment = $("#assortiment_nom");
        if (inputLibelleAssortiment && inputLibelleAssortiment.val() === ""){
            window.alert("Le libellé de l'assortiment est requis");
            inputLibelleAssortiment.focus();
            return false;
        }
        // Prevent any further click on the submit button, and add visual hints that the page is loading.
        else {
            $submit_button.addClass("disabled");
            $('#loading-fullscreen').show();
            $.ajax({
                type: "POST",
                url: Routing.generate(
                    submit_url,
                    {
                        "_locale": locale
                    }),
                data: dataSubmit,
                dataType: "json",
                success: function (response) {
                    window.location.href = Routing.generate(
                        back_url,
                        back_url_params
                    );
                },
                error: function (response) {
                    $submit_button.removeClass("disabled");
                      $('#loading-fullscreen').hide();
                    if (response.status === 422) {
                        window.alert(JSON.parse(response.statusText));
                    } else {
                        window.alert("Une erreur est survenue lors de la création de l'assortiment.");
                    }
                }
            });
        }
    });

    $("#choose-prospect-assortiment").change(function(){
        var prospectSelected = this.value;
        var new_fetch_url_params = fetch_url_params;

        if (prospectSelected && prospectSelected !== ""){
            new_fetch_url_params = {
                "codeProspect": prospectSelected,
                "valeurAssortiment": prospectSelected.valeurAssortiment,
                "_locale": locale
            };
        }

        $product_search_tree.jstree(true).settings.core.data.url = Routing.generate(
            fetch_url,
            new_fetch_url_params
        );
        $("#products-assortiment-amount").text('0');
        $product_search_tree.jstree(true).refresh(true,true);

    });

    $("#choose-client-assortiment").change(function(){
        var assortimentSelected = this.value;
        var new_fetch_url_params = null;

        if (assortimentSelected && assortimentSelected !== ""){
            new_fetch_url_params = {
                "valeurAssortiment": assortimentSelected,
                "codeClient": getParameterByName(),
                "_locale": locale
            };
        } else {
            return 0;
        }

        if (new_fetch_url_params){
            $product_search_tree.jstree(true).settings.core.data.url = Routing.generate(
                fetch_url,
                new_fetch_url_params
            );
            $("#products-assortiment-amount").text('0');
            $product_search_tree.jstree(true).refresh(true,true);
        }

    });

    /**
     * Get an id+type subset of the selected nodes to lighten the POST request's payload.
     * Inspiration: https://stackoverflow.com/questions/17781472/how-to-get-a-subset-of-a-javascript-objects-properties
     *
     * @param {Object[]} nodes Array of objects, where each object represents a node with its information.
     * @return {Object[]} Lightened subset of selected nodes, each containing only an id and a type.
     */
    function getPluckedNodesWithIdAndType(nodes) {
        var lightenedNodesArray = [];
        nodes.forEach(function (node) {
            var nodeSubset = ['id', 'type'].reduce(function (obj, key) {
                obj[key] = node[key];
                return obj;
            }, {});
            lightenedNodesArray.push(nodeSubset);
        }, this);
        return lightenedNodesArray;
    }

    function toggleOpenAllNodes(){
        if (!$search_tree_all_opened){
            $product_search_tree.jstree(true).open_all();
            $search_tree_all_opened = true;
            $(this).text('Tout refermer');
        } else {
            $product_search_tree.jstree(true).close_all();
            $(this).text('Tout déployer');
            $search_tree_all_opened = false;
        }
    }

    $("#toggle-open-all").click(function(){
        toggleOpenAllNodes();
    })

    $("#toggle-check-all").click(function(){
        if (!$search_tree_all_checked){
            $product_search_tree.jstree(true).check_all();
            $search_tree_all_checked = true;
            $(this).text('Tout décocher');
        } else {
            $product_search_tree.jstree(true).uncheck_all();
            $(this).text('Tout cocher');
            $search_tree_all_checked = false;
        }
    })

    function toggleOpenAllSectors(target){
        if (!$search_tree_first_level_opened){
            $(".js-tree-li-level-1").each(function(index){
                $product_search_tree.jstree(true).open_node($(this));
            });
            target.text('Refermer secteurs');
            $search_tree_first_level_opened = true;
        } else {
            $(".js-tree-li-level-1").each(function(){   
                $product_search_tree.jstree(true).close_node($(this));
            });
            target.text('Déployer secteurs');
            $search_tree_first_level_opened = false;
        }
    }

    function toggleOpenSecondLevel(){
        if (!$search_tree_second_level_opened){
            $(".js-tree-li-level-2").each(function(){
                $product_search_tree.jstree(true).open_node($(this));
            });
            $search_tree_second_level_opened = true;
            $("#toggle-open-second-level").text('Refermer rayons'); 
        } else {
            $(".js-tree-li-level-2").each(function(){   
                $product_search_tree.jstree(true).close_node($(this));
            });
            $("#toggle-open-second-level").text('Déployer rayons');
            $search_tree_second_level_opened = false;
        }
    }

    $("#toggle-open-first-level").click(function(){
        toggleOpenAllSectors($(this));
    })

    $("#toggle-open-second-level").click(function(){
        if (!$search_tree_first_level_opened){
            toggleOpenAllSectors($("#toggle-open-first-level"))
            setTimeout(function() {
                toggleOpenSecondLevel();
            }, 500);
        } else{
            toggleOpenSecondLevel();
        }
    })
//});