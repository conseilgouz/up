<?php

/* @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a> */

// doc: https://docs.joomla.org/J3.x:Creating_a_simple_module/Adding_an_install-uninstall-update_script_file/fr
// voir flexicontactplus
// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;

/*
 *  Première installation de UP :
 *  - rien
 *
 *  Mise à jour de UP :
 *  - sauvegarde fichier config (assets/_variables.scss)
 *  -
 *  -
 *  - recalculer dico.json
 *  - restauration des fichiers de configuration
 */

class plgContentUpInstallerScript {
    
	private $dir  = null;
	private $lang = null;

	public function __construct()
	{
		$this->dir = __DIR__;
		$this->lang = Factory::getApplication()->getLanguage();
		$this->lang->load('plg_content_up');
	}

    /**
     * Method to install the extension
     * $parent is the class calling this method
     *
     * @return void
     */
    public function install($parent) {
        echo('<p>Le plugin a été installé</p>');
    }

    /**
     * Method to uninstall the extension
     * $parent is the class calling this method
     *
     * @return void
     */
    public function uninstall($parent) {
        echo('<p>Le plugin a été désinstallé</p>');
    }

    /**
     * Method to update the extension
     * $parent is the class calling this method
     *
     * @return void
     */
    public function update($parent) {
        $app = Factory::getApplication();
    }

    /**
     * Method to run before an install/update/uninstall method
     * $parent is the class calling this method
     * $type is the type of change (install, update or discover_install)
     *
     * @return void
     */
    public function preflight($type, $parent) {
        $app = Factory::getApplication();
        // $app->enqueueMessage('<p>actions avant l\'installation/mise à jour/désinstallation du plugin</p>');
        $path = JPATH_ROOT . '/plugins/content/up/';
        $ficVariablesBak = 'assets/custom/_variables.v' . $parent->getManifest()->version . '.scss.bak';

        // MAJ V2.5
        // déplacer le fichier 'assets/_variables.scss' vers 'assets/custom/_variables.scss'
        if (file_exists($path . 'assets/_variables.scss')) {
            rename($path . 'assets/_variables.scss', $path . 'assets/custom/_variables.scss');
        }
        // renommer tous les fichiers ACTION/up/options.ini en upbtn-options.ini
        $filelist = glob($path . 'actions/*/up/options.ini');
        foreach ($filelist AS $file) {
            rename($file, dirname($file) . '/upbtn-options.ini');
        }
        // si un fichier perso existe
		if ($type!='uninstall'){
			if (file_exists($path . 'assets/custom/_variables.scss')) {
				// si pas deja sauve pour cette version
				if (file_exists($path . $ficVariablesBak) === false) {
					copy($path . 'assets/custom/_variables.scss', $path . $ficVariablesBak);
					$app->enqueueMessage('<p>Une copie du fichier assets/_variables.scss a été créée sour le nom ' . $ficVariablesBak . '</p>');
				}
			}
		}
		$xml = simplexml_load_file(JPATH_SITE . '/plugins/content/up/up.xml');
		$previous_version = $xml->version;
        
		if ($type =='update'){ // clean up updated actions
            if ($previous_version <= '5.4.1') {
                $actionsList = ['jcat_image','jcategories_by_tags','jcategories_list','jcontent_by_categories','jcontent_by_subcat'
                               ,'jcontent_by_tags','jcontent_image','jcontent_in_content','jcontent_info','jcontent_metadata'
                               ,'jextensions_list','jmenus_list','jmenus_metadata','jmodules_list','pdf','php','sitemap','sql'
                               ,'upfilescleaner','upsearch'];
            } else { // version 5.4.2
                $actionsList = ['pdf'];
            }
            foreach ($actionsList as $action) {
                $dir = $path.'actions/' . $action;
                $this->delete_directory($dir);
            }
        }
    }

    /**
     * Method to run after an install/update/uninstall method
     * $parent is the class calling this method
     * $type is the type of change (install, update or discover_install)
     *
     * @return void
     */
    function postflight($type, $parent) {
        // echo('<p>actions après l\'installation/mise à jour/désinstallation du plugin</p>');
        $app = Factory::getApplication();
        $path = JPATH_ROOT . '/plugins/content/up/';
        $ficVariablesBak = 'assets/custom/_variables.v' . $parent->getManifest()->version . '.scss.bak';

        // nettoyage anciens fichiers inutiles
        $filelist[] = 'assets/scss/print.scss'; // remplacé par _print.scss
        foreach ($filelist AS $file) {
            if (file_exists($path . $file)) {
                if (unlink($path . $file))
                    $app->enqueueMessage('suppression : ' . $file);
            }
        }
        // nettoyage des fichiers checkfile
        $filelist = glob($path .'assets/up_checkfile.*');
        foreach ($filelist AS $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        // on ecrase le fichier (vide) _variables.scss par celui sauvegardé
		if (file_exists($path . $ficVariablesBak)===true && $type != 'uninstall') {
			copy($path . $ficVariablesBak, $path . 'assets/custom/_variables.scss');
			$app->enqueueMessage('<p>Le fichier "assets/custom/_variables.scss" est inchangé.</p>');
		}
		$cache = Factory::getContainer()->get(Joomla\CMS\Cache\CacheControllerFactoryInterface::class)->createCacheController();
        $cache->clean('_system');
    }
    /* 
    * from https://www.w3docs.com/snippets/php/how-do-i-recursively-delete-a-directory-and-its-entire-contents-files-sub-dirs-in-php.html
    *
    * supprime les fichiers d'une action, sauf le répertoire custom
    */
    private function delete_directory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..' || $item == 'custom') {
                continue;
            }
            if (!$this->delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        return rmdir($dir);
    }

}
