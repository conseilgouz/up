<?php

/**
 * Efface les fichiers du cache  
 *
 * Supprime tous les fichiers PHP du sous-dossier indiqué en option. com_content par défaut
 *
 * syntaxe {up cache-cleaner=com_modules} // defaut : com_content
 *
 * @version  UP-2.6
 * @author   Lomart
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Expert
 *
 */

/*
 * v31 - folder-exclude : liste des dossiers à conserver
 */
defined('_JEXEC') or die();

use Joomla\CMS\Factory;

class cache_cleaner extends upAction
{

    function init()
    {
        return true;
    }

    function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => 'com_content', // liste des dossiers du cache ou * pour tous (séparateur: point-virgule)
            'folder-exclude' => '', // chemin relatif (à folder-cache) des dossiers à conserver (séparateur: point-virgule)
            'folder-cache' => '', // dossier racine du cache. vide=celui défini dans la configuration Joomla
            'file-mask' => '*.php' // masque pour sélectionner les fichiers. *.* = tous, *.{php,html} = php et html
        );

        if (! isset($this->options_user['folder-cache']))
            $this->options_user['folder-cache'] = Factory::getConfig()->get('cache_path', 'cache');

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        $this->debug = (isset($this->options_user['debug']));
        $this->debugMsg = array();

        // dossier de base du cache
        $cachepath = $this->path_normalize($options['folder-cache']);
        $cachepath = rtrim($cachepath, ' /\\') . DIRECTORY_SEPARATOR;
        // bloquer les dossiers Joomla en racine
        $ctrlpath = substr($this->get_url_absolute($cachepath), strlen($this->get_url_absolute('')));
        $nivone = substr($ctrlpath, 0, strpos($ctrlpath, '/'));
        // autorisés en racine cache et tmp
        $joomlaRep = explode(',', ',administrator,api,cli,components,images,includes,language,layouts,libraries,media,modules,plugins,templates');
        if (in_array(strtolower($nivone), $joomlaRep))
            return $this->trad_keyword('ROOT_PROHIBITED', $nivone);

        // sous-dossiers demandés
        // tous si demande exclusion
        if ($options[__class__] == '*') {
            $folders = glob($cachepath . '*', GLOB_ONLYDIR);
            for ($i = 0; $i < count($folders); $i ++) {
                $folders[$i] = substr($folders[$i], strlen($cachepath));
            }
        } else {
            $folders = array_map('trim', explode(';', $options[__class__]));
        }
        // sous-dossiers exclus
        $this->folders_exclude = array();
        if (! empty($this->options_user['folder-exclude'])) {
            $options['folder-exclude'] = $this->path_normalize($options['folder-exclude']);
            $this->folders_exclude = array_map('trim', explode(';', $options['folder-exclude']));
            for ($i = 0; $i < count($this->folders_exclude); $i ++) {
                $this->folders_exclude[$i] = $cachepath . rtrim($this->folders_exclude[$i], '/\\') . DIRECTORY_SEPARATOR;
            }
        }

        // POUR DEBUG : liste des sous-dossiers de cache + alerte si sous-dossier cache non trouvé
        if (empty($folders)) {
            $this->debugMsg[] = $this->trad_keyword('SUBDIR_NONE');
        } else {
            $this->debugMsg[] = $this->trad_keyword('SUBDIR_LIST', implode(' | ', $folders));
            if (! empty($this->options_user['folder-exclude']))
                $this->debugMsg[] = $this->trad_keyword('EXCLUDE_FOLDERS_LIST', implode(' | ', $this->folders_exclude));
        }
        // alerte si dossiers non trouvés
        foreach ($folders as $k => $folder) {
            if (! is_dir($cachepath . $folder)) {
                $folders_notfound[] = $folder;
                unset($folders[$k]);
            }
        }
        if (! empty($folders_notfound))
            $this->debugMsg[] = $this->trad_keyword('SUBDIR_NOT_FOUND', $cachepath) . implode(' | ', $folders_notfound);

        // === Supprime le cache
        $this->debugMsg[] = $this->trad_keyword('DEBUG_SIMULATION');
        $this->debugMsg[] = $this->trad_keyword('CLEANING_LOG');
        foreach ($folders as $folder) {
            $this->deleteTree($cachepath . $folder . DIRECTORY_SEPARATOR, $options['file-mask']);
        }
        // supprime fichiers en racine de folder-cache selon file-mask
        foreach (glob($cachepath . $options['file-mask']) as $file) {
            $filename = basename($file);
            if ($filename[0] != '.') {
                if ($this->debug) {
                    $msg = $this->trad_keyword('DELETE_SIMULATION');
                } else {
                    $msg = (unlink($file)) ? $this->trad_keyword('DELETE') : $this->trad_keyword('DELETE_ERROR');
                }
                $this->debugMsg[] = $msg . ' : ' . $file;
            }
        }

        if ($this->debug) {
            $this->msg_info(implode('<br>', $this->debugMsg));
        }
        return '';
    }

    // run

    /*
     * Supprime tous les dossiers et fichiers du répertoire indiqué
     */
    function deleteTree($dir, $mask)
    {
        if (in_array($dir, $this->folders_exclude)) {
            $this->debugMsg[] = $this->trad_keyword('FOLDER_EXCLUDE', $dir);
            return;
        }
        foreach (glob($dir . $mask) as $file) {
            $filename = basename($file);
            if ($filename[0] != '.') {
                $chmod1 = substr(sprintf('%o', fileperms($file)), - 4);
                $ok = @chmod($file, 0777);
                $chmod2 = substr(sprintf('%o', fileperms($file)), - 4);
                if ($this->debug) {
                    $msg = $this->trad_keyword('DEBUG_CHMOD', $chmod1 . '->' . $chmod2);
                } else {
                    $msg = (unlink($file)) ? '<i>[OK [' . $chmod2 . '] </i>' : '<i>NO [' . $chmod1 . '->' . $chmod2 . '] ';
                }
                $this->debugMsg[] = $msg . ' : ' . $file;
            }
        }
        // suppression contenu sous-dossiers
        foreach (glob($dir . '*', GLOB_ONLYDIR) as $subdir) {
            if (! $this->debug)
                $this->deleteTree($subdir . DIRECTORY_SEPARATOR, $mask); // On rappel la fonction deleteTree
                $this->debugMsg[] = $this->trad_keyword('DELETE_TREE' ,$subdir);
        }
        // suppression dossier
        if (empty(glob($dir . '*'))) {
            $this->debugMsg[] = $this->trad_keyword('REMOVE_EMPTY_FOLDER', $dir);
            $ok = rmdir($dir); // si le dossier est vide, on le supprime
        }
    }

    /*
     * remplace les séparateurs de chemin
     */
    function path_normalize($path)
    {
        return str_replace(array(
            '/',
            '\\'
        ), DIRECTORY_SEPARATOR, $path);
    }
}

// class
