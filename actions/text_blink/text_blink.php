<?php

/**
 * Faire clignoter un texte
 *
 * syntaxe 1 : {up text-blink}content{/up text-blink} // clignotement simple
 * syntaxe 2 : {up text-blink=color:red}content{/up text-blink} // style de l'alternance
 * syntaxe 3 : {up text-blink=prefset}content{/up text-blink} // jeu d'options
 * syntaxe 4 : {up text-blink=css_class}content{/up text-blink} // classe CSS
 *
 * @version  UP-5.1
 * @license  <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @author   LOMART
 * @tags     HTML
 *
 */
defined('_JEXEC') or die();

class text_blink extends upAction
{

    function init()
    {
        $this->load_file('text_blink.css');
        return true;
    }

    function run()
    {

        // si cette action a obligatoirement du contenu
        if (! $this->ctrl_content_exists()) {
            return false;
        }

        // lien vers la page de demo
        // - vide = page sur le site de UP
        // - URL complete = page disponible sur ce lien
        // - 0 pour cacher le lien vers demo car inexistante
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // rien, prefset, classe ou style(s) CSS
            'speed' => '1.5', // durée de l'animation
            'function' => 'ease-in-out', // ease, ease-in, ease-out, ease-in-out, linear, ...
            'count' => 'infinite', // nombre de cycle ou infinite pour clignotement permanent
			/*[base] style de base*/			
            'id' => '',
            'main-tag' => 'span', // balise HTML pour le bloc retourné
            'class' => '', // style et classe(s) pour bloc
            'style' => '', // style et classe(s) pour bloc
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // === CSS-HEAD
        $css = $options['css-head'];

        // attributs du bloc principal
        $id = $options['id'];
        $attr_main = array();
        $attr_main['id'] = $id;
        $this->get_attr_style($attr_main, $options['class'], $options['style']);

        if (! empty($options[__class__]) && ! str_contains($options[__class__], ':')) {
            // === si option principale est une classe
            $attr_main['class'] .= ' ' . $options[__class__];
            $html = $this->set_attr_tag($options['main-tag'], $attr_main, $this->content);
        } else {
            // ===
            $speed = ($options['speed']) ? floatval($options['speed']) . 's' : '1.5s';
            // on ajoute les styles passés en paramètres principal
            if (str_contains($options[__class__], ':')) {
                $css_to = $options[__class__];
            }
            // action sans argument : on clignote rapidement
            if (empty($css_to)) {
                $css_to = 'visibility:hidden;';
                $options['function'] = ($options['function']!='ease-in-out' ) ? $options['function']  : 'cubic-bezier(.68,-0.55,.27,1.55)';
                $speed = ($options['speed']=='1.5') ? $speed : '1s';
            }
            // === règles CSS
            $css .= '#' . $id . '{animation:kf-' . $id . ' ' . $speed . ' ' . $options['function'] . ' ' . $options['count'] . ';}';
            $css .= '@keyframes kf-' . $id . '{';
            $css .= ($css_to) ? 'to{' . $css_to . '}' : '';
            $css .= '}';
            $this->load_css_head($css);
        }
        // code en retour
        $html = $this->set_attr_tag($options['main-tag'], $attr_main, $this->content);
        return $html;
    }

    // run
}

// class
