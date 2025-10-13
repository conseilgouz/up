<?php

/**
 * sélectionne une ou plusieurs valeurs dans une liste ou un dossier
 *
 * syntaxe
 * {up random=1;2;3} -> une des 3 valeurs au hasard
 * {up random=1;2;3 | maxi=2 | sep-out=;} -> deux des 3 valeurs au hasard séparées par un point virgule
 * {up random=1,2,3 | sep-in=,} -> une des 3 valeurs séparées par une virgule au hasard
 * {up random=dossier/*.{jpg,ˆpng}} -> un des fichiers jpg ou png dans le dossier
 * {up random=dossier/fichier.csv} -> une des valeurs dans le fichier csv
 * {up random}val1{===}val2{===}val3{===}valN{/up random} -> une des valeurs au hasard
 *
 * @version  UP-3.1
 * @author   Lomart
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @auyhor    Lomart
 * @tags    Expert
 *
 */
defined('_JEXEC') or die();

class random extends upAction
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
            __class__ => '', // liste des valeurs, fichier txt ou csv avec les valeurs, chemin vers fichiers
            'maxi' => 1, // nombre de valeurs retournées
            'sep-in' => ';', // séparateur pour la liste des valeurs en entrée
            'sep-out' => ';', // séparateur pour la liste des valeurs en sortie
            'csv-numcol' => 0, // numéro de la colonne d'un fichier .csv
            'csv-title' => 1, // 1 si la 1ere ligne d'un fichier .csv contient les titres
            'mask' => '*.*', // masque pour sélection des fichiers d'un dossier. ex: *\[ab\].[jpg,png] -> [ab]*.{jpg,png}
            'msg-empty' => '', // message si aucun argument en entrée
            /* [st-style] Mise en forme du retour */
            'main-tag' => '0', // 0=liste texte ou balise du conteneur pour support id, class et style
            'item-tag' => 'div', // 0=liste texte ou balise du conteneur pour support id, class et style
            'id' => '', // identifier
            'main-style' => '', // classe et style inline pour bloc principal
            'item-style' => '', // classe et style inline pour les blocs item
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);
        $options['csv-numcol'] = (int) $options['csv-numcol'];
        $options['csv-title'] = (int) $options['csv-title'];

        // données en entrée
        $data = $options[__class__];
        if (! empty($this->content)) {
            // liste entre shortcode
            $datalist = $this->get_content_parts($this->content);
        } elseif (is_file($data)) {
            // fichier texte avec valeurs
            if (pathinfo($data, PATHINFO_EXTENSION) == 'csv') {
                $data = $this->get_html_contents($data);
                $data = $this->get_content_csv($data, false);
                if (! empty($data)) {
                    if ($options['csv-title']) {
                        unset($data[0]);
                        $data = array_values($data);
                    }
                    if (! empty($options['csv-numcol'])) {
                        $numcol = $options['csv-numcol'] - 1;
                        foreach ($data as $lign) {
                            $lign_csv = str_getcsv($lign, $options['sep-in'], '"', '\\');
                            $datalist[] = (isset($lign_csv[$numcol])) ? $lign_csv[$numcol] : $lign;
                        }
                    } else {
                        $datalist = $data;
                    }
                }
            } else {
                // fichier texte avec une donnée par ligne
                $data = $this->get_html_contents($data);
                $datalist = array_values(array_filter(explode(PHP_EOL, $data))); // ote lignes vides
            }
        } elseif (is_dir($data)) {
            // les fichiers d'un dossier
            $data = rtrim($data, '/\\') . '/' . $this->get_code($options['mask']);
            //$data = $this->get_url_absolute($data);
            $datalist = glob($data, GLOB_BRACE);
        } else {
            // range
            if (preg_match('/(\d*)\-(\d*)/', $data, $match) === 1) {
                $max = min($options['maxi'], ($match[2] - $match[1] + 1));
                for ($i = 0; $i < $max; $i++) {
                    $x = rand($match[1], $match[2]);
                    if (isset($datalist) && in_array($x, $datalist)) {
                        $i--;
                    } else {
                        $datalist[] = $x;
                    }
                }
                $out = $datalist; // le retour est déjà fait
            } else {
                // liste simple
                $data = $this->get_bbcode($data);
                $datalist = explode($options['sep-in'], $data);
            }
        }
        // --- sortie si vide
        if (empty($datalist)) {
            return $this->msg_inline($options['msg-empty']);
        }

        // === préparation retour
        if (! isset($out)) {
            $max = min($options['maxi'], count($datalist));
            $i = 0;
            while ($i < $max) {
                $num = rand(0, count($datalist) - 1);
                if (isset($datalist[$num])) {
                    $out[] = $datalist[$num];
                    unset($datalist[$num]);
                    $i++;
                }
            }
        }

        if (empty($options['main-tag'])) {
            // valeurs(s) brute(s)
            return implode($options['sep-out'], $out);
        } else {
            // attributs du bloc principal
            $this->load_css_head($options['css-head']);
            $attr_main = array();
            $attr_main['id'] = $options['id'];
            $this->get_attr_style($attr_main, $options['main-style']);
            $this->get_attr_style($attr_item, $options['item-style']);

            for ($i = 0; $i < count($out); $i++) {
                if (! empty($out[$i])) {
                    $out[$i] = $this->set_attr_tag($options['item-tag'], $attr_item, $out[$i]);
                }
            }
            // code en retour
            return $this->set_attr_tag($options['main-tag'], $attr_main, implode(PHP_EOL, $out));
        }
    }

    // run
}

// class
