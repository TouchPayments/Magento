if (typeof(Review) !== 'undefined') {

    Review.prototype.nextStep  = Review.prototype.nextStep.wrap(function(parent, transport){
        var response;

        if (transport && transport.responseText) {
            try {
                response = eval('(' + transport.responseText + ')');
            } catch (e) {
                response = {};
            }

            if (response.redirect && response.redirect.indexOf('/sms') !== -1) {

                var request = new Ajax.Request(
                    response.redirect,
                    {
                        method: 'get',
                        onSuccess: function(transport) {
                            try {
                                response = eval('(' + transport.responseText + ')');
                            } catch (e) {
                                response = {};
                            }

                            var modal = new Control.Modal($('modal'),{
                                overlayOpacity: 0.75,
                                className: 'modal',
                                fade: true,
                                closeOnClick: false
                            });

                            modal.container.insert(response.responseText);
                            modal.open();
                        }
                    }
                );
                return false;
            } else {
                return parent(transport);
            }
        }
    });
}
