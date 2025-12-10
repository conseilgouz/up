<?php

/**
 * Joue un son au survol d'un élément ou d'un événement JS
 *
 * syntaxe {up sound_hover=fichier_son }image{/up sound_hover}
 *
 * @author   LOMART
 * @version  UP-1.1
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="https://github.com/admsev/jquery-play-sound" target"_blank">adaptation du script playSound de Alexander Manzyuk</a>
 * @tags   Media
 */
defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;
use Lomart\Plugin\Content\Up\Helper\UpHelper;

class sound_hover extends Lomart\Plugin\Content\Up\Extension\Up {

    /**
     * charger les ressources communes à toutes les instances de l'action
     * cette fonction n'est lancée qu'une fois pour la première instance
     * @return true
     */
    function init() {
        UpHelper::load_file($this,'jquery.playSound.js');
        return true;
    }

    /**
     * analyse et interprete le shortcode
     * @return [string] [code HTML pour remplacer le shortcode]
     */
    function run() {

        // si cette action a obligatoirement du contenu
        if (!UpHelper::ctrl_content_exists($this)) {
            return false;
        }

        // lien vers la page de demo
        UpHelper::set_demopage($this);

        // ===== valeur paramétres par défaut (sauf JS)
        $options_def = array(
          __class__ => '', // fichier_son
          'evenement' => 'onmouseenter', // ou onclick, onmouseover...
          /* [st-css] Style CSS*/
          'tag' => 'div', // balise pour le bloc. span pour un bloc inline
          'id' => '', // id genérée automatiquement par UP
          'class' => '', // classe(s) pour le bloc parent
          'style' => '', // style pour le bloc parent
        );

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);

        // === le code HTML
        // -- ajout options utilisateur dans la div principale
        $son = $options[__class__];
        if (strpos($son, '://') == 0) {
            $son = Uri::root() . $son;
        }

        $attr_main = array();
        $attr_main['class'] = $options['class'];
        $attr_main['style'] = $options['style'];
        $attr_main[$options['evenement']] = 'jQuery.playSound(\'' . $son . '\')';

        // -- le code en retour
        $out = UpHelper::set_attr_tag($this,$options['tag'], $attr_main);
        $out .= $this->content;
        $out .= '</' . $options['tag'] . '>';

        return $out;
    }

// run
}

// class
