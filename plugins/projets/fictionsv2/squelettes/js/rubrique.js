$(document).ready(function() {
	// Redimensionnement
	window.onresize = resize_global_content;
	resize_global_content();

	// Tooltip liste
	function showTooltipListeLinkSmall(el) {
		var offset = $(el).offset();
		var content_tooltip = $(el).find('.liste-tooltip-content').html();
		$('#liste-tooltip').show().html(content_tooltip);
		var hauteur_tooltip = $('#liste-tooltip .liste-tooltip-inner').height();
		$('#liste-tooltip').height(hauteur_tooltip);
		$('#liste-tooltip').css('top', Math.round(offset.top) - hauteur_tooltip - 5);
		$('#liste-tooltip').css('left', Math.round(offset.left) - 108 + 2);
	}
	$('.liste-link-small').on('mouseover focus', function() { showTooltipListeLinkSmall(this); });
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

	// Script écrivain : open/close
	$('#slideup-script-ecrivain, #close-script-ecrivain').hide();
	$('#open-script-ecrivain').click(function() {
		$('#slideup-script-ecrivain').toggle("slow");
		$('#open-script-ecrivain, #close-script-ecrivain').toggle();
	});
	$('#close-script-ecrivain').click(function() {
		$('#slideup-script-ecrivain').toggle("slow");
		$('#open-script-ecrivain, #close-script-ecrivain').toggle();
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

	// Corrections plugin Crayon
	setInterval(function() {
		$('.crayon-html').css({top:'0px', left:'0px', width:'100%', height:'100%', 'z-index':'9400'});
		$('.crayon-active').css({'background-color':'#FFF', color:'#000', height:'300px', 'font-size':'13px', 'line-height':'18px', width:'516px'});
		$('.formulaire_crayon').css({position:'absolute', width:'520px', height:'500px', top:'50%', left:'50%', 'margin-left':'-250px', 'margin-top':'-150px'});
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
