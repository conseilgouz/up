<?php

/**
 * liste des modules sur le site
 *
 * syntaxe : {up jmodules-list=position ou client_id}
 *
 * MOTS-CLES:
 * ##id## ##client## ##position## ##module## ##title##
 * ##state## ##note## ##ordering## ##language##
 *
 * @author   LOMART
 * @version  UP-1.8
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Joomla
 */
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

class jmodules_list extends upAction
{

    function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    function run()
    {
        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        $options_def = array(
            /* [st-select] Critères de sélection des modules */
            __class__ => '', // prefset ou position(s). vide=tous les modules site
            'position-exclude' => '0', // 1= toutes les positions sauf celles passées en paramètre principal
            'client' => '0', // 0=site, 1=admin, 2=tous
            'module' => '', // nom du module. ex: LM-Custom-SITE
            'module-exclude' => '0', // 1= tous les modules sauf ceux passés au paramètre module
            'actif-only' => '0', // 1 pour lister les extensions dépubliées
            'order' => 'position, ordering, title', // ordre de tri. sépérateur virgule
            'no-content-html' => '[p]aucun module a cette position[/p]', // retour si aucune catégorie trouvée
            /* [st-main] Balise et style du bloc principal */
            'main-tag' => 'ul', // balise pour le bloc englobant tous les modules. 0 pour aucun
            'id' => '', // identifiant
            'main-style' => '', // classes et styles inline pour bloc principal
            'main-class' => '', // classe(s) pour bloc principal (obsolète)
            /* [st-item] Balise et style d'un bloc module */
            'item-tag' => 'li', // balise pour un module. 0 pour aucun
            'item-style' => '', // classes et styles inline pour bloc ligne
            'item-class' => '', // classe(s) pour bloc ligne (obsolète)
            /* [st-model] Modèle de présentation */
            'template' => '\[##position##\]  [b class="##state##"][/b] [b]##title##[/b] [small] (id:##id## - ##module##) ##language##[/small] ##note##', // modèle de mise en page.
            'model-note' => '[i class="t-blue"]%s[/i]', // présentation pour ##note##
            'state-list' => 'icon-unpublish t-rouge, icon-publish t-vert, icon-trash t-gris', // liste de choix : inactif, actif &#x1f534
            /* [st-css] Style CSS */
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // ======> fusion et controle des options
        $options = $this->ctrl_options($options_def);
        $options['template'] = $this->get_bbcode($options['template'], false);
        $options['model-note'] = $this->get_bbcode($options['model-note'], false);
        $options['no-content-html'] = $this->get_bbcode($options['no-content-html'], false);

        // === Consolidation des options
        // balise HTML
        $options['main-tag'] = ($options['main-tag'] == '0') ? '' : $options['main-tag'];
        $options['item-tag'] = ($options['item-tag'] == '0') ? '' : $options['item-tag'];
        $state = explode(',', $options['state-list'] . ',,');

        // ======
        // SQL : position pour list
        // ======
        // where sur positions
        if ($options[__class__]) {
            $where_position = ($options['position-exclude'] == '0') ? '' : ' NOT';
            foreach (explode(',', $options[__class__]) as $position) {
                if (trim($position) != '')
                    $positionlist[] = '\'' . trim($position) . '\'';
            }
            $where_position .= ' IN (' . trim(implode(',', $positionlist)) . ')';
        }
        // where sur module
        if ($options['module']) {
            $where_module = ($options['module-exclude'] == '0') ? '' : ' NOT';
            foreach (explode(',', $options['module']) as $module) {
                if (trim($module) != '')
                    $modulelist[] = '\'' . trim($module) . '\'';
            }
            $where_module .= ' IN (' . trim(implode(',', $modulelist)) . ')';
        }

        // SQL : where sur client_id
        $client = $this->ctrl_argument($options['client'], ',0,1', false);

        // === RECUP MODULES
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery();
        $query->select('*');
        $query->from($db->quoteName('#__modules'));
        if ($options[__class__])
            $query->where($db->quoteName('position') . $where_position);
        if ($options['module'])
            $query->where($db->quoteName('module') . $where_module);
        if ($client != '')
            $query->where($db->quoteName('client_id') . '=' . $db->quote($client));
        if ($options['order'] != '')
            $query->order($options['order']);

        $db->setQuery($query);
        $debug = $query->__toString();
        if (isset($this->options_user['debug'])) {
            $debug = $query->__toString();
            $this->msg_info(htmlentities($debug), 'Requete SQL');
        }
        $resbrut = $db->loadAssocList();

        // === Lecture des notes webmaster
        $notes_file = $this->actionPath . 'custom/info.ini';
        if (file_exists($notes_file)) {
            $notes = $this->load_inifile($notes_file, true);
            $notes = ($notes === false) ? array() : $notes;
        }
        // ces caractéres sont a supprimer du nom des extensions
        // pour éviter les erreurs dans le fichier info.ini
        $note_name_char_exclude = explode(',', '!,@');

        // == Consolider et trier le résultat
        foreach ($resbrut as $res) {
            if ($options['actif-only'] && $res['enabled'] == '0')
                continue;
            $res['client'] = ($res['client_id'] == '0') ? 'site' : 'admin';

            // on ajoute l'extension, sauf si noté 0 dans info.ini
            if ($res['note'] != '0') {
                $results[] = $res;
            }
        }

        // == si aucun résultat
        if (empty($results))
            return $options['no-content-html'];

        // ==
        // === MISE EN FORME
        // ==
        $item_attr = array();
        $this->get_attr_style($item_attr, $options['item-class'], $options['item-style']);

        foreach ($results as $res) {
            $out = $options['template'];
            $this->kw_replace($out, 'id', $res['id']);
            $this->kw_replace($out, 'client', $res['client']);
            $this->kw_replace($out, 'position', $res['position']);
            $this->kw_replace($out, 'module', $res['module']);
            $this->kw_replace($out, 'title', $res['title']);
            $this->kw_replace($out, 'state', $state[abs($res['published'])]);
            $this->kw_replace($out, 'ordering', $res['ordering']);
            $str = ($res['language'] == '*') ? '' : $res['language'];
            $this->kw_replace($out, 'language', $str);
            $str = ($res['note'] == '') ? '' : sprintf($options['model-note'], $res['note']);
            $this->kw_replace($out, 'note', $str);

            $html[] = $this->set_attr_tag($options['item-tag'], $item_attr, $out);
        }
        // les modules
        $out = implode(PHP_EOL, $html);

        // le bloc principal
        if ($options['main-tag'] != '0') {
            $main_attr['id'] = $options['id'];
            $this->get_attr_style($main_attr, $options['main-class'], $options['main-style']);
            $out = $this->set_attr_tag($options['main-tag'], $main_attr, $out);
        }

        return $out;
    }

    // run
}

// class



