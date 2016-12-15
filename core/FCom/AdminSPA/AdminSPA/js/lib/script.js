$(document).ready(function() {
	
	$(function() {
		$('#daterange1').daterangepicker({
			singleDatePicker: true,
			showDropdowns: true,
			opens: 'left'
		});
		$('#daterange2').daterangepicker({
			format: "YYYY-MM-DD",
			showDropdowns: true,
			opens: 'left'
		});
	});
	
	$(function() {
		$('#daterange2').daterangepicker({
			format: "YYYY-MM-DD",
			opens: 'left'
		});
	});
										 
	
	/*jQuery('#myFixedTable').fixedTable({
		table: {
			height: 600,
			width: 1600
		}
	});
		*/
	$.Placeholder.init({  
		color : "#000"
	});
	
	$('select').select2();
	
	var body, click_event, content, nav, nav_toggler;
	nav_toggler = $("header .navbar-toggle");
	nav = $("#main-nav");
	content = $("#content");
	body = $("body");
	click_event = (jQuery.support.touch ? "tap" : "click");
		
	$("#main-nav .dropdown-collapse").on('click', function (e) {
		$('.dropdown, .login-status').removeClass('open');
		$('#main-nav .dropdown-collapse ul ul').css('display', 'none');
		var link, list;
		e.preventDefault();
		link = $(this);
		list = link.parent().find("> ul");
		if (list.is(":visible")) {
			if (body.hasClass("main-nav-closed") && link.parents("li").length === 1) {
				return false;
			} else {
				link.removeClass("in");
				list.slideUp(300, function () {
					return $(this).removeClass("in");
				});
			}
		} else {
			if (list.parents("ul.nav.nav-stacked").length === 1) {
				$(document).trigger("nav-open");
			}
			link.addClass("in");
			list.slideDown(300, function () {
				return $(this).addClass("in");
			});
		}
		return false;
	});
	
	$('.navbar-toggle').on('click', function(e) {
		e.preventDefault();
			$('.navbar-collapse').toggleClass('close');
			$('#main-nav .dropdown-collapse, #main-nav .nav-stacked').removeClass('in');
			$('#main-nav .nav-stacked').removeAttr('style');
			console.log('done');
			if ($('.navbar-collapse').hasClass('close')) {
				body.removeClass("main-nav-opened").addClass("main-nav-closed");
			} else {
				body.addClass("main-nav-opened").removeClass("main-nav-closed");
			}
	});
	
	$('.chat-toggle').on('click', function(e) {
		e.preventDefault();
		$('header .dropdown, .login-status').removeClass('open');
		if ($('.chat-toggle').parent().hasClass('open')) {
			$('.chat-toggle').parent().removeClass("open");
		} else {
			$('.chat-toggle').parent().addClass("open");
		}
	});
	
	$('.filter-toggle').on('click', function(e) {
		e.preventDefault();
		$('header .dropdown, .login-status').removeClass('open');
		if ($(this).parent().hasClass('open')) {
			$(this).parent().removeClass("open").parent().children().removeAttr('style');
		} else {
			$('.f-grid-filter-panel .dropdown').removeClass('open');
			$(this).parent().addClass("open").next().css('border-color', 'transparent').prev().prev().css('border-color', 'transparent');
		}
	});
	
	$('.status-toggle').on('click', function(e) {
		e.preventDefault();
		$('header .dropdown').removeClass('open');
		$('.chat-toggle').parent().removeClass('open');
		if ($('.status-toggle').parents('.login-status').hasClass('open')) {
			$(this).parents('.login-status').removeClass('open');
		} else {
			$('.status-toggle').parents('.login-status').addClass("open");
		}
		return false;
	});
	
	$('header .dropdown').on('click', function(e) {
		e.preventDefault();
		$('.chat-toggle').parent().removeClass('open');
		$('.login-status').removeClass('open');
	});
});

