<?php

/**
 * Ajoute un bouton pour proposer l'impression d'une partie de la page
 *
 * syntaxe {up printer=texte bouton} le contenu à imprimer {/up printer}
 *
 * @author   LOMART
 * @version UP-1.8
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Editor
 */
/*
 * v2.8 - ajout règle css pour masquer le bouton lors appel externe à l'action
 * v5.2 - fix appel multiple. rewrite printer.js
 */
defined('_JEXEC') or die();

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class printer extends Lomart\Plugin\Content\Up\Extension\Up 
{

    function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        UpHelper::load_file($this,'printer.js');
        return true;
    }

    function run()
    {

        // lien vers la page de demo
        UpHelper::set_demopage($this);

        $options_def = array(
            __class__ => '&#x2399; Imprimer', // texte du bouton
            'selector' => '', // sélecteur CSS du bloc à imprimer
            /* [st-pos] Position du bouton */
            'btn-before' => 0, // le bouton est après le contenu à imprimer
            'btn-display-on-print' => 0, // 0 = masque le bouton sur l'impression
            /* [st-divers] Divers */
            'filename' => '', // nom du document si impression PDF
            'id' => '', // identifiant
            /* [st-css] Style CSS du bouton */
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);

        // si le selecteur CSS n'est pas indiqué, cette action doit avoir un contenu
        if ($options['selector'] == '') {
            if (! UpHelper::ctrl_content_exists($this)) {
                return false;
            }
            $options['selector'] = '#' . $options['id'];
        }

        // === CSS-HEAD
        UpHelper::load_css_head($this,$options['css-head']);

        // attributs du bloc principal
        $filename = ($options['filename']) ? ',\'' . $options['filename'] . '\'' : '';
        $attr_btn['onClick'] = 'pdf_print(\'' . $options['selector'] . '\'' . $filename . ')';
        $attr_btn['class'] = 'display-block';
        if (! $options['btn-display-on-print']) {
            $attr_btn['class'] .= ' no-print';
            // si appel de l'exterieur de l'article
            UpHelper::load_css_head($this,'@media print [.no-print[display:none;visibility:hidden]]');
        }
        UpHelper::get_attr_style($this,$attr_btn, $options['class'], $options['style']);

        // code en retour
        if ($options['btn-before'])
            $html[] = UpHelper::set_attr_tag($this,'button', $attr_btn, html_entity_decode($options[__class__]));
        $html[] = '<div id=\'' . $options['id'] . '\'>';
        $html[] = $this->content;
        $html[] = '</div>';
        if (! $options['btn-before'])
            $html[] = UpHelper::set_attr_tag($this,'button', $attr_btn, html_entity_decode($options[__class__]));
        return implode(PHP_EOL, $html);
    }

    // run
}

// class
