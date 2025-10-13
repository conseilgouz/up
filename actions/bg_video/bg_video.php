<?php

/**
 * Affiche une video locale ou youtube en fond de site ou dans un bloc
 *
 * syntaxe :
 * fond site : {up bg-video=fichier video}
 * fond bloc : {up bg-video=fichier video}content{/up bg-video}
 *
 * @author   LOMART
 * @version  UP-1.5
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit Script <a href="https://github.com/lemehovskiy/videoBackground" target="_blank">videoBackground de lemehovskiy</a>
 * @tags    Body
 */
/* PSEUDO-CODE
  1/ Ajout options class & style dans attr_main + up-bgvid-inner -> attr_inner
  2/ Contrôle options
  - bg-position & bg-ratio -> $js_params & $css_position
  - si center -> up-center-outer dans attr_main
  _______________up-center-inner + options[center] dans attr_content
  - overlay sur video -> $attr_overlay = up-overlay + $options[bg-overlay]
  - overlay sur contenu -> css dans head
  3/ Origine et cible de la video
  - si video locale : $on_serveur = true
  - si contenu : attr_main = up-bgvid-bloc, sinon up-bgvid-body
  - si smartphone & $options[mobile]: $on_mobile = true
  4/ Code pour video
  - si $on mobile : $options[mobile] -> attr_inner
  - si $on serveur :  $video_code = <video>
  - si !$on serveur :  $video_code = <iframe>
  5/ Background video -> attr_inner
  6/ Init JS
  7/ Retour HTML
  - div.attr_main (up-bgvid-[bloc|body] up-center-outer)
  - ___ div.attr_inner
  - ______video
  - ___ div.attr_overlay
  - ___ div.attr_content  (up-center-inner)
 */

/*
 * v2.9 ajout option RGPD pour ne pas appliquer localement la règle générale
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

class bg_video extends upAction {

    function init() {
        // charger les ressources communes à toutes les instances de l'action
        $this->load_file('lib/bg_video.css');
        $this->load_file('lib/videoBackground.js');
        return true;
    }

    function run() {
        // ---- lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            /*video*/
            __class__ => '', // fichier video ou ID video Youtube.
            'poster' => '', // fichier image affiché avant chargement vidéo
            'mobile' => '', // image ou css si un appareil mobile est détecté
            /*[st-bg]paramètres de la vidéo*/
            'bg-position' => '50% 50%', // 50% 50% = position poster et video
            'bg-ratio' => '16/9', // proportion de la video
            'bg-overlay' => '', // calque superposé à l'image de fond (png, opacité, RGBA, CSS)
            'bg-color' => '', // couleur de fond sous la vidéo lors chargement
            /*[st-page]transparence du contenu de la page*/
            'page-selector' => '', // bloc sur contenu
            'page-overlay' => '', // background de page-selector (png, opacité, RGBA, CSS)
            /*[st-annexe]options secondaires*/
            'center' => '', // style et classe pour centrage vertical du contenu entre shortcodes
            'filter' => '', // conditions. Voir doc action filter  (v1.8)
            'id' => '', // identifiant
            'style' => '', // classes et style inline pour bloc créé
            'class' => '', // classes pour bloc créé (deprecated)
            'css-head' => '', // règles CSS définies par le webmaster (ajout dans le head)
            /*[rgpd]gestion tarteaucitron*/
            'height' => '400px', // hauteur d'une video Youtube ou Vimeo avec tarteaucitron
            'rgpd' => '1', // 0 pour ne pas appliquer la règle pour le RGPD
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);
        // === Filtrage
        if ($this->filter_ok($options['filter']) !== true) {
            return '';
        }

        // liste des formats acceptés
        $listVidExt = array('mp4', 'webm', 'ogv');
        $listImgExt = array('jpg', 'jpeg', 'png');

        // === init variables
        $attr_main = array();
        $attr_content = array();
        $on_mobile = false;
        $js_params = '';

        // === css-head
        $this->load_css_head($options['css-head']);

        // =========== Code HTML commun
        $attr_main['id'] = $options['id'];
        $this->get_attr_style($attr_main, $options['style']);
        $attr_inner['class'] = 'up-bgvid-inner';

        // =========== CONTROLE DES OPTIONS
        // --- bg-position & bg-ratio si saisie par redacteur
        $css_position = '50% 50%'; // default
        if (isset($this->options_user['bg-position'])) {
            $pos = str_replace(array('left', 'right', 'center', 'top', 'bottom'), array('0%', '100%', '50%', '0%', '100%'), $options['bg-position']);
            // si une seule valeur, on la duplique. si vide, on force au centre
            $pos = trim(str_replace('  ', ' ', $pos . ' ' . $pos . ' 50% 50%'));
            list($posX, $posY) = explode(' ', $pos);
            // on neutralise les valeurs non permises et on ajoute le signe %
            $js_params .= '"pos_x":"' . intval($posX) . '%' . '","pos_y":"' . intval($posY) . '%' . '"';
            $css_position = intval($posX) . '% ' . intval($posY) . '%';
        }
        if (isset($this->options_user['bg-ratio'])) {
            list($ratioX, $ratioY) = explode('/', $options['bg-ratio']);
            $js_params .= (isset($js_params)) ? $js_params . ',' : '';
            $js_params .= '"ratio_x":"' . $ratioX . '","ratio_y":"' . $ratioY . '"';
        }

        // --- classe pour centrage vertical
        if ($options['center']) {
            $this->add_class($attr_main['class'], 'up-center-outer');
            $this->get_attr_style($attr_content, 'up-center-inner', $options['center']);
        }

        // --- overlay sur video
        if ($options['bg-overlay']) {
            $val = $this->get_overlay($options['bg-overlay']);
            $this->get_attr_style($attr_overlay, 'background:' . $val, 'up-overlay');
        }

        // --- overlay sur page
        if ($options['page-overlay'] && $options['page-selector']) {
            $val = $this->get_overlay($options['page-overlay']);
            $this->load_css_head($options['page-selector'] . '{background:' . $val . '}');
        }

        // ====== origine et cible de la video
        $video = $options[__class__];
        $on_server = is_file($video); // VIDEO LOCALE
        $selector = '#' . $options['id'];
        if ($this->content) {
            $this->add_class($attr_main['class'], 'up-bgvid-bloc');
        } else {
            $this->add_class($attr_main['class'], 'up-bgvid-body');
        }

        // ====== si le device est un mobile
        // on affiche l'image fixe de l'option mobile
        if ($options['mobile']) {
            $client = Factory::getApplication()->client;
            $on_mobile = $client->mobile;
        }

        // ===== Code pour video
        $posterSource = '';
        switch (true) {
            case ($on_server && !$on_mobile) :
                //sur serveur et desktop
                // --- on recherche tous les fichiers de même nom
                $vidNoExt = substr($video, 0, strrpos($video, '.'));
                $tmp = glob($vidNoExt . '.*', GLOB_BRACE); //  | GLOB_NOSORT
                foreach ($tmp AS $vid) {
                    if ($vidNoExt == substr($vid, 0, strrpos($vid, '.'))) {
                        $ext = strtolower(substr($vid, strrpos($vid, '.') + 1));
                        if (in_array($ext, $listVidExt)) {
                            $vidSources[$ext] = $vid;
                        } else if (in_array($ext, $listImgExt)) {
                            $posterSource = $vid;
                        }
                    }
                }
                $attr_video['class'] = 'html-video';
                $attr_video['autoplay'] = null;
                $attr_video['loop'] = null;
                $attr_video['muted'] = null;
                $video_code = $this->set_attr_tag('video', $attr_video);
                foreach ($vidSources AS $ext => $vid) {
                    $video_code .= '<source src="' . $this->get_url_relative($vid) . '" type="video/' . $ext . '">';
                }
                $video_code .= 'Votre navigateur ne permet pas de lire les vidéos HTML5.';
                $video_code .= '</video>';
                break;

            case (!$on_server && !$on_mobile) :
                // sur youtube/vimeo et desktop
                // le code youtube est alphanumérique et vimeo numérique
                if (strval(intval($video)) == $video) {
                    // vimeo : https://help.vimeo.com/hc/en-us/articles/360001494447-Using-Player-Parameters
                    if ($this->tarteaucitron && $options['rgpd']) {
                        $tag = 'div';
                        $attr_iframe['class'] = 'vimeo_player';
                        $attr_iframe['videoID'] = $video;
                        $attr_iframe['autoplay'] = '1';
                        $attr_iframe['loop'] = '1';
                        $attr_iframe['mute'] = '1';
                        $attr_iframe['background'] = '1';
                        $attr_iframe['hd'] = '1';
                        $attr_iframe['width'] = '100%';
                        $attr_iframe['height'] = $options['height'];
                    } else {
                        $tag = 'iframe';
                        $attr_iframe['src'] = '//player.vimeo.com/video/' . $video . '?autoplay=1&muted=1&loop=1&background=1&hd=1';
                        $attr_iframe['class'] .= ' vimeo';
                    }
                } else {
                    // youtube : https://developers.google.com/youtube/player_parameters?hl=fr
                    if ($this->tarteaucitron && $options['rgpd']) {
                        $tag = 'div';
                        $attr_iframe['class'] = 'youtube_player';
                        $attr_iframe['videoID'] = $video;
                        $attr_iframe['width'] = '100%';
                        $attr_iframe['height'] = $options['height'];
                        $attr_iframe['rel'] = '0';
                        $attr_iframe['controls'] = '0';
                        $attr_iframe['autoplay'] = '1';
                        $attr_iframe['loop'] = '1';
                        $attr_iframe['mute'] = '1';
                        $attr_iframe['modestbranding'] = '1';
                    } else {
                        $tag = 'iframe';
                        $attr_iframe['src'] = '//www.youtube.com/embed/' . $video . '?rel=0&controls=0&autoplay=1&loop=1&mute=1&modestbranding=1';
                        $attr_iframe['allow'] = 'autoplay; encrypted-media';
                    }
                }
                $attr_iframe['frameborder'] = 0;
                $attr_iframe['allowfullscreen'] = null;

                $video_code = $this->set_attr_tag($tag, $attr_iframe, true);
                break;

            default :
                // sur mobile
                $video_code = '';
                break;
        }


        // ====== background sous video
        // dans l'ordre :
        // 1 - image dans option poster
        // 2 - image de même nom que la video
        // 3 - si mobile : valeur option si non vide (la video n'est pas affichée)
        // le fond par défaut
        $posterSource = ($options['poster']) ? $options['poster'] : $posterSource;
        if ($posterSource) {
            $css = 'background:url(\'' . $this->get_url_relative($posterSource) . '\') ';
            $css .= $options['bg-color'] . ' no-repeat ' . $css_position . '/cover';
        } else {
            $bgColor = ($options['bg-color']) ? $options['bg-color'] : '#aaa';
            $css = 'background-color:' . $bgColor;
        }

        // fond surchargé si info mobile
        if ($on_mobile) {
            if ($options['mobile'] != 1) {
                // infos dans option mobile prioritaire
                $css = $this->get_bg_mobile($options);
            } else {
                if ($posterSource == '' && $on_server) {
                    $vidNoExt = substr($video, 0, strrpos($video, '.'));
                    foreach ($listImgExt AS $ext) {
                        $img = $vidNoExt . '.' . $ext;
                        if (is_file($img)) {
                            $css = 'background:url(\'' . $img . '\') no-repeat ';
                            $css .= $options['bg-color'] . ' center/cover';
                            break;
                        }
                    }
                }
            }
        }
        $this->get_attr_style($attr_inner, $css);

        // ===== init JS
        if (!$on_mobile)
            $this->load_jquery_code('$("#' . $options['id'] . '").videoBackground({' . $js_params . '});');

        // ====== HTML pour retour
        // attr_main
        //    attr_inner
        //        video
        //    attr_overlay
        //    attr_content
        $out['tag'] = $this->set_attr_tag('div', $attr_main);
        $out['tag'] .= $this->set_attr_tag('div', $attr_inner);
        $out['tag'] .= $video_code;
        $out['tag'] .= '</div>'; // inner
        $out['tag'] .= (isset($attr_overlay)) ? $this->set_attr_tag('div', $attr_overlay, true) : '';
        if ($this->content) {
            $out['tag'] .= $this->set_attr_tag('div', $attr_content, $this->content);
        }
        $out['tag'] .= '</div>'; // main
        // c'est fini
        return $out;
    }

// run


    /*
     * get_overlay : retourne la valeur pour la propriété background d'un overley
     * si $val se termine par .png : image répétée
     * si $val est un nombre (70, 70%) : masque blanc transparent
     * si $val commence par # (#FF9999 70%) : masque coloré transparent
     * sinon $val est une règle CSS (linear-gradient ou radial-gradient)
     */

    function get_overlay($val) {
        if (strtolower(substr($val, strrpos($val, '.'))) == '.png') {
            // si fichier PNG
            if (dirname($val) == '.') {
                $val = $this->upPath . 'assets/overlay/' . $val;
                $val = str_replace('\\', '/', $val);
            }
            $val = 'url(\'' . Uri::root(true) . '/' . $val . '\') repeat';
        } else if ($val[0] == '#') {
            $rgba = $this->hex2rgba($val);
            $val = 'linear-gradient(' . $rgba . ' 0%,' . $rgba . ' 100%)';
        } else if ((float) $val > 0) {
            // si 70 ou 70% -> rgba(256,256,256,.7)
            $val = (float) $val;
            $val = $val / 100;
            $val = 'linear-gradient(rgba(240,240,240,' . $val . ') 0%,rgba(240,240,240,' . $val . ') 100%)';
        }
        // sinon, c'était une règle CSS
        return $val;
    }

    /*
     * hex2rgba : retourne une couleur au format #RRGGBBAA ou #RGBA au format rgba(r,g,b,a)
     * opacité à 1 par défaut
     */

    function hex2rgba($hex) {
        // on retire le #
        $hex = str_replace('#', '', $hex);
        // si #RGBA ou #RGB : on double en forcant à FF si besoin
        if (strlen($hex) <= 4) {
            $hex .= $hex . 'FFFF';
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2] . $hex[3] . $hex[3];
        }
        // si >4 et <8, on force à FF
        $hex = substr($hex . 'FFFF', 0, 8);
        // conversion en décimal
        $rgba = array_map('hexdec', str_split($hex, 2));
        // canal alpha sous forme coeff
        $rgba[3] = round($rgba[3] / 255, 1);
        // retour
        return 'rgba(' . implode(',', $rgba) . ')';
    }

    /*
     * retourne le CSS pour le background sur mobile
     * $opt_mobile peut contenir :
     * - rien : on n'affiche pas la video, mais le fond prévu (poster bg-color)
     * - une image
     * - des propriétés css pour background : url(image.jpg) repeat-y
     * - du css : background:...;color:...
     */

    function get_bg_mobile($options) {
        $opt_mobile = $options['mobile'];
        if (is_file($opt_mobile)) {
            // image existante
            list($w, $h) = getimagesize($opt_mobile);
            if (($w + $h) < 200) {
                $out = 'background:url(\'' . $opt_mobile . '\') repeat ' . $options['bg-color'];
            } else {
                $out = 'background:url(\'' . $opt_mobile . '\') no-repeat ' . $options['bg-color'] . ' center/cover';
            }
        } else {
            $out = (substr($opt_mobile, 0, 11) == 'background:') ? '' : 'background:';
            $out .= $opt_mobile;
        }

        return $out;
    }

}

// class























