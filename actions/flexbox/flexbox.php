<?php

/**
 * affiche des blocs enfants dans une grille FLEXBOX responsive
 *
 * syntaxe 1 : {up flexbox=x1-x2}contenu avec plusieurs blocs enfants{/up flexbox}
 * syntaxe 2 : {up flexbox=x1-x2}contenu bloc-1 {====} contenu bloc-2{/up flexbox}
 *
 * x1-x2 sont les largeurs relatives des colonnes
 * exemple : flexbox=4-8 ou flexbox=33-66 pour 2 colonnes (équivalent: span4 et span8)
 *
 * Note : les options class-1 à class-6 et style-1 à style-6 sont à saisir directement dans le shortcode
 *
 * @author  Lomart
 * @version UP-1.0
 * @license  <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags   Layout-static
 */
/*
 * v5.1 - ajout options bloc-tag
 * - reprise code pour ajout classes row-x et col-x aux blocs
 * v5.1.2 - ajout options class-col et class-row
 * v5.2 - ajout option alternate
 * - optimisation chargement simple_html_dom.php
 * - prise en charge class2style
 * - ajout option no-content-html
 */
defined('_JEXEC') or die();

class flexbox extends upAction
{
    public function init()
    {
        // charge la feuille de style UP
        require_once($this->upPath . '/assets/lib/simple_html_dom.php');
        //$this->load_upcss();
    }

    public function run()
    {
        if (! $this->ctrl_content_exists()) {
            return false;
        }
        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        // ===== valeur paramétres par défaut
        // il est indispensable de tous les définir ici
        $options_def = array(
            __class__ => '', // nombre de colonnes ou prefset sous la forme x-x-x-x
            'tablet' => '', // nombre de colonnes sur moyen écran sous la forme x-x-x-x
            'mobile' => '', // nombre de colonnes sur petit écran sous la forme x-x-x-x
            'alternate' => '0', // valeur du breapoint pour inverser l'ordre des colonnes sur les lignes paires. option sans argument ou 1=480
            /* [st-css] Style bloc principal */
            'id' => '', // identifiant du bloc principal
            'class' => '', // class ou style pour le bloc principal
            'style' => '', // class ou style pour le bloc principal
            /* [st-bloc] Style des blocs secondaires */
            'bloc-tag' => 1, // ajoute un bloc DIV autour des contenus enyre {===}
            'class-*' => '', // class pour tous les blocs colonnes. sinon voir class-1 à class-6
            'style-*' => '', // style inline pour tous les blocs colonnes. sinon voir style-1 à style-6
            'bloc-style' => '', // style et class2style ajoutés au bloc enfant
            /*[st-classname] Nom des classes identificatrices */
            'class-col' => 'up-col-', // nom des classes pour les colonnes
            'class-row' => 'up-row-', // nom des classes pour les lignes
            /* [st-head] Style ajouté dans le head de la page */
            'css-head' => '', // style ajouté dans le HEAD de la page
           /* [st-div] Divers*/
           'no-content-html' => 'en=no content;fr=aucun contenu' // message si aucun contenu
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // si alternate = 1, on force à 480px
        $alternate = $options['alternate'];
        if ($alternate == 1) {
            $alternate = '480';
        }

        $colname = $options['class-col'];
        $rowname = $options['class-row'];

        // ======== les styles des colonnes
        // -- taille des colonnes (version rwd)
        $colSize = array(); // les largeurs de colonnes ordi, tablet et mobile
        $colSize[0] = explode('-', $options[__class__]);
        $nbcol = count($colSize[0]); // le nombre de colonnes est défini en vue normale
        $tmp = $this->str_append($options['tablet'], 'x-x-x-x-x-x', '-');
        $colSize[1] = explode('-', $tmp);
        // v2.3 si non défini, force colonne à 100% en mobile
        $tmp = $this->str_append($options['mobile'], '12-12-12-12-12-12', '-');
        $colSize[2] = explode('-', $tmp);
        // ======== css-head
        $css = $options['css-head'];
        $css .= ($options['bloc-style']) ? '#id > *[' . $options['bloc-style'] . ']' : '';
        // --- ajout des styles pour les colonnes
        if (! empty($options['style-*'])) {
            $css .= '#id > *[' . $options['style-*'] . ']';
        }
        for ($i = 0; $i < $nbcol; $i++) {
            if (! empty($options['style-' . ($i + 1)])) {
                $css .= '#id .col-' . ($i + 1) . '[' . $options['style-' . ($i + 1)] . ']';
            }
        }
        $this->load_css_head($css);

        // ======== ajout options utilisateur dans la div principale
        $attr_main['id'] = $options['id']; // v1.8
        $this->get_attr_style($attr_main, 'fg-row', $options['class'], $options['style']);


        // -- ajout des styles pour les colonnes
        // note: le style général est toujours appliqué
        // exemple: bordure identique pour toutes les colonnes + fond pour une spécifique
        $classForAll = (empty($options['class-*'])) ? '' : $options['class-*'] . ' ';
        $colClass = array(); // les attributs
        for ($i = 0; $i < $nbcol; $i++) {
            $class = 'fg-c' . $colSize[0][$i];
            $class .= ($colSize[1][$i] != 'x') ? ' fg-cm' . $colSize[1][$i] : '';
            $class .= ($colSize[2][$i] != 'x') ? ' fg-cs' . $colSize[2][$i] : '';
            $class .= ' '. $options['class-' . ($i + 1)];
            $colClass[$i + 1]['class'] = $class;
        }

        // ================================================================================
        // RECUPERATION & ANALYSE CONTENU
        // --- Suppression des shortcodes separateurs {====}
        // --- si bloc-tag, on ajoute balise DIV
        // ================================================================================

        $Content_OK = false;
        $this->content = $this->supertrim($this->content);  //v5.1.1
        if ($this->ctrl_content_parts($this->content) === true) {
            $Content_OK = ($options['bloc-tag'] == 1);
            $allcoltxt = $this->get_content_parts($this->content);
            $this->content = '';
            $i = 0;
            foreach ($allcoltxt as $coltxt) {
                $i++;
                if (str_starts_with($coltxt, '<p></p>')) {
                    $coltxt = substr($coltxt, 7);
                }
                if (str_ends_with($coltxt, '<p></p>')) {
                    $coltxt = substr($coltxt, 0, - 7);
                }
                if (empty($options['bloc-tag'])) {
                    $this->content .= $coltxt;
                } else {
                    $row = ceil($i / $nbcol);
                    $col = ($i - ($row - 1) * $nbcol);
                    $class = 'item-' . $i;
                    $class .= ' '.$colname . $col;
                    $class .= ' '.$rowname . $row;
                    $class .= ' '.$rowname . (($row % 2 == 0) ? 'even' : 'odd');
                    $class .= ' '.$classForAll;
                    $class .= ' '.$colClass[$col]['class'];
                    $items[] =  '<div class="'.trim($class).'">' . $coltxt . '</div>';
                }
            }
        }

        if ($Content_OK === false) {
            // Analyse par simple_html_dom
            // --- ajout des classes de reperage aux blocs enfants
            require_once($this->upPath . '/assets/lib/simple_html_dom.php');
            $html = new simple_html_dom();
            $html->load('<html>' . $this->content . '</html>', true, false);

            $childs = $html->find('html>*');
            $i = 0;
            foreach ($childs as $child) {
                $i++;
                $row = ceil($i / $nbcol);
                $col = ($i - ($row - 1) * $nbcol);
                $child->addClass('item-' . $i);
                $child->addClass($colname . $col);
                $child->addClass($rowname . $row);
                $child->addClass($rowname . (($row % 2 == 0) ? 'even' : 'odd'));
                $child->addClass($classForAll);
                $child->addClass($colClass[$col]['class']);
                // pas de div dans un p  v5.1.1
                if ($child->tag == 'p') {
                    $child->tag = 'div';
                }
                $items[] = $child->outertext;
            }
            $html->clear();
            unset($html);
        }
        $this->content = implode(PHP_EOL, $items ?? []);

        // === css pour ordre des colonnes sur smartphone v5.2
        if ($alternate) {
            $nbItem = count($items);
            $css = '@media(min-width:'.$alternate.'px){';
            for ($i = 1; $i <= $nbItem; $i++) {
                $row = ceil($i / $nbcol);
                $col = ($i - ($row - 1) * $nbcol);
                $pair = ($row % 2 == 0);
                if ($pair) {
                    $order = $i - $col + ($nbcol - $col + 1);
                } else {
                    $order = $i;
                }
                $css .= '#' . $options['id'] . ' .item-' . $i . '{order:' . $order . '}';
            }
            $css .= '}';
            $this->load_css_head($css);
        }


        // ================================================================================
        // le code HTML en retour
        // ================================================================================
        if (! isset($this->content)) {
            $out = $this->msg_inline($options['no-content-html']);
        } else {
            $out = $this->set_attr_tag('div', $attr_main, $this->content);
        }
        return $out;
    }

    // run
}

// class
