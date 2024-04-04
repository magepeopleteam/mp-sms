const tabItems = document.querySelectorAll('.mpStyles .tab-item');
const tabContents = document.querySelectorAll('.mpStyles .tab-content');

tabItems.forEach((tabItem) => {
tabItem.addEventListener('click', () => {

	tabItems.forEach((item) => {
	item.classList.remove('active');
	});
	tabContents.forEach((content) => {
	content.classList.remove('active');
	});

	const target = tabItem.getAttribute('data-tabs-target');
	tabItem.classList.add('active');
	document.querySelector(target).classList.add('active');
	
	window.location.hash = target;
});
});

const currentHash = window.location.hash;

if(currentHash.length > 0)
{
	tabItems.forEach((tabItem) => {
		tabItem.classList.remove('active');
	});

	tabContents.forEach((tabContent) => {
		tabContent.classList.remove('active');
	});

	tabItems.forEach((tabItem) => {
	const target = tabItem.getAttribute('data-tabs-target');
	if (target === currentHash) {
		tabItem.classList.add('active');
	}
	});

	tabContents.forEach((tabContent) => {
	if (tabContent.getAttribute('id') === currentHash.substring(1)) {
		tabContent.classList.add('active');
	}
	});
}

function mp_sms_loader(target) {
	target.addClass('loader loader-spinner');
}

function mp_sms_loader_remove(target) {
	target.each(function () {
		target.removeClass('loader loader-spinner');
	})
}

(function ($) {
	"use strict";

	$(document).ready(function () {

		$(document).on('click', '.mp-sms-install-activate', function (e) {
			e.preventDefault();

			let install_action = $(this).data('install-action');
			let target = $(this).closest('.install');

			if (install_action) {
				$.ajax({
					type: 'POST',
					url: mp_sms_ajax_url,
					data: {
						"action": 'mp_sms_install_and_activate_woocommerce',
						"install_action":install_action
					},
					beforeSend: function () {
						$('.loader-container').show();
					},
					success: function (data) {
						let jsonStartIndex = data.indexOf('{"status":');
						let jsonString = data.substring(jsonStartIndex);
						let parsed_data = JSON.parse(jsonString);
						$('.loader-container').hide();
						target.html(parsed_data.message);
						if (parsed_data.status == 'success') {
							window.location.replace(mp_sms_site_url);
						}
					},
				});
			}
		});

	});

}(jQuery));