var MPPOLL =  {

	Seeresults : function (el) {
		var url = 'index.php?route=extension/mppoll/mppoll';
		var id = $(el).attr('data-id');
		if(id != '' && id != 'undefined' && typeof id != 'undefined') {
			url += '&mppoll_id='  + id;
		}	
		location = url;
	},
	Addvote : function (el) {

		var valid = true;
		var module = $(el).attr('data-module');
		var id = $(el).attr('data-id');

		// remove specific poll alert only.
		$('#mppoll' + '-' + module + '-' + id).find('.alert').remove();


		var answer_id = $('#vote' + '-' + module + '-' + id).find('input[type="radio"]:checked').val();

		// check if any option is selected or not. if not raise an error.
		if(answer_id == 0 || answer_id == '' || answer_id == 'undefined' || typeof answer_id == 'undefined') {
			$(el).parent().before('<div class="alert alert-danger"><i class="fa fa-check-circle"></i> ' + $(el).attr('data-error') + ' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
			valid = false;
		}

		if(valid) {
			$(el).attr('disabled','disabled');
			$.ajax({
				url: 'index.php?route=extension/mppoll/module/mppoll|addPollHasVote',
				type: 'post',
				data: 'answer_id=' + answer_id+ '&id=' + id ,
				dataType: 'json',
				beforeSend: function() {
					$(el).after('<i class="fa fa-spin"></i>');
				},
				complete: function() {
					$('#mppoll' + '-' + module + '-' + id).find('.fa').remove();
				},
				success: function(json) {
					$('#mppoll' + '-' + module + '-' + id).find('.alert').remove();
					if(typeof json['error'] != 'undefined') {

						$(el).parent().before('<div class="alert alert-danger"><i class="fa fa-check-circle"></i> ' + json['error'] + ' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
						$(el).removeAttr('disabled');

					}

					if(typeof json['success'] != 'undefined') {
						$(el).parent().before('<div class="alert alert-success"><i class="fa fa-check-circle"></i> ' + json['success'] + ' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
						$(el).remove();
						// possiblities that one poll is showing twice as a module.
						// hence find them and remove vote button from them as well.
						// remove another associated vote button with same poll id

						$('.my-mppolls').each(function(){
							var $this = $(this);
							var myid = $this.attr('id');
							var idpart = myid.split('-');
							if(idpart.length == 3) {
							if(idpart[2] == id) {
								$this.find('.dovote').remove();
							}
							}
						});



					}
				},
				error: function(xhr, ajaxOptions, thrownError) {
					console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
				}
			});

		}
	},
	Removevoteresult : function (el) {
		var id = $(el).attr('data-id');
		if($('#mppoll-result-' + id).length) {
			$('#mppoll-result-' + id).fadeOut(1000,"swing",function() {
				$('#mppoll-' + id).find('.mppoll-result').remove();
			});
		}
	},
	Seevoteresult : function (el) {

		var valid = true;
		var id = $(el).attr('data-id');

		// remove specific poll alert only.
		$('.alert').remove();


		
		/*if($('#mppoll-result-' + id).length) {
			$('#mppoll-result-' + id).slideUp(500,'swing',function() {
				
			});
		}*/
		$('#mppoll-' + id).find('.mppoll-result').remove();

		// check if we got valid id. if not raise an error.
		if(id == 0 || id == '' || id == 'undefined' || typeof id == 'undefined') {
			$('#mppolls').before('<div class="alert alert-danger"><i class="fa fa-check-circle"></i> ' + $(el).attr('data-error') + ' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
			valid = false;
		}

		if(valid) {
			$(el).attr('disabled','disabled');
			$.ajax({
				url: 'index.php?route=extension/mppoll/mppoll|viewResult',
				type: 'post',
				data: 'id=' + id ,
				dataType: 'json',
				beforeSend: function() {
					$(el).after('<i class="fa fa-spin"></i>');
				},
				complete: function() {
					$('#mppoll' + '-' + id).find('.fa').remove();
				},
				success: function(json) {
					$('.alert').remove();
					if(typeof json['error'] != 'undefined') {
						$('#mppolls').before('<div class="alert alert-danger"><i class="fa fa-check-circle"></i> ' + json['error'] + ' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
						$(el).removeAttr('disabled');

					}

					if(typeof json['success'] != 'undefined') {
						$('#mppoll-' + id).append(json['mppoll_result']);
						$(el).removeAttr('disabled');

						$('#mppoll-result-' + id).fadeIn(1000);
					}

					// if both are undefined. Show custom message
					if(typeof json['success'] == 'undefined' && typeof json['error'] == 'undefined') {
						$('#mppolls').before('<div class="alert alert-danger"><i class="fa fa-check-circle"></i> ' + $(el).attr('data-error') + ' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
					}
				},
				error: function(xhr, ajaxOptions, thrownError) {
					console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
				}
			});

		}
	},
}

$(document).ready(function(){
	var mppoll_id = $('#mppoll_id').attr('data-id');
	if(mppoll_id != 0) {
		$('.voteresults').each(function() {
			if( $(this).attr('data-id') == mppoll_id ) {
				$(this).trigger('click');
			}
		});
	} else {
		$('.voteresults').each(function() {			
			$(this).trigger('click');
			return false;
		});
	}
});