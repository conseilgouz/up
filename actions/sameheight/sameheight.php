<?php

/**
 *  égalise la hauteur des enfants directs du bloc (p ou div)
 *
 *   syntaxe:
 *   {up sameheight}
 *   < div>...< /div>
 *   < div>...< /div>
 *   {/up sameheight}
 *
 *   note: gestion de la largeur avec l'option "css-head" qui ajoute du code css dans le head
 *   exemple: css-head=.sameheight[float:left;width:30%;]
 *   <b style="color:red">Attention</b>: remplacer les {} par []
 *
 * @author   Lomart
 * @version  UP-1.0
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit   un vieux script de mes archives
 * @tags HTML
 */
defined('_JEXEC') or die();

class sameheight extends upAction
{

    function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        $this->load_file('sameheight.js');
        return true;
    }

    function run()
    {
        // cette action a obligatoirement du contenu
        if (! $this->ctrl_content_exists()) {
            return false;
        }
        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // inutilisé
            'id' => '',
            'class' => '', // classe(s) pour bloc principal
            'style' => '', // style pour bloc principal
            'css-head' => '' // code css libre dans le head. attention: ] au lieu de }
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // === ajout code CSS dans le header
        $this->load_css_head($options['css-head']);

        // === le code HTML
        // -- ajout options utilisateur dans la div principale
        $outer_div['id'] = $options['id'];
        $outer_div['class'] = 'sameheight';
        $this->add_class($outer_div['class'], $options['class']);
        $outer_div['style'] = $options['style'];

        // -- le code en retour
        $out = $this->set_attr_tag('div', $outer_div);
        $out .= $this->content;
        $out .= '</div>';

        return $out;
    }

    // run
}

// class
