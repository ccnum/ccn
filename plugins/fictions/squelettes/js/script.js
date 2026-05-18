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
