<?php

/**
 * permet d'exécuter du code PHP dans un article.
 *
 * Exemples :
 * date actuelle :  {up php=echo date('d-m-Y H:i:s');}
 * langage : {up php=echo JFactory::getLanguage()getTag(); }
 * nom user : {up php=  $user = JFactory::getUser(); echo  ($user->guest!=1) ? $user->username : 'invité'; }
 *
 * @author   LOMART
 * @version  UP-1.0
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags  Expert
 */

/*
 * v1.7 : corrige les caractéres <> convertis par les éditeur wysiwyg
 * v2.8.1: ajout option tag pour insertion class et style
 * v5.1 : ajout spaceNormalize pour supprimer espaces durs issus de copier-coller
 *      : ajout option authorized-functions pour permettre ponctuellement l'utilisation d'une fonction interdite
 */
defined('_JEXEC') or die();
use Lomart\Plugin\Content\Up\Helper\UpHelper;
class php extends Lomart\Plugin\Content\Up\Extension\Up
{

    function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    function run()
    {
        // lien vers la page de demo (vide=page sur le site de UP)
        UpHelper::set_demopage($this);

        $options_def = array(
            __class__ => '', // le code PHP
            'authorized-functions' => '', // liste des exemptions à la liste des fonctions bannies. séparateur virgule
            /* [st-css] Style CSS */
            'tag' => 'div', // balise utilisée pour les classes, styles et id si class ou style définis
            'id' => '', // identifiant
            'class' => '', // classe(s) ou style pour le bloc retour'
            'style' => '', // classe(s) ou style pour le bloc retour'
            /* [st-divers] Divers */
            'filter' => '' // conditions. Voir doc action filter (v1.8)
        );

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);

        // === Filtrage
        if (UpHelper::filter_ok($this,$options['filter']) !== true) {
            return '';
        }

        $phpCode = $options[__class__];
        
        // Restaurer les caractères modifiés par les éditeurs wysiwyg (v1.7)
        // ex JCE : "&lt;":"<","&gt;":">","&amp;":"&","&quot;":'"',"&apos;":"'"},
        $search = array(
            '&lt;',
            '&gt;',
            '&amp;',
            '&quot;',
            '&apos;'
        );
        $replace = array(
            '<',
            '>',
            '&',
            '"',
            "'"
        );
        $phpCode = str_replace($search, $replace, $phpCode);
        $phpCode = UpHelper::spaceNormalize($this,$phpCode);

        // liste des fonctions interdites
        $block_list = explode(' ', 'assert base64_decode basename call_user_func call_user_func_array chgrp chmod chown clearstatcache copy delete dirname disk_free_space disk_total_space diskfreespace escapeshellcmd eval exec fclose feof fflush fgetc fgetcsv fgets fgetss file_exists file_get_contents file_put_contents file fileatime filectime filegroup fileinode filemtime fileowner fileperms filesize filetype flock fnmatch fopen fpassthru fputcsv fputs fread fscanf fseek fsockopen fstat ftell ftruncate fwrite glob include include_once lchgrp lchown link linkinfo lstat mkdir move_uploaded_file opendir parse_ini_file passthru pathinfo pclose popen pcntl_exec preg_replace proc_close proc_open readfile readdir readllink realpath rename require require_once rewind rmdir set_file_buffer shell_exec stat symlink system tempnam tmpfile touch umask unlink');
        if (! empty($options['authorized-functions'])) {
            $no_block_list = explode(',', $options['authorized-functions']);
            $block_list = array_diff($block_list, $no_block_list);
        }
        // ====> Contrôle du code
        $errmsg = '';
        $function_list = array();
        // liste des fonctions dans l'argument
        if (preg_match_all('/([a-zA-Z0-9_]+)\s*[(|"|\']/s', $phpCode, $matches)) {
            $function_list = $matches[1];
        }
        // Recherche dans la liste des interdits
        foreach ($function_list as $command) {
            if (in_array($command, $block_list)) {
                $errmsg = $command;
                break;
            }
        }

        // ====> Execution du code
        if ($errmsg == '') {
            ob_start();
            eval($phpCode);
            $out = ob_get_contents();
            ob_end_clean();
        } else {
            $out = UpHelper::msg_inline($this,'****** INVALID CODE IN PHP : ' . $errmsg . ' ******');
        }

        if ($options['class'] || $options['style']) {
            $attr['id'] = $options['id'];
            UpHelper::get_attr_style($this,$attr, $options['class'], $options['style']);
            $out = UpHelper::set_attr_tag($this,$options['tag'], $attr, $out);
        }
        return $out;
    }

    // run
}

// class
