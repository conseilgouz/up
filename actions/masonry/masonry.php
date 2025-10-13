<?php

/**
 * Affiche des blocs dans une grille fluide et responsive
 *
 * syntaxe {up masonry=breakpoint:nbCols*margeX*margeY} bloc-1 bloc-2 bloc-n {/up masonry}
 *
 * l'argument principal est la liste du nombre de colonnes et des marges par breakpoint.
 * Exemple :
 * {up masonry=960:4,1200:5} 4 colonnes au-dessus de 960px, 5 si 1200px
 * {up masonry=960:4*10, 1200:5*10*20} idem plus marges XY de 10px pour 960 et marge X de 10px et Y de 20px pour 12000
 *
 * @author   LOMART
 * @version  UP-3.0
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="https://github.com/bigbite/macy.js" target"_blank">script macy.js de bigbite</a>
 * @tags    Layout-static
 *
 * */
/**
 * Note interne :
 * certaines options du script macy.js sont définies par UP
 * mobileFirst : forcé à true
 * columns : forcé à 1, le nombre de colonne sur mobile
 * margin : on conserve la valeur 0
 */
defined('_JEXEC') or die();

class masonry extends upAction
{
    /**
     * charger les ressources communes à toutes les instances de l'action
     */
    public function init()
    {
        $this->load_file('macy.js');
        return true;
    }

    /**
     * analyse et interprete le shortcode
     *
     * @return [string] [code HTML pour remplacer le shortcode]
     */
    public function run()
    {

        // si cette action a obligatoirement du contenu
        if (! $this->ctrl_content_exists()) {
            return false;
        }

        // lien vers la page de demo
        $this->set_demopage();

        // ===== valeur paramétres par défaut (sauf JS)
        $options_def = array(
            __class__ => '480:2,720:3,960:4,1200:5', // colonnes et marges par breakpoint : bp:col*x*y. ex: 960:2*10*10,480:1
            'margin' => 0, // marge en pixels. x*y pour des marges différentes en horizontal et vertical
            /*[st-divers] Options annexes*/
            'preserve-order' => 0, // 1: l'ordre des blocs est préservé. 0=priorité à l'égalité de la hauteur des colonnes
            'wait-images' => 0, // 1: charge toutes les images avant le calcul. 0: calcul à chaque chargement d'image.
            'breakpoints-container' => 0, // 0: breakpoints définis sur la largeur du navigateur. 1: largeur du bloc parent
            /*[st-css] mise en forme CSS*/
            'id' => '', // id genérée automatiquement par UP
            'class' => '', // classe(s) ajoutées au bloc principal
            'style' => '', // style inline ajouté au bloc principal
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // === CSS-HEAD
        $this->load_css_head($options['css-head']);

        // -- initialisation JS
        $js_code = 'var masonry = new Macy({';
        $js_code .= 'container:\'#' . $options['id'] . '\',';
        $js_code .= $this->get_break_at($options[__class__]);
        if ($options['margin']) {
            $js_code .= $this->get_margin($options['margin']).',';
        }
        if (! empty($options_user['breakpoints-container'])) {
            $js_code .= 'useContainerForBreakpoints:1,';
        }
        $js_code .= 'trueOrder:' . (int) $options['preserve-order'] . ',';
        $js_code .= 'waitForImages:' . (int) $options['wait-images'] . ',';
        $js_code .= 'mobileFirst:1,';
        $js_code .= 'columns:1,';
        $js_code .= '});';
        $js_code = $this->load_js_code($js_code, false);

        // === Contrôle contenu
        if ($this->ctrl_content_parts($this->content) !== false) {
            $allcoltxt = $this->get_content_parts($this->content);
            $this->content = '';
            foreach ($allcoltxt as $coltxt) {
                $this->content .= '<div>' . $coltxt . '</div>';
            }
        }

        // === le code HTML
        // -- ajout options utilisateur dans la div principale
        $attr_main['id'] = $options['id'];
        $this->get_attr_style($attr_main, $options['class'], $options['style']);

        // code en retour
        $html[] = $this->set_attr_tag('div', $attr_main);
        $html[] = $this->content;
        $html[] = '</div>';
        // ajout du script js
        $html[] = $js_code;

        return implode(PHP_EOL, $html);
    }

    // run
    public function get_break_at($arg)
    {
        if (empty($arg)) {
            return;
        }
        $bps = $this->strtoarray($arg, ',', ':', false);
        foreach ($bps as $bp => $val) {
            $val = array_map('trim', explode('*', $val, 2));
            if (count($val) == 1) {
                $bpout[] = (int) $bp . ':' . trim($val[0]);
            } else {
                $tmp = (int) $bp . ':{';
                $tmp .= 'columns:' . $this->supertrim($val[0]) . ',';
                $tmp .= $this->get_margin($val[1]);
                $tmp .= '}';
                $bpout[] = $tmp;
            }
        }
        return 'breakAt:{' . implode(',', $bpout) . '},';
    }

    public function get_margin($in)
    {
        list($x, $y) = explode('*', $in . '*' . $in, 2);
        return 'margin:{x:' . (int) $x . ',y:' . (int) $y . '}';
    }
}

// class
