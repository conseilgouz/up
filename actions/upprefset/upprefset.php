<?php

/**
 * Affiche la liste de tous les prefsets de UP pour le site
 *
 * syntaxe {up upPrefSet}
 *
 * @author   LOMART
 * @version  UP-1.8
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags UP
 */
/*
 * v2.8 - chgt nom fichier custom/info.txt en help.txt
 * v3.1 - export totalité de tous les dossiers custom
 */
defined('_JEXEC') or die();

class upprefset extends upAction
{
    public function init()
    {
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
        /* [st-sel] Sélection des actions */
            __class__ => '', // liste des actions ou vide pour toutes
            'action-exclude' => 0, // 1: toutes les actions sauf celles du paramétre principal
            'prefset-exclude' => 'icons,options', // sections exclues
            /* [st-form] Format de la mise en page */
            'action-template' => '[h4]##action##[/h4]', // présentation pour ##action##
            'prefset-template' => '[b class="t-vertFonce"]##prefset##[/b] : [small]##options##[/small]', // présentation pour ##prefset##
            'info-template' => '[div class="bd-grey ph1"]##info##[/div]', // présentation pour ##info##
            'prefset-separator' => '[br]', // séparateur entre items
            'options-separator' => '[b class="t-vert"] | [/b]', // les underscrores sont remplacés par des espaces
            /* [st-exp] exportation des fichiers */
            'export-prefs' => '', // ou sous-dossier de TMP pour sauver l'arborescence. ex : up-pref-foo
            /* [st-css] Style CSS */
            'action-class' => '', // classes et style pour le bloc d'une action
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '' // règles CSS définies par le webmaster (ajout dans le head)
        );

        // controle options
        if (isset($this->options_user['export-prefs'])) {
            $path = rtrim($this->options_user['export-prefs'], '/') . '/';
            $path = preg_replace('#^/?tmp/?#i', '', $path);
            $path = 'tmp/UP/' . trim($path, '/') . '/';
            $this->options_user['export-prefs'] = $path;
            $log_export = '';
        }
        // fusion et controle des options
        $options = $this->ctrl_options($options_def);
        // bbcode
        $options['action-template'] = $this->get_bbcode($options['action-template'], false);
        $options['prefset-template'] = $this->get_bbcode($options['prefset-template'], false);
        $options['info-template'] = $this->get_bbcode($options['info-template'], false);
        $options['prefset-separator'] = $this->get_bbcode($options['prefset-separator'], false);
        $options['options-separator'] = $this->get_bbcode($options['options-separator'], false);
        // liste des noms de sections réservées par UP
        $prefset_exclude = explode(',', $options['prefset-exclude']);

        // les actions concernées
        if ($options[__class__] == '') {
            // toutes les actions
            $actionsList = $this->up_actions_list();
        } else {
            // uniquement celles demandées
            // TODO : voir à conserver $dico en global
            // charger le dictionnaire
            $dico = file_get_contents($this->upPath . 'dico.json');
            $dico = json_decode($dico, true);

            $tmp = array_map('trim', explode(',', $options[__class__]));
            foreach ($tmp as $key) {
                if (array_key_exists($key, $dico)) {
                    $key = $dico[$key];
                }
                $actionsList[] = str_replace('-', '_', $key);
            }
            // les actions à exclure
            if ($options['action-exclude'] == '1') {
                $actionsList = array_diff($this->up_actions_list(), $actionsList);
            }
        }

        // === css-head
        $this->load_css_head($options['css-head']);

        // === supprimer un ancien export pour le recréer
        if ($options['export-prefs']) {
            $options['export-prefs'] = rtrim($options['export-prefs'], '/') . '/';
            $this->deleteTree($options['export-prefs']);
            // -- copie de assets/custom
            $filelist = array();
            $regex = '/.*\.dist$|.*\.bak$|.*\.empty$|index.html/';
            $this->scanSubdir($filelist, $this->upPath . 'assets/custom', $regex, $this->upPath);
            $this->copyFilelist($filelist, $this->upPath, $options['export-prefs']);
        }

        // attributs du bloc principal
        $attr_main = array();
        $attr_main['id'] = $options['id'];
        $attr_main['class'] = $options['class'];
        $attr_main['style'] = $options['style'];
        // attributs bloc action
        $attr_action = array();
        $this->get_attr_style($attr_action, $options['action-class']);

        // ==== code en retour
        $html = array();
        // boucle sur actions
        foreach ($actionsList as $action) {

            // EXPORT CUSTOM
            if ($options['export-prefs']) {
                $regex = '/.*\.dist$|index.html/';
                $srcFolder = $this->upPath . 'actions/' . $action . '/custom/';
                $filelist = array();
                $this->scanSubdir($filelist, $srcFolder, $regex, $this->upPath);
                $this->copyFilelist($filelist, $this->upPath, $options['export-prefs']);
            }

            // le prefs.ini
            $pref_user = array();
            $pref_user_file = $this->upPath . 'actions/' . $action . '/custom/prefs.ini';
            $ok = file_exists($pref_user_file);
            if ($ok) {
                $pref_user = $this->load_inifile($pref_user_file, true);
                foreach ($prefset_exclude as $key) {
                    unset($pref_user[$key]);
                }
                $ok = ! empty($pref_user);
            }

            // les infos du webmaster
            $info = '';
            $info_file = $this->upPath . 'actions/' . $action . '/custom/help.txt';
            if (file_exists($info_file)) {
                $info = file_get_contents($info_file);
                // si texte brut, on ajoute les sauts de ligne
                if (strip_tags($info) == $info) {
                    $info = str_replace(PHP_EOL, '<br>', $info);
                }
                $ok = ($info !== false);
            }

            if (! $ok) {
                continue;
            }

            // DEBUT POUR UNE ACTION
            if ($options['action-class']) {
                $html[] = $this->set_attr_tag('div', $attr_action);
            }
            if ($options['action-template'] != '0') {
                $html[] = str_replace('##action##', $action, $options['action-template']);
            }
            // -- les notes du webmaster
            if ($info) {
                $html[] = str_replace('##info##', $info, $options['info-template']);
            }
            // boucle des prefs
            $first = true;
            foreach ($pref_user as $pref => $opts) {
                if (! in_array($pref, $prefset_exclude) && ! empty($opts)) {
                    if (strpos($options['prefset-template'], '##options##') !== false) {
                        $str = '';
                        // boucle des options
                        foreach ($opts as $opt => $val) {
                            // $val neutralisé
                            $str .= ($str) ? $options['options-separator'] : '';
                            $str .= '<b>' . $opt . '</b>=' . htmlentities($val);
                        }
                    }
                    $out = str_replace('##prefset##', $pref, $options['prefset-template']);
                    if ($first == false) {
                        $out = $options['prefset-separator'] . $out;
                    }
                    $html[] = str_replace('##options##', $str, $out);
                    $first = false;
                }
            }
            if ($options['action-class']) {
                $html[] = '</div>';
            }

            // FIN POUR UNE ACTION
        }

        if (isset($log_export)) {
            $this->msg_info($this->trad_keyword('EXPORT_PREFS_OK', $options['export-prefs']));
        }

        return $this->set_attr_tag('div', $attr_main, implode(PHP_EOL, $html));
    }

    // run

    /*
     * Supprime tous les dossiers et fichiers du répertoire indiqué
     */
    public function deleteTree($dir)
    {
        $dir = rtrim($dir, '/') . '/';
        foreach (glob($dir . '*') as $element) {
            if (is_dir($element)) {
                $this->deleteTree($element); // On rappel la fonction deleteTree
                rmdir($element); // Une fois le dossier courant vidé, on le supprime
            } else { // Sinon c'est un fichier, on le supprime
                unlink($element);
            }
            // On passe à l'élément suivant
        }
    }

    /*
     * retourne la liste de tous les fichiers d'un dossier et sous-dossiers
     * $regex_exclus : les fichiers exclus. ex: /*.dist\s|index.html/ (se terminant par .dist ou index.html)
     * $root : la racine retirée pour chemin relatif
     */
    public function scanSubdir(&$filelist, $folder, $regex_exclus = null, $root = '')
    {
        $tmp = glob(trim($folder, '/') . '/*');
        foreach ($tmp as $file) {
            if (is_dir($file)) {
                $this->scanSubdir($filelist, $file, $regex_exclus, $root);
            } else {
                if ($root) {
                    $rootSize = strlen($root);
                    $file = substr($file, strlen($root));
                }
                if ($regex_exclus) {
                    $foo = preg_match($regex_exclus, $file, $match);
                    if (preg_match($regex_exclus, $file, $match)) {
                        $file = '';
                    }
                }
                if ($file) {
                    $filelist[] = $file;
                }
            }
        }
    }

    // }

    /*
     * copie d'une liste de fichiers vers un dossier
     * $filelist : chemin relatif des fichiers
     * $srcRoot : racine fichiers source
     * $destRoot : racine fichiers dstination
     */
    public function copyFilelist($filelist, $srcRoot, $destRoot)
    {
        foreach ($filelist as $file) {
            if (file_exists($srcRoot . $file)) {
                if (! file_exists(dirname($destRoot . $file))) {
                    mkdir(dirname($destRoot . $file), 0777, true);
                }
                if (! copy($srcRoot . $file, $destRoot . $file)) {
                    $this->msg_error($this->trad_keyword('COPYFILE_ERR', $destRoot . $file));
                }
            }
        }
    }
}

// class
