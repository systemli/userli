require('../../vendor/twbs/bootstrap/dist/css/bootstrap.css');
require('../../vendor/twbs/bootstrap/dist/js/bootstrap.min.js');
require('../css/app.css');

$(function () {
    // Event handler that copies the value of element's [data-link]
    // attribute to clipboard.
    //
    // Usage example:
    //
    //   <button data-value="foo">Copy foo to clickboard</button>
    //
    //   let el = document.querySelector('button');
    //   el.addEventListener('click', copyToClipboard);
    //
    // To do so it creates a non visible textarea with the value,
    // selects it and tell the browser to copy the selected content.
    // After the value has been copied, the textarea is removed from
    // DOM again.
    function copyToClipboard(event) {
        let el = document.createElement('textarea');

        el.value = event.currentTarget.dataset.value;
        el.setAttribute('readonly', '');
        el.style.position = 'absolute';
        el.style.left = '-9999px';
        document.body.appendChild(el);
        el.select();

        document.execCommand('copy');

        document.body.removeChild(el);
    }

    // initialize Bootstrap's tooltip and popover
    $('[data-toggle="tooltip"]').tooltip();
    $('[data-toggle="popover"]').popover();

    // initialize copy-to-clickboard buttons
    document.querySelectorAll('[data-button="copy-to-clipboard"]')
        .forEach(function(el) {
            el.addEventListener('click', copyToClipboard);
        });

    setTimeout(function () {
        $('.flash-notification').fadeOut();
    }, 10000);
});
