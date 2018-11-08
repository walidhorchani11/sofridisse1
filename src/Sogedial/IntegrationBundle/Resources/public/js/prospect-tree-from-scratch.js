$(function() {
    // Store common variables
    var $prospect_product_search_tree = $("#prospect-product-search-tree");
    var $prospect_product_search_form = $("#prospect-product-search-form");
    var $prospect_product_search_input = $("#prospect-product-search-input");
    var $prospect_submit_button = $('#prospect-submit-button');
    var checked_nodes = [];

    $prospect_product_search_tree
        .on("changed.jstree", function(e, data) {
            checked_nodes = data.instance.get_bottom_checked(
                "full"
            );
            var $amount_of_parents = 0;
            for (var $i = 0, $length = checked_nodes.length; $i < $length; $i++) {
                if (
                    $prospect_product_search_tree
                        .jstree(true)
                        .is_parent(checked_nodes[$i])
                ) {
                    $amount_of_parents++;
                }
            }
        })
        .jstree({
            core: {
                data: {
                    url: function (node) {
                        // Define which route will be called (and with which parameters) for the root node's loading and for further lazy loads.
                        return Routing.generate(
                            "sogedial_integration_admin_fetch_node_children",
                            {
                                "nodeId": node.id,
                                "nodeType": node.type,
                                "_locale": locale
                            }
                        );
                    }
                }
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
            plugins: ["checkbox", "search", "types"]
        });

    // TODO: réactiver cette partie lorsqu'on pourra relier la recherche à la base de données : on recherche n'importe quel produit, même s'il n'a pas encore été lazily loaded, et on doit pouvoir peupler l'arbre avec toutes les noeuds manquants, jusqu'au produit lui-même.

    // If the search input is submitted, parse the tree for results matching the input's value.
    $prospect_product_search_form.submit(function(e) {
        e.preventDefault();
        $prospect_product_search_tree
            .jstree(true)
            .search($prospect_product_search_input.val(), false);
    });

    // If the search input is cleared, clear the search results in the tree (and hide nodes that got revealed only because of this search).
    $prospect_product_search_input.on("input", function() {
        if ($prospect_product_search_input.val() === "") {
            $prospect_product_search_tree.jstree(true).clear_search();
        }
    });

    // Handle assortiment's submit.
    $prospect_submit_button.on("click", function (e) {
        e.preventDefault();
        var selectedNodes = getPluckedNodesWithIdAndType(checked_nodes);
        if (selectedNodes.length === 0) {
            window.alert("Vous ne pouvez pas soumettre un assortiment vide.");
            return;
        }
        $.ajax({
            type: "POST",
            url: Routing.generate(
                "sogedial_integration_admin_submit_prospect_assortiment",
                {
                    "_locale": locale
                }),
            data: {
                codeProspect: getParameterByName('codeProspect'),
                nodes: selectedNodes
            },
            dataType: "json",
            success: function (response) {
                window.location.href = Routing.generate(
                    'sogedial_integration_admin_mesprospects',
                    {
                      _locale: locale,
                    }
                  );
            },
            error: function (response) {
                if (response.status === 422) {
                    window.alert(JSON.parse(response.statusText));
                } else {
                    window.alert("Une erreur est survenue lors de la création de l'assortiment.");
                    console.log('ERROR :');
                    console.log(response);
                }
            }
        });
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

    /**
     * Easily retrieve query string parameters.
     * Source: https://stackoverflow.com/questions/901115/how-can-i-get-query-string-values-in-javascript
     *
     * @param {string} name Name of the query string parameter whose value must be fetched.
     * @param {string} [url] Optional url.
     */
    function getParameterByName(name, url) {
        if (!url) url = window.location.href;
        name = name.replace(/[\[\]]/g, "\\$&");
        var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, " "));
    }
});
