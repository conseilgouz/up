<?php

/**
 * Saisir du code HTML avec un éditeur WYSIWYG
 *
 * syntax 1 {up bbcode=content}
 * syntax 2 {up bbcode} content {/up bbcode}
 *
 * @author   LOMART
 * @version  UP-1.8
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags     Editor
 */
defined('_JEXEC') or die;

class bbcode extends upAction {

    function init() {
        return true;
    }

    function run() {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // contenu au format BBCODE
            'tags' => '', // balises à traiter. vide: par défaut, liste (a,b,p) ou balise en plus de défaut (+a,b,p)
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);
        // Ou est le bbcode ?
        $content = ($this->content) ? $this->content : $options[__class__];
        if ($content === '')
            return $this->msg_inline($this->trad_keyword('no_content'));

        // Tags spécifiques à cette instance de l'action
        $tags = null;
        if ($options['tags']) {
            $tags = str_replace(',', '|', $tags = $options['tags']);
            $tags = str_replace(' ', '', $tags);
        }

        // suppression balises HTML
        $content = strip_tags($content);
        // Conversion contenu
        $content = $this->get_bbcode($content, $tags);

        // === CSS-HEAD
        $this->load_css_head($options['css-head']);

        // code en retour

        return $content;
    }

// run
}

// class
