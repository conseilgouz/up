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
 * @version UP-5.2
 * @license <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @author Lomart
 * @tags   file
 *
 */

/*
 */
defined('_JEXEC') or die();

class file_explorer extends upAction
{

    function init()
    {
        $this->load_file('../modal/flashy.css');
        $this->load_file('effect.css');
        $this->load_file('../modal/jquery.flashy.min.js');
        return true;
    }

    function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // chemin relatif du dossier sur le serveur
            'mask' => '', // masque de sélection des fichiers
            'file-exclude' => '', // fichiers non listés. (separateur=virgule)
            'folder-recursive' => '0', // niveaux d'exploration des sous-dossiers
            'root-view' => '0', // si main-tag=ul, affiche t'on la racine
            /* [st-type] type de contenu des fichiers */
            'ext-none' => '', // ni préview, ni téléchargement
            'ext-download-only' => '', // fichiers non visualisable, téléchargement uniquement
            'ext-image' => '', // jpg,png,webp,gif
            'ext-text' => '', // txt,csv
            'ext-pdf' => '', // pdf
            'ext-office' => '', // doc,docx,odt,xls,xlsx,ods,pps,ppsx,pptx
            'ext-audio' => '', // mp3,ogg
            'ext-video' => '', // mp4,ogv
            'ext-ajax' => '', //
            'ext-iframe' => '', //
            'ext-inline' => '', //
            /* [st-tmpl] Modèle de mise en forme */
            'template' => '##basename##', // modèle de mise en forme du résultat
            'template-folder' => '[b]##foldername##[/b]', // modèle de mise en forme pour les dossier en vue liste
            /* [st-tag] Balises pour les blocs parents et enfants */
            'main-tag' => '', // balise principale. indispensable pour utiliser id, class et style
            'item-tag' => 'p', // balise pour un fichier ou dossier
            /* [st-format] Format des éléments mot-clé */
            'date-format' => '%Y/%m/%d %H:%M', // format de la date
            'decimal' => '0', // nombre de décimales pour la taille du fichier
            'icon-thumbnail' => 1, // pour les images, on affiche une vignette
            'icon-size' => '32', // 48 ou 16 taille de l'icone ou vignette
            /* [st-download] configuration bouton télécharger */
            'download-label' => '&#x1F4E5', //
            'download-style' => '', // classes et/ou styles du bouton pour télécharger
            /* [st-popup] configuration bouton voir */
            'view-label' => '&#x1F441', // texte du bouton pour voir le fichier
            'view-style' => '', // classes et/ou styles du bouton ou du lien pour voir le fichier
            'close-left' => 0, // croix de fermeture en haut à gauche. haut-droite par défaut
            'zoom-suffix' => '-mini', // suffixe pour les versions vignettes des images
            'base-js-params' => '', // règles JS définies par le webmaster (ajout dans init JS)
            /* [st-css] Styles CSS */
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '', // style ajouté dans le HEAD de la page
            'id' => '' // Identifiant
        );
        // ===== parametres attendus par le script JS
        // important: valeurs par defaut entre guillemets pour les chaines
        // attention: le nom des options est transmis au script sous la forme indique ici.
        $js_options_def = array(
            /*[st-js] Options pour Javascript */
            'overlayClose' => 1, // 1 pour fermer la fenêtre modale en cliquant sur la zone grisée autour du contenu
            'videoAutoPlay' => 0, // 1 pour démarrer la video à l'ouverture du popup
            'gallery' => 0, // 0 pour traiter les images individuellement
            'title' => 1, // afficher le titre
            'width' => '', // largeur avec unité. Ex: 80%, 500px, ...
            'height' => '' // hauteur avec unité. Ex: 80%, 500px, ...
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
        $this->options = $this->ctrl_options($options_def, $js_options_def);
        $this->ctrl_unit($this->options['width'], '%, px');
        $this->ctrl_unit($this->options['height'], 'px, %');

        // =========== le code JS
        // les options saisies par l'utilisateur concernant le script JS
        // cela evite de toutes les renvoyer au script JS
        // $js_options = $this->only_using_options($js_options_def);

        // -- conversion en chaine Json
        // il existe 2 modes: mode1=normal, mode2=sans guillemets
        $js_params = $this->json_arrtostr($js_options_def, 2);
        if ($this->options['base-js-params']) {
            $js_params = str_replace('{', '{' . $this->options['base-js-params'] . ',', $js_params);
        }
        $id = $this->options['id'];
        // -- init JS
        $this->load_jquery_code('$(".' . $id . '").flashy(' . $js_params . ');');

        // === CSS inline
        if ($this->options['close-left']) {
            // $css = '#' . $id . ' .flashy-overlay .flashy-close{left:0}';
            // pour toutes les occurrences
            $css = '.flashy-overlay .flashy-close{left:0}';
            $this->load_css_head($css);
        }

        // === fusion et controle des options
        $this->options = $this->ctrl_options($options_def);
        $this->options['template'] = $this->get_bbcode($this->options['template']);
        $this->options['template-folder'] = $this->get_bbcode($this->options['template-folder']);

        // extraction des composantes de la recherche
        $path = $this->options[__class__];
        $mask = $this->options['mask'];

        // annuler les echappements du shortcode UP
        $mask = str_replace('\[', '§{', $mask);
        $mask = str_replace('\]', '§}', $mask);
        $mask = str_replace(']', '}', $mask);
        $mask = str_replace('[', '{', $mask);
        $mask = str_replace('§{', '[', $mask);
        $mask = str_replace('§}', ']', $mask);

        // === relation extension et type des fichiers
        $this->ext_types = array();
        $this->init_ext_types('none', '');
        $this->init_ext_types('download-only', 'zip,rar');
        $this->init_ext_types('image', 'jpg,png,webp,gif');
        $this->init_ext_types('text', 'txt,csv');
        $this->init_ext_types('pdf', 'pdf');
        $this->init_ext_types('office', 'doc,docx,odt,xls,xlsx,ods,pps,ppsx,pptx');
        $this->init_ext_types('audio', 'mp3,ogg');
        $this->init_ext_types('video', 'mp4,ogv,webm');
        $this->init_ext_types('ajax', '');
        $this->init_ext_types('iframe', 'html,url,csv');
        $this->init_ext_types('inline', '');

        // === force tag si liste
        if (strtolower($this->options['main-tag']) == 'ul' || strtolower($this->options['item-tag']) == 'li') {
            $this->options['main-tag'] = 'ul';
            $this->options['item-tag'] = 'li';
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
            if (empty($this->options['root-view']))
                unset($this->result[0]);
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
    }

    /*
     * glob_recursive
     * --------------
     * retourne les fichiers correspondants au masque
     * $max est le niveau d'exploration des sous-dossiers
     */
    function glob_recursive($path, $mask, $max = 0, $flags = 0, $treeview = false)
    {
        $files = glob($path . '/' . $mask, $flags);
        if ($max > 0) {
            $max --;
            foreach (glob($path . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
                if ($treeview) {
                    $tmpl = $this->options['template-folder'];
                    $tmpl = str_ireplace("##foldername##", basename($dir), $tmpl);
                    $tmpl = str_ireplace("##folderpath##", $dir, $tmpl);

                    $this->result[] = '<li>' . $tmpl . '<ul>';
                }
                $this->glob_recursive($dir, $mask, $max, $flags, $treeview);
                if ($treeview)
                    $this->result[] = '</ul></li>';
            }
        }
        foreach ($files as $file) {
            if (! is_dir($file))
                $this->make_item($file);
        }
    }

    /*
     * make_item
     * ---------
     * construit une ligne pour un fichier
     */
    function make_item($file)
    {
        $file = str_replace('\\', '/', $file);
        $abs_file = JPATH_ROOT . '/' . $file;
        $tag = $this->options['item-tag'];
        if (is_file($file)) {
            $tmpl = $this->options['template'];
            $tmpl = str_ireplace("##file##", $file, $tmpl);
            if (strpos($tmpl, '##') !== false) {
                $info = pathinfo($this->get_url_absolute($file));
                $tmpl = str_ireplace("##dirname##", $info['dirname'], $tmpl);
                $tmpl = str_ireplace("##basename##", $info['basename'], $tmpl);
                $tmpl = str_ireplace("##filename##", $info['filename'], $tmpl);
                $tmpl = str_ireplace("##extension##", $info['extension'], $tmpl);
                $tmpl = str_ireplace("##size##", $this->human_filesize($file, $this->options['decimal']), $tmpl);
                $tmpl = str_ireplace("##date##", $this->up_date_format(date('Y-m-d H:i:s', filemtime($abs_file)), $this->options['date-format']), $tmpl);

                $relpath = trim(substr($info['dirname'], strlen($this->options[__class__])), "/");
                $relpath .= ($relpath) ? '/' : '';
                $tmpl = str_ireplace("##relpath##", $relpath, $tmpl);
                $ext = strtolower($info['extension']);
                // -- ICON
                if (strpos($tmpl, '##icon##') !== false) {
                    if (! isset($this->ext_types[$ext]))
                        $this->ext_types[$ext] = 'none';
                    if ($this->ext_types[$ext] == 'image' && $this->options['icon-thumbnail']) {
                        // vignette si image
                        $tmpl = str_ireplace("##icon##", '<img src="' . $file . '" style="' . $this->attr_style_icon_image . '">', $tmpl);
                    } else {
                        $icon = $this->upPath . 'assets/img/file/' . $this->options['icon-size'] . '/' . $ext . '.png';
                        if (! $icon) {
                            $icon = $this->actionPath . 'img/' . $this->options['icon-size'] . '/' . $this->ext_types[$ext] . '.png';
                        }
                        $tmpl = str_ireplace("##icon##", '<img src="' . $icon . '">', $tmpl);
                    }
                }
                // -- POPUP
                if (strpos($tmpl, '##view') !== false) {
                    $code = '';
                    $attr = array();
                    $attr['href'] = $this->get_url_absolute($file);
                    $attr['class'] = $this->options['id'] . ' ' . $this->options['view-style'];
                    $attr['title'] = $this->link_humanize($file);
                    // typemodal : inline, iframe, image, video, ajax
                    switch ($this->ext_types[$ext] ?? '') {
                        case 'image':
                            $attr['data-flashy-type'] = 'image';
                            break;
                        case 'text':
                            $attr['data-flashy-type'] = 'iframe';
                            break;
                        case 'pdf':
                            $attr['href'] = $this->upPath . 'actions/pdf/pdfjs/web/viewer.html?file=' . $this->get_url_absolute($file);
                            $attr['data-flashy-type'] = 'iframe';
                            break;
                        case 'office':
                            $attr['href'] = 'https://view.officeapps.live.com/op/embed.aspx?src=' . $this->get_url_absolute($file);
//                             $attr['href'] = 'https://view.officeapps.live.com/op/embed.aspx?src=https%3A%2F%2Flomart.fr%2Fdemo%2Ftest.docx';
                            $attr['data-flashy-type'] = 'iframe';
                            break;
                        case 'audio':
                            $attr['data-flashy-type'] = 'iframe';
                            break;
                        case 'video':
                            // $attr['href'] = '<video><source src="'. $this->get_url_absolute($file) .'" type="video/'.$ext.'">'.'<div>no support</div></video>';
                            $attr['data-flashy-type'] = 'iframe';
                            break;
                        case 'ajax':
                            $attr['data-flashy-type'] = 'iframe';
                            break;
                        case 'iframe':
                            $attr['data-flashy-type'] = 'iframe';
                            break;
                        case 'inline':
                            $attr['data-flashy-type'] = 'iframe';
                            break;
                        default: // none, download-only et les inexistants
                            $attr['data-flashy-type'] = 'none';
                            break;
                    }
                    if ($attr['data-flashy-type'] == 'none') {
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
                    $attr = array();
                    $attr['download'] = $info['basename'];
                    $attr['href'] = $this->get_url_absolute($file);
                    $attr['class'] = $this->options['download-style'];
                    $attr['title'] = $this->link_humanize($file);
                    $code = $this->set_attr_tag('a', $attr, $this->options['download-label']);
                    $tmpl = str_ireplace("##download##", $code, $tmpl);
                }
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
    function human_filesize($file, $decimals = 2)
    {
        $bytes = filesize($file);
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }

    function date_modif($file, $format = 'Y/m/d H:i')
    {
        return date($format, filemtime($file));
    }

    /*
     * initialisation des types de contenu
     * -----------------------------------
     */
    function init_ext_types($type, $base)
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

    // run
}

// class
