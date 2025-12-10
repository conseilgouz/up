<?php

/**
 * Sert un café virtuel
 *
 * syntaxe {up kawa=long | sucre | speculoos=lotus }
 *
 * @author   LOMART
 * @version  UP-1.3.2
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags Widget
 */
defined('_JEXEC') or die;

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class kawa extends Lomart\Plugin\Content\Up\Extension\Up
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    public function run()
    {


        // lien vers la page de demo
        UpHelper::set_demopage($this);

        $options_def = array(
          __class__ => '', // vide = café court, long = café long
          'sucre' => 1, // option sucre
          'speculoos' => '', // speculoos base ou spécifier marque (lotus)
          /* [st-css] Style CSS*/
          'id' => '',  // identifiant
          'class' => '', // classe(s) pour bloc
          'style' => '', // style inline pour bloc
        );

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);

        // ----- préparation commande
        $img = '';
        // sucre à gauche
        if ($options['sucre']) {
            $img .= '<img width="15%" src="' . $this->actionPath . 'img/sucre.jpg' . '">';
        }
        // café au centre
        if ($options[__class__] == 'long') {
            $img .= '<img width="25%" src="' . $this->actionPath . 'img/cafe-long.jpg' . '">';
        } else {
            $img .= '<img width="40%" src="' . $this->actionPath . 'img/cafe.jpg' . '">';
        }

        // speeculoos
        if ($options['speculoos'] != '') {
            if ($options['speculoos'] == 1) {
                $img_speeculoos = $this->actionPath . 'img/speculoos.jpg';
            } else {
                $img_speeculoos = $this->actionPath . 'img/speculoos-lotus.jpg';
            }
            $img .= '<img width="24%" src="' . $img_speeculoos . '">';
        }

        // attributs du bloc principal
        $attr_main = array();
        $attr_main['class'] = $options['class'];
        $attr_main['style'] = $options['style'];
        UpHelper::add_style($this,$attr_main['style'], 'background', 'url(plugins/content/up/actions/kawa/img/bg.jpg) no-repeat');
        UpHelper::add_style($this,$attr_main['style'], 'background-size', '100%,100%');
        // code en retour
        $out = UpHelper::set_attr_tag($this,'div', $attr_main);
        $out .= $img;
        $out .= '</div>';

        return $out;
    }

    // run
}

// class
