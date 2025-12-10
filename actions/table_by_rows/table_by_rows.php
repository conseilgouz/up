<?php

/**
 * tables responsives par empilement des colonnes par lignes.
 *
 * {up table-par-lignes}
 * < table> ... < /table>
 * {/up table-par-lignes}
 *
 * les colonnes  sont empilées par lignes avec la possibilité de les déplacer, de les fusionner, de supprimer le titre, d'afficher seulement certaines colonnes. https://github.com/codefog/restables/blob/master/README.md
 * .
 * IMPERATIF : Les titres des colonnes doivent être dans une balise HEAD
 *
 * @author    lomart
 * @version   UP-1.0
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="https://github.com/codefog/restables/blob/master/README.md" target="_blank">codefog</a>
 * @tags Responsive
 * */

/*
 * - v2.8 : ajout option css-head
 */
defined('_JEXEC') or die();

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class table_by_rows extends Lomart\Plugin\Content\Up\Extension\Up
{

    function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        UpHelper::load_file($this,'restables.min.js');
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

        // ===== valeur paramétres par défaut (sauf JS)
        $options_def = array(
            __class__ => '', // rien
            'breakpoint' => '720px', // bascule en vue responsive
            'max-height' => '', // max-height pour la table
            /* [st-css] Style CSS*/
            'id' => '', // identifiant
            'style' => '', // style inline pour balise table
            'class' => '', // classe(s) pour balise table (obsolète)
			'css-head' => '',  // permet d'ajouter des style à la table incluse
        );

        // ===== paramétres attendus par le script JS
        $js_options_def = array(
        /* [st-JS] paramètres Javascript pour configuration */
            'merge' => '', // fusion de colonnes. 1:[2,3],5:[6] = 2&3 avec 1 et 6 avec 5
            'move' => '', // déplacement colonne. 1:0,6:1 = 1 au debut et 6 en 2eme
            'skip' => '', // non visible. [3,5] = col 3 et 5 non visible
            'span' => '' // [2,4] = valeur sans libellé (eq: colspan)
        );
        /*
         * Paramètres JS non utilisés
         * 'cssClassOrigin' => '', // defaut: restables-origin
         * 'cssClassClone' => '', // defaut: restables-clone
         * 'uniqueAttributes' => '', // defaut: ['id', 'for']
         * 'attributeSuffix' => '', // defaut: -restables-clone
         * 'cloneCallback' => '', // fonction callback. defaut: null
         * 'preserveCellClasses' => '' // defaut: true
         */

        // on fusionne avec celles dans shortcode
        $options = UpHelper::ctrl_options($this,$options_def, $js_options_def);

        UpHelper::load_css_head($this,$options['css-head']);

        // ==== ctrl thead
        if (strpos($this->content,'</thead>')===false) {
            return UpHelper::msg_inline($this,UpHelper::trad_keyword($this,'THEAD_MISSING'));
        }
        
        $id = $options['id']; // l'id qui identifie le bloc action
                              // balise table originale et array des attributs
        preg_match('#<table.*>#U', $this->content, $table_opentag_old);
        $table_opentag_old = (! empty($table_opentag_old)) ? $table_opentag_old[0] : '';
        $table_attr = UpHelper::get_attr_tag($this,$table_opentag_old);

        // si l'user force l'id de la table, on la conserve
        if ($table_attr['id'] > '')
            $id = $table_attr['id'];
        $table_attr['id'] = $id;

        // preparer un array vide pour la div outer
        $outer_attr = UpHelper::get_attr_tag($this,null);
        if ($options['max-height']) {
            UpHelper::get_attr_style($this,$outer_attr, 'max-height:' . $options['max-height']);
            UpHelper::get_attr_style($this,$outer_attr, 'overflow:auto');
        }
        // ajout paramétres user
        UpHelper::get_attr_style($this,$table_attr, $options['class'], $options['style']);

        // =========== le code JS
        // les options saisis par l'utilisateur concernant le script JS
        $js_options = UpHelper::only_using_options($this,$js_options_def);
        // -- conversion en chaine Json
        $js_params = UpHelper::json_arrtostr($this,$js_options, 2);

        $code = '$("#' . $id . '").resTables(';
        $code .= $js_params;
        $code .= ');';
        UpHelper::load_jquery_code($this,$code);

        // ==== code CSS dans head
        $prefix = 'table#' . $id . '.restables-';
        $css = $prefix . 'clone { display: none; }';
        $css .= $prefix . 'clone tr:first-child td { ';
        $css .= 'background: #eee; font-weight:bold; font-size:120% }';
        $css .= '@media (max-width:' . UpHelper::ctrl_unit($this,$options['breakpoint']) . ') {';
        $css .= $prefix . 'origin { display: none; }';
        $css .= $prefix . 'clone { display: table; }';
        $css .= '}';
        UpHelper::load_css_head($this,$css);

        // === mise à jour attributs de la table dans $content
        $table_opentag_new = UpHelper::set_attr_tag($this,'table', $table_attr);
        $this->content = str_replace($table_opentag_old, $table_opentag_new, $this->content);

        // ==== code pour retour
        $out = '';
        $out .= UpHelper::set_attr_tag($this,'div', $outer_attr);
        $out .= $this->content;
        $out .= '</div>';

        return $out;
    }

    // run
}

// class
