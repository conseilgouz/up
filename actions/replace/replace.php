<?php

/**
 * Remplace un texte par un autre dans le contenu entre les shortcodes
 *
 * syntaxe {up replace=ancien:nouveau, ...}
 *
 * @version  UP-5.1
 * @author   Lomart
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Expert
 *
 */
defined('_JEXEC') or die();

class replace extends upAction
{
    public function init()
    {
        return true;
    }

    public function run()
    {

        // si cette action a obligatoirement du contenu
        if (! $this->ctrl_content_exists()) {
            return false;
        }

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // liste des remplacements sous la forme ancien:nouveau, ... BBcode admis
            'sep-item' => ',', // séparateur entre les groupes ancien:nouveau. virgule par défaut
            'sep-oldnew' => ':', // séparateur entre les parties recherche et remplace. 2 points par défaut
            'tags' => '', // vide: conserve les balises autorisées par défaut, liste (a,b,p) ou en plus des balises par défaut (+a,b,p)
            'regex' => '0' // 1 si la partie recherche est une expression régulière
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);
        $options['tags'] = str_replace(',', '|', $options['tags']);
        $options[__class__] = strip_tags($options[__class__]);

        $list = $this->get_bbcode($options[__class__], $options['tags']);
        $replace = $this->strtoarray($list, $options['sep-item'], $options['sep-oldnew'], false);

        foreach ($replace as $old => $new) {
            if ($options['regex']) {
                $this->content = preg_replace($old, $new, $this->content);
            } else {
                $this->content = str_ireplace($old, $new, $this->content);
            }
        }
        return $this->content;
    }

    // run
}

// class
