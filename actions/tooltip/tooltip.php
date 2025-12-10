<?php

/**
 * des info-bulles au survol d'un élément
 *
 * syntaxe {up tooltip=texte info-bulle}texte{/up tooltip}
 *
 * @author   LOMART
 * @version  UP-1.9.5
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit  <a href="https://github.com/stefangabos/Zebra_Tooltips" target="_blank">sur un script de stefangabos</a>
 * @tags    Editor
 */
defined('_JEXEC') or die;

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class tooltip extends Lomart\Plugin\Content\Up\Extension\Up {

    function init() {
        // charger les ressources communes à toutes les instances de l'action
        UpHelper::load_file($this,'style/up-model.css');
        UpHelper::load_file($this,'zebra_tooltips.min.js');
        UpHelper::load_file($this,'init.js');
        return true;
    }

    function run() {

        // si cette action a obligatoirement du contenu
        if (!UpHelper::ctrl_content_exists($this)) {
            return false;
        }

        // lien vers la page de demo
        // - vide = page sur le site de UP
        // - URL complete = page disponible sur ce lien
        // - rien pour ne pas proposer d'aide
        UpHelper::set_demopage($this);

        $options_def = array(
            __class__ => '', // texte de la bulle. bbcode permis
            /* [st-pos] style et position de la bulle */
            'model' => '', // une des classes principales définies dans up-model.scss
            'position' => 'center', // left, right
            'bottom' => 0, // vrai pour afficher la bulle au-dessous
            'width' => '250', // largeur maxi de l'info-bulle
            'offset' => 0, // décalage vertical de info-bulle. Négatif=vers le haut
            'opacity' => '95', // transparence de l'info-bulle
            'open' => 0, // info-bulle affichée au chargement de la page
            /* [st-css] Style CSS pour l'élément déclencheur */
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '' // règles CSS définies par le webmaster (ajout dans le head)
        );

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);
        $txt = UpHelper::get_bbcode($this,$options[__class__]);
        $txt = str_replace('<', '&lt;', $txt);
        $txt = str_replace('>', '&gt;', $txt);
        $txt = str_replace('"', '&quot;', $txt);


        // === css-head
        UpHelper::load_css_head($this,$options['css-head']);

        // attributs du bloc principal
        $attr_main['id'] = $options['id'];
        $attr_main['href'] = 'javascript: void(0)';
        $attr_main['class'] = 'Zebra_Tooltips';
        $attr_main['class'] .= ($options['open']) ? '_open' : '';
        UpHelper::get_attr_style($this,$attr_main, $options['class'], $options['style']);

        $attr_main['data-ztt_content'] = $txt;
        // -- largeur maxi
        $mw = (int) $options['width'];
        if ($mw > 0 && $mw != 250)
            $attr_main['data-ztt_max_width'] = (string) $mw;
        // -- transparence
        $opacity = (int) $options['opacity'];
        if ($opacity > 0 && $opacity <= 100 && $opacity != 95)
            $attr_main['data-ztt_opacity'] = (string) ($opacity / 100);
        // -- position latérale ('center', 'left' or 'right')
        $position = UpHelper::ctrl_argument($this,$options['position'], 'center,left,right');
        if ($position != 'center')
            $attr_main['data-ztt_position'] = $position;
        // -- position verticale (below or above)
        if ($options['bottom'])
            $attr_main['data-ztt_vertical_alignment'] = 'below';
        // -- décalage vertical
        $offset = (int) $options['offset'];
        if ($offset)
            $attr_main['data-ztt_vertical_offset'] = (string) $offset;
        // -- classe(s) personnalisation
        if ($options['model'])
            $attr_main['data-ztt_class'] = $options['model'];

        // code en retour
        $out = UpHelper::set_attr_tag($this,'a', $attr_main, $this->content);

        return $out;
    }

// run
}

// class
