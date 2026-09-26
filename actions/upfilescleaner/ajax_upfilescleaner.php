<?php

defined('_JEXEC') or die();

/**
 * /* @license <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 */
use Joomla\CMS\Factory;
use Lomart\Plugin\Content\Up\Helper\UpHelper;

class upfilescleaner
{
    public static function goAjax($input)
    {
        $user = Factory::getApplication()->getIdentity();
        if (!$user->authorise('core.manage')) {
            return 'Not for your eyes';
        }
        $actionName = 'upfilescleaner';
        $action = new $actionName($actionName);

        $data = $input->get('data', '', 'string');
        parse_str($data, $output);
        $up = $output['upid'];
        $session = Factory::getApplication()->getSession();
        $base = $session->get($up.'upfilescleaner.source');
        $ext_list = explode(',', $session->get($up.'upfilescleaner.extensions'));

        $folder_source = JPATH_BASE .'/';
        $base = $session->get($up.'upfilescleaner.source');

        $backup = $session->get($up.'upfilescleaner.folderbackup');

        $folder_backup = JPATH_BASE .'/'. $backup.'/';

        // on récupère la liste des fichiers à déplacer
        $files_move = file_get_contents($folder_backup.'/upfilescleaner-files.txt');
        $files_move = explode(PHP_EOL, $files_move);
        foreach ($files_move as $file) {
            if (strpos($file, '..') !== false || strpos($file, $base) !== 0) { // forgery
                continue; // ignore it
            }
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($ext, $ext_list)) { // not in list : ignore it
                continue;
            }
            if (file_exists($folder_source.$file)) {
                if (! file_exists(dirname($folder_backup . $file))) {
                    mkdir(dirname($folder_backup .$file), 0777, true);
                }
                if (! rename($folder_source . $file, $folder_backup . $file)) {
                    return $action->lang('en=Error when moving files;fr=Erreur lors du déplacement des fichiers');
                }
            }
        }
        $purge = $session->get($up.'upfilescleaner.folderpurge', 0);
        if ($purge == 1) {
            // la 1ere ligne contient les fichiers inutiles
            // la suite, la liste des dossiers
            $folder_purge = file_get_contents($folder_backup.'/upfilescleaner-folder-purge.txt');
            $folder_purge = explode(PHP_EOL, $folder_purge);
            $file_ignore = explode(',', $folder_purge[0]);
            unset($folder_purge[0]);
            // la liste des dossiers
            // on supprime les dossiers vides à partir du dossier racine
            for ($i = count($folder_purge);$i >= 0; $i--) {
                // pas de suppression si le dossier a un sous-dossier
                $dirs = glob($folder_purge[$i].'/*', GLOB_ONLYDIR);
                if (empty($dirs)) {
                    $files = glob($folder_purge[$i].'/*.*');
                    $nbfiles = count($files);
                    foreach ($files as $file) {
                        if (in_array(basename($file), $file_ignore)) {
                            $nbfiles--;
                        }
                    }
                    // si pas d'autres fichier que ceux ignorés
                    if ($nbfiles == 0) {
                        // on supprime les fichiers et le dossier
                        foreach ($files as $file) {
                            unlink($file);
                        }
                        rmdir($folder_purge[$i]);
                    }
                }
            }
        }
        // remove unnecessary files
        unlink($folder_backup.'/upfilescleaner-folder-purge.txt');
        unlink($folder_backup.'/upfilescleaner-files.txt');
        $session->clear($up);
        $session->clear($up.'upfilescleaner');
        return 'OK';
    }


}
