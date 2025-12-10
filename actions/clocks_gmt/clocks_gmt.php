<?php

/**
 * Affiche une horloge analogique et/ou digitale avec l'heure d'un fuseau horaire
 *
 * syntaxe {up clocks-gmt=ville | offset=décalage horaire}
 *
 * @author   LOMART
 * @version  UP-1.4
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="https://github.com/mcmastermind/jClocksGMT" target"_blank">script jClocksGMT de mcmastermind</a>
 * @tags Widget
 */
defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;
use Lomart\Plugin\Content\Up\Helper\UpHelper;

class clocks_gmt extends Lomart\Plugin\Content\Up\Extension\Up {

    function init() {
        UpHelper::load_file($this,'jClocksGMT.fr.css');
        UpHelper::load_file($this,'jClocksGMT.fr.js');
        UpHelper::load_file($this,'jquery.rotate.js');
        return true;
    }

    /**
     * analyse et interprete le shortcode
     * @return [string] [code HTML pour remplacer le shortcode]
     */
    function run() {

        // lien vers la page de demo
        UpHelper::set_demopage($this);

        // ===== valeur paramétres par défaut (sauf JS)
        $options_def = array(
            __class__ => '', // Nom de la ville (pour légende)
            'label-style' => '', // style inline pour le nom ville
            'digital-style' => '', // style inline pour horloge digitale
            'date-style' => '', // style inline pour date
            'base-js-params' => '', // règles JS définies par le webmaster (ajout dans init JS)
            /* [st-annexe] style général */
            'id' => '', // id genérée automatiquement par UP
            'class' => '', // classe(s) ajoutées au bloc principal
            'style' => '', // style inline ajouté au bloc principal
            'css-head' => '', // autres règles CSS définies par le webmaster (ajout dans le head)
        );

        // ===== paramétres attendus par le script JS
        // important: valeurs par défaut entre guillemets pour les chaines
        // attention: le nom des options est transmis au script sous la forme indiqué ici.
        $js_options_def = array(
            /* [st-JS] Paramétres Javascript pour l'horloge */
            'offset' => '', // GMT offset
            'title' => 'Greenwich, England', // titre
            'skin' => 1, // indice images (voir la démo)
            'dst' => 1, // gestion heure d'été
            'analog' => 1, // afficher horloge analogique
            'digital' => 1, // afficher horloge digitale
            'timeformat' => 'HH:mm', // format pour la date
            'date' => 0, // afficher la date
            'dateformat' => 'MM/DD/YYYY', // format pour l'heure
            'imgpath' => '' // chemin vers les images
        );

        // forcer offset 0 (v2.3 merci smlcol)
        if (isset($this->options_user['offset']) && $this->options_user['offset'] == '')
            $this->options_user['offset'] = '0';
        if ($this->options_user[__class__] != 1) {
            $this->options_user['title'] = $this->options_user[__class__];
        } else {
            $this->options_user['title'] = '';
        }
        $this->options_user['imgpath'] = Uri::root() . $this->actionPath;

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def, $js_options_def);

        // =========== le code JS
        // les options saisies par l'utilisateur concernant le script JS
        // cela évite de toutes les renvoyer au script JS
        $js_options = UpHelper::only_using_options($this,$js_options_def);

        // -- conversion en chaine Json
        // il existe 2 modes: mode1=normal, mode2=sans guillemets
        $js_params = UpHelper::json_arrtostr($this,$js_options);
        if ($options['base-js-params']) {
            $js_params = str_replace('{', '{' . $options['base-js-params'] . ',', $js_params);
        }
        $id = $options['id'];

        // === css-head
        UpHelper::load_css_head($this,$options['css-head']);

        // -- initialisation
        $js_code = '$("#' . $options['id'] . '").jClocksGMT(';
        $js_code .= $js_params;
        $js_code .= ');';
        UpHelper::load_jquery_code($this,$js_code);

        // === les règles CSS spécifiques
        if ($options['label-style'])
            UpHelper::load_css_head($this,'#' . $options['id'] . ' .jcgmt-lbl{' . $options['label-style'] . '}');
        if ($options['digital-style'])
            UpHelper::load_css_head($this,'#' . $options['id'] . ' .jcgmt-digital{' . $options['digital-style'] . '}');
        if ($options['date-style'])
            UpHelper::load_css_head($this,'#' . $options['id'] . ' .jcgmt-date{' . $options['date-style'] . '}');

        // === le code HTML
        // -- ajout options utilisateur dans la div principale
        $attr_main['id'] = $options['id'];
        $attr_main['class'] = 'clock_container';
        UpHelper::add_class($this,$attr_main['class'], $options['class']);
        $attr_main['style'] = $options['style'];

        // -- le code en retour
        return UpHelper::set_attr_tag($this,'div', $attr_main, true);
    }

// run
}

// class

