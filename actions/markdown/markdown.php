<?php

/**
 * Affiche du contenu MARKDOWN provenant d'un fichier ou saisi entre les shortcodes
 *
 * syntaxe 1:  {up markdown}contenu{/up markdown}
 * syntaxe 2:  {up markdown=nom_fichier_md}
 *
 * Utilisation : afficher un fichier changelog.md
 *
 *
 * @author   LOMART
 * @version  UP-1.3
 * @credit   <a href="https://github.com/erusev/parsedown">erusev/parsedown</a>
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    File
 */
/*
 * v1.3 - suppression commentaires YAML et gestion chemin images
 * v2.7 - fix lecture fichier
 * v5.2 - update parsedown 1.6->1.8
 */
defined('_JEXEC') or die();

class markdown extends upAction
{
    public function init()
    {
        require_once 'Parsedown.php';

        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // chemin et nom du fichier markdown ou vide pour contenu
            'strip-tags' => 1, // 0 pour conserver les tags HTML dans le contenu saisi entre les shortcodes. Ils sont toujours conservés si la source est un fichier.
            /* [st-css] Style CSS*/
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc (obsolète)
            'style' => '' // classes et styles pour bloc
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // récupération du contenu
        // 1 - le texte entre les shortcodes (sans html)
        $content = $this->content;
        if ($content and $options['strip-tags']) { // v2.1
            $content = strip_tags($content);
        }
        // 2 - le contenu d'un fichier
        $filename = $options[__class__];
        if ($content == '' and $filename != '') {
            $content = $this->get_html_contents($filename);
        }
        if ($content == '') {
            return $this->msg_inline('Markdown - content not found ' . $filename);
        }
        // attributs du bloc principal
        $attr_main = array();
        $this->get_attr_style($attr_main, $options['class'], $options['style']);

        $Parsedown = new Parsedown();
        $content = $Parsedown->text($content);

        // code en retour
        $out = $this->set_attr_tag('div', $attr_main);
        $out .= $content;
        $out .= '</div>';

        return $out;
    }

    // run
}

// class
