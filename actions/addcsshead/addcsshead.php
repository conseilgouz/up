<?php

/**
 * ajoute du code ou fichier CSS dans le head.
 *
 * sans risque de nettoyage par un éditeur WYSIWYG
 *
 * syntaxe 1 {up addCssHead=.foo[color:red]} Attention: mettre des [ ] au lieu de {}
 * syntaxe 2 {up addCssHead}.foo{color:red}{/up addCssHead} 
 * syntaxe 3 {up addCssHead=chemin_fichier_css}
 *
 * Utilisation : charger un fichier CSS spécifique à une page
 *
 * @author   LOMART
 * @version  UP-0.9
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags Expert
 *
 */

/*
 * v1.6 : possibilité de charger un fichier
 * v1.7 : possibilité de saisir des crochet [] en les échappant par \
 * v2.9 : syntaxe avec css comme contenu
 * v5.1 : strip_tags sur contenu
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;

class addcsshead extends upAction {

    function init() {
        // aucune
    }

    function run() {
        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        $options_def = array(
          __class__ => '', // fichier ou code CSS. ATTENTION [ ] à la place des {} pour code dans shortcode
          'filter' => '', // conditions. Voir doc action filter  (v1.8)
          'id' => '' // identifiant
        );

        $options = $this->ctrl_options($options_def);

        // === Filtrage
        if ($this->filter_ok($options['filter']) !== true) {
            return '';
        }

        if (strpos($options[__class__], '[')) {
            // il suffit de charger le code dans le head
            $this->load_css_head($options[__class__]);
        } elseif(!empty($this->content)) { // v2.9
            $this->load_css_head(strip_tags($this->content));
        } else {
            $ficname = $options[__class__];
            if (strtolower(pathinfo($ficname, PATHINFO_EXTENSION)) == 'css' && file_exists($ficname)) {
                HTMLHelper::stylesheet($ficname);
            } else {
                $this->msg_error($this->trad_keyword('UP_FIC_NOT_FOUND', $ficname));
            }
        }
        // -- aucun code en retour
        return '';
    }

// run
}

// class addcsshead

