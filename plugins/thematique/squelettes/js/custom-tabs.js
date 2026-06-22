(function ($) {

    $.fn.customTabs = function (options) {

        const settings = $.extend({
            active: 0
        }, options);

        return this.each(function () {

            const $container = $(this);

            const $panels = $container.children();

            const $wrapper = $('<div class="ctabs"></div>');
            const $nav = $('<ul class="ctabs-nav"></ul>');
            const $content = $('<div class="ctabs-content"></div>');

            $panels.each(function (index) {

                const $panel = $(this);

                const title = $panel.data('title') || ('Onglet ' + (index + 1));

                const $tab = $('<li></li>')
                    .text(title)
                    .attr('data-index', index);

                $nav.append($tab);

                $panel
                    .addClass('ctabs-panel')
                    .appendTo($content);
            });

            $container.empty();

            $wrapper
                .append($nav)
                .append($content);

            $container.append($wrapper);

            function activate(index) {

                $nav.children().removeClass('active');
                $nav.children().eq(index).addClass('active');

                $content
                    .children('.ctabs-panel')
                    .hide()
                    .eq(index)
                    .show();
            }

            $nav.on('click', 'li', function () {
                activate($(this).data('index'));
            });

            activate(settings.active);
        });
    };

})(jQuery);