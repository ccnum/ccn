function confirmation(txt) {
    return window.confirm(txt);
}

// Rollover générique : lit data-src-hover sur l'img pour le state hover
$(document).ready(function() {
    $('.btn-rollover').on('mouseenter', function() {
        var hover = $(this).data('src-hover');
        if (hover) {
            $(this).data('src-normal', $(this).attr('src')).attr('src', hover);
        }
    }).on('mouseleave', function() {
        var normal = $(this).data('src-normal');
        if (normal) $(this).attr('src', normal);
    });
});

// Tooltip de la grille des histoires (#526) : s'ouvre sous la case survolée,
// ou au-dessus si elle déborderait du bas de la fenêtre ; la bulle (flèche)
// est placée côté case.
function afficher_tooltip_liste(el, decalage_gauche) {
    var $el = $(el);
    var $tooltip = $('#liste-tooltip');
    $tooltip.removeClass('en-bas').show().html($el.find('.liste-tooltip-content').html());
    var hauteur = $tooltip.find('.liste-tooltip-inner').outerHeight();
    var hauteur_bulle = $tooltip.find('.liste-tooltip-bulle').outerHeight() || 0;
    $tooltip.height(hauteur + hauteur_bulle);
    var offset = $el.offset();
    var rect = el.getBoundingClientRect();
    var place_dessous = window.innerHeight - rect.bottom;
    var en_haut = place_dessous < hauteur + hauteur_bulle + 5 && rect.top > place_dessous;
    var top;
    if (en_haut) {
        top = offset.top - hauteur - hauteur_bulle - 5;
    } else {
        $tooltip.addClass('en-bas').find('.liste-tooltip-bulle').prependTo($tooltip);
        top = offset.top + $el.outerHeight() + 5;
    }
    $tooltip.css({top: Math.round(top), left: Math.round(offset.left) + decalage_gauche});
}

function resize_global_content() {
    var h = $(window).height() - 61;
    $('.global-content').height(h);
    var stitches_margin = ((h - 60) - 258) / 4;
    $('.stitches-inner').css({'margin-top': stitches_margin});
}

function pagination(var_compteur) {
    var num_page_gauche, num_page_droite;
    if (var_compteur % 2 === 0) {
        num_page_gauche = var_compteur - 1;
        num_page_droite = var_compteur;
    } else {
        num_page_gauche = var_compteur;
        num_page_droite = var_compteur + 1;
    }
    $('.lecture-page').hide();
    $('#page-gauche-' + num_page_gauche).show();
    $('#page-droite-' + num_page_droite).show();
}

function next_prev(var_compteur, var_limite) {
    $('#bt-next, #bt-prev').show();
    if (var_compteur >= var_limite - 1) {
        $('#bt-next').hide();
    }
    if (var_compteur === 1 || var_compteur === 2) {
        $('#bt-prev').hide();
    }
}
