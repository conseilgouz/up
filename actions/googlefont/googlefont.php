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

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class googlefont extends Lomart\Plugin\Content\Up\Extension\Up {

    function init() {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    function run() {
        // lien vers la page de demo (vide=page sur le site de UP)
        UpHelper::set_demopage($this);

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
        $options = UpHelper::ctrl_options($this,$options_def);
        if ($options['size'])
            $options['size'] = UpHelper::ctrl_unit($this,$options['size'], 'px, rem, %');

        // === Ajout dans le head
        // --- on charge la première police
        $font = strip_tags($options[$this->name]);  // si recup par copier-coller du site google
        $font = explode('|', $font)[0]; // uniquement la première
        $font = str_replace(' ', '+', $font); // en cas de copie du nom avec espace
        if ($font > '') {
            $link = '<link href="https://fonts.googleapis.com/css?family=';
            $link .= $font;
            $link .= '" rel="stylesheet">';
            UpHelper::load_custom_code_head($this,$link);
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
        UpHelper::add_str($this,$css, strip_tags($options['family']), ',');

        // la propriété font-size
        if ($options['size']) {
            UpHelper::add_style($this,$css, 'font-size', strip_tags($options['size']));
            UpHelper::add_style($this,$css, 'line-height', '120%');
        }

        // le code user
        UpHelper::add_str($this,$css, strip_tags($options['css-head']), ';');

        // ajout du css dans le head
        UpHelper::load_css_head($this,'.' . $options['className'] . '{' . $css . '}');

        // -- le code en retour
        $out = '';
        if ($this->content) {
            // on applique la classe au bloc
            $attr_main['class'] = $options['className'];
            // si le contenu contient des block, on remplace span par div
            if ((strpos($this->content, '</p>') !== false) || (strpos($this->content, '</div>') !== false))
                $options['tag'] = 'div';
            $out = UpHelper::set_attr_tag($this,$options['tag'], $attr_main, $this->content);
        }

        return $out;
    }

// run
}

// class
