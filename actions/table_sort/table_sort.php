<?php

/**
 * Trier, filtrer et paginer une table
 *
 * syntaxe {up table-sort}LA TABLE{up table-sort}
 *
 * @author   LOMART
 * @version  UP-2.3
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="https://www.jqueryscript.net/table/sorting-filtering-pagination-fancytable.html" target"_blank">script jQuery fancyTable de myspace-nu</a>
 * @tags    layout-dynamic
 *
 * V 6.0.18 : 
 *  col-type : ajout l lien,
 *  force la colonne date e numérique pour le tri,
 *  fancyTable version 1.0.36,
 *  JCE: tous les th sur une ligne
 *
 */
defined('_JEXEC') or die();

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class table_sort extends Lomart\Plugin\Content\Up\Extension\Up
{
    /**
     * charger les ressources communes à toutes les instances de l'action
     * cette fonction n'est lancée qu'une fois pour la première instance
     *
     * @return true
     */
    public function init()
    {
        UpHelper::load_file($this, 'fancyTable.min.js');
        return true;
    }

    /**
     * analyse et interprete le shortcode
     *
     * @return [string] [code HTML pour remplacer le shortcode]
     */
    public function run()
    {

        // cette action a obligatoirement du contenu : la table
        if (! UpHelper::ctrl_content_exists($this)) {
            return false;
        }

        // lien vers la page de demo
        UpHelper::set_demopage($this);

        // ===== valeur paramétres par défaut (sauf JS)
        $options_def = array(
            __class__ => '', // Aucun argument
            'col-type' => '', // mode de tri des colonnes. n=numerique, a=alphanum, i=alphanum case insensitive, d=date, l=link case insensitive
            'col-init' => '', // n° de la colonne triée au chargement et sens (asc, desc)
            /* [st-rech] champs pour recherche */
            'placeholder' => 'lang[en=Search;fr=Rechercher]', // texte dans la zone recherche
            'globalSearch' => '', // vide= recherche sur toutes les colonnes, sinon liste des colonnes (1,2,5)
            /* [st-page] Pagination */
            'pagination' => 0, // nombre de lignes par pages ou 0 pour désactiver
            'pagination-class' => '', // classe pour les boutons
            'pagination-class-active' => '', // classe pour le bouton actif
            /* [st-css] Style CSS */
            'id' => '', // id genérée automatiquement par UP
            'class' => '', // classe(s) ajoutées à la table
            'style' => '', // style inline ajouté à la table
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // ===== paramétres attendus par le script JS
        // important: valeurs par défaut entre guillemets pour les chaines
        // attention: le nom des options est transmis au script sous la forme indiqué ici.
        $js_options_def = array(
        /* [st-JS] paramétres JS */
            'sortable' => 1, // Activer le tri
            'searchable' => 1 // Activer la recherche
        );

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this, $options_def, $js_options_def);

        // ===== Analyse et MAJ de la table
        // ============================================================
        // balise ouvrante de la table originale et array des attributs
        preg_match('#<table.*>#U', $this->content, $table_opentag_old);
        $table_opentag_old = (! empty($table_opentag_old)) ? $table_opentag_old[0] : '';
        $table_attr = UpHelper::get_attr_tag($this, $table_opentag_old);
        // si la table a une ID, on l'utilise
        if ($table_attr['id'] != '') {
            $options['id'] = $table_attr['id'];
        }

        // =========== le code JS
        // ============================================================
        $js_options = UpHelper::only_using_options($this, $js_options_def);
        if (! empty($this->options_user['pagination'])) {
            $js_options['pagination'] = ($options['pagination'] > 0);
            $js_options['perPage'] = $options['pagination'];
        }
        $tmp = $this->options_user;
        if (! empty($this->options_user['pagination-class'])) {
            $js_options['paginationClass'] = $options['pagination-class'];
        }
        if (! empty($this->options_user['pagination-class-active'])) {
            $js_options['paginationClassActive'] = $options['pagination-class-active'];
        }

        if (! empty($this->options_user['col-init'])) {
            list($ind, $sens) = explode(',', $this->options_user['col-init'] . ',asc');
            $js_options['sortColumn'] = intval($ind) - 1;
            $js_options['sortOrder'] = $sens;
        }
        $js_options['inputPlaceholder'] = $options['placeholder'];

        // recherche globale si demande par user
        if (isset($this->options_user['globalsearch'])) {
            $js_options['globalSearch'] = true;
            if ($this->options_user['globalsearch'] !== '') {
                // liste des colonnes utilisées pour la recherche globale
                $colarg = array_map('intval', explode(',', $this->options_user['globalsearch']));
                $nbcol = substr_count($this->content, '</th>');
                for ($i = 1; $i <= $nbcol; $i++) {
                    if (! in_array($i, $colarg)) {
                        $colsearch[] = $i;
                    }
                }
                $js_options['globalSearchExcludeColumns'] = $colsearch;
            }
        }

        // -- conversion en chaine Json
        $js_params = UpHelper::json_arrtostr($this, $js_options);
        // -- initialisation
        // on cible une classe car l'id a déjà pu être définie par le shortcode interne
        $js_code = '$("#' . $options['id'] . '").fancyTable(';
        $js_code .= $js_params;
        $js_code .= ');';
        UpHelper::load_jquery_code($this, $js_code);

        // ==== actualisation attributs de la table
        // ============================================================
        $table_attr['id'] = $options['id'];
        UpHelper::get_attr_style($this, $table_attr, $options['class'], $options['style']);
        $table_opentag_new = UpHelper::set_attr_tag($this, 'table', $table_attr);
        $this->content = str_replace($table_opentag_old, $table_opentag_new, $this->content);

        if (strpos($this->content, '<thead') === false) {
            $this->content = UpHelper::msg_inline($this, 'la table doit avoir un entête THEAD / the table must have a THEAD header') . '<br>' . $this->content;
        }

        // ==== Mode de tri selon données
        // ============================================================
        // n=numerique, a=alpha (defaut), i=alpha insensitive, d = date, l = link case insensitive
        $col_date = array();
        $col_link = array();
        if ($options['col-type']) {
            $col_type = strtolower($options['col-type']);
            $col_type = array_map('trim', explode('-', $col_type));
            $regex = '/<th(.*)>(.*)<\/th>/U'; // 6.0.18 : JCE met tous les th sur une seule ligne
            preg_match_all($regex, $this->content, $headers);
            for ($i = 0; $i < count($col_type); $i++) {
                if ($col_type[$i][0] == 'd') { // date
                    $sort = ' data-sortas="numeric"';  // 6.0.18 : force as numeric
                    if (isset($headers[0][$i])) { 
                        $this->content = str_replace($headers[0][$i], '<th ' . $headers[1][$i] . $sort . '>' . $headers[2][$i] . '</th>', $this->content);
                    }
                    $col_date[] = $i;
                } elseif ($col_type[$i][0] == 'l') { // link is case insensitive
                    $col_link[] = $i;
                    $sort = ' data-sortas="case-insensitive"';
                    if (isset($headers[0][$i])) { // v2.9
                        $this->content = str_replace($headers[0][$i], '<th ' . $headers[1][$i] . $sort . '>' . $headers[2][$i] . '</th>', $this->content);
                    }

                } else {
                    $sort = '';
                    if ($col_type[$i][0] == 'i') {
                        $sort = ' data-sortas="case-insensitive"';
                    }
                    if ($col_type[$i][0] == 'n') {
                        $sort = ' data-sortas="numeric"';
                    }
                    if (isset($headers[0][$i])) { // v2.9
                        $this->content = str_replace($headers[0][$i], '<th ' . $headers[1][$i] . $sort . '>' . $headers[2][$i] . '</th>', $this->content);
                    }
                }
            }
        }

        // ==== Valeurs de tri dans <td data-sortvalue="xxx">
        // ============================================================
        if (! empty($col_date)  || ! empty($col_link)) {
            require_once($this->upPath . '/assets/lib/simple_html_dom.php');
            // http://petit-dev.com/parsez-le-contenu-dun-site-avec-simple-html-dom-parser/
            $html = new simple_html_dom();
            $html->load($this->content);
            // exploration de la table
            $trs = $html->find('tbody tr');
            foreach ($trs as $tr) {
                $tds = $tr->find('td');
                foreach ($col_date as $ind) { // date : make it YYYYMMDD as key
                    if ($tds[$ind]) {
                        $tmp = $tds[$ind]->innertext();
                        $tmp = UpHelper::up_date_format($this, $tmp, '%Y%m%d');
                        $tds[$ind]->setAttribute('data-sortvalue', $tmp);
                    }
                }
                foreach ($col_link as $ind) { // link : take text as key
                    if ($tds[$ind]) {
                        $tmp = $tds[$ind]->innertext();
                        preg_match('/<a href="(.+)">(.+)<\/a>/', $tmp, $output_array);
                        $tds[$ind]->setAttribute('data-sortvalue', $output_array[2]);
                    }
                }
            }
            // le bloc externe de la liste
            $this->content = $html->save();
            $html->clear();
        }

        // === CSS-HEAD
        // ============================================================
        UpHelper::load_css_head($this, $options['css-head'], $options['id']);

        // fini
        // ============================================================
        return $this->content;
    }

    // run
}

// class
