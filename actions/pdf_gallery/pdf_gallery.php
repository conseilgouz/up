<?php

/**
 * Affiche une liste des PDF contenus dans un dossier
 *
 * syntaxe {up pdf-gallery=dossier}
 * syntaxe {up pdf-gallery=dossier}template{/up pdf-gallery}
 *
 * Template pour définir le rendu
 * ##name## : nom et extension du fichier sans le prefixe date
 * ##full-name## : nom et extension du fichier avec le prefixe date
 * ##human-name## : nom du fichier sans les tirets
 * ##size## : taille du fichier
 * ##date## : date du fichier ou prefixe date du nom de fichier
 * ##info##  : fichier .info de même nom que le PDF avec texte descriptif
 * ##image## : fichier jpg ou png de même nom que le PDF
 * ##image-view## : idem ##image## avec lien pour afficher PDF dans fenêtre modale
 * ##btn-view##     : lien pour afficher PDF dans fenêtre modale. Texte selon btn-view-text
 * ##btn-download##, ##name-download## : lien pour télécharger le PDF. Texte selon btn-download-text
 * ##preview##     : vue du PDF avec la méthode jsviewer de l'action PDF.
 * ATTENTION ##preview## charge tous les fichiers PDF lors de l'affichage de la page
 *
 * @author   LOMART
 * @version  UP-2.8
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit  <a href="https://mozilla.github.io/pdf.js/" target="_blank">Mozilla PDF.js</a>
 * @tags  File
 */
/*
 * v2.81 - ajout option popup-width et popup-height pour modifier la taille de la fenêtre modale de visualisation du PDF
 * v2.9 - le template peut être mis comme contenu
 * v5.0 - fix date
 * v5.2 - preview inactif si user en Win8
 * v5.3.1 - ajout de l'option flip pour visualiser les pdf en mode magazine
 */

/*
 * --- notes
 * voir : https://rootslabs.net/blog/538-embarquer-pdf-page-web-pdf-js
 */
defined('_JEXEC') or die();

class pdf_gallery extends upAction
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // chemin du dossier contenant les PDF
            'file-mask' => '', // pour sélectionner les fichiers d'un dossier. ex: fic-*
            'sort-by' => 'name', // tri des fichiers. name ou date. Voir la demo
            'sort-order' => 'asc', // sens du tri. asc ou desc
            'msg-no-file' => 'en=no file;fr=aucun fichier', // message si aucun fichier PDF dans le dossier
            'flip' => 0, // activer le mode flipbook
            'template' => '##human-name## [small](##size## - ##date##)[/small] ##btn-view## ##btn-download##', // modele pour affichage (bbcode et motcle)
            /* [st-form] Balise pour affichage de la liste */
            'main-tag' => 'ul', // balise principale. 0 = aucune
            'item-tag' => 'li', // balise pour le bloc d'un fichier. 0 = aucune
            /* [st-preview] Configuration prévisualisation du PDF */
            'preview-width' => '100%', // largeur du bloc pour preview
            'preview-height' => '500px', // hauteur du bloc pour preview
            'preview-background' => '', // couleur fond perdu du PDF (preview et modal) au format #rrggbb
            /* [st-modal] Configuration fenêtre modale */
            'popup-width' => 0, // largeur de la fenêtre popup avec unité. Ex: 90vw, 80%, ...
            'popup-height' => 0, // hauteur de la fenêtre popup avec unité. Ex: 90vh, 500px, ...
            'popup-close-left' => 0, // croix de fermeture en haut à gauche. haut-droite par défaut
            /* [st-dl] Bouton télécharger le PDF */
            'add-sitename' => '', // texte à ajouter au début des fichiers téléchargés
            'btn-download-text' => 'en=Download %s;fr=Telecharger %s', // texte pour le bouton 'télécharger'
            'btn-download-style' => '', // classe et style inline pour le bouton 'télécharger'
            /* [st-view] Bouton voir le PDF */
            'btn-view-text' => 'Voir', // texte pour bouton 'voir'
            'btn-view-style' => '', // classe et style inline pour le bouton 'voir'
            /* [st-img] style pour ##image## */
            'image-style' => '', // classe et style pour l'image
            'info-style' => '', // classe et style pour le contenu du fichier .info. Ajoute un bloc DIV
            /* [st-label] Label du fichier */
            'label-replace' => '', // liste des remplacements sous la forme ancien:nouveau, ... BBcode admis
            /* [st-format] Format pour les mots-clés */
            'format-date' => 'lang[en=m/d/Y H:i;fr=d/m/Y H:i]', // format pour la date. ex: 'd/m/Y H:i'
            'prefix-date-size' => 0, // ou le nombre de caractères pour définir la date. 13 si YYYYMMDDHHMM-, 11 si YYYY-MM-DD-
            /* [st-css] Style CSS */
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc parent
            'style' => '', // style inline pour bloc parent
            'item-style' => '', // classe et style inline pour un bloc fichier
            'css-head' => '' // règles CSS ajoutés dans le HEAD
        );

        // === fusion et controle des options
        $options = $this->ctrl_options($options_def);
        $this->ctrl_unit($options['preview-width'], '%,vw');
        $this->ctrl_unit($options['preview-height'], 'px,vh,rem');
        $this->ctrl_unit($options['popup-width'], '%,vw');
        $this->ctrl_unit($options['popup-height'], 'vh,px,rem');
        $bgcolor = $options['preview-background'] ? '#background=' . ltrim($options['preview-background'], ' #') : '';
        $flip = ($options['flip']) ? '#magazineMode=true' : ''; // v2.9
        
        // === les fichiers pdf du dossier
        $folder = $options[__class__];
        $folder = rtrim($folder, '/') . '/';
        $mask = ($options['file-mask']) ? $options['file-mask'] : '*';
        $pattern = $folder . $mask . '.{pdf,PDF}';
        $fileList = glob($pattern, GLOB_BRACE); // | GLOB_NOSORT
        if (empty($fileList)) {
            return $this->get_bbcode($options['msg-no-file']);
        }

        // === Preparation template
        $template = ($this->content) ? $this->content : $options['template'];
        $template = $this->get_bbcode($template);
        if (empty($template)) {
            return '';
        }

        // === tri des dossiers
        $prefixDateSize = (int) $options['prefix-date-size'];
        $pathFolder = dirname($fileList[0]); // $options[__class__];
        if ($prefixDateSize == 0 && $options['sort-by'] == 'date') {
            // tri sur le filemtime du fichier
            foreach ($fileList as $file) {
                $fileSort[$file] = date('YmdHi', filemtime($file));
            }
        } elseif ($prefixDateSize > 0 && $options['sort-by'] == 'name') {
            // tri sur la partie texte du nom du fichier
            foreach ($fileList as $file) {
                $fileSort[$file] = substr($file, $prefixDateSize + strlen($pathFolder) + 1);
            }
        } else {
            // tri alpha classique
            foreach ($fileList as $file) {
                $fileSort[$file] = $file;
            }
        }
        if ($options['sort-order'] == 'asc') {
            asort($fileSort);
        } else {
            arsort($fileSort);
        }

        // ==================================
        // ==== PREPARATION ATTRIBUTS COMMUNS
        // ==================================

        // --- style bloc principal
        $attr_main['id'] = $options['id'];
        $this->get_attr_style($attr_main, $options['class'], $options['style']);
        // on force le bloc principal si nécessaire
        if ($options['main-tag'] == 0 && ($options['class'] || $options['style'])) {
            $options['main-tag'] = 'div';
        }

        // --- style bloc item
        $attr_item = array();
        $this->get_attr_style($attr_item, $options['item-style']);
        // on force le bloc principal si nécessaire
        if ($options['item-tag'] == 0 && $options['item-style'] != '') {
            $options['item-tag'] = 'div';
        }

        // === bouton ou image view en modal
        if (stripos($template, '-view##')) {
            $attr_image_view = array();
            $this->load_file('../modal/flashy.css');
            $this->load_file('../modal/jquery.flashy.min.js');
            $attr_image_view['data-flashy-type'] = 'iframe';
            $this->add_class($attr_image_view['class'], 'flashy');

            $css = '';
            if ($options['popup-close-left']) {
                $css .= '.flashy-overlay .flashy-close{left:0}';
            }
            if ($options['popup-height'] || $options['popup-width']) {
                $css .= '.flashy-container .flashy-content.flashy-iframe {';
                $css .= ($options['popup-width']) ? 'width:' . $options['popup-width'] . ';' : '';
                $css .= ($options['popup-height']) ? 'height:' . $options['popup-height'] . ';' : '';
                $css .= '}';
            }
            if ($css) {
                $this->load_css_head($css);
            }
            // le bouton a un style en plus
            $attr_btn_view = $attr_image_view;
            $this->get_attr_style($attr_btn_view, $options['btn-view-style']);
        }

        // === image
        $attr_image = array();
        $this->get_attr_style($attr_image, $options['image-style']);

        // === info
        $attr_info = array();
        $this->get_attr_style($attr_info, $options['info-style']);

        // === lien ou bouton download
        if (stripos($template, '-download##')) {
        }

        $attr_btn_download = array();
        $this->get_attr_style($attr_btn_download, $options['btn-download-style']);

        // === Preparation preview
        if (strpos($template, '##preview##') !== false) {
            // style
            $attr_view['style'] = 'width:' . $options['width'];
            $this->add_style($attr_view['style'], 'height', $options['height']);

            error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

            // Préparation lien pour affichage
            // $attr_view_src = JURI::root() . 'plugins/content/up/actions/pdf/';
            $attr_view_src = 'plugins/content/up/actions/pdf/';
            $attr_view_src .= 'pdfjs/web/viewer.html';
            $attr_view_src .= '?file=' . '##PDF_URL##' . $bgcolor;
        } // fin Preparation preview

        // ==================================
        // ==== PREPARATION CODE HTML RETOUR
        // ==================================

        $html = array();
        $html[] = ($options['main-tag'] != '0') ? $this->set_attr_tag($options['main-tag'], $attr_main, false) : '';
        // v3.0 pour ajout image dans le label du fichier
        $label_replace = $this->get_bbcode($options['label-replace']);
        $label_replace = $this->strtoarray($label_replace, ',', ':', false);
        foreach ($fileSort as $file => $sortVal) {
            $fullName = basename($file);
            // $name = ($prefixDateSize > 0) ? substr($fullName, $prefixDateSize):$fullName;
            $name = substr($fullName, $prefixDateSize);

            // $file : path/date-nom.ext
            // $sortVal : valeur de tri (inutile)
            // $fullName : date-nom.ext nom complet
            // $name : nom.ext (sans date)

            // --- reset valeur pour file
            $tmpl = $template;

            // --- NAME
            $this->kw_replace($tmpl, 'name', $name);
            $this->kw_replace($tmpl, 'full-name', $fullName);

            // HUMAN-NAME
            $human_name = $this->link_humanize($name);
            foreach ($label_replace as $old => $new) {
                $human_name = str_ireplace($old, $new, $human_name);
            }
            $this->kw_replace($tmpl, 'human-name', $human_name);

            // --- SIZE
            if (strpos($tmpl, '##size##') !== false) {
                $this->kw_replace($tmpl, 'size', $this->filesize($file, 2));
            }
            // --- DATE
            if (strpos($tmpl, '##date##') !== false) {
                if ($prefixDateSize > 0) {
                    $tmp = strtotime(substr($fullName, 0, $prefixDateSize - 1));
                } else {
                    $tmp = filemtime($file);
                }
                $this->kw_replace($tmpl, 'date', date($options['format-date'], $tmp)); // fix v3.2
            }

            // --- INFO : fichier .info de même nom que le PDF avec texte descriptif
            if (strpos($tmpl, '##info##') !== false) {
                $fileinfo = $pathFolder . '/' . basename($fullName) . '.info';
                $info = '';
                if (file_exists($fileinfo)) {
                    $info = file_get_contents($fileinfo);
                    if ($options['info-style']) {
                        $info = $this->set_attr_tag('div', $attr_info, $info);
                    }
                }
                $this->kw_replace($tmpl, 'info', $info);
            }

            // --- IMAGE : fichier jpg ou png de même nom que le PDF
            if (strpos($tmpl, '##image') !== false) {
                $filelist = glob($pathFolder . '/' . basename($fullName) . '.{jpg,png}', GLOB_BRACE);
                $attr_image['src'] = (empty($filelist)) ? $this->actionPath . '/pdf-icon.svg' : $filelist[0];
                $tmp = $this->set_attr_tag('img', $attr_image, '');
                $this->kw_replace($tmpl, 'image', $tmp);
                // ---
                $attr_image_view['href'] = $this->get_url_absolute('plugins/content/up/actions/pdf/pdfjs/web/viewer.html') . '?file=' . $this->get_url_absolute($file) . $bgcolor. $flip;
                $tmp = $this->set_attr_tag('a', $attr_image_view, $tmp);
                $this->kw_replace($tmpl, 'image-view', $tmp);
            }

            // --- PREVIEW : vue du PDF selon method
            if (strpos($tmpl, '##preview##') !== false) {
                $attr_view['src'] = str_replace('##PDF_URL##', $this->get_url_absolute($file), $attr_view_src);
                $tmp = $this->set_attr_tag('iframe', $attr_view, true);
                $this->kw_replace($tmpl, 'preview', $tmp);
            }

            // --- BTN-VIEW : lien pour afficher PDF dans modal. Texte selon btn-view-text
            if (strpos($tmpl, '##btn-view##') !== false) {
                $attr_btn_view['href'] = $this->get_url_absolute('plugins/content/up/actions/pdf/pdfjs/web/viewer.html') . '?file=' . $this->get_url_absolute($file) . $bgcolor . $flip;
                $tmp = $this->set_attr_tag('a', $attr_btn_view, sprintf($options['btn-view-text'], basename($file)));
                if ($this->preview_ok() == false) {
                    $tmp = ''; // v5.2 si <= win8
                }
                $this->kw_replace($tmpl, 'btn-view', $tmp);
            }

            // --- BTN-DOWNLOAD : lien pour télécharger le PDF. Texte selon btn-download-text
            if (strpos($tmpl, '##btn-download##') !== false) {
                $attr_btn_download['href'] = $file;
                $attr_btn_download['download'] = $options['add-sitename'];
                $attr_btn_download['download'] .= str_replace('--', '-', basename($file));
                $str = sprintf($this->get_bbcode($options['btn-download-text']), basename($file));
                $tmp = $this->set_attr_tag('a', $attr_btn_download, $str);
                $this->kw_replace($tmpl, 'btn-download', $tmp);
            }

            // habillage bloc ligne fichier
            if ($options['item-tag'] != '0') {
                $html[] = $this->set_attr_tag($options['item-tag'], $attr_item, $tmpl);
            } else {
                $html[] = $tmpl . PHP_EOL;
            }
        }
        $html[] = ($options['main-tag'] != '0') ? '</' . $options['main-tag'] . '>' : '';
        if (stripos($template, '##btn-view##')) {
            $html[] = $this->load_jquery_code('if ($(".flashy").length > 0) $(".flashy").flashy({overlayClose:1})');
        }

        return implode(PHP_EOL, $html);
    }

    // run
    public function filesize($file, $decimal = 0)
    {
        $size = filesize($file);
        $units = array(
            'Go',
            'Mo',
            'ko',
            'o'
        );
        $divider = 1024 * 1024 * 1024;
        foreach ($units as $unit) {
            if (floor($size / $divider) > 0) {
                return round($size / $divider, $decimal) . '&nbsp;' . $unit;
            }
            $divider /= 1024;
        }
        return '';
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
