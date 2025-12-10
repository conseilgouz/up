<?php

/**
 * tables responsives par empilement des lignes d'une colonne.
 *
 * Syntaxe {up table-par-colonnes}
 * < table> ... < /table>
 * {/up table-par-colonnes}
 *
 * Les lignes sont empilées par colonnes. Très pratique pour des plannings
 *
 * @author    lomart
 * @version   UP-1.0
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="http://johnpolacek.github.io/stacktable.js/" target="_blank">John Polacek</a>
 * @tags Responsive
 * */

/*
 * - v2.8 : ajout option css-head
 * - v5.1 : messages pour balise interdite
 */
defined('_JEXEC') or die();

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class table_by_columns extends Lomart\Plugin\Content\Up\Extension\Up
{

    function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        UpHelper::load_file($this,'up-stacktable.js');
        return true;
    }

    function run()
    {

        // cette action a obligatoirement du contenu
        if (! UpHelper::ctrl_content_exists($this)) {
            return false;
        }
        // lien vers la page de demo (vide=page sur le site de UP)
        UpHelper::set_demopage($this);

        // ===== valeur paramétres par défaut
        // il est indispensable tous les parametres generaux
        // sauf ceux du script JS
        $options_def = array(
            __class__ => '', // rien
            'model' => 'up-stacktable', // nom d'un fichier CSS prévu par le webmaster pour toutes les tables de la page
            'max-height' => '', // permet de limiter la place en hauteur par l'affichage d'un ascenseur
            'breakpoint' => '720px', // bascule en vue responsive
            'key-width' => '35%', // largeur de la première colonne en vue responsive
            'title-style' => '', // style pour la ligne titre en vue responsive
            /* [st-css] Style CSS */
            'id' => '', // Identifiant
            'style' => '', // style inline pour balise table
            'class' => '', // classe(s) pour balise table (obsolète)
            'css-head' => '' // permet d'ajouter des style à la table incluse
        );

        // on fusionne avec celles dans shortcode
        $options = UpHelper::ctrl_options($this,$options_def);
        $id = $options['id']; // l'id qui identifie le bloc action

        UpHelper::load_css_head($this,$options['css-head']);

        // ===== Message sur balises non supportées (v5.0)
        $debug = '';
        if (stripos($this->content, 'rowspan'))
            $debug .= 'ROWSPAN ';
//         if (stripos($this->content, '<thead')) // v5.1
//             $debug .= 'THEAD ';
        if (stripos($this->content, '<tfoot'))
            $debug .= 'TFOOT';
        if ($debug > '')
            return UpHelper::msg_inline($this,UpHelper::trad_keyword($this,'UNALLOWED_TAG', $debug));

        // ===== Analyse et MAJ de la table
        // balise ouvrante de la table originale et array des attributs
        preg_match('#<table.*>#U', $this->content, $table_opentag_old);
        $table_opentag_old = (! empty($table_opentag_old)) ? $table_opentag_old[0] : '';
        $table_attr = UpHelper::get_attr_tag($this,$table_opentag_old);

        // ==== actualisation attributs de la table
        $table_opentag_new = UpHelper::set_attr_tag($this,'table', $table_attr);
        $this->content = str_replace($table_opentag_old, $table_opentag_new, $this->content);

        // ===== Bloc conteneur pour la table (outer)
        // preparer un array vide pour la div outer
        $outer_attr = UpHelper::get_attr_tag($this,null);

        $outer_attr['id'] = $id;
        if ($options['max-height'] != '') {
            $outer_attr['style'] = 'max-height:' . $options['max-height'] . ';';
            $outer_attr['style'] .= 'overflow:auto;';
        }
        UpHelper::get_attr_style($this,$outer_attr, $options['class'], $options['style']);

        // ==== fichier modele css
        UpHelper::load_file($this,$options['model'] . '.css');

        // ==== action principale
        $code = '$("#' . $id . ' table").stackcolumns();';
        UpHelper::load_jquery_code($this,$code);

        // ==== code CSS dans head pour gestion breakpoint
        $prefix = '#' . $id . ' table.stacktable';
        $css = $prefix . ' .st-key {width:' . $options['key-width'] . '}';
        $css .= $prefix . ' .st-val {width: auto}';
        $css .= '@media (max-width:' . UpHelper::ctrl_unit($this,$options['breakpoint']) . ') {';
        $css .= $prefix . '.small-only { display: table; }';
        $css .= $prefix . '.large-only { display: none; }';
        $css .= '}';
        if ($options['title-style'])
            $css .= $prefix . ' .st-head-row.st-head-row-main{' . $options['title-style'] . '}';
        UpHelper::load_css_head($this,$css);

        // ==== RETOUR HTML
        $out = '';
        $out .= UpHelper::set_attr_tag($this,'div', $outer_attr);
        $out .= $this->content;
        $out .= '</div>';

        return $out;
    }

    // run
}

// class
