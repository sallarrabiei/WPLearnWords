(function($){
	function fetchWords($root){
		var atts = RASWP.atts || {};
		return $.ajax({
			url: RASWP.ajax_url,
			type: 'GET',
			dataType: 'json',
			data: {
				action: 'raswp_get_session_words',
				nonce: RASWP.nonce,
				book: $root.data('book') || '',
				book_id: $root.data('bookId') || 0
			}
		});
	}

	function submitAnswer(wordId, isCorrect){
		return $.ajax({
			url: RASWP.ajax_url,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'raswp_submit_answer',
				nonce: RASWP.nonce,
				word_id: wordId,
				is_correct: isCorrect ? 1 : 0
			}
		});
	}

	function renderCard($root, words){
		var idx = 0;
		var $card = $root.find('.raswp-card');
		var $word = $card.find('.raswp-word');
		var $translation = $card.find('.raswp-translation');
		var $example = $card.find('.raswp-example');
		var $show = $card.find('.raswp-show');
		var $correct = $card.find('.raswp-correct');
		var $wrong = $card.find('.raswp-wrong');
		var $next = $card.find('.raswp-next');
		var $status = $card.find('.raswp-status');

		function showWord(){
			if (idx >= words.length){
				$word.text('');
				$translation.text('');
				$example.text('');
				$show.hide();
				$correct.hide();
				$wrong.hide();
				$next.hide();
				$status.text('');
				return;
			}
			var w = words[idx];
			$word.text(w.word);
			$translation.text(w.translation).hide();
			$example.text(w.example || '').hide();
			$show.show();
			$correct.hide();
			$wrong.hide();
			$next.hide();
			$status.text('(' + (idx+1) + '/' + words.length + ')');
		}

		$show.off('click').on('click', function(){
			$translation.show();
			$example.show();
			$show.hide();
			$correct.show();
			$wrong.show();
		});

		$correct.off('click').on('click', function(){
			submitAnswer(words[idx].id, true).always(function(){
				$correct.hide();
				$wrong.hide();
				$next.show();
			});
		});

		$wrong.off('click').on('click', function(){
			submitAnswer(words[idx].id, false).always(function(){
				$correct.hide();
				$wrong.hide();
				$next.show();
			});
		});

		$next.off('click').on('click', function(){
			idx += 1;
			showWord();
		});

		showWord();
	}

	$(function(){
		$('.raswp-leitner').each(function(){
			var $root = $(this);
			fetchWords($root).done(function(resp){
				if (!resp || !resp.success){
					if (resp && resp.data && resp.data.code === 'need_upgrade'){
						$root.find('.raswp-card').hide();
						$root.find('.raswp-upgrade').show().prepend('<div class="raswp-message">' + RASWP.i18n.upgrade_needed + '</div>');
					} else if (resp && resp.data && resp.data.code === 'not_logged_in'){
						$root.html('<div class="raswp-message">' + RASWP.i18n.login_needed + '</div>');
					}
					return;
				}
				var words = resp.data.words || [];
				if (!words.length){
					$root.find('.raswp-card').html('<div class="raswp-message">' + 'No words due now. Please come back later.' + '</div>');
					return;
				}
				renderCard($root, words);
			});
		});
	});
})(jQuery);