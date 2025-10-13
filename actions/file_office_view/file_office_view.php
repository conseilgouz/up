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

class file_office_view extends upAction {

    function init() {
        return true;
    }

    function run() {


        // lien vers la page de demo
        $this->set_demopage();

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
        $options = $this->ctrl_options($options_def);

        // === CSS-HEAD
        $this->load_css_head($options['css-head']);
        
        // === le lien
        $file_url = $this->get_url_absolute($options[__class__], true);

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
        $iframe = $this->set_attr_tag('iframe', $attr_iframe, true);

        // attributs du bloc principal
        $attr_main = array();
        $attr_main['id'] = $options['id'];
        $this->get_attr_style($attr_main, $options['class'], $options['style'], $mainclass);

        // code en retour
        $html[] = $this->set_attr_tag('div', $attr_main, $iframe);

        return implode(PHP_EOL, $html);
    }

// run
}

// class
