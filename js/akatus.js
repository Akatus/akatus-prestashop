$("form input[name=submitState]").live("click", function() {

    if ($("select[name=id_order_state] option:selected").val() == 20) {

        $(this).hide();

        if ($(".temp").length === 0) {
            $(this).after('<span class="temp">solicitando estorno...</span>');
        } else {
            $(".temp").show();
        }

        var formulario = $(this.form);

        $.post(formulario.url, formulario.serialize())
            .always(function(){
                $(this).show();
                $(".temp").hide();
        });
    }

});
