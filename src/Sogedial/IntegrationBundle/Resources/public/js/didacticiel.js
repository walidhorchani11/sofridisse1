var is_a_new_order = $(".new-order-button").length;
var steps_on_dashboard = (is_a_new_order > 0)?
[
	{
	    'click .new-order-button' : 'Pour constituer une commande, cliquez ici',
	    'showNext':true,
		"skipButton" : {className: "didacticiel-button" ,text: "Quitter"},
		"nextButton" : {className: "didacticiel-button" ,text: "Suivant"}
	},{
	    'click .show-catalogue' : 'Pour consulter le catalogue, cliquez ici',
	    'showNext':false,
		"skipButton" : {className: "didacticiel-button" ,text: "Quitter"}
	},{
	    'click .change-company' : 'Pour basculer sur une autre de vos société, cliquez ici',
	    'showNext':false,
	    "nextButton" : {className: "didacticiel-button" ,text: "Suivant"},
		"skipButton" : {className: "didacticiel-button" ,text: "Quitter"}
	}
]:
[
	{
	    'click .sidebar-element-commandes' : 'Pour accéder à vos commandes passées et pouvoir les renouveler, cliquez ici',
	    'showNext':true,
		"skipButton" : {className: "didacticiel-button" ,text: "Quitter"},
		"nextButton" : {className: "didacticiel-button" ,text: "Suivant"}
	},
  	{
	    'click .infobar-table-sale-category-link' : 'Accédez rapidement aux produits nouveaux ou en promotion ici',
	    'showNext':false,
		"skipButton" : {className: "didacticiel-button" ,text: "Quitter"}
	}
];

var steps_on_catalogue = [
	{
		'click .menu-scroll' : "Naviguez dans les catégories pour visualiser nos produits d'une famille précise",
		'showNext':true,
		"skipButton" : {className: "didacticiel-button" ,text: "Quitter"},
		"nextButton" : {className: "didacticiel-button" ,text: "Suivant"}
	},{
		'click .search-bar-selector' : "Vous pouvez rechercher une référence par son nom, son EAN, sa famille...",
		'showNext':true,
		"skipButton" : {className: "didacticiel-button" ,text: "Quitter"},
		"nextButton" : {className: "didacticiel-button" ,text: "Suivant"}
	},{
		'click .prix_ht-center' : "Ajoutez un produit à votre panier en cliquant sur le + ou en modifiant la quantité voulue",
		'showNext':true,
		"skipButton" : {className: "didacticiel-button" ,text: "Quitter"},
		"nextButton" : {className: "didacticiel-button" ,text: "Suivant"}
	},{
		'click .order-button-to-order' : "Accédez à votre panier et continuer votre commande",
		'showNext':true,
		"skipButton" : {className: "didacticiel-button" ,text: "Quitter"},
		"nextButton" : {className: "didacticiel-button" ,text: "Suivant"}
	},{
		'click .logo' : "Vous pouvez revenir à votre page d'accueil ici",
		'showNext':true,
		"skipButton" : {className: "didacticiel-button" ,text: "Quitter"},
		"nextButton" : {className: "didacticiel-button" ,text: "Suivant"}
	}
];

var steps_on_order = [
	{
		'click .arrow-menu' : "Vous pouvez à tout moment revenir en arrière en cliquant ici.",
		'showNext':true,
		"skipButton" : {className: "didacticiel-button" ,text: "Quitter"},
		"nextButton" : {className: "didacticiel-button" ,text: "Suivant"}
	},{
		'click .dry-date' : "Vérifiez que vous n'avez rien oublié d'ajouter à votre commande, puis entrez la/les date(s) de livraison requises",
		'showNext':true,
		"skipButton" : {className: "didacticiel-button" ,text: "Quitter"},
		"nextButton" : {className: "didacticiel-button" ,text: "Suivant"}
	},{
		'click #order' : "Vous pouvez ensuite valider votre commande.<br><strong>ATTENTION : SI VOUS VALIDEZ, CELA VA LANCER UNE VRAIE COMMANDE.</strong>",
		'showNext':true,
		"skipButton" : {className: "didacticiel-button" ,text: "Quitter"},
		"nextButton" : {className: "didacticiel-button" ,text: "Suivant"}
	}
];

//set script config

var test = function(){
	var url = Routing.generate('sogedial_finish_first_visit',{
		'_locale': "fr" // REPLACER PAR VRAIE locale DANS LES VARIABLES GLOBALES DE LAYOUT
	});
	var enjoyhint_instance = new EnjoyHint({
		onEnd:function(){
			$.ajax({
                url: url,
                type: 'GET'
            });
  		},
  		onSkip:function(){
			$.ajax({
                url: url,
                type: 'GET'
            });
  		}
	});
	var splittedPathCheck = window.location.pathname.split('/');
	var type_didacticiel;
    // Split the path to get url and call right route
    if (splittedPathCheck[splittedPathCheck.length - 1] === "catalogue"){
    	type_didacticiel = steps_on_catalogue;
    } else if (splittedPathCheck[splittedPathCheck.length - 1] === "order" || splittedPathCheck[splittedPathCheck.length - 2] === "order"){
    	type_didacticiel = steps_on_order;
    } else if (splittedPathCheck[splittedPathCheck.length - 1] === "dashboard"){
    	type_didacticiel = steps_on_dashboard;
    } else {
    	return(0);
    }
	enjoyhint_instance.set(type_didacticiel);
	enjoyhint_instance.run();	
} 
if (showDidacticiel){
	test();
}

$(".launch-didacticiel").click(function(){
	test();
})