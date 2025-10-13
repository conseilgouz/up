<?php

/**
 * Force le téléchargement d'un fichier
 *
 * Propose un lien sur un fichier.
 * Le fichier peut etre affiché (si prise en charge par navigateur client)
 * ou le téléchargement peut-etre forcé avec changement de nom optionnel
 * Une icône représentative est affichée devant le lien
 *
 * syntaxe :
 * {up file=nomfichier.ext | download | icon |target }
 * texte du lien
 * {/up file}
 *
 * @author   LOMART
 * @version  UP-1.2
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags   File
 */
defined('_JEXEC') or die();

class file extends upAction
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    public function run()
    {

        // si cette action a obligatoirement du contenu
        if (! $this->ctrl_content_exists()) {
            return false;
        }

        // lien vers la page de demo
        // - vide = page sur le site de UP
        // - URL complete = page disponible sur ce lien
        // - rien pour ne pas proposer d'aide
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // chemin et nom du fichier à télécharger
            'download' => '', // vide ou nom du fichier récupéré par le client
            'icon' => '32', // chemin et nom de l'icone devant le lien ou taille de l'icone selon extension du fichier (16 ou 32)
            'target' => '', // argument ou _blank si option sans argument
            'rel' => '', // nofollow, noreferrer, ...
            /* [st-annexe] style et options secondaires */
            'id' => '', // Identifiant
            'class' => '', // classe(s) pour la balise a
            'style' => '' // style inline pour la balise a
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // extension du fichier
        $ficext = strtolower(pathinfo($options[__class__], PATHINFO_EXTENSION));

        // ==== icone devant le lien
        // si icon sans argument
        if ($options['icon'] == '1') {
            $options['icon'] = $options_def['icon'];
        }
        //
        switch ($options['icon']) {
            case '0': // pas d'icone demandée
                $icon = '';
                break;
            case '16': // icone selon type fichier
            case '32':
                if (in_array($ficext, array(
                    'doc',
                    'exe',
                    'jpg',
                    'pdf',
                    'png',
                    'txt',
                    'xls',
                    'zip'
                ))) {
                    $icon = '<img src="' . $this->actionPath . 'img' . $options['icon'] . '/' . $ficext . '.png"> ';
                } else {
                    $icon = '<img src="' . $this->actionPath . 'img' . $options['icon'] . '/download.png"> ';
                }
                break;
            default: // icone indiquée dans shortcode
                $icon = '<img src="' . $options['icon'] . '"> ';
                break;
        }

        // === nom du fichier téléchargé
        switch ($options['download']) {
            case '': // pas de téléchargement
            case '0':
                $download = '';
                break;
            case '1': // téléchargement sous un nom humanisé
                $options['download'] = pathinfo($options[__class__], PATHINFO_FILENAME);
                $options['download'] .= '.' . $ficext;
                break;
            default: // le nom indiqué en forcant l'extension
                $options['download'] = pathinfo($options['download'], PATHINFO_FILENAME);
                $options['download'] .= '.' . $ficext;
                break;
        }

        // === cible du fichier
        switch ($options['target']) {
            case '0': // pas de cible
                $options['target'] = '';
                break;
            case '1': // par défaut dans une nouvelle fenetre
                $options['target'] = '_blank';
                break;
            default: // la cible indiquée
                $options['target'] = '';
                break;
        }

        // attributs du bloc principal
        $attr_main = array();
        $attr_main['class'] = $options['class'];
        $attr_main['style'] = $options['style'];
        $attr_main['href'] = $options[__class__];
        if (! empty($options['download'])) {
            $attr_main['download'] = $options['download'];
        }
        $attr_main['target'] = $options['target'];
        $attr_main['rel'] = $options['rel'];

        // code en retour
        $out = $this->set_attr_tag('a', $attr_main);
        $out .= $icon;
        $out .= $this->content;
        $out .= '</a>';

        return $out;
    }

    // run
}

// class
