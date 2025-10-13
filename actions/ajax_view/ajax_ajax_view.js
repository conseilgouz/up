/* 
 * UP LOMART 2.9
 */
jQuery(function($) {
	var $up_id;
	$(".ajax-view-btn").click(function() {
		$up_id = this.getAttribute('data-id');
		var requete = 'action=ajax_view';
		requete += '&content=' + this.getAttribute('data-content');
		requete += '&type=' + this.getAttribute('data-type');
		requete += '&html=' + this.getAttribute('data-html');
		requete += '&eol=' + this.getAttribute('data-eol');
		if (this.getAttribute('data-md5')) {
			requete += '&pwd=' + prompt('mot de passe');
			requete += '&md5=' + this.getAttribute('data-md5');
		}
		// console.log('REQUETE: ', requete);
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
			// console.log(response.slice(0, 3), response);
			if (response.slice(0, 3) != 'Err') {
				$('.' + $up_id + '.ajax-view-btn').hide();
			}
			$('.' + $up_id + '.ajax-view-result').html(response);
			$('.' + $up_id + '.ajax-view-result').show();
		});
		req.fail(function(jqXHR, textStatus) {
			alert("Request failed: " + textStatus);
		});
	});
});