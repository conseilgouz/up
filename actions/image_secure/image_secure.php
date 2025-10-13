<?php

/**
 * Affiche une image de manière à compliquer sa récupération
 *
 * syntaxe {up image-secure=chemin_image}
 *
 * @author   LOMART
 * @version  UP-1.8
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags  image
 */
defined('_JEXEC') or die;

class image_secure extends upAction {

    function init() {
        // charger les ressources communes à toutes les instances de l'action
        $this->load_file('image_secure.css');
        return true;
    }

    function run() {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // chemin relatif vers image jpg ou png
            'folder-source' => 'images', // dossier racine des images originales
            'folder-strip' => 'images/image-secure', // dossier avec les images fractionnées
            'nb-strip' => 5, // nombre de bandes
            'alt' => '', // texte alternatif pour image. Si vide: nom du fichier humanisé
            'quality' => 80, // pourcentage qualité en JPG
            'reset' => 0, // force la génération des images strip
            'delete-source' => 0, // supprime l'image source après génération des strips
            /* [st-css] Style CSS */
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // ==============================
        // fusion et controle des options
        // ==============================
        $options = $this->ctrl_options($options_def);
        // -- les dossiers racines
        $folder_source = rtrim($options['folder-source'], '\/');
        $folder_strip = rtrim($options['folder-strip'], '\/');

        // -- analyse chemin fichier source
        // $img_path : chemin relatif à $folder_source
        // $img_name : nom image source sans extension
        // $img_type : extension image source
        // $img_source : chemin complet vers image source

        $imgSrc = pathinfo($options[__class__]);

        $img_path = $imgSrc['dirname'];
        // suppression dossier folder_source du chemin fichier
        if (strpos($img_path, $folder_source) === 0)
            $img_path = substr($img_path, strlen($folder_source) + 1);
        $img_name = $imgSrc['filename'];
        // -- extensions autorisées
        $img_type = $imgSrc['extension'];
        // -- chemin complet vers image source
        $img_source = $folder_source . '/' . $img_path . '/' . $img_name . '.' . $img_type;

        // liste des images fractionnées
        $pattern = $folder_strip . '/' . $img_path . '/' . $img_name . '-up??.' . $img_type;
        $img_list = glob($pattern);

        // si le nombre de strip est différent de l'option, on reset
        if (count($img_list) != $options['nb-strip'])
            $options['reset'] = 1;

        // ===========================
        // Reset des images partielles
        // ===========================
        // uniquement si l'image source existe
        if ($options['reset'] && file_exists($img_source)) {
            foreach ($img_list AS $k => $v) {
                unlink($v);
            }
            unset($img_list);
        }

        // ===========================
        // Fractionnement de l'image
        // ===========================
        if (empty($img_list)) {
            // si image originale manquante -> c'est fini
            if (!file_exists($img_source)) {
                return($this->msg_inline(lang('en=NOT FOUND : ;fr=NON TROUVE : ') . $img_source));
            }
            // on découpe
            $this->create_subdir($folder_strip . '/' . $img_path);
            $img_base = $folder_strip . '/' . $img_path . '/' . $img_name;
            $img_list = $this->make_strip($img_source, $img_base, $img_type, $options['nb-strip'], $options['quality']);

            // on supprime original ?
            // uniquement lors de la phase de création
            if ($options['delete-source'] && !empty($img_list)) {
                unlink($img_source);
            }
        }

        // ===========================
        // copie de l'image leurre
        // ===========================
        $img_leurre = $folder_strip . '/no-copy.png';
        if (!file_exists($img_leurre))
            $ok = copy($this->actionPath . 'no-copy.png', $img_leurre);

        // ===========================
        // Code pour les strip-images
        // ===========================
        $alt = ($options['alt']) ? $options['alt'] : $this->link_humanize($img_name);
        $strip_code = '<div class="overlay"><img src="' . $img_leurre . '"></div>';
        foreach ($img_list as $k => $v) {
            $strip_code .= '<img src="' . $v . '" alt="' . $alt . '">';
        }

        // === CSS-HEAD
        $options['css-head'] .= '#id> img [width:' . (100 / count($img_list)) . '%]';
        $this->load_css_head($options['css-head']);

        // attributs du bloc principal
        $attr_main = array();
        $attr_main['id'] = $options['id'];
        $attr_main['class'] = 'up-secure';
        $this->get_attr_style($attr_main, $options['class'], $options['style']);

        // code en retour
        $html = $this->set_attr_tag('div', $attr_main, $strip_code);

        return $html;
    }

// run

    /*
     * make_strip :  decoupe l'image source en bandes
     * retourne la listes des images partielles classées de gauche à droite
     */

    function make_strip($imgSrc, $img_base, $img_type, $nb_strip, $quality = 90) {
        list($w, $h) = getimagesize($imgSrc);
        $wstrip = intval($w / $nb_strip);
        $img_type = strtolower($img_type);

        // controle et initialisation image source
        if ($img_type == 'jpg') {
            $img = imagecreatefromjpeg($imgSrc);
        } elseif ($img_type == 'png') {
            $img = imagecreatefrompng($imgSrc);
        } else {
            return $imgSrc;
        }
        // creation strip-images
        for ($i = 0; $i < $nb_strip; $i++) {
            $strip = imagecrop($img, ['x' => ($wstrip * $i), 'y' => 0, 'width' => ($wstrip), 'height' => $h]);
            $idx = substr(('0' . $i), -2, 2);
            if ($img_type == 'jpg') {
                $imgDest = $img_base . '-up' . $idx . '.jpg';
                imagejpeg($strip, $imgDest, $quality);
            } else { // png
                $imgDest = $img_base . '-up' . $idx . '.png';
                imagealphablending($strip, false);
                imagesavealpha($strip, true);
                imagepng($strip, $imgDest);
            }
            $out[] = $imgDest;
        }

        // on retourne la liste des chemins des strip-images
        return $out;
    }

    /*
     * creation d'un sous-dossier si inexistant dans le chemin image
     */

    function create_subdir($path) {

        if (!is_dir(JPATH_ROOT . '/' . $path)) {
            if (!@mkdir(JPATH_ROOT . '/' . $path, 0755, true) && !is_dir(JPATH_ROOT . '/' . $path)) {
                throw new RuntimeException('There was a file permissions problem in folder \'' . $subdir . '\'');
            }
        }
        return true;
    }

}

// class







