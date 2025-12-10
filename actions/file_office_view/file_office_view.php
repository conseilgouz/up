<?php

/**
 * Affiche un fichier MS Office (word, excel, powerpoint)
 *
 * syntaxe {up file_office_view=nom_fichier}
 *
 * @version  UP-2.9
 * @author   Lomart
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="" target"_blank">script xxx de xxx</a>
 * @tags    Widget
 *
 */
defined('_JEXEC') or die;

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class file_office_view extends Lomart\Plugin\Content\Up\Extension\Up {

    function init() {
        return true;
    }

    function run() {


        // lien vers la page de demo
        UpHelper::set_demopage($this);

        $options_def = array(
            __class__ => '', // nom du fichier
            'mode' => 'office-embed', // ou office-view, google-docs, google-drive
            'width' => '100%', // largeur du bloc conteneur
            'height' => '50vh', // hauteur du bloc conteneur
            /* [st-annexe] style et options secondaires */
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);

        // === CSS-HEAD
        UpHelper::load_css_head($this,$options['css-head']);
        
        // === le lien
        $file_url = UpHelper::get_url_absolute($this,$options[__class__], true);

		$mainclass= '';
		switch ($options['mode']){
			case 'google-drive' :
				$url = 'https://drive.google.com/viewer?embedded=true&url='.$file_url;
				break;
			case 'google-docs' :
				$url = 'https://docs.google.com/viewer?embedded=true&url='.$file_url;
				break;
			case 'office-view' :
				$url = 'https://view.officeapps.live.com/op/view.aspx?src='.$file_url;
				break;
			default :
				$url = 'https://view.officeapps.live.com/op/embed.aspx?src='.$file_url;
				$mainclass= 'embedoffice';
		}
        
        // ---
        $attr_iframe = array();
        $attr_iframe['src'] =$url;
        $attr_iframe['style'] = 'width:'.$options['width'].';height:'.$options['height'];
        $attr_iframe['frameborder'] ='0';
        $iframe = UpHelper::set_attr_tag($this,'iframe', $attr_iframe, true);

        // attributs du bloc principal
        $attr_main = array();
        $attr_main['id'] = $options['id'];
        UpHelper::get_attr_style($this,$attr_main, $options['class'], $options['style'], $mainclass);

        // code en retour
        $html[] = UpHelper::set_attr_tag($this,'div', $attr_main, $iframe);

        return implode(PHP_EOL, $html);
    }

// run
}

// class
