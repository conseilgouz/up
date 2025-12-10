<?php

/**
 * affiche un lecteur video ou audio qui s'inscrit au maxima dans son bloc parent
 *
 * {up media_plyr=yt | code=bTqVqk7FSmY }
 * {up media_plyr=vimeo | code=bTqVqk7FSmY }
 * <b>media_plyr</b> : précisez yt ou youtube ou vimeo
 * <b>code</b>  : il s'agit du code figurant dans l'URL de la vidéo.
 * .
 * {up media_plyr=video | poster=url image | mp4 | webm | vtt | download }
 * <b>media_plyr</b> : video pour indiquer l'URL vers la video.
 * <b>poster</b> : nom de l'image fixe (obligatoire)
 * <b>mp4, webm, vtt, download</b> : si URL non spécifiée, elle sera déduite de celle pour 'poster'
 * .
 * {up media_plyr=audio | mp3=url fichier mp3 | ogg}
 * <b>media_plyr</b> : audio
 * <b>mp3</b> : url vers fichier MP3 (obligatoire)
 * <b>ogg</b> : si URL non spécifiée, elle sera déduite de celle pour 'MP3'
 * .
 * @author      DANEEL & LOMART
 * @version     UP-1.1
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Media
 */
 /* 
 * v5.3.4 : audio : hide html5 player
 */
defined('_JEXEC') or die();

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class media_plyr extends Lomart\Plugin\Content\Up\Extension\Up
{

    function init()
    {
        UpHelper::load_file($this,'plyr.css'); // v 2.0.13
        UpHelper::load_file($this,'plyr.js'); // v 2.0.13

        $js_code = 'var instances = plyr.setup({ debug: false });';
        UpHelper::load_jquery_code($this,$js_code);

        return true;
    }

    function run()
    {

        // lien vers la page de demo (vide=page sur le site de UP)
        UpHelper::set_demopage($this);

        $options_def = array(
            __class__ => '', // type de lecture : youtube, vimeo, video ou audio
            'code' => '', // code de la video (à la fin de l'url youtube ou vimeo)
            'poster' => '', // URL de l'image (obligatoire)
            'mp4' => '', // URL du fichier MP4. Si vide, on utilise le nom de poster
            'webm' => '', // URL du fichier WEBM. Si vide, on utilise le nom de poster
            'vtt' => '', // URL du fichier VTT pour sous-titrage. Si vide, on utilise le nom de poster
            'download' => '', // URL du fichier téléchargeable. Si vide, on utilise le nom du fichier MP4
            'mp3' => '', // URL du fichier audio mp3. (obligatoire)
            'ogg' => '', // URL du fichier audio ogg. Si vide, on utilise le nom du fichier MP3
            /* [st-css] Style CSS */
            'id' => '', // identifiant
            'class' => '', // classe(s) ajoutée(s) au bloc extérieur
            'style' => '' // style inline ajouté au bloc extérieur
        );

        // ==== si video en video ou audio, on affecte les variables pour un eventuel debug
        // si appel par vimeo, audio ou video
        UpHelper::set_option_user_if_true($this,__class__, $this->actionUserName);

        // si video, on affecte les variables pour debug
        if ($this->options_user[__class__] == "video") {
            if (! isset($this->options_user['poster'])) {
                return UpHelper::msg_inline($this,UpHelper::trad_keyword($this,'NO_POSTER'));
            }
            $name = substr($this->options_user['poster'], 0, strrpos($this->options_user['poster'], '.'));
            UpHelper::set_option_user_if_true($this,'mp4', $name . '.mp4');
            UpHelper::set_option_user_if_true($this,'webm', $name . '.webm');
            UpHelper::set_option_user_if_true($this,'vtt', $name . '.vtt');
            UpHelper::set_option_user_if_true($this,'download', $name . '.download');
        }

        // si audio, on affecte les variables pour debug
        if ($this->options_user[__class__] == "audio") {
            if (! isset($this->options_user['mp3'])) {
                return UpHelper::msg_inline($this,UpHelper::trad_keyword($this,'NO_MP3'));
            }
            $name = substr($this->options_user['mp3'], 0, strrpos($this->options_user['mp3'], '.'));
            UpHelper::set_option_user_if_true($this,'ogg', $name . '.ogg');
        }

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);

        switch ($options[__class__]) {
            case 'yt':
            case 'youtube':
                $out = '<div data-type="youtube" data-video-id="' . $options['code'] . '"></div>';
                break;

            case 'vimeo':
                $out = '<div data-type="vimeo" data-video-id="' . $options['code'] . '"></div>';
                break;

            case 'video':
                $out = '<video poster="' . $options['poster'] . '" controls crossorigin style="max-width:100%">';
                $out .= ($options['mp4'] > '') ? '<source src="' . $options['mp4'] . '" type="video/mp4">' : '';
                $out .= ($options['webm'] > '') ? '<source src="' . $options['webm'] . '" type="video/webm">' : '';
                $out .= ($options['vtt'] > '') ? '<track kind="captions" label="English" srclang="en" src="' . $options['vtt'] . '" default>' : '';
                $out .= ($options['download'] > '') ? '<a href="' . $options['download'] . '">download</a>' : '';
                $out .= '</video>';

                if ($options['download'] && UpHelper::on_server($this,$name . '.mp4')) { // v5.0
                    $attr_link['href'] = $name . '.mp4';
                    $attr_link['download'] = basename($name . '.mp4'); // on force le téléchargement
                    $attr_link['class'] = 'plyr-download';
                    $out .= UpHelper::set_attr_tag($this,'a', $attr_link, 'télécharger ' . basename($name . '.mp4'));
                }
                break;

            case 'audio':
                $out = '<audio controls style="opacity:0">';
                $out .= '<source src="' . $options['mp3'] . '" type="audio/mp3">';
                $out .= ($options['ogg'] > '') ? '<source src="' . $options['ogg'] . '" type="audio/ogg">' : '';
                $out .= '</audio>';
                if ($options['download'] && UpHelper::on_server($this,$name . '.mp3')) { // v5.0
                    $attr_link['href'] = $name . '.mp3';
                    $attr_link['download'] = basename($name . '.mp3'); // on force le téléchargement
                    $attr_link['class'] = 'plyr-download';
                    $out .= UpHelper::set_attr_tag($this,'a', $attr_link, 'télécharger ' . basename($name . '.mp3'));
                }
                break;
                break;

            default:
                $out = UpHelper::msg_inline($this,UpHelper::trad_keyword($this,'ARG_INVALID', $options[__class__]));
        }

        if (($options['class'] > '') || ($options['style'] > '')) {
            $inner = $out;
            // attributs du bloc principal
            $attr_main = array();
            $attr_main['class'] = $options['class'];
            $attr_main['style'] = $options['style'];

            // code en retour
            $out = UpHelper::set_attr_tag($this,'div', $attr_main);
            $out .= $inner;
            $out .= '</div>';
        }

        if (isset($this->options_user['debug'])) { // v2.7
            UpHelper::msg_info($this,$options['id'] . ' : <code>' . str_replace('<', '&lt;', $out) . '</code>');
        }
        return $out;
    }

    // run
}

// class
