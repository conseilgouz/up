<?php

/**
 * DESCRIPTION COURTE
 *
 * suite description
 *
 * syntaxe {up action=option_principale}
 *
 * @version  UP-1.0  <- version minimale de UP pour prise en charge
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="" target"_blank">script xxx de xxx</a>
 * @tags    Groupe pour bouton editeur
 *
 */
defined('_JEXEC') or die;

class _example_simple extends upAction {

    function init() {
        // charger les ressources communes à toutes les instances de l'action
        $this->load_file('xxxxx.css');
        $this->load_file('xxxxx.js');
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
        // - 0 pour cacher le lien vers demo car inexistante
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // description argument attendu
            'id' => '',
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // === CSS-HEAD
        $this->load_css_head($options['css-head']);

        // attributs du bloc principal
        $attr_main = array();
        $attr_main['id'] = $options['id'];
        $this->get_attr_style($attr_main, $options['class'], $options['style']);

        // code en retour
        $html[] = $this->set_attr_tag('div', $attr_main, $this->content);

        return implode(PHP_EOL, $html);
    }

// run
}

// class
