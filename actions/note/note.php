<?php

/**
 * Ajouter des commentaires visibles dans un éditeur WYSIWYG et pas sur le site
 *
 * syntax 1 {up note=texte_commentaire}
 * syntax 2 {up note=texte_commentaire} contenu {/up note}
 *
 * @author   LOMART
 * @version  UP-1.9.5
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Editor
 */
defined('_JEXEC') or die();

class note extends upAction
{

    function init()
    {
        return true;
    }

    function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            __class__ => 'lang[en=hidden text. HTML allowed;fr=texte masqué. HTML autorisé]' // texte à masquer
        );
        
        // code en retour
        return '';
    }

    // run
}

// class
