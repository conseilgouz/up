<?php

/**
 * Affiche le contenu d'un fichier JSON, XML ou CSV sous forme d'une liste
 *
 * syntaxe {up data2list=data_source}
 *
 * @version  UP-3.0  
 * @author   lomart
 * @license  <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Expert
 *
 */

/*
 * v5.1 - ajout options lign-filter, lign-sort et lign-max
 */
defined('_JEXEC') or die();

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class data2list extends Lomart\Plugin\Content\Up\Extension\Up
{

    function init()
    {
        return true;
    }

    function run()
    {

        // lien vers la page de demo
        UpHelper::set_demopage($this);

        $options_def = array(
            /* [st-data] emplacement et type des données */
            __class__ => '', // URL vers data ou fichier
            'datatype' => '', // pour forcer la détection du type de données (json, xml, csv)
            'encoding' => '', // codage des caractères de la source. ex: ISO-8859-1
            /* [st-csv] uniquement pour des données CSV ou XML */
            'csv-header' => 1, // 1 si la première ligne contient les titres des colonnes.
            'csv-header-title' => '', // titres des colonnes séparées par csv-separator. Si défini, remplace les titres du fichier
            'csv-separator' => ';', // caractère séparateur pour les colonnes
            'xml-attributes' => 0, // les champs dans la balise ouvrante sont dans un sous-tableau @attributes, Non par défaut
            /* [st-sel-lign] sélection des lignes */
            'lign-root' => '', // chemin vers la ligne racine. Exemple: trk/trkseg
            'lign-select' => '', // indice(s) de(s) ligne(s) de données ou col1:val1;ou val2, colN:valN pour recherche contenu
            'lign-filter' => '', // filtrage après lign-select sous la forme champ[=>,<=,==,<>]valeur OU field><valeurMin-valeurMax
            'lign-sort' => '', // tri des données sous la forme champ1:asc|desc, champ2:asc|desc, ...
            'lign-max' => '', // nombre de lignes retournées
            /* [st-sel-col] sélection des colonnes/champs */
            'col-include' => '', // liste des champs retournés (séparateur virgule)
            'col-exclude' => '', // liste des champs non retournés (séparateur virgule)
            /* [st-tmpl] template pour mise en forme */
            'template ' => '##LABEL##: ##VALUE##', // modèle pour une ligne
            'array-subtitle' => '', // contenu du premier niveau de la liste. vide=indice
            /* [st-format] mise en forme du contenu */
            'col-class' => '', // classes pour les champs (champ1:class1 class2,champ2:class)
            'col-label' => '', // correspondance entre nom du champ et titre colonne (champ1:col1,champ2:col2, ...)
            /* [st-type] type des contenus */
            'col-type' => '', // date, url, image, boolean, string ou format pour fonction php: sprintf
            'date-format' => '%e %B %Y', // format pour les dates
            'boolean-in' => '1,0,', // valeurs dans fichier pour true,false,null
            'boolean-out' => 'lang[fr=oui,non,-;en=yes,no,n.a.]', // texte en sortie pour les valeurs true,false,null
            'url-target' => '_blank', // Cible pour ouverture URL
            'image-path' => '', // chemin vers une image dans les données
            'image-max-size' => '', // coté du carré dans lequel elle sera inscrite. Exemple: 100px
            /* [st-empty] si un champ est vide */
            'col-empty' => '', // contenu d'un champ si vide u égal à zéro. ex: colname:none, ...
            'col-empty-invisible' => 0, // 1 = ne pas afficher les labels des champs avec valeur vide
            'no-data-html' => 'en=no data for %s;fr=aucune donnée pour %s', // contenu si aucune donnée disponible. BBcode admis.
            /* [st-style] habillage du bloc retourné */
            'id' => '',
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '', // style ajouté dans le HEAD de la page
            /* [st-cache] Gestion du cache interne */
            'cache-delay' => 30 // durée du cache en minutes. 0 pas de cache
        );

        include_once ($this->upPath . '/assets/lib/data.php');

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);

        // === CSS-HEAD
        UpHelper::load_css_head($this,$options['css-head']);

        // ========================
        // === recup des données
        // ========================
        $data = get_data($options[__class__], $options['cache-delay']);
        if ($data == '') {
            return UpHelper::msg_inline($this,'data-info - data source not found or empty' . $options[__class__]);
        }

        // Conversion des données en array
        $data = convert_data_to_array($data, $options, false);
        if ($data == '') {
            return UpHelper::msg_inline($this,'data2list - format data source invalid : ' . $options[__class__]);
        }

        // consolidation des options de formattage
        $options['boolean-out'] = UpHelper::get_bbcode($this,$options['boolean-out']);
        $options['col-type'] = UpHelper::get_bbcode($this,$options['col-type']);
        $options['col-empty'] = UpHelper::get_bbcode($this,$options['col-empty']);
        $options['template '] = UpHelper::get_bbcode($this,$options['template ']);
        fix_options($options);

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

        // HTML si pas de donnée v5.1
        if (empty($data))
            return sprintf(UpHelper::get_bbcode($this,$options['no-data-html']), $options[__class__]);

        if (! empty($options['array-subtitle'])) {
            $options['array-subtitle'] = UpHelper::get_bbcode($this,$options['array-subtitle']);
            $options['array-subtitle'] = strtoarray($options['array-subtitle']);
            foreach ($options['array-subtitle'] as $k => $v) {
                if (preg_match_all('#\#\#(.*)\#\##U', $v, $matches))
                    $this->array_subtitle[$k] = $matches[1];
            }
        }
        // === resultat
        $this->result = array();
        UpHelper::make_list($this,$data, $options);

        // attributs du bloc principal
        $attr_main = array();
        $attr_main['id'] = $options['id'];
        UpHelper::get_attr_style($this,$attr_main, $options['class'], $options['style']);

        // code en retour
        $html[] = UpHelper::set_attr_tag($this,'div', $attr_main, implode(PHP_EOL, $this->result));

        return implode(PHP_EOL, $html);
    }

    // run


}

// class
