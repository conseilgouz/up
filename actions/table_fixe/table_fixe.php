<?php

/**
 * tables responsives: ligne entete reste visible
 *
 * {up table-fixe}
 * < table> ... < /table>
 * {/up table-fixe}
 *
 * col-left :  nombre de colonnes toujours visible.
 * Une barre de défilement est ajoutée pour les autres colonnes.
 *
 * @author    lomart
 * @version   UP-1.0
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="http://www.jqueryscript.net/table/jQuery-Plugin-For-Fixed-Table-Header-Footer-Columns-TableHeadFixer.html" target="_blank">lai32290</a>
 * @tags      Responsive
 * */

/*
 * - v2.8 : ajout option css-head
 */
defined('_JEXEC') or die();

class table_fixe extends upAction
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        $this->load_file('tableHeadFixer.js');
        return true;
    }

    public function run()
    {
        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        // cette action a obligatoirement du contenu
        if (! $this->ctrl_content_exists()) {
            return false;
        }

        // ===== valeur paramétres par défaut
        // il est indispensable tous les parametres generaux
        // sauf ceux du script JS
        $options_def = array(
            __class__ => '', // aucun argument
            'col-left' => '0', // nombre de colonnes fixées à gauche
            'max-height' => '', // max-height pour le bloc parent
            /* [st-css] Style CSS */
            'id' => '', // identifiant
            'style' => '', // classes et styles pour le bloc parent
            'class' => '', // classe(s) pour le bloc parent (obsolète)
            'css-head' => '' // permet d'ajouter des style à la table incluse
        );

        // on fusionne avec celles dans shortcode
        $options = $this->ctrl_options($options_def);
        $id = $options['id']; // l'id qui identifie le bloc action

        $this->load_css_head($options['css-head']);

        // ==== ctrl thead
        if (stripos($this->content, '</thead>') === false) {
            return $this->msg_inline($this->trad_keyword('THEAD_MISSING'));
        }

        // ===== Analyse et MAJ de la table
        // balise ouvrante de la table originale et array des attributs
        preg_match('#<table.*>#U', $this->content, $table_opentag_old);
        $table_opentag_old = (! empty($table_opentag_old)) ? $table_opentag_old[0] : '';
        $table_attr = $this->get_attr_tag($table_opentag_old);

        // si l'user force l'id de la table, on la conserve
        if ($table_attr['id'] > '') {
            $id = $table_attr['id'];
        }
        $table_attr['id'] = $id;

        // ==== actualisation attributs de la table
        $table_opentag_new = $this->set_attr_tag('table', $table_attr);
        $content = str_replace($table_opentag_old, $table_opentag_new, $this->content);

        // ===== Bloc conteneur pour la table (outer)
        // preparer un array vide pour la div outer
        $outer_attr = $this->get_attr_tag(null);

        if ($options['max-height']) {
            $this->get_attr_style($outer_attr, 'max-height:' . $options['max-height']);
            $this->get_attr_style($outer_attr, 'overflow:auto');
        }
        // ajout paramétres user
        $this->get_attr_style($outer_attr, $options['class'], $options['style']);

        // ==== action principale
        $code = '$("#' . $id . '").tableHeadFixer(';
        if ($options['col-left']) {
            $code .= '{"left" :' . $options['col-left'] . '}';
        }
        $code .= ');';
        $this->load_jquery_code($code);

        // ==== RETOUR HTML
        $out = '';
        $out .= $this->set_attr_tag('div', $outer_attr);
        $out .= $content;
        $out .= '</div>';

        return $out;
    }

    // run
}

// class
