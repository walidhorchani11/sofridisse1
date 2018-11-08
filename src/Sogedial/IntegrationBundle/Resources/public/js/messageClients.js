console.log("Chargé");
$("#edit-or-create-message-client").click(function(){
	var postMessage = {
		"messageLibelle" : $("#message_libelle").val(),
		"messageContent" : $("#message_content").val(),
		"dateDebut" : $("#date_debut").val(),
		"dateFin" : $("#date_fin").val(),
		"messageId": (message && message.id)?message.id:null
	};
	$.post(Routing.generate(
        "sogedial_create_or_edit_message",
        {
        	"_locale" : locale
    	}
    ), {
    	"messageId":postMessage.messageId,
    	"messageLibelle":postMessage.messageLibelle,
    	"messageContent":postMessage.messageContent,
    	"dateDebut": postMessage.dateDebut,
    	"dateFin": postMessage.dateFin,
    	"listeDestinataires":['1','2','3','4']
    }).done(function(data){
    	console.log(data);
    	if (data.data === "ok"){
    		window.location.href = Routing.generate(
    		'SogedialSite_integration_messages_clients',
    		{
    				"_locale" : locale
    		})
    	}
    });
})