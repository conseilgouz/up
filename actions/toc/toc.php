<?php

/**
 * Sommaire automatique
 *
 * Création d'un sommaire à partir des titres de l'article
 *
 * syntaxe {up toc}
 *
 * @author   LOMART
 * @version  UP-1.7
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    https://www.jqueryscript.net/layout/Highly-Configurable-jQuery-Table-of-Contents-Plugin-TocJS.html
 * @tags     Editor
 */
/* * **************************************************************************** */
/*
 * v1.71 - ajout maxlen
 * v1.8 - si item tronqué par maxlen, texte complet dans tooltip title
 * v2.9 - utilisation $primary et $secondary dans SCSS
 */
defined('_JEXEC') or die;

class toc extends upAction {

    /**
     * charger les ressources communes à toutes les instances de l'action
     * cette fonction n'est lancée qu'une fois pour la première instance
     * @return true
     */
    function init() {
        $this->load_file('toc.css');
        $this->load_file('toc.js');
        $this->load_js_file_body('smooth-scroll.js');
        return true;
    }

    /**
     * analyse et interprete le shortcode
     * @return [string] [code HTML pour remplacer le shortcode]
     */
    function run() {

        // lien vers la page de demo
        $this->set_demopage();

        // ===== valeur paramétres par défaut (sauf JS)
        $options_def = array(
          __class__ => '', // inutilisé
          /* [st-css] Style CSS*/
          'id' => '', // id genérée automatiquement par UP
          'class' => '', // classe(s) ajoutées au bloc principal
          'style' => '', // style inline ajouté au bloc principal
          'css-head' => '' // style ajouté au head de la page
        );

        // ===== paramétres attendus par le script JS
        // important: valeurs par défaut entre guillemets pour les chaines
        // attention: le nom des options est transmis au script sous la forme indiqué ici.
        $js_options_def = array(
        /* [st-JS] JS: définir le contenu analysé */
          'content' => '[itemprop="articleBody"]', // bloc analysé pour le sommaire
          'selector' => 'h2,h3,h4,h5,h6', // liste des selecteurs utilisés pour le sommaire
          'exclude' => '.notoc', // liste sélecteur pour exclusion du sommaire
          /* [st-format] JS: Mise en forme du sommaire */
          'indexingFormats' => '', // format des index : number, 1, upperAlphabet, A, lowerAlphabet, a, upperRoman, I, lowerRoman, i, - (aucun)
          'maxlen' => '', // longueur maxi des titres du sommaire
          'heading' => '', // Titre du sommaire
          /* [st-div] JS: Divers pour experts */
          'elementClass' => 'uptoc', // class de la div navigation
          'rootUlClass' => 'toc-ul-root', // class pour le bloc contenant la liste
          'ulClass' => 'toc-ul', // class pour la liste
          'levelPrefixClass' => 'toc-level-', // (interne) préfixe des classes
        );

        // forcer les options JS obligatoires
//         foreach (array('content') as $key) {
//             if (empty($this->options_user[$key])) {
//                 $this->options_user[$key] = $js_options_def[$key];
//                 $js_options_def[$key] = ''; //raz
//             }
//         }

        // fusion et controle des options
        $options = $this->ctrl_options($options_def, $js_options_def);

        // =========== le code JS
        // les options saisies par l'utilisateur concernant le script JS
        // cela évite de toutes les renvoyer au script JS
        $js_options = $this->only_using_options($js_options_def);

        // -- conversion en chaine Json
        // il existe 2 modes: mode1=normal, mode2=sans guillemets
        $js_params = $this->json_arrtostr($js_options, 1);
        // -- initialisation
        $js_code = '$(\'#' . $options['id'] . '\').toc(';
        $js_code .= $js_params;
        $js_code .= ');';


        $this->load_jquery_code($js_code);

        // === CSS-HEAD
        $this->load_css_head($options['css-head']);

        // === le code HTML
        $attr['id'] = $options['id'];
        $attr['class'] = "uptoc";
        $this->get_attr_style($attr, $options['class'], $options['style']);
        $out = $this->set_attr_tag('div', $attr, true);
        return $out;
    }

// run
}

// class
