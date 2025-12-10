<?php

/**
 * ajoute du code ou un fichier JS ou JQuery
 *
 * Par défaut, le code est ajouté dans le head.
 * Si le paramètre principal est body, le code est inséré à la position du shortcode
 *
 * syntaxe {up addScript=body | jquery }code ou fichier{/up addScript}
 *
 * @author   LOMART
 * @version  UP-1.3.1
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags     Expert
 */

/*
 * v2.2  - nettoyage balises P et BR.
 *    	 - conversion des entités HTML créées par les éditeurs wysiwyg
 */

defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;
use Lomart\Plugin\Content\Up\Helper\UpHelper;

class addscript extends Lomart\Plugin\Content\Up\Extension\Up
{
    public function init()
    {
        // aucune
    }

    public function run()
    {

        // si cette action a obligatoirement du contenu
        if (!UpHelper::ctrl_content_exists($this)) {
            return false;
        }

        // lien vers la page de demo (vide=page sur le site de UP)
        UpHelper::set_demopage($this);

        $options_def = array(
            __class__ => 'head', // ou body pour insérer le code à la position du shortcode
            'jquery' => 0, // 1: entoure avec appel jquery (sauf fichier)
            'filter' => '', // conditions. Voir doc action filter  (v1.8)
            'id' => '' // identifiant
        );

        $options = UpHelper::ctrl_options($this,$options_def);

        // === Filtrage
        if (UpHelper::filter_ok($this,$options['filter']) !== true) {
            return '';
        }

        // dans head, sauf saisie de n'importe quoi pour addscript
        $inHead = ($options[__class__] == 'head');

        $out = '';

        // si fichier
        if (strtolower(substr($this->content, -3, 3)) == '.js') {
            $fic = $this->content;
            if ((stripos($fic, '://') == false) && (substr($fic, 0, 2) != '//')) {
                $fic = Uri::root() . $fic;
            }
            UpHelper::load_file($this,$fic); //HTMLHelper::script($fic);
        } else {
            //v2.2 annuler les entites HTML creees par TinyMCE
            $content = html_entity_decode($this->content);
            //v2.2 suppression saut ligne
            $content = str_ireplace('<p>', '', $content);
            $content = str_ireplace('</p>', '', $content);
            $content = str_ireplace('<br>', '', $content);
            $content = str_ireplace('<br />', '', $content);
            // ajout script
            if ($options['jquery']) { //v4
                $out = UpHelper::load_jquery_code($this,$content, $inHead);
            } else {
                $out = UpHelper::load_js_code($this,$content, $inHead);
            }
        }

        // -- code en retour si position shortcode
        return $out;
    }

    // run
}

// class addcsshead
