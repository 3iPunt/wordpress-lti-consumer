var current_modal_open = false;
function lti_consumer_launch(id, id_lti, resource_link_id_val, is_modal, is_in_comments, internal_id) {
  var form = jQuery('form#launch-' + id);
    current_modal_open = "#modal"+id;
    jQuery("#iframe-modal-"+id).contents().find('html').html("<link rel='stylesheet' href='http://getbootstrap.com/dist/css/bootstrap.min.css' type='text/css' media='screen'><link rel='stylesheet' href='http://netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.css' type='text/css' media='screen'>  <br><br><div class=\"col-xs-5 col-centered\"><div class=\"item\"><div class=\"content\"></div></div></div><div class='col-xs-2 col-centered'><div class='item'><div class='content'><h1><i class='icon-spinner icon-spin icon-large glyphicon-align-center'></i></h1></div></div></div><div class=\"col-xs-5 col-centered\"><div class=\"item\"><div class=\"content\"></div></div></div>");
    jQuery.post(
      ajaxurl,
      {action: 'lti_launch', id: id_lti,  resource_link_id: resource_link_id_val, internal_id: internal_id, is_in_comments: is_in_comments}
    ).done( function(data){
      //1.json decode data array
      var object = JSON.parse(data);
      //2.set form parameters      
      jQuery.each( object.parameters, function( key, value ) {
        
         eval('jQuery("form#launch-' + id+' input[name=' + key + ']").val("'+value+'")' );

         if (is_modal) {
            if (eval('jQuery("form#launch-modal-' + id+' input[name=' + key + ']")').length>0) {
              //update it
              eval('jQuery("form#launch-modal-' + id+' input[name=' + key + ']").val("'+value+'")' );
            } else {
              //appedn it
             eval('jQuery("form#launch-modal-' + id+'").append(jQuery("form#launch-' + id+' input[name=' + key + ']"));' );
            }
         }

      });
      if (is_modal) {
        jQuery('form#launch-modal-' + id).submit();
      } else {
        form.submit();
      }

    });

  
}

jQuery(document).ready(function () {
  jQuery('form[data-auto-launch="yes"]').each(function () {
    lti_consumer_launch(jQuery(this).data('id'));
  });
});

var eventMethod = window.addEventListener ? "addEventListener" : "attachEvent";
var eventer = window[eventMethod];
var messageEvent = eventMethod == "attachEvent" ? "onmessage" : "message";

// Listen to message from child window
eventer(messageEvent,function(e) {
  if (e.data && e.data.embed_id) {
    if ($(current_modal_open)) {
      $(current_modal_open).hide();
    }
    try {
      setWowzaEmbedId(e.data.embed_id);
    } catch (e) {

    }
  }
},false);
