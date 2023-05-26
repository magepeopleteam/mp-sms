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