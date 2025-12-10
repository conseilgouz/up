<?php

/**
 * Affiche une série d'images défilantes en fond du site ou d'un bloc
 *
 * syntaxe :
 * fond site : {up bg-slideshow=liste images ou dossier}
 * fond bloc : {up bg-slideshow=liste images ou dossier}contenu{/up bg-slideshow}
 * fond autre bloc : {up bg-slideshow=liste images ou dossier | bg-selector=#foo}
 *
 * doc : https://developer.mozilla.org/fr/docs/Web/CSS/background
 *
 * @author   LOMART
 * @version  UP-1.5
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit  <a href="https://github.com/jaysalvat/vegas" target="_blank">Vegas de jaysalvat</a>
 * @tags    Body
 */
// doc : http://vegas.jaysalvat.com/documentation/settings/

/*
 * 3.1 - modif CSS vegas pour J4/flexgrid
 * 5.1 - fix shuffle
 */

/*
 * PSEUDO-CODE
 * 1/ selon cible, $selector = #id, body ou bg-selector
 * 2/ si mobile : css mobile dans $selector (head)
 * 3/ bg-overlay (sauf si mobile) dans head
 * 4/ page-overlay dans head
 * 5/ recupération liste des images (sauf si mobile)
 * 6/ init JS (sauf si mobile)
 * 7/ Retour HTML (si content)
 * - div.attr_main
 * - ___ div attr_center
 * - ______ div > content
 */
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Lomart\Plugin\Content\Up\Helper\UpHelper;

class bg_slideshow extends Lomart\Plugin\Content\Up\Extension\Up
{

    function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        UpHelper::load_upcss($this);
        UpHelper::load_file($this,'vegas/vegas.min.css');
        UpHelper::load_file($this,'vegas/vegas.min.js');
        return true;
    }

    function run()
    {

        // ---- lien vers la page de demo
        UpHelper::set_demopage($this);

        $options_def = array(
            /*images*/
            __class__ => '', // dossier ou images séparées par des points-virgules
            'mobile' => '', // image ou règle(s) css
            'path' => '', // chemin commun aux images indiquées en argument principal
            'shuffle' => 0, // tri aléatoire des images. 1=toutes, sinon le nombre d'images pour alléger le chargement
            /* [st-bloc]emplacement du fond image */
            'bg-selector' => 'body', // bloc portant le slideshow
            /* [st-bg]paramètres de l'image */
            'bg-overlay' => '', // calque superposé à l'image de fond (png, opacité, RGBA, CSS)
            'bg-color' => '', // couleur sous image
            /* [st-page]transparence du contenu de la page */
            'page-selector' => '', // bloc sur contenu
            'page-overlay' => '', // background de page-selector (png, opacité, RGBA, CSS)
            /* [st-annexe]options secondaires */
            'filter' => '', // conditions. Voir doc action filter (v1.8)
            'center' => 1, // centrage vertical du contenu entre shortcodes
            'id' => '', // identifiant
            'style' => '', // classes et styles inline pour bloc créé
            'css-head' => '', // règles CSS définies par le webmaster (ajout dans le head)
            'js-options' => '' // options non prévues par l'action
        );

        // ===== paramétres attendus par le script JS
        $js_options_def = array(
            /*[st-JS]paramétres de l'animation*/
            'delay' => 5000, // durée par image
            'transition' => 'fade', // random,blur,flash,negative,burn,slideLeft,slideRight,slideUp,slideDown,zoomIn,zoomOut,swirlLeft,swirlRight
            'transitionDuration' => 1000, // durée transition
            'animation' => '', // kenburns,kenburnsLeft,kenburnsUp,kenburnsDown,kenburnsUpLeft,kenburnsUpRight,kenburnsDownLeft,kenburnsDownRight,random
            'animationDuration' => 'auto', // durée des animations
            'timer' => 1 // affiche barre de progression
        );

        $transition_list = 'fade,random,blur,flash,negative,burn,slideLeft,slideRight,slideUp,slideDown,zoomIn,zoomOut,swirlLeft,swirlRight';
        $transition_list .= 'fade2,blur2,flash2,negative2,burn2,slideLeft2,slideRight2,slideUp2,slideDown2,zoomIn2,zoomOut2,swirlLeft2,swirlRight2';
        $animation_list = 'kenburns,kenburnsLeft,kenburnsUp,kenburnsDown,kenburnsUpLeft,kenburnsUpRight,kenburnsDownLeft,kenburnsDownRight,random';

        // --- fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def, $js_options_def);

        // === Filtrage
        if (UpHelper::filter_ok($this,$options['filter']) !== true) {
            return '';
        }

        $options['transition'] = UpHelper::ctrl_argument($this,$options['transition'], $transition_list);
        if ($options['animation'] != null) {
            $options['animation'] = UpHelper::ctrl_argument($this,$options['animation'], $animation_list);
        }
        // === Init variables
        $on_mobile = false;

        // === css-head
        UpHelper::load_css_head($this,$options['css-head']);

        // ========== localisation du fond
        $selector = ($this->content) ? '#' . $options['id'] : $options['bg-selector'];

        // ===== Alternative si mobile
        if ($options['mobile'] > '1') {
            $client = Factory::getApplication()->client;
            if ($client->mobile) {
                $on_mobile = true;
                $css_mobile = UpHelper::get_bg_mobile($this,$options);
                UpHelper::load_css_head($this,$selector . '{' . $css_mobile . '}');
            }
        }

        // =========== overlay sur slideshow
        if ($options['bg-overlay'] && ! $on_mobile) {
            UpHelper::add_str($this,$options['js-options'], 'overlay:true', ',');
            $val = UpHelper::get_overlay($this,$options['bg-overlay']);
            UpHelper::load_css_head($this,$selector . ' .vegas-overlay{background:' . $val . '}');
        }
        // =========== overlay sur page
        if ($options['page-overlay'] && $options['page-selector']) {
            $val = UpHelper::get_overlay($this,$options['page-overlay']);
            UpHelper::load_css_head($this,$options['page-selector'] . '{background:' . $val . '}');
        }

        // ====== Récupération de la liste des images
        if (! $on_mobile) {
            $option_images = $options[__class__];

            if (is_dir($options['path'] . $option_images)) {
                $is_dir = true;
                $folder = $options['path'] . $option_images;
                $pattern = $folder . '/*.{jpg,JPG,jpeg,JPEG,png,PNG,webp,WEBP}'; // v2.5 pascal
                $imgList = glob($pattern, GLOB_BRACE); // | GLOB_NOSORT
            } else {
                $is_dir = false;
                $imgList = array_map('trim', explode(';', $option_images));
            }
            foreach ($imgList as $img) {
                $imgsrc[] = UpHelper::get_slide_info($this,$img, $is_dir, $options['path']);
            }
            if ($options['shuffle']) { // v5.1 forcé car non pris en charge par le JS
                shuffle($imgsrc);
                if ($options['shuffle'] > 1 && $options['shuffle'] < sizeof($imgsrc))
                    $imgsrc = array_slice($imgsrc, 0, $options['shuffle']);
            }
            $slides = implode(',', $imgsrc);
        }

        // =========== le code JS
        // les options saisies par l'utilisateur concernant le script JS
        // cela évite de toutes les renvoyer au script JS
        if (! $on_mobile) {
            $js_options = UpHelper::only_using_options($this,$js_options_def);
            // -- conversion en chaine Json
            $js_params = UpHelper::json_arrtostr($this,$js_options, 2, false);
            // -- initialisation
            $js_code = '$("' . $selector . '").vegas({';
            $js_code .= 'slides: [' . $slides . ']';
            UpHelper::add_str($this,$js_code, $js_params, ',');
            if ($options['js-options'])
                UpHelper::add_str($this,$js_code, $options['js-options'], ',');
            $js_code .= '});';
            UpHelper::load_jquery_code($this,$js_code);
        }

        // ====== si contenu, on crée un bloc à la position du shortcode
        if ($this->content) {
            // attributs du bloc principal
            $attr_main['id'] = $options['id'];
            if ($options['bg-color']) {
                $attr_main['style'] = 'background-color:' . $options['bg-color'];
            }
            // === center
            if ($options['center']) {
                $attr_center['class'] = 'up-center';
            }
            UpHelper::get_attr_style($this,$attr_center, $options['style']);

            // -- code retour
            $html[] = UpHelper::set_attr_tag($this,'div', $attr_main);
            $html[] = UpHelper::set_attr_tag($this,'div', $attr_center);
            $html[] = '<div>';
            $html[] = $this->content;
            $html[] = '</div>';
            $html[] = '</div>';
            $html[] = '</div>';

            return implode(PHP_EOL, $html);
        } else {
            // sinon, uniquement le JS et CSS
            return '';
        }
    }

    // run

}

// class







