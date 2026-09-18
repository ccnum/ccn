(function ($) {

    /**
     * Transforme chaque élément sélectionné en un système d'onglets.
     * Chaque enfant direct doit contenir une div `.tabs-title` (libellé de l'onglet)
     * et une div `.tabs-content` (contenu associé).
     *
     * @param {Object} [options]
     * @param {number} [options.active=0] - Index de l'onglet actif à l'initialisation
     * @returns {jQuery} La collection jQuery pour le chaînage
     */
    $.fn.customTabs = function (options) {

        const settings = $.extend({
            active: 0,
            urlParam: null,
            pushHistory: true
        }, options);

        return this.each(function () {

            const $container = $(this);

            // Idempotent : un conteneur déjà transformé ne l'est pas une 2e fois.
            if ($container.children('.ctabs').length > 0) {
                return;
            }

            const $panels = $container.children();

            const $wrapper = $('<div class="ctabs"></div>');
            const $nav = $('<ul class="ctabs-nav"></ul>');
            const $content = $('<div class="ctabs-content"></div>');

            // Onglet demandé dans l'URL (ex: &onglet=commentaires), pour
            // restaurer l'onglet actif après un F5 ou un lien direct.
            const tabIdDansUrl = settings.urlParam
                ? new URL(window.location.href).searchParams.get(settings.urlParam)
                : null;
            let initialActive = settings.active;

            $panels.each(function (index) {

                const $panel = $(this);

                const $titleEl = $panel.children('.tabs-title');
                const $panelContent = $panel.children('.tabs-content');

                const title = $titleEl.length
                    ? $titleEl.html().trim()
                    : ('Onglet ' + (index + 1));

                const $tab = $('<li></li>')
                    .html(title)
                    .attr('data-index', index);

                if ($panel.data('tab-id')) {
                    $tab.attr('data-tab-id', $panel.data('tab-id'));
                    if (tabIdDansUrl && $panel.data('tab-id') === tabIdDansUrl) {
                        initialActive = index;
                    }
                }

                $nav.append($tab);
                $panelContent
                    .addClass('ctabs-panel')
                    .appendTo($content);
            });

            $container.empty();

            $wrapper
                .append($nav)
                .append($content);

            $container.append($wrapper);

            /**
             * Active l'onglet à l'index donné.
             *
             * @param {number} index - Index (base 0) de l'onglet à afficher
             */
            function activate(index) {

                $nav.children().removeClass('active');
                $nav.children().eq(index).addClass('active');

                const $activePanel =$content
                    .children('.ctabs-panel')
                    .hide()
                    .eq(index)
                    .show();

                const $activeTab = $nav.children().eq(index);

                if (settings.urlParam) {
                    const tabId = $activeTab.data('tab-id');
                    if (tabId) {
                        const url = new URL(window.location.href);
                        url.searchParams.set(settings.urlParam, tabId);
                        if (settings.pushHistory) {
                            window.history.pushState(null, '', url);
                        } else {
                            window.history.replaceState(null, '', url);
                        }
                    }
                }

                $container.trigger('customtabs:activated', {
                    index: index,
                    panel: $activePanel[0],
                    tabId: $activeTab.data('tab-id')
                });
            }

            $nav.on('click', 'li', function () {
                activate($(this).data('index'));
            });

            activate(initialActive);
        });
    };

})(jQuery);