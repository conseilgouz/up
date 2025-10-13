<?php

use Joomla\Component\Media\Administrator\Exception\FileExistsException;

/**
 * Explorer un dossier et ses sous-dossiers avec prévisualisation et téléchargement.
 *
 * syntaxe {up file-explorer=folder_relative_path_on_server}
 *
 * ##file## : chemin/nom.extension - pour copier/coller comme argument shortcode
 * ##dirname## : chemin (sans slash final)
 * ##basename## : nom et extension
 * ##filename## : nom sans extension (sans le point)
 * ##extension## : extension
 * ##relpath## : chemin relatif au chemin passé comme principal argument
 * ##size## : taille du fichier
 * ##date## : date dernière modification
 * ##icon## : icone ou vignette du fichier
 * ##download## : bouton pour télécharger
 * ##view-btn## : bouton pour voir le fichier dans une fenêtre modale
 * ##view## & ##/view## : balise a (ouvrante et fermante) avec attributs pour voir le fichier dans une fenêtre modale
 *
 * Motclé disponible pour le dossier en format liste (ul/li)
 * ##foldername## : nom du dossier (sans l'arboresccence)
 * ##folderpath## : chemin et nom du dossier (avec l'arboresccence)
 *
 * @version UP-5.1
 * @license <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @author Lomart
 * @credit <a href="https://www.jqueryscript.net/lightbox/full-featured-rbox.html" target"_blank">script rbox de batpad</a>
 * @tags   File
 *
 */

/*
 * v5.2  pas de preview sous Windows-8
 */
defined('_JEXEC') or die();

class file_explorer extends upAction
{
    public function init()
    {
        $this->load_file('jquery-rbox.css');
        $this->load_file('jquery-rbox.js');
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // chemin relatif du dossier sur le serveur
            'mask' => '', // masque de sélection des fichiers
            'file-exclude' => '', // fichiers non listés. (separateur=virgule)
            'folder-recursive' => '0', // niveaux d'exploration des sous-dossiers
            'view-root' => '0', // si main-tag=ul, affiche t'on la racine
            'view-unknow-ext' => 0, // 1: affiche tous les fichiers, 0: seules les extensions connues
            /* [st-type] type de contenu des fichiers. +ext1,ext2 : pour ajouter */
            'ext-none' => '', // liste des extensions sans préview, ni téléchargement
            'ext-download-only' => '', // fichiers non visualisables, téléchargement uniquement. défaut: zip,rar
            'ext-image' => '', // les fichiers images. défaut: jpg,png,webp,gif
            'ext-pdf' => '', // les fichiers pdf. défaut: pdf
            'ext-office' => '', // les fichiers bureautiques. défaut: doc,docx,odt,xls,xlsx,ods,pps,ppsx,pptx
            'ext-audio' => '', // les fichiers audios. défaut: mp3,ogg
            'ext-video' => '', // les fichiers vidéos. défaut: mp4,ogv
            'ext-ajax' => '', // fichiers gérés en ajax. défaut: txt,csv,html,url
            'ext-iframe' => '', // fichiers gérés comme iframe. défaut : aucun
            /* [st-tmpl] Modèle de mise en forme */
            'template' => '##icon## ##basename## ##view-btn## ##download##', // modèle de mise en forme du résultat
            'template-folder' => '[b]##foldername##[/b]', // modèle de mise en forme pour les dossier en vue liste
            /* [st-tag] Balises pour les blocs parents et enfants */
            'main-tag' => 'ul', // balise principale. indispensable pour utiliser id, class et style
            'item-tag' => 'li', // balise pour un fichier ou dossier. DIV si main-tag différent de UL
            /* [st-format] Format des éléments mot-clé */
            'date-format' => '%Y/%m/%d %H:%M', // format de la date
            'size-decimal' => '0', // nombre de décimales pour la taille du fichier
            'icon-thumbnail' => 1, // pour les images, on affiche une vignette
            'icon-size' => '32', // 16, 32 ou 48: taille de l'icone ou vignette
            'icon-path' => '', // le chemin vers vos fichiers icônes. ce dossier doit contenir 3 sous-dossiers 16,32 et 48
            /* [st-download] configuration bouton télécharger */
            'download-label' => '&#x1F4E5', // texte ou bbcode pour le bouton télécharger
            'download-style' => '', // classes et/ou styles du bouton pour télécharger
            /* [st-popup] configuration bouton voir */
            'view-label' => '&#x1F441', // texte du bouton pour voir le fichier
            'view-style' => '', // classes et/ou styles du bouton ou du lien pour voir le fichier
            /* [st-css] Styles CSS */
            'js-params' => '', // règles JS définies par le webmaster
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '', // style ajouté dans le HEAD de la page
            'id' => '' // Identifiant
        );

        // === correction saisie path et mask
        if (empty($this->options_user['mask'])) {
            if (is_dir($this->options_user[__class__])) {
                $this->options_user['mask'] = '*';
                $this->options_user[__class__] = str_replace('\\', '/', $this->options_user[__class__]);
            } else {
                // on extrait le chemin et le masque
                $this->options_user['mask'] = basename($this->options_user[__class__]);
                $this->options_user[__class__] = str_replace('\\', '/', dirname($this->options_user[__class__]));
            }
        }

        // fusion et controle des options
        $this->options = $this->ctrl_options($options_def);
        $this->options['template'] = $this->get_bbcode($this->options['template']);
        $this->options['template-folder'] = $this->get_bbcode($this->options['template-folder']);

        $this->options['file-exclude'] = array_map('trim', explode(',', strtolower($this->options['file-exclude'])));
        if (empty($this->options['icon-path'])) {
            $this->options['icon-path'] = $this->upPath . 'assets/img/file/';
        }
        $this->options['icon-path'] = rtrim($this->options['icon-path']);
        $this->options['icon-path'] .= '/' . $this->options['icon-size'] . '/';

        // =========== le code JS
        $this->load_jquery_code('$(".up-rbox").rbox({' . $this->options['js-params'] . '});');

        // extraction des composantes de la recherche
        $path = trim($this->options[__class__], ' /\\');
        $mask = $this->options['mask'];
        $this->basepath = $path;

        // annuler les echappements du shortcode UP
        $mask = str_replace('\[', '§{', $mask);
        $mask = str_replace('\]', '§}', $mask);
        $mask = str_replace(']', '}', $mask);
        $mask = str_replace('[', '{', $mask);
        $mask = str_replace('§{', '[', $mask);
        $mask = str_replace('§}', ']', $mask);

        // === relation extension et type des fichiers
        $this->ext_types = array();
        $this->init_ext_types('image', 'jpg,png,webp,gif');
        $this->init_ext_types('pdf', 'pdf'); // iframe
        $this->init_ext_types('office', 'doc,docx,odt,xls,xlsx,ods,pps,ppsx,pptx'); // iframe
        $this->init_ext_types('audio', 'mp3,ogg'); // html
        $this->init_ext_types('video', 'mp4,ogv,webm'); // video
        $this->init_ext_types('ajax', 'txt,csv,html,url'); // ajax
        $this->init_ext_types('iframe', ''); // iframe
        $this->init_ext_types('download-only', 'zip,rar');
        $this->init_ext_types('none', '');

        // === si le shortcode modifie au moins l'une des balises
        // si main-tag == ul -> item-tag=li
        // si main-tag != ul -> si item-tag == li -> item-tag=div
        if (strtolower($this->options['main-tag']) == 'ul') {
            $this->options['item-tag'] = 'li';
        } else {
            if (strtolower($this->options['item-tag']) == 'li') {
                $this->options['item-tag'] = 'div';
            }
        }

        // === ATTRIBUT POUR TEMPLATE
        // - view
        $this->attr_view = array();
        $this->get_attr_style($this->attr_view, $this->options['view-style']);
        $this->options['view-label'] = $this->get_bbcode($this->options['view-label']);
        // - download
        $this->attr_download = array();
        $this->get_attr_style($this->attr_download, $this->options['download-style']);
        $this->options['download-label'] = $this->get_bbcode($this->options['download-label']);
        // - image
        $iconsize = (int) $this->supertrim($this->options['icon-size']);
        if ($iconsize <= 16) {
            $this->options['icon-size'] = 16;
        } elseif ($iconsize >= 48) {
            $iconsize = 48;
        } else {
            $iconsize = 32;
        }
        $this->options['icon-size'] = $iconsize;
        $this->attr_style_icon_image = 'height:' . $iconsize . 'px;width:' . $iconsize . 'px;object-fit: cover;';

        // === CSS-HEAD
        $this->load_css_head($this->options['css-head']);

        // === Recupération de la liste
        $this->result = array();
        $treeview = (strtolower($this->options['main-tag']) == 'ul' || strtolower($this->options['item-tag']) == 'li');
        $this->glob_recursive($path, $mask, (int) $this->options['folder-recursive'], GLOB_BRACE, $treeview);

        if ($treeview) {
            array_unshift($this->result, '<li>' . $path . '<ul>');
            array_push($this->result, '</ul></li>');
            if (empty($this->options['view-root'])) {
                unset($this->result[0]);
            }
        }
        $out = (isset($this->result)) ? implode(PHP_EOL, $this->result) : '';

        // attributs du bloc principal
        if ($this->options['main-tag']) {
            $attr_main['id'] = $this->options['id'];
            $this->get_attr_style($attr_main, $this->options['class'], $this->options['style']);
            // code en retour
            $out = $this->set_attr_tag($this->options['main-tag'], $attr_main, $out);
        }

        return $out;
    } // run

    /*
     * glob_recursive
     * --------------
     * retourne les fichiers correspondants au masque
     * $max est le niveau d'exploration des sous-dossiers
     */
    public function glob_recursive($path, $mask, $max = 0, $flags = 0, $treeview = false)
    {
        $files = glob($path . '/' . $mask, $flags);
        if ($max > 0) {
            $max--;
            foreach (glob($path . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
                if ($treeview) {
                    $tmpl = $this->options['template-folder'];
                    $tmpl = str_ireplace("##foldername##", basename($dir), $tmpl);
                    $tmpl = str_ireplace("##folderpath##", $dir, $tmpl);

                    $this->result[] = '<li>' . $tmpl . '<ul>';
                }
                $this->glob_recursive($dir, $mask, $max, $flags, $treeview);
                if ($treeview) {
                    $this->result[] = '</ul></li>';
                }
            }
        }
        foreach ($files as $file) {
            if (! is_dir($file)) {
                $this->make_item($file);
            }
        }
    }

    /*
     * make_item
     * ---------
     * construit une ligne pour un fichier
     */
    public function make_item($file)
    {
        $rel_file = str_replace('\\', '/', $file); // chemin relatif
        $fileinfo = pathinfo($rel_file);
        if (in_array(strtolower($fileinfo['basename']), $this->options['file-exclude'])) {
            return '';
        }
        if (empty($this->options['view-unknow-ext']) && ! isset($this->ext_types[$fileinfo['extension']])) {
            return '';
        }

        $abs_file = JPATH_ROOT . '/' . $file; // chemin disque
        $url_file = $this->get_url_absolute($file); // chemin serveur
        $tag = $this->options['item-tag'];

        if (is_file($rel_file)) {
            $tmpl = $this->options['template'];
            $tmpl = str_ireplace("##file##", $rel_file, $tmpl);
            $tmpl = str_ireplace("##dirname##", $fileinfo['dirname'], $tmpl);
            $tmpl = str_ireplace("##basename##", $fileinfo['basename'], $tmpl);
            $tmpl = str_ireplace("##filename##", $fileinfo['filename'], $tmpl);
            $tmpl = str_ireplace("##extension##", $fileinfo['extension'], $tmpl);
            $tmpl = str_ireplace("##size##", $this->human_filesize($file, $this->options['size-decimal']), $tmpl);
            $tmpl = str_ireplace("##date##", $this->up_date_format(date('Y-m-d H:i:s', filemtime($abs_file)), $this->options['date-format']), $tmpl);
            $tmpl = str_ireplace("##relpath##", $rel_file, $tmpl);
            $ext = strtolower($fileinfo['extension']);
            // -- ICON
            if (strpos($tmpl, '##icon##') !== false) {
                if (! isset($this->ext_types[$ext])) {
                    $this->ext_types[$ext] = 'none';
                }
                if ($this->ext_types[$ext] == 'image' && $this->options['icon-thumbnail']) {
                    // vignette si image
                    $tmpl = str_ireplace("##icon##", '<img src="' . $file . '" style="' . $this->attr_style_icon_image . '">', $tmpl);
                } else {
                    $icon = $this->options['icon-path'] . $ext . '.png';
                    if (! file_exists($icon)) {
                        $icon = $this->actionPath . 'img/' . $this->options['icon-size'] . '/' . $this->ext_types[$ext] . '.png';
                    }
                    $tmpl = str_ireplace("##icon##", '<img src="' . $icon . '">', $tmpl);
                }
            }
            // -- POPUP
            if (strpos($tmpl, '##view') !== false) {
                $code = '';
                $attr = array();
                $attr['href'] = '#';
                $attr['class'] = 'up-rbox';
                // $attr['class'] = $this->options['id'] . ' ' . $this->options['view-style'];
                $attr['data-rbox-caption'] = str_replace($this->basepath . '/', '', $file);
                // typemodal : inline, iframe, image, video, ajax
                switch ($this->ext_types[$ext] ?? '') {
                    case 'image':
                        // $foo = str_replace($this->basepath, '', $fileinfo['dirname']);
                        // $attr['data-rbox-series'] = 'rbox-serie-' . str_replace($this->basepath, '', $fileinfo['dirname']);
                        $attr['data-rbox-type'] = 'image';
                        $attr['data-rbox-image'] = $this->get_url_absolute($file);
                        break;
                    case 'pdf':
                        $attr['data-rbox-type'] = "iframe";
                        $pdfjs_path = $this->get_url_absolute($this->upPath . 'actions/pdf/pdfjs/web/viewer.html');
                        $attr['data-rbox-iframe'] = $pdfjs_path . '?file=' . $url_file;
                        break;
                    case 'office':
                        $attr['data-rbox-type'] = "iframe";
                        $attr['data-rbox-iframe'] = 'https://view.officeapps.live.com/op/embed.aspx?src=' . $url_file;
                        // $attr['href'] = 'https://view.officeapps.live.com/op/embed.aspx?src=https%3A%2F%2Flomart.fr%2Fdemo%2Ftest.docx';
                        break;
                    case 'audio':
                        $attr['data-rbox-type'] = "html";
                        $attr['data-rbox-html'] = "<audio controls='controls' preload='auto' autoplay><source src='" . $url_file . "' type='audio/" . $ext . "'></audio>";
                        break;
                    case 'video':
                        $attr['data-rbox-type'] = "video";
                        $attr['data-rbox-video'] = $url_file;
                        $attr['data-rbox-autoplay'] = "true";
                        break;
                    case 'ajax':
                        $attr['data-rbox-type'] = 'ajax';
                        $attr['class'] .= ' rbox-ajax';
                        $attr['data-rbox-ajax'] = $url_file;
                        break;
                    case 'iframe':
                        $attr['data-rbox-type'] = 'iframe';
                        $attr['data-rbox-iframe'] = $url_file;
                        break;
                    case 'inline':
                        $attr['data-rbox-type'] = 'inline';
                        break;
                    default: // none, download-only et les inexistants
                        $attr['data-rbox-type'] = 'none';
                        break;
                }
                if ($attr['data-rbox-type'] == 'none' || $this->preview_ok() == false) {  // v5.2 si <= win8
                    $tmpl = str_ireplace("##view-btn##", '', $tmpl);
                    $tmpl = str_ireplace("##view##", '', $tmpl);
                    $tmpl = str_ireplace("##/view##", '', $tmpl);
                } else {
                    $code = $this->set_attr_tag('a', array_merge($this->attr_view, $attr), false);
                    $tmpl = str_ireplace("##view-btn##", $code . $this->options['view-label'] . '</a>', $tmpl);
                    $tmpl = str_ireplace("##view##", $code, $tmpl);
                    $tmpl = str_ireplace("##/view##", '</a>', $tmpl);
                }
            }
            // -- DOWNLOAD
            if (strpos($tmpl, '##download##') !== false) {
                $code = '';
                if ($this->ext_types[$ext] != 'none') {
                    $attr = array();
                    $attr['download'] = $fileinfo['basename'];
                    $attr['href'] = $url_file;
                    $attr['class'] = $this->options['download-style'];
                    // $attr['title'] = 'Download ' . $fileinfo['basename'];
                    $code = $this->set_attr_tag('a', $attr, $this->options['download-label']);
                }
                $tmpl = str_ireplace("##download##", $code, $tmpl);
            }

            // ajout tag
            $tmpl = '<' . $tag . '>' . $tmpl . '</' . $tag . '>';
        } else { // le dernier dossier du chemin
            $tmp = explode('/', $file);
            $lastdir = array_pop($tmp);
            $tmpl = '<' . $this->options['item-tag'] . '>' . $lastdir; // v2.9 pour php8
        }
        $this->result[] = $tmpl;
    }

    /*
     * human_filesize
     * --------------
     */
    public function human_filesize($file, $decimals = 2)
    {
        $bytes = filesize($file);
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }

    public function date_modif($file, $format = 'Y/m/d H:i')
    {
        return date($format, filemtime($file));
    }

    /*
     * initialisation des types de contenu
     * -----------------------------------
     */
    public function init_ext_types($type, $base)
    {
        $user_ext = $this->supertrim($this->options['ext-' . $type]);
        if (! empty($base) && (empty($user_ext) || $user_ext[0] == '+')) {
            foreach (array_map('trim', explode(',', $base)) as $ext) {
                $this->ext_types[$ext] = $type;
            }
        }
        $user_ext = trim($user_ext, '+');
        if (! empty($user_ext)) {
            foreach (array_map('trim', explode(',', $user_ext)) as $ext) {
                $this->ext_types[$ext] = $type;
            }
        }
    }

    /*
     * preview_ok()
     * return TRUE si l'OS ou navigateur visiteur permet pdfjs
     * XP: NT 5.1, W7: NT 6.1, W8: NT 6.2, W8.1: NT 6.3, W10: NT 10
     */

    public function preview_ok()
    {
        $ok = true;
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $regex = '/Windows NT ([0-9.]*)/';
        if (preg_match($regex, $userAgent, $version) == 1) {
            $ok = (intval($version[1]) > 6);
        }
        return $ok;
    }
}

// class
