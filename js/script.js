(function( $ ) {

    "use strict";

    $(document).ready( function(){
        $("#website-form").submit(function(e) {

            e.preventDefault(); // avoid to execute the actual submit of the form.
            var form = $(this);
            var actionUrl = form.attr('action');
            $.ajax({
                type: "POST",
                url: actionUrl,
                data: form.serialize(), // serializes the form's elements.
                success: function(data)
                {
                    if(data.success){
                        $(".response-result").addClass('success');
                        $(".response-result").removeClass('error');
                    }else{
                        $(".response-result").addClass('error');
                        $(".response-result").removeClass('success');
                    }
                    $(".response-result").html(data.message);
                }
            });

        });
    } );

})(jQuery);
