$(document).ready(function() {
	console.log("From Front")
	$(".material-form input").each(function(){
		if (!this.value || this.value === ''){
			$(this).addClass('input-empty');	
		}
	});
	$(".material-form textarea").each(function(){
		if (!this.value || this.value === ''){
			$(this).addClass('input-empty');	
		}
	});

	$(".material-form input").change(function() {
		if (this.value && this.value !== ''){
			$(this).removeClass("input-empty");
		} else {
			$(this).addClass('input-empty');
		}
	});
	$(".material-form textarea").change(function() {
		if (this.value && this.value !== ''){
			$(this).removeClass("input-empty");
		} else {
			$(this).addClass('input-empty');
		}
	});

	

	// Début d'empêchement dynamique des lettres dans un nombre qui doit être checké
	// en longueur de texte (Ex : Téléphone / Code Postal)
	$(".input-text-number input").each(function() {
		var elem = $(this);

		elem.keyup(function(e) {
			var input = $(this);

			input.val(input.val().replace(/[^0-9]/g, function (str) {
				return '';
			}));
		});
	});
});