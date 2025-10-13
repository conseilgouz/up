<?php

/**
 * Affiche une ou plusieurs images dans une lightbox
 * avec adaptation de la taille image à celle de l'appareil et légendes
 *
 * syntaxe 1 {up image-gallery=chemin_image | alt=texte}
 * syntaxe 2 {up image-gallery=chemin_dossier}
 * syntaxe 3 {up image-gallery}contenu avec des images{/up image-gallery}
 *
 * @author   LOMART
 * @version  UP-1.4
 * @license   <a href="//www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit <a href="//ergec.github.io/jQuery-for-PhotoSwipe/" target="_blank">jqPhotoSwipe de Ergec</a> - <a href="//photoswipe.com/" target="_blank">photoswipe.com</a> -  <a href="//masonry.desandro.com" target="_blank">Masonry</a> -  <a href="https://vestride.github.io/Shuffle/" target="_blank">Shuffle.js</a>
 * @tags  image
 */

/*
 * v1.6 - correction texte description
 * v1.8 - ajout tri shuffle par Pascal
 * v1.9 - boutons shuffle responsives
 * v1.95 - création d'une galerie à partir d'images insérées entre les shortcodes (merci Marc)
 * - suppression automatique des images (srcset) obsolètes (merci Marc)
 * - ajout option shuffle-reverse pour inverser ordre des dossiers
 * v2.82 - test si dossier sans image
 * v2.9 - création des vignettes (srcset) dans le dossier tmp pour éviter la sauvegarde par Akeeba Backup
 * - ajout d'une fonction lazyload par Pascal Leconte
 * v3.0 - ajout option download-xxx pour proposer le téléchargement de l'image dans sa plus haute définition
 * - l'option random est utilisable pour toutes les images lues dans un dossier
 * - nouvelle version humanise et option legend-template
 * v3.1 - ajout option grid-ratio pour layout=grid
 * v5.2 - ajout option sort-by-date
 */
defined('_JEXEC') or die();

use Joomla\CMS\Factory;

class image_gallery extends upAction
{
    public function init()
    {

        // charger les ressources communes à toutes les instances de l'action
        $this->load_file('image_gallery.css');
        $this->load_file('lib/PhotoSwipe/photoswipe.css');
        $this->load_file('lib/PhotoSwipe/default-skin/default-skin.css');
        $this->load_file('lib/PhotoSwipe/photoswipe.min.js');
        $this->load_file('lib/PhotoSwipe/photoswipe-ui-default.min.js');
        $this->load_file('lib/jqPhotoSwipe.js');
        return true;
    }

    public function run()
    {
        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // fichier image, dossier pour galerie ou largeurs des images contenues
            'layout' => 'masonry-3-2-1', // layout pour galerie : grid-x-x-x, none-x-x-x, shuffle-x-x-x
            'grid-ratio' => '', // pour layout grid, rapport hauteur/largeur. 1:carré, 0.7:horizontal, 1.4:vertical
            'nb-img' => 0, // nombre de vignettes affiché pour la galerie d'un dossier, 0 = toutes
            'gallery' => 1, // affiche la galerie dans une lightbox. Saisie obligée pour faire une galerie des images du contenu
            'random' => 0, // tri aléatoire des images pour gallerie depuis un dossier
            'sort-desc' => 0, // tri alphanumérique descendant
            'sort-by-date' => 0, // tri par date de création
            /* [st-legend] configuration de la legende (type et style) */
            'legend' => '', // label pour image unique
            'legend-type' => 2, // 0:aucune, 1:hover-top, 2:hover-bottom, 3:sous l'image
            'legend-class' => '', // classe(s) pour la légende vignette
            'legend-style' => '', // style pour la légende vignette
            'legend-template' => '', // modèle pour légende séparée par 3 tirets. ex: [span style=""]##1##[/span][span style=""]##after@##[/span]
            /* [st-download] pour proposer le téléchargement haute-définition de l'image */
            'download-label' => '', // texte ajouté à la légende . Ex: lang[en=Download; fr=Télécharger] ou &#x1f4e5;
            'download-title' => '', // texte de la bulle d'aide
            'download-style' => '', // classe ou style pour le lien de téléchargement. Ex: color:#FFF;bg-white
            /* [st-img] style des images */
            'img-class' => '', // classe(s) pour bloc figure avec image et legende
            'img-style' => '', // style inline pour bloc avec image et legende
            /* [st-css] style du bloc principal */
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc principal
            'style' => '', // style inline pour bloc principal
            'css-head' => '', // style CSS inséré dans le HEAD
            /* [st-shuffle] options pour layout=masonry uniquement (recherche et classement par sous-dossiers) */
            'search' => 0, // afficher un champ de recherche
            'shuffle-reverse' => 0, // inverser l'ordre d'affichage des sous-dossiers
            /* [st-infinite] mode chargement progressif (layout=masonry uniquement) */
            'infinite-scroll' => 0, // 0 ou le nombre d'images affichées pour activer infinite scroll
            /* [st-rwd] gestion responsive. paramétres des images adaptées (srcset) */
            'img-sizes' => '', // largeur image en pourcentage de wmax-page en mode desktop, tablette et mobile. 100,100,100 par défaut
            'wmin-image' => 250, // largeur mini de l'image pour prise en charge
            'wmax-page' => 1200, // largeur maxi du conteneur contenu de la page
            'wmax-lightbox' => 1200, // largeur image dans lightbox
            'wmax-lightbox-mobile' => 768, // largeur image dans lightboox sur mobile ou 0 pour désactiver. Multiplié par pixel-ratio
            'pixel-ratio' => 0, // facteur DPR. ex: 0:sans, vide, 1 ou 2: 2, 3:3
            'quality' => 70, // taux compression jpeg
            'similarity' => 80, // taux minimal. Si la largeur entre 2 images alternatives est inférieure a 80%, la plus petite n'est pas cree
            'bp-tablet' => '768', // point de rupture tablette
            'bp-mobile' => '480', // point de rupture smartphone

            // === INTERNE
            // rwd-sizes : array des largeur images à créer pour vignette et lightbox
            // rwd-sizes-lightbox : array des largeur images à créer pour lightbox seule
            // wimg-desktop : largeur image pour desktop en pxhenri64
            // wimg-tablet : largeur image pour tablette en px
            // wimg-mobile : largeur image pour mobile en px

            /* [st-root] gestion du dossier de stockage des variantes de tailles pour les images (a mettre dans custom/prefset.ini) */
            'srcset-dir' => '', // par défaut, dans le dossier de l'image. 'tmp/subdir' = non sauvegardé par Akeeba-backup
            'srcset-raz' => 0 // 1 pour supprimer tous les sous-dossiers srcset du dossier passé comme option principale
        );

        // === fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // === suppression des dossiers srcset
        if ($options['srcset-raz']) {
            $this->srcset_raz($options[__class__]);
            return '<div class="box-info">' . $this->trad_keyword('msg-srcset-raz', $options[__class__]) . '</div>';
        }

        // === le dossier racine pour srcset
        $this->srcset_path = ($options['srcset-dir']) ? trim($options['srcset-dir'], ' /\\') . '/' : '';

        // === Init option infinite en mode layout masonry
        // si nb-img indiqué, infinite-scroll est impossible
        $options['infinite-scroll'] = ($options['nb-img'] > 0) ? 0 : $options['infinite-scroll'];
        $options['infinite-start'] = 0;
        if ($options['infinite-scroll'] > 0) { // infinite scroll : check next page
            $app = Factory::getApplication();
            $this->infinite = $app->getInput()->getInt('infinite');
            if ($this->infinite) {
                $options['infinite-start'] = $this->infinite * $options['infinite-scroll'];
            }
        }

        // CSS dans le head
        if ($options['css-head']) {
            $this->load_css_head($options['css-head']);
        }

        // =========================================
        // === Controle et mise en forme des options
        // =========================================
        // --- style de la legende

        $options['legend-type'] = $this->ctrl_argument($options['legend-type'], '2,0,1,3');
        $main_legend_classes = array(
            '',
            'legend-hover legend-top',
            'legend-hover legend-bottom',
            'legend'
        );
        $options['main-legend-class'] = $main_legend_classes[$options['legend-type']];

        // === options diverses
        $options['similarity'] = $options['similarity'] / 100;
        if ($options['legend-template']) {
            $options['legend-template'] = str_replace('"', '\'', $this->get_bbcode($options['legend-template']));
        }

        // =============================================
        // === largeur image lightbox en fonction device
        // =============================================

        $options['pixel-ratio'] = intval($options['pixel-ratio']);
        $client = Factory::getApplication()->client;
        if ($client->mobile && $options['wmax-lightbox-mobile'] != 0) {
            $options['wimg-lightbox'] = $options['wmax-lightbox-mobile'] * $options['pixel-ratio'];
        } else {
            $options['wimg-lightbox'] = $options['wmax-lightbox'];
        }

        // =============================================
        // === attributs du bloc principal
        // =============================================

        $attr_main['id'] = $options['id'];
        $attr_main['class'] = $options['class'];
        $this->add_class($attr_main['class'], $options['main-legend-class']);
        $attr_main['style'] = $options['style'];

        // =============================================
        // ==== Mise en forme contenu
        // =============================================

        if ($this->content == '') {
            // une image ou un dossier passe comme parametre principal
            if (is_dir($options[__class__])) {
                $grid = explode('-', $options['layout']);
                $shuffle = ($grid[0] == 'shuffle');
                if ($shuffle) { // affichage en mode shuffle
                    $this->get_folder($options);
                } else { // affichage d'un repertoire
                    $this->make_folder($options);
                }
            } else {
                $this->make_imgfile($options);
            }
        } else {
            if (isset($this->options_user['gallery'])) {
                // les photos à utiliser pour une galerie
                $this->make_content_gallery($options);
            } else {
                // le contenu entre shortcodes
                $this->make_content($options);
            }
        }

        // =============================================
        // ==== code en retour
        // =============================================

        $html[] = $this->set_attr_tag('div', $attr_main);
        $html[] = $this->content;
        $html[] = '</div>';

        // charger juste avant </body> pour 1er appel de l'action dans la page
        $js[] = '$(".' . $options['id'] . '").jqPhotoSwipe({';
        $js[] = 'galleryOpen: function (gallery) {';
        $js[] = '}';
        $js[] = '});';
        $after[] = $this->load_jquery_code(implode(PHP_EOL, $js), false);

        // on retourne
        $out['after'] = implode(PHP_EOL, $after);
        $out['tag'] = implode(PHP_EOL, $html);

        if (isset($this->options_user['debug'])) { // v2.7
            $debug = implode('#br#', $out);
            $debug = str_replace('<', '&lt;', $debug);
            $debug = str_replace('#br#', '<br>', $debug);
            $this->msg_info($options['id'] . ' : <pre>' . $debug . '</pre>');
        }

        return $out;
    }

    // run

    /*
     * traitement pour shortcode avec image unique
     */
    public function make_imgfile($options)
    {
        if (file_exists($options[__class__])) {
            // largeur de l'image dans la page
            $grid = $this->img_get_sizes($options, '100');
            // attribut image
            $img['src'] = $options[__class__];
            $img['alt'] = $this->img_get_legend($img, $options['legend']);
            $img['class'] = '';
            $this->content = $this->img_get_code($img, $options);
        } elseif (isset($this->options_user['debug'])) { // v2.7
            $this->msg_error('file not found : ' . $options[__class__]);
        }
    }

    /*
     * traitement pour shortcode avec un dossier.
     * creation d'une galerie flex ou masonry
     */
    public function make_folder($options)
    {
        $folder = trim($options[__class__], ' /\\');
        $pattern = $folder . '/*.{jpg,JPG,jpeg,JPEG,png,PNG,webp,WEBP}'; // v2.5 pascal
        $imgList = glob($pattern, GLOB_BRACE); // | GLOB_NOSORT
        // tri des images : alphabetique ordre naturel insensible à la case
        if ($options['random']) {
            shuffle($imgList);
        } elseif ($options['sort-by-date']) {
            foreach ($imgList as $file) {
                $d = filemtime($file);
                $fileSort[$file] = date('YmdHi', filemtime($file));
            }
            if ($options['sort-desc']) {
                arsort($fileSort);
            } else {
                asort($fileSort);
            }
            $imgList = array_keys($fileSort);
        } else {
            if ($options['sort-desc']) { // v31
                $tmp = $imgList;
                natcasesort($tmp);
                $imgList = array_reverse($tmp);
            } else {
                natcasesort($imgList);
            }
        }

        // recuperer les legendes
        $legends = $this->load_inifile($this->str_append($folder, 'legend.ini', DIRECTORY_SEPARATOR), false, false);

        // creation tableau attributs images
        $i = 0;

        $cpt = ($options['infinite-scroll'] > 0) ? $options['infinite-scroll'] : 999999;

        $images = array(); // v2.8.2

        foreach ($imgList as $img) {
            if ($options['infinite-start'] > 0) {
                $options['infinite-start']--;
                continue;
            }
            $images[$i]['src'] = $img;
            $images[$i]['class'] = '';
            $images[$i]['style'] = '';
            $imgname = basename($img);
            if (isset($legends[$imgname])) {
                $images[$i]['alt'] = $legends[$imgname];
            } elseif ($options['legend-template'] && strpos($imgname, '---') > 0) {
                $legend = $this->legend_style($this->link_humanize($imgname), $options['legend-template']);
                $images[$i]['alt'] = $legend;
            } else {
                $images[$i]['alt'] = $this->link_humanize($imgname);
            }
            $i++;
            if ($cpt && $i == $cpt) {
                break;
            }
        }

        // ==== mise en forme (TODO : par include)
        // pour img_get_code attribut sizes
        $out = array();
        $out = $this->layouts($out, $options, $images);
        $this->content = implode(PHP_EOL, $out);
    }

    /*
     * traitement pour le contenu entre shortcodes.
     */
    public function make_content($options)
    {

        // les largeurs d'images du contenu peuvent etre dans l'option principale ou img-sizes
        if ($options['image_gallery'] != 1) {
            $options['img-sizes'] = $options['image_gallery'];
        }
        $this->img_get_sizes($options, '100');

        // recuperation des images dans contenu
        $regeximg = '#<img .*>#U';
        preg_match_all($regeximg, $this->content, $images);
        foreach ($images[0] as $img) {
            $imgattr = $this->get_attr_tag($img);
            $imgattr['alt'] = $this->img_get_legend($imgattr);
            $imgCode = $this->img_get_code($imgattr, $options);

            if ($imgCode != false) {
                $this->content = str_replace($img, $imgCode, $this->content);
            }
        }
    }

    /*
     * le contenu contient les photos dans l'ordre d'affichage pour une galerie
     */
    public function make_content_gallery($options)
    {
        // recuperation des images dans contenu
        $regeximg = '#<img .*>#U';
        preg_match_all($regeximg, $this->content, $matches);
        foreach ($matches[0] as $i => $img) {
            $imgattr = $this->get_attr_tag($img);
            $images[$i]['class'] = '';
            $images[$i]['src'] = $imgattr['src'];
            $images[$i]['alt'] = $this->img_get_legend($imgattr);
        }
        // ==== mise en forme (TODO : par include)
        // pour img_get_code attribut sizes
        $out = array();
        if (! isset($images)) { // 3.0 Marc
            $images = array();
        }
        $out = $this->layouts($out, $options, $images);
        $this->content = implode(PHP_EOL, $out);
    }

    /*
     * affichage des images depuis les sous-repertoires avec shuffle
     */
    public function get_folder($options)
    {
        $maindir = $options[__class__];
        $this->load_file('lib/shuffle/shuffle.min.js');
        $this->load_file('lib/shuffle/shuffle-init.js');
        $imgList = array();
        $legends = array();
        $directories = glob($maindir . '/*', GLOB_ONLYDIR);
        if (empty($directories)) {  // v5.1
            $this->msg_error('no subfolders in "'. $maindir.'"');
            return false;
        }
        if ($options['shuffle-reverse'] != '') { // reverse order des boutons
            rsort($directories);
        }
        foreach ($directories as $directory) {
            $folder = $directory;
            $split = explode('/', $folder);
            $dirname = $split[count($split) - 1];
            if ($dirname == 'srcset') {
                continue;
            } // ignorer les repertoires de miniatures
            $pattern = $folder . '/*.{jpg,JPG,jpeg,JPEG,png,PNG,webp,WEBP}';
            $imgList[$dirname] = glob($pattern, GLOB_BRACE); // | GLOB_NOSORT
            // tri des images : alphabetique ordre naturel insensible à la case
            if (is_array($imgList)) {
                foreach ($imgList as $key => $val) {
                    natcasesort($val);
                }
            }
            // recuperer les legendes
            $legend = $this->str_append($folder, 'legend.ini', DIRECTORY_SEPARATOR);
            if (file_exists($legend)) {
                $legends[$dirname] = $this->load_inifile($legend);
            }
        }
        // creation tableau attributs images
        $i = 0;
        $out[] = '<div class="fg-row">'; // ajout flex pour search et les boutons
        if (! empty($options['search'])) { // add search shuffle - v32
            $out[] = '<input class="textfield filter__search js-shuffle-search fg-c2 fg-cs12 mb1" type="search" id="filters-search-input" placeholder="Recherche" />';
            $flex = 'fg-c10 fg-cs12'; // pour les boutons
        } else {
            $flex = 'fg-c12 fg-cs12'; // pour les boutons
        }

        $random = "";
        if ($options['random'] != '') { // random order
            $random = " data-random='true'";
        }
        $out[] = '<div class="btn-group filter-options display-block pl1 ' . $flex . '" ' . $random . '><button class="btn active" data-group="all">TOUT</button>';
        foreach ($imgList as $key => $dir) {
            $out[] = '<button class="btn" data-group="' . $key . '" style="margin:2px auto">' . $this->link_humanize($key) . '</button>';
            foreach ($dir as $img) {
                if ($options['infinite-start'] > 0) {
                    $options['infinite-start']--;
                    continue;
                }
                $images[$i]['src'] = $img;
                $images[$i]['class'] = '';
                $images[$i]['style'] = '';
                $images[$i]['data-groups'] = '[' . $key . ']';
                $imgname = basename($img);
                $images[$i]['data-name'] = $imgname;
                if (isset($legends[$key][$imgname])) {
                    $images[$i]['alt'] = $legends[$key][$imgname];
                } else {
                    $images[$i]['alt'] = $this->link_humanize($imgname);
                }
                $i++;
            }
        }
        $out[] = '</div></div>';
        // ==== mise en forme (TODO : par include)
        // pour img_get_code attribut sizes
        $out = $this->layouts($out, $options, $images);
        $this->content = implode(PHP_EOL, $out);
    }

    /*
     * gestion de la mise en forme de l'affichage (layout)
     */
    public function layouts($out, $options, $images)
    {
        $grid = explode('-', $options['layout']);
        for ($i = 1; $i <= 3; $i++) {
            if (! isset($grid[$i])) {
                $grid[$i] = 4 - $i;
            }
            if (is_numeric($grid[$i])) { // 3.0 Marc
                $wcol[] = floor(100 / $grid[$i]);
            }
        }
        $options['img-sizes'] = implode('-', $wcol);

        $this->img_get_sizes($options, "100");
        // mise en forme selon type galerie
        switch ($grid[0]) {
            case 'masonry':
                // -- js
                $this->load_file('lib/masonry/masonry.pkgd.min.js');
                if ($options['infinite-scroll'] > 0) {
                    $this->load_file('lib/masonry/infinite-scroll.min.js'); // imagesloaded is in infinite-scroll.js
                } else {
                    $this->load_file('lib/masonry/imagesloaded.pkgd.min.js');
                }
                $this->load_file('lib/masonry/masonry-init.js');
                // -- css
                $select = '#' . $options['id'] . ' .masonry-grid-sizer,' . '#' . $options['id'] . ' figure';
                $css = $select . '{width:' . (floor(100 / $grid[1])) . '%}';
                $css .= '@media (max-width:768px){' . $select . '{width:' . (floor(100 / $grid[2])) . '%}}';
                $css .= '@media (max-width:480px){' . $select . '{width:' . (floor(100 / $grid[3])) . '%}}';

                $this->load_css_head($css);

                // -- contenu
                $out[] = '<div class="masonry-grid">';
                $out[] = '<div class="masonry-grid-sizer"></div>';
                $cpt = ($options['nb-img'] < 1) ? sizeof($images) : $options['nb-img'];
                foreach (($images ?? []) as $img) {
                    $out[] = $this->img_get_code($img, $options, ($cpt > 0));
                    $cpt--;
                }
                $out[] = '</div>';
                if ($options['infinite-scroll'] > 0) { // add wait
                    $out[] = '<div class="page-load-status"><div class="loader-ellips infinite-scroll-request">
                              <span class="loader-ellips__dot"></span><span class="loader-ellips__dot"></span><span class="loader-ellips__dot"></span><span class="loader-ellips__dot"></span>
                               </div><p class="infinite-scroll-last">' . $this->trad_keyword('end-of-content') . '</p><p class="infinite-scroll-error">' . $this->trad_keyword('no-more-page') . '</p></div>';
                }
                break;
            case 'shuffle':
                // -- css
                $select = '#' . $options['id'] . ' .shuffle-grid-sizer,' . '#' . $options['id'] . ' figure';
                $css = $select . '{width:' . (floor(100 / $grid[1])) . '%}';
                $css .= '@media (max-width:768px){' . $select . '{width:' . (floor(100 / $grid[2])) . '%}}';
                $css .= '@media (max-width:480px){' . $select . '{width:' . (floor(100 / $grid[3])) . '%}}';

                $this->load_css_head($css);

                // -- contenu
                $out[] = '<div class="shuffle-grid">';
                $out[] = '<div class="shuffle-grid-sizer"></div>';
                $cpt = ($options['nb-img'] < 1) ? sizeof($images) : $options['nb-img'];
                foreach (($images ?? []) as $img) {
                    $out[] = $this->img_get_code($img, $options, ($cpt > 0));
                    $cpt--;
                }
                $out[] = '</div>';
                break;

            case 'none':
                $cpt = ($options['nb-img'] < 1) ? sizeof($images) : $options['nb-img'];
                foreach (($images ?? []) as $img) {
                    $out[] = $this->img_get_code($img, $options, ($cpt > 0));
                    $cpt--;
                }
                break;

            default: // grid
                if ($options['grid-ratio']) {
                    $css = '#id figure.upgallery img[object-fit:cover;]';
                    $this->load_css_head($css);
                    $this->load_file('lib/upgrid.js');
                    $js_code = 'upgrid("#' . $options['id'] . '", ' . $options['grid-ratio'] . ')';
                    $this->load_js_code($js_code);
                }
                $out[] = '<div class="fg-row fg-auto-' . $grid[1] . ' fg-auto-m' . $grid[2] . ' fg-auto-s' . $grid[3] . ' fg-gap">';
                $cpt = ($options['nb-img'] < 1) ? sizeof($images) : $options['nb-img'];
                foreach (($images ?? []) as $img) {
                    $out[] = $this->img_get_code($img, $options, ($cpt > 0));
                    $cpt--;
                }
                $out[] = '</div>';
        }

        return $out;
    }

    /*
     * retourne le code pour une image ou FALSE si non prise en charge
     * l'image doit etre sur le serveur avec un chemin relatif a la racine du site
     */
    public function img_get_code($img_attr, $options, $thumb = true)
    {

        // charge image et proprietes
        if (! file_exists($img_attr['src']) || ! (list($w, $h, $type) = getimagesize(JPATH_ROOT . '/' . $img_attr['src']))) {
            // ce n'est pas une image ou chemin incorrect
            if (isset($this->options_user['debug'])) { // v2.7
                $this->msg_error($this->trad_keyword('UP_FIC_NOT_FOUND', $img_attr['src']));
            }

            return false;
        }

        // === pas de prise en charge si class 'nogallery' ou image trop petite
        if (strpos($img_attr['class'], 'nogallery') !== false || $w < $options['wmin-image']) {
            if (isset($this->options_user['debug'])) { // v2.7
                $this->msg_error($this->trad_keyword('image-too-small', $img_attr['src'], $w, $options['wmin-image']));
            }

            return false;
        }

        // === OK, creation variables de travail
        $imgPath = $this->srcset_path . dirname($img_attr['src']) . '/'; // forme: dir/
        $imgName = pathinfo($img_attr['src'], PATHINFO_FILENAME); // forme: nom
        $imgExt = '.' . pathinfo($img_attr['src'], PATHINFO_EXTENSION); // forme: .ext
        // === si besoin, creation dossier pour images reduites
        $this->create_subdir($imgPath, 'srcset');
        // datetime de l'image
        $img_datetime = (file_exists($img_attr['src'])) ? filemtime($img_attr['src']) : PHP_INT_MAX;

        // === verif et creation images reduites
        $list_sizes = ($thumb) ? $options['rwd-sizes'] : $options['rwd-sizes-lightbox'];

        foreach ($list_sizes as $size) {
            $newPath = $imgPath . 'srcset/' . $imgName . '-' . $size . $imgExt;

            // si image plus récente que srcset, on supprime le srcset pour le recréer
            if (file_exists($newPath)) {
                if ($img_datetime > filemtime($newPath)) { // v2.9 evite de recreer lors recup akeeba (merci lab47)
                    unlink($newPath);
                }
            }

            // creation image si inferieure a original
            if (! file_exists($newPath)) {
                if ($size > $w) {
                    // création image taille maxi si inexistante
                    $size = $w;
                    $newPath = $imgPath . 'srcset/' . $imgName . '-' . $size . $imgExt;
                    if (! file_exists($newPath)) {
                        $this->img_resize($img_attr['src'], $newPath, $type, $w, $h, $size, round($size * ($h / $w), $options['quality']));
                    }
                } else {
                    $this->img_resize($img_attr['src'], $newPath, $type, $w, $h, $size, round($size * ($h / $w), $options['quality']));
                }
            }

            // si image existe, ajout au tableau des largeurs disponibles
            if (file_exists($newPath)) {
                $sizes[] = $size;
            }
        }

        // avant nettoyage pour img_attr alt
        $legend = $img_attr['alt'];
        $grid = explode('-', $options['layout']);
        $shuffle = ($grid[0] == 'shuffle');

        // === FIGURE = style pour image
        if ($shuffle) {
            $figure_attr['class'] = 'upgallery picture-item';
            $figure_attr['data-groups'] = $img_attr['data-groups'];
        } else {
            $figure_attr['class'] = 'upgallery';
        }
        /* */
        $this->add_class($figure_attr['class'], $options['img-class']);
        $figure_attr['style'] = $options['img-style'];
        /* */
        // === FIGCAPTION = style pour legende
        $figcaption_attr['class'] = $options['legend-class'];
        $figcaption_attr['style'] = $options['legend-style'];

        // === A = lien pour lightbox
        $val = $this->img_get_width($options['wimg-lightbox'], $sizes);
        $a_attr['href'] = $imgPath . 'srcset/' . $imgName . '-' . $val . $imgExt;
        // id permet fusion dans galerie unique
        $a_attr['class'] = $this->options_user['id'];
        // le titre pour la lightbox
        $a_attr['title'] = $img_attr['alt'];
        // cacher la bulle title au survol
        $a_attr['onmouseover'] = "this.title =''";

        // SOURCE = images alternatives pour breakpoints

        $val = $this->img_get_width($options['wimg-mobile'], $sizes);
        $src_mobile_attr['media'] = '(max-width:' . $options['bp-mobile'] . 'px)';
        $src_mobile_attr['srcset'] = $imgPath . 'srcset/' . $imgName . '-' . $val . $imgExt;

        if ($options['pixel-ratio']) {
            $val = $this->img_get_width($options['wimg-mobile'] * $options['pixel-ratio'], $sizes);
            $src_mobile_attr['srcset'] .= ', '; // virgule + espace = important
            $src_mobile_attr['srcset'] .= $imgPath . 'srcset/' . $imgName . '-' . $val . $imgExt;
            $src_mobile_attr['srcset'] .= ' ' . $options['pixel-ratio'] . 'x';
        }

        $val = $this->img_get_width($options['wimg-tablet'], $sizes);
        $src_tablet_attr['media'] = '(max-width:' . $options['bp-tablet'] . 'px)';
        $src_tablet_attr['srcset'] = $imgPath . 'srcset/' . $imgName . '-' . $val . $imgExt;

        if ($options['pixel-ratio']) {
            $val = $this->img_get_width($options['wimg-tablet'] * $options['pixel-ratio'], $sizes);
            $src_tablet_attr['srcset'] .= ', ';
            $src_tablet_attr['srcset'] .= $imgPath . 'srcset/' . $imgName . '-' . $val . $imgExt;
            $src_tablet_attr['srcset'] .= ' ' . $options['pixel-ratio'] . 'x';
        }

        // === IMG = image pour vignette
        $img_attr['alt'] = strip_tags($img_attr['alt']);
        // forcer la largeur à 100%
        $this->get_attr_style($img_attr, $options['img-class'], $options['img-style']); // v2.7

        // $img_attr['loading'] = "lazy";

        $val = $this->img_get_width($options['wimg-desktop'], $sizes);
        $img_attr['src'] = $imgPath . 'srcset/' . $imgName . '-' . $val . $imgExt;

        if ($options['pixel-ratio']) {
            $val = $this->img_get_width($options['wimg-desktop'] * $options['pixel-ratio'], $sizes);
            $img_attr['srcset'] = '';
            $img_attr['srcset'] .= ($img_attr['srcset'] == '') ? '' : ', ';
            $img_attr['srcset'] .= $imgPath . 'srcset/' . $imgName . '-' . $val . $imgExt;
            $img_attr['srcset'] .= ' ' . $options['pixel-ratio'] . 'x';
        }

        // === code pour affichage image

        if ($thumb && ! $shuffle) {
            $out[] = '<div class="grid__item">';
        }
        $out[] = $this->set_attr_tag('figure', $figure_attr);
        if ($options['gallery']) {
            $out[] = $this->set_attr_tag('a', $a_attr);
        }

        if ($thumb) {
            $out[] = '<picture>';
            $out[] = $this->set_attr_tag('source', $src_mobile_attr);
            $out[] = $this->set_attr_tag('source', $src_tablet_attr);
            $out[] = $this->set_attr_tag('img', $img_attr);
            $out[] = '</picture></a>'; // 3.0
            if ($options['legend-type'] != 0) {
                // --- code pour download
                $download = '';
                if ($options['download-label']) {
                    $attr_download['title'] = $options['download-title'];
                    $attr_download['href'] = $imgPath . 'srcset/' . $imgName . '-' . end($sizes) . $imgExt;
                    $attr_download['download'] = $imgName . $imgExt;
                    $this->get_attr_style($attr_download, $options['download-style']);
                    $download = '&nbsp;' . $this->set_attr_tag('a', $attr_download, $options['download-label']);
                }
                // if (strpos($legend, ' -- ') > 0)
                // $legend = $this->legend_style($legend, $options['legend-template']);
                $out[] = $this->set_attr_tag('figcaption', $figcaption_attr, $legend . $download);
            }
        }

        if ($options['gallery']) {
            $out[] = '</a>';
        }

        $out[] = '</figure>';

        if ($thumb && ! $shuffle) {
            $out[] = '</div>';
        }

        return implode(PHP_EOL, $out);
    }

    // img_get_code

    /*
     * creation d'un sous-dossier si inexistant dans le chemin image
     */
    public function create_subdir($dir, $subdir = 'srcset')
    {
        if (! is_dir(JPATH_ROOT . '/' . $dir . '/' . $subdir)) {
            if (! @mkdir(JPATH_ROOT . '/' . $dir . '/' . $subdir, 0755, true) && ! is_dir(JPATH_ROOT . '/' . $this->srcset_path . $dir . '/' . $subdir)) {
                throw new RuntimeException('There was a file permissions problem in folder \'' . $subdir . '\'');
            }
        }
        return true;
    }

    /*
     * retourne le texte pour le titre lightbox et la balise ALT
     * Utilise par fichier unique et contenu.
     * $img : array des attributs IMG
     * $legend : texte fourni par shortcode
     */
    public function img_get_legend($img, $legend = '')
    {

        // 1 - la legende du shortcode pour fichier unique
        if (! empty($legend)) {
            return $legend;
        }

        // 2 - l'attribut alt
        if (! empty($img['alt'])) {
            return $img['alt'];
        }

        // 3 - le texte dans le fichier legend.ini
        $path = pathinfo($img['src']);
        if (file_exists($path['dirname'] . '/legend.ini')) {
            $legends = $this->load_inifile($path['dirname'] . '/legend.ini');
            if (isset($legends[$path['basename']])) {
                return $legends[$path['basename']];
            }
        }
        // 4 - par defaut, le nom du fichier humanisé
        return $this->link_humanize($img['src']);
    }

    /*
     * Creation d'une image redimensionnee
     * pas utilisation JImage pour transparence png
     */
    public function img_resize($imgSrc, $imgDest, $typeSrc, $wSrc, $hSrc, $wDest, $hDest, $quality = 70)
    {
        $hDest = (int) $hDest;
        if ($typeSrc === 3) { // PNG
            $img = imagecreatefrompng($imgSrc);
            if ($img === false) {
                $this->msg_error($this->trad_keyword('PNG-file-corrupt', $imgSrc));
                return;
            }
            $imgNew = imagecreatetruecolor($wDest, $hDest);
            imagealphablending($imgNew, false);
            imagesavealpha($imgNew, true);
            $transparency = imagecolorallocatealpha($imgNew, 255, 255, 255, 127);
            imagefilledrectangle($imgNew, 0, 0, $wDest, $hDest, $transparency);
            imagecopyresampled($imgNew, $img, 0, 0, 0, 0, $wDest, $hDest, $wSrc, $hSrc);
            imagepng($imgNew, $imgDest, 9);
        } elseif ($typeSrc === 18) { // WEBP
            $img = imagecreatefromwebp($imgSrc);
            $imgNew = imagecreatetruecolor($wDest, $hDest);
            imagecopyresampled($imgNew, $img, 0, 0, 0, 0, $wDest, $hDest, $wSrc, $hSrc);
            imagewebp($imgNew, $imgDest, $quality);
        } elseif ($typeSrc == IMG_JPG) {
            $img = imagecreatefromjpeg($imgSrc);
            $imgNew = imagecreatetruecolor($wDest, $hDest);
            imagecopyresampled($imgNew, $img, 0, 0, 0, 0, $wDest, $hDest, $wSrc, $hSrc);
            // rotation v3.1
            $exif = @exif_read_data($imgSrc);
            if (isset($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 8:
                        $imgNew = imagerotate($imgNew, 90, 0);
                        break;
                    case 3:
                        $imgNew = imagerotate($imgNew, 180, 0);
                        break;
                    case 6:
                        $imgNew = imagerotate($imgNew, - 90, 0);
                        break;
                }
            }
            imagejpeg($imgNew, $imgDest, $quality);
        } else {
            $this->msg_error($this->trad_keyword('error-type-image', $typeSrc, $imgSrc));
        }
    }

    /*
     * Calcule les tailles d'image necessaires
     * le resultat est stocke dans l'array $option
     */
    public function img_get_sizes(&$options, $default)
    {

        // on ajoute les tailles pour lightbox et max-page
        $rwd_sizes[] = $options['wmax-lightbox'];
        $rwd_sizes[] = $options['wmax-lightbox-mobile'];

        // si prise en charge pixel-ratio, on prevoit les tailles pour lightbox
        $options['pixel-ratio'] = intval($options['pixel-ratio']);
        if ($options['pixel-ratio']) {
            $rwd_sizes[] = $options['wmax-lightbox'] * $options['pixel-ratio'];
            $rwd_sizes[] = $options['wmax-lightbox-mobile'] * $options['pixel-ratio'];
        }

        // la liste des largeurs pour la lightbox uniquement
        $rwd_sizes = array_unique($rwd_sizes);
        $rwd_sizes = array_map('intval', $rwd_sizes);
        sort($rwd_sizes);
        $options['rwd-sizes-lightbox'] = $rwd_sizes;

        // les largeurs (en %) pour les images
        if ($options['img-sizes'] == '') {
            $grid = explode('-', $default);
        } else {
            // normalise le separateur
            $img_sizes = str_replace(',', '-', $options['img-sizes']);
            $grid = array_replace(explode('-', $default), explode('-', $img_sizes));
        }

        $grid = array_pad($grid, 3, $grid[count($grid) - 1]);
        $options['wimg-desktop'] = $options['wmax-page'] * $grid[0] / 100;
        $rwd_sizes[] = $options['wimg-desktop'];
        $rwd_sizes[] = $options['wimg-desktop'] * $options['pixel-ratio'];
        $options['wimg-tablet'] = $options['bp-tablet'] * $grid[1] / 100;
        $rwd_sizes[] = $options['wimg-tablet'];
        $rwd_sizes[] = $options['wimg-tablet'] * $options['pixel-ratio'];
        $options['wimg-mobile'] = $options['bp-mobile'] * $grid[2] / 100;
        $rwd_sizes[] = $options['wimg-mobile'];
        $rwd_sizes[] = $options['wimg-mobile'] * $options['pixel-ratio'];

        // Les largeurs d'images a creer
        $rwd_sizes = array_unique($rwd_sizes);
        $rwd_sizes = array_map('intval', $rwd_sizes);
        sort($rwd_sizes);

        // suppression des largeurs inutiles
        while ($rwd_sizes[0] < 100) {
            array_shift($rwd_sizes);
        }
        $j = count($rwd_sizes) - 1;
        for ($i = $j; $i > 0; $i--) {
            if ($rwd_sizes[$i - 1] > ($rwd_sizes[$i] * $options['similarity'])) {
                unset($rwd_sizes[$i - 1]);
                $i--;
            }
        }

        // liste pour redimensionnement des images
        $options['rwd-sizes'] = $rwd_sizes;
    }

    /*
     * utilise pour avoir la largeur d'image imediatemment superieure au besoin
     * ou la plus grande disponible
     * exemple pour $val=412 et $array_range=array(320,480,768) retourne 480
     */
    public function img_get_width($wimg, $wlist)
    {
        foreach ($wlist as $w) {
            if ($w >= $wimg) {
                break;
            }
        }
        return $w;
    }

    /*
     * Supprime tous les sous-dossiers 'srcset' du dossier $folder
     */
    public function srcset_raz($folder)
    {
        foreach (glob($folder . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            if (substr($dir, - 6, 6) == 'srcset') {
                foreach (glob($dir . '/*.*') as $file) {
                    unlink($file);
                }
                if (rmdir($dir) === false) {
                    $this->msg_error($this->trad_keyword('error-delete-folder', $dir));
                }
            } else {
                $this->srcset_raz($dir);
            }
        }
    }

    /*
     *
     */
    public function legend_style($legend, $tmpl)
    {
        $sep = ' -- ';
        if ($sep && strpos($legend, $sep) > 2 && strrpos($legend, $sep) > 2) {
            $tmp = explode($sep, $legend, 2);
            $legend = str_replace('##1##', $tmp[0], $tmpl);
            $legend = str_replace('##2##', $tmp[1], $legend);
        }
        return $legend;
    }
}

// class
