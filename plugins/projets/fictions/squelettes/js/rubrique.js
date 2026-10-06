$(document).ready(function() {
	// Redimensionnement
	window.onresize = resize_global_content;
	resize_global_content();

	// Tooltip liste
	$('.liste-link-small').on('mouseover focus', function() { afficher_tooltip_liste(this, -106); });
	$('.liste-link-small').on('mouseout blur', function() { $('#liste-tooltip').hide(); });

	// Navigation par ancre
	var $scrollTarget = $('.global-content');
	function scrollToEl($container, selector, duration, offsetTop) {
		var top = selector === 0 ? 0 : $container.find(selector).position().top + $container.scrollTop() + (offsetTop || 0);
		$container.stop(true).animate({scrollTop: top}, duration);
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

	// Script écrivain : open/close, un bloc par chapitre visible
	$('.slideup-script-ecrivain, .close-script-ecrivain').hide();
	$('.open-script-ecrivain, .close-script-ecrivain').click(function() {
		var $script = $(this).closest('.script-ecrivain');
		$script.find('.slideup-script-ecrivain').toggle("slow");
		$script.find('.open-script-ecrivain, .close-script-ecrivain').toggle();
	});

	// Script collège : open/close
	$('#open-script-college').click(function() {
		$('#slideup-script-college').toggle('fast');
		$(this).toggleClass('is-open');
	});

	// Prologue : open/close
	$('#texte_prologue, #close_prologue').hide();
	$('#open_prologue').click(function() {
		$('#texte_prologue').show('normal');
		$('#close_prologue').show();
		$(this).hide();
		resize_global_content();
	});
	$('#close_prologue').click(function() {
		$('#texte_prologue').hide('normal');
		$('#open_prologue').show();
		$(this).hide();
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
