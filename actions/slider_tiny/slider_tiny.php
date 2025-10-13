<?php

/**
 * bandeau défilant d'images ou de blocs HTML
 *
 * Syntaxe 1 :
 * {up slider-tiny |items=2}
 * < div>...< /div>
 * < img src="...">
 * < a href="..">< img src="...">< /a>
 * {/up slider-owl}
 * Syntaxe 2 :
 * {up slider-tiny=dossier_images |items=2}
 *
 * @author  LOMART
 * @version UP-5.1
 * @license <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="https://ganlanyuan.github.io/tiny-slider/" target"_blank">script tiny-slider de ganlanyuan</a>
 * @tags    image
 *
 * */

/*
 * ==================== TODO LMZOOM
 * revoir les "zoom pour"
 * ajouter uniquement avec le suffix
 * ajouter sur les grandes images sauf si classe
 * legend "taille minima" -> cas 3
 *
 */
defined('_JEXEC') or die();

class slider_tiny extends upAction
{
    /**
     * charger les ressources communes à toutes les instances de l'action
     * cette fonction n'est lancée qu'une fois pour la première instance
     *
     * @return true
     */
    public function init()
    {
        $this->load_file('tiny-slider.css');
        $this->load_file('tiny-slider-min.js');
        // $this->load_file('https://cdnjs.cloudflare.com/ajax/libs/tiny-slider/2.9.4/min/tiny-slider.js');
        return true;
    }

    /**
     * analyse et interprete le shortcode
     *
     * @return [string] [code HTML pour remplacer le shortcode]
     */
    public function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        // ===== valeur paramétres par défaut (sauf JS)
        $options_def = array(
            __class__ => '', // chemin vers dossier ou rien
            /* [st-folder] Options pour dossier d'images */
            'image-extension' => 'jpg,JPG,jpeg,JPEG,png,PNG,webp,WEBP,svg,SVG', // etensions autorisées pour les images
            'legend' => 0, // Ajoute le nom humanisé du fichier comme légende
            'legend-template' => '', // modèle pour légende séparée par 3 tirets. ex: [span style=""]##1##[/span][span style=""]##after@##[/span]
            'sort-by' => 'name', // date, random
            'sort-order' => 'asc', // asc ou desc
            'maxi' => '', // nombre d'images retenues
            'image-style' => '', // classes/style appliqués aux images
            'zoom-suffix' => '', // si indiqué, seules les images avec ce suffixe sont utilisées. ! devant inverse la sélection
            /* [st-resp] Responsive */
            'responsive-breakpoints' => '0, 480, 960', // liste des largeurs d'écran en px pour les points de changement
            /* [st-btn] Boutons précédent-suivant */
            'btn-prev' => 'préc', // contenu du bouton précédent. BBcode accepté
            'btn-next' => 'suiv', // contenu du bouton suivant. BBcode accepté
            /* [st-style] style */
            'id' => '', // id genérée automatiquement par UP
            'slider-style' => '', // classe(s) ou styles pour le slider
            'item-style' => '', // classe(s) ou styles ajoutés à chaque items du slider
            'class' => '', // classe(s) ajoutées au bloc principal
            'style' => '', // style inline ajouté au bloc principal
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // ===== paramétres attendus par le script JS
        // important: valeurs par défaut entre guillemets pour les chaines
        // attention: le nom des options est transmis au script sous la forme indiqué ici.
        $js_options_def = array(
            /* [st-intro]  Les options avec un commentaire commençant par [RESP] sont responsives*/
            /* [st-js-layout] Nombre et présentation des items */
            'items' => '1', // [RESP] Nombre de diapositives affichées dans la fenêtre.
            'startIndex' => '0', // [RESP] l'index du 1er item affiché
            'slideBy' => '1', // [RESP] nombre d'items déplacés en un clic. page: egal à items
            'autoHeight' => 0, // [RESP] 1: La hauteur du conteneur change en fonction de la hauteur des items visibles
            'edgePadding' => '0', // [RESP] largeur visible des items joutant les items visualisés (en "px")
            'gutter' => '0', // [RESP] Espace entre les items (en "px").
            'center' => '0', // [RESP] Centre l'item actif dans la fenêtre.
            'rewind' => 0, // revient au début lorsque la fin est atteinte et vice-versa
            /* [st-js-responsive] responsive */
            'responsive' => '', // chaine json pour définir les options pour différentes largeurs de fenêtre
            /* [st-js-btns] navigation boutons */
            'controls' => '1', // [RESP] affiche les boutons précédent/suivant et active le déplacement au clavier
            'controlsPosition' => 'top', // position des boutons (top, bottom ou center)
            'arrowKeys' => '0', // [RESP] Permet d'utiliser les touches fléchées pour changer de diapositive.
            /* [st-js-dots] navigation dots */
            'nav' => 1, // [RESP] affiche la navigation par points et active toutes les fonctionnalités
            'navPosition' => 'top', // position des points (top, bottom ou center)
            'navAsThumbnails' => 0, // Si true, les points sont des vignettes toujours visibles même si plus d'un item est affiché dans la fenêtre.
            /* [st-js-autoplay] Autoplay */
            'autoplay' => '0', // [RESP] Active/désactive le changement automatique des items.
            'autoplayTimeout' => 5000, // [RESP] délai avant changement items (en "ms").
            'speed' => '300', // [RESP] Vitesse de l'animation de transition (en "ms").
            'autoplayDirection' => 'forward', // sens déplacement : forward ou backward
            'autoplayPosition' => 'top', // position du bouton marche/arrêt (top, bottom, center, none)
            'autoplayHoverPause' => '0', // [RESP] Arrêt lors survol de la souris.
            'autoplayText' => '&#x23F5;,&#x23F8;', // [RESP] Texte ou bbcode du bouton marche/arrêt
            'autoplayResetOnVisibility' => '1', // [RESP] arrêt déplacement tant que la page est invisible
            /* [st-js-animation] Animation */
            'mode' => 'carousel', // comportement de l'animation. carousel, glisse sur le côté, gallery: animations de fondu de toutes les diapositives en même temps.
            'animateIn' => 'tns-fadeIn', // classe animation entrée
            'animateOut' => 'tns-fadeOut', // classe animation sortie
            'animateDelay' => 0, // délai entre chaque animation de la galerie (en "ms").
            /* [st-js-mobile] Options spécifique pour les mobiles */
            'touch' => '1', // [RESP] Active la détection d'entrée pour les appareils tactiles.
            'mouseDrag' => '0' // [RESP] Change les diapositives en les faisant glisser.

        /*
         * ===== non repris - accessible par responsive
         * animateNormal
         * autoplayButton
         * autoplayButtonOutput
         * autoplayDirection
         * autoWidth [RESP]
         * container
         * controlsContainer
         * controlsText [RESP]
         * disable [RESP]
         * fixedWidth [RESP]
         * freezable
         * lazyload
         * lazyloadSelector
         * loop
         * navContainer
         * nested
         * nextButton
         * nonce
         * onInit
         * prevButton
         * preventActionWhenRunning
         * preventScrollOnTouch
         * swipeAngle
         * useLocalStorage
         * viewportMax
         */
        );

        // fusion et controle des options
        // attention pour $this->options_user, le nom de l'action est en minuscules
        $options = $this->ctrl_options($options_def, $js_options_def);

        // === controle options PHP
        $options['sort-by'] = $this->ctrl_argument($options['sort-by'], 'name,random,date');
        $options['sort-order'] = $this->ctrl_argument($options['sort-order'], 'asc,desc');
        $options['legend-template'] = $this->get_bbcode($options['legend-template']);

        // === controle options JS
        $options['navPosition'] = $this->ctrl_argument($options['navPosition'], 'top,bottom,center');
        $options['controlsPosition'] = $this->ctrl_argument($options['controlsPosition'], 'top,bottom,center');
        $options['autoplayPosition'] = $this->ctrl_argument($options['autoplayPosition'], 'top,bottom,center,none');
        $options['mode'] = $this->ctrl_argument($options['mode'], 'carousel,gallery');
        $options['autoplayDirection'] = $this->ctrl_argument($options['autoplayDirection'], 'forward,backward');

        if (isset($this->options_user['startindex'])) {
            $this->options_user['startindex']--;
        }

        // on force la valeur du script
        if (! empty($options['autoHeight'])) {
            $options['css-head'] .= '#id .tns-slider[display:inherit;]';
        }
        // =================================
        // Calcul des responsive-breakpoints
        // =================================

        $bp = array_map('trim', explode(',', $options['responsive-breakpoints']));
        if ($bp[0] != 0) {
            array_unshift($bp, 0);
        }
        $nbbp = str_repeat(',', count($bp) - 1);
        $aResp = array(); // [480] => "option-1":"val-1"
        // NON UTILISE / controlsText,autoplayText,
        $respOptionsList = array_map('trim', explode(',', 'items,edgePadding,controls,startIndex,slideBy,speed,autoHeight,fixedWidth,gutter,center,nav,autoplay,autoplayHoverPause,autoplayResetOnVisibility,autoplayTimeout,touch,mouseDrag,arrowKeys,disable'));
        foreach ($respOptionsList as $option) {
            if (isset($options[$option]) && strpos($options[$option], ',') !== false) {
                $tmp = array_map('trim', explode(',', $options[$option] . $nbbp));
                // pour actualiser JScode
                $this->js_actualise($option, $tmp[0], $options, $js_options_def);
                for ($i = 1; $i < count($bp); $i++) {
                    if ($tmp[$i]) {
                        $aResp[$bp[$i]][$option] = $tmp[$i];
                    }
                }
            }
        }
        // l'option responsive est prioritaire. On a supprimé les alternative des options
        if (empty($options['responsive'])) {
            $responsive = $this->json_arrtostr($aResp);
        } else {
            $responsive = $this->get_code(trim($options['responsive']), true);
            $this->options_user['responsive'] = '';
        }

        // === Compactage options JS
        $js_options = $this->only_using_options($js_options_def);

        // === CSS-HEAD
        if ($options['zoom-suffix']) {
            $options['css-head'] .= '#id a[cursor:zoom-in]';
        }
        $this->load_css_head($options['css-head']);

        // ========================================
        // === RECUPERATION CONTENU : IMAGE ou BLOC
        // ========================================
        $attr_item['class'] = 'item';
        $this->get_attr_style($attr_item, $options['item-style']);

        $images = array(); // code html à afficher
        $tag = 'div';
        if (! empty($options[__class__])) {
            // ==========> toutes les images d'un dossier
            $pattern = $options[__class__] . '/*.{' . $options['image-extension'] . '}';
            $images = glob($pattern, GLOB_BRACE);
            if (isset($this->options_user['debug'])) {
                $this->msg_info(json_encode($images));
            }
            // uniquement les images dont le nom se termine par zoom-suffix
            if (! empty($options['zoom-suffix'])) {
                $nb = count($images);
                for ($i = 0; $i < $nb; $i++) {
                    if ($options['zoom-suffix'][0] != '!' && stripos($images[$i], $options['zoom-suffix'] . '.') === false) {
                        unset($images[$i]);
                    }
                    if ($options['zoom-suffix'][0] == '!' && stripos($images[$i], substr($options['zoom-suffix'], 1) . '.') !== false) {
                        unset($images[$i]);
                    }
                }
            }
            // tri
            switch (strtolower($options['sort-by'])) {
                case 'random':
                    shuffle($images);
                    break;
                case 'date':
                    foreach ($images as $img) {
                        $imgDate[$img] = filemtime($img);
                    }
                    asort($imgDate);
                    $images = array_keys($imgDate);
                    break;
                default:
                    sort($images); // sinon ordre recherche extension
            }
            if (strtolower($options['sort-order']) == 'desc') {
                $images = array_reverse($images);
            }
            // maxi
            if (! empty($options['maxi'])) {
                $images = array_slice($images, 0, (int) $options['maxi']);
            }

            foreach ($images as $img) {
                $attr_image['src'] = $img;
                $attr_image['alt'] = $this->link_humanize(str_replace($options['zoom-suffix'] . '.', '.', $img));
                $thumbnails[] = $img;

                $str = $this->set_attr_tag('img', $attr_image);
                if ($options['legend']) {
                    $str = $str . '<figcaption>' . $this->legend_style($attr_image['alt'], $options['legend-template']) . '</figcaption>';
                }
                $items[] = $str;
            }
        } elseif ($this->ctrl_content_parts($this->content)) {
            // séparées par {====} : recup texte colonnes sans le tag P ajouté par éditeur
            $items = $this->get_content_parts($this->content);
        } else {
            // on prend les blocs de 1er niveau du contenu
            require_once($this->upPath . '/assets/lib/simple_html_dom.php');
            $html = new simple_html_dom();
            // $html->load($this->content);
            $html->load('<html>' . $this->content . '</html>');
            $childs = $html->find('html>*');
            foreach ($childs as $child) {
                $items[] = $child;
            }
            unset($html);
        }

        if (empty($items)) {
            return $this->msg_inline('No content for slider-tiny');
        }

        // ===========================
        // ORGANISATION DES BLOCS HTML
        // ===========================
        // .tns-bloc-outer : relative
        // -- .tns-bloc-top : grid
        // ---- .tns-bloc-nav : optionnel
        // ---- .tns-bloc-buttons : optionnel
        // ------ .tns-autoplay : optionnel
        // ------ .tns-controls : optionnel
        // -- .tns-bloc-center : grid
        // ---- .ID-slider
        // ---- .tns-bloc-nav : optionnel
        // ---- .tns-bloc-buttons : optionnel
        // ------ .tns-autoplay : optionnel
        // ------ .tns-controls : optionnel
        // -- .tns-bloc-bottom : grid
        // ---- .tns-bloc-nav : optionnel
        // ---- .tns-bloc-buttons : optionnel
        // ------ .tns-autoplay : optionnel
        // ------ .tns-controls : optionnel

        // le bloc externe a l'ID habituel pour usage par css-head
        $outer_div['id'] = $options['id'];
        $this->get_attr_style($outer_div, $options['class'], $options['style'], 'tns-bloc-outer');
        // pour le slider on ajoute '-slider'
        $slider_id = $options['id'] . '-slider';

        // --- BOUTON AUTOPLAY
        // -------------------
        if ($options['autoplay']) {
            // --- le texte
            $options['autoplayText'] = html_entity_decode(strtolower($options['autoplayText']));
            $btnAutoplayText = explode(',', $this->get_bbcode($options['autoplayText'] ?? ''));

            if (count($btnAutoplayText) != 2) {
                return $this->msg_inline('ERROR autoplayText=START,STOP');
            }
            $js_options['autoplayText'] = '[\'' . $btnAutoplayText[0] . '\',\'' . $btnAutoplayText[1] . '\']';

            // --- le code HTML du bouton autoplay
            $tns_autoplay_id = $options['id'] . '-autoplay';
            $js_options['autoplayButton'] = '#' . $tns_autoplay_id;
            // si position=none, on masque le bouton autoplay qui doit toujours exister
            $styleNone = '';
            if ($options['autoplayPosition'] == 'none') {
                $options['autoplayPosition'] = 'center';
                $styleNone = ' style="display:none"';
            }
            $htmlAutoplay = '<div class="tns-autoplay"><button id="' . $tns_autoplay_id . '"' . $styleNone . '></button></div>';
        }

        // BOUTONS PREV/NEXT (controls)
        // ----------------------------
        if ($options['controls']) {
            // --- la position des boutons
            $tns_controls_id = $options['id'] . '-controls';
            $js_options['controlsContainer'] = '#' . $tns_controls_id;
            $htmlControls = '<div class="tns-controls" id="' . $tns_controls_id . '">';
            // $htmlControls .= '<button data-controls="prev" tabindex="-1" aria-controls="' . $slider_id . '">' . $btnControlsText[0] . '</button>';
            // $htmlControls .= '<button data-controls="next" tabindex="-1" aria-controls="' . $slider_id . '">' . $btnControlsText[1] . '</button>';
            $htmlControls .= '<button data-controls="prev" tabindex="-1" aria-controls="' . $slider_id . '">' . $this->get_bbcode($options['btn-prev']) . '</button>';
            $htmlControls .= '<button data-controls="next" tabindex="-1" aria-controls="' . $slider_id . '">' . $this->get_bbcode($options['btn-next']) . '</button>';
            $htmlControls .= '</div>';
        }

        // NAVIGATION PERSO (dots)
        // -----------------------
        $navStyle = (empty($options['nav'])) ? ' style="display:none"' : '';
        if ($options['navAsThumbnails']) {
            $tns_nav_thumbnails_id = $options['id'] . '-thumbnails';
            $js_options['navContainer'] = '#' . $tns_nav_thumbnails_id;
            $htmlNav = '<div class="tns-bloc-nav-thumbnails" id="' . $tns_nav_thumbnails_id . '"' . $navStyle . '>';
            foreach ($thumbnails as $thumbnail) {
                $htmlNav .= '<img src="' . $thumbnail . '">';
            }
            $htmlNav .= '</div>';
            // forcer le param JS
            $customNavPosition = (isset($js_options['navPosition'])) ? $js_options['navPosition'] : $js_options_def['navPosition'];
        } else {
            $tns_nav_id = $options['id'] . '-dots';
            $js_options['navContainer'] = '#' . $tns_nav_id;
            $pages = ceil(count($items) / (int) $options['items']);
            $htmlNav = '<div class="tns-bloc-nav" aria-label="Carousel Pagination" id="' . $tns_nav_id . '"' . $navStyle . '>';
            for ($i = 0; $i < $pages; $i++) {
                $htmlNav .= '<button data-nav="' . $i . '" tabindex="-1" aria-controls="' . $slider_id . '" aria-label="Carousel Page ' . ($i + 1) . '" class="" style=""></button>';
            }
            $htmlNav .= '</div>';
        }

        // CODE HTML PERSO POUR CONTROLS, NAV et AUTOPLAY
        // -----------------------
        foreach (array(
            'top',
            'center',
            'bottom'
        ) as $pos) {
            $html[$pos] = array();
            // boutons controls & autoplay
            if ($options['autoplayPosition'] == $pos || $options['controlsPosition'] == $pos) {
                $html[$pos][] = '<div class="tns-bloc-buttons">';
            }
            if ($options['autoplayPosition'] == $pos && ! empty($htmlAutoplay)) {
                $html[$pos][] = $htmlAutoplay ?? '';
            }
            if ($options['controlsPosition'] == $pos && ! empty($htmlControls)) {
                $html[$pos][] = $htmlControls ?? '';
            }
            if ($options['autoplayPosition'] == $pos || $options['controlsPosition'] == $pos) {
                $html[$pos][] = '</div>';
            }
            // dots de navigation
            if ($options['navPosition'] == $pos && isset($htmlNav)) {
                if ($options['navPosition'] == 'bottom') {
                    array_unshift($html[$pos], $htmlNav);
                } else {
                    array_push($html[$pos], $htmlNav);
                }
            }
        }

        // ======================
        // ========= le code HTML
        // ======================

        // code en retour
        $out[] = $this->set_attr_tag('div', $outer_div, false);
        // ========== bloc top
        if (! empty($html['top'])) {
            $out[] = '<div class="tns-bloc-top">';
            $out[] = implode(PHP_EOL, $html['top']);
            $out[] = '</div> <!--bloc top-->';
        }
        // ========== bloc centre
        $out[] = '<div class="tns-bloc-center">';
        // le slider
        $attr_slider['id'] = $slider_id;
        $this->get_attr_style($attr_slider, $options['slider-style']);
        $out[] = $this->set_attr_tag('div', $attr_slider, false);
        foreach ($items as $item) {
            $out[] = $this->set_attr_tag($tag, $attr_item, $item);
        }
        $out[] = '</div> <!--slider-->';
        // les boutons
        if (! empty($html['center'])) {
            $out[] = implode(PHP_EOL, $html['center']);
        }
        $out[] = '</div> <!--bloc center-->';

        // ========== bloc bottom
        if (! empty($html['bottom'])) {
            $out[] = '<div class="tns-bottom-bloc">';
            $out[] = implode(PHP_EOL, $html['bottom']);
            $out[] = '</div><!--bloc bottom-->';
        }
        $out[] = '</div> <!--outer-->'; // outer_div

        // -- debug
        if (isset($this->options_user['debug'])) {
            $this->msg_info(nl2br(htmlspecialchars(implode(PHP_EOL, $out))));
        }

        // ======================
        // =========== le code JS
        // ======================
        $js_params = $this->json_arrtostr($js_options, 2, false);
        if (isset($responsive)) {
            $js_params .= ',"responsive":' . $responsive;
        }
        // -- initialisation
        $js_code = 'tns({container:"#' . $slider_id . '",';
        $js_code .= $js_params;
        $js_code .= '});';
        $js_code = $this->load_js_code($js_code, false);
        // force la valeurs booleennes
        $js_code = str_replace('"1"', 1, $js_code);
        $js_code = str_replace('"0"', 0, $js_code);
        $out[] = $js_code;
        // -- debug
        if (isset($this->options_user['debug'])) {
            $this->msg_info(str_replace(',', ', ', htmlspecialchars($js_code)));
        }

        return implode(PHP_EOL, $out);
    }

    // run

    /*
     * legend_style
     * mise en forme d'une légende
     * si pas de séparateur, on ajoute un espace pour respecter un BR eventuel
     */
    public function legend_style($legend, $template)
    {
        $sep = ' -- ';
        $tmp = explode($sep, $legend, 2);
        if (empty($template)) {
            $template = '##1## -- ##2##';
        }
        $legend = str_replace('##1##', $tmp[0], $template);
        $legend = str_replace('##2##', $tmp[1] ?? '&nbsp;', $legend);

        return $legend;
    }
}

// class
