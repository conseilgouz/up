/* 
 * UP LOMART 2019-12
 * from https://developers.google.com/web/updates/2011/08/Downloading-resources-in-HTML5-a-download
 */
jQuery(function ($) {
var $file, $up_id;
    $(".updownload").click(function () {
	$file = this.getAttribute('data-file');
	$up_id = this.getAttribute('data-up-id');
        var requete = 'action=file_download&file=' + this.getAttribute('data-file');
        if (md5 = this.getAttribute('md5')) {
            requete += '&pwd=' + prompt('mot de passe');
            requete += '&md5=' + this.getAttribute('md5');
            // console.log(requete);
		}
		request = {
			'option' : 'com_ajax',
			'group' : 'content',
			'plugin' : 'up',
			'data'   :  requete,
			'format' : 'raw'
		};
        var req = $.ajax({
            type: 'POST',
			data: request});
		req.done(function (response) {
            if (response.slice(0, 2) == 'ok') {
                res = response.split(',');
                var filename = $file.split('/').pop(); // file name
                var link = document.createElement('a'); // create a href
                link.style.display = "none";
                document.body.appendChild(link);
                link.href = res[1]+res[2]+'/'+$file; 
                link.download = filename;
                link.click();
                document.body.removeChild(link);   // clean up
                filestr = res[3];
                // update hit and latest date
                $('#'+$up_id+' .up-tmpl-hits.'+filestr).html(res[4]);
                $('#'+$up_id+' .up-tmpl-time.'+filestr).html(res[5]);
            } else {
                alert('UP file-download : internal error');
			}
        });
		req.fail(function (jqXHR, textStatus) {
                alert( "Request failed: " + textStatus );
        });
    });
});
