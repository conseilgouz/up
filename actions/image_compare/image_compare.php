<?php

/**
 * comparaison de 2 images par déplacement d'un volet
 *
 * {up imagecompare}
 * < img src="avant.jpg" >
 * < img src="apres.jpg" >
 * {/up imagecompare}
 *
 * @author lomart
 * @version UP-0.9
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit  script de <a href="https://github.com/sylvaincombes/jquery-images-compare" target="_blank">Sylvain Combes</a>
 * @tags    image
 */
defined('_JEXEC') or die();

use Joomla\CMS\HTML\HTMLHelper;
use Lomart\Plugin\Content\Up\Helper\UpHelper;

class image_compare extends Lomart\Plugin\Content\Up\Extension\Up
{

    function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        UpHelper::load_file($this,'images-compare.css');
        UpHelper::load_file($this,'jquery.images-compare.min.js');
        HTMLHelper::script('https://cdnjs.cloudflare.com/ajax/libs/hammer.js/2.0.8/hammer.min.js');
        return true;
    }

    function run()
    {

        // cette action a obligatoirement du contenu
        if (! UpHelper::ctrl_content_exists($this)) {
            return false;
        }
        // lien vers la page de demo (vide=page sur le site de UP)
        UpHelper::set_demopage($this);

        // ===== valeur paramétres par défaut
        // il est indispensable de tous les définir ici
        $options_def = array(
            __class__ => '', // aucun argument nécessaire
            /* [st-css] Style CSS*/
            'id' => '', // Identifiant
            'class' => '', // classe(s) pour le bloc
            'style' => '' // style inline pour le bloc
        );

        $js_options_def = array(
        /* [st-JS] paramètres Javascript pour la configuration */
            'initVisibleRatio' => 0.2, // position initiale
            'interactionMode' => 'drag', // mode: drag, mousemove, click
            'addSeparator' => 1, // ajoute séparateur (ligne verticale)
            'addDragHandle' => 1, // ajoute poignée sur séparateur
            'animationDuration' => 450, // durée animation en ms
            'animationEasing' => 'linear', // animation: linear, swing
            'precision' => 2 // précision rapport, nb decimales
        );

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def, $js_options_def);

        $regeximg = '#<img .*>#U';
        preg_match_all($regeximg, $this->content, $img);

        // =========== le code JS
        // les options saisis par l'utilisateur concernant le script JS
        $js_options = UpHelper::only_using_options($this,$js_options_def);

        // conversion params JS en chaine JSON
        $js_params = '';
        if (isset($js_options)) {
            $js_params = json_encode($js_options, JSON_UNESCAPED_SLASHES);
        }

        // -- initialisation
        $js_code = '$("#' . $options['id'] . '").imagesCompare(';
        $js_code .= $js_params;
        $js_code .= ');';
        UpHelper::load_jquery_code($this,$js_code);

        // === le code HTML
        // -- ajout options utilisateur dans la div principale
        $outer_div['id'] = $options['id'];
        $outer_div['class'] = $options['class'];
        $outer_div['style'] = $options['style'];

        // -- le code en retour
        $out = UpHelper::set_attr_tag($this,'div', $outer_div);
        $out .= '<div style="display: none;">';
        $out .= $img[0][0];
        $out .= '</div>';
        $out .= '<div>';
        $out .= $img[0][1];
        $out .= '</div>';
        $out .= '</div>';

        return $out;
    }

    // run
}

// class
