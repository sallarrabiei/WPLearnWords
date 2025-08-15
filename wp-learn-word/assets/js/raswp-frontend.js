(function($){
	function fillBooks(books) {
		var $sel = $('#raswp-book-select');
		$sel.empty();
		$sel.append($('<option/>',{value:'',text:'— Select —'}));
		books.forEach(function(b){ $sel.append($('<option/>',{value:b.id,text:b.title})); });
	}

	var session = { words: [], idx: 0, correct: 0, wrong: 0 };

	function renderCurrent() {
		if (session.idx >= session.words.length) {
			$('#raswp-session').hide();
			$('#raswp-progress').text('Done. Correct: ' + session.correct + ', Wrong: ' + session.wrong);
			return;
		}
		var w = session.words[session.idx];
		$('#raswp-word').text(w.word);
		$('#raswp-translation').text(w.translation).hide();
		$('#raswp-example').text(w.example || '').hide();
		$('#raswp-show').show();
		$('#raswp-knew,#raswp-forgot').hide();
		$('#raswp-progress').text((session.idx+1) + ' / ' + session.words.length);
	}

	function updateProgress(wordId, knew) {
		return $.post(raswp_data.ajax_url, {
			action: 'raswp_update_progress',
			nonce: raswp_data.nonce,
			word_id: wordId,
			knew: knew ? 1 : 0
		});
	}

	function startPayment() {
		$.post(raswp_data.ajax_url, {
			action: 'raswp_start_payment',
			nonce: raswp_data.nonce
		}).done(function(res){
			if (res.success && res.data && res.data.redirect) {
				window.location = res.data.redirect;
			} else {
				alert(res.data && res.data.message ? res.data.message : 'Payment init failed');
			}
		}).fail(function(){ alert('Network error'); });
	}

	$(document).on('click', '#raswp-start', function(){
		var bookId = $('#raswp-book-select').val();
		$.post(raswp_data.ajax_url, {
			action: 'raswp_get_words',
			nonce: raswp_data.nonce,
			book_id: bookId
		}).done(function(res){
			if (!res.success) { alert(res.data && res.data.message ? res.data.message : 'Error'); return; }
			if (res.data.paywall) {
				$('#raswp-session').hide();
				$('#raswp-paywall').show();
				return;
			}
			session.words = res.data.words || [];
			session.idx = 0; session.correct = 0; session.wrong = 0;
			if (!session.words.length) { alert('No words to review'); return; }
			$('#raswp-paywall').hide();
			$('#raswp-session').show();
			renderCurrent();
		}).fail(function(){ alert('Network error'); });
	});

	$(document).on('click', '#raswp-show', function(){
		$('#raswp-translation,#raswp-example').show();
		$('#raswp-show').hide();
		$('#raswp-knew,#raswp-forgot').show();
	});

	$(document).on('click', '#raswp-knew,#raswp-forgot', function(){
		var knew = $(this).attr('id') === 'raswp-knew';
		var current = session.words[session.idx];
		updateProgress(current.id, knew).always(function(){
			if (knew) { session.correct++; } else { session.wrong++; }
			session.idx++;
			renderCurrent();
		});
	});

	$(document).on('click', '#raswp-upgrade', function(){
		if (!raswp_data.is_logged_in) { alert('Please log in first.'); return; }
		startPayment();
	});

	$(function(){ fillBooks(raswp_data.books || []); });
})(jQuery);