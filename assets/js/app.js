require('../../vendor/twbs/bootstrap/dist/css/bootstrap.css');
require('../../vendor/twbs/bootstrap/dist/js/bootstrap.min.js');
require('../css/app.css');
//require('../css/AdminLTE.css');
$(function () {
    $('[data-toggle="tooltip"]').tooltip();
    $('[data-toggle="popover"]').popover();

    setTimeout(function () {
        $('.flash-notification').fadeOut();
    }, 10000);
});
