////////////////////////////////////////////////
// La barre de la recherche avec les suggestions
// convertit l'onjet de suggestion (1 par ligne) en code HTML

function sbs_format(suggestion_obj, order) {
    var onlyName = false;
    if ($('#search').attr("name") === "clients"){
        onlyName = true;
    }
    return '<tr class="sbs_line" sbs_order="' + order + '" data="' + suggestion_obj.text_to_use + '">'+
        (onlyName ? '': (suggestion_obj.ean13 ? ('<td>' + suggestion_obj.ean13 + '</td>') : '<td></td>')) +
        '<td>' + suggestion_obj.text + '</td>' +
        (onlyName ? '': (suggestion_obj.category ? (' <td class="sbs_category">' + suggestion_obj.category + '</td>') : '<td></td>')) +
        (onlyName ? '': (suggestion_obj.code_produit ? ('<td>' + suggestion_obj.code_produit + '</td>') : '<td></td>')) +
        '</tr>';
}

function sbs_trademark_format(trademark,index) {
    return "<tr sbs_trademark_index='"+index+"' class='sbs_trademark_line' data='" +
    trademark.name + "'><td><div class='trademark-name'>" +
    trademark.name + "</div><div class='trademark-infos'><div><i class='fa fa-info-circle'></i> Marque</div><div><i class='fa fa-barcode'></i>&nbsp;&nbsp; " +
    trademark.amount + "&nbsp; produits</div></div></td></tr>";
}

function checkIfMarkExists(list, item){
    
    var exists = list.some(function(listItem){
        if (listItem.name === item.category || !item.category && listItem.name === "SANS MARQUE"){
            listItem.amount++;
            return true;
        }
    });

    if (!exists){
        list.push({
            name: (item.category?item.category:"SANS MARQUE"),
            amount: 1
        })
    }
}

function orderTrademarks(a,b) {
  if (a.name < b.name)
    return -1;
  if (a.name > b.name)
    return 1;
  return 0;
}

function sbs_received_suggestions(data) {
    // cas spécial : on reçoit une réponse qui ne matche pas la valeur actuelle
    // il est important d'utiliser sbs_own_value et pas directement la valeur du champ
    if (sbs_own_value != data.query) {
        return;
    }
    $("#search-suggestions").removeClass('sbs_hidden');
    sbs_current = 0;
    // on reconstruit la liste des suggestions entièrement
    var list = $("#search-suggestions > #products-suggestions");
    var marques = $("#search-suggestions > #trademark-suggestions")
    list.empty();
    marques.empty();
    var marquesList = [];
    sbs_nb_suggestions = data.items.length;

    for (var suggestion_index = 0; suggestion_index < sbs_nb_suggestions; suggestion_index++) {
        checkIfMarkExists(marquesList, data.items[suggestion_index]);
        list.append(sbs_format(data.items[suggestion_index], suggestion_index));
    }

    marquesList.sort(orderTrademarks);
    for (var i = 0; i < marquesList.length; i++){
        marques.append(sbs_trademark_format(marquesList[i],i));
    }

    if (sbs_nb_suggestions == 0) {
        list.append('<tr class="sbs_line"><td><span class="sbs_none">(aucune suggestion)</span></td></tr>');
    }
    sbs_register_dynamic_handlers();
}

function sbs_on_input() {
    var look_for = $('#search').val();
    sbs_own_value = look_for;
    // cas spécial : chaîne vide : pas besoin de requête
    if (look_for == '') {
        $("#search-suggestions > #products-suggestions").empty();
        $("#search-suggestions > #trademark-suggestions").empty();
        $("#search-suggestions").addClass('sbs_hidden');
        $('#searchLoadingDiv').hide();
        return;
    } else {
        var list = $("#search-suggestions > #products-suggestions");
        var marques = $("#search-suggestions > #trademark-suggestions")
        list.empty();
        marques.empty();
        $("#search-suggestions").removeClass('sbs_hidden');
        $('#searchLoadingDiv').show();
    }
    var requestData = {
        q: look_for
    };

    //sbs_url_suggest is defined in layout.html.twig, it's depend of {{ args.privileges }}
    $.get(Routing.generate(sbs_url_suggest, { '_locale': locale }), requestData)
        .done(sbs_received_suggestions);
}

// ces fonctions gèrent les déplacements dans la liste des suggestions
function sbs_goto(offset) {
    if (sbs_nb_suggestions == 0) {
        return; // rien à faire
    }
    $("#search-suggestions").removeClass('sbs_hidden');
    $('#search-suggestions > #products-suggestions > tr').removeClass('sbs_line_selected');
    $('#search-suggestions > #trademark-suggestions > tr').removeClass('sbs_line_selected');

    sbs_current += offset;
    if (sbs_current < 0) sbs_current = sbs_nb_suggestions;
    if (sbs_current > sbs_nb_suggestions) sbs_current = 0;

    if (sbs_current == 0) {
        // on se souvient de la valeur originale si on boucle
        $('#search').val(sbs_own_value);
    } else {
        var current_item = $('#search-suggestions > #products-suggestions > tr');
        current_item.eq(sbs_current - 1).addClass('sbs_line_selected');
        var new_value = current_item.eq(sbs_current - 1).attr('data');
        $('#search').val(new_value);
    }
}

function sbs_on_keypress(event) {
    var pressed_key = event.which;
    if (pressed_key == 40) // flèche bas
    {
        sbs_goto(1);
    } else if (pressed_key == 38) // flèche haut
    {
        sbs_goto(-1);
    } else if (pressed_key == 27) // ESC => cacher la liste (sauf si une des flèches est appuyée de nouveau)
    {
        sbs_current = 0;
        $("#search-suggestions").addClass('sbs_hidden');
    } else if (pressed_key == 13)   // Enter => annuler le debounce_timer, utile pour la douchette
    {
        clearTimeout(sbs_debounce_timer);
    }
    // ensuite, le traitement par défaut sera exécuté
}

// la souris ne fait que pointer, cela ne change pas le texte dans la barre principale
// en revanche, cela remet le curseur "clavier" à zéro
function sbs_on_mousein(event) {
    $('#search-suggestions > tr').removeClass('sbs_line_selected');
    $(this).addClass('sbs_line_selected');
    sbs_current = 0;
}

function sbs_on_mouseout(event) {
    $(this).removeClass('sbs_line_selected');
}

function sbs_on_click(event) {
    $("#search-suggestions").addClass('sbs_hidden');        // pour ne pas avoir une animation sur la liste ouverte
    var order = $(this).attr('sbs_order');
    var current_item;
    var isTrademark = false;
    if (!order){
        isTrademark = true;
        order = $(this).attr('sbs_trademark_index');
        current_item = $('#search-suggestions > #trademark-suggestions > tr');
    } else {
        current_item = $('#search-suggestions > #products-suggestions > tr');
    }
    var new_value = current_item.eq(order).attr('data');
    $('#search').val((isTrademark ?"marque:"+new_value+" "+$('#search').val():new_value));
    $('#infobar').submit();
}

function sbs_register_dynamic_handlers() {
    var list_elements = $('#search-suggestions > #products-suggestions > tr');
    for (var suggestion_index = 0; suggestion_index < sbs_nb_suggestions; suggestion_index++) {
        list_elements.eq(suggestion_index).hover(sbs_on_mousein, sbs_on_mouseout);
        list_elements.eq(suggestion_index).mousedown(sbs_on_click);
    }

    var list_elements_trademarks = $('#search-suggestions > #trademark-suggestions > tr');
    for (var i = 0; i < list_elements_trademarks.length; i++) {
        list_elements_trademarks.eq(i).hover(sbs_on_mousein, sbs_on_mouseout);
        list_elements_trademarks.eq(i).mousedown(sbs_on_click);
    }
    $('#searchLoadingDiv').hide();
}

function sbs_is_digit(c)
{
    return !isNaN(parseInt(c, 10));
}

// debounce uniquement sur les chiffres
function sbs_on_input_debounced() {
    var func = sbs_on_input;
    return function() {
        var context = this, args = arguments;
        var later = function() {
            func.apply(context, args);
        };

        var sval = $('#search').val();
        var wait = 0;
        if (sval.length >= 1 && sbs_is_digit(sval.substr(sval.length-1, 1)))
        {
            wait = 200;
        }

        clearTimeout(sbs_debounce_timer);
        sbs_debounce_timer = setTimeout(later, wait);
    };
}

function sbs_on_blur(event) {
    $("#search-suggestions").addClass('sbs_hidden');
}

function sbs_register_static_handlers() {
    $('#search').on('input', sbs_on_input_debounced());
    $('#search').keydown(sbs_on_keypress);
    $('#search').blur(sbs_on_blur);
}

function sbs_main() {
    sbs_register_static_handlers();
}

function focusSearch() {                                    // ne pas faire le collapse ici! pour éviter que collapse soit fait dès l'ouverture de la page
    var search_bar_input = $("#search");
    if (search_bar_input.length > 0) {                      // trouvé
        search_bar_input.focus();

        var strLength = search_bar_input.val().length * 2;
        search_bar_input.focus();
        search_bar_input[0].setSelectionRange(strLength, strLength);    // rien n'est sélectionné

        var xTriggered = 0;
        search_bar_input.keypress(function(event) {
            if (event.which == 13) {
                //event.preventDefault();
            }
            xTriggered++;
            var msg = "Handler for .keypress() called " + xTriggered + " time(s).";
        });
    }
}

// variables globales
var sbs_debounce_timer;
var sbs_nb_suggestions = 0; // le nombre de suggestions à afficher
var sbs_current = 0; // le numéro de la ligne selectionnée par clavier ; 0 = aucune
var sbs_own_value = ''; // la valeur de la recherche tapée à la main
sbs_main();