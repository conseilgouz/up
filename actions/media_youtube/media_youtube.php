<?php

/**
 * affiche une video Youtube qui s'inscrit au maxima dans son bloc parent
 *
 * {up media-youtube=ID [|autoplay|play-on-visible|muted|loop|facade]}
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
 * v3.0 - ajout de l'option ratio pour utiliser la propriété CSS aspect-ratio (compatibilité avec cookieck)
 * v5.3 - ajout de l'option facade : chargement d'une image au lieu de la vidéo
 */
defined('_JEXEC') or die();

class media_youtube extends upAction
{

    function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    function run()
    {

        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // code de la video (à la fin de l'url youtube)
            'ratio' => '16/9', // homothétie de la vidéo sous la forme 16/9 ou 1.77
            'width' => '', // largeur de la video en px ou %
            'autoplay' => '0', // demarrage automatique
            'loop' => '0', // boucle sur la video
            'muted' => '0', // coupe le son
            'play-on-visible' => '0', // démarre et arrête la video selon sa visibilité sur l'ecran
            /* [st-css] Style CSS */
            'id' => '', // Identifiant
            'class' => '', // classe pour bloc externe
            'style' => '', // code css libre pour bloc externe
            /* [st-divers] Divers */
            'rgpd' => '1', // 0 pour ne pas appliquer la règle pour le RGPD
            'facade' => '' // activer ou non le mode facade
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);
        if ($options['width'])
            $options['width'] = $this->ctrl_unit($options['width'], '%,px');

        // le bloc externe
        $outer_attr = array();
        $this->get_attr_style($outer_attr, $options['class'], $options['style']);
        $this->add_style($outer_attr['style'], 'width', $options['width']);

        // le bloc contenant la video
        $main_attr['id'] = $options['id'];
        $main_attr['class'] = 'up-video-container';
        $main_attr['style'] = '';

        if ($options['facade']) {
            $this->load_file('lib/lite-yt-embed.css');
            $this->load_file('lib/lite-yt-embed.js');
            $out = "";
            $out .= ($outer_attr) ? $this->set_attr_tag('div', $outer_attr) : '';
            $out .= '<lite-youtube videoid='.$options[__class__].' style="margin-left:auto;margin-right:auto"></lite-youtube>';
            $out .= ($outer_attr) ? '</div>' : '';
            return $out;
        }
        $api = "?rel=0";
        if (! empty($options['play-on-visible'])) {
            $this->load_file('youtube_api.min.js');
            $this->load_file('media_youtube.js');
            $api .= "&enablejsapi=1";
            $attr_iframe['class'] = 'play-on-visible';
        }
        // note loop ne fonctionne qu'avec un parametre playlist : https://developers.google.com/youtube/player_parameters#loop
        $api .= ($options['loop'] != '') ? '&loop=1&playlist=' . $options[__class__] : '&loop=0';
        $api .= ($options['muted'] != '') ? '&mute=1' : '&mute=0';
        if (! empty($options['autoplay'])) {
            $api .= '&autoplay=1';
            $attr_iframe['allow'] = 'autoplay';
            $attr_iframe['autoplay'] = '1'; // v2.4 tarteaucitron
        } else {
            $api .= '&autoplay=0';
        }
        // l'iframe de la video
        $attr_iframe['width'] = '100%';
        $attr_iframe['height'] = '100%';
        $attr_iframe['frameborder'] = '0';
        $attr_iframe['scrolling'] = 'no';
        $attr_iframe['allowfullscreen'] = null; // null = attribut sans argument
        if ($this->tarteaucitron && $options['rgpd']) {
            $attr_iframe['videoID'] = $options[__class__] . $api;
            $this->add_class($attr_iframe['class'], 'youtube_player');
            $tag = 'div';
        } else {
            $attr_iframe['src'] = 'https://www.youtube.com/embed/' . $options[__class__] . $api;
            $tag = 'iframe';
        }

        // le CSS
        $css_code = '#' . $options['id'] . '.up-video-container div  {aspect-ratio: ' . $options['ratio'] . '}';
        $css_code .= '#' . $options['id'] . '.up-video-container iframe {aspect-ratio: ' . $options['ratio'] . ';display:flex;}';
        $this->load_css_head($css_code);

        // le code renvoyé
        $out = $this->set_attr_tag($tag, $attr_iframe, true);
        $out = $this->set_attr_tag('div', $main_attr, $out);
        $out = $this->set_attr_tag('_div', $outer_attr, $out);

        return $out;
    }

    // run
}

// class
