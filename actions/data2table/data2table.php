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

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class data2table extends Lomart\Plugin\Content\Up\Extension\Up
{

    function init()
    {
        UpHelper::load_file($this,'data2table.css');
        return true;
    }

    function run()
    {

        // lien vers la page de demo
        UpHelper::set_demopage($this);

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
        $options = UpHelper::ctrl_options($this,$options_def);

        // === CSS-HEAD
        UpHelper::load_css_head($this,$options['css-head']);

        $data = get_data($options[__class__], $options['cache-delay']);
        if ($data == '') {
            return UpHelper::msg_inline($this,'data-info - data source not found or empty' . $options[__class__]);
        }

        // Conversion des données en array
        $data = convert_data_to_array($data, $options, true);
        if ($data == '') {
            return UpHelper::msg_inline($this,'data2table - format data source invalid : ' . $options[__class__]);
        }

        // consolidation des options de formattage
        $options['boolean-out'] = UpHelper::get_bbcode($this,$options['boolean-out']);
        $options['col-type'] = UpHelper::get_bbcode($this,$options['col-type']);
        $options['col-empty'] = UpHelper::get_bbcode($this,$options['col-empty']);
        fix_options($options);
        if ($options['datatype'] = 'json')
            $options['xml-attributes'] = 1;

        // selection de la racine options['root']
        if ($options['lign-root'] != '')
            if (get_root($data, $options) === false)
                return UpHelper::msg_error($this,UpHelper::trad_keyword($this,'ITEM_NOT_FOUND', $options['lign-root']));

        // --- tri des données v5.1
        $msg = sort_data($data, UpHelper::strtoarray($this,$options['lign-sort'], ',', ':', false));
        if ($msg)
            UpHelper::msg_error($this,$msg . ' for ' . $options[__class__]);

        // selection options['select']
        if ($options['lign-select'] != '') {
            $msg = get_select($data, $options);
            if ($msg)
                UpHelper::msg_error($this,$msg . ' for ' . $options[__class__]);
        }

        // --- filtrage des données v5.1
        // nomcol:condition(<=,>=,==,<>,><)valeur
        $msg = get_filter($data, $options['lign-filter']);
        if ($msg)
            UpHelper::msg_error($this,$msg . ' ' . $options['lign-filter'] . ' for ' . $options[__class__]);

        // --- lign-max v5.1
        if ((int) $options['lign-max'] > 0) {
            $data = array_slice($data, 0, (int) $options['lign-max']);
        }

        // === les sous-titres des colonnes (THEAD)
        $title = UpHelper::get_title($this,$data, $options);
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
                UpHelper::msg_error($this,UpHelper::trad_keyword($this,'ERROR_COL_LIST', implode(',', $col_novalid)));
            }
        }
        $out = UpHelper::make_table($this,$data, $title, $options);

        // attributs du bloc principal
        $attr_main = array();
        $attr_main['id'] = $options['id'];
        $attr_main['class'] = 'data2table ' . $options['model'];
        UpHelper::get_attr_style($this,$attr_main, $options['class'], $options['style']);

        // code en retour
        $out = UpHelper::set_attr_tag($this,'table', $attr_main, implode(PHP_EOL, $out));

        return $out;
    }

    /* run */

    // --- fin class
}
