/* 
 * UP LOMART 6.1
 */
document.addEventListener('DOMContentLoaded', function() {
	var up_id;
    ajax_buttons =  document.querySelectorAll('.upfilescleaner-btn');
	for (var t=0;t < ajax_buttons.length;t++ ) {
        ['click', 'touchstart'].forEach(type => {
            ajax_buttons[t].addEventListener(type,function(e) {
                up_id = this.getAttribute('data-id');
                var requete = 'upid%3D' + up_id;
                url = '?option=com_ajax&group=content&plugin=up&format=raw&data='+requete;
                var token = Joomla.getOptions('csrf.token', '');
                var req = Joomla.request({
                    type: 'POST',
                    headers: { 'X-CSRF-Token': token },
                    url: url,
                    onSuccess: function(data, xhr) {
                        if (data.slice(0, 3) == 'Err') {
                            document.querySelector('#' + up_id + ' .upfilescleaner-result').style.color = 'red';
                            document.querySelector('#' + up_id + ' .upfilescleaner-result').style.fontSize = '150%';
                            document.querySelector('#' + up_id + ' .upfilescleaner-result').innerHTML = data;
                        }
                        document.querySelector('#' + up_id + ' .upfilescleaner-btn').style.display = 'none';
                        document.querySelector('#' + up_id + ' .upfilescleaner-warning').style.display = 'none';
                        document.querySelector('#' + up_id + ' .upfilescleaner-result').style.display = 'block';
                    },
                    onError: function(message) {
                        alert("Request failed: " + message);
                    }
                });
            });
        });
    }
});