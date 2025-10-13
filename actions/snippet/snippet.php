<?php

/**
 * créer et charger des petits fichiers texte (snippet)
 *
 * Syntaxe :
 * {up snippet=foo} // charge un fichier up/snippet/foo.html
 * {up snippet=foo}texte{/up snippet} // écrit le texte dans le fichier up/snippet/foo.snippet
 * {up snippet} // affiche la liste de tous les fichiers du dossier up/snippet/
 * {up snippet=* | delete} // supprime tous les snippets
 *
 * @version  UP-3.1
 * @author   Lomart
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="" target"_blank">script xxx de xxx</a>
 * @tags Editor
 *
 */
defined('_JEXEC') or die();

class snippet extends upAction
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
            __class__ => '*', // nom du fichier à charger/créer. vide ou mask pour list. ex: filter*
            'strip-tags' => 0, // supprime toutes les balises HTML
            'delete' => 0, // supprime le ou les fichiers passées comme argument principal
            'id' => '',
            'dir-base' => 'up/snippet' // dossier pour les snippets. Utilisez custom/prefs.ini
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        $dirbase = JPATH_ROOT . '/' .  $options['dir-base'];
        if (! file_exists($dirbase)) {
            mkdir($dirbase, 0755, true);
        }
        $out = ''; // pour retour

        // si nom seul : on retourne contenu du fichier dir-base/nom.html
        // si contenu : on sauve le contenu dans fichier dir-base/nom.html
        // si nom contient * ou ? : on affiche la liste des fichiers correspondant au masque dans le dossier dir-base

        $snippet_name = pathinfo($options[__CLASS__], PATHINFO_FILENAME);
        $snippet_name = $snippet_name ?? '*';
        $snippet_file = $dirbase . '/' . $snippet_name . '.html';

        // === demande de suppression
        if ($options['delete']) {
            if ($snippet_name == '*' && $this->options_user[__CLASS__] != '*') {
                return $this->msg_inline($this->trad_keyword('DELETE_ALL_SECURITY'));
            }
            $files = glob($snippet_file);
            if (empty($files)) {
                return $this->msg_inline($this->trad_keyword('NO_FILE_TO_DELETE', $snippet_file));
            } else {
                $msg = '<div class="bd-gris p1">';
                $msg .= $this->trad_keyword('DELETE_TITLE');
                $msg .= '<ul>';
                foreach ($files as $file) {
                    unlink($file);
                    $msg .= '<li>' . pathinfo($file, PATHINFO_FILENAME) . '</li>';
                }
                $msg .= '</ul>';
                $msg .= '</div>';
                return $this->msg_inline($msg);
            }
        }

        // === Liste des snippets
        if (strpos($snippet_name, '*') !== false || strpos($snippet_name, '?') !== false) {
            $list = glob($snippet_file);
            $msg = '<div class="bd-gris p1">';
            $msg .= $this->trad_keyword('LIST_TITLE', $snippet_name);
            foreach ($list as $file) {
                $msg .= '<div><b>' . pathinfo($file, PATHINFO_FILENAME) . '</b></div>';
                $msg .= '<code class="ml2 mb2">' . htmlentities(file_get_contents($file)) . '</code>';
            }
            $msg .= '</div>';
            return $this->msg_inline($msg);
        }

        // === creation snippet
        if ($this->content) {
            $out = $this->content;
            if ($options['strip-tags']) {
                $out = strip_tags($out);
            }
            // on annule la neutralisation de l'éditeur
            $out = str_replace('&lt;', '<', $out);
            $out = str_replace('&gt;', '>', $out);
            file_put_contents($snippet_file, $out);
            $msg = '<div class="bd-green p1">';
            $msg .= $this->trad_keyword('SAVE_OK', $snippet_file);
            $msg .= '<br><code>' . htmlentities($out).'</code>';
            $msg .= '</div>';
            return $this->msg_inline($msg);
        }

        // === lecture snippet
        if (file_exists($snippet_file)) {
            $out = $this->get_bbcode(file_get_contents($snippet_file));
        } else {
            $out = $this->msg_inline($this->trad_keyword('NOT_FOUND', $snippet_file));
        }
        return $out;
    }

    // run
}

// class
