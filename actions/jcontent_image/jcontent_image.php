<?php

/**
 * Affecte une image aux images intro et fulltext d'un article
 *
 * syntaxe 1 : {up jcontent-image} // première image du contenu de l'article
 * syntaxe 2 : {up jcontent-image=chemin_vers_image}
 * syntaxe 3 : {up jcontent-image=dossier} // première image du dossier
 *
 *
 * @version  UP-5.2
 * @author   Lomart
 * @license  <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit   LOMART
 * @tags     Joomla
 *
 */
defined('_JEXEC') or die();

use Joomla\CMS\Factory;

class jcontent_image extends upAction
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // chemin vers l'image pour intro et fulltext
            /* [st-intro] Options pour image d'introduction */
            'intro-size' => '250px', // coté du carré en px. 0=pas d'image
            'intro-alt' => '',       // contenu attribut alt. 0=aucun, vide=nom du fichier humanisé
            'intro-caption' => '',   // légende. 0=aucun, vide=nom du fichier humanisé
            'intro-class' => '',     // classe(s) pour l'image. si débute par +, on ajoute aux classes existantes
            /* [st-full] Options pour image de l'article */
            'full-size' => '500px',  // coté du carré en px. 0=pas d'image
            'full-alt' => '',        // contenu attribut alt. 0=aucun, vide=nom du fichier humanisé
            'full-caption' => '',    // légende. 0=aucun, vide=nom du fichier humanisé
            'full-class' => '',      // classe(s) pour l'image. si débute par +, on ajoute aux classes existantes
            /* [st-params] Paramètres */
            'subfolder-thumbs' => '', // nom du sous-dossier pour la vignette. vide=images/upthumbs pour toutes les vignettes
            'id' => 'identifiant interne' // interne. inutilisé
        );

        // ==== fusion et controle des options
        $this->options = $this->ctrl_options($options_def);
        $this->options['full-size'] = (int) $this->options['full-size'];
        $this->options['intro-size'] = (int) $this->options['intro-size'];

        // ==================================================================
        // ==== les images sont-elles défines ?
        // l'action ne modifie jamais les images définies dans l'article
        // ==================================================================
        $content_images = json_decode($this->article->images, true);
        $tmp = explode('#joomlaImage:', $content_images['image_intro']);
        $img = $tmp[0];
        $ok = ($img && file_exists($img)) ;
        $tmp = explode('#joomlaImage:', $content_images['image_fulltext']);
        $img = $tmp[0];
        $ok = ($ok && $img && file_exists($img)) ;
        if ($ok) {
            // on a déjà les images
            return '';
        }

        // ==================================================================
        // ==== recherche de l'image pour vignette
        // ==================================================================
        if (empty($this->options[__class__])) {
            // on cherche la première image du contenu de l'article
            preg_match_all('#<img.*src=[\"\'](.*)[\"\']#U', $this->article->text, $matches);
            $imgList = $matches[1];
        } elseif (is_dir($this->options[__class__])) {
            // on cherche la première image du dossier
            $dir = trim($this->options[__class__], ' /\/').'/';
            $imgList = glob($dir . '*.{jpg,JPG,jpeg,JPEG,png,PNG,webp,WEBP}', GLOB_BRACE | GLOB_NOSORT);
        } else {
            // on utilise l'image passée en paramètre principal
            $img = $this->options[__class__];
            // on vérifie que l'image est valide
            list($w, $h) = getimagesize($img);
            $img = (isset($w)) ? $img : '';
            if (empty($img)) {
                $this->msg_error('error image not valid : ' . $this->options[__class__]);
                return '';
            }
            $imgList[] = $img;
        }

        // ==================================================================
        // ==== on cherche la 1ere image de taille suffisante
        // ==================================================================
        $img_src_intro = ''; // chemin vers l'image d'intro (en réserve si pas de full)
        foreach ($imgList as $img) {
            if (file_exists($img)) {
                list($w, $h) = getimagesize($img);
                // la 1ere image compatible intro
                if (empty($img_src_intro) && ($w > $this->options['intro-size'] || $h > $this->options['intro-size'])) {
                    $img_src_intro = $img;
                }
                // la bonne image pour les 2 vignettes
                if ($w > $this->options['full-size'] || $h > $this->options['full-size']) {
                    break;
                }
            }
        }

        // ==================================================================
        // ==== on redimensionne les images et on sauve
        // ==================================================================

        $img = str_replace('\\', '/', $img); // pour windows
        if ($this->options['intro-size'] > 0) {
            $img = (empty($img)) ? $img_src_intro : $img;
            if (!empty($img)) {
                $img_thumb = $this->make_thumbnail($img, 'intro');
                $content_images['image_intro'] = $this->normalize_path($img_thumb);
                $content_images['image_intro_alt'] = $this->triple_choix($this->options['intro-alt'], $img);
                $content_images['image_intro_caption'] = $this->triple_choix($this->options['intro-caption'], $img);
                $content_images['float_intro'] = $this->get_image_class($content_images['float_intro'], $this->options['intro-class']);
            }
        }
        if ($this->options['full-size'] > 0 && !empty($img)) {
            $img_thumb = $this->make_thumbnail($img, 'full');
            $content_images['image_fulltext'] = $this->normalize_path($img_thumb);
            $content_images['image_fulltext_alt'] = $this->triple_choix($this->options['full-alt'], $img);
            $content_images['image_fulltext_caption'] = $this->triple_choix($this->options['full-caption'], $img);
            $content_images['float_fulltext'] = $this->get_image_class($content_images['float_fulltext'], $this->options['full-class']);
        }

        // on sauve
        $this->update_content($content_images);
        // on recharge la page
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    } // end of function run


    /*
     * renvoie le texte pour le champ alt ou caption
     * @param string $val : valeur de l'attribut alt ou caption
     * @param string $img : chemin vers l'image
     * @return string : texte pour alt ou caption
     */
    public function triple_choix($val, $img)
    {
        switch ($val) {
            case 0:
                return '';
                break;
            case '':
                return $this->link_humanize($img);
                break;
            default:
                return $val ;
        }
    }

    /*
     * normalise le nom du fichier pour le champ Joomla
     */
    public function normalize_path($img)
    {
        list($w, $h) = getimagesize($img);
        $out = $img;
        $out .= '#joomlaImage://local-' . $img;
        $out .= '?width:' . $w . '?height:' . $h;
        return $out;
    }

    /*
    * retourne la valeur pour la classe CSS de l'image
    * @param string $old : valeur actuelle de la classe CSS
    * @param string $new : valeur de l'option inro-class ou full-class
    * @return string : valeur de la classe CSS
    */

    public function get_image_class($old, $new)
    {
        $out = $old; // par défaut, on garde la valeur actuelle
        $new = trim($new);
        if ($new) {
            if ($new[0] == '+') { // on ajoute la classe
                $new = ltrim($new, '+');
                $out = array_merge(explode(' ', $old), explode(' ', $new));
                $out = implode(' ', array_unique($out));
            } else {
                $out = $new; // on remplace les classes existantes
            }
        }
        return trim($out);
    }

    /*
     * crée une vignette de l'image source
     * @param string $imgSrc : chemin vers l'image source
     * @param string $type : 'intro' ou 'full'
     * @return string : chemin vers la vignette créée
     */

    public function make_thumbnail($imgSrc, $type)
    {
        $imgPath = pathinfo($imgSrc, PATHINFO_DIRNAME);
        $imgName = pathinfo($imgSrc, PATHINFO_FILENAME);
        $imgExt = strtolower(pathinfo($imgSrc, PATHINFO_EXTENSION));
        if (empty($this->options['subfolder-thumbs'])) {
            // on sauve dans un dossier images/upthumbs
            // format : /images/upthumbs/idarticle-ficname-type.ext
            $imgPath = 'images/upthumbs/';
            $imgDest = $imgPath . $this->article->id . '-' . $imgName . '-' . $type . '.' . $imgExt;
        } else {
            // on sauve dans un sous-dossier de l'image source
            // format : /pathfic/subfolder/ficname-type.ext
            $imgPath .= '/' . $this->options['subfolder-thumbs'].'/';
            $imgDest =  $imgPath . $imgName . '-' . $type . '.' . $imgExt;
        }
        // création sous-dossier
        if (! is_dir(JPATH_ROOT . '/' . $imgPath)) {
            if (! @mkdir(JPATH_ROOT . '/' . $imgPath, 0755, true) && ! is_dir(JPATH_ROOT . '/' . $imgPath)) {
                throw new RuntimeException('There was a file permissions problem in folder \'' . $imgPath . '\'');
            }
        }

        // info sur image
        list($wSrc, $hSrc, $typeSrc) = getimagesize($imgSrc);
        $coef = $this->options[$type.'-size'] / max($wSrc, $hSrc);
        $wDest = intval($wSrc * $coef);
        $hDest = intval($hSrc * $coef);

        // traitement
        if ($imgExt == 'png') { // PNG
            $img = imagecreatefrompng($imgSrc);
            if ($img === false) {
                $this->msg_error('PNG-file-corrupt : '. $imgSrc);
                return;
            }
            $imgNew = imagecreatetruecolor($wDest, $hDest);
            imagealphablending($imgNew, false);
            imagesavealpha($imgNew, true);
            $transparency = imagecolorallocatealpha($imgNew, 255, 255, 255, 127);
            imagefilledrectangle($imgNew, 0, 0, $wDest, $hDest, $transparency);
            imagecopyresampled($imgNew, $img, 0, 0, 0, 0, $wDest, $hDest, $wSrc, $hSrc);
            imagepng($imgNew, $imgDest, 9);
        } elseif ($imgExt == 'webp') { // WEBP 18
            $img = imagecreatefromwebp($imgSrc);
            $imgNew = imagecreatetruecolor($wDest, $hDest);
            imagecopyresampled($imgNew, $img, 0, 0, 0, 0, $wDest, $hDest, $wSrc, $hSrc);
            imagewebp($imgNew, $imgDest);
        } elseif ($imgExt == 'jpg' || $imgExt == 'jpeg') { // JPG 18
            $img = imagecreatefromjpeg($imgSrc);
            $imgNew = imagecreatetruecolor($wDest, $hDest);
            imagecopyresampled($imgNew, $img, 0, 0, 0, 0, $wDest, $hDest, $wSrc, $hSrc);
            imagejpeg($imgNew, $imgDest);
        } else {
            $this->msg_error('error-type-image : ', $imgSrc);
        }
        // le chemin vers la vignette créée
        return $imgDest;
    }

    /*
     * Enregistre les images dans la base de données
     */
    public function update_content($images)
    {
        $images = json_encode($images);

        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->createQuery();

        $query->update($db->quoteName('#__content'))
            ->set($db->quoteName('images') . ' = ' . $db->quote($images))
            ->where($db->quoteName('id') . ' = ' . $this->article->id);

        $db->setQuery($query);

        $result = $db->execute();
    }
}

// class
