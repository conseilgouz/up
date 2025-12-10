<?php

/**
 * Utilise tous les fichiers d'un dossier pour construire un article
 *
 * syntaxe {up file-in-content=fichier ou dossier}
 *
 * Mots-clés
 * ##title## : le nom du fichier txt
 * ##content## : le contenu du fichier txt
 * ##image## : fichier image de même nom que le fichier texte
 * ##date## : la date du fichier
 *
 * @author   LOMART
 * @version  UP-2.8..1
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    File
 */
defined('_JEXEC') or die();

use Lomart\Plugin\Content\Up\Helper\UpHelper;

class file_in_content extends Lomart\Plugin\Content\Up\Extension\Up
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        UpHelper::set_demopage($this);

        $options_def = array(
            __class__ => '', // chemin et nom du fichier
            'file-mask' => '', // pour sélectionner les fichiers d'un dossier sur le serveur. ex: fic-*
            'maxi' => '', // nombre maxima de fichier (selon sort-order).
            'sort-order' => 'desc', // sens du tri. asc ou desc. Tri selon date
            'msg-no-file' => 'en=no file;fr=aucun fichier', // message si aucun fichier PDF dans le dossier
            'template' => '[h2]##title##[/h2][div]##image####content##[/div]', // modèle pour affichage
            /* [st-main] balise et style du bloc contenant les différents fichiers */
            'main-tag' => 'div', // balise pour le bloc externe (si main-tag<>0)
            'main-style' => '', // idem class & style
            /* [st-item] balise et style du bloc d'un fichier */
            'item-tag' => 'div', // balise pour le bloc d'un fichier (si item-tag<>0)
            'item-style' => '', // style et classe pour le bloc d'un fichier
            /* [st-img] Définition pour ##image## */
            'image-style' => '', // style et classe pour l'image
            'image-popup' => '0', // 1 pour afficher en grand dans fenêtre modale
            'image-extension' => 'jpg,JPG,jpeg,JPEG,png,PNG', // liste des extensions acceptées
            /* [st-format] Formattage */
            'HTML' => '0', // 0= aucun traitement, 1=affiche le code, ou liste des tags à garder (ex: img,a)
            'EOL' => '0', // forcer un retour à la ligne
            'format-date' => 'lang[en=m/d/Y H:i;fr=d/m/Y H:i]', // format pour la date. ex: 'd/m/Y H:i'
            /* [st-annexe] style et options secondaires */
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc (si main-tag<>0)
            'style' => '', // idem class
            'css-head' => '' // règles CSS définies par le webmaster (ajout dans le head)
        );

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);

        if (empty($options[__class__])) {
            return '';
        }
        // force le tag item pour contenir le style
        if (empty($options['item-tag']) && ! empty($options['item-style'])) {
            $options['item-tag'] = 'div';
        }

        $attr_image = array();
        UpHelper::get_attr_style($this,$attr_image, $options['image-style']);
        $attr_item = array();
        UpHelper::get_attr_style($this,$attr_item, $options['item-style']);

        // === lecture et nettoyage fichier
        if (strpos($options[__class__], '//') === false) {
            // sur le serveur du site : tous les fichiers du dossier
            $filepath = rtrim($options[__class__], '/\\');
            if (is_dir($filepath)) {
                $filepath .= '/'; // + RTRIM
                $filepath .= ($options['file-mask']) ? $options['file-mask'] : '*';
                $filepath .= '.txt';
                $files = glob($filepath, GLOB_BRACE);
                // === ajout timestamp aux nouveaux fichiers
                for ($i = 0; $i < count($files); $i++) {
                    if (UpHelper::check_timestamp($this,$files[$i]) === false) {
                        $files[$i] = UpHelper::add_timestamp($this,$files[$i]);
                    }
                }

                // === tri des fichiers
                if ($options['sort-order'] == 'asc') {
                    sort($files);
                } else {
                    rsort($files);
                }
                // === nombre maxi
                if ((int) $options['maxi'] > 0) {
                    $files = array_slice($files, 0, (int) $options['maxi']);
                }
            } else {
                // sur site distant : un seul fichier
                $files[] = $options[__class__];
            }
        }

        // === pas de fichier
        if (empty($files)) {
            return UpHelper::get_bbcode($this,$options['msg-no-file']);
        }

        // === Recuperation contenu
        foreach ($files as $file) {
            $pathinfo = pathinfo($file);
            // $ext = strtolower($pathinfo['extension']);
            // --- récupération des valeurs pour mots-clés
            $content = UpHelper::get_html_contents($this,$file);
            $content = UpHelper::clean_HTML($this,$content, $options['HTML'], $options['EOL']);
            if (UpHelper::check_timestamp($this,$file)) {
                list($date, $title) = explode('-', $pathinfo['basename'], 2);
                $datetime = strtotime($date);
            } else {
                $title = $pathinfo['basename'];
                $datetime = filemtime($file);
            }
            $date = date($options['format-date'], $datetime);
            // --- image. on accepte avec ou sans l'extension du fichier principal
            $list_image = glob($file . '.{' . $options['image-extension'] . '}', GLOB_BRACE);
            if (empty($list_image)) {
                $list_image = glob($pathinfo['dirname'] . '/' . $pathinfo['filename'] . '.{' . $options['image-extension'] . '}', GLOB_BRACE);
            }
            $image = (empty($list_image)) ? '' : $list_image[0];
            // préparation retour
            $tmpl = UpHelper::get_bbcode($this,$options['template']);
            UpHelper::kw_replace($this,$tmpl, 'title', UpHelper::link_humanize($this,$title));
            UpHelper::kw_replace($this,$tmpl, 'content', $content);
            UpHelper::kw_replace($this,$tmpl, 'date', $date);
            if ($image) {
                $attr_image['src'] = $image;
                $image = UpHelper::set_attr_tag($this,'img', $attr_image);
            }
            UpHelper::kw_replace($this,$tmpl, 'image', $image);
            // le bloc pour un fichier
            if (empty($options['main-tag']) && empty($options['item-style'])) {
                $html[] = $tmpl;
            } else {
                $html[] = UpHelper::set_attr_tag($this,$options['item-tag'], $attr_item, $tmpl);
            }
        }

        // === css-head
        UpHelper::load_css_head($this,$options['css-head']);

        // code en retour
        if (empty($options['main-tag'])) {
            return implode(PHP_EOL, $html);
        } else {
            // attributs du bloc principal
            $attr_main = array();
            $attr_main['id'] = $options['id'];
            UpHelper::get_attr_style($this,$attr_main, $options['main-style'], $options['class'], $options['style']);
            return UpHelper::set_attr_tag($this,$options['main-tag'], $attr_main, implode(PHP_EOL, $html));
        }
    }

    // run

}

// class
