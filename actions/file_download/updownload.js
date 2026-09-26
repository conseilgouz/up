/* 
 * UP LOMART 6.1
 * from https://developers.google.com/web/updates/2011/08/Downloading-resources-in-HTML5-a-download
 */
document.addEventListener('DOMContentLoaded', function() {
var $file, $up_id;
    ajax_buttons =  document.querySelectorAll('.updownload');
	for (var t=0;t < ajax_buttons.length;t++ ) {
        ['click', 'touchstart'].forEach(type => {
            ajax_buttons[t].addEventListener(type,function(e) {
            $file = this.getAttribute('data-file');
            $up_id = this.getAttribute('data-up-id');
            var requete = 'file%3D' + this.getAttribute('data-file');
            requete += '%26upid%3D' + $up_id;
            if (md5 = this.getAttribute('md5')) {
                requete += '%26pwd%3D' + prompt('mot de passe');
                requete += '%26md5%3D' + this.getAttribute('md5');
            }
            url = '?option=com_ajax&group=content&plugin=up&format=raw&data='+requete;
            const token = Joomla.getOptions('csrf.token', '');
            var req = Joomla.request({
                type: 'POST',
                headers: { 'X-CSRF-Token': token },
                url: url,
                onSuccess: function(data, xhr) {
                    if (data.slice(0, 2) == 'ok') {
                        res = data.split(',');
                        var link = document.createElement('a'); // create a href
                        link.style.display = "none";
                        document.body.appendChild(link);
                        link.href = res[1]+res[2]+'/'+res[3]; 
                        link.download = res[3];
                        link.click();
                        document.body.removeChild(link);   // clean up
                        filestr = res[3].split('/').pop();
                        filestr = 'up-cls-' + filestr.replace('.', '-');
                // update hit and latest date
                        document.querySelector('#'+$up_id+' .up-tmpl-hits.'+filestr).innerHTML = res[4];
                        document.querySelector('#'+$up_id+' .up-tmpl-time.'+filestr).innerHTML = res[5];
                    } else {
                        alert('UP file-download : internal error');
                    }
                },
                onError: function(message) {
                    alert( "Request failed: " + message );
                }
            });
        });
        });
    }
});
