<?php

/**
 * Slideshow responsive d'images avec légendes et liens
 *
 * syntaxe 1:
 * {up slideshow_billboard=chemin_sous_dossier}
 * syntaxe 2:
 * {up slideshow_billboard}
 * < img src="images/img.jpg" alt="légende"> // image avec légende dans alt
 * < a href="#">< img src="img.jpg">< /a> // image avec lien
 * {/up slideshow_billboard}
 *
 * @author   LOMART
 * @version  UP-1.1
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="http://www.jqueryscript.net/slideshow/Easy-jQuery-Responsive-Slideshow-Plugin-Billboard.html" target"_blank">Easy jQuery Responsive Slideshow - Billboard de Spalmer</a>
 * @tags layout-dynamic
 */
/*
 * v1.4 - fix test sur type contenu (dossier ou content)
 * v1.7 - ajout zoom-suffix pour compatibilité avec l'action modal
 * v2.6 - J4 fix jquery (merci Pascal)
 * v2.8 - prise en charge image avec extension en majuscule
 */
defined('_JEXEC') or die();

class slideshow_billboard extends upAction
{

    /**
     * charger les ressources communes à toutes les instances de l'action
     * cette fonction n'est lancée qu'une fois pour la première instance
     *
     * @return true
     */
    function init()
    {
        $this->load_file('jquery.billboard.css');
        $this->load_file('jquery.easing.min.js');
        $this->load_file('jquery.billboard.min.js');
        return true;
    }

    /**
     * analyse et interprete le shortcode
     *
     * @return [string] [code HTML pour remplacer le shortcode]
     */
    function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        // valeur paramétres par défaut (sauf JS)
        $options_def = array(
            __class__ => '', // si indiqué, toutes les images de ce dossier (ordre alpha)
            'label' => 1, // affiche la légende des images (attribut alt ou nom fichier humanisé).
            'zoom-suffix' => '', // si indiqué, seules les images avec ce suffixe sont utilisées
            /* [st-css] Style CSS */
            'id' => '', // id genérée automatiquement par UP
            'class' => '', // classe(s) ajoutées au bloc principal
            'style' => '', // style inline ajouté au bloc principal
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // ===== paramétres attendus par le script JS
        $js_options_def = array(
        /* [st-JS] paramètres Javascript pour configuration */
            'ease' => 'easeInOutExpo', // <a href="http://easings.net/fr" target="_blank">mode de transition</a>
            'speed' => 1000, // durée des transitions en millisecondes
            'duration' => 5000, // durée entre les changements d'images
            'autoplay' => 1, // démarrage automatique
            'loop' => 1, // diaporama en boucle si exécution automatique est vraie
            'transition' => 'left', // "fade", "up", "down", "left", "right"
            'navType' => 'list', // "controls", "list", "both" or "none"
            'styleNav' => 1, // applies default styles to nav
            'includeFooter' => 1, // afficher/masquer le pied de page (légende et navigation)
            'autosize' => 1, // hauteur diaporama fixe. calcul sur 1ère image
            'resize' => 0, // tente de détecter automatiquement la taille de chaque diapositive
            'stretch' => 1 // étire les images pour remplir le conteneur
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def, $js_options_def);

        // =========== le code JS
        // les options saisies par l'utilisateur concernant le script JS
        // cela évite de toutes les renvoyer au script JS
        $js_options = $this->only_using_options($js_options_def);

        // -- conversion en chaine Json
        // il existe 2 modes: mode1=normal, mode2=sans guillemets
        $js_params = $this->json_arrtostr($js_options, 2);

        // -- initialisation
        $js_code = '$("#' . $options['id'] . '").billboard(';
        $js_code .= $js_params;
        $js_code .= ');';
        $this->load_jquery_code($js_code);

        // === le code CSS
        if ($options['zoom-suffix'])
            $options['css-head'] .= '#id a[cursor:zoom-in]';
        $this->load_css_head($options['css-head']);

        // === le code HTML
        // -- ajout options utilisateur dans la div principale
        $outer_div['id'] = $options['id'];
        $outer_div['class'] = $options['class'];
        $outer_div['style'] = $options['style'];

        // === Récupération contenu : les images
        $images = array(); // code html à afficher
        if ($options[__class__] != '') {

            // ==========> toutes les images d'un dossier
            // $folder = JPATH_ROOT . '/' . $options[__class__] . '/'; // v2.7
            $folder = $options[__class__] . '/';
            $imglist = glob($folder . '*.{jpg,png,gif,JPG,PNG,GIF,webp,WEBP}', GLOB_BRACE);
            sort($imglist); // sinon ordre recherche extension
            foreach ($imglist as $img) {
                if ($options['zoom-suffix']) {
                    // uniquement les images dont le nom se termine par img-suffix
                    $imgname = str_ireplace($options['zoom-suffix'] . '.', '.', $img);
                    if ($img != $imgname) {
                        $img = str_replace(JPATH_ROOT . '/', '', $img); // chemin relatif
                        $images[] = '<img src="' . $img . '" alt="' . $this->link_humanize($imgname) . '">';
                    }
                } else {
                    $img = str_replace(JPATH_ROOT . '/', '', $img); // chemin relatif
                    $images[] = '<img src="' . $img . '" alt="' . $this->link_humanize($img) . '">';
                }
            }
        } else {
            // ==========> les images (avec liens) indiquées entre les shortcodes
            $regex = '#(?:<a .*>)?<img.*>(?:</a>)?#i';
            if (preg_match_all($regex, $this->content, $imglist)) {
                foreach ($imglist[0] as $img) {
                    preg_match('#(<a.*>)?(<img .*>)(</a>)?#iU', $img, $matches);
                    $tmp = $this->get_attr_tag($matches[2], 'alt');
                    if ($tmp['alt'] == '') {
                        $imgname = str_ireplace($options['zoom-suffix'] . '.', '.', $tmp['src']);
                        $tmp['alt'] = $this->link_humanize($imgname);
                    }
                    $matches[3] = ($matches[1]) ? '</a>' : ''; // lien ouvrant et fermant
                    $images[] = $matches[1] . $this->set_attr_tag('img', $tmp) . $matches[3];
                }
            }
        }

        // -- le code en retour
        $out = $this->set_attr_tag('div', $outer_div);
        $out .= '<ul>';
        foreach ($images as $img) {
            if ($options['label']) {
                $alt = $this->preg_string('#alt="(.*)"#i', $img);
                $out .= '<li title="' . $alt . '">';
            } else {
                $out .= '<li>';
            }
            $out .= $img;
            $out .= '</li>';
        }
        $out .= '</ul>';
        $out .= '</div>';

        return $out;
    }

    // run
}

// class




