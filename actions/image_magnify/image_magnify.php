<?php

/**
 * affiche un effet de loupe sur une image
 *
 * syntaxe 1 {up image-magnify=petite image | imgzoom=grande image pour zoom}
 * syntaxe 2 {up image-magnify=image pour vignette et zoom}
 *
 * @author   LOMART
 * @version  UP-0.9
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="https://github.com/thdoan/magnify" target="_blank">thdoan</a>
 * @tags  image
 * */

/*
 * v1.8 - fix class/style. reprise image-magnify pour imgzoom
 */

defined('_JEXEC') or die;

class image_magnify extends upAction {

    function init() {
        // charger les ressources communes à toutes les instances de l'action
        $this->load_file('magnify.css');
        $this->load_file('jquery.magnify.js');
        $this->load_jquery_code('$(".loupe").magnify({speed: 400, limitBounds:true});');
        $this->load_jquery_code('$(".zoommanuel").magnify();');
        return true;
    }

    function run() {

        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        // ===== valeur paramétres par défaut (sauf JS)
        $options_def = array(
            __class__ => '', // fichier image normale
            'imgzoom' => '', // fichier image utilisé pour le zoom. si vide: image principale
            /* [st-param] Paramètres de la loupe */
            'size' => '', // diamètre de la loupe en pixel
            'radius' => '', // taille de l'arrondi de la loupe en pixel
            'border' => '', // modele de bordure pour la loupe. fin ou vide
            /* [st-css] Style CSS*/
            'class' => '', // classe(s) ajoutées au bloc principal
            'style' => '', // style inline ajouté au bloc principal
            'id' => '' // id genérée automatiquement par UP
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        if ($options['imgzoom'] == '')
            $options['imgzoom'] = $options[__class__];

        // === style HTML
        // Constante
        $lib_border = array(
            'fin' => 'box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.85), 0 0 7px 7px rgba(0, 0, 0, 0.25), inset 0 0 40px 2px rgba(0, 0, 0, 0.25);'
        );

        if ($options['size']) {
            $size = $this->ctrl_unit($options['size'], 'px');
            $css = '#' . $options['id'] . ' .magnify > .magnify-lens {';
            $css .= 'width:' . $size . ';';
            $css .= 'height:' . $size . ';';
            if ($options['radius'])
                $css .= 'border-radius:' . $this->ctrl_unit($options['radius'], 'px,%');
            if (array_key_exists($options['border'], $lib_border)) {
                $this->add_str($css, $lib_border[$options['border']], ';');
            }
            $css .= '}';
            $this->load_css_head($css);
        }

        // === le code HTML
        $link_attr['id'] = $options['id'];
        $link_attr['href'] = $options['imgzoom'];

        $img_attr['class'] = 'loupe';
        $this->get_attr_style($img_attr, $options['class'], $options['style']);
        $img_attr['src'] = $options[__class__];
        // $img_attr['data-magnify-finalwidth'] = '600px';
        // $img_attr['data-magnify-finalheight'] = '400px';
        // -- le code en retour
        $out = $this->set_attr_tag('a', $link_attr);
        $out .= $this->set_attr_tag('img', $img_attr);
        $out .= '</a>';

        return $out;
    }

// run
}

// class
