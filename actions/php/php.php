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

class php extends upAction
{

    function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    function run()
    {
        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

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
        $options = $this->ctrl_options($options_def);

        // === Filtrage
        if ($this->filter_ok($options['filter']) !== true) {
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
        $phpCode = $this->spaceNormalize($phpCode);

        // liste des fonctions interdites
        $block_list = explode(' ', 'basename chgrp chmod chown clearstatcache copy delete dirname disk_free_space disk_total_space diskfreespace fclose feof fflush fgetc fgetcsv fgets fgetss file_exists file_get_contents file_put_contents file fileatime filectime filegroup fileinode filemtime fileowner fileperms filesize filetype flock fnmatch fopen fpassthru fputcsv fputs fread fscanf fseek fstat ftell ftruncate fwrite glob lchgrp lchown link linkinfo lstat move_uploaded_file opendir parse_ini_file pathinfo pclose popen readfile readdir readllink realpath rename rewind rmdir set_file_buffer stat symlink tempnam tmpfile touch umask unlink fsockopen system exec passthru escapeshellcmd pcntl_exec proc_open proc_close mkdir rmdir base64_decode');
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
            $out = $this->msg_inline('****** INVALID CODE IN PHP : ' . $errmsg . ' ******');
        }

        if ($options['class'] || $options['style']) {
            $attr['id'] = $options['id'];
            $this->get_attr_style($attr, $options['class'], $options['style']);
            $out = $this->set_attr_tag($options['tag'], $attr, $out);
        }
        return $out;
    }

    // run
}

// class
