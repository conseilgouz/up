<?php

/**
 * Retourne la valeur d'un custom field pour le contenu courant
 *
 * syntaxe
 * {up field=id_or_name_field}  // contenu brut du champ
 * {up field=id_or_name_field | model=value}  // contenu mis en forme du champ
 * {up field=id_or_name_field | model=label : [b]%id_or_name_field%[/b]}  // modèle avec BBCODE
 *
 * @version  UP-2.3
 * @author   Lomart
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Joomla
 *
 */
/*
 * doc : https://docs.joomla.org/J3.x:Adding_custom_fields/fr
 * marc : https://cinnk.com/magazine/archives/aller-plus-loin-avec-les-custom-fields-champs-personnalises-de-joomla-3-7-juillet-2017
 */
defined('_JEXEC') or die;

class field extends upAction {

    function init() {
        return true;
    }

    function run() {

// lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // id ou name du champ
            'model' => '', // modèle BBCODE avec id ou nom des champs entre signes %
            'separator' => ',', // sépare les éléments d'un tableau pour rawvalue
            'tag' => '', // balise pour bloc principal
            /* [st-annexe] style et options secondaires */
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);
        $id = $options[__class__];

        // ==== Les CF de l'article par id & name
        // ==========================================================
        foreach ($this->article->jcfields AS $field) {
            $cf[$field->id] = $field;
            $cf[$field->name] = $field;
        }

        // ==== retour avec message si champ inexistant
        // ==========================================================
        if (isset($cf[$id]) === false) {
            $this->msg_error($id . ' : field not found');
            return '';
        }

        // ==== retour direct si CF non argumenté
        // note: si default_value -> rawvalue = default_value
        // ==========================================================
        if (empty($cf[$id]->rawvalue))
            return '';

        // ==== sans model, ni tag, on retourne rawvalue
        // ==========================================================
        $model = $options['model'];
        if ($model == '') {
            $out = $cf[$id]->rawvalue;
            if (is_array($out))
                $out = implode($options['separator'], $cf[$id]->rawvalue);
            return $out;
        }

        // ==== mise en forme
        // ==========================================================
        // ajouter les % pour un élément unique
        if (strpos($model, '%') === false)
            $model = '%' . $model . '%';
        // extraire tous les noms d'éléments du model
        $model = $this->get_bbcode($model);
        preg_match_all('#%(.*)%#U', $model, $kw);
        // la chaine model peut contenir du bbcode
        $out = $model;
        // traitement rawvalue qui peut être un array
        if (is_array($cf[$id]->rawvalue))
            $cf[$id]->rawvalue = implode($options['separator'], $cf[$id]->rawvalue);
        // remplacer les mots-clé
        for ($i = 0; $i < count($kw[0]); $i++) {
            $param = str_replace('fieldparams->', '', $kw[1][$i]);
            if ($param != $kw[1][$i]) {
                $tmp = json_decode($cf[$id]->fieldparams);
                $out = str_ireplace($kw[0][$i], $tmp->{$param}, $out);
            } else {
                $out = str_ireplace($kw[0][$i], $cf[$id]->{$param}, $out);
            }
        }

        // === CSS-HEAD
        // ==========================================================
        $this->load_css_head($options['css-head']);

        // === Insertion dans bloc HTML ?
        // ==========================================================
        if ($options['tag']) {
            // attributs du bloc principal
            $attr_main = array();
            $attr_main['id'] = $options['id'];
            $this->get_attr_style($attr_main, $options['class'], $options['style']);

            // code en retour
            $out = $this->set_attr_tag($options['tag'], $attr_main, $out);
        }

        // ==== FINI
        return $out;
    }

// run
}

// class
