<?php

/**
 * bandeau défilant d'images ou de blocs HTML
 *
 * {up slider-owl |items=2}
 * < div>...< /div>
 * < img src="...">
 * < a href="..">< img src="...">< /a>
 * {/up slider-owl}
 *
 * @author  LOMART
 * @version UP-1.0
 * @license <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit  <a href="http://www.jqueryscript.net/slider/Powerful-Customizable-jQuery-Carousel-Slider-OWL-Carousel.html" target"_blank">script OWL Carousel de OwlFonk</a>
 * @tags    image
 */
/*
 * v2.1 - fix pour items=1
 * v2.2 - fix navigationText
 * v2.9 - ajout option css-head
 * v3.1 - option max-height pour égaliser la hauteur des blocs
 */
defined('_JEXEC') or die;

class slider_owl extends upAction {

    /**
     * charger les ressources communes à toutes les instances de l'action
     * cette fonction n'est lancée qu'une fois pour la première instance
     * @return true
     */
    function init() {
        $this->load_file('owl.carousel.css');
        $this->load_file('owl.theme.css');
        $this->load_file('owl.carousel.min.js');
        if (file_exists($this->actionPath . 'custom.css')) {
            $this->load_file('custom.css');
        }
//        $this->load_file('up_fit_center.js'); v3.1

        $code = '.owl-item > div > *:first-child{margin-top:0} ';
        $code .= ' .owl-item > div > *:last-child{margin-bottom:0}';

        $this->load_css_head($code);
        return true;
    }

    /**
     * analyse et interprete le shortcode
     * @return [string] [code HTML pour remplacer le shortcode]
     */
    function run() {

        // si cette action a obligatoirement du contenu
        if (!$this->ctrl_content_exists()) {
            return false;
        }

        // lien vers la page de demo
        // - vide = page sur le site de UP
        // - URL complete = page disponible sur ce lien
        // - rien pour ne pas proposer d'aide
        $this->set_demopage();

        // ===== valeur paramétres par défaut (sauf JS)
        $options_def = array(
            __class__ => '', // aucun paramètre nécessaire
            'max-height' => 1, // égalise la hauteur de tous les blocs
            /* [st-css] Style CSS*/
            'id' => '', // id genérée automatiquement par UP
            'class' => '', // classe(s) ajoutées au bloc principal
            'style' => '', // style inline ajouté au bloc principal
            'css-head' => '', // style ajouté dans le HEAD de la page
        );

        // ===== paramétres attendus par le script JS
        // important: valeurs par défaut entre guillemets pour les chaines
        $js_options_def = array(
            /* [st-JS] JS : autoplay */
            'autoPlay' => 0, // 0 ou durée affichage image en millisecondes
            'slideSpeed' => 200, // vitesse de transition en millisecondes
            /* [st-JS] JS : pagination */
            'paginationSpeed' => 0, // durée changement en millisecondes
            'pagination' => true, // affiche pagination
            'paginationNumbers' => 0, //affiche numéros à l'intérieur des boutons de pagination
            /* [st-JS] JS : navigation */
            'goToFirst' => 1, // Retour sur premier élément si lecture automatique à la fin
            'goToFirstSpeed' => 1000, // vitesse de l'option goToFirst en millisecondes
            'navigation' => false, // affichage boutons "next" et "prev"
            'navigationText' => "prev,next", // boutons sans texte: "navigationText: false"
            /* [st-JS] JS : nombre d'images selon largeur écran */
            'responsive' => true, // adaptation sur petits ecrans 
            'items' => 0, // nombre maxi d'éléments affichés en même temps sur la plus grande largeur de navigateur
            'itemsDesktop' => [1199, 4], // cela vous permet de prédéfinir le nombre de diapositives visibles avec une largeur de navigateur particulière. Le format est [x, y] où x = largeur du navigateur et y = nombre de diapositives affichées. Par exemple, [1199,4] signifie que si (window <= 1199) {affiche 4 diapositives par page} Vous pouvez également utiliser "itemsDesktop: false" pour ignorer ces paramètres. Pour bien comprendre comment cela fonctionne, consultez ma démo personnalisée
            'itemsDesktopSmall' => [979, 3], // voir ci-dessus
            'itemsTablet' => [768, 2], // voir ci-dessus
            'itemsMobile' => [479, 1]  // voir ci-dessus
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def, $js_options_def);

        // =========== le code JS
        // les options saisies par l'utilisateur concernant le script JS
        // cela évite de toutes les renvoyer au script JS
        $js_options = $this->only_using_options($js_options_def);
        $js_options['navigationText'] = explode(',', $options['navigationText']);

        // pour égaliser la hauteur de tous les blocs - v3.1
        if ($options['max-height'])
            $this->load_file('up_fit_center.js');
        
        // -- conversion en chaine Json
        $js_params = $this->json_arrtostr($js_options, 3);
        // -- initialisation
        $js_code = '$("#' . $options['id'] . '").owlCarousel(';
        $js_code .= $js_params;
        $js_code .= ');';
        $this->load_jquery_code($js_code);
        
        // === css-head
        $this->load_css_head($options['css-head']);
        
        // === analyse structure HTML du content
        // ajout div pour égaliser hauteur, centrer en hauteur et supprimer marges enfants deb & fin
        if ($this->ctrl_content_parts($this->content)) {
            // séparées par {====} : recup texte colonnes sans le tag P ajouté par éditeur
            $allcoltxt = $this->get_content_parts($this->content);
            // mise en forme
            $this->content = '';

            foreach ($allcoltxt as $coltxt) {
                $this->content .= '<div>';
                $this->content .= $coltxt;
                $this->content .= '</div>';
            }
        }

        // === le code HTML
        // -- ajout options utilisateur dans la div principale
        $outer_div['id'] = $options['id'];
        $outer_div['class'] = 'owl-carousel owl-theme cell-row';
        $this->add_class($outer_div['class'], $options['class']);
        $outer_div['style'] = $options['style'];

        // -- le code en retour
        $out = $this->set_attr_tag('div', $outer_div);
        $out .= $this->content;  // le contenu entre les shortcodes ouvrant et fermant
        $out .= '</div>';

        return $out;
    }

// run
}

// class
