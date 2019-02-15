require('../../vendor/twbs/bootstrap/dist/css/bootstrap.css');
require('../../vendor/twbs/bootstrap/dist/js/bootstrap.min.js');
require('../css/app.css');

$(function () {
    $('[data-toggle="tooltip"]').tooltip();
    $('[data-toggle="popover"]').popover();

    // We create a non visible textarea with the link,
    // select them and tell the browser to copy the selected content
    // after we copied the link the element will removed from DOM.
    $('.invite-share-link').click(function (element) {
        let el = document.createElement('textarea');

        el.value = element.target.dataset.link;
        el.setAttribute('readonly', '');
        el.style.position = 'absolute';
        el.style.left = '-9999px';
        document.body.appendChild(el);
        el.select();

        document.execCommand('copy');

        document.body.removeChild(el);
    });
    setTimeout(function () {
        $('.flash-notification').fadeOut();
    }, 10000);
});
