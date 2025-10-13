<?php

/**
 * Facilite la saisie d'un bloc inline SPAN avec un éditeur wysiwyg
 *
 * syntaxe {up span=class_and_style}content{/up span}
 *
 * @author   LOMART
 * @version  UP-2.0
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags   HTML
 */

/*
 * v5.0.1 - prise en charge de tous les attributs possibles
*/
defined('_JEXEC') or die;

class span extends upAction
{
    public function init()
    {
        return true;
    }

    public function run()
    {

        // si cette action a obligatoirement du contenu
        if (!$this->ctrl_content_exists()) {
            return false;
        }

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
          __class__ => '', // classes et style (séparateur : point-virgule)
          'id' => '', // si indiqué, l'attribut ID est ajouté à la balise SPAN
          'class' => '', // classe(s) pour bloc (méthode traditionnelle)
          'style' => '', // style inline pour bloc (méthode traditionnelle)
          'css-head' => '', // style ajouté dans le HEAD de la page
          'xxx' => 'yyy' // couple attribut-valeur. ex: title=le titre, href=//google.fr
        );

        // On accepte toutes les options. Il faut les ajouter avant contrôle
        foreach (array_diff_key($this->options_user, $options_def) as $key => $val) {
            $options_def[$key] = '';
        }

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // === CSS-HEAD
        $this->load_css_head($options['css-head']);

        // -- toutes les options sont des attributs sauf html, class et style
        $outer_div = $options;
        unset($outer_div[__class__]);
        unset($outer_div['class']);
        unset($outer_div['style']);
        unset($outer_div['xxx']);
        unset($outer_div['css-head']);

        // -- analyse et ajout class et style
        $this->get_attr_style($outer_div, $options[__class__], $options['class'], $options['style']);

        // attributs du bloc principal
        if (substr($this->options_user['id'], 0, 3) !== 'up-') {
            $outer_div['id'] = $options['id'];
        }

        // code en retour
        $html[] = $this->set_attr_tag('span', $outer_div, $this->content);

        return implode(PHP_EOL, $html);
    }

    // run
}

// class
