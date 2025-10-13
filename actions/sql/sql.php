<?php

/**
 * Requête SQL avec mise en forme et tri
 *
 * syntaxe {up sql=nom_table | ...}
 *
 * Terminologie:
 * row : ligne de la table
 * col : cellule. Colonne de la table
 * tag ou motclé : ##nomcol##
 *
 * @author   LOMART
 * @version  UP-1.8
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="https://github.com/joequery/Stupid-Table-Plugin#stupid-jquery-table-sort" target"_blank">script Stupid Table de joequery</a>
 * @tags  Joomla
 */
/*
 * V2.5 - retour valeur brute pour count, min, max, sum, avg
 * v3.1.1 - fix forcer strtolower pour les clés
 * v5.1 : ajout option variable-*
 * v5.2 : ajout option overflow
 * v5.3 : fix xxx-format=img[60] in set_type function
 */
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

class sql extends upAction
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        $this->load_file('sql.css');
        $this->load_file('stupidtable.min.js');
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // nom de la table
            'select' => '*', // listes des colonnes
            'where' => '', // commande SQL : where
            'order' => '', // commande SQL : order
            'group' => '', // commande SQL : group
            'innerjoin' => '', // commande SQL : innerjoin
            'outerjoin' => '', // commande SQL : outerjoin
            'leftjoin' => '', // commande SQL : leftjoin
            'rightjoin' => '', // commande SQL : rightjoin
            'setlimit' => '', // commande SQL : setlimit
            'variable-*' => '', // remplace ##variable-X## dans les options ci-dessus
            /* [st-info] informations sur la base de données */
            'dbinfos' => '', // vide= la liste des tables OU nom_table = la liste des colonnes
            'no-prefix-auto' => 0, // utiliser le nom de la table sans ajouter le prefix #__
            /* [st-form] mise en forme du résultat */
            'presentation' => 'table', // présentation du résultat : list,table,div ou 0
            'template' => '', // modèle mise en page
            'header' => '', // pour presentation table : 1 ou vide pour utiliser les mots-clés, sinon titres séparés par des points-virgules
            'overflow' => 1, // pour presentation table : O n'encapsuler pas la table dans un bloc div pour ajout d'un overflow. Utile si utilisation de table-fixe
            'sort' => '', // type de tri par colonne sous la forme: i,3-f-s. i:int, s:string, f:float. ,3 indique un tri secondaire sur la 3e colonne
            'sort-first' => '', // nom ou position de la colonne triée en premier
            'col' => '', // alignement et largeur des colonnes sous la forme x-D-C5-100 pour rien-droite-centre 5%-100% (voir doc)
            'no-content-html' => 'aucun résultat', // essage si echec requete
            /* [st-css] Style CSS pour afficher le résultat */
            'id' => '', // identifiant
            'main-class' => 'up', // classe(s) pour bloc
            'main-style' => '', // style inline pour bloc
            'item-class' => '', // classe(s) pour ligne
            'item-style' => '', // style inline pour ligne
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // ========================================
        // ==== CONTROLE et CONSOLIDATION ARGUMENTS
        // ========================================
        // fusion et controle des options
        // 3e arg : '#\-(?:format|model)$#' autorise les options dont le nom se termine par -format ou -model
        $optmask = '#\-(?:format|model|rowclass|colclass)$#';
        $options = $this->ctrl_options($options_def, [], $optmask);

        if ($options[__class__] && empty($options['no-prefix-auto'])) {
            $options[__class__] = '#__' . ltrim($options[__class__], '#_');
        }
        if ($options['template']) { // v3
            // gestion BBCode
            $options['template'] = $this->get_bbcode($options['template'], false);
            // on reactive les tags HTML
            $options['template'] = html_entity_decode($options['template']);
        }
        // tableau des balises de présentation
        $options['presentation'] = $this->ctrl_argument($options['presentation'], 'list,table,div,0,,1');

        // type des colonnes pour ajouter attribut 'data-sort-value'
        $sortcol_type = array(
            'list',
            'date'
        );

        // =============================================
        // ==== DEMANDE D'INFOS explicite dans shortcode
        // =============================================
        if (isset($this->options_user['dbinfos'])) {
            $out = '';
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true);
            if ($options[__class__] == '') {
                $db->setQuery('SHOW TABLES');
                $rows = $db->loadRowList();
                foreach ($rows as $row) {
                    $out .= ($out) ? ' &#x25cf; ' . $row[0] : $row[0];
                }
                $this->msg_info($out, $this->trad_keyword('TITLE_TABLE'));
                return ''; // juste infos sur tables
            } else {
                $db->setQuery('DESCRIBE ' . $db->quoteName($options[__class__]));
                $rows = $db->loadAssocList();
                foreach ($rows as $col) {
                    $field = ($col['Key'] != '') ? '<u>' . $col['Field'] . '</u>' : $col['Field'];
                    $field = ($col['Key'] == 'PRI') ? '<b>' . $field . '</b>' : $field;
                    $out .= $field . ' <small>' . $col['Type'] . '</small>  ';
                }
                $this->msg_info($out, $this->trad_keyword('TITLE_COLUMNS', $options[__class__]));
                unset($rows);
            }
        }

        // ==============
        // ==== VARIABLES
        // ==============
        $cdelist = explode(',', 'sql,select,where,innerjoin,outerjoin,leftjoin,rightjoin,order,setlimit,group');
        $regex = '/##(variable-\d+)##/i';
        $matches = array();
        foreach ($cdelist as $cde) {
            preg_match_all($regex, $options[$cde], $matches);
            if (! empty($matches[1])) {
                foreach ($matches[1] as $match) {
                    $options[$cde] = str_replace('##' . $match . '##', $options[strtolower($match)], $options[$cde]);
                }
            }
        }

        // ================
        // ==== REQUETE SQL
        // ================

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select($options['select']);
        $query->from($options[__class__]);
        if ($options['innerjoin']) {
            $query->innerjoin($options['innerjoin']);
        }
        if ($options['outerjoin']) {
            $query->outerjoin($options['outerjoin']);
        }
        if ($options['leftjoin']) {
            $query->leftjoin($options['leftjoin']);
        }
        if ($options['rightjoin']) {
            $query->rightjoin($options['rightjoin']);
        }
        if ($options['where']) {
            $query->where(html_entity_decode($options['where']));
        }
        if ($options['order']) {
            $query->order($options['order']);
        }
        if ($options['setlimit']) {
            $query->setLimit($options['setlimit']);
        }
        if ($options['group']) {
            $query->group($options['group']);
        }

        $db->setQuery($query);
        if (isset($this->options_user['debug'])) {
            $debug = $query->__toString();
            $this->msg_info(htmlentities($debug), 'Requete SQL');
        }
        try {
            $row_tmp = $db->loadAssocList();
        } catch (RuntimeException $e) {
            $this->msg_error(reset(explode('Stack', $e)));
        }
        // si pas de résultat
        if (empty($row_tmp)) {
            return $options['no-content-html'];
        }

        // si count, max, min, sum, avg : on retourne le resultat brut
        if (count($row_tmp) == 1 && count($row_tmp[0]) == 1) {
            return reset($row_tmp[0]);
        }

        // on force les noms de colonne en min
        $cols_name = array_keys($row_tmp[0]);
        //         if (count($row_tmp) == 1) {
        //             $cols_name = array_keys($row_tmp[0]);
        //         } else {
        //             $cols_name = array_keys($row_tmp[0][0]);
        //         }
        for ($i = 0; $i < count($row_tmp); $i++) {
            $rows[] = array_change_key_case($row_tmp[$i]);
        }
        unset($row_tmp);

        // ================================
        // ==== ANALYSE OPTIONS FORMATAGE
        // ================================
        // si template=vide : on affiche toutes les colonnes et limite les champs texte à 100 car
        if ($options['template'] == '') {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true);
            $db->setQuery('DESCRIBE ' . $db->quoteName($options[__class__]));
            $tmp = $db->loadAssocList();
            foreach ($tmp as $col) {
                if (in_array($col['Field'], $cols_name)) {
                    $options['template'] .= '##' . $col['Field'] . '## ';
                    if (strpos($col['Type'], 'text') !== false) {
                        $options[$col['Field'] . '-format'] = 'text[100]';
                    }
                }
            }
            $options['header'] = '1'; // on force entete table
        }
        // la liste des tags dans l'ordre
        $tags_order = array(); // [0] ##keyword## [1] keyName origine
        $tags = array(); // [key][tag|format|rowclass|colclass|model] info sur chaque cle
        if (preg_match_all('/##(.*)##/U', $options['template'], $tags_order) === false) {
            return $this->info_debug('option "template" is empty');
        }
        foreach ($tags_order[1] as $tag) {
            list($tagBase) = explode('.', $tag); // si json
            if (array_key_exists(strtolower($tagBase), $rows[0])) { // v311
                $tags[$tag]['tag'] = '##' . $tag . '##';
                // $tags[$tag]['format']['type'] = '';
                // --- ANALYSE FORMAT
                if (isset($options[$tag . '-format'])) {
                    $tags[$tag]['format'] = $this->get_type($options[$tag . '-format']);
                }
                if (isset($options[$tag . '-rowclass'])) {
                    $tags[$tag]['rowclass'] = $this->get_type($options[$tag . '-rowclass']);
                }
                if (isset($options[$tag . '-colclass'])) {
                    $tags[$tag]['colclass'] = $this->get_type($options[$tag . '-colclass']);
                }
                // --- ANALYSE MODEL
                if (isset($options[$tag . '-model'])) {
                    // $tags[$tag]['model'] = html_entity_decode($options[$tag . '-model']);
                    $tags[$tag]['model'] = $this->get_bbcode($options[$tag . '-model'], false);
                }
            } else {
                $this->msg_error($this->trad_keyword('UNKNOWN_COLUMN', $tag));
            }
        }
        // === Récupération ordre de tri initial pour SORT
        $primary_sort = '';
        if ($options['sort'] && $options['order']) {
            list($tmp) = explode(',', $options['order'] . ',');
            list($col, $sens) = explode(' ', $tmp . ' ASC');
            $col = array_search($col, $tags_order[1]);
            $primary_sort = $col . ',' . $sens;
        }

        // === Contrôle erreur saisie nom colonne pour option format ou model
        $optmask = '#(.*)-(?:format|model|rowclass|colclass)$#';
        foreach ($options as $key => $val) {
            if (preg_match($optmask, $key, $tmp)) {
                if (! isset($tags[$tmp[1]])) {
                    $this->msg_error($this->trad_keyword('UNKNOWN_TAG', $key));
                }
            }
        }

        // ================================
        // ==== JS
        // ================================
        if ($options['sort']) {
            $js = 'var table = $("#' . $options['id'] . '").stupidtable();';
            $js .= <<<JS
            table.on("aftertablesort", function (event, data) {
            var th = $(this).find("th");
            th.find(".arrow").remove();
            var dir = $.fn.stupidtable.dir;
            var arrow = data.direction === dir.ASC ? "&#x25B4;" : "&#x25BE;";
            th.eq(data.column).append('<span class="arrow">' + arrow +'</span>');
            });
            JS;
            $this->load_jquery_code($js);
        }

        // ================================
        // ==== MISE EN FORME DONNEES
        // ================================
        $html = array(); // pour retour
        // === CSS-HEAD
        $this->load_css_head($options['css-head']);

        // === ENTETE
        $attr_main = array();
        $attr_main['id'] = $options['id'];
        $this->get_attr_style($attr_main, $options['main-class'], $options['main-style']);
        $is_table = false;
        switch ($options['presentation']) {
            case 'table':
                $is_table = true;
                // régles CSS dans head
                $this->table_col_css($options['col']);
                if ($options['overflow']) {
                    $html[] = '<div style="max-width:100%;overflow:auto;">';
                }
                $html[] = $this->set_attr_tag('table', $attr_main);
                // entete avec attributs pour tri
                if ($options['sort'] || $options['header']) {
                    $sort = $this->table_col_sort($options['sort'], $primary_sort, count($tags));
                    $html[] = '<thead><tr>';
                    $titles = (strpos($options['header'], ',') === false) ? array_keys($tags) : explode(',', $options['header']);
                    for ($i = 0; $i < count($tags); $i++) {
                        $html[] = '<th' . $sort[$i] . '>' . $titles[$i] . '</th>';
                    }
                    $html[] = '</tr></thead>';
                }
                //
                if ($options['overflow']) {
                    $close_main = '</tbody></table></div>';
                } else {
                    $close_main = '</tbody></table>';
                }
                $row_tag = 'tr';
                $col_tag = 'td';
                break;
            case 'div':
                $html[] = $this->set_attr_tag('div', $attr_main);
                $close_main = '</div>';
                $row_tag = 'div';
                $col_tag = 'span';
                break;
            case 'list':
                $html[] = $this->set_attr_tag('ul', $attr_main);
                $close_main = '</ul>';
                $row_tag = 'li';
                $col_tag = 'span';
                break;
            default: // 0,1,vide
                $close_main = '';
                $row_tag = '';
                $col_tag = 'span';
                break;
        }
        // === LIGNES

        foreach ($rows as $row) {
            $out = ($is_table) ? '' : $options['template'];
            $attr_row = array();
            foreach ($tags as $key => $tag) {
                $attr_col = array();

                // == RECUP JSON
                if (strpos($key, '.') !== false) {
                    list($key1, $key2) = explode('.', $key);
                    $tmp = (array) json_decode($row[$key1]);
                    $val = (isset($tmp[$key2])) ? $tmp[$key2] : '';
                } else {
                    if (array_key_exists(strtolower($key), $row)) { // v311
                        $val = $row[strtolower($key)];
                    } else {
                        $val = '---';
                    }
                }

                // == STYLE LIGNE
                if (isset($tag['rowclass'])) {
                    $attr_row['class'] = $this->set_type($tag['rowclass'], $val);
                }

                // == STYLE COLONNE (avant modif valeur par format)
                if (isset($tag['colclass'])) {
                    $attr_col['class'] = $this->set_type($tag['colclass'], $val);
                }

                // == ajout attributs pour tri sur valeur non formatée
                if (isset($tag['format'])) {
                    if (isset($options['sort']) && in_array(key($tag['format']), $sortcol_type)) {
                        $attr_col['data-sort-value'] = $val;
                    }
                }

                // == FORMAT
                if (isset($tag['format'])) {
                    $val = $this->set_type($tag['format'], $val);
                }

                // == MODELE
                if (isset($tag['model'])) {
                    list($model, $modelvide) = str_getcsv($tag['model'] . ';', ';', '"', '\\');
                    $val = (! empty($val)) ? sprintf($model, $val) : sprintf($modelvide, $val);
                }

                // ===== SET COLONNE
                if ($is_table) {
                    $out .= $this->set_attr_tag($col_tag, $attr_col, $val);
                } else {
                    if (! empty($attr_col['class'])) {
                        $val = $this->set_attr_tag($col_tag, $attr_col, $val);
                    }
                    $out = str_ireplace($tag['tag'], $val, $out);
                }
            } // fin col
            // ===== SET LIGNE
            if ($is_table) {
                $html[] = $this->set_attr_tag('tr', $attr_row, $out);
            } elseif ($row_tag != '') {
                $html[] = $this->set_attr_tag($row_tag, $attr_row, $out);
            } else {
                $html[] = $out;
            }
        }

        // === FOOTER
        $html[] = $close_main;

        // code en retour
        $ret = implode(PHP_EOL, $html);
        return $ret;
        // run
    }

    /*
     * Retourne un tableau avec les types définis pour une option de formatage
     * $arg = argument de x-format, x-rowclass, x-colclass
     * return
     * type (ex:list)
     * -- key1 = val1
     * -- key2 = val2
     * type (ex:min)
     * -- key = val
     */
    public function get_type($deftype)
    {
        // si multicriteres
        $deftypes = explode(']', rtrim($deftype, ']'));

        foreach ($deftypes as $deftype) {

            list($type, $arg) = explode('[', $deftype . '[');
            $type = strtolower(trim($type));

            switch ($type) {
                case 'list':
                case 'min':
                case 'max':
                case 'regex':
                    // list[1:un, 2:deux]
                    $out[$type] = $this->strtoarray($arg, ',', ':', false);
                    break;
                    // case 'regex' :
                    // // regex[regex:class]
                    // list($regex, $val) = array_map('trim', explode(':', $arg));
                    // $out[$type][$regex] = $val;
                    // break;
                case 'replace':
                    // replace[old:new] - remplace old par new. multiple= old1,old2: new1,new2
                    list($old, $new) = explode(':', $arg . ':');
                    $out[$type]['old'] = array_map('trim', explode(',', $old));
                    $out[$type]['new'] = array_map('trim', explode(',', $new));
                    break;
                case 'date': // date[%e %B %Y]
                case 'text': // text[100] - les 100 premiers car sans tags HTML
                case 'img': // img[size] - image inscrit dans taille carré
                    $out[$type] = array(
                        $arg => ''
                    );
                    break;
                default:
                    $this->msg_error('Type inconnu pour ' . $type);
                    $this->msg_error($this->trad_keyword('UNKNOWN_TYPE', $type));
                    break;
            }
        }
        return $out;
    }

    /*
     * Retourne une chaine avec les classes
     */
    public function set_type($arrtype, $val)
    {
        $out = '';
        $val = (is_null($val)) ? '' : $val;
        foreach ($arrtype as $type => $arg) {
            switch ($type) {
                case 'list':
                    if (isset($arg[$val])) {
                        $out .= $arg[$val] . ' ';
                    }
                    break;
                case 'min':
                    if ($val >= key($arg)) {
                        $out .= current($arg) . ' ';
                    }
                    break;
                case 'max':
                    if ($val <= key($arg)) {
                        $out .= current($arg) . ' ';
                    }
                    break;
                case 'regex':
                    foreach ($arg as $k => $v) {
                        if (preg_match($k, $val) == 1) {
                            $out .= $v;
                        }
                    }
                    break;
                case 'text':
                    $out = strip_tags($val);
                    $len = (int) key($arg);
                    if ($len > 0 && $len < strlen($val)) {
                        $out = substr($out, 0, $len) . '&#x2026';
                    }
                    break;
                case 'date':
                    $out = (empty($val)) ? '' : $this->up_date_format($val, key($arg)); // v2.9
                    break;
                case 'replace':
                    $out = str_ireplace($arg['old'], $arg['new'], $val);
                    break;
                case 'img':
                    $size = '';
                    if ((int) key($arg) > 10 && file_exists($val)) {
                        list($w, $h) = getimagesize($val);
                        $size = ($w > $h) ? ' width' : ' height';
                        $size .= '="' . key($arg) . 'px"';
                    }
                    $out = '<img src="' . $val . '" alt="' . $this->link_humanize($val) . '"' . $size . '>';
                    break;
            }
        }
        return $out;
    }

    /*
     * Retourne attributs entete table pour tri
     * Analyse option sort (sous la forme x-5-c-g5
     */
    public function table_col_sort($sortdef, $primary_sort, $nbcol)
    {
        $sorttype = array(
            'i' => 'int',
            'f' => 'float',
            's' => 'string'
        );
        $cols = explode('-', strtolower($sortdef));
        for ($i = 0; $i < $nbcol; $i++) {
            $out[$i] = '';
            if (isset($cols[$i])) {
                $tmp = explode(',', $cols[$i]);
                if (isset($sorttype[$tmp[0]])) {
                    // type de tri de la colonne
                    $out[$i] = ' data-sort="' . $sorttype[$tmp[0]] . '"';
                    // multi-colonnes
                    array_shift($tmp);
                    if (count($tmp) > 0) {
                        for ($i = 0; $i < count($tmp); $i++) {
                            $tmp[$i] = (int) $tmp[$i] - 1;
                        }
                        if (count($tmp) == 1) {
                            $tmp[] = $i;
                        } // astuce pour palier a bug du JS qui exige 2 colonnes
                        $out[$i] .= ' data-sort-multicolumn="' . implode(',', $tmp) . '"';
                    }
                }
            }
        }
        return $out;
    }

    /*
     * MISE EN FORME DES COLONNES LARGEUR & ALIGN (ajouté dans le HEAD)
     * Analyse option col (largeur et alignement des colonnes sous la forme x-5-c-g5
     */
    public function table_col_css($coldef)
    {
        $css = '';
        $cols = explode('-', $coldef);
        foreach ($cols as $key => $c) {
            if ($c > '') {
                switch (strtoupper($c[0])) {
                    case 'G':
                    case 'L':
                        $col_style = 'text-align:left;';
                        $c = substr($c, 1);
                        break;
                    case 'C':
                        $col_style = 'text-align:center;';
                        $c = substr($c, 1);
                        break;
                    case 'D':
                    case 'R':
                        $col_style = 'text-align:right;';
                        $c = substr($c, 1);
                        break;
                    default:
                        $col_style = '';
                }
                if (intval($c) > 0) {
                    $col_style .= 'width:' . intval($c) . '%;';
                }
                if ($col_style) {
                    $css .= '#id td:nth-child(' . ($key + 1) . '){' . $col_style . '}';
                }
            }
        }
        $this->load_css_head($css);
    }

    // class
}
