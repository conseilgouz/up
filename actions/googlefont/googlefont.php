<?php

/**
 * ajoute une police GoogleFont, ainsi qu'une classe pour l'utiliser
 *
 * syntaxe :
 * {up googlefont=nompolice} contenu {/up googlefont}
 * {up googlefont=nompolice | class=foo} < p class="foo">...< p>
 *
 * @author   LOMART
 * @version  UP-0.9
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Expert
 */

/*
 * v1.8  - tag pour contenu selon son type (block ou inline)
 */

defined('_JEXEC') or die;

class googlefont extends upAction {

    function init() {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    function run() {
        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        // ===== valeur paramétres par défaut
        // il est indispensable de tous les définir ici
        $options_def = array(
          $this->name => '', // nom police
          'size' => '', // ajout font-size et line-height
          'family' => '', // famille de substitution (cursive, fantasy)
          /* [st-divers] Divers */
          'tag' => 'span', // balise HTML pour entourer le contenu
          'className' => '', // nom de la classe pour utiliser la police ailleurs dans la page
          /* [st-css] Style*/
          'id' => '', // identifiant
          'css-head' => '', // complément de css: color, font-size, ....
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);
        if ($options['size'])
            $options['size'] = $this->ctrl_unit($options['size'], 'px, rem, %');

        // === Ajout dans le head
        // --- on charge la première police
        $font = strip_tags($options[$this->name]);  // si recup par copier-coller du site google
        $font = explode('|', $font)[0]; // uniquement la première
        $font = str_replace(' ', '+', $font); // en cas de copie du nom avec espace
        if ($font > '') {
            $link = '<link href="https://fonts.googleapis.com/css?family=';
            $link .= $font;
            $link .= '" rel="stylesheet">';
            $this->load_custom_code_head($link);
        }

        // --- on crée la classe
        // son nom
        if ($options['className'] == '') {
            // si aucune classe indiquée, on utilise l'ID pour parer
            // à plusieurs utilsations avec des size et/ou classcode différents
            $options['className'] = $options['id'];
        }
        // la propriété font-family
        $css = 'font-family:"' . str_replace('+', ' ', explode(':', $font)[0]) . '"';
        $this->add_str($css, strip_tags($options['family']), ',');

        // la propriété font-size
        if ($options['size']) {
            $this->add_style($css, 'font-size', strip_tags($options['size']));
            $this->add_style($css, 'line-height', '120%');
        }

        // le code user
        $this->add_str($css, strip_tags($options['css-head']), ';');

        // ajout du css dans le head
        $this->load_css_head('.' . $options['className'] . '{' . $css . '}');

        // -- le code en retour
        $out = '';
        if ($this->content) {
            // on applique la classe au bloc
            $attr_main['class'] = $options['className'];
            // si le contenu contient des block, on remplace span par div
            if ((strpos($this->content, '</p>') !== false) || (strpos($this->content, '</div>') !== false))
                $options['tag'] = 'div';
            $out = $this->set_attr_tag($options['tag'], $attr_main, $this->content);
        }

        return $out;
    }

// run
}

// class
