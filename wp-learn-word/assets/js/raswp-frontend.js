(function($){
	function fillBooks(books) {
		var $sel = $('#raswp-book-select');
		$sel.empty();
		$sel.append($('<option/>',{value:'',text:'— انتخاب —'}));
		books.forEach(function(b){ $sel.append($('<option/>',{value:b.id,text:b.title})); });
	}

	function fillPlans(plans){
		var $sel = $('#raswp-plan-select');
		if (!$sel.length) return;
		$sel.empty();
		if (!plans || !plans.length) {
			$sel.append($('<option/>',{value:'',text:'—'}));
			return;
		}
		plans.forEach(function(p){
			var label = p.name + ' - ' + (p.amount||0).toLocaleString('fa-IR') + ' ' + 'ریال' + (p.duration_days ? (' / ' + p.duration_days + ' روز') : '');
			$sel.append($('<option/>',{value:p.id,text:label}));
		});
	}

	var session = { words: [], idx: 0, correct: 0, wrong: 0 };

	function renderCurrent() {
		if (session.idx >= session.words.length) {
			$('#raswp-session').hide();
			$('#raswp-progress').text('تمام شد. درست: ' + session.correct + '، نادرست: ' + session.wrong);
			return;
		}
		var w = session.words[session.idx];
		$('#raswp-word').text(w.word);
		$('#raswp-translation').text(w.translation).hide();
		$('#raswp-example1').text(w.example1 || '').hide();
		$('#raswp-example2').text(w.example2 || '').hide();
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

	function startPayment(planId) {
		var payload = { action: 'raswp_start_payment', nonce: raswp_data.nonce };
		if (planId) { payload.plan_id = planId; }
		$.post(raswp_data.ajax_url, payload)
			.done(function(res){
				if (res.success && res.data && res.data.redirect) {
					window.location = res.data.redirect;
				} else {
					alert(res.data && res.data.message ? res.data.message : 'شروع پرداخت ناموفق بود');
				}
			}).fail(function(){ alert('خطای شبکه'); });
	}

	$(document).on('click', '#raswp-start', function(){
		var bookId = $('#raswp-book-select').val();
		$.post(raswp_data.ajax_url, {
			action: 'raswp_get_words',
			nonce: raswp_data.nonce,
			book_id: bookId
		}).done(function(res){
			if (!res.success) { alert(res.data && res.data.message ? res.data.message : 'خطا'); return; }
			if (res.data.paywall) {
				$('#raswp-session').hide();
				$('#raswp-paywall').show();
				return;
			}
			session.words = res.data.words || [];
			session.idx = 0; session.correct = 0; session.wrong = 0;
			if (!session.words.length) { alert('واژه‌ای برای مرور نیست'); return; }
			$('#raswp-paywall').hide();
			$('#raswp-session').show();
			renderCurrent();
		}).fail(function(){ alert('خطای شبکه'); });
	});

	$(document).on('click', '#raswp-show', function(){
		$('#raswp-translation,#raswp-example1,#raswp-example2').show();
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
		if (!raswp_data.is_logged_in) { alert('لطفاً ابتدا وارد شوید.'); return; }
		var planId = $('#raswp-plan-select').val();
		startPayment(planId);
	});

	$(function(){ fillBooks(raswp_data.books || []); fillPlans(raswp_data.plans || []); });
})(jQuery);