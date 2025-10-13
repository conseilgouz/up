<?php

/**
 * Affiche une série d'images défilantes en fond du site ou d'un bloc
 *
 * syntaxe :
 * fond site : {up bg-slideshow=liste images ou dossier}
 * fond bloc : {up bg-slideshow=liste images ou dossier}contenu{/up bg-slideshow}
 * fond autre bloc : {up bg-slideshow=liste images ou dossier | bg-selector=#foo}
 *
 * doc : https://developer.mozilla.org/fr/docs/Web/CSS/background
 *
 * @author   LOMART
 * @version  UP-1.5
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit  <a href="https://github.com/jaysalvat/vegas" target="_blank">Vegas de jaysalvat</a>
 * @tags    Body
 */
// doc : http://vegas.jaysalvat.com/documentation/settings/

/*
 * 3.1 - modif CSS vegas pour J4/flexgrid
 * 5.1 - fix shuffle
 */

/*
 * PSEUDO-CODE
 * 1/ selon cible, $selector = #id, body ou bg-selector
 * 2/ si mobile : css mobile dans $selector (head)
 * 3/ bg-overlay (sauf si mobile) dans head
 * 4/ page-overlay dans head
 * 5/ recupération liste des images (sauf si mobile)
 * 6/ init JS (sauf si mobile)
 * 7/ Retour HTML (si content)
 * - div.attr_main
 * - ___ div attr_center
 * - ______ div > content
 */
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

class bg_slideshow extends upAction
{

    function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        $this->load_upcss();
        $this->load_file('vegas/vegas.min.css');
        $this->load_file('vegas/vegas.min.js');
        return true;
    }

    function run()
    {

        // ---- lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            /*images*/
            __class__ => '', // dossier ou images séparées par des points-virgules
            'mobile' => '', // image ou règle(s) css
            'path' => '', // chemin commun aux images indiquées en argument principal
            'shuffle' => 0, // tri aléatoire des images. 1=toutes, sinon le nombre d'images pour alléger le chargement
            /* [st-bloc]emplacement du fond image */
            'bg-selector' => 'body', // bloc portant le slideshow
            /* [st-bg]paramètres de l'image */
            'bg-overlay' => '', // calque superposé à l'image de fond (png, opacité, RGBA, CSS)
            'bg-color' => '', // couleur sous image
            /* [st-page]transparence du contenu de la page */
            'page-selector' => '', // bloc sur contenu
            'page-overlay' => '', // background de page-selector (png, opacité, RGBA, CSS)
            /* [st-annexe]options secondaires */
            'filter' => '', // conditions. Voir doc action filter (v1.8)
            'center' => 1, // centrage vertical du contenu entre shortcodes
            'id' => '', // identifiant
            'style' => '', // classes et styles inline pour bloc créé
            'css-head' => '', // règles CSS définies par le webmaster (ajout dans le head)
            'js-options' => '' // options non prévues par l'action
        );

        // ===== paramétres attendus par le script JS
        $js_options_def = array(
            /*[st-JS]paramétres de l'animation*/
            'delay' => 5000, // durée par image
            'transition' => 'fade', // random,blur,flash,negative,burn,slideLeft,slideRight,slideUp,slideDown,zoomIn,zoomOut,swirlLeft,swirlRight
            'transitionDuration' => 1000, // durée transition
            'animation' => '', // kenburns,kenburnsLeft,kenburnsUp,kenburnsDown,kenburnsUpLeft,kenburnsUpRight,kenburnsDownLeft,kenburnsDownRight,random
            'animationDuration' => 'auto', // durée des animations
            'timer' => 1 // affiche barre de progression
        );

        $transition_list = 'fade,random,blur,flash,negative,burn,slideLeft,slideRight,slideUp,slideDown,zoomIn,zoomOut,swirlLeft,swirlRight';
        $transition_list .= 'fade2,blur2,flash2,negative2,burn2,slideLeft2,slideRight2,slideUp2,slideDown2,zoomIn2,zoomOut2,swirlLeft2,swirlRight2';
        $animation_list = 'kenburns,kenburnsLeft,kenburnsUp,kenburnsDown,kenburnsUpLeft,kenburnsUpRight,kenburnsDownLeft,kenburnsDownRight,random';

        // --- fusion et controle des options
        $options = $this->ctrl_options($options_def, $js_options_def);

        // === Filtrage
        if ($this->filter_ok($options['filter']) !== true) {
            return '';
        }

        $options['transition'] = $this->ctrl_argument($options['transition'], $transition_list);
        if ($options['animation'] != null) {
            $options['animation'] = $this->ctrl_argument($options['animation'], $animation_list);
        }
        // === Init variables
        $on_mobile = false;

        // === css-head
        $this->load_css_head($options['css-head']);

        // ========== localisation du fond
        $selector = ($this->content) ? '#' . $options['id'] : $options['bg-selector'];

        // ===== Alternative si mobile
        if ($options['mobile'] > '1') {
            $client = Factory::getApplication()->client;
            if ($client->mobile) {
                $on_mobile = true;
                $css_mobile = $this->get_bg_mobile($options);
                $this->load_css_head($selector . '{' . $css_mobile . '}');
            }
        }

        // =========== overlay sur slideshow
        if ($options['bg-overlay'] && ! $on_mobile) {
            $this->add_str($options['js-options'], 'overlay:true', ',');
            $val = $this->get_overlay($options['bg-overlay']);
            $this->load_css_head($selector . ' .vegas-overlay{background:' . $val . '}');
        }
        // =========== overlay sur page
        if ($options['page-overlay'] && $options['page-selector']) {
            $val = $this->get_overlay($options['page-overlay']);
            $this->load_css_head($options['page-selector'] . '{background:' . $val . '}');
        }

        // ====== Récupération de la liste des images
        if (! $on_mobile) {
            $option_images = $options[__class__];

            if (is_dir($options['path'] . $option_images)) {
                $is_dir = true;
                $folder = $options['path'] . $option_images;
                $pattern = $folder . '/*.{jpg,JPG,jpeg,JPEG,png,PNG,webp,WEBP}'; // v2.5 pascal
                $imgList = glob($pattern, GLOB_BRACE); // | GLOB_NOSORT
            } else {
                $is_dir = false;
                $imgList = array_map('trim', explode(';', $option_images));
            }
            foreach ($imgList as $img) {
                $imgsrc[] = $this->get_slide_info($img, $is_dir, $options['path']);
            }
            if ($options['shuffle']) { // v5.1 forcé car non pris en charge par le JS
                shuffle($imgsrc);
                if ($options['shuffle'] > 1 && $options['shuffle'] < sizeof($imgsrc))
                    $imgsrc = array_slice($imgsrc, 0, $options['shuffle']);
            }
            $slides = implode(',', $imgsrc);
        }

        // =========== le code JS
        // les options saisies par l'utilisateur concernant le script JS
        // cela évite de toutes les renvoyer au script JS
        if (! $on_mobile) {
            $js_options = $this->only_using_options($js_options_def);
            // -- conversion en chaine Json
            $js_params = $this->json_arrtostr($js_options, 2, false);
            // -- initialisation
            $js_code = '$("' . $selector . '").vegas({';
            $js_code .= 'slides: [' . $slides . ']';
            $this->add_str($js_code, $js_params, ',');
            if ($options['js-options'])
                $this->add_str($js_code, $options['js-options'], ',');
            $js_code .= '});';
            $this->load_jquery_code($js_code);
        }

        // ====== si contenu, on crée un bloc à la position du shortcode
        if ($this->content) {
            // attributs du bloc principal
            $attr_main['id'] = $options['id'];
            if ($options['bg-color']) {
                $attr_main['style'] = 'background-color:' . $options['bg-color'];
            }
            // === center
            if ($options['center']) {
                $attr_center['class'] = 'up-center';
            }
            $this->get_attr_style($attr_center, $options['style']);

            // -- code retour
            $html[] = $this->set_attr_tag('div', $attr_main);
            $html[] = $this->set_attr_tag('div', $attr_center);
            $html[] = '<div>';
            $html[] = $this->content;
            $html[] = '</div>';
            $html[] = '</div>';
            $html[] = '</div>';

            return implode(PHP_EOL, $html);
        } else {
            // sinon, uniquement le JS et CSS
            return '';
        }
    }

    // run

    /*
     * get_slide_info : retourne la chaine pour l'argument slide de vegas
     * -------------------------------------------------
     * LE PRINCIPE.
     * les infos de cadrage permettent d'indiquer le point référence pour le recadrage
     * elles sont ajoutées entre crochets à la fin du nom
     * exemple maPhoto[100-100].jpg pour recadrer à partir du droit-bas (Right-Bottom)
     * 1ere valeur = position horizontale en pourcentage. 0=gauche, 100=droite
     * 2eme valeur = position verticale en pourcentage. 0=haut, 100=bas
     * 3eme valeur = mode recouvrement : repeat, contain ou cover
     * Cet ajout peut-être :
     * - dans le nom du fichier pour les images passées par dossier
     * - ajouté au nom du fichier dans l'option principale
     * -------------------------------------------------
     * $img : nom de l'image
     * $is_dir : TRUE si les infos de cadrage existe dans le nom du fichier
     * $path : chemin commun
     */
    function get_slide_info($img, $is_dir, $path)
    {
        // recherche options dans nom du fichier
        $regex = '#(.*)\[([\d]{0,3})\-?([\d]{0,3})\-?(.*)\]\.(.*)#i';
        if (preg_match($regex, $img, $result) == 1) {
            // $result[0] = $img
            // $result[1] = chemin et nom image (sans extension)
            // $result[2] = cadrage horizontal en %
            // $result[3] = cadrage vertical en %
            // $result[4] = mode size
            // $result[5] = extension (sans le point)
            if ($is_dir) {
                $out = '{src:"' . $this->get_url_relative($img) . '" ';
            } else {
                $out = '{src:"' . $this->get_url_relative($path . $result[1] . '.' . $result[5]) . '" ';
            }
            $this->add_str($out, $result[2], ',', 'align:"', '%"');
            $this->add_str($out, $result[3], ',', 'valign:"', '%"');
            $arg = strtolower($result[4]);
            switch ($arg) {
                case 'cover':
                    $this->add_str($out, 'cover:true', ',');
                    break;
                case 'contain':
                    $this->add_str($out, 'cover:false', ',');
                    break;
                case 'repeat':
                    $this->add_str($out, 'cover:"repeat"', ',');
                    break;
            }
            $out .= '}';
        } else {
            $out = '{src:"' . $this->get_url_relative($path . $img) . '"}';
        }

        return $out;
    }

    /*
     * get_overlay : retourne la valeur pour la propriété background d'un overley
     * si $val se termine par .png : image répétée
     * si $val est un nombre (70, 70%) : masque blanc transparent
     * si $val commence par # (#FF9999 70%) : masque coloré transparent
     * sinon $val est une règle CSS (linear-gradient ou radial-gradient)
     */
    function get_overlay($val)
    {
        if (strtolower(substr($val, strrpos($val, '.'))) == '.png') {
            // si fichier PNG
            if (dirname($val) == '.') {
                $val = $this->upPath . 'assets/overlay/' . $val;
                $val = str_replace('\\', '/', $val);
            }
            $val = 'url(\'' . Uri::root(true) . '/' . $val . '\') repeat';
        } else if ($val[0] == '#') {
            $rgba = $this->hex2rgba($val);
            $val = 'linear-gradient(' . $rgba . ' 0%,' . $rgba . ' 100%)';
        } else if ((float) $val > 0) {
            // si 70 ou 70% -> rgba(256,256,256,.7)
            $val = (float) $val;
            $val = $val / 100;
            $val = 'linear-gradient(rgba(240,240,240,' . $val . ') 0%,rgba(240,240,240,' . $val . ') 100%)';
        }
        // sinon, c'était une règle CSS
        return $val;
    }

    /*
     * hex2rgba : retourne une couleur au format #RRGGBBAA ou #RGBA au format rgba(r,g,b,a)
     * opacité à 1 par défaut
     */
    function hex2rgba($hex)
    {
        // on retire le #
        $hex = str_replace('#', '', $hex);
        // si #RGBA ou #RGB : on double en forcant à FF si besoin
        if (strlen($hex) <= 4) {
            $hex .= $hex . 'FFFF';
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2] . $hex[3] . $hex[3];
        }
        // si >4 et <8, on force à FF
        $hex = substr($hex . 'FFFF', 0, 8);
        // conversion en décimal
        $rgba = array_map('hexdec', str_split($hex, 2));
        // canal alpha sous forme coeff
        $rgba[3] = round($rgba[3] / 255, 1);
        // retour
        return 'rgba(' . implode(',', $rgba) . ')';
    }

    /*
     * retourne le CSS pour le background sur mobile
     * $opt_mobile peut contenir :
     * - rien : on n'affiche pas la video, mais le fond prévu (poster bg-color)
     * - une image
     * - des propriétés css pour background : url(image.jpg) repeat-y
     * - du css : background:...;color:...
     */
    function get_bg_mobile($options)
    {
        $opt_mobile = $options['mobile'];
        if ($opt_mobile == '1') {
            $out = '';
        } elseif (is_file($opt_mobile)) {
            // image existante
            list ($w, $h) = getimagesize($opt_mobile);
            if (($w + $h) < 200) {
                $out = 'background:url(\'' . $opt_mobile . '\') repeat ' . $options['bg-color'];
            } else {
                $out = 'background:url(\'' . $opt_mobile . '\') no-repeat ' . $options['bg-color'] . ' center/cover';
            }
        } else {
            $out = (substr($opt_mobile, 0, 11) == 'background:') ? '' : 'background:';
            $out .= $opt_mobile;
        }
        return $out;
    }
}

// class







