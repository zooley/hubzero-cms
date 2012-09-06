/**
 * @package     hubzero-cms
 * @file        templates/hubbasic/js/globals.js
 * @copyright   Copyright 2005-2011 Purdue University. All rights reserved.
 * @license     http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

//-----------------------------------------------------------
//  Create our namespace
//-----------------------------------------------------------
if (!HUB) {
	var HUB = {};
}

//-----------------------------------------------------------
//  Various functions - encapsulated in HUB namespace
//-----------------------------------------------------------
if (!jq) {
	var jq = $;
	
	$.getDocHeight = function(){
	     var D = document;
	     return Math.max(Math.max(D.body.scrollHeight, D.documentElement.scrollHeight), Math.max(D.body.offsetHeight, D.documentElement.offsetHeight), Math.max(D.body.clientHeight, D.documentElement.clientHeight));
	};
} else {
	jq.getDocHeight = function(){
	     var D = document;
	     return Math.max(Math.max(D.body.scrollHeight, D.documentElement.scrollHeight), Math.max(D.body.offsetHeight, D.documentElement.offsetHeight), Math.max(D.body.clientHeight, D.documentElement.clientHeight));
	};
}

HUB.Base = {

	jQuery: jq,

	templatepath: '/templates/hubbasic2012/',

	// launch functions
	initialize: function() {
		var $ = this.jQuery, w = 760, h = 520;

		// Set the base path to this template
		/*$('script').each(function(i, s) {
			if (s.src && s.src.match(/hub\.jquery\.js(\?.*)?$/)) {
				HUB.Base.templatepath = s.src.replace(/js\/hub\.jquery\.js(\?.*)?$/, '');
			}
		});*/

		// Set focus on username field for login form
		if ($('#username').length > 0) {
			$('#username').focus();
		}

		// Set the search box's placeholder text color
		if ($('#searchword').length > 0) {
			$('#searchword')
				.css('color', '#777')
				.on('focus', function(){
					if ($(this).val() == 'Search') {
						$(this).val('').css('color', '#ddd');
					}
				})
				.on('blur', function(){
					if ($(this).val() == '' || $(this).val() == 'Search') {
						$(this).val('Search').css('color', '#777');
					}
				});
		}

		// Turn links with specific classes into popups
		$('a').each(function(i, trigger) {
			if ($(trigger).is('.demo, .popinfo, .popup, .breeze')) {
				$(trigger).on('click', function (e) {
					e.preventDefault();

					if ($(this).attr('class')) {
						var sizeString = $(this).attr('class').split(' ').pop();
						if (sizeString && sizeString.match('/\d+x\d+/')) {
							var sizeTokens = sizeString.split('x');
							w = parseInt(sizeTokens[0]);
							h = parseInt(sizeTokens[1]);
						}
					}

					window.open($(this).attr('href'), 'popup', 'resizable=1,scrollbars=1,height='+ h + ',width=' + w);
				});
			}
			if ($(trigger).attr('rel') && $(trigger).attr('rel').indexOf('external') !=- 1) {
				$(trigger).attr('target', '_blank');
			}
		});

		// Set the overlay trigger for launch tool links
		$('.launchtool').on('click', function(e) {
			$.fancybox({
				closeBtn: false, 
				href: HUB.Base.templatepath + 'images/anim/circling-ball-loading.gif'
			});
		});

		// Set overlays for lightboxed elements
		$('a[rel=lightbox]').fancybox();

		// Init tooltips
		$('.hasTip').tooltip({
			position:'TOP RIGHT',
			//offset: [10,-20],
			onBeforeShow: function(event, position) {
				var tip = this.getTip(),
					tipText = tip[0].innerHTML;
					
				if (tipText.indexOf('::') != -1) {
					var parts = tipText.split('::');
					tip[0].innerHTML = '<span class="tooltip-title">' + parts[0] + '</span><span class="tooltip-text">' + parts[1] + '</span>';
				}
			}
		}).dynamic({ bottom: { direction: 'down' }, right: { direction: 'left' } });
		$('.tooltips').tooltip({
			position:'TOP RIGHT',
			//offset: [10,2],
			onBeforeShow: function(event, position) {
				var tip = this.getTip(),
					tipText = tip[0].innerHTML;
					
				if (tipText.indexOf('::') != -1) {
					var parts = tipText.split('::');
					tip[0].innerHTML = '<span class="tooltip-title">' + parts[0] + '</span><span class="tooltip-text">' + parts[1] + '</span>';
				}
			}
		}).dynamic({ bottom: { direction: 'down' }, right: { direction: 'left' } });

		// Init fixed position DOM: tooltips
		$('.fixedToolTip').tooltip({
			relative: true
		});
	}

};

jQuery(document).ready(function($){
	HUB.Base.initialize();
});

