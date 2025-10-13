<?php

/**
 * Affiche le contenu d'un fichier JSON, XML ou CSV sous forme d'un tableau
 *
 * syntaxe {up data2table=data_source}
 *
 * @version  UP-3.0  
 * @author   lomart
 * @license  <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Expert
 *
 * */
defined('_JEXEC') or die();

class data2table extends upAction
{

    function init()
    {
        $this->load_file('data2table.css');
        return true;
    }

    function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
           /* [st-data] emplacement et type des données */
            __class__ => '', // URL vers data ou fichier
            'datatype' => '', // pour forcer la détection du type de données (json, xml)
            'encoding' => '', // codage des caractères de la source : ISO-8859-1
            /* [st-csv] uniquement pour les données CSV ou XML */
            'csv-header' => 1, // 0 ou 1 pour indiquer que la première ligne contient les titres de colonnes
            'csv-header-title' => '', // liste des titres de colonnes séparés par csv-separator
            'csv-separator' => ';', // caractère séparateur pour les colonnes
            'xml-attributes' => 0, // les champs dans la balise ouvrante sont dans un sous-tableau @attributes, Non par défaut: 0 /* [st-sel-lign] sélection des lignes */
            /* [st-sel-lign] sélection racine données et lignes */
            'lign-root' => '', // chemin de la clé racine. Exemple: trk/trkseg
            'lign-select' => '', // indice du groupe de données ou champ:valeur pour recherche contenu
            'lign-filter' => '', // filtrage après lign-select sous la forme champ[=>,<=,==,<>]valeur OU field><valeurMin-valeurMax
            'lign-sort' => '', // tri des données sous la forme champ1:asc|desc, champ2:asc|desc, ...
            'lign-max' => '', // nombre de lignes retournées
            /* [st-sel-col] sélection des colonnes/champs */
            'col-include' => '', // liste des champs (séparateur virgule) retournés
            'col-exclude' => '', // liste des champs (séparateur virgule) non retournés
            /* [st-format] Mise en forme des colonnes */
            'col-list' => '', //
            'col-class' => '', // classes pour les champs (champ1:class1 class2,champ2:class)
            'col-label' => '', // correspondance entre nom du champ et titre colonne (champ1:col1,champ2:col2, ...)
            /* [st-type] type des contenus */
            'col-type' => '', // date, url, image, boolean, compact ou format pour fonction php: sprintf
            'date-format' => '%e %B %Y', // format pour les dates
            'boolean-in' => '1,0,', // valeurs dans fichier pour true,false,null
            'boolean-out' => 'lang[fr=oui,non,-;en=yes,no,n.a.]', // texte en sortie pour les valeurs true,false,null
            'url-target' => '_blank', // Cible pour ouverture URL
            'image-path' => '', // chemin vers une image dans les données
            'image-max-size' => '', // coté du carré dans lequel elle sera inscrite. Exemple: 100px
            /* [st-empty] si un champ est vide */
            'col-empty' => '', // contenu d'un champ si vide ou égal à zéro. ex: colname:none, ...
            'no-data-html'=>'en=no data for %s;fr=aucune donnée pour %s', // contenu si aucune donnée disponible. BBcode admis.
            /* [st-style] habillage du bloc retourné */
            'model' => '', // nom de la classe modèle dans le fichier data2table.css : noborder, line, blue, green
            'id' => '',
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '', // style ajouté dans le HEAD de la page
            /* [st-cache] internal action cache */
            'cache-delay' => 30 // durée du cache en minutes. 0 pas de cache
        );

        include_once ($this->upPath . '/assets/lib/data.php');

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // === CSS-HEAD
        $this->load_css_head($options['css-head']);

        $data = get_data($options[__class__], $options['cache-delay']);
        if ($data == '') {
            return $this->msg_inline('data-info - data source not found or empty' . $options[__class__]);
        }

        // Conversion des données en array
        $data = convert_data_to_array($data, $options, true);
        if ($data == '') {
            return $this->msg_inline('data2table - format data source invalid : ' . $options[__class__]);
        }

        // consolidation des options de formattage
        $options['boolean-out'] = $this->get_bbcode($options['boolean-out']);
        $options['col-type'] = $this->get_bbcode($options['col-type']);
        $options['col-empty'] = $this->get_bbcode($options['col-empty']);
        fix_options($options);
        if ($options['datatype'] = 'json')
            $options['xml-attributes'] = 1;

        // selection de la racine options['root']
        if ($options['lign-root'] != '')
            if (get_root($data, $options) === false)
                return $this->msg_error($this->trad_keyword('ITEM_NOT_FOUND', $options['lign-root']));

        // selection options['select']
        if ($options['lign-select'] != '') {
            $msg = get_select($data, $options);
            if ($msg)
                $this->msg_inline($msg . ' for ' . $options[__class__]);
        }

        // --- filtrage des données v5.1
        // nomcol:condition(<=,>=,==,<>,><)valeur
        $msg = get_filter($data, $options['lign-filter']);
        if ($msg)
            return $this->msg_inline($msg . ' ' . $options['lign-filter'] . ' for ' . $options[__class__]);

        // --- tri des données v5.1
        $msg = sort_data($data, $this->strtoarray($options['lign-sort'], ',', ':', false));
        if ($msg)
            $this->msg_inline($msg . ' for ' . $options[__class__]);

        // --- lign-max v5.1
        if ((int) $options['lign-max'] > 0) {
            $data = array_slice($data, 0, (int) $options['lign-max']);
        }
        
        // HTML si pas de donnée v5.1
        if (empty($data))
            return sprintf($this->get_bbcode($options['no-data-html']), $options[__class__]);
            
        // === les sous-titres des colonnes (THEAD)
        $title = $this->get_title($data, $options);
        if ($options['col-list']) { // v31
            $title2 = array_map('trim', explode(',', $options['col-list']));
            foreach ($title2 as $col2) {
                if (array_key_exists($col2, $title)) {
                    $title3[$col2] = $title[$col2];
                } else {
                    $col_novalid[] = $col2;
                }
            }
            if (empty($col_novalid)) {
                $title = $title3;
            } else {
                $this->msg_error($this->trad_keyword('ERROR_COL_LIST', implode(',', $col_novalid)));
            }
        }
        $out = $this->make_table($data, $title, $options);

        // attributs du bloc principal
        $attr_main = array();
        $attr_main['id'] = $options['id'];
        $attr_main['class'] = 'data2table ' . $options['model'];
        $this->get_attr_style($attr_main, $options['class'], $options['style']);

        // code en retour
        $out = $this->set_attr_tag('table', $attr_main, implode(PHP_EOL, $out));

        return $out;
    }

    /* run */

    /*
     * ---------------------------------------------------------------------
     * make_table
     * retourne le code HTML pour la table (thead & tbody)
     * ---------------------------------------------------------------------
     */
    function make_table($data, $title, $options)
    {
        // == thead
        // profondeur sous-titres
        $rowspan = '';
        foreach ($title as $k => $v) {
            if (is_array($v))
                $rowspan = ' rowspan="2"';
        }
        //
        $title1 = array();
        $title2 = array();
        $cols = array();
        foreach ($title as $k => $v) {
            $label = (isset($options['col-label'][$k])) ? $options['col-label'][$k] : $k;
            if (is_array($v)) {
                $nbcol = count($v);
                $title1[] = '<th colspan="' . $nbcol . '">' . $label . '</th>';
                foreach ($v as $k2 => $v2) {
                    $label = (isset($options['col-label'][$k2])) ? $options['col-label'][$k2] : $k2;
                    $title2[] = '<th>' . $label . '</th>';
                    $cols[] = $k . '/' . $k2;
                }
            } else {
                $title1[] = '<th' . $rowspan . '>' . $label . '</th>';
                $cols[] = $k;
            }
        }
        $html[] = '<thead>';
        $html[] = '<tr>' . implode(PHP_EOL, $title1) . '</tr>';
        if ($title2)
            $html[] = '<tr>' . implode(PHP_EOL, $title2) . '</tr>';
        $html[] = '</thead>';

        // == tbody
        $html[] = '<tbody>';
        foreach ($data as $kdata => $vdata) {
            $lign = '';
            foreach ($cols as $col) {
                $ret = get_col_value($col, $vdata, $options);
                if ($ret[0]) {
                    $val = $ret[0];
                } else {
                    $val = (isset($options['col-empty'][$col])) ? $options['col-empty'][$col] : '';
                }
                $class = ($ret[1]);
                if (is_array($val)) {
                    $str = '';
                    array_to_string($str, $val);
                    $val = $str;
                }

                $lign .= '<td' . $class . '>' . $val . '</td>';
            }
            $html[] = '<tr>' . $lign . '</tr>';
        }
        $html[] = '</tbody>';
        // == fini
        return $html;
    }

    /*
     * ---------------------------------------------------------------------
     * function get_title
     * retourne un tableau dont les clés avec les titres de colonne
     * $title['col1'] <- titre colonne 1er niveau
     * $title['col1'][subcol1] <- sous-titre de la sous-colonne
     * ---------------------------------------------------------------------
     * Il est imperatif que le 1er niveau de data soit les lignes de la future table
     */
    function get_title($data, $options)
    {
        foreach ($data as $krow => $vrow) { // les lignes

            foreach ($vrow as $kcol => $vcol) {
                // supprimer les champs exclus
                if (in_array($kcol, $options['col-exclude'])) {
                    unset($data[$kcol]);
                    continue;
                }
                // conserver uniquement les champs inclus
                if (! empty($options['col-include']) && ! in_array($kcol, $options['col-include'])) {
                    unset($data[$kcol]);
                    continue;
                }
                // les sous-titres de colonnes

                if (is_array($vcol)) {
                    if ((isset($options['col-type'][$kcol]) && $options['col-type'][$kcol] == 'compact') || empty($options['xml-attributes'])) {
                        // 1 - titre attributes + contenu compact
                        if (! isset($title[$kcol]))
                            $title[$kcol] = '';
                    } else {
                        // 3 - titre attributes + sous-colonnes
                        foreach ($vcol as $ksub => $vsub) {
                            if (! is_array($vsub)) {
                                if (! isset($title[$kcol][$ksub])) {
                                    $title[$kcol][$ksub] = '';
                                }
                            }
                        }
                    }
                } else {
                    if (! isset($title[$kcol]))
                        $title[$kcol] = '';
                }
            }
        }
        return $title ?? '';
    }

    // --- fin class
}
