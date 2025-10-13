<?php

/**
 * affiche une image aleatoire
 *
 * Syntax 1 :  {up loremflickr=type | width=xx | height=xx}
 * .
 * <b>Note</b> : width & height sont les dimensions de l'image retournée par lorempixel. Pour l'afficher en remplissant le bloc parent, il faut ajouter style=width:100%
 *
 * Le site //lorempixel.com étant fermé, l'action utilise le site //loremflickr.com
 * on peut appeler l'action par loremflickr ou lorempixel
 *
 * @author   Lomart
 * @version  UP-1.0
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Editor
 */
/*
 * v2.9 : - remplacement de feu lorempixel.com par https://loremflickr.com/
 * - ajout options tag et color
 * v5.1 : reprise totale de l'action a cause de l'API flick
 * v5.2 : suite a blocage de loremflickr, remplacement par lorem_place
 */
defined('_JEXEC') or die();

class lorem_flickr extends upAction
{
    public function init()
    {
        // aucune
    }

    public function run()
    {

        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        // ===== valeur paramétres par défaut
        // il est indispensable de tous les définir ici
        $options_def = array(
            __class__ => '', // texte
            'height' => '200', // hauteur image téléchargée
            'width' => '200', // largeur image téléchargée
            'orientation' => '', // NON DISPONIBLE
            'color' => 'g', // Couleur background
            'number' => 1, // nombre d'images retournées
            /* [st-cache] gestion du cache */
            'cache-delai' => - 1, // durée de validité du cache. 0:aucun, 1:une heure, -1:illimité
            'cache-reset' => 0, // efface TOUS les fichiers dans le cache
            /* [st-out] Methode pour retour */
            'mode' => 'img', // balise img ou dir pour le chemin vers le dossier
            /* [st-main] Bloc parent */
            'main-tag' => 'div', // balise du bloc parent à l'image si options main-class ou align
            'main-class' => '', // classe(s)
            'align' => '', // alignement horizontal : left, center, right
            /* [st-css] Style CSS */
            'id' => '', // identifiant
            'class' => '', // classes et styles image(s) en mode=img
            'style' => '' // classes et styles image(s) en mode=img
        );

        // __class__ => '', // dimension de l'image largeur x hauteur
        // 'width' => 300, // largeur de l'image en pixels
        // 'height' => 300, // hauteur de l'image en pixels
        // 'bg-color' => 'CCC', // Couleur de fond. Code hexa ou nom.
        // 'text-color' => '333', // Couleur du texte. Code hexa ou nom.
        // 'text' => '', // texte affiché. 56 caractères maxi. Saut de ligne: \n
        // 'font' => 'Lato', // police : Lato,Montserrat,Oswald,PT Sans,Roboto,Lora,Open Sans,Playfair Display,Raleway,Source Sans Pro
        // 'format' => 'svg', // svg, png, jpeg, gif ou Webp.
        // /* [st-cache] gestion du cache */
        // 'cache-delai' => - 1, // durée de validité du cache. 0:aucun, 1:une heure, -1:illimité
        // 'cache-reset' => 0, // efface TOUS les fichiers dans le cache
        // /* [st-style] style */
        // 'id' => '', // identifiant
        // 'class' => '', // classe(s) et style pour bloc
        // 'style' => '', // classe(s) et style pour bloc
        // 'css-head' => '' // style ajouté dans le HEAD de la page

        // ====== fusion et controle des options
        // =====================================
        $options = $this->ctrl_options($options_def);

        // réaffectation des options entre lorem_flickr et lorem_place
        $options['text'] = $options[__CLASS__];
        // $option['bg-color'] = $options['color'];

        // === CSS-HEAD
        //$this->load_css_head($options['css-head']);

        // === nettoyage du cache
        if ($options['cache-reset']) {
            $this->delTree(JPATH_BASE . '/tmp/up-place/');
        }

        // la requete est composée de :
        // =====================================
        // 1- $format : size et color
        // 2- $ext : extension image
        // 3- $texte : text et font

        // --- 1- $format : size et color
        $w = (int) $options['width'];
        $h = (int) $options['height'];
        $w = $w ?: ($h ?: 300);
        $h = $h ?: $w;
        $format = $w . 'x' . $h;

        // --- si une des couleurs est demandee, il faut les 2
        $color = '';
        if (isset($this->options_user['color'])) {
            $bgcolor = trim($options['color']);
            $textcolor = '888';
            if ($bgcolor[0] == '#') {
                $bgcolor = ltrim($bgcolor, '#');
                if (strlen($bgcolor) === 3) {
                    $bgcolor = str_repeat(substr($bgcolor, 0, 1), 2) .
                               str_repeat(substr($bgcolor, 1, 1), 2) .
                               str_repeat(substr($bgcolor, 2, 1), 2);
                }
                $r = hexdec(substr($bgcolor, 0, 2));
                $g = hexdec(substr($bgcolor, 2, 2));
                $b = hexdec(substr($bgcolor, 4, 2));
                $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
                $textcolor = ($luminance > 0.5) ? '000000' : 'FFFFFF';
            }
            $color .= '/' . $bgcolor . '/' . $textcolor ;
        }

        // --- 2- $ext : extension image
        $ext = 'png';

        // --- 3- $text : text et font
        $texte_prefix = '';
        $texte = trim($options['text']);
        if ($texte > '') {
            $texte = strip_tags($texte);
            if (strlen($texte) > 56) {
                $texte = substr($texte, 0, 56) . '...';
            }
            $texte = str_replace(' ', '+', $texte);
            $texte = $this->filename_secure($texte);
            $texte_prefix = '?text=';
        }

        // ==== creer ou recuperer l'image
        // =====================================
        // --- nom du fichier
        $cachepath = 'tmp/up-place/';
        $filename = $format ;
        $filename .= str_replace('/', '-', $color);
        $filename .= ($texte > '') ? '-' . $texte : '';
        $filepath = $cachepath . $filename . '.' . $ext;

        // --- url
        $site = 'https://placehold.co/';
        $fileUrl = $site . $format. $color  . '/'.$ext. $texte_prefix . $texte ;

        $cache_delay = $options['cache-delai'];

        if ($cache_delay == 0) {
            // pas de cache, l'image est l'URL
            $image = $fileUrl;
        } else {
            if (file_exists($filepath)) {
                // controle duree cache
                if ($cache_delay < 0 || filemtime($filepath) > strtotime('-' . $cache_delay . 'hour')) {
                    // on retourne les images en cache
                    $image = $filepath;
                } else {
                    // cache non valide, on supprime pour recreer
                    unlink($filepath);
                }
            }
            // pas en cache
            if (empty($image)) {
                if (! is_dir(JPATH_ROOT . '/' . $cachepath)) {
                    if (! @mkdir(JPATH_ROOT . '/' . $cachepath, 0755, true) && ! is_dir(JPATH_ROOT . '/' . $cachepath)) {
                        throw new RuntimeException('There was a file permissions problem in folder \'' . $cachepath . '\'');
                    }
                }
                if (file_put_contents($filepath, file_get_contents($fileUrl))) {
                    $image = $filepath;
                } else {
                    $image = $fileUrl;
                }
            }
        }

        // attributs du bloc principal
        $attr_main = array();
        $attr_main['id'] = $options['id'];
        $attr_main['src'] = $image;
        $this->get_attr_style($attr_main, $options['class'], $options['style']);

        // code en retour
        $html = $this->set_attr_tag('img', $attr_main, false);

        return $html;
        // === fini
        return $items;

    }

    /*
     * ----------------------------------------------------------------------
     * filename_secure
     * ----------------------------------------------------------------------
     * remplace les caractères à risque pour windows dans le nom du fichier
     */
    public function filename_secure($filename)
    {
        $filename = str_replace('\'', '_', $filename);
        // $filename = str_replace('%2B', '+', $filename);
        $old = explode(',', '%,<,>,:,*,?,",/,\,|, ,=,+,#,text');
        $new = explode(',', '-,(,),-,_,#,,_,_,_,_,_,_,,_');
        $filename = str_replace($old, $new, $filename);
        return $filename;
    }

    public function url_secure($url)
    {
        $url = str_replace('+', 'XPLUSX', $url);
        $url = urlencode($url);
        $url = str_replace('XPLUSX', '+', $url);
        // $url = str_replace('\'','%27',$url);
        return $url;
    }

    public function delTree($dir)
    {
        $files = array_diff(scandir($dir), array(
            '.',
            '..'
        ));

        foreach ($files as $file) {

            (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }

        return rmdir($dir);
    }


    /*
     * remplace les séparateurs de chemin
     */
    public function path_normalize($path)
    {
        return str_replace(array(
            '/',
            '\\'
        ), DIRECTORY_SEPARATOR, $path);
    }

}

// class
