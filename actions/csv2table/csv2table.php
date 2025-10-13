<?php

/**
 * Conversion d'un contenu au format CSV en table
 *
 * 1/ le contenu est lu dans un fichier
 * {up csv2table=emplacement-fichier}
 * 2/ le contenu est saisi entre les shortcodes
 * {up csv2table}
 *     article 1;5€
 *    "article 2";25€
 * {/up csv2table}
 *
 * @author   LOMART
 * @version  UP-1.6
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Layout-static
 */

/*
 * v1.8 - saut de ligne dans contenu CSV avec [br]
 * v2.3 - fix import csv (merci Eddy)
 * - possibilité de style de 6 à 12 colonnes
 * - suppression espaces ajoutés par TinyMCE
 * v2.8 - ajout option col-list et model noborder
 * v2.82- fix si justif non indiquée
 * v2.9 - Utilisation $primary et $secondary dans SCSS
 * v3.1 - bbcode pour option header
 */
defined('_JEXEC') or die();

class csv2table extends upAction
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        $this->load_file('csv2table.css');
        // $this->load_file('xxxxx.js');
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        // - vide = page sur le site de UP
        // - URL complete = page disponible sur ce lien
        // - rien pour ne pas proposer d'aide
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // URL ou chemin et nom d'un fichier local
            'separator' => ';', // séparateur des colonnes
            'HTML' => '0', // 0= aucun traitement, 1=affiche le code, ou liste des tags a garder (strip_tags)
            'model' => '', // nom de la classe modèle dans le fichier csv2table.css : noborder, line, blue, green
            /* [st-table] style de la table */
            'class' => '', // classe(s) pour la table
            'style' => '', // style inline pour la table
            /* [st-col] style des colonnes */
            'col-list' => '', // liste des colonnes utilisées. ex: 1,2,5 (v2.8)
            'col' => '', // alignement et largeur des colonnes sous la forme x-D-C5-100 pour rien-droite-centre 5%-100% (voir doc)
            'col-style-*' => '', // style inline pour tous les blocs colonnes. sinon voir style-1 à style-6
            /* [st-lign] style des lignes */
            'color-contrast' => '', // couleur des lignes impaires. la couleur des lignes paires est à définir dans class ou style
            /* [st-header] Entête des colonnes */
            'header' => '0', // 0: pas de titre, 1: premiere ligne contenu, titre des colonnes format CSV
            'header-class' => '', // classe(s) pour la balise thead
            'header-style' => '', // style pour la balise thead
            /* [st-footer] Pied des colonnes */
            'footer' => '0', // 0: pas de pied, 1: dernière ligne contenu, pied colonne
            'footer-class' => '', // classe(s) pour la balise tfoot
            'footer-style' => '', // style pour la balise tfoot
            /* [st-annexe] style et options secondaires */
            'id' => '', // identifiant
            'css-head' => '' // règles CSS définies par le webmaster (ajout dans le head)
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        $id = '#' . $options['id'];

        // === Recuperation du contenu CSV
        // 1 - le texte entre les shortcodes (sans html)
        $content = $this->content;
        // 2 - le contenu d'un fichier
        $filename = $options[__class__];
        if ($content == '' and $filename != '') {
            $content = $this->get_html_contents($filename);
        }

        // retour sans prévenir, le contenu peut être envoyé par une autre action
        if ($content == '') {
            return '';
            // $content = $this->info_debug('csv2table - content not found ' . $filename);
        }

        // === Analyse et nettoyage du contenu
        // ===================================
        // $content = $this->get_content_csv($content, 'br,code'); // v2.9
        $content = $this->get_content_csv($content, false); // v2.9.1 retour 2.8

        // === analyse et nombre de colonnes du tableau
        $nbcol = 0;
        foreach ($content as $key => $val) {
            if (! trim($val) == '') {
                $csv[$key] = str_getcsv($val, $options['separator'], '"', '\\');
                $csv[$key] = str_replace('[br]', '<br>', $csv[$key]);
                $nbcol = max($nbcol, count($csv[$key]));
            }
        }

        // === conserver uniquement les colonnes indiquées
        if ($options['col-list']) {
            $cols_ok = explode(',', $options['col-list']);
            $csv2 = array();
            for ($lign = 0; $lign < count($csv); $lign++) {
                foreach ($cols_ok as $col) {
                    $csv2[$lign][] = $csv[$lign][$col - 1];
                }
            }
            $nbcol = count($cols_ok);
            $csv = $csv2;
        }

        // === Recuperation des titres (head)
        // ==================================
        switch ($options['header']) {
            case '0':
            case '': // pas de titre
                break;
            case '1': // 1ere ligne est le titre
                $csvHead = array_shift($csv);
                break;
            default: // la valeur est le titre au format csv
                $csvHead = str_getcsv($this->get_bbcode($options['header']), $options['separator'], '"', '\\');
        }

        // === Recuperation des pieds de table (footer)
        // ============================================
        switch ($options['footer']) {
            case '0':
            case '': // pas de pied
                break;
            case '1': // derniere ligne est le pied
                $csvFoot = array_pop($csv);
                break;
            default: // la valeur est le titre au format csv
                $csvFoot = str_getcsv($options['footer'], $options['separator'], '"', '\\');
        }

        // MISE EN FORME (ajouté dans le HEAD)
        // =============
        // === Analyse col (largeur et alignement des colonnes sous la forme x-5-c-g5
        $col_style = array_fill(1, 12, '');
        if ($options['col']) {
            $search = array(
                'g',
                'l',
                'c',
                'd',
                'r'
            );
            $replace = array(
                'left',
                'left',
                'center',
                'right',
                'right'
            );
            $cols = explode('-', $options['col']);
            foreach ($cols as $key => $c) {
                if ($c) { // v2.8.3
                    switch (strtoupper($c[0])) {
                        case 'G':
                        case 'L':
                            $col_style[$key + 1] = 'text-align:left;';
                            $c = substr($c, 1);
                            break;
                        case 'C':
                            $col_style[$key + 1] = 'text-align:center;';
                            $c = substr($c, 1);
                            break;
                        case 'D':
                        case 'R':
                            $col_style[$key + 1] = 'text-align:right;';
                            $c = substr($c, 1);
                            break;
                        default:
                            $col_style[$key + 1] = '';
                    }
                    if (intval($c) > 0) {
                        $col_style[$key + 1] .= 'width:' . intval($c) . '%;';
                    }
                }
            }
        }

        // -- Creation des regles pour les colonnes (col et col-style-*)

        for ($i = 1; $i <= min(12, $nbcol); $i++) {
            if (! empty($options['col-style-' . $i]) || $col_style[$i]) {
                $css[] = $id . ' tr td:nth-child(' . $i . '){' . $col_style[$i] . $options['col-style-' . $i] . '}';
            }
            if (isset($options['col-' . $i])) {
                $align = str_ireplace($search, $replace, $options['col-' . $i][0]);
                $width = (int) substr($options['col-' . $i], 1);
                $css[] = $id . ' td:nth-child(' . $i . '){text-align:' . $align . ';width:' . $width . '%}';
            }
        }

        // ==== Couleur des lignes impaires
        if ($options['color-contrast']) {
            $css[] = $id . ' tbody tr:nth-of-type(odd) {background: ' . $options['color-contrast'] . '}';
        }

        // === css-head (en dernier pour priorité haute)
        $css[] = str_ireplace('#id', $id, $options['css-head']);

        // ---------- Ajout CSS dans le head avec substitution de #id par le vrai
        if (isset($css)) {
            $this->load_css_head(implode(PHP_EOL, $css));
        }

        // MISE EN FORME (ajouté dans les balise du code HTML)
        // ===================================================
        // -- balise TABLE
        $attr_table['id'] = $options['id'];
        $attr_table['class'] = 'csv2table';
        $this->add_class($attr_table['class'], $options['model']);
        $this->add_class($attr_table['class'], $options['class']);
        $attr_table['style'] = $options['style'];

        // -- balise THEAD TR
        $attr_thead['class'] = $options['header-class'];
        $attr_thead['style'] = $options['header-style'];

        // -- balise TFOOT TR
        $attr_tfoot['class'] = $options['footer-class'];
        $attr_tfoot['style'] = $options['footer-style'];

        // CREATION DU CODE HTML en retour
        // ===============================
        // -- TABLE
        $html[] = $this->set_attr_tag('table', $attr_table);
        // -- THEAD
        if (isset($csvHead)) {
            $html[] = '<thead>';
            $html[] = $this->set_attr_tag('tr', $attr_thead);

            $max = $nbcol;
            for ($i = 0; $i < $max; $i++) {
                $txt = (isset($csvHead[$i])) ? trim($csvHead[$i]) : '';
                // $txt = $this->get_bbcode($txt); // v2.9
                if (isset($txt[0]) && $txt[0] == '[') {
                    list($arg, $txt) = array_map('trim', explode(']', substr($txt, 1)));
                    $out = '';
                    if ($arg[0] > '1' && $arg[0] <= '6') {
                        $out .= ' colspan="' . $arg[0] . '"';
                        $max -= intval($arg[0]) - 1;
                        $arg = substr($arg, 1);
                    }
                    if ($arg) {
                        $out .= ' class="' . trim($arg) . '"';
                    }
                    $html[] = '<th' . $out . '>' . $txt . '</th>';
                } else {
                    $html[] = '<th>' . $txt . '</th>';
                }
            }
            $html[] = '</tr>';
            $html[] = '</thead>';
        }
        // -- TBODY
        $html[] = '<tbody>';
        if (isset($csv)) { // v2.9
            foreach ($csv as $lign) {
                $html[] = '<tr>';
                $max = $nbcol;
                for ($i = 0; $i < $max; $i++) {
                    $txt = (isset($lign[$i])) ? $this->supertrim($lign[$i]) : '';
                    if ($txt && $txt[0] == '[') {
                        list($arg, $txt) = array_map('trim', explode(']', substr($txt, 1)));
                        $out = '';
                        if (! empty($arg) && (int) $arg[0] > 0) {
                            $out .= ' colspan="' . $arg[0] . '"';
                            $i += (int) $arg[0] - 1;
                            $max -= intval($arg[0]) - 1;
                            $arg = trim(substr($arg, 1));
                        }
                        if (! empty($arg)) {
                            $out .= ' class="' . trim($arg) . '"';
                        }
                        $html[] = '<td' . $out . '>' . $txt . '</td>';
                    } else {
                        $html[] = '<td>' . $txt . '</td>';
                    }
                }
                $html[] = '</tr>';
            }
        }
        $html[] = '</tbody>';
        // -- TFOOT
        if (isset($csvFoot)) {
            $html[] = '<tfoot>';
            $html[] = $this->set_attr_tag('tr', $attr_tfoot);

            $max = $nbcol;
            for ($i = 0; $i < $max; $i++) {
                $txt = (isset($csvFoot[$i])) ? trim($csvFoot[$i]) : '';
                if (isset($txt[0]) && $txt[0] == '[') {
                    list($arg, $txt) = array_map('trim', explode(']', substr($txt, 1)));
                    $out = '';
                    if ($arg[0] > '1' && $arg[0] <= '6') {
                        $out .= ' colspan="' . $arg[0] . '"';
                        $max -= intval($arg[0]) - 1;
                        $arg = substr($arg, 1);
                    }
                    if ($arg) {
                        $out .= ' class="' . trim($arg) . '"';
                    }
                    $html[] = '<td' . $out . '>' . $txt . '</td>';
                } else {
                    $html[] = '<td>' . $txt . '</td>';
                }
            }
            $html[] = '</tr>';
            $html[] = '</tfoot>';
        }
        // -- /TABLE
        $html[] = '</table>';

        return implode(PHP_EOL, $html);
    }

    // run
}

// class
