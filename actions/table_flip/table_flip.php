<?php

/**
 * tables responsives par permutation lignes/colonnes
 *
 * Inversion colonnes/lignes. les titres de colonnes deviennent la 1ère colonne et reste visibles.
 * Une barre de défilement est ajoutée pour les autres colonnes.
 * {up table-flip}
 * < table> ... < /table>
 * {/up table-flip}
 *
 * @author    lomart
 * @version   UP-1.0
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="https://codepen.io/JasonAGross/pen/rjmyx" target="_blank">Jason Gross</a>
 * @tags  Responsive
 * */

 /*
 - v2.8 : ajout option css-head
 */
 
defined('_JEXEC') or die;

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class table_flip extends Lomart\Plugin\Content\Up\Extension\Up {

    function init() {
        // charger les ressources communes à toutes les instances de l'action
        UpHelper::load_file($this,'flip.js');
        UpHelper::load_file($this,'flip.css');
        return true;
    }

    function run() {

        // cette action a obligatoirement du contenu
        if (!UpHelper::ctrl_content_exists($this)) {
            return false;
        }

        // lien vers la page de demo (vide=page sur le site de UP)
        UpHelper::set_demopage($this);

        // ===== valeur paramétres par défaut
        // il est indispensable tous les parametres generaux
        // sauf ceux du script JS
        $options_def = array(
          __class__ => '', // aucun argument
          /* [st-css] Style CSS*/
          'id' => '', // identifiant
          'style' => '', // classes et styles inline pour balise table
          'class' => '', // classe(s) pour balise table (obsolète)
		  'css-head' => '',  // permet d'ajouter des style à la table incluse
        );
        // on fusionne avec celles dans shortcode
        $options = UpHelper::ctrl_options($this,$options_def);
        $id = $options['id'];  // l'id qui identifie le bloc action
		
        UpHelper::load_css_head($this,$options['css-head']);

        // ===== Analyse et MAJ de la table
        // balise ouvrante de la table originale et array des attributs
        $table_opentag_old = array();
        preg_match('#<table.*>#U', $this->content, $table_opentag_old);
        $table_opentag_old = (!empty($table_opentag_old)) ? $table_opentag_old[0] : '';
        $table_attr = UpHelper::get_attr_tag($this,$table_opentag_old);

        // ==== actualisation attributs de la table
        UpHelper::add_class($this,$table_attr['class'], 'fliptable');
        UpHelper::add_style($this,$table_attr['style'], 'max-width', '100%');
        $table_opentag_new = UpHelper::set_attr_tag($this,'table', $table_attr);
        $this->content = str_replace($table_opentag_old, $table_opentag_new, $this->content);

        // ===== Bloc conteneur pour la table (outer)
        // preparer un array vide pour la div outer
        $outer_attr = UpHelper::get_attr_tag($this,null);

        $outer_attr['id'] = $id;
        UpHelper::add_style($this,$outer_attr['style'], 'overflow', 'auto');

        // ajout paramétres user
        UpHelper::get_attr_style($this,$outer_attr, $options['class'], $options['style']);

        //  ====  action principale
        // $code = '$("#'.$id.' table").flip();';
        // load_jquery_code($code);
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
