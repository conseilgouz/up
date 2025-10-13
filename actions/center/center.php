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

class center extends upAction {

    function init() {
        // charger les ressources communes à toutes les instances de l'action
        $this->load_upcss();
        return true;
    }

    function run() {

        // si cette action a obligatoirement du contenu
        if (!$this->ctrl_content_exists()) {
            return false;
        }

        // lien vers la page de demo
        // - vide = page sur le site de UP
        // - URL complete = page disponible sur ce lien
        // - rien pour ne pas proposer d'aide
        $this->set_demopage();

        $options_def = array(
          __class__ => '', // classe(s) et style(s) pour le bloc interne (celui qui est centré)
          'id' => '', // identifiant
          'class' => '', // classe(s) pour bloc externe
          'style' => '', // style inline pour bloc externe
          'css-head' => '' // règles CSS définies par le webmaster (ajout dans le head)
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // === css-head
        $this->load_css_head($options['css-head']);

        // --- attributs du bloc principal
        // centrage H&V du bloc interne
        $outer_main['id'] = $options['id'];
        $this->get_attr_style($outer_main, "up-center-outer;" . $options['class'], $options['style']);

        // --- classe pour centrage vertical
        if ($options[__class__] != 1) {
            $this->get_attr_style($attr_content, "up-center-inner;", $options[__class__]);
        }

        // code en retour
        $html[] = $this->set_attr_tag('div', $outer_main);
        $html[] = $this->set_attr_tag('div', $attr_content);
        $html[] = $this->content;
        $html[] = '</div>';
        $html[] = '</div>';

        return implode(PHP_EOL, $html);
    }

// run
}

// class
