/* 
 * UP LOMART 5.2
 */
jQuery(function($) {
	var up_id;
	$(".upfilescleaner-btn").click(function() {
		up_id = this.getAttribute('data-id');
		var requete = 'action=upfilescleaner';
		requete += '&backup=' + this.getAttribute('data-backup');
		requete += '&folder-purge=' + this.getAttribute('data-folder-purge');
		
		request = {
			'option': 'com_ajax',
			'group': 'content',
			'plugin': 'up',
			'data': requete,
			'format': 'raw'
		};
		var req = $.ajax({
			type: 'POST',
			data: request
		});
		req.done(function(response) {
			if (response.slice(0, 3) == 'Err') {
				$('#' + up_id + ' .upfilescleaner-result').css({'color':'red','font-size':'150%'});
				$('#' + up_id + ' .upfilescleaner-result').html(response);
			}
			$('#' + up_id + ' .upfilescleaner-btn').hide();
			$('#' + up_id + ' .upfilescleaner-warning').hide();
			$('#' + up_id + ' .upfilescleaner-result').show();
		});
		req.fail(function(jqXHR, textStatus) {
			alert("Request failed: " + textStatus);
		});
	});
});