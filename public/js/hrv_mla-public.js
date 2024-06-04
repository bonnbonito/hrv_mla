window.addEventListener("DOMContentLoaded", (event) => {
	const questionWrapper = document.querySelectorAll('.question-wrapper');

	questionWrapper.forEach( question => {
		question.addEventListener('click', e => {
			e.preventDefault();
			if ( question.classList.contains('active') ) return;
			let q = question.getAttribute('id');
			let parent = question.closest('.faq-wrapper');
			
			parent.querySelectorAll('.question-wrapper').forEach( question => question.classList.remove('active') );
			parent.querySelectorAll('.answer-wrapper').forEach( answer => answer.classList.remove('active') );
			question.classList.add('active');
			document.querySelector('#' + q + 'c').classList.add('active');
		});
	})
});