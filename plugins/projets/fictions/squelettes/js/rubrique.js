$(document).ready(function() {
	// Redimensionnement
	window.onresize = resize_global_content;
	resize_global_content();

	// Tooltip liste
	$('.liste-link-small').on('mouseover focusin', function() { afficher_tooltip_liste(this, -106); });
	$('.liste-link-small').on('mouseout focusout', function() { $('#liste-tooltip').hide(); });

	// Navigation par ancre : défile jusqu'à la cible et y place le focus
	// clavier, pour que la tabulation reparte de là
	var $scrollTarget = $('.global-content');
	function scrollToEl($container, selector, duration, offsetTop) {
		var $cible = selector === 0 ? $() : $container.find(selector).first();
		if (selector !== 0 && !$cible.length) {
			return;
		}
		var top = selector === 0 ? 0 : $cible.position().top + $container.scrollTop() + (offsetTop || 0);
		$container.stop(true).animate({scrollTop: top}, duration);
		if ($cible.length) {
			// cible dans un lien (bouton forum) : c'est le lien qui prend le focus
			var el = $cible.closest('a')[0] || $cible.attr('tabindex', -1)[0];
			el.focus({preventScroll: true});
		}
	}
	$('#nav-script').click(function(event) {
		event.preventDefault();
		scrollToEl($scrollTarget, '.script-ecrivain', 300, -50);
	});
	$('#nav-forum').click(function(event) {
		event.preventDefault();
		scrollToEl($scrollTarget, '.forum-titre', 300, -75);
	});
	$('#nav-ecrire').click(function(event) {
		event.preventDefault();
		scrollToEl($scrollTarget, '.ecriture-chapitre:last', 300, -40);
	});
	$('#nav-top').click(function(event) {
		event.preventDefault();
		scrollToEl($scrollTarget, 0, 300, 0);
	});

	// Script écrivain (un bloc par chapitre visible) et script collège :
	// bouton open/close, état porté par aria-expanded
	$('.slideup-script-ecrivain').hide();
	$('.open-script-ecrivain, #open-script-college').click(function() {
		var ouvert = $(this).attr('aria-expanded') !== 'true';
		$('#' + $(this).attr('aria-controls')).toggle(ouvert ? 'slow' : 'fast');
		$(this).attr('aria-expanded', ouvert).toggleClass('is-open', ouvert);
	});

	// Prologue : open/close, le focus passe au bouton qui remplace celui cliqué
	$('#texte_prologue, #close_prologue').hide();
	$('#open_prologue').click(function() {
		$('#texte_prologue').show('normal');
		$(this).hide();
		$('#close_prologue').show().focus();
		resize_global_content();
	});
	$('#close_prologue').click(function() {
		$('#texte_prologue').hide('normal');
		$(this).hide();
		$('#open_prologue').show().focus();
	});

	// Titre, texte et script du chapitre : le crayon s'édite sur place,
	// dans son cadre. Crayons pose son formulaire en absolu dans <body>
	// (rel de .crayon-icones = id du formulaire) : on le replace juste après
	// l'élément, qui reprend son cadre (marges, bordure, padding, largeur).
	function crayons_en_place() {
		$('.ecriture-edition, .ecriture-chapitre-titre, .script-texte').children('.crayon-icones[rel]').each(function() {
			var $source = $(this).parent();
			var $crayon = $('#' + $(this).attr('rel'));
			// formulaire supprimé (annuler) : on réaffiche l'élément
			if (!$crayon.length) {
				$source.show();
				return;
			}
			if (!$crayon.hasClass('crayon-en-place')) {
				$crayon
					.addClass('crayon-en-place')
					.css({
						margin: $source.css('margin'),
						padding: $source.css('padding'),
						border: $source.css('border'),
						width: $source.css('width')
					})
					.insertAfter($source);
			}
			$source.toggle(!$source.hasClass('crayon-has') || !$crayon.is(':visible'));
		});
	}
	$(document).ajaxComplete(crayons_en_place);

	// Corrections plugin Crayon (autres crayons : formulaire en modale centrée)
	setInterval(function() {
		crayons_en_place();
		$('.crayon-html:not(.crayon-en-place)').css({top:'0px', left:'0px', width:'100%', height:'100%', 'z-index':'9400'});
		$('.crayon-html:not(.crayon-en-place) .crayon-active').css({'background-color':'#FFF', color:'#000', height:'300px', 'font-size':'13px', 'line-height':'18px', width:'516px'});
		$('.crayon-html:not(.crayon-en-place) .formulaire_crayon').css({position:'absolute', width:'520px', height:'500px', top:'50%', left:'50%', 'margin-left':'-250px', 'margin-top':'-150px'});
	}, 200);

	// Rollover flèche script écrivain : dérivé du nom de fichier _hover
	$('.script-ecrivain-edit').on('mouseenter', function() {
		var img = $(this).prev('.script-forum-fleche').find('img');
		img.data('src-normal', img.attr('src'));
		img.attr('src', img.attr('src').replace(/\.png$/, '_hover.png'));
	}).on('mouseleave', function() {
		var img = $(this).prev('.script-forum-fleche').find('img');
		var normal = img.data('src-normal');
		if (normal) img.attr('src', normal);
	});
});
