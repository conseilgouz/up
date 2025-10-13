<?php

/**
 * affiche une image aleatoire (INUTILISABLE)
 *
 * L'API Flickr est fermée ainsi que les alternatives.
 * afin de préserver les shortcodes existants, l'action génère une image avec un fond coloré et les dimensions spécifiées
 *
 * Syntax 1 :  {up loremflickr=type | width=xx | height=xx}
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

        // ===== valeur paramétressvg par défaut
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


        // ====== fusion et controle des options
        // =====================================
        $options = $this->ctrl_options($options_def);

        // réaffectation des options entre lorem_flickr et lorem_place
        $options['text'] = $options[__CLASS__];

        // === nettoyage du cache
        if ($options['cache-reset']) {
            $this->delTree(JPATH_BASE . '/tmp/up-place/');
        }

        // =====================================
        // la requete est composée de :
        // https://placehold.co/$wx$h/$bgcolor/$textcolor?text=$texte
        // le dossier dans cache :
        // $wx$h-$bgcolor-$textcolor-$texte-$nb
        // le ou les fichiers :
        // 1.svg à $number.svg

        // --- $format : $wx$h  (300x300 par défaut)
        $w = (int) $options['width'];
        $h = (int) $options['height'];
        $w = $w ?: ($h ?: 300);
        $h = $h ?: $w;
        $format = $w . 'x' . $h;

        // --- $color : /$bgcolor/$textcolor
        $color = '';
        if (isset($this->options_user['color'])) {
            $bgcolor = trim($options['color']);
            if ($bgcolor = 'g' || $bgcolor = 'p') {
                $bgcolor = '#aaa';
            }
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

        // --- $texte :
        $texte = trim($options['text']);
        if ($texte > '') {
            $texte = strip_tags($texte);
            if (strlen($texte) > 56) {
                $texte = substr($texte, 0, 56) . '...';
            }
            // $texte = str_replace('[br]', '\\n', $texte);
            $texte = str_replace(' ', '+', $texte);
            $texte = $this->filename_secure($texte);
        }


        // ==== creer ou recuperer l'image
        $images = $this->get_image($format, $color, $texte, $options['number'], (int) $options['cache-delai']);

        // ==== préparer le retour
        $main_attr = array();
        if ($options['mode'] == 'img') {
            $img_attr = array();
            $this->get_attr_style($img_attr, $options['class'], $options['style']);
            foreach ($images as $image) {
                $img_attr['src'] = $image;
                $imgout[] = $this->set_attr_tag('img', $img_attr);
            }
            if ($options['align'] || $options['main-class']) {
                $align = ($options['align']) ? 'text-align:' . $options['align'] . ';' : '';
                $this->get_attr_style($main_attr, $options['main-class'], $align);
                $out = $this->set_attr_tag($options['main-tag'], $main_attr, implode(PHP_EOL, $imgout));
            } else {
                $out = implode('', $imgout);
            }
        } else {
            // on retourne le chemin du dossier
            $out = dirname($images[0]);
        }

        return $out;

    }

    /*
     * ----------------------------------------------------------------------
     * get_image
     * ----------------------------------------------------------------------
     * récupère les images dans le cache OU les créé avec placehold.co
     * retourne un tableau avec le chemin vers la ou les images
    */
    private function get_image($format, $color, $texte, $nb, $cache_delay)
    {
        // le dossier
        $folderPath = 'tmp/up-flickr/' . $format;
        $folderPath .= str_replace('/', '-', $color);
        $folderPath .= ($texte != '') ? '-'.$texte : '';
        $folderPath .= ($nb > 1) ? '-'.$nb : '';

        if (file_exists($folderPath)) {
            // controle duree cache
            if ($cache_delay < 0 || filemtime($folderPath) > strtotime('-' . $cache_delay . 'hour')) {
                // on retourne les images en cache
                $items = glob($folderPath . '/*.svg');
                // en cas d'erreur
                if (count($items) == $nb) {
                    return $items;
                } else {
                    // on supprime pour le recreer le nombre demandé
                    $this->delTree(JPATH_ROOT . '/' . $folderPath);
                    unset($items);
                }
            } else {
                // on supprime pour le recreer
                $this->delTree(JPATH_ROOT . '/' . $folderPath);
            }
        }
        // créer le dossier
        if (! is_dir(JPATH_ROOT . '/' . $folderPath)) {
            if (! @mkdir(JPATH_ROOT . '/' . $folderPath, 0755, true) && ! is_dir(JPATH_ROOT . '/' . $folderPath)) {
                throw new RuntimeException('There was a file permissions problem in folder \'' . $folderPath . '\'');
            }
        }
        // créer la ou les images
        for ($i = 1; $i <= $nb; $i++) {
            if ($nb > 1) {
                $texte_idx = '?text='. $i . '\\n' . $texte;
            } else {
                $texte_idx = '?text='. $texte;
            }
            $fileUrl = 'https://placehold.co/'.$format.$color.$texte_idx;
            // recup image
            $filepath = $folderPath . '/' . str_pad($i, strlen($nb), "0", STR_PAD_LEFT) .'.svg';
            $image = file_get_contents($fileUrl);
            file_put_contents($filepath, $image);
            $out[] = $filepath;
            // }
        }
        // on retourne le tableau des images
        return $out;
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
