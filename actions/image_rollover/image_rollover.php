<?php

/**
 * Change l'image au survol
 *
 * syntaxe {up image-rollover=image_base | hover=image_survol}
 *
 * @author   LOMART
 * @version  UP-1.4
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags  image
 */
defined('_JEXEC') or die;

class image_rollover extends upAction {

    function init() {
        return true;
    }

    function run() {


        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // image repos
            'hover' => '', // image au survol
            'click' => '', // image lors clic
            /* [st-css] Style CSS*/
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // attributs du bloc principal
        $attr_main = array();
        $attr_main['src'] = $options[__class__];
        $attr_main['onmouseover'] = 'this.src=\'' . $this->get_url_absolute($options['hover']) . '\'';
        $attr_main['onmouseout'] = 'this.src=\'' . $this->get_url_absolute($options[__class__]) . '\'';
        if ($options['click']) {
            $attr_main['onmousedown'] = 'this.src=\'' . $this->get_url_absolute($options['click']) . '\'';
            $attr_main['onmouseup'] = 'this.src=\'' . $this->get_url_absolute($options['hover']) . '\'';
        }
        $attr_main['class'] = $options['class'];
        $attr_main['style'] = $options['style'];

        // code en retour
        $html[] = $this->set_attr_tag('img', $attr_main);

        return implode(PHP_EOL, $html);
    }

// run
}

// class
