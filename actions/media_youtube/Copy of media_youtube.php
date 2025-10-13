<?php

/**
 * affiche une video Youtube qui s'inscrit au maxima dans son bloc parent
 *
 * {up media-youtube=ID [|autoplay|play-on-visible|muted|loop]}
 * ID : il s'agit du code figurant à la fin de l'URL de la vidéo.
 *
 * @author      LOMART/ modifié par Pascal
 * @version     UP-0.9
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Media
 */
/*
 * v1.95 - marche/arret selon visibilité vidéo à l'écran (par Pascal)
 * v2.6 - fix si tarteaucitron
 * v2.9 - ajout option RGPD pour ne pas appliquer localement la règle générale 
 */
defined('_JEXEC') or die;

class media_youtube extends upAction {

    function init() {
        // charger les ressources communes à toutes les instances de l'action
        //-- le CSS
        //  http://clic-en-berry.com/comment-rendre-vos-integrations-iframe-youtube-responsive/
        $css_code = '.up-video-container {position: relative; padding-bottom: 56.25%;	height: 0;overflow:hidden;}';
        $css_code .= '.up-video-container iframe{ position:absolute;top:0;left:0;width:100%;height:100%;}';
        $this->load_css_head($css_code);
        return true;
    }

    function run() {

        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // code de la video (à la fin de l'url youtube)
            'width' => '', // largeur de la video en px ou %
            'autoplay' => '0', // demarrage automatique
            'loop' => '0', // boucle sur la video
            'muted' => '0', // coupe le son
            'play-on-visible' => '0', // démarre et arrête la video selon sa visibilité sur l'ecran
            /* [st-css] Style CSS*/
            'id' => '', // Identifiant
            'class' => '', // classe pour bloc externe
            'style' => '', // code css libre pour bloc externe
            /* [st-divers] Divers */
            'rgpd' => '1', // 0 pour ne pas appliquer la règle pour le RGPD
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);
        if ($options['width'])
            $options['width'] = $this->ctrl_unit($options['width'], '%,px');

        // le bloc externe
        $this->get_attr_style($outer_attr, $options['class'], $options['style']);
        $this->add_style($outer_attr['style'], 'width', $options['width']);

        // le bloc contenant la video
        $main_attr['class'] = 'up-video-container';
        $main_attr['style'] = '';

        $api = "?rel=0";
        if (!empty($options['play-on-visible'])) {
            $this->load_file('youtube_api.min.js');
            $this->load_file('media_youtube.js');
            $api .= "&enablejsapi=1";
            $attr_iframe['class'] = 'play-on-visible';
        }
        // note loop ne fonctionne qu'avec un parametre playlist : https://developers.google.com/youtube/player_parameters#loop
        $api .= ($options['loop'] != '') ? '&loop=1&playlist=' . $options[__class__] : '&loop=0';
        $api .= ($options['muted'] != '') ? '&mute=1' : '&mute=0';
        if (!empty($options['autoplay'])) {
            $api .= '&autoplay=1';
            $attr_iframe['allow'] = 'autoplay';
            $attr_iframe['autoplay'] = '1';  // v2.4 tarteaucitron
        } else {
            $api .= '&autoplay=0';
        }
        // l'iframe de la video
        $attr_iframe['frameborder'] = '0';
        $attr_iframe['scrolling'] = 'no';
        $attr_iframe['allowfullscreen'] = null;  // null = attribut sans argument
        $attr_iframe['id'] = $options['id'];
        if ($this->tarteaucitron && $options['rgpd']) {
            $attr_iframe['videoID'] = $options[__class__] . $api;
            $this->add_class($attr_iframe['class'], 'youtube_player');
            $tag = 'div';
        } else {
            $attr_iframe['src'] = 'https://www.youtube.com/embed/' . $options[__class__] . $api;
            $tag = 'iframe';
        }
        // le code renvoyé
//        $out = $this->set_attr_tag('iframe', $attr_iframe, true);
        $out = $this->set_attr_tag($tag, $attr_iframe, true);
        $out = $this->set_attr_tag('div', $main_attr, $out);
        $out = $this->set_attr_tag('_div', $outer_attr, $out);
        
        return $out;
    }

// run
}

// class
