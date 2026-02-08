/**
 * @package  UP Content plugin
 * @version  UP-6.0
 * @author   Lomart
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    LOMART
 **/

document.addEventListener("DOMContentLoaded", function(){
    
    let compil = document.querySelector('#compile');
    let need_compile = false;
    let s = document.querySelector('#jform_params_breaks');
    let m = document.querySelector('#jform_params_breakm');
    let sl = document.querySelector('#jform_params_breaksl');
    let l = document.querySelector('#jform_params_breakl');
    let xl = document.querySelector('#jform_params_breakxl');
    
    s.addEventListener('input',function() {
        compil.style.display ='block';
        need_compile = true;
    })
    m.addEventListener('input',function() {
        compil.style.display ='block';
        need_compile = true;
    })
    sl.addEventListener('input',function() {
        compil.style.display ='block';
        need_compile = true;
    })
    l.addEventListener('input',function() {
        compil.style.display ='block';
        need_compile = true;
    })
    xl.addEventListener('input',function() {
        compil.style.display ='block';
        need_compile = true;
    })
    // compile SCSS button
    if (!compil) return; // not defined : ignore
    compil.addEventListener('click',function() {
        compil_scss();
    })
})    
function compil_scss() {    
    let s = document.querySelector('#jform_params_breaks');
    let m = document.querySelector('#jform_params_breakm');
    let sl = document.querySelector('#jform_params_breaksl');
    let l = document.querySelector('#jform_params_breakl');
    let xl = document.querySelector('#jform_params_breakxl');

    let compil = document.querySelector('#compile');
    compil.setAttribute("disabled",true);
    let box = document.createElement('div');
    let systemmsg = document.querySelector('#compile_message');
    box.innerHTML = '<joomla-alert type="warning" role="alert" style="animation-name: joomla-alert-fade-in;"><div class="alert-heading"><span class="visually-hidden">info</span></div><div class="alert-wrapper"><div class="alert-message"><p>Compilation SCSS....</p><p style="text-align: center;margin-left: 10em;"><span class="switching"></span></div></div></joomla-alert>';
    systemmsg.appendChild(box);
	var csrf = Joomla.getOptions("csrf.token", "");
    var vals = s.value;
    var valsl = sl.value;
    var valm = m.value;
    var vall = l.value;
    var valxl= xl.value;
	var url = "?"+csrf+"=1&option=com_ajax&group=content&plugin=up&data=compil&s="+vals+"&sl="+valsl+"&m="+valm+"&l="+vall+"&xl="+valxl+"&format=raw";
	Joomla.request({
		method : 'POST',
		url : url,
		onSuccess: function(data, xhr) {
            try{
                result = JSON.parse(data);
            } catch(err) {
                result = 1; // receiving notices, suppose ok
            }
            if (!result) {
                window.alert(data);
            }
            Joomla.submitbutton('plugin.apply');
            compil.removeAttribute("disabled");
            systemmsg.removeChild(box);
		},
		onError: function(message) {
            console.log(message.responseText);
            compil.removeAttribute("disabled");
            systemmsg.removeChild(box);
        }
	}) 
}
