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

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class image_rollover extends Lomart\Plugin\Content\Up\Extension\Up {

    function init() {
        return true;
    }

    function run() {


        // lien vers la page de demo
        UpHelper::set_demopage($this);

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
        $options = UpHelper::ctrl_options($this,$options_def);

        // attributs du bloc principal
        $attr_main = array();
        $attr_main['src'] = $options[__class__];
        $attr_main['onmouseover'] = 'this.src=\'' . UpHelper::get_url_absolute($this,$options['hover']) . '\'';
        $attr_main['onmouseout'] = 'this.src=\'' . UpHelper::get_url_absolute($this,$options[__class__]) . '\'';
        if ($options['click']) {
            $attr_main['onmousedown'] = 'this.src=\'' . UpHelper::get_url_absolute($this,$options['click']) . '\'';
            $attr_main['onmouseup'] = 'this.src=\'' . UpHelper::get_url_absolute($this,$options['hover']) . '\'';
        }
        $attr_main['class'] = $options['class'];
        $attr_main['style'] = $options['style'];

        // code en retour
        $html[] = UpHelper::set_attr_tag($this,'img', $attr_main);

        return implode(PHP_EOL, $html);
    }

// run
}

// class
