<?php

/**
 * Retourne la valeur brute d'un élément (ligne/colonne) d'un fichier CSV
 *
 * syntaxe {up csv-info=chemin-fichier | col=x | line=x}
 *
 * @version  UP-2.6
 * @license  <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @author   Denis HANTZ
 * @tags     HTML
 *
 */
defined('_JEXEC') or die();

class csv_info extends upAction
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    public function run()
    {
        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // URL ou chemin et nom d'un fichier local
            'separator' => ';', // séparateur des colonnes
            'line' => '-1', // Titre dans la 1ere colonne ou numéro de la ligne où se trouve l'information. un nombre négatif recherche à partir de la fin
            'col' => '1', // Titre de la colonne dans la première ligne ou numero de colonne de l'information. Négatif à partir de la fin
            'default' => '[b class="t-red"]###[/b]' // valeur retournée si coordonnées cellule hors feuille
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // retour si cellule non trouvée
        $out = $this->get_bbcode($options['default']);

        // === Recuperation du contenu fichier CSV
        $filename = $options[__class__];
        if (! file_exists($filename)) {
            $this->msg_error($this->trad_keyword('error-file-not-found') . $filename);
            return $out;
        }

        $lines = file($filename);
        if (empty($lines)) {
            $this->msg_error($this->trad_keyword('error-file-empty') . $filename);
            return $out;
        }

        // ---- LIGNE
        if (is_numeric($options['line'])) {
            // recherche par indice
            $numline = intval($options['line']);
            if (abs($numline) <= count($lines)) {
                if ($numline <= - 1) {
                    $numline = count($lines) + $numline + 1;
                } elseif ($numline == 0) {
                    $numline = count($lines);
                }
                $fields = str_getcsv($lines[$numline - 1], $options['separator'], '"', '\\');
            }
        } else {
            // recherche par clé
            $key = trim(strtolower($options['line']));
            for ($numline = 1; $numline < count($lines); $numline++) {
                $tempfields = str_getcsv($lines[$numline], $options['separator'], '"', '\\');
                if (trim(strtolower($tempfields[0])) == $key) {
                    $fields = $tempfields;
                    break;
                }
            }
        }

        // ligne inexistante
        if (empty($fields)) {
            return $out;
        }

        // ---- COLONNE
        if (is_numeric($options['col'])) {
            // recherche par indice
            $numcol = intval($options['col']);
            if (abs($numcol) <= count($fields)) {
                if ($numcol <= - 1) {
                    $numcol = count($fields) + $numcol + 1;
                } elseif ($numcol == 0) {
                    $numcol = count($fields);
                }
                $out = $fields[$numcol - 1];
            }
        } else {
            // titre de colonne
            $key = trim(strtolower($options['col']));
            $coltitle = str_getcsv($lines[0], $options['separator'], '"', '\\');
            for ($numcol = 0; $numcol < count($coltitle); $numcol++) {
                if (trim(strtolower($coltitle[$numcol])) == $key) {
                    $out = $fields[$numcol];
                    break;
                }
            }
        }

        // -- c'est fini
        return $out;
    } // run
} // class
