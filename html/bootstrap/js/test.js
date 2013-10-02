$(document).ready(function() {

	$('.fcom-icon-search').on('click', function(evt) {
		$('.fcom-search').toggle();
	});

	$('.fcom-chat-search').on('click', function(evt) {
		evt.stopPropagation();
	});

	$('.fcom-multilevel').on('click', function(evt) {
		$('.fcom-multilevel-menu-nav').removeClass('selected');
		$('.fcom-multilevel-menu').hide();

		if($(this).hasClass('open')) {
			$(this).find('.fcom-multilevel-menu').first().hide();
			$(this).removeClass('open');
		}else {
			$(this).find('.fcom-multilevel-menu').first().show();
			$(this).addClass('open');
		}

		evt.stopPropagation();
	});

	$('.fcom-multilevel-menu-nav').on('click', function(evt) {
		$('.fcom-multilevel-menu-nav').removeClass('selected');
		$('.fcom-multilevel-menu').hide();

		if($(this).closest('ul').hasClass('fcom-multilevel-menu-firstLevel')) {
			$(this).closest('ul').show();
		} else if($(this).closest('ul').hasClass('fcom-multilevel-menu-secondLevel')) {
			$(this).closest('ul').show();
			$(this).closest('ul').closest('ul').show();
		}

		$(this).addClass('selected');
		$(this).find('.fcom-multilevel-menu').first().show();

		evt.stopPropagation();
	});

	$('.fcom-multilevel-menu-round-small').on('click', function(evt) {
		evt.stopPropagation();
	});

	var showParent = function(element) {
		$(element).show();

	};

});