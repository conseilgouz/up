<?php

/**
 * Ajoute un compteur avec prefix et suffix
 *
 * Syntaxe : {up counter=0,100}
 *
 * @author   LOMART
 * @version  UP-1.7
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit <a href="https://github.com/marcovincit/jquery-simple-counter" target="_blank">jquery-simple-counter de marcovincit</a>
 * @tags    Widget
 */
defined('_JEXEC') or die;

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class counter extends Lomart\Plugin\Content\Up\Extension\Up {

    function init() {
        // charger les ressources communes à toutes les instances de l'action
        UpHelper::load_file($this,'jQuerySimpleCounter.js');
        return true;
    }

    function run() {

        // lien vers la page de demo
        UpHelper::set_demopage($this);

        $options_def = array(
          __class__ => '', // min,max : valeurs de départ et de fin
          'width' => 0, // largeur minimal (par défaut en pixels)
          'mono' => 1, // force la police en monospace
          /* [st-annexe] style et options secondaires */
          'id' => '', // identifiant
          'class' => '', // classe(s) pour bloc
          'style' => '', // style inline pour bloc
          'css-head' => '', // règles CSS définies par le webmaster (ajout dans le head)
          'filter' => '', // conditions. Voir doc action filter  (v1.8)
        );

        // ===== paramétres attendus par le script JS
        // important: valeurs par défaut entre guillemets pour les chaines
        // attention: le nom des options est transmis au script sous la forme indiqué ici.
        $js_options_def = array(
        /* [st-JS] paramétres Javascript pour configuration du compteur */
          'start' => 0, // valeur de départ du compteur
          'end' => 100, // valeur d'arrivée du compteur
          'easing' => 'swing', // ou linear : effet
          'duration' => 1500, // durée du décompte en millisecondes
          'prefix' => '', // texte devant compteur. BBcode autorisé
          'suffix' => '' // texte après compteur. BBcode autorisé
        );

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def, $js_options_def);
        // === Filtrage
        if (UpHelper::filter_ok($this,$options['filter']) !== true) {
            return '';
        }

        $options['prefix'] = UpHelper::get_bbcode($this,$options['prefix']);
        $options['suffix'] = UpHelper::get_bbcode($this,$options['suffix']);

        // unité de min-width
        if ($options['width'])
            $options['width'] = UpHelper::ctrl_unit($this,$options['width'], 'px,em,rem');

        // =========== le code JS
        // ventilation valeurs start-end saisies dans option principale
        if (strpos($options[__class__], ',') != false) {
            list($this->options_user['start'], $this->options_user['end']) = explode(',', $options[__class__]);
        }

        // les options saisies par l'utilisateur concernant le script JS
        // cela évite de toutes les renvoyer au script JS
        $js_options = UpHelper::only_using_options($this,$js_options_def);
        // -- conversion en chaine Json
        // il existe 2 modes: mode1=normal, mode2=sans guillemets
        $js_params = UpHelper::json_arrtostr($this,$js_options, 2);

        // -- initialisation
        $js_code = '$("#' . $options['id'] . '").jQuerySimpleCounter(';
        $js_code .= $js_params;
        $js_code .= ');';
        UpHelper::load_jquery_code($this,$js_code);

        // === css-head
        UpHelper::load_css_head($this,$options['css-head']);

        // attributs du bloc principal
        $attr_main['id'] = $options['id'];
        if ($options['width'])
            $attr_main['style'] = 'display:inline-block;text-align:center;min-width:' . $options['width'];
        if ($options['mono'])
            $attr_main['class'] = 'ff-mono';
        UpHelper::get_attr_style($this,$attr_main, $options['class'], $options['style']);

        // code en retour
        $out = UpHelper::set_attr_tag($this,'span', $attr_main, true);

        return $out;
    }

// run
}

// class
