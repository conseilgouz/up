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
use Lomart\Plugin\Content\Up\Helper\UpHelper;

class cache_cleaner extends Lomart\Plugin\Content\Up\Extension\Up
{

    function init()
    {
        return true;
    }

    function run()
    {

        // lien vers la page de demo
        UpHelper::set_demopage($this);

        $options_def = array(
            __class__ => 'com_content', // liste des dossiers du cache ou * pour tous (séparateur: point-virgule)
            'folder-exclude' => '', // chemin relatif (à folder-cache) des dossiers à conserver (séparateur: point-virgule)
            'folder-cache' => '', // dossier racine du cache. vide=celui défini dans la configuration Joomla
            'file-mask' => '*.php' // masque pour sélectionner les fichiers. *.* = tous, *.{php,html} = php et html
        );

        if (! isset($this->options_user['folder-cache']))
            $this->options_user['folder-cache'] = Factory::getConfig()->get('cache_path', 'cache');

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);

        $this->debug = (isset($this->options_user['debug']));
        $this->debugMsg = array();

        // dossier de base du cache
        $cachepath = UpHelper::path_normalize($this,$options['folder-cache']);
        $cachepath = rtrim($cachepath, ' /\\') . DIRECTORY_SEPARATOR;
        // bloquer les dossiers Joomla en racine
        $ctrlpath = substr(UpHelper::get_url_absolute($this,$cachepath), strlen(UpHelper::get_url_absolute($this,'')));
        $nivone = substr($ctrlpath, 0, strpos($ctrlpath, '/'));
        // autorisés en racine cache et tmp
        $joomlaRep = explode(',', ',administrator,api,cli,components,images,includes,language,layouts,libraries,media,modules,plugins,templates');
        if (in_array(strtolower($nivone), $joomlaRep))
            return UpHelper::trad_keyword($this,'ROOT_PROHIBITED', $nivone);

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
            $options['folder-exclude'] = UpHelper::path_normalize($this,$options['folder-exclude']);
            $this->folders_exclude = array_map('trim', explode(';', $options['folder-exclude']));
            for ($i = 0; $i < count($this->folders_exclude); $i ++) {
                $this->folders_exclude[$i] = $cachepath . rtrim($this->folders_exclude[$i], '/\\') . DIRECTORY_SEPARATOR;
            }
        }

        // POUR DEBUG : liste des sous-dossiers de cache + alerte si sous-dossier cache non trouvé
        if (empty($folders)) {
            $this->debugMsg[] = UpHelper::trad_keyword($this,'SUBDIR_NONE');
        } else {
            $this->debugMsg[] = UpHelper::trad_keyword($this,'SUBDIR_LIST', implode(' | ', $folders));
            if (! empty($this->options_user['folder-exclude']))
                $this->debugMsg[] = UpHelper::trad_keyword($this,'EXCLUDE_FOLDERS_LIST', implode(' | ', $this->folders_exclude));
        }
        // alerte si dossiers non trouvés
        foreach ($folders as $k => $folder) {
            if (! is_dir($cachepath . $folder)) {
                $folders_notfound[] = $folder;
                unset($folders[$k]);
            }
        }
        if (! empty($folders_notfound))
            $this->debugMsg[] = UpHelper::trad_keyword($this,'SUBDIR_NOT_FOUND', $cachepath) . implode(' | ', $folders_notfound);

        // === Supprime le cache
        $this->debugMsg[] = UpHelper::trad_keyword($this,'DEBUG_SIMULATION');
        $this->debugMsg[] = UpHelper::trad_keyword($this,'CLEANING_LOG');
        foreach ($folders as $folder) {
            UpHelper::deleteTree($this,$cachepath . $folder . DIRECTORY_SEPARATOR, $options['file-mask']);
        }
        // supprime fichiers en racine de folder-cache selon file-mask
        foreach (glob($cachepath . $options['file-mask']) as $file) {
            $filename = basename($file);
            if ($filename[0] != '.') {
                if ($this->debug) {
                    $msg = UpHelper::trad_keyword($this,'DELETE_SIMULATION');
                } else {
                    $msg = (unlink($file)) ? UpHelper::trad_keyword($this,'DELETE') : UpHelper::trad_keyword($this,'DELETE_ERROR');
                }
                $this->debugMsg[] = $msg . ' : ' . $file;
            }
        }

        if ($this->debug) {
            UpHelper::msg_info($this,implode('<br>', $this->debugMsg));
        }
        return '';
    }

    // run


}

// class
