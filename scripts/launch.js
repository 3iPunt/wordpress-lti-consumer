function lti_consumer_launch(id, id_lti, resource_link_id_val, is_modal) {
  var form = jQuery('form#launch-' + id);

  if ( form.data('post') !== '' ) {
    jQuery.post(
      ajaxurl,
      {action: 'lti_launch', id: id_lti,  resource_link_id: resource_link_id_val}
    ).done( function(data){
      console.log(data);
      
      

     //1.json decode data array

      var object = JSON.parse(data);
     
      //console.log(object);

      //2.set form parameters
      
      jQuery.each( object.parameters, function( key, value ) {
        
        console.log( key + ": " + value );

         eval('jQuery("form#launch-' + id+' input[name=' + key + ']").val("'+value+'")' );

         if (is_modal) {
           eval('jQuery("form#launch-modal-' + id+'").append(jQuery("form#launch-' + id+' input[name=' + key + ']"));' );
         } 
        

      });
      if (is_modal) {
        jQuery('form#launch-modal-' + id).submit();
      } else {
        form.submit();
      }

    });
  }

  
}

jQuery(document).ready(function () {
  jQuery('form[data-auto-launch="yes"]').each(function () {
    lti_consumer_launch(jQuery(this).data('id'));
  });
});
