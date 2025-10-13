<?php

/**
 * une grille responsive sur plusieurs colonnes
 *
 * syntaxe 1 :  {up flexauto=x_ordi | tablet=x_tablet | mobile=x_mobile }contenu{/up flexauto}
 * syntaxe 2 :  {up flexauto=x_ordi-x_tablet-x_mobile }contenu{/up flexauto}
 * x=1 à 12 est le nombre de colonnes sur grand écran et tablette. 4 sur mobile.
 *
 * @author    Lomart
 * @version   UP-1.0
 * @license  <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags Layout-static
 */
/*
 * v1.8 - ajout options bloc-style & css-head.
 * - Ajout séparateur {===}
 * v2.10 - possibilité saisie largeurs responsives sous la forme G-T-M
 * v5.0 - ajout des classes de reperage aux blocs enfants
 * v5.1.1 - pas de div dans un p
 * v5.1.2 - ajout options class-col et class-row
 * v5.2 - optimisation chargement simple_html_dom.php & prise en charge class2style
 * - ajout option no-content-html
 */
defined('_JEXEC') or die();

class flexauto extends upAction
{
    public function init()
    {
        return true;
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
            __class__ => '3', // nombre de colonnes sur grand écran ou ordi-tablet-mobile
            'tablet' => '2', // nombre de colonnes sur moyen écran
            'mobile' => '1', // nombre de colonnes sur petit écran
            /* [st-css] Style bloc principal */
            'id' => '', // identifiant
            'class' => '', // class ou style pour le bloc principal
            'style' => '', // class ou style pour le bloc principal
            /* [st-bloc] Style des blocs secondaires */
            'bloc-tag' => 1, // uniquement si {===} 0=pas de bloc
            'class-*' => '', // class pour tous les blocs colonnes. sinon voir class-1 à class-12
            'style-*' => '', // style ou class2style pour tous les blocs colonnes. sinon voir style-1 à style-12
            'bloc-style' => '', // style ou class2style ajoutés au bloc enfant
            /*[st-classname] Nom des classes identificatrices */
            'class-col' => 'up-col-', // nom des classes pour les colonnes
            'class-row' => 'up-row-', // nom des classes pour les lignes
            /* [st-head] Style ajouté dans le head de la page */
            'css-head' => '', // style ou class2style ajouté dans le HEAD de la page
            /* [st-div] Divers*/
            'no-content-html' => 'en=no content;fr=aucun contenu' // message si aucun contenu
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        $colname = $options['class-col'];
        $rowname = $options['class-row'];

        // -- v2.1 saisie largeurs selon écran dans l'option principale
        $nbcol = $options[__class__];
        if (strpos($options[__class__], '-') !== false) {
            $tmp = array_map('trim', explode('-', $options[__class__]));
            $nbcol = $tmp[0];
            $options[__class__] = $nbcol;
            if (isset($tmp[1])) {
                $options['tablet'] = $tmp[1];
            }
            if (isset($tmp[2])) {
                $options['mobile'] = $tmp[2];
            }
        }

        // === css-head
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

        // -- ajout options utilisateur dans la div principale
        $attr_main['id'] = $options['id'];
        $this->get_attr_style($attr_main, 'fg-row fg-auto-' . $options[__class__], 'fg-auto-m' . $options['tablet'], 'fg-auto-s' . $options['mobile'], $options['class'], $options['style']);

        // -- ajout des styles pour les colonnes
        // note: le style général est toujours appliqué
        // exemple: bordure identique pour toutes les colonnes + fond pour une spécifique
        $classForAll = (empty($options['class-*'])) ? '' : $options['class-*'] . ' ';
        $colClass = array(); // les attributs
        for ($i = 0; $i < $nbcol; $i++) {
            $colClass[$i + 1]['class'] = $options['class-' . ($i + 1)];
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

        // ================================================================================
        // le code HTML en retour
        // ================================================================================
        if (empty($this->content)) {
            $out = $this->msg_inline($options['no-content-html']);
        } else {
            $out = $this->set_attr_tag('div', $attr_main, $this->content);
        }
        return $out;
    }
    // run
}

// class
