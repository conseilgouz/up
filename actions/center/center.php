<?php

/**
 * Centre tous les blocs enfants en supprimant les margins superflus
 * *
 * syntaxe {up center=classe/style(s)}contenu{/up center}
 *
 * note: cette action reconnait les classes et les styles dans : bg-yellow;color:red
 *
 * @author   LOMART
 * @version  UP-1.5
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    HTML
 */
defined('_JEXEC') or die;

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class center extends Lomart\Plugin\Content\Up\Extension\Up {

    function init() {
        // charger les ressources communes à toutes les instances de l'action
        UpHelper::load_upcss($this);
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
          __class__ => '', // classe(s) et style(s) pour le bloc interne (celui qui est centré)
          'id' => '', // identifiant
          'class' => '', // classe(s) pour bloc externe
          'style' => '', // style inline pour bloc externe
          'css-head' => '' // règles CSS définies par le webmaster (ajout dans le head)
        );

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);

        // === css-head
        UpHelper::load_css_head($this,$options['css-head']);

        // --- attributs du bloc principal
        // centrage H&V du bloc interne
        $outer_main['id'] = $options['id'];
        UpHelper::get_attr_style($this,$outer_main, "up-center-outer;" . $options['class'], $options['style']);

        // --- classe pour centrage vertical
        if ($options[__class__] != 1) {
            UpHelper::get_attr_style($this,$attr_content, "up-center-inner;", $options[__class__]);
        }

        // code en retour
        $html[] = UpHelper::set_attr_tag($this,'div', $outer_main);
        $html[] = UpHelper::set_attr_tag($this,'div', $attr_content);
        $html[] = $this->content;
        $html[] = '</div>';
        $html[] = '</div>';

        return implode(PHP_EOL, $html);
    }

// run
}

// class
