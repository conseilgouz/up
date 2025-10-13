<?php

/**
 * Affiche une image aux dimensions indiquées
 *
 * Affiche une image aux dimensions indiquées avec ou sans texte personnalisé.
 * La couleur  du fond et du texte peut être spécifiée par son code hexadecimal ou son nom
 *
 * syntaxe {up lorem-place=250x150 | text=... | bg-color=gold | text-color=333}
 *
 * @version  UP-5.1
 * @author   Lomart
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="https://placehold.co/" target"_blank">Utilise le site placehold.co/</a>
 * @tags    Editor
 *
 */

/*
 * v5.2 : remplace espace de font par +
 */
defined('_JEXEC') or die();

class lorem_place extends upAction
{
    public function init()
    {
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // dimension de l'image largeur x hauteur
            'width' => 300, // largeur de l'image en pixels
            'height' => 300, // hauteur de l'image en pixels
            'bg-color' => 'CCC', // Couleur de fond. Code hexa ou nom.
            'text-color' => '333', // Couleur du texte. Code hexa ou nom.
            'text' => '', // texte affiché. 56 caractères maxi. Saut de ligne BR: \n
            'font' => 'Lato', // police : Lato,Montserrat,Oswald,PT Sans,Roboto,Lora,Open Sans,Playfair Display,Raleway,Source Sans Pro
            'format' => 'svg', // svg, png, jpeg, gif ou Webp.
            /* [st-cache] gestion du cache */
            'cache-delai' => - 1, // durée de validité du cache. 0:aucun, 1:une heure, -1:illimité
            'cache-reset' => 0, // efface TOUS les fichiers dans le cache
            /* [st-style] style */
            'id' => '', // identifiant
            'class' => '', // classe(s) et style pour bloc
            'style' => '', // classe(s) et style pour bloc
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // ====== fusion et controle des options
        // =====================================
        $options = $this->ctrl_options($options_def);

        // === CSS-HEAD
        $this->load_css_head($options['css-head']);

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
        if ($options[__class__]) { // prioritaire
            $size = array_map('trim', explode('x', strtolower($options[__class__])));
            $w = (empty($size[0])) ? 300 : (int) $size[0];
            $h = (isset($size[1])) ? (int) $size[1] : $w; // carre, si non defini
        } else {
            $w = (int) $options['width'];
            $h = (int) $options['height'];
        }
        $format = $w . 'x' . $h;
        // --- si une des couleurs est demandee, il faut les 2
        if (isset($this->options_user['bg-color']) || isset($this->options_user['text-color'])) {
            $options['bg-color'] = strtolower(trim($options['bg-color'], '#'));
            $options['text-color'] = strtolower(trim($options['text-color'], '#'));
            $format .= '/' . $options['bg-color'] . '/' . $options['text-color'];
        }

        // --- 2- $ext : extension image
        $ext = (isset($this->options_user['format'])) ? $this->ctrl_argument($this->options_user['format'], 'svg,png,jpeg,jpg,gif,webp') : '';

        // --- 3- $text : text et font
        $texte_prefix = '';
        $texte = trim($options['text']);
        if ($texte > '') {
            $texte = strip_tags($texte);
            if (strlen($texte) > 56) {
                $texte = substr($texte, 0, 56) . '...';
            }
            // $texte = str_replace('+', '#', $texte);
            // $texte = str_replace(' ', '+', $texte);
            $texte_prefix = '?text=';
        }
        // --- police
        $font_prefix = '';
        $font = '';
        if (isset($this->options_user['font'])) {
            $font = $this->ctrl_argument($options['font'], 'Lato,Montserrat,Oswald,PT Sans,Roboto,Lora,Open Sans,Playfair Display,Raleway,Source Sans Pro', false);
            $font = str_replace(' ', '+', $font);
            $font_prefix = ((empty($texte)) ? '?' : '&') . 'font='. $font;
        }

        // ==== creer ou recuperer l'image
        // =====================================
        $site = 'https://placehold.co/';
        $cachepath = 'tmp/up-place/';
        // nom du fichier
        $filename = str_replace('/', '-', $format);
        $filename .= ($texte > '') ? '-' . $this->filename_secure($texte) : '';
        $filename .= ($font > '') ? '-' . $font : '';
        $filename .= (empty($ext)) ? '.svg' : '.' . $ext;
        $filepath = $cachepath . $filename;
        $fileUrl = $site . $format . ((empty($ext)) ? '' : '/' . $ext) . $texte_prefix . $this->url_secure($texte) . $font_prefix . $font;
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
}

// class
