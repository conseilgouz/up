<?php

/**
 * Mise en page par grid
 *
 * Facilite une mise en page responsive en utilisant les propriétés CSS grid
 *
 * syntaxe {up grid='a a b' 'a a b' 'c d b'}
 *
 * @version  UP-5.2
 * @author   Lomart
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="" target"_blank">script xxx de xxx</a>
 * @tags    Layout-static
 *
 */
defined('_JEXEC') or die();

class grid extends upAction
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        require_once($this->upPath . '/assets/lib/simple_html_dom.php');
        return true;
    }

    public function run()
    {

        // si cette action a obligatoirement du contenu
        if (! $this->ctrl_content_exists()) {
            return false;
        }

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            /*[st-grid] mise en page par grid-template-areas */
            __class__ => '', // grid-template-areas
            'tablet-grid' => '', // grid-template-areas pour tablette
            'mobile-grid' => '', // grid-template-areas pour mobile
            /*[st-rawcol] taille des colonnes et des lignes */
            'grid-template-columns' => '', // définition des colonnes. par default : repeat(nb_colonnes,1fr)
            'tablet-grid-template-columns' => '', // définition des colonnes. par default : repeat(nb_colonnes,1fr)
            'mobile-grid-template-columns' => '', // définition des colonnes. par default : repeat(nb_colonnes,1fr)
            'grid-template-rows' => '', // définition des lignes. par default : auto
            'mobile-grid-template-rows' => '', // définition des lignes. par default : auto
            'tablet-grid-template-rows' => '', // définition des lignes. par default : auto
            /*[st-auto] taille et position des items placés en dehors des zones définies ci-dessus) */
            'grid-auto-columns' => '', // taille par défaut des colonnes pour les items non prévus
            'grid-auto-rows' => '', // taille par défaut des lignes pour les items non prévus
            'grid-auto-flow' => '', // placement automatique des items : row, column, dense, row dense ou column dense
            /*[st-grid-divers] nom des zones et répétition */
            'zone-name' => '', // liste des noms de zone dans l'ordre des blocs de contenu. Séparateur virgule.
            'grid-repeat' => 0, // nombre de répétitions de la grille
            /*[st-gap] espace entre les lignes et les colonnes */
            'gap' => '10px', // espace entre les blocs. valeur_verticale valeur_horizontal. 1 seule valeur pour les deux
            'tablet-gap' => '', // espace entre les blocs. valeur_verticale valeur_horizontal. 1 seule valeur pour les deux
            'mobile-gap' => '', // espace entre les blocs. valeur_verticale valeur_horizontal. 1 seule valeur pour les deux
            /* [st-items] alignement et répartition des items dans le conteneur */
            'place-content' => '', // justify-content align-content : start end center space-around space-between space-everly
            'tablet-place-content' => '', //grid-template-rows grid-template-columns : start end center space-around space-between space-everly
            'mobile-place-content' => '', // grid-template-rows grid-template-columns : start end center space-around space-between space-everly
            'place-items' => '', // align-items justify-items : start end center stretch
            'tablet-place-items' => '', //align-items justify-items : start end center stretch
            'mobile-place-items' => '', // align-items justify-items : start end center stretch
            /* [st-item] style des contenus */
            'items-style-*' => '', // class2style et style pour les items (12 maximum dans l'ordre normal ou de zone-name)
            'tablet-items-style-*' => '', // class2style et style pour les items sur tablet (12 maximum)
            'mobile-items-style-*' => '', // class2style et style pour les items sur mobile (12 maximum)
            /* [st-tablet] breakpoints - points de rupture */
            'tablet-bp' => '760px', // breakpoint pour tablette
            'mobile-bp' => '480px', // breakpoint pour mobile
            /* [st-main] Style pour le conteneur parent */
            'main-tag' => 'div', // ajoute un bloc DIV autour des contenus {===}
            'id' => '', // id du conteneur principal
            'main-style' => '', // classes et style inline pour container
            'main-mobile-style' => '', // class2style et style inline pour container sur mobile
            'main-tablet-style' => '', // class2style et style inline pour container sur tablet
            /* [st-divers] Divers */
            'css-head' => '' // class2style et style ajouté dans le HEAD de la page
        );
        // fusion et controle des options
        $this->options = $this->ctrl_options($options_def);

        // list des propriétés pour propertyNoEmpty
        $properties = array(
            'grid-template-columns',
            'grid-template-rows',
            'gap',
            'grid-auto-columns',
            'place-content',
            'place-items',
        );

        // === ANALYSE OPTIONS
        $this->ctrl_timer('début');
        // === LES NOMS DES ZONES
        // si pas défini par l'option 'zone-name', on analyse $grid_css
        $grid_css = '';
        if (!empty($this->options['grid'])) {
            $grid_css = $this->normalize_grid_template_areas($this->options['grid']);
            $zone_name = array();
            if (preg_match_all('/\"(.*)\"/U', $grid_css, $matches)) {
                $zone_name = array_map('trim', explode(' ', implode(' ', $matches[1])));
                $zone_name = array_unique($zone_name);
                foreach ($zone_name as $k => $v) {
                    // il ne faut pas prendre en compte les points. Doit commencer par une lettre
                    if ($v[0] == '.') {
                        unset($zone_name[$k]);
                    } elseif (is_numeric($v)) {
                        return $this->msg_inline('Nom de zone interdit :' . $v);
                    }
                }
                $zone_name = array_values($zone_name);
            }
        }
        $nb_zone = count($zone_name ?? []);
        // --- repeat grid
        if ($this->options['grid-repeat'] > 0) {
            $grid_css_orig = $grid_css;
            $zone_name_new = array();
            for ($i = 1; $i <= $this->options['grid-repeat'];$i++) {
                $grid_css_new =  $grid_css_orig;
                foreach ($zone_name as $name) {
                    $grid_css_new = str_replace($name, $name . '-' . $i, $grid_css_new);
                    $zone_name_new[] = $name . '-' . $i;
                }
                $grid_css .= $grid_css_new;
            }
            $zone_name = array_merge($zone_name, $zone_name_new);
        }
        // controle zone-name
        if (!empty($this->options['zone-name'])) {
            $zone_name_2 = array_map('trim', explode(',', $this->options['zone-name']));
            if (count($zone_name_2) < $nb_zone) {
                $zone_name = array_merge($zone_name_2, array_slice($zone_name, count($zone_name_2)));
            } else {
                $zone_name = $zone_name_2;
            }
            $diff = array_diff($zone_name_2, $zone_name);
            if (!empty($diff)) {
                $this->msg_error('Les noms de zone-name ne correspondent pas à ceux de grid');
                return false;
            }
            $zone_name = $zone_name_2;
        }


        // ====== CSS-HEAD
        // --- les styles pour desktop
        $css = '#id[display:grid;';
        $css .= ($grid_css) ? 'grid-template-areas:' . $grid_css . ';' : '';
        foreach ($properties as $property) {
            $css .= $this->propertyNoEmpty($property);
        }
        $css .= $this->propertyNoEmpty('grid-auto-rows');
        $css .= $this->propertyNoEmpty('grid-auto-columns');
        $css .= $this->propertyNoEmpty('grid-auto-flow');
        $css .= ']';

        $attr_all_items_class = array();
        $this->get_attr_style($attr_all_items_class, $this->options['items-style-*']);
        $attr_items = array(); // classes et styles pour item individuellement
        for ($i = 1; $i <= 12; $i++) {
            $this->get_attr_style($attr_items[$i], $this->options['items-style-' . $i]);
        }

        // --- les styles pour tablet et mobile
        foreach (
            array(
                'tablet-',
                'mobile-'
            ) as $bp
        ) {
            $mqcss = $this->normalize_grid_template_areas($this->options[$bp.'grid']);
            if (!empty($mqcss)) {
                $nbcol = $this->get_nb_col($mqcss);
                if (empty($this->options[$bp.'grid-template-columns'])) {
                    $this->options[$bp.'grid-template-columns'] = 'repeat(' . $nbcol . ',1fr)';
                }
                $mqcss = 'grid-template-areas:' . $mqcss . ';' ;
            }
            foreach ($properties as $property) {
                $mqcss .= $this->propertyNoEmpty($property, $bp);
            }
            if (!empty($mqcss)) {
                $mqcss = '#id[' . $mqcss . ']';
            }
            // --- les styles pour tablet et mobile
            if (!empty($this->options[$bp . 'items-style-*'])) {
                $mqcss .= '#id>*['.$this->options[$bp . 'items-style-*'].']';
            }

            for ($i = 1; $i <= 12; $i++) {
                if (!empty($this->options[$bp . 'items-style-'.$i])) {
                    $mqcss .= '#id>*:nth-child('.$i.')['.$this->options[$bp . 'items-style-'.$i].']';
                }

            }
            // --- ajout mediaqueries
            if (! empty($mqcss)) {
                $css .= '@media screen and (max-width:' . $this->options[$bp . 'bp'] . ')['. $mqcss . ']';
            }
        }


        $css .= $this->options['css-head'];
        $this->load_css_head($css);

        // ======== RECUPERATION & ANALYSE CONTENU
        // --- Suppression des shortcodes separateurs {====}
        // --- si bloc-tag, on ajoute balise DIV
        $this->content = $this->supertrim($this->content);
        if ($this->ctrl_content_parts($this->content) === true) {
            $allcoltxt = $this->get_content_parts($this->content);
            // mise en forme
            $this->content = '';
            $tag = $this->options['main-tag'];
            foreach ($allcoltxt as $coltxt) {
                if (empty($tag)) {
                    $this->content .= $coltxt;
                } else {
                    $this->content .= '<div>' . $coltxt . '</div>';
                }
            }
        }

        // --- ajout des classes de reperage aux blocs enfants
        // require_once($this->upPath . '/assets/lib/simple_html_dom.php');
        $this->ctrl_timer('avant simple_html_dom');
        $html = new simple_html_dom();
        $html->load('<html>' . $this->content . '</html>', true, false);
        $this->ctrl_timer('avant $html->find');
        $childs = $html->find('html>*');
        $item = 1;
        $this->ctrl_timer('avant foreach');
        foreach ($childs as $child) {
            $name = (empty($zone_name[$item - 1])) ? 'item-'.$item : $zone_name[$item - 1];
            $child->addClass($name);
            $child->addClass($attr_all_items_class["class"] ?? '');
            $child->addClass($attr_items[$item]["class"] ?? '');
            $style = '' ;
            if (!empty($zone_name[$item - 1])) {
                $style .= 'grid-area:'. $zone_name[$item - 1] .';';
            }
            if (!empty($attr_all_items_class['style'])) {
                $style .= $attr_all_items_class['style'] .';';
            }
            if (!empty($attr_items[$item]['style'])) {
                $style .= $attr_items[$item]['style'] . ';';
            }
            if ($style) {
                $child->setAttribute('style', $style);
            }
            $items[] = $child->outertext;
            $item++;
        }
        $this->ctrl_timer('après foreach');
        unset($html);
        $content = implode(PHP_EOL, $items);

        $this->ctrl_timer('après simple_html_dom');

        // === ATTRIBUTS BLOC PRINCPAL
        $attr_main = array();
        $attr_main['id'] = $this->options['id'];
        $this->get_attr_style($attr_main, $this->options['main-style']);

        $this->ctrl_timer('avant retour');

        // code en retour
        return $this->set_attr_tag($this->options['main-tag'], $attr_main, $content);
    }

    // run

    /**
     * Normalise le contenu de grid-template-areas
     *
     * @param string $grid
     * @return string
     */
    private function normalize_grid_template_areas($grid)
    {
        $grid = str_replace('\'', '"', $grid);
        $grid = $this->spaceNormalize($grid);
        $grid = preg_replace('/\s+/', ' ', $grid);
        return $grid;
    }

    private function propertyNoEmpty($option, $bp = '')
    {
        $str = (!empty($this->options[$bp.$option])) ? $option.':' . $this->options[$bp.$option] . ';' : '';
        return $str;
    }

    /**
    * retourne le nombre de colonnes d'une grille
    */
    private function get_nb_col($grid)
    {
        $nbSpace = -1;
        if (preg_match_all('/\"(.*)\"/U', $grid, $matches)) {
            foreach ($matches[1] as $match) {
                $nb = substr_count($match, ' ');
                if ($nbSpace == -1) {
                    $nbSpace = $nb;
                } elseif ($nb != $nbSpace) {
                    $this->msg_error('Le nombre de colonnes doit être identique pour tous les items');
                }
            }
        }
        return $nbSpace + 1;
    }
}

// class
