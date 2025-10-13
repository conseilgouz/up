<?php

/**
 * affiche une video Viméo qui s'inscrit au maxima dans son bloc parent
 *
 * {up media_vimeo=ID [| autoplay | portrait |title |muted |loop | play-on-visible]}
 *
 * ID : il s'agit du code figurant à la fin de l'URL de la vidéo.
 *
 * L'autoplay sous Firefox/Chrome est interdit si la video contient du son.
 * Utilisez muted pour débloquer cela.
 * voir https://vimeo.zendesk.com/hc/en-us/articles/115004485728-Autoplaying-and-looping-embedded-videos
 *
 * Les options title et portrait ne fonctionne que si la video le supporte.
 *
 * @author      Pascal
 * @version     UP-1.9.5
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Media
 */
/*
 * v2.4 - Prise en charge des services pour le script RGPD TarteAuCitron
 * v2.9 - ajout option RGPD pour ne pas appliquer localement la règle générale 
 */
defined('_JEXEC') or die;

class media_vimeo extends upAction {

    function init() {
        // charger les ressources communes à toutes les instances de l'action
        //-- le CSS
        //  http://clic-en-berry.com/comment-rendre-vos-integrations-iframe-youtube-responsive/
        $css_code = '.up-vimeo-container {position: relative; padding-bottom: 56.25%;	height: 0;overflow:hidden;}';
        $css_code .= '.up-vimeo-container iframe{ position:absolute;top:0;left:0;width:100%;height:100%;}';
        $this->load_css_head($css_code);
        return true;
    }

    function run() {

        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // code de la video (à la fin de l'url vimeo)
            'width' => '', // largeur de la video en px ou %
            'autoplay' => '0', // démarrage automatique
            'title' => '0', // afficher le titre de la vidéo
            'portrait' => '0', // afficher l'image de profil de l'auteur (portrait)
            'loop' => '0', // boucle en fin de video
            'muted' => '0', // coupe le son
            'play-on-visible' => '0', // démarre et arrête la video selon sa visibilité sur l'ecran
            /* [st-css] Style CSS*/
            'id' => '', // identifiant
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
        $outer_attr=array();
        $this->get_attr_style($outer_attr, $options['class'], $options['style']);
        $this->add_style($outer_attr['style'], 'width', $options['width']);

        // le bloc contenant la video
        $main_attr['class'] = 'up-vimeo-container';

        // l'iframe de la video
        $title = (!empty($options['title'])) ? '?title=1' : '?title=0';
        $autoplay = (!empty($options['autoplay'])) ? '&autoplay=true' : '&autoplay=false';
        $portrait = (!empty($options['portrait'])) ? '&portrait=1' : '&portrait=0';
        $loop = (!empty($options['loop'])) ? '&loop=1' : '&loop=0';
        $muted = (!empty($options['muted'])) ? '&muted=1' : '&muted=0';
        if (!empty($options['play-on-visible'])) { // mettre en pause si caché
            $this->load_file('player.min.js');
            $this->load_file('media_vimeo.js');
            $attr_iframe['class'] = 'play-on-visible';
        }
        $attr_iframe['frameborder'] = '0';
        $attr_iframe['scrolling'] = 'no';
        $attr_iframe['allowfullscreen'] = null;  // null = attribut sans argument
        if (!empty($options['autoplay']) || $options['play-on-visible']) {
            $attr_iframe['allow'] = 'autoplay';
            $attr_iframe['autoplay'] = '1'; // 2.4 tarteaucitron
        }

        if ($this->tarteaucitron && $options['rgpd']) {
            $attr_iframe['videoID'] = $options[__class__];
            $this->add_class($attr_iframe['class'], 'vimeo_player');
            $tag = 'div';
        } else {
            $attr_iframe['src'] = 'https://player.vimeo.com/video/' . $options[__class__] . $title . $portrait . $loop . $muted . $autoplay;
            $tag = 'iframe';
        }

        // le code renvoyé
        $out = $this->set_attr_tag($tag, $attr_iframe, true);
        $out = $this->set_attr_tag('div', $main_attr, $out);
        $out = $this->set_attr_tag('_div', $outer_attr, $out);
        return $out;
    }

// run
}

// class

// <div class="vimeo_player" videoID="video_id" width="width" height="height"></div>