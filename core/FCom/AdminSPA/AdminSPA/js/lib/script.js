/*
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
	
	$('select').select2();
	
	var body, click_event, content, nav, nav_toggler;
	nav_toggler = $("header .navbar-toggle");
	nav = $("#main-nav");
	content = $("#content");
	body = $("body");
	click_event = (jQuery.support.touch ? "tap" : "click");
		
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
	
	$('.favorite').click( function(e) {
		e.preventDefault();
		$(this).toggleClass('on');
	});
});

*/

/*
$('.f-scroll').perfectScrollbar();
console.log('есть скролл');
*/
