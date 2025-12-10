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
use Lomart\Plugin\Content\Up\Helper\UpHelper;

class jmodules_list extends Lomart\Plugin\Content\Up\Extension\Up
{

    function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    function run()
    {
        // lien vers la page de demo (vide=page sur le site de UP)
        UpHelper::set_demopage($this);

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
        $options = UpHelper::ctrl_options($this,$options_def);
        $options['template'] = UpHelper::get_bbcode($this,$options['template'], false);
        $options['model-note'] = UpHelper::get_bbcode($this,$options['model-note'], false);
        $options['no-content-html'] = UpHelper::get_bbcode($this,$options['no-content-html'], false);

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
        $client = UpHelper::ctrl_argument($this,$options['client'], ',0,1', false);

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
            UpHelper::msg_info($this,htmlentities($debug), 'Requete SQL');
        }
        $resbrut = $db->loadAssocList();

        // === Lecture des notes webmaster
        $notes_file = $this->actionPath . 'custom/info.ini';
        if (file_exists($notes_file)) {
            $notes = UpHelper::load_inifile($this,$notes_file, true);
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
        UpHelper::get_attr_style($this,$item_attr, $options['item-class'], $options['item-style']);

        foreach ($results as $res) {
            $out = $options['template'];
            UpHelper::kw_replace($this,$out, 'id', $res['id']);
            UpHelper::kw_replace($this,$out, 'client', $res['client']);
            UpHelper::kw_replace($this,$out, 'position', $res['position']);
            UpHelper::kw_replace($this,$out, 'module', $res['module']);
            UpHelper::kw_replace($this,$out, 'title', $res['title']);
            UpHelper::kw_replace($this,$out, 'state', $state[abs($res['published'])]);
            UpHelper::kw_replace($this,$out, 'ordering', $res['ordering']);
            $str = ($res['language'] == '*') ? '' : $res['language'];
            UpHelper::kw_replace($this,$out, 'language', $str);
            $str = ($res['note'] == '') ? '' : sprintf($options['model-note'], $res['note']);
            UpHelper::kw_replace($this,$out, 'note', $str);

            $html[] = UpHelper::set_attr_tag($this,$options['item-tag'], $item_attr, $out);
        }
        // les modules
        $out = implode(PHP_EOL, $html);

        // le bloc principal
        if ($options['main-tag'] != '0') {
            $main_attr['id'] = $options['id'];
            UpHelper::get_attr_style($this,$main_attr, $options['main-class'], $options['main-style']);
            $out = UpHelper::set_attr_tag($this,$options['main-tag'], $main_attr, $out);
        }

        return $out;
    }

    // run
}

// class



