<?php

/**
 * affiche une image aleatoire
 *
 * Syntaxe :
 * {up lorem-placeimg=catégorie | width=xx | height=xx | grayscale | sepia }
 *
 * --> catégorie = any, animals, arch, nature, people, tech
 *
 * @author   Lomart
 * @version  UP-1.5
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags   Editor
 */
defined('_JEXEC') or die;

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class lorem_placeimg extends Lomart\Plugin\Content\Up\Extension\Up {

    function init() {
        // aucune
    }

    function run() {

        // lien vers la page de demo (vide=page sur le site de UP)
        UpHelper::set_demopage($this);

        // ===== valeur paramétres par défaut
        // il est indispensable de tous les définir ici
        $options_def = array(
          __class__ => 'any', // type d'image: any, animals, arch, nature, people, tech
          'height' => '200', // hauteur image téléchargée
          'width' => '200', // largeur image téléchargée
          'grayscale' => 0, // rendu en niveau de gris
          'sepia' => 0, // rendu sépia
          /* [st-css] Style CSS*/
          'id' => '', // identifiant
          'class' => '', // classe(s) (obsolète)
          'style' => ''   // classes et styles
        );

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);

        // controle options
        $type = 'any, animals, arch, nature, people, tech';
        $options[__class__] = ($options[__class__] == 1) ? 'any' : $options[__class__];

        if (in_array($options[__class__], explode(', ', $type))) {
            // si le type existe, on affiche l'image
            $img_attr = array();
            UpHelper::get_attr_style($this,$img_attr, $options['class'], $options['style']);
            $img_attr['src'] = 'https://placeimg.com';
            $img_attr['src'] .= '/' . (int) $options['width'];
            $img_attr['src'] .= '/' . (int) $options['height'];
            $img_attr['src'] .= '/' . $options[__class__];
            if ($options['grayscale']) {
                $img_attr['src'] .= '/grayscale';
            }
            if ($options['sepia']) {
                $img_attr['src'] .= '/sepia';
            }
            // --
            $txt = UpHelper::set_attr_tag($this,'img', $img_attr);
        } else {
            // on affiche un rappel des styles disponibles
            $error_attr['style'] = '';
            UpHelper::add_style($this,$error_attr['style'], 'width', (int) $options['width'] . 'px');
            UpHelper::add_style($this,$error_attr['style'], 'height', (int) $options['height'] . 'px');
            UpHelper::add_style($this,$error_attr['style'], 'background-color', 'salmon');
            UpHelper::add_style($this,$error_attr['style'], 'color', 'black');
            UpHelper::add_style($this,$error_attr['style'], 'text-align', 'center');
            $txt = UpHelper::set_attr_tag($this,'div', $error_attr);
            $txt .= 'placeimg - category = ' . $type;
            $txt .= '</div>';
        }

        return $txt;
    }

// run
}

// class
