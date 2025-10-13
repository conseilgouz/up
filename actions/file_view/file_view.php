<?php

/**
 * Force le chargement d'un fichier pour l'afficher en brut
 *
 * syntaxe {up file-view=chemin fichier}
 *
 * Utilisation :
 * - charger du contenu récurrent à plusieurs pages
 * - voir un fichier CSV
 * - voir le code HTML
 *
 * @author   LOMART
 * @version  UP-1.6
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    File
 */
defined('_JEXEC') or die;

/*
 * v2.9 : l'option block est renomée main-tag
 * v3.1 : pas de bloc pour main-tag=0
 */

class file_view extends upAction
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
            __class__ => '', // chemin et nom du fichier
            /* [st-style] Style du bloc */
            'main-tag' => 'div', // balise principale. 0 pour aucune
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            /* [st-format] Mise en forme du fichier */
            'HTML' => '0', // 0= aucun traitement, 1=affiche le code, ou liste des tags à garder (ex: img,a)
            'EOL' => '0', // forcer un retour à la ligne
            /* [st-annexe] style et options secondaires */
            'id' => '', // identifiant
            'css-head' => '', // règles CSS définies par le webmaster (ajout dans le head)
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // === lecture et nettoyage fichier
        $content = $this->get_html_contents($options['file_view']);
        $content = $this->clean_HTML($content, $options['HTML'], $options['EOL']);
        // === css-head
        $this->load_css_head($options['css-head']);

        // contenu brut
        if (empty($options['main-tag'])) {
            return $content;
        }

        // attributs du bloc principal
        $attr_main = array();
        $attr_main['id'] = $options['id'];
        $attr_main['class'] = $options['class'];
        $attr_main['style'] = $options['style'];

        // code en retour
        return $this->set_attr_tag($options['main-tag'], $attr_main, $content);
    }

    // run
}

// class
