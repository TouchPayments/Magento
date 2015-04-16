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

// ----- Make sure that we have a Touch Express button after an ajax call
Ajax.Responders.register({
    onComplete: function(callback, request) {
        if (request.responseText.indexOf('touch-express-button') !== -1) {
            $$('script.touch-express-button').each(function(element) {
                var tag = document.createElement('SCRIPT'), attr;

                tag.setAttribute('src', element.getAttribute('src'));
                tag.setAttribute('class', element.getAttribute('class'));
                tag.setAttribute('id', element.getAttribute('id'));
                tag.setAttribute('data-key', element.getAttribute('data-key'));
                tag.setAttribute('data-url', element.getAttribute('data-url'));
                tag.setAttribute('data-success-url', element.getAttribute('data-success-url'));

                element.parentElement.replaceChild(tag, element);
            });
        }
    }
});

// ----- Touch Payments promotional material

function isiOS() {
    return navigator.userAgent.match(/(iPhone|iPod|iPad|Android)/g) ? true : false;
}

var touchModal = new Control.Modal($('modal'),{
    overlayOpacity: 0.40,
    className: 'touch-modal',
    fade: true,
    closeOnClick: true
});

var iframeHeight = 800,
    iframeWidth = 400,
    loaded = false;


if (isiOS()) {
    if (screen.width < 400) {
        iframeHeight = 1050;
        iframeWidth = 360;
    } else {
        iframeHeight = 900;
    }
}

document.observe('click', function(e, el) {
    if (e.findElement('.touch-cart-banner,.touch-product-banner,.what-is-touch')) {

        if (isiOS()) {
            window.scrollTo(0, 0);
        } else {
            $$('.touch-modal').each(function (element) {
                element.style.position = 'fixed';
            });
        }

        if (!loaded) {
            touchModal.container.insert('<iframe src="https://app.touchpayments.com.au/index/howto?type=express" width="' + iframeWidth + '" height="' + iframeHeight + '"></iframe>');
            loaded = true;
        }

        touchModal.open();
    }
});

window.addEventListener("message", function (e) {
    if (e.data == 'close') {
        touchModal.close();
    }
}, false);

window.addEventListener("keyup", function (e) {
    if (e.keyCode == 27) {
        touchModal.close();
    }
}, false);

