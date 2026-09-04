(function ($, window, i) {
    /* get selector name */
    $.fn._init = $.fn.init;

    $.fn.init = function (selector, context, root) {
        return (typeof selector === 'string') ?
            new $.fn._init(selector, context, root).data('selector', selector) :
            new $.fn._init(selector, context, root);
    };
})(jQuery, this, 0);
