<?php

/**
 * affiche une vidéo locale en HTML5
 *
 * il suffit d'indiquer le chemin et le nom du fichier (avec ou sans extension)
 * pour utiliser toutes les vidéos de ce nom dans le dossier.
 * Si le nom du fichier n'est pas indiqué ou contient des jokers,
 * toutes les vidéos correspondantes seront retournées
 * Supporte les formats video : mp4,webm,ogg
 * Si une image (jpg,png,webp,gif) existe, elle sera utilisé comme preview (poster)
 *
 * syntaxe {up media-video=chemin_vers_videos}
 *
 * @version  UP-3.1
 * @license  <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @author   lomart
 * @tags     Media
 *
 */
defined('_JEXEC') or die();

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class media_video extends Lomart\Plugin\Content\Up\Extension\Up
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        UpHelper::load_file($this,'media_video.css');
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        UpHelper::set_demopage($this);

        $options_def = array(
            __class__ => '', // chemin et nom du fichier vidéo. caractères joker autorisés
            /* [ST-config] configuration du lecteur vidéo */
            'autoplay' => 0, // lancement automatique de la 1ere video
            'muted' => 0, // coupe le son
            'loop' => 0, // joue la vidéo en boucle
            'controls' => 1, // affiche les boutons de commande
            'preload' => 'auto', // none, metadata, auto.
            'legend' => '', // texte affiché au dessous des vidéo ou 1 pour le nom humanisé de chaque vidéo
            'legend-style' => '', // classe(s) et style pour la légende
            /* [ST-param] Types des vidéos acceptées */
            'types' => 'mp4:mp4;webm:webm;ogv:ogg', // liste extension fichier et type mime
            'codecs' => '', // liste type et codec supportés. ex: ogg:theora,vorbis; webm:vp8.0,vorbis
            /* [ST-msg] Messages si erreur */
            'no-video' => 'lang[en=no video;fr=aucune vidéo]', // message si video non trouvée
            'no-support' => 'lang[en=Your web browser does not support HTML5 video;fr=Votre navigateur Web ne prend pas en charge la vidéo HTML5', // message si video non supportée
            /* [ST-Style] styles */
            'id' => '',
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);

        // === CSS-HEAD
        UpHelper::load_css_head($this,$options['css-head']);

        // === analyse options
        $ext_codecs = UpHelper::strtoarray($this,$options['codecs'], ';', ':', false);
        $options['types'] = strtolower($options['types']) . ';jpg:img;png:img;gif:img;webp:img';
        $ext_type = UpHelper::strtoarray($this,$options['types'], ';', ':', false);

        $attr_legend['class'] = 'upvideo-caption';
        UpHelper::get_attr_style($this,$attr_legend, $options['legend-style']);

        // attributs du bloc principal
        $attr_main['class'] = 'upvideo-box';
        UpHelper::get_attr_style($this,$attr_main, $options['class'], $options['style']);

        // === liste des fichiers video
        if (is_dir($options[__class__])) {
            $options[__class__] .= '*';
        }
        $filename = pathinfo($options[__class__]);
        $filename = $filename['dirname'] . '/' . $filename['filename'];
        $filelist = glob($filename . '.*');
        natcasesort($filelist);
        $nbfile = count($filelist);
        if (empty($filelist)) {
            return $options['no-video'];
        }

        // === code pour video
        $vidoptions['autoplay'] = (empty($options['autoplay'])) ? '' : 'autoplay';
        $vidoptions['muted'] = (empty($options['muted'])) ? '' : 'muted';
        $vidoptions['loop'] = (empty($options['loop'])) ? '' : 'loop';
        $vidoptions['controls'] = (empty($options['controls'])) ? '' : 'controls';
        $vidoptions['preload'] = (empty($options['preload'])) ? '' : 'preload="' . $options['preload'] . '"';

        $current = pathinfo($filelist[0], PATHINFO_FILENAME);
        $sources = array();
        $i = 0;
        do {
            // ---
            $file = ($i >= $nbfile) ? 'xxxxx.xxx' : $filelist[$i];
            $fileinfo = pathinfo($file);
            $ext = strtolower($fileinfo['extension']);
            // ---
            if ($fileinfo['filename'] != $current) {
                if (! empty($sources)) { // pas les images seules
                    $out[] = UpHelper::set_attr_tag($this,'div', $attr_main);
                    $out[] = '<video ' . implode(' ', $vidoptions) . '>';
                    $out = array_merge($out, $sources);
                    $out[] = '<div class="upvideo-nosupport">' . $options['no-support'] . '</div>';
                    $out[] = '</video>';
                    if ($options['legend']) {
                        if ($options['legend'] == 1) {
                            $legend = UpHelper::link_humanize($this,$current);
                        } else {
                            $legend = UpHelper::get_bbcode($this,$options['legend']);
                        }
                        $out[] = UpHelper::set_attr_tag($this,'div', $attr_legend, $legend);
                    }
                    $out[] = '</div>';
                }
                // --- fin ?
                if ($i == $nbfile) {
                    break;
                }
                // --- reinit
                $current = $fileinfo['filename'];
                $sources = array();
                if (! empty($out)) {
                    unset($vidoptions['autoplay']);
                } // la première uniquement
                if (! empty($vidoptions['poster']) && (pathinfo($vidoptions['poster'], PATHINFO_FILENAME) != $current)) {
                    unset($vidoptions['poster']);
                }
            }
            // ---
            if (isset($ext_type[$ext])) {
                if ($ext_type[$ext] == 'img') {
                    $vidoptions['poster='] = 'poster="' . $file . '"';
                } else {
                    $type = $ext_type[$ext];
                    if (isset($ext_codecs[$type])) {
                        $type .= '; codecs=\'' . $ext_codecs[$type] . '\'';
                    }
                    $sources[] = '<source src="' . $file . '" type="video/' . $type . '">';
                }
            }
            $i++;
        } while (($i <= $nbfile));

        // code en retour
        return implode(PHP_EOL, $out);
    }

    // run
}

// class
