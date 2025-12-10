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

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class upprefset extends Lomart\Plugin\Content\Up\Extension\Up
{
    public function init()
    {
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        UpHelper::set_demopage($this);

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
        $options = UpHelper::ctrl_options($this,$options_def);
        // bbcode
        $options['action-template'] = UpHelper::get_bbcode($this,$options['action-template'], false);
        $options['prefset-template'] = UpHelper::get_bbcode($this,$options['prefset-template'], false);
        $options['info-template'] = UpHelper::get_bbcode($this,$options['info-template'], false);
        $options['prefset-separator'] = UpHelper::get_bbcode($this,$options['prefset-separator'], false);
        $options['options-separator'] = UpHelper::get_bbcode($this,$options['options-separator'], false);
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
        UpHelper::load_css_head($this,$options['css-head']);

        // === supprimer un ancien export pour le recréer
        if ($options['export-prefs']) {
            $options['export-prefs'] = rtrim($options['export-prefs'], '/') . '/';
            UpHelper::deleteTree($this,$options['export-prefs']);
            // -- copie de assets/custom
            $filelist = array();
            $regex = '/.*\.dist$|.*\.bak$|.*\.empty$|index.html/';
            UpHelper::scanSubdir($this,$filelist, $this->upPath . 'assets/custom', $regex, $this->upPath);
            UpHelper::copyFilelist($this,$filelist, $this->upPath, $options['export-prefs']);
        }

        // attributs du bloc principal
        $attr_main = array();
        $attr_main['id'] = $options['id'];
        $attr_main['class'] = $options['class'];
        $attr_main['style'] = $options['style'];
        // attributs bloc action
        $attr_action = array();
        UpHelper::get_attr_style($this,$attr_action, $options['action-class']);

        // ==== code en retour
        $html = array();
        // boucle sur actions
        foreach ($actionsList as $action) {

            // EXPORT CUSTOM
            if ($options['export-prefs']) {
                $regex = '/.*\.dist$|index.html/';
                $srcFolder = $this->upPath . 'actions/' . $action . '/custom/';
                $filelist = array();
                UpHelper::scanSubdir($this,$filelist, $srcFolder, $regex, $this->upPath);
                UpHelper::copyFilelist($this,$filelist, $this->upPath, $options['export-prefs']);
            }

            // le prefs.ini
            $pref_user = array();
            $pref_user_file = $this->upPath . 'actions/' . $action . '/custom/prefs.ini';
            $ok = file_exists($pref_user_file);
            if ($ok) {
                $pref_user = UpHelper::load_inifile($this,$pref_user_file, true);
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
                $html[] = UpHelper::set_attr_tag($this,'div', $attr_action);
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
            UpHelper::msg_info($this,UpHelper::trad_keyword($this,'EXPORT_PREFS_OK', $options['export-prefs']));
        }

        return UpHelper::set_attr_tag($this,'div', $attr_main, implode(PHP_EOL, $html));
    }

    // run


}

// class
