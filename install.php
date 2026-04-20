<?php

/* @license   <a href="https://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a> */

// doc: https://docs.joomla.org/J3.x:Creating_a_simple_module/Adding_an_install-uninstall-update_script_file/fr

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Archive\Archive;
use Joomla\Archive\Zip;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Version;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Database\DatabaseInterface;

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

class plgContentUpInstallerScript
{
    private $dir  = null;
    private $lang = null;
    private $min_joomla_version      = '5.2.0';
    private $min_php_version         = '8.1';
    private $installerName = 'plgcontentupinstaller';
    private $actions_obsoletes = ['add-html','animate','article_category','audio','facebook','googlemap','jmetadata','jnews','lorempixel','lorem_placeimg','video','vimeo','youtube'];
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
    public function install($parent)
    {
        echo('<p>Le plugin a été installé</p>');
    }

    /**
     * Method to uninstall the extension
     * $parent is the class calling this method
     *
     * @return void
     */
    public function uninstall($parent)
    {
        echo('<p>Le plugin a été désinstallé</p>');
    }

    /**
     * Method to run before an install/update/uninstall method
     * $parent is the class calling this method
     * $type is the type of change (install, update or discover_install)
     *
     * @return void
     */
    public function preflight($type, $parent)
    {

        if (! $this->passMinimumJoomlaVersion()) {
            $this->uninstallInstaller();
            return false;
        }

        if (! $this->passMinimumPHPVersion()) {
            $this->uninstallInstaller();
            return false;
        }
        // vérifie s'il y a des actions personnalisées à migrer en UP 6.0
        $actionsList = $this->up_actions();
        $other = $this->up_otheractions_list($actionsList);

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
        foreach ($filelist as $file) {
            rename($file, dirname($file) . '/upbtn-options.ini');
        }
        // si un fichier perso existe
        if ($type != 'uninstall') {
            if (file_exists($path . 'assets/custom/_variables.scss')) {
                // si pas deja sauve pour cette version
                if (file_exists($path . $ficVariablesBak) === false) {
                    copy($path . 'assets/custom/_variables.scss', $path . $ficVariablesBak);
                    $app->enqueueMessage('<p>Une copie du fichier assets/_variables.scss a été créée sour le nom ' . $ficVariablesBak . '</p>');
                }
            }
        }
        $previous_version = false;
        $actionsList = [];
        if ($type == 'update') {// clean up updated actions
            $xml = simplexml_load_file(JPATH_SITE . '/plugins/content/up/up.xml');
            $previous_version = $xml->version;
            if ($previous_version && $previous_version < '6.0.0') { // on était avant la version 6.0.0
                $this->save_actions(); // sauvegarde du répertoire actions avant nettoyage
                $actionsList = $this->up_actions(); // toutes les actions UP ont été modifiées
                $actionsList = $this->up_actions_obsoletes($actionsList); // liste des actions obsolètes en 6.0.0
            } else if ($previous_version && $previous_version < '6.0.13') { // 6.0.13 : mise à jour de l'action pdf
                $actionsList[] = "pdf";
            } 
			// 6.0.20 : mise à jour de l'action table_sort/faq
            $actionsList[] = "table_sort";
            $actionsList[] = "faq";
            $actionsList[] = "sql";
            foreach ($actionsList as $action) {
                $dir = $path.'actions/' . $action;
                $this->delete_directory($dir);
            }
        }
    }
    // sauvegarde des actions avant installation de la version 6.0 de UP
    public function save_actions()
    {
        $this->zip('*', JPATH_ROOT . '/plugins/content/up/actions_avant_up6.zip');
        Factory::getApplication()->enqueueMessage('<p>Un fichier zip du répertoire actions a été créée sous le nom <b>actions_avant_up6.zip</b>.</p>');
    }
    public function zip($source, $destination, $include_dir = false, $exclusions = false)
    {
        // Remove existing archive
        if (file_exists($destination)) {
            unlink($destination);
        }
        $zip = (new Archive())->getAdapter('zip');
        $folder = JPATH_ROOT . '/plugins/content/up/actions';
        $zipFilesArray = [];
        $zipFilesArray = $this->ziplist($zipFilesArray, $folder);
        $zip->create($destination, $zipFilesArray);
    }
    public function ziplist(&$arr, $from, $base = false)
    {
        if (!file_exists($from)) {
            Factory::getApplication()->enqueueMessage('Fichier '.$from.' non trouvé', 'error');
            return false;
        }
        $dir = opendir($from);
        if (!$base) {
            $base = 'actions';
        }
        while (false !== ($file = readdir($dir))) {
            if ($file == '.' or $file == '..') {
                continue;
            }
            if (is_dir($from . '/' . $file)) {
                $zip = $this->ziplist($arr, $from . '/' . $file, $base.'/'.$file);
            } else {
                $name = $base . '/' . $file;
                $data = file_get_contents($from . '/' . $file) ;
                $arr[] = ['name' => $name, 'data' => $data];
            }
        }
        return $arr;

    }
    // récupère la liste des actions UP à partir du fichier UP-list-actions-versions.txt
    // tel que défini dans l'installation
    public function up_actions()
    {
        $upPath = __DIR__; // répertoire d'installation
        $file = $upPath.'/assets/UP-list-actions-version.txt';
        $actions = [];
        if (!is_file($file)) {
            return false;
        }
        $readBuffer = file($file, FILE_IGNORE_NEW_LINES);
        if (!$readBuffer) {// `file` couldn't read the htaccess we can't do anything at this point
            return '';
        }
        foreach ($readBuffer as $line) {
            $one = explode(':', $line);
            if (sizeof($one) > 1) {
                $actions[] = $one[0];
            }
        }
        return $actions;
    }
    // récupère la liste des actions UP obsolètes en 6.0.0
    public function up_actions_obsoletes($actions)
    {

        foreach ($this->actions_obsoletes as $one) {
            $actions[] = $one;
        }
        return $actions;
    }
    public function up_otheractions_list($actions, $exclude_prefix = '_,x_')
    {
        $path = JPATH_ROOT . '/plugins/content/up/'; // répertoire actuel de UP
        $actionsFolder = $path . 'actions' . DIRECTORY_SEPARATOR;
        $list = array(); // retour si vide
        $actionsPathList = glob($actionsFolder . '*', GLOB_ONLYDIR);

        $prefix = array_map('trim', explode(',', $exclude_prefix));
        foreach ($actionsPathList as $e) {
            $file = substr($e, strlen($actionsFolder));
            if (in_array($file, $actions)) { // dans les actions standards ?
                continue;
            }
            if (in_array($file, $this->actions_obsoletes)) { //dans les actions obsoletes ?
                continue;
            }
            $ok = true;
            foreach ($prefix as $p) {
                $res = stripos($file, $p);
                $ok = ($ok && stripos($file, $p) !== 0);
            }
            $phpfile = $actionsFolder . $file . DIRECTORY_SEPARATOR . $file . '.php'; // v2.6 si dossier vide
            if ($ok && file_exists($phpfile)) {
                $ret = $this->checkVersion($file, $path.'actions/' . $file . '/' . $file . '.php');
                if (!$ret) { // action à migrer en UP 6.0
                    $list[] = $file;
                }
            }
        }
        return $list;
    }
    public function checkVersion($action, $file)
    {
        $app = Factory::getApplication();
        try {
            @include_once $file;
        } catch (\Throwable $throwable) {
            if (strpos($throwable->getMessage(), "Lomart\Plugin\Content\Up\Extension\Up")) {
                // classe UP6  mais le namespace n'a pas encore été installé
                return true;
            }
            // on doit être sur une autre classe.
            Factory::getApplication()->enqueueMessage(
                'Action à migrer en UP 6.0 détectée : ' . $action .' : '.$throwable->getMessage().'<br>Informations complementaires dans <a href="https://up.lomart.fr/docs/aide-memoire/aide-memoire-developpeur-bis" target="_blank">Aide Mémoire Développeur UP</a>',
                'warning'
            );
            return false;
        }
        return true;
    }
    /**
     * Method to run after an install/update/uninstall method
     * $parent is the class calling this method
     * $type is the type of change (install, update or discover_install)
     *
     * @return void
     */
    public function postflight($type, $parent)
    {
        // echo('<p>actions après l\'installation/mise à jour/désinstallation du plugin</p>');
        if ($type != 'install' && $type != 'update') {
            return;
        }
        $app = Factory::getApplication();
        $path = JPATH_ROOT . '/plugins/content/up/';
        $ficVariablesBak = 'assets/custom/_variables.v' . $parent->getManifest()->version . '.scss.bak';

        // nettoyage anciens fichiers inutiles
        $filelist[] = 'assets/scss/print.scss'; // remplacé par _print.scss
        $filelist[] = 'assets/js/faq.js'; // 6.0.14 : utilisation bootstrap collapse
        foreach ($filelist as $file) {
            if (file_exists($path . $file)) {
                if (unlink($path . $file)) {
                    $app->enqueueMessage('suppression : ' . $file);
                }
            }
        }
        // enable plugin
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $conditions = array(
            $db->qn('type') . ' = ' . $db->q('plugin'),
            $db->qn('folder') . ' = ' . $db->q('content'),
            $db->qn('element') . ' = ' . $db->quote('up')
        );
        $fields = array($db->qn('enabled') . ' = 1');
        $query = $db->getQuery(true);
        $query->update($db->quoteName('#__extensions'))->set($fields)->where($conditions);
        $db->setQuery($query);
        try {
            $db->execute();
        } catch (RuntimeException $e) {
            $app->enqueueMessage('-------->  Erreur à l\'activation du plugin UP <-----------');
        }
        // nettoyage des fichiers checkfile
        $filelist = glob($path .'assets/up_checkfile.*');
        foreach ($filelist as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        // on ecrase le fichier (vide) _variables.scss par celui sauvegardé
        if (file_exists($path . $ficVariablesBak) === true && $type != 'uninstall') {
            copy($path . $ficVariablesBak, $path . 'assets/custom/_variables.scss');
            $app->enqueueMessage('<p>Le fichier "assets/custom/_variables.scss" est inchangé.</p>');
        }
        // vérifie si besoin de recompiler le scss avec des nouvelles valeurs de breakpoints
        // 1. récupération des valeurs définies dans les paramètres généraux de UP
        $up_params = (array) PluginHelper::getPlugin('content', 'up');
        if (isset($up_params['params'])) {
            $params = json_decode($up_params['params']);
            $sizes = [];
            if ($params->loadcss == 1) { // utilisation du css de UP
                if (isset($params->breaks) && $params->breaks) {
                    $sizes['s'] =  $params->breaks;
                }
                if (isset($params->breakm) && $params->breakm) {
                    $sizes['m'] = $params->breakm;
                }
                if (isset($params->breaksl) && $params->breaksl) {
                    $sizes['sl'] = $params->breaksl;
                }
                if (isset($params->breakl) && $params->breakl) {
                    $sizes['l'] =  $params->breakl;
                }
                if (isset($params->breakxl) && $params->breakxl) {
                    $sizes['xl'] =  $params->breakxl;
                }
            }
            if (count($sizes)) {
                // on a saisi des paramètres breakpoints : regénération du fichier up.css
                $val = '';
                $up = new Lomart\Plugin\Content\Up\Extension\Up($val);
                $up->store_scss($sizes);  // mise à jour du fichier assets/custom/_variables.scss
                $up->compile_scss();      // génération du fichier up.css
                $app->enqueueMessage('<p>Fichier up.css généré avec vos personnalisations.</p>');
            }
        }
        // nettoyage du cache
        $cacheModel = Factory::getApplication()->bootComponent('com_cache')->getMVCFactory()->createModel('Cache', 'Administrator', ['ignore_request' => true]);
        $cache = $cacheModel->getCache() ?? null;
        if ($cache) {
            foreach ($cache->getAll() as $group) {
                $cache->clean($group->group);
            }
            $app->enqueueMessage('<p>Cache OK.</p>');
        }
        return;
    }
    /*
    * from https://www.w3docs.com/snippets/php/how-do-i-recursively-delete-a-directory-and-its-entire-contents-files-sub-dirs-in-php.html
    *
    * supprime les fichiers d'un répertoire, sauf le répertoire custom pour les actions
    */
    private function delete_directory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        $empty = true;
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if ($item == 'custom') { // keep custom folder
                $empty = false;
                continue;
            }
            if (!$this->delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        if ($empty) {
            rmdir($dir);
        }
        return true;
    }
    // Check if Joomla version passes minimum requirement
    private function passMinimumJoomlaVersion()
    {
        $j = new Version();
        $version = $j->getShortVersion();
        if (version_compare($version, $this->min_joomla_version, '<')) {
            Factory::getApplication()->enqueueMessage(
                'Incompatible Joomla version : found <strong>' . $version . '</strong>, Minimum : <strong>' . $this->min_joomla_version . '</strong>',
                'error'
            );

            return false;
        }

        return true;
    }

    // Check if PHP version passes minimum requirement
    private function passMinimumPHPVersion()
    {

        if (version_compare(PHP_VERSION, $this->min_php_version, '<')) {
            Factory::getApplication()->enqueueMessage(
                'Incompatible PHP version : found  <strong>' . PHP_VERSION . '</strong>, Minimum <strong>' . $this->min_php_version . '</strong>',
                'error'
            );
            return false;
        }

        return true;
    }
    private function uninstallInstaller()
    {
        if (! is_dir(JPATH_PLUGINS . '/system/' . $this->installerName)) {
            return;
        }
        $this->delete([
            JPATH_PLUGINS . '/system/' . $this->installerName . '/language',
            JPATH_PLUGINS . '/system/' . $this->installerName,
        ]);
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery()
            ->delete('#__extensions')
            ->where($db->quoteName('element') . ' = ' . $db->quote($this->installerName))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'));
        $db->setQuery($query);
        $db->execute();
        $cacheModel = Factory::getApplication()->bootComponent('com_cache')->getMVCFactory()->createModel('Cache', 'Administrator', ['ignore_request' => true]);
        $cache = $cacheModel->getCache() ?? null;
        if ($cache) {
            foreach ($cache->getAll() as $group) {
                $cache->clean($group->group);
            }
            Factory::getApplication()->enqueueMessage('<p>Cache Ok.</p>');
        }
    }
    public function delete($files = [])
    {
        foreach ($files as $file) {
            if (is_dir($file)) {
                Folder::delete($file);
            }

            if (is_file($file)) {
                File::delete($file);
            }
        }
    }
}
