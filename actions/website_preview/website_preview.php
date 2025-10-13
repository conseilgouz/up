<?php

/**
 * Affiche une prévisualisation du site au survol d'un lien
 *
 * syntaxe {up website-preview=CSS_selecteur}
 *
 * @author  LOMART
 * @version  UP-2.7
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="https://www.jqueryscript.net/other/jQuery-Plugin-To-Create-A-Live-Preview-Of-A-URL-Mini-Preview.html" target"_blank">script jQuery Mini Preview de lonekorean</a>
 * @tags    Widget
 *
 */
defined('_JEXEC') or die;

class website_preview extends upAction {

    function init() {
        // charger les ressources communes à toutes les instances de l'action
        $this->load_file('jquery.minipreview.css');
        $this->load_file('jquery.minipreview.js');
        return true;
    }

    function run() {


        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '.com-content-article__body a', // sélecteur CSS des liens à afficher
            'mode' => 'parenthover', // mode chargement preview : parenthover, none, pageload, 
            'width' => '256', // largeur preview (px)
            'height' => '144', // hauteur preview
            'scale' => '.25',  // Echelle entre 0.1 et 1
        );

        // -- fusion et controle des options
        $options = $this->ctrl_options($options_def);

		if ($options['mode']!='pageload')
			$js_options['prefetch'] = $options['mode'];
		if ($options['width']!='256')
			$js_options['width'] = (int) $options['width'];
		if ($options['height']!='144')
			$js_options['height'] = (int) $options['height'];
		if ($options['scale']!='256')
			$js_options['scale'] = strval($options['scale']);

        // -- conversion en chaine Json
        $js_params = $this->json_arrtostr($js_options);

        // -- initialisation
        $js_code = '$("' . $options[__class__] . '").miniPreview(';
        $js_code .= $js_params;
        $js_code .= ');';
        $this->load_jquery_code($js_code);


        // aucun code HTML en retour
        return '';
    }

// run
}

// class
