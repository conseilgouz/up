<?php

/**
 * Retourne des champs d'une source de données selon un template de mise en forme
 *
 * syntaxe {up data-info=data_source | template=##nom_champ##}
 *
 * @version  UP-3.0  
 * @author   lomart
 * @license  <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Expert
 *
 */

/*
 * v5.1 - retourne plusieurs lignes. Pour avoir uniquement la première, utiliser lign-select=1
 * - ajout options lign-filter, lign-sort et lign-max
 */
defined('_JEXEC') or die();

class data_info extends upAction
{

    function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            /* [st-data] emplacement et type des données */
            __class__ => '', // URL vers data ou fichier
            'datatype' => '', // pour forcer la détection du type de données (json, xml, csv)
            'encoding' => '', // codage des caractères de la source : ISO-8859-1
            /* [st-csv] uniquement pour des données CSV ou XML */
            'csv-header' => 1, // 0 ou 1 pour indiquer que la première ligne contient les titres de colonnes
            'csv-header-title' => '', // liste des titres de colonnes séparés par csv-separator
            'csv-separator' => ';', // caractère séparateur pour les colonnes
            'xml-attributes' => 0, // les champs dans la balise ouvrante sont dans un sous-tableau @attributes, Non par défaut: 0 /* [st-sel-lign] sélection des lignes */
            /* [st-sel-lign] sélection des lignes */
            'lign-root' => '', // chemin de la clé racine. Exemple: trk/trkseg
            'lign-select' => '', // indice du groupe de données ou champ:val1;val2 pour recherche champ=val1 ou val2
            'lign-filter' => '', // filtrage après lign-select sous la forme champ[=>,<=,==,<>]valeur OU field><valeurMin-valeurMax
            'lign-sort' => '', // tri des données sous la forme champ1:asc|desc, champ2:asc|desc, ...
            'lign-max' => '', // nombre de lignes retournées
            /* [st-tmpl] template pour mise en forme */
            'template' => '', // modèle de mise en forme résultat
            /* [st-type] type des contenus */
            'col-type' => '', // date, url, image, boolean, compact ou format pour fonction php: sprintf
            'date-format' => '%e %B %Y', // format pour les dates
            'boolean-in' => '1,0,', // valeurs dans fichier pour true,false,null
            'boolean-out' => 'lang[fr=oui,non,-;en=yes,no,n.a.]', // texte en sortie pour les valeurs true,false,null
            'url-target' => '_blank', // Cible pour ouverture URL
            'image-path' => '', // chemin vers une image dans les données
            'image-max-size' => '', // coté du carré dans lequel elle sera inscrite. Exemple: 100px
            /* [st-empty] si un champ est vide */
            'col-empty' => '', // contenu d'un champ si vide u égal à zéro. ex: colname:none, ...
            'no-data-html' => 'en=no data for %s;fr=aucune donnée pour %s', // contenu si aucune donnée disponible. BBcode admis.
            /* [st-style] habillage du bloc retourné */
            'id' => '', // identifiant
            'tag' => '', // balise pour bloc principal
            'class' => '', // classe(s) ou style inline pour bloc principal
            'style' => '', // classe(s) ou style inline pour bloc principal
            'css-head' => '', // style ajouté dans le HEAD de la page
            /* [st-cache] cache interne */
            'cache-delay' => 30 // durée du cache en minutes. 0 pas de cache
        );

        include_once ($this->upPath . '/assets/lib/data.php');

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // === CSS-HEAD
        $this->load_css_head($options['css-head']);

        // ========================
        // === recup des données
        // ========================
        $data = get_data($options[__class__], $options['cache-delay']);
        if ($data == '') {
            return $this->msg_inline('data-info - data source not found or empty' . $options[__class__]);
        }

        // Conversion des données en array
        $data = convert_data_to_array($data, $options);
        if ($data == '') {
            return $this->msg_inline('data2table - format data source invalid : ' . $options[__class__]);
        }

        // consolidation des options de formattage
        $options['boolean-out'] = $this->get_bbcode($options['boolean-out']);
        $options['col-type'] = $this->get_bbcode($options['col-type']);
        $options['col-empty'] = $this->get_bbcode($options['col-empty']);
        fix_options($options);

        // selection de la racine options['root']
        if ($options['lign-root'] != '')
            if (get_root($data, $options) === false)
                return $this->msg_error('l\'élément ' . $options['lign-root'] . ' n\'existe pas dans le fichier');

        // --- tri des données v5.1
        $msg = sort_data($data, $this->strtoarray($options['lign-sort'], ',', ':', false));
        if ($msg)
            $this->msg_error($msg . ' for ' . $options[__class__]);

        // selection options['select']
        if ($options['lign-select'] != '') {
            $msg = get_select($data, $options);
            if ($msg)
                $this->msg_error($msg . ' for ' . $options[__class__]);
        }

        // --- filtrage des données v5.1
        // nomcol:condition(<=,>=,==,<>,><)valeur
        $msg = get_filter($data, $options['lign-filter']);
        if ($msg)
            $this->msg_error($msg . ' ' . $options['lign-filter'] . ' for ' . $options[__class__]);

        // --- lign-max v5.1
        if ((int) $options['lign-max'] > 0) {
            $data = array_slice($data, 0, (int) $options['lign-max']);
        }

        // HTML si pas de donnée v5.1
        if (empty($data))
            return sprintf($this->get_bbcode($options['no-data-html']), $options[__class__]);

        // mise en forme pour retour
        $tmpl = $this->get_bbcode($options['template']);
        if (empty($tmpl)) {
            $tmpl = $this->get_bbcode($this->content);
            if (empty($tmpl))
                return $this->msg_inline('data-info - template not found ' . $options[__class__]);
        }
        if ($tmpl == '')
            return $this->msg_error('no template');

        // -- on récupère les champs demandés
        $out = array();
        if (preg_match_all('#\#\#(.*)\#\##U', $tmpl, $fields)) {
            foreach ($data as $data_lign) {
                $lign = $tmpl;
                foreach ($fields[1] as $field) {
                    $ret = get_col_value($field, $data_lign, $options);
                    if ($ret[0]) {
                        $val = $ret[0];
                    } else {
                        $val = (isset($options['col-empty'][$field])) ? $options['col-empty'][$field] : '';
                    }
                    $class = $ret[1];
                    if (is_array($val)) {
                        $str = '';
                        array_to_string($str, $val);
                        $val = $str;
                    }
                    $lign = str_ireplace('##' . $field . '##', $val, $lign);
                }
                $out[] = $lign;
            }
        }

        // attributs du bloc principal
        $attr_main = array();
        $attr_main['id'] = $options['id'];
        $this->get_attr_style($attr_main, $options['class'], $options['style']);

        // code en retour
        $html = $this->set_attr_tag($options['tag'], $attr_main, implode(PHP_EOL, $out));

        return $html;
    }

    // run
    function get_data_field($data, $fieldlist)
    {
        $fields = explode(',', $fieldlist);
        foreach ($fields as $key)
            $data = $data[$key];
        return $data;
    }
}

// class
