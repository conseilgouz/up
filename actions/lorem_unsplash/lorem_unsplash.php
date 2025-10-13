<?php

/**
 * affiche une image aleatoire
 *
 * Syntaxe :  {up lorem-unsplash=#image | width=xx | height=xx }
 * rechercher n° image sur https://picsum.photos/images
 *
 * @author   Lomart
 * @version  UP-1.5
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Editor
 */
defined('_JEXEC') or die;

class lorem_unsplash extends upAction
{
    public function init()
    {
        // aucune
    }

    public function run()
    {

        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        // ===== valeur paramétres par défaut
        // il est indispensable de tous les définir ici
        $options_def = array(
          __class__ => '', // n° de l'image sur https://picsum.photos/images
          'height' => '200', // hauteur image téléchargée
          'width' => '200', // largeur image téléchargée
          'random' => '0', // force la récupération aléatoire
          'grayscale' => '0', // pour rendu en niveaux de gris
          'blur' => '0', // rendu flou
          'gravity' => '', // recadrage : north, east, south, west, center
          /* [st-css] Style CSS*/
          'id' => '',   // identifiant
          'class' => '', // classe(s) (obsolète)
          'style' => ''   // classes et styles
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // controle options
        $gravity_list = 'north,east,south,west,center';
        $options[__class__] = ($options[__class__] == 1) ? 'cats' : $options[__class__];

        // si le type existe, on affiche l'image
        $img_attr = array();
        $this->get_attr_style($img_attr, $options['class'], $options['style']);
        $img_attr['src'] = 'https://picsum.photos';
        if ($options["grayscale"]) {
            $img_attr['src'] .= '/g';
        }
        $img_attr['src'] .= '/' . (int) $options['width'];
        $img_attr['src'] .= '/' . (int) $options['height'];
        // les options

        if ($this->options_user[__class__] != '') {
            $src_options[] = 'image=' . intval($options[__class__]);
        }
        if (!empty($options['random'])) {
            $src_options[] = 'random';
        }
        if (!empty($options['blur'])) {
            $src_options[] = 'blur';
        }
        if (empty($this->options_user['gravity'])) {
            $gravity = $this->ctrl_argument($options['gravity'], ',' . $gravity_list);
            if ($gravity > '') {
                $src_options[] = 'gravity=' . $gravity;
            }
        }
        // assemblage
        if (isset($src_options)) {
            $img_attr['src'] .= '/?' . implode('&', $src_options);
        }
        // --
        $txt = $this->set_attr_tag('img', $img_attr);

        return $txt;
    }

    // run
}

// class
