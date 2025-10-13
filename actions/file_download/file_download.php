<?php

/**
 * Gestionnaire simple de téléchargements avec stats et protection par mot de passe
 *
 * syntaxe 1 {up file-download=dossier ou fichier}
 * syntaxe 2 {up file-download=dossier ou fichier}##icon## ##filename-link##{/up file-download}
 *
 * présentation des liens :
 * ##link## ##/link## ##filename-link## ##filename## ##icon## ##icon-link##
 * ##hit## ##lastdownload##
 * ##info##  ##size##  ##date##
 *
 * @author   LOMART
 * @version  UP-1.9
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags  File
 */

/*
 * v1.9 - prise en charge PDF,TXT,... Bravo Pascal
 * v1.91 - blocage extensions dangereuses et gestion icon
 * v1.95 - file-download=path/file*.zip = retourne la dernière version du fichier file*.zip
 * v2.3 - ajout de l'option 'file-mask' pour sélectionner les fichiers d'un dossier
 * v2.61 - ajout messages sur analyse option principale
 * v5 - ajout option sort-order
 * v5.1 - ajout options file-max et sort-by-date
 */
defined('_JEXEC') or die();

use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Uri\Uri;

class file_download extends upAction
{
    public function init()
    {
        $this->load_file('updownload.js');
        return true;
    }

    public function run()
    {
        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // fichier ou dossier
            'file-mask' => '', // pour sélectionner les fichiers d'un dossier. ex: fic-*
            'file-max' => 0, // nombre maximal de fichiers retournés
            'extensions' => 'zip, pdf,txt,md,png,jpg,gif,svg,doc,docx,xls,xlsx,odt,ods', // extensions autorisées
            'sort-by-date' => 0, // 1 pour tri selon date des fichiers.
            'sort-order' => 'desc', // desc ou asc = ordre affichage
            'password' => '', // mot de passe pour télécharger le fichier
            'template' => '##icon## ##filename-link## (##size## - ##date##) [small]##hit## ##lastdownload##[/small] [br]##info##', // modèle de mise en page. keywords + bbcode
            /* [st-ul] Définition du type de liste */
            'main-tag' => 'ul', // balise pour la liste des fichiers
            'main-style' => '', // style pour la liste des fichiers
            'main-class' => 'list-none', // classes pour la liste des fichiers
            /* [st-li] Définition d'une ligne pour un fichier */
            'item-tag' => 'li', // balise pour un bloc fichier
            'item-style' => '', // style pour un bloc fichier
            'item-class' => '', // classes pour un bloc fichier
            'link-style' => '', // style pour le lien (classes admises)
            'icon' => '32', // chemin et nom de l'icône devant le lien ou taille de l'icône selon extension du fichier (16 ou 32)
            /* [st-format] format pour les mots-clés */
            'format-date' => 'lang[en=m/d/Y H:i;fr=d/m/Y H:i]', // 'd/m/Y H:i' format pour la date
            'model-hit' => 'téléchargé %s fois', // présentation pour ##hit##
            'model-lastdownload' => 'dernier téléchargement le %s', // présentation pour ##lastdownload##
            'model-info' => '%s', // présentation pour ##info##
            /* [st-annexe] style et options secondaires */
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '', // règles CSS définies par le webmaster (ajout dans le head)
            'filter' => '' // condition pour exécuter l'action
        );
        // si contenu, c'est le template
        if ($this->content && ! isset($this->options_user['template'])) {
            $this->options_user['template'] = $this->content;
        }
        // sauf mention explicite, si main-tag=0 alors item-tag=0 (v2.5)
        if (isset($this->options_user['main-tag']) && $this->options_user['main-tag'] == '0' && (! isset($this->options_user['item-tag']))) {
            $this->options_user['item-tag'] = '0';
        }

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);
        $options['template'] = $this->get_bbcode($options['template']);

        // Filtrage
        if ($this->filter_ok($options['filter']) !== true) {
            return '';
        }
        // === chemin du dossier racine des téléchargement (unique pour le site)
        // le webmaster peut le changer en dupliquant le fichier en
        // si vide, les chemins seront relatifs à la racine du site
        $cfg_file = $this->get_custom_path('updownload.cfg');
        if ($cfg_file === false) {
            return '';
        }
        $cfg = parse_ini_file($cfg_file);
        $rootFolderAbs = JPATH_ROOT . '/' . rtrim($cfg['root'], '/') . '/';

        // === les extensions autorisées
        // celles définies dans $options['extensions'] à condition qu'elles soient
        // autorisées par le webmaster dans custom/updownload.cfg
        $cfg_extensions = array_map('trim', explode(',', $cfg['extensions']));
        $user_extensions = array_map('trim', explode(',', $options['extensions']));
        $extensions = implode(',', array_intersect($cfg_extensions, $user_extensions));
        /*
         * Les variables ciblant le fichier à télécharger
         *
         * variable : -----$filelist(array) & $file (item)---- : interne
         * variable : | ---- $fileRel ------- : le lien
         * variable : $rootFolderAbs | $folder | $fileName
         * exemple : files/ | doc/1/ | memo.pdf
         *
         */
        // === analyse demande
        $fileList = array(); // liste des téléchargements
        if (is_dir($rootFolderAbs . $options[__class__])) {
            // chemin pour icon
            $this->filepath = rtrim($cfg['root'], '/') . '/' . rtrim($options[__class__], '/') . '/';
            // tous les fichiers d'un dossier selon un masque sur extensions
            $folder = $options[__class__];
            $folder = rtrim($folder, '/') . '/';
            $dirStat = $rootFolderAbs . $folder . '.log';
            $mask = ($options['file-mask']) ? $options['file-mask'] : '*';
            $pattern = $rootFolderAbs . $folder . $mask . '.{' . $extensions . '}';
            $fileList = glob($pattern, GLOB_BRACE); // | GLOB_NOSORT
            if ($options['sort-by-date']) { // v5.1
                $fileListDate = array();
                foreach ($fileList as $k => $file) {
                    $fileListDate[$k]['name'] = $file;
                    $fileListDate[$k]['size'] = filemtime($file);
                }
                array_multisort(array_column($fileListDate, 'size'), array_column($fileListDate, 'name'), $fileListDate);
                $fileList = array(); // reset
                foreach ($fileListDate as $k => $v) {
                    $fileList[] = $v['name'];
                }
            }
            if (strtolower($options['sort-order']) == 'desc') {
                $fileList = array_reverse($fileList);
            }
        } else {
            // chemin pour icon
            $this->filepath = rtrim($cfg['root'], '/') . '/' . dirname($options[__class__]) . '/';
            // un unique fichier
            $ext = strtolower(pathinfo($options[__class__], PATHINFO_EXTENSION));
            if (! in_array($ext, $cfg_extensions)) {
                return $this->msg_inline($this->lang('en=File type prohibited;fr=Type de fichier interdit :') . ' ' . $ext);
            }
            // nouvelle version
            $tmp = glob($rootFolderAbs . $options[__class__], GLOB_BRACE);

            if (empty($tmp)) {
                return $this->msg_inline($this->lang('en=File not found :;fr=Fichier non trouvé :') . ' ' . $options[__class__]);
            }
            $fileList[] = end($tmp);
            $folder = dirname($options[__class__]);
            $folder = ($folder == '.') ? '' : $folder . '/';
            $dirStat = $rootFolderAbs . $folder . '.log';
        }
        // --- lign-max v5.1
        if ((int) $options['file-max'] > 0) {
            $fileList = array_slice($fileList, 0, (int) $options['file-max']);
        }

        // --- controle existence dossier .log
        if (! is_dir($dirStat)) {
            mkdir($dirStat);
        }
        // === css-head
        $this->load_css_head($options['css-head']);
        // attributs du bloc principal
        $this->get_attr_style($attr_main, $options['main-class'], $options['main-style']);
        $this->get_attr_style($attr_main, $options['class'], $options['style']);
        $attr_main['id'] = $options['id'];
        // attributs d'un bloc fichier
        $this->get_attr_style($attr_item, $options['item-class'], $options['item-style']);
        // attributs d'un lien fichier (balise a)
        $attr_link['class'] = 'updownload';
        $attr_link['href'] = '#dontmove';
        if ($options['password']) {
            $attr_link['md5'] = password_hash($options['password'], PASSWORD_DEFAULT);
        }
        // === sortie HTML
        $html = array();
        $html[] = ($options['main-tag'] != '0') ? $this->set_attr_tag($options['main-tag'], $attr_main, false) : '';
        foreach ($fileList as $file) {
            // --- reset valeur pour file
            $tmpl = $options['template'];
            $fileRel = substr($file, strlen($rootFolderAbs)); // relatif à rootFolder
            $fileName = basename($fileRel);
            $file_attr = $attr_link;
            $file_attr['data-up-id'] = $options['id'];
            $file_attr['data-file'] = $fileRel;
            // les infos fichier sont au format ini (info & icon) ou uniqument texte info
            unset($file_info);
            if (file_exists($file . '.info')) {
                $file_info = parse_ini_file($file . '.info'); // info et icon
                if (empty($file_info)) {
                    $file_info['info'] = file_get_contents($file . '.info');
                }
            }
            //
            // ==== creation ligne de sortie
            // LINK exemple : ##link## ##icon## texte ##/link##
            if (stripos($tmpl, '##link') !== false) {
                $str = $this->set_attr_tag('a', $file_attr);
                $this->kw_replace($tmpl, 'link', $str);
                $tmpl = str_replace('##/link##', '</a>', $tmpl);
            }
            if (stripos($tmpl, '##/link##') !== false) {
                // déjà traité. Plus aucun sens
                $tmpl = str_replace('##/link##', '', $tmpl); // v1.9.3
            }
            // FILENAME
            if (stripos($tmpl, '##filename-link##') !== false) {
                $str = $this->set_attr_tag('a', $file_attr, $fileName);
                $this->kw_replace($tmpl, 'filename-link', $str);
            }
            if (stripos($tmpl, '##filename##') !== false) {
                $this->kw_replace($tmpl, 'filename', $fileName);
            }
            // HITS & LASTDOWNLOAD
            if (stripos($tmpl, '##hit##') || stripos($tmpl, '##lastdownload##')) {
                $nb = 0;
                $time = '';
                $fileStat = dirname($file) . '/.log/' . $fileName . '.stat';
                if (file_exists($fileStat)) {
                    list($nb, $time) = explode('|', file_get_contents($fileStat));
                }
                $filecls = OutputFilter::stringURLSafe('up-cls-' . str_replace('.', '-', $fileName));
                $nb = '<span class="up-tmpl-hits ' . $filecls . '">' . $nb . '</span>';
                $this->kw_replace($tmpl, 'hit', ($nb) ? sprintf($options['model-hit'], $nb) : '');
                $time = ($time) ? date($options['format-date'], strtotime($time)) : '';
                $time = '<span class="up-tmpl-time ' . $filecls . '">' . $time . '</span>';
                $this->kw_replace($tmpl, 'lastdownload', ($time) ? sprintf($options['model-lastdownload'], $time) : '');
            }
            // INFO dans fichier $filename.info
            if (stripos($tmpl, '##info##')) {
                $str = (isset($file_info['info'])) ? $file_info['info'] : '';
                $this->kw_replace($tmpl, 'info', ($str) ? sprintf($options['model-info'], $str) : '');
            }
            // ICON
            if (stripos($tmpl, '##icon##') !== false) {
                $icon = (isset($file_info['icon'])) ? $file_info['icon'] : $options['icon'];
                $tmpl = str_replace('##icon##', $this->icon($icon, $file), $tmpl);
                $this->kw_replace($tmpl, 'icon', $this->icon($icon, $file));
            }
            if (stripos($tmpl, '##icon-link##') !== false) {
                $icon = (isset($file_info['icon'])) ? $file_info['icon'] : $options['icon'];
                $str = $this->set_attr_tag('a', $file_attr, $this->icon($icon, $file));
                $this->kw_replace($tmpl, 'icon-link', $str);
            }
            // SIZE
            if (stripos($tmpl, '##size##') !== false) {
                $this->kw_replace($tmpl, 'size', $this->filesize($file, 2));
            }
            // DATE
            if (stripos($tmpl, '##date##')) {
                $this->kw_replace($tmpl, 'date', date($options['format-date'], filemtime($file)));
            }
            // habillage bloc ligne fichier
            if ($options['item-tag'] != '0') {
                $html[] = $this->set_attr_tag($options['item-tag'], $attr_item, $tmpl);
            } else {
                $html[] = $tmpl . PHP_EOL;
            }
        } // foreach $file
        $html[] = ($options['main-tag'] != '0') ? '</' . $options['main-tag'] . '>' : '';
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

    // filesize
    public function icon($icon, $file)
    {
        if (strpos($icon, '.') !== false) {
            // icone indiquée dans shortcode ou .info
            if (strpos($icon, '/') === false) {
                $icon = $this->filepath . $icon;
            }
            return '<img src="' . $icon . '"> ';
        } else {
            $slash = (URI::root(true)) ? URI::root(true) . '/' : '/';
            // icone selon type fichier
            $imgdir = $this->upPath . 'assets/img/file/' . $icon;
            if (is_dir($imgdir)) {
                $ficext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $tmp = glob($imgdir . '/' . $ficext . '.{jpg,png,gif}', GLOB_BRACE);
                if (empty($tmp) || $tmp === false) {
                    return '<img src = "' . $slash . $imgdir . '/download.png"> ';
                } else {
                    return '<img src = "' . $slash . $tmp[0] . '"> ';
                }
            }
        }
        return '';
    }

    // icon
}

// class
