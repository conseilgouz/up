/* 
 * UP LOMART 6.0
 */
document.addEventListener('DOMContentLoaded', function() {
	var $up_id;
    ajax_buttons =  document.querySelectorAll('.ajax-view-btn');
	for (var t=0;t < ajax_buttons.length;t++ ) {
       
		['click', 'touchstart'].forEach(type => {
            ajax_buttons[t].addEventListener(type,function(e) {
                $up_id = this.getAttribute('data-id');
                var requete = 'action%3Dajax_view';
                requete += '%26content%3D' + this.getAttribute('data-content');
                requete += '%26type%3D' + this.getAttribute('data-type');
                requete += '%26html%3D' + this.getAttribute('data-html');
                requete += '%26eol%3D' + this.getAttribute('data-eol');
                if (this.getAttribute('data-md5')) {
                    requete += '%26pwd%3D' + prompt('mot de passe');
                    requete += '%26md5%3D' + this.getAttribute('data-md5');
                }
                url = '?option=com_ajax&group=content&plugin=up&format=raw&data='+requete;

                Joomla.request({
                    method : 'POST',
					url : url,
					onSuccess: function(data, xhr) {
                        if (data.slice(0, 3) != 'Err') {
                            document.querySelector('.' + $up_id + '.ajax-view-btn').style.display = "none";
                        }
                        document.querySelector('.' + $up_id + '.ajax-view-result').innerHTML = data;
                        document.querySelector('.' + $up_id + '.ajax-view-result').style.display = "block";
                    },
                    onError: function(message) {
                        console.log(message.responseText)
                        alert("Request failed: " + message.responseText);
                    }
                });
            })
        })
    }
});