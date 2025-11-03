<?php

/**
 * Déplace les fichiers inutilisés d'un dossier vers un dossier pour récupération éventuelle
 *
 * syntaxe {up upfilescleaner=folder_source}
 *
 * @version  UP-5.2
 * @author	 Lomart
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    Lomart
 * @tags    Expert
 *
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;

class upfilescleaner extends upAction
{
    public function init()
    {
        $this->load_file('ajax_upfilescleaner.js');
        $this->load_file('tooltip.js');
        $this->load_file('tooltip.css');
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // chemin du dossier à analyser
            'extensions' => 'jpg,jpeg,png,gif,pdf', // extensions
            'bd-tables' => '', // content:introtext,fulltext,images ; categories:description,params ; modules:params ; menus:params
            'folder-backup' => 'tmp/up-files-cleaner', // si vide, on affiche la liste
            'folder-purge' => '', // suppressions des dossiers vides du dossier upfilescleaner OU contenant uniquement les fichiers indiqués (ex: index.html,info.txt)
            /*[st-filter]*/
            'suffix-lang' => '-fr,-en', // pour ne pas prendre en compte les versions en et fr
            'suffix-version' => '-mini', // pour conserver la version sans le suffixe
            'folders-exclude' => '', // liste des dossiers non concernés. séparateur : virgule
            'files-exclude' => '', // liste des fichiers conservés. séparateur : virgule
            /*[st-divers]*/
            'id' => '',
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'btn-style' => 'btn btn-primary', // style du bouton action
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // ==== FUSION ET CONTROLE DES OPTIONS ====
        // ========================================
        $options = $this->ctrl_options($options_def);

        $source = trim($options[__class__], ' \//');
        if (empty($source || file_exists($source) === false || is_dir($source) === false)) {
            return $this->msg_error($this->trad_keyword('NO_SOURCE'));
        }

        $folder_backup = trim($options['folder-backup'], ' /');
        if (empty($folder_backup)) {
            $this->msg_error($this->trad_keyword('NO_BACKUP_PATH'));
        }
        $folder_backup = JPATH_BASE .'/'. $folder_backup;
        if (! file_exists($folder_backup)) {
            mkdir($folder_backup, 0777, true);
        }

        $suffix_version = array_map('trim', explode(',', $options['suffix-version']));
        $suffix_lang = array_map('trim', explode(',', $options['suffix-lang']));

        // si l'utilisateur ajoute l'option folder-purge sans argument, on met index.html par défaut
        if (isset($this->options_user['folder-purge']) && empty($this->options_user['folder-purge'])) {
            $options['folder-purge'] = 'index.html';
        }

        // === CSS-HEAD
        $css = '.tooltip-size[width:100%;min-width:260px;margin:0;padding:10px]';
        $css .= $options['css-head'];
        $this->load_css_head($css);

        // ==== RECUPERATION DU CONTENU DE LA BD ====
        // ==========================================
        // tables SQL examinées
        // si l'option commence par +, on ajoute les tables aux tables par défaut
        $bd_tables = trim($options['bd-tables']);
        $bd_tables_default = 'content:introtext,fulltext,images ; categories:description,params ; modules:params ; menu:params';
        if (!empty($bd_tables)) {
            if ($bd_tables[0] == '+') {
                $bd_tables = substr($bd_tables, 1) .';'. $bd_tables_default;
            }
        } else {
            $bd_tables = $bd_tables_default;
        }
        // le contenu des $bd_tables
        $data_source = $this->exportSQL($bd_tables, $folder_backup);

        // ==== LES VARIABLES PRINCIPALES ====
        // ===================================
        $list_folder_exclude_by_user = array(); // pour affichage compte-rendu uniquement
        $list_folder_used = array(); // tous les dossiers à conserver (user+utilisé)
        $list_folder_move = array(); // les dossiers à analyser pour déplacer son contenu

        $list_file_exclude_by_user = array(); // pour affichage compte-rendu uniquement
        $list_file_used = array(); // les fichiers à conserver
        $list_file_orange = array(); // utilisé directement dans un dossier utilisé pour affichage compte-rendu uniquement

        // ==== 1 - LES DOSSIERS ET FICHIERS A EXCLURE ====
        // ================================================
        // en sortie, les dossiers et fihiers exclus par l'utilisateur sont dans :
        // $list_folder_used = $list_folder_exclude_by_user;
        // $list_file_used = $list_file_exclude_by_user;

        // les exclusions passées par les options
        $tmp = explode(',', $options['folders-exclude'].','.$options['files-exclude']);
        // les exclusions passées par fichier texte
        $filepath =  $this->actionPath . 'custom/list-exclude.txt';
        if (file_exists($filepath)) {
            $tmp = array_merge($tmp, explode(PHP_EOL, file_get_contents($filepath)));
        }
        // nettoyage
        $nb = count($tmp);
        for ($i = 0; $i < $nb; $i++) {
            $tmp[$i] = trim(str_replace('\\', '\/', $tmp[$i]));
            if (empty($tmp[$i]) || $tmp[$i][0] == ';') {
                unset($tmp[$i]);
            }
        }
        $tmp = array_unique($tmp);

        // ventilation dossiers/fichiers
        foreach ($tmp as $lign) {
            if (strpos($lign, '/') === false) {
                $list_folder_exclude_by_user[] = $lign; // sous-dossiers génériques (ex:srcset)
            } elseif (str_starts_with($lign, $source) && is_dir($lign)) {
                $list_folder_exclude_by_user[] = $lign; // chemin complet dossier
            } elseif (str_starts_with($lign, $source) && file_exists($lign)) {
                $list_file_exclude_by_user[] = $lign; // chemin complet vers un fichier
            }
        }
        // pour simplifier les tests
        $list_folder_used = $list_folder_exclude_by_user;
        $list_file_used = $list_file_exclude_by_user;
        unset($tmp);


        // ==== 2 - LISTE DES DOSSIERS À ANALYSER ====
        // ===========================================
        // en sortie
        // $list_all_folders contient la liste de tous les dossiers à analyser
        // ventilés dans $list_folder_used & $list_folder_move

        $this->result = array();
        $this->glob_recursive_dir($source, 99);
        $list_all_folders = $this->result;
        unset($this->result);
        array_unshift($list_all_folders, $source);
        foreach ($list_all_folders as $dir) {
            $dir = str_replace('\\', '\/', $dir);
            $rootDir = substr($dir, 0, strrpos($dir, '/'));
            if (in_array($rootDir, $list_folder_used)) {
                // les sous-dossiers d'un dossier exclus sont exclus
                $list_folder_used[] = $dir;
                $list_subfolder_used[] = $dir;
            } elseif (in_array($dir, $list_folder_used)) {
                // dossier dans la liste des déjà exclus par l'utilisateur
            } elseif (strpos($data_source, $dir) > 0) {
                if ($this->dirUsed($dir, $data_source)) {
                    // dossier utilisé. ex:image-gallery=images/photos
                    $list_folder_used[] = $dir;
                } else {
                    // OK, on analyse le contenu du dossier
                    $list_folder_move[] = $dir;
                }
            } else {
                // OK, on analyse le contenu du dossier
                $list_folder_move[] = $dir;
            }
        }

        // suppression des dossiers exclus des move
        if (!empty($list_folder_used)) {
            $list_folder_move = array_diff($list_folder_move, $list_folder_used);
        }
        if (empty($list_folder_move)) {
            return $this->msg_inline($this->trad_keyword('NO_FOLDER_MOVE'));
        }

        // ==== 3 - RECHERCHE DES FICHIERS À DÉPLACER ====
        // ===============================================
        // en sortie, $jnl contient la liste des dossiers et fichiers dans l'ordre d'affchage
        // les fichiers sont classés dans : $list_file_used et $list_file_orange
        $jnl = array();
        $list_all_files = array();
        foreach ($list_all_folders as $dir) {
            $jnl[] = $dir;
            $file_list = glob($dir.'/*.{' . $options['extensions'].'}', GLOB_BRACE | GLOB_NOSORT);

            foreach ($file_list as $file) {
                $jnl[] = $file;
                $list_all_files[] = $file;

                $dirUsed = in_array($dir, $list_folder_used);
                $fileUsed = (strpos($data_source, $file) !== false);

                if ($dirUsed && $fileUsed) {
                    $list_file_orange[] = $file;
                }
                if ($dirUsed || $fileUsed) {
                    $list_file_used[] = $file;
                }

                // -- ON VÉRIFIE LES SUFFIXES
                // un fichier avec suffixe est utilisé (ex: -mini)
                // on cherche la version sans suffixe dans les fichiers du dossier
                foreach ($suffix_version as $suffix) {
                    $tmp = str_replace($suffix.'.', '.', $file);
                    if ($tmp != $file && in_array($tmp, $file_list)) {
                        if ($fileUsed || (strpos($data_source, $tmp) !== false)) {
                            $list_file_used[] = $tmp;
                        }
                        $jnl[] = $file;
                    }
                }
                // si non utilisé, une version sans le suffixe (-fr) existe t'elle
                foreach ($suffix_lang as $suffix) {
                    if (strpos($file, $suffix.'.') !== false) {
                        $tmp = str_replace($suffix.'.', '(.*).', $file);
                        $regex = '#'. $tmp .'#U';
                        if (preg_match($regex, $data_source, $match) == 1) {
                            $list_file_used[] = $file;
                        }
                    }
                }
                // on vérifie l'utilisation dans params
                $fileParams = str_replace('/', '\\/', $file);
                if (strpos($data_source, $fileParams) !== false) {
                    $list_file_used[] = $file;
                }

            }

        }

        // ==== FICHIER A DEPLACER POUR AJAX ====
        // ======================================
        // en sortie, la liste des fichiers à déplacer se trouve dans le fichier
        // folder_backup/upfilescleaner-files.txt

        $files_a_deplacer = array_diff($list_all_files, $list_file_used ?? []);
        file_put_contents($folder_backup.'/upfilescleaner-files.txt', implode(PHP_EOL, $files_a_deplacer));
        if ($options['folder-purge']) {
            array_unshift($list_all_folders, $options['folder-purge']);
            file_put_contents($folder_backup.'/upfilescleaner-folder-purge.txt', implode(PHP_EOL, $list_all_folders));
        }
        // ==== AFFICHAGE COMPTE-RENDU ====
        // ================================

        // attributs du bloc principal
        $attr_main = array();
        $attr_main['id'] = $options['id'];
        $this->get_attr_style($attr_main, $options['class'], $options['style']);

        // commentaires dans liste
        $comment_folder_exclude_by_user = $this->trad_keyword('FOLDER_EXCLUDE_BY_USER');
        $comment_subfolder_used = $this->trad_keyword('SUBFOLDER_USED');
        $comment_folder_used = $this->trad_keyword('FOLDER_USED');
        $comment_file_exclude_by_user = $this->trad_keyword('FILE_EXCLUDE_BY_USER');
        $comment_file_orange = $this->trad_keyword('FILE_ORANGE');

        // code en retour
        $html[] = $this->set_attr_tag('div', $attr_main);

        $list_folder_used = array_diff($list_folder_used, $list_folder_exclude_by_user);
        $jnl = array_unique($jnl);
        $out[] = $this->trad_keyword('LOG_TITLE', $source);
        $out[] = $this->trad_keyword('LOG_COLOR');
        $out[] = '<ul>';
        $nivBak = substr_count($jnl[0], '/');
        foreach ($jnl as $f) {
            $niv = substr_count($f, '/');
            if ($niv != $nivBak) {
                if ($niv > $nivBak) {
                    $out[] = str_repeat('<ul>', ($niv - $nivBak));
                } elseif ($niv < $nivBak) {
                    $out[] = str_repeat('</ul>', ($nivBak - $niv));
                }
                $nivBak = $niv;
            }
            // $fdir = '<b>'.substr($f, strrpos($f, '/') + 1).'</b>';
            $fdir = '<b>'.trim($f, ' /').'</b>';
            // les dossiers
            if (in_array($f, $list_folder_exclude_by_user)) {
                $out[] = "<li class='ph1 bg-jauneFonce t-vert'>$fdir $comment_folder_exclude_by_user</li>";
            } elseif (in_array($f, $list_subfolder_used)) {
                $out[] = "<li class='ph1 bg-jauneFonce t-bleuClair'>$fdir $comment_subfolder_used</li>";
            } elseif (in_array($f, $list_folder_used)) {
                $out[] = "<li class='ph1 bg-jauneFonce t-bleuClair'>$fdir $comment_folder_used</li>";
            } elseif (in_array($f, $list_folder_move)) {
                $out[] = "<li class='ph1 bg-jauneFonce t-bleuClair'>$fdir</li>";
                // les fichiers
            } elseif (in_array($f, $list_file_exclude_by_user)) {
                $out[] = "<li>".$this->tooltip($f, 't-vert', $comment_file_exclude_by_user)."</li>";
            } elseif (in_array($f, $list_file_orange)) {
                $out[] = "<li>".$this->tooltip($f, 't-orange', $comment_file_orange)."</li>";
            } elseif (in_array($f, $list_file_used)) {
                $out[] = "<li>".$this->tooltip($f, 't-vert')."</li>";
            } else {
                $out[] = "<li>".$this->tooltip($f, 't-rougeFonce')."</li>";
            }
        }
        $out[] = str_repeat('</ul>', ($niv - 1));
        $html[] = implode(PHP_EOL, $out);

        // ==== RETOUR SI AUCUN FICHIER A DEPLACER ====
        // ============================================
        if (empty($files_a_deplacer)) {
            $html[] = '<div class="mt2 tc p1 bg-jauneClair bd-gris">';
            $html[] = $this->trad_keyword('ACTION_NONE', $source);
            $html[] = '</div>';
            return implode(PHP_EOL, $html);
        }

        // ==== POUR APPEL JAVASCRIPT ====
        // ===============================
        // === attributs du bouton
        $id = $options['id'];
        $attr_btn = array();
        $attr_btn['id'] = "upfilescleaner-btn";
        $attr_btn['disabled'] = "true";
        $attr_btn['data-id'] = $options['id'];
        $attr_btn['data-backup'] = htmlentities($options['folder-backup']);
        if (!empty($options['folder-purge'])) {
            $attr_btn['data-folder-purge'] = '1';
        }
        $this->get_attr_style($attr_btn, $options['btn-style'], 'upfilescleaner-btn', $options['id']);

        // bouton appel javascript et résultat
        $btn_label = $this->trad_keyword('ACTION_BTN_LABEL', $options['folder-backup']);
        $warning = $this->trad_keyword('ACTION_WARNING');
        $html[] = '<div class="mt2 tc bg-jauneClair p1 bd-gris">';
        $html[] = '<label class="upfilescleaner-warning mb1">  <input id="upfilescleaner-cb" type="checkbox">'.$warning.'</label>';
        $html[] = '<div class="tc">';
        $html[] = $this->set_attr_tag('button', $attr_btn, $btn_label);
        $html[] = '</div>';
        $html[] = '<div class="upfilescleaner-result" style="display:none">';
        $html[] = $this->trad_keyword('ACTION_RESULT', $options['folder-backup']);
        $html[] = '</div>';

        $html[] = '</div>';

        // activation bouton
        $js = '<script>';
        $js .= "const cb = document.getElementById('upfilescleaner-cb');";
        $js .= "const btnOK = document.getElementById('upfilescleaner-btn');";
        $js .= "cb.addEventListener('change', function () {";
        $js .= 'console.log("clic bouton");';
        $js .= 'btnOK.disabled = !cb.checked;';
        $js .= '});';
        $js .= '</script>';
        $html[] =  $js;

        // tooltip
        $html[] = '<div id="thumbnail-preview"></div>';

        return implode(PHP_EOL, $html);
    }

    // run

    /*
     * glob_recursive_dir
     * ------------------
     * retourne les fichiers correspondants au masque
     * $max est le niveau d'exploration des sous-dossiers
     */
    private function glob_recursive_dir($path, $max = 0)
    {
        $dirlist = glob($path . '/*', GLOB_ONLYDIR | GLOB_NOSORT);
        if ($max > 0) {
            $max--;
            foreach ($dirlist as $dir) {
                $this->result[] = $dir;
                $this->glob_recursive_dir($dir, $max);
            }
        }
    }

    /*
     * filepart
     * --------
     * retourne un tableau avec :
     * dirname : chemin et nom du fichier
     * ext : extension (avec le point)
     */
    private function filepart($filepath)
    {
        $tmp = pathinfo($filepath);
        $out['dirname'] = $tmp['dirname'] . '/' . $tmp['filename'];
        $out['ext'] = '.'.$tmp['extension'];
        return $out;
    }

    /*
     * getFileList
     * -----------
     * retourne un tableau avec le contenu du fichier sans les commentaires
     */
    private function getFileList($option, $filename)
    {
        $out = array();
        $filepath =  $this->actionPath . 'custom/' . $filename;
        if (file_exists($filepath)) {
            $tmp = explode(PHP_EOL, file_get_contents($filepath));
            for ($i = 0; $i < count($tmp); $i++) {
                $tmp[$i] = trim($tmp[$i]);
                if ($tmp[$i][0] != ';') {
                    $out[] = $tmp[$i];
                }
            }
        }
        return $out;
    }

    /*
     * dirUsed
     * ----------
     * return true si le chemin du dossier seul est trouvé
     * tester avec images/actions-demo/table-sort/simple-wysiwyg.png pour images/actions-demo/table
    */
    private function dirUsed($dir, &$data)
    {
        if (strpos($dir, '/') === false) {
            return false; // chemin non significatif
        }

        $regex = '#'.str_replace('/', '\/', $dir) . '[ |\"}\<\[]#U';
        return (preg_match($regex, $data) == 1);
    }

    /*
     * exportSQL
     * ---------
     * copie le conteu des tables/champs définis dans l'option $bd_tables
     * dans $folder_backup (pour analyse ultérieure )
    */
    public function exportSQL($bd_tables, $folder_backup)
    {
        // Initialiser l'application
        $app = Factory::getApplication('site');

        $out = '';
        $tables = $this->params_decode($bd_tables, ';');
        // Récupérer l'objet base de données
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        foreach ($tables as $table => $cols) {
            $table = trim($table);
            $cols = trim($cols);

            // contrôle nom des champs
            if (empty($cols) || $cols == '1') {
                $cols = '*';
            } else {
                $db->setQuery('DESCRIBE ' . $db->quoteName('#__'.$table));
                $rows = $db->loadAssocList();
                $fields = array_column($rows, 'Field');
                $cols = array_map('trim', explode(',', $cols.',id'));
                $cols = array_intersect($cols, $fields);
                $cols = $db->qn($cols);
            }

            // Construire la requête
            $query = $db->createQuery();
            $query->select($cols);
            $query->from($db->qn('#__'.$table));

            $db->setQuery($query);
            $rows = $db->loadAssocList();

            foreach ($rows as $row) {
                $id = (isset($row['id'])) ? $row['id'] : '';
                $ref = "************** ".$table.':'.$id." **************";
                $sep = str_repeat('*', strlen($ref));
                $out .= "\n\n".$sep."\n".$ref."\n".$sep."\n\n".implode('', $row);
                // $out .= "\n**************".$table.':'.$id."**************\n_n".implode('', $row);
            }
            $out .= PHP_EOL;

        }
        // pour controle mannuel
        file_put_contents($folder_backup.'/data-source.txt', $out);
        return $out;

    }

    /*
     * tooltip
     * -------
     * retourne une chaine pour un fichier
     * avec la couleur, le commentaire et une vignette pour les images
    */

    public function tooltip($file, $color, $comment = '')
    {
        list($w, $h) = getimagesize($file);
        $filesize = $this->filesize_human($file);
        if (is_null($w)) {
            // ce n'est pas un image
            $out = '<span class="'.$color.'">'.$file.' ('. $filesize . ') ' . $comment.'</span>';
        } else {
            $info = $w .'x'. $h .' - ' . $filesize;
            $out =  '<span class="'.$color.'">'.$file . ' (';
            $out .=  '<span class="thumbnail" onmouseover="showThumbnail(\'' . $file . '\')" onmouseout="hideThumbnail()">' . $info . '</span>';
            $out .=  ') ' . $comment;
            $out .=  '</span>' ;
        }
        return $out;
    }

    /*
    * filesize_human
    * --------------
    * Affiche la taille d'un fichier avec l'unité la plus pertinente (o, ko, Mo, Go, To)
    * $maxsize (204800 = 200ko) les fichier d'une taille supérieure sont surlignés
    */
    public function filesize_human($file, $decimal = 1, $maxsize = 204800)
    {
        $size = @filesize($file);
        if ($size === false) {
            return '';
        }
        if ($size > $maxsize) {
            $mark = 'mark';
        }
        $units = array('o', 'ko', 'Mo', 'Go', 'To');
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        $out = round($size, $decimal) . '&nbsp;' . $units[$i];
        if (isset($mark)) {
            $out = '<mark>'.$out.'</mark>';
        }
        return $out;
    }
}

// class
