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
            __class__ => 'joomla', // mot(s) clé(s) séparé par des virgules
            'height' => '200', // hauteur image téléchargée
            'width' => '200', // largeur image téléchargée
            'orientation' => '', // H ou V pour utiliser des photos horizontale ou verticale avant recadrage
            'color' => 'g', // NON DISPONIBLE (g (gris), p (pixellisé), red, green, ou blue)
            'number' => 1, // nombre d'images retournées
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

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        $keyword = ($options[__class__]);
        $keyword = str_replace(array(
            '/',
            '\\'
        ), '-', $keyword);
        $keyword = array_map('trim', explode(',', $keyword));

        $w = (int) $options['width'];
        $h = (int) $options['height'];
        $wpx = $w . 'px';
        $hpx = $h . 'px';

        $orientation = ($options['orientation'] === true) ? ' ' : strtoupper($options['orientation']);
        if (isset($this->options_user['orientation'])) {
            $orientation = $this->ctrl_argument($orientation[0], ' ,H,V');
        }

        $nb = $options['number'];
        $cache_delay = $options['cache-delai'];

        // === nettoyage du cache
        if ($options['cache-reset']) {
            $this->delTree($this->path_normalize(JPATH_BASE . '/tmp/up-flickr/'));
        }

        // recupere image(s) dans cache
        $images = $this->get_image($w, $h, $keyword, $orientation, $nb, $cache_delay);

        $main_attr = array();
        if ($options['mode'] == 'img') {
            $img_attr = array();
            $this->get_attr_style($img_attr, $options['class'], $options['style']);
            foreach ($images as $image) {
                $img_attr['src'] = $image;
                if (basename($image) == '00_error.jpg') {
                    // $img_attr['style'] = 'max-width:' . $wpx . ';max-height:' . $hpx . ';';
                    unset($img_attr['title']);
                } else {
                    $img_attr['title'] = 'Credit: ' . $this->link_humanize($image);
                    // $img_attr['alt'] = 'Credit: '.$this->link_humanize($image) . ' <a href="//flickr.com">flickr.com</a>';
                }
                $imgout[] = $this->set_attr_tag('img', $img_attr);
            }
            if ($options['align'] || $options['main-class']) {
                $align = ($options['align']) ? 'text-align:' . $options['align'] . ';' : '';
                $this->get_attr_style($main_attr, $options['main-class'], $align);
                $out = $this->set_attr_tag($options['main-tag'], $main_attr, implode(PHP_EOL, $imgout));
            } else {
                $out = implode(PHP_EOL, $imgout);
            }
        } else {
            // on retourne le chemin du dossier
            $out = dirname($images[0]);
        }

        return $out;
    }

    // run

    /*
     * get_image : retourne les images dans le cache ou les récupère
     */
    public function get_image($w, $h, $keyword, $orientation = '', $nb = 1, $cache_delay = 60)
    {
        $sOrientation = ($orientation > '') ? '_' . $orientation : '';
        $folderName = $this->filename_secure(implode('-', $keyword) . '_' . $w . '_' . $h . '_' . $nb . $sOrientation);
        $folderPath = 'tmp/up-flickr/' . $folderName;
        if (file_exists($folderPath)) {
            // controle duree cache
            if ($cache_delay < 0 || filemtime($folderPath) > strtotime('-' . $cache_delay . 'hour')) {
                // on retourne les images en cache
                $items = glob($folderPath . '/*.*');
                // en cas d'erreur
                if (count($items) == $nb) {
                    return $items;
                } else {
                    $this->delTree(JPATH_ROOT . '/' . $folderPath);
                    unset($items);
                }
            } else {
                // on supprime pour le recreer
                $this->delTree(JPATH_ROOT . '/' . $folderPath);
            }
        }
        if (! is_dir(JPATH_ROOT . '/' . $folderPath)) {
            if (! @mkdir(JPATH_ROOT . '/' . $folderPath, 0755, true) && ! is_dir(JPATH_ROOT . '/' . $folderPath)) {
                throw new RuntimeException('There was a file permissions problem in folder \'' . $folderPath . '\'');
            }
        }

        $items = array();
        for ($i = 1; $i <= $nb; $i++) {
            $name = $w.'x'.$h;
            if ($nb > 1) {
                $name .= $i .'\n'  .$w.'x'.$h;
            }
            $image = file_get_contents('https://placehold.co/'.$name);
            $filename = str_replace('\n', '-', $name);
            $file = 'tmp/up-place/' . $filename . '/' . $filename . '.png';
            $absFile = JPATH_BASE . '/' . $file ;
            file_put_contents($absFile, $image);
        }
        /* Quand flickr refonctionnera
        $dataFile = 'https://loremflickr.com/json/' . $w . '/' . $h . '/' . implode(',', $keyword) . '/all';
        $essai = 0;
        $doublon = array();
        do {
            $essai++;
            $data = file_get_contents($dataFile);
            $data = json_decode($data);
            if ($data->rawFileUrl > '' && in_array($data->rawFileUrl, $doublon) === false) {
                // test doublons !!!!!!!!
                $doublon[] = $data->rawFileUrl;
                $file = 'tmp/up-flickr/' . $folderName . '/';
                $file .= $data->owner . '--flickr---license-' . str_replace('-', '--', $data->license) . '_' . $essai . '.jpg';
                $absFile = JPATH_BASE . '/' . $file;
                if ($this->resizeAndCropImage($data->rawFileUrl, $absFile, $w, $h, $orientation)) {
                    $items[] = $file;
                }
            }
        } while (count($items) < $nb && $essai < ($nb + 5));
*/
        // === alerter en cas de manque
        if (count($items) < $nb) {
            if (! copy($this->actionPath . '/00_error.jpg', JPATH_BASE . '/' . $folderPath . '/00_error.jpg')) {
                echo "La copie $file du fichier a échoué...\n";
            }
            array_unshift($items, $folderPath . '/00_error.jpg');
        }

        // === fini
        return $items;
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
     * ajuste les dimensions pour que l'image couvre entièrement les dimensions souhaitées.
     */
    public function resizeAndCropImage($sourcePath, $destinationPath, $newWidth, $newHeight, $orientation)
    {
        // Charger l'image source
        $sourceImage = imagecreatefromstring(file_get_contents($sourcePath));
        if (! $sourceImage) {
            return false;
        }

        // Obtenir les dimensions originales
        list($sourceWidth, $sourceHeight) = getimagesize($sourcePath);

        // contrôle orientation
        if ($orientation > '') {
            $sens = ($sourceWidth < $sourceHeight) ? 'V' : 'H';
            if ($sens != $orientation) {
                return false;
            }
        }

        // Calculer le facteur de redimensionnement pour remplir entièrement les nouvelles dimensions
        $scale = max($newWidth / $sourceWidth, $newHeight / $sourceHeight);

        // Dimensions intermédiaires
        $resizeWidth = ceil($sourceWidth * $scale);
        $resizeHeight = ceil($sourceHeight * $scale);

        // Créer une image temporaire redimensionnée
        $resizedImage = imagecreatetruecolor($resizeWidth, $resizeHeight);
        imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $resizeWidth, $resizeHeight, $sourceWidth, $sourceHeight);

        // Créer une image finale pour recadrer
        $croppedImage = imagecreatetruecolor($newWidth, $newHeight);

        // Calculer les coordonnées de recadrage
        $xOffset = ceil(($resizeWidth - $newWidth) / 2);
        $yOffset = ceil(($resizeHeight - $newHeight) / 2);

        // Recadrer l'image
        imagecopy($croppedImage, $resizedImage, 0, 0, $xOffset, $yOffset, $newWidth, $newHeight);

        // Sauvegarder l'image redimensionnée et recadrée
        imagejpeg($croppedImage, $destinationPath);

        // Libérer la mémoire
        imagedestroy($sourceImage);
        imagedestroy($resizedImage);
        imagedestroy($croppedImage);

        return true;
    }

    /*
     * ----------------------------------------------------------------------
     * filename_secure
     * ----------------------------------------------------------------------
     * remplace les caractères à risque pour windows dans le nom du fichier
     */
    public function filename_secure($filename)
    {
        $old = explode(',', '<,>,:,*,?,",/,\,|, ');
        $new = explode(',', '(,),-,_,#,,_,_,_,_');
        return str_replace($old, $new, $filename);
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
