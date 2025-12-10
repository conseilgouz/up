<?php

/**
 * affiche un lecteur (HTML5) pour un ou des fichiers audio hébergé sur le serveur
 *
 * il suffit d'indiquer le chemin et le nom du fichier (avec ou sans extension)
 * pour utiliser toutes les fichiers audio de ce nom dans le dossier.
 * Si le nom du fichier n'est pas indiqué ou contient des jokers,
 * tous les fichiers audios correspondants seront retournés
 * Supporte les formats audio : mp3,ogg
 * Si une image (jpg,png,webp,gif) existe, elle sera utilisé comme preview (poster)
 *
 * syntaxe {up media-audio=chemin_vers_audios}
 * Mot-clés pour le template : ##player##, ##title##, ##image##, ##info##, ##download##
 *
 * @version  UP-5.1
 * @license  <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @author   lomart
 * @tags     Media
 *
 */
defined('_JEXEC') or die();

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class media_audio extends Lomart\Plugin\Content\Up\Extension\Up
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        UpHelper::load_file($this,'media_audio.css');
        UpHelper::load_file($this,'media_audio.js');
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        UpHelper::set_demopage($this);

        $options_def = array(
            __class__ => '', // chemin et nom du fichier vidéo ou dossier. caractères joker autorisés
            /* [ST-config] configuration du lecteur vidéo */
            'autoplay' => 0, // lancement automatique du 1er fichier audio
            'muted' => 0, // coupe le son
            'loop' => 0, // joue le fichier audio en boucle
            'controls' => 1, // affiche les boutons de commande
            'preload' => 'auto', // mode de chargement du fichier audio : none, metadata, auto.
            /* [ST-template] Mise en forme résultat */
            'template' => '##title## ##player##', // modèle de mise en forme d'un item
            'main-tag' => '0', // balise pour le bmoc principal
            'main-style' => '', // classe(s) et style pour le bloc principal
            'item-tag' => 'div', // balise pour un fichier. forcé à DIV si vide
            'item-style' => '', // classe(s) et style pour les blocs des différents morceaux
            'player-style' => '', // classe(s) et style pour le lecteur audio
            'title-style' => '', // classe(s) et style pour la légende
            'title-tag' => 'div', // balise pour le titre du morceau
            'image-default' => '', // image utilisée par défaut pour tous les fichiers
            'image-types' => 'jpg,jpeg,png,gif,webp', // liste extension des fichiers images acceptés
            'image-style' => '', // classe(s) et style pour la légende
            'info-style' => '', // classe(s) et style pour les infos
            'download-tag' => 'a', // balise pour bouton download. forcé à DIV si vide
            'download-text' => 'lang[en=Download;fr=Télécharger]', // texte pour bouton téléchager
            'download-style' => '', // classe(s) et style pour la légende. ex: btn btn-primary
            /* [ST-param] Types des fichiers audio acceptés */
            'types' => 'ogg:ogg;mp3:mpeg;MP3:mpeg', // liste extension fichier et type mime. défaut: ogg:ogg;mp3:mpeg;MP3:mpeg
            'codecs' => '', // liste type et codec supportés. ex: ogg:opus,vorbis
            /* [ST-msg] Messages si erreur */
            'no-audio' => 'lang[en=no audio;fr=aucun fichier audio]', // message si audio non trouvée
            'no-support' => 'lang[en=Your web browser does not support HTML5 audio;fr=Votre navigateur Web ne prend pas en charge l\'audio HTML5', // message si type audio non supporté
            /* [ST-Style] styles */
            'id' => 'identifiant instance',
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);

        // === CSS-HEAD
        UpHelper::load_css_head($this,$options['css-head']);

        // ===================
        // === analyse options
        // ===================
        $audio_types = UpHelper::strtoarray($this,$options['types'], ';', ':', false);
        $ext_codecs = UpHelper::strtoarray($this,$options['codecs'], ';', ':', false);

        $image_types = explode(';', $options['image-types']);
        if (empty($options['image-default'])) {
            //             $options['image-default'] = $this->actionPath.'media_audio.png' ;
            $options['image-default'] = UpHelper::get_custom_path($this,'audio-default.png') ;
        }

        $options['template'] = str_replace('"', '\'', UpHelper::get_bbcode($this,$options['template']));

        // ============================
        // === liste des fichiers audio
        // ============================
        $mask = ''; // masque pour glob
        $filelist = array(); // liste des fichiers audio retournée par glob
        $file_info = array(); // array[filename]=> list-extensions
        $filepath = ''; // chemin du dossier

        if (is_dir($options[__class__])) {
            $options[__class__] .= '/*';
        }
        $mask = pathinfo($options[__class__]);
        $filepath = $mask['dirname'];
        $mask = $filepath . '/' . $mask['filename'];
        $mask .= '.{' . implode(',', array_keys($audio_types)) . '}';
        $filelist = glob($mask, GLOB_BRACE);

        if (empty($filelist)) {
            return $options['no-audio'];
        }

        sort($filelist);

        foreach ($filelist as $file) {
            $file_info[pathinfo($file, PATHINFO_FILENAME)][] = pathinfo($file, PATHINFO_EXTENSION);
        }
        // $nbfile = count($filelist);
        $nbfileunique = count($file_info);

        // ============================
        // === attributs HTML des blocs
        // ============================

        // le bloc main est forcé en div si 0 et plusieurs fichiers (box)
        if (empty($options['main-tag']) && (count($file_info) > 1)) {
            $options['main-tag'] = 'div';
        }
        if (empty($options['main-tag'])) {
            $attr_item['id'] = $options['id'];
        } else {
            $attr_main['id'] = $options['id'];
        }

        // -- attributs du bloc principal
        $attr_main['class'] = 'upaudio-main';
        UpHelper::get_attr_style($this,$attr_main, $options['main-style']);

        // -- attributs du bloc d'un fichier
        if (empty($options['item-tag']) && !empty($options['item-style'])) {
            $options['item-tag'] = 'div';
        }
        $attr_item['class'] = 'upaudio-item';
        UpHelper::get_attr_style($this,$attr_item, $options['item-style']);

        // -- attributs du player HTML
        $attr_player['class'] = 'upaudio-player';
        UpHelper::get_attr_style($this,$attr_player, $options['player-style']);
        if (! empty($options['autoplay'])) {
            $attr_player['autoplay'] = null;
        }
        if (! empty($options['muted'])) {
            $attr_player['muted'] = null;
        }
        if (! empty($options['loop'])) {
            $attr_player['loop'] = null;
        }
        if (! empty($options['controls'])) {
            $attr_player['controls'] = null;
        }
        $attr_player['preload'] = (empty($options['preload'])) ? 'auto' : $options['preload'];

        // -- attributs du bloc titre
        $attr_title['class'] = 'upaudio-title';
        UpHelper::get_attr_style($this,$attr_title, $options['title-style']);

        // -- attributs du bloc image
        $attr_image['class'] = 'upaudio-image';
        UpHelper::get_attr_style($this,$attr_image, $options['image-style']);

        // -- attributs du bloc téléchargement
        $attr_download['download'] = null; // attribut sans argument pour forcer le téléchargement
        $attr_download['class'] = 'upaudio-download';
        UpHelper::get_attr_style($this,$attr_download, $options['download-style']);

        // -- attributs du bloc téléchargement
        $attr_info['class'] = 'upaudio-info' ;

        // ============================
        // === construction HTML retour
        // ============================

        foreach ($file_info as $name => $exts) {
            $template = $options['template'];
            if (stripos($template, '##player') !== false) {
                $player = '';
                foreach ($exts as $ext) {
                    $src = $filepath . '/' . $name . '.' . $ext;
                    $player .= '<source src="' . $src . '" type="audio/' . $audio_types[$ext] . '">';
                }
                $player .= '<p class="upaudio-nosupport">' . $options['no-support'] . '</p>' . PHP_EOL;
                $player = UpHelper::set_attr_tag($this,'audio', $attr_player, $player);
                unset($attr_player['autoplay']);
                UpHelper::kw_replace($this,$template, 'player', $player);
            }
            if (strpos($template, '##title') !== false) {
                $tmp = UpHelper::set_attr_tag($this,$options['title-tag'], $attr_title, UpHelper::link_humanize($this,$name));
                UpHelper::kw_replace($this,$template, 'title', $tmp);
            }
            if (strpos($template, '##image') !== false) {
                $mask = dirname($options[__class__]) . '/' . $name . '.{' . $options['image-types'] . '}';
                $images = glob($mask, GLOB_BRACE);
                $attr_image['src'] = ($images) ? $images[0] : $options['image-default'];
                $tmp = ($attr_image['src']) ? UpHelper::set_attr_tag($this,'img', $attr_image) : '';
                UpHelper::kw_replace($this,$template, 'image', $tmp);
            }
            if (strpos($template, '##info') !== false) {
                $fileinfo = $filepath . '/' . $name . '.info';
                $info = '';
                if (file_exists($fileinfo)) {
                    $info = file_get_contents($fileinfo);
                    if ($options['info-style']) {
                        $info = UpHelper::set_attr_tag($this,'div', $attr_info, $info);
                    }
                }
                UpHelper::kw_replace($this,$template, 'info', $info);
            }

            if (strpos($template, '##download') !== false) {
                $attr_download['href'] = $filepath . '/' . $name . '.' . $exts[0];
                $tmp = UpHelper::set_attr_tag($this,$options['download-tag'], $attr_download, $options['download-text']);
                UpHelper::kw_replace($this,$template, 'download', PHP_EOL . $tmp);
            }
            $out[] = UpHelper::set_attr_tag($this,$options['item-tag'], $attr_item, $template);
        }

        // ajout du bloc main
        $html = implode(PHP_EOL, $out);
        if (! empty($options['main-tag'])) {
            $html = UpHelper::set_attr_tag($this,$options['main-tag'], $attr_main, PHP_EOL . $html . PHP_EOL);
        }

        // code en retour
        return  $html;
    }

    // run
}

// class
