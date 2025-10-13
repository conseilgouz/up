<?php

/**
 * liste des extensions installées sur le site
 *
 * syntaxe : {up jextensions-list=prefset ou type(s)}
 *
 * MOTS-CLES:
 * ##id## ##client## ##type## ##name-link## ##name## ##author## ##version## ##note## ##folder## ##state##
 *
 * @author   LOMART
 * @version  UP-1.7
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Joomla
 */

/*
 * v2.7 - ajout option 'author-exclude' pour J4
 * v5.1 - suppression valeur par défaut pour minimal-id
 */
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

class jextensions_list extends upAction
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
            __class__ => 'component,module,plugin', // nom d'un prefset ou un des types suivants : component,module,plugin,template
            'type-exclude' => '0', // 1= tous les types sauf ceux passés en paramètre principal
            'client' => '0', // 0=site, 1=admin, 2=tous
            'minimal-id' => '0', // pour exclure les composants du core Joomla 3.0
            'author-exclude' => 'Joomla! Project', // pour exclure les composants du core Joomla 4.0
            'actif-only' => '0', // 1 pour lister les extensions dépubliées
            'sort' => 'type,folder,name', // tri
            /* [st-model] Modèle de présentation */
            'template' => '##state####name##[small] ##client## ##type## ##folder## ##version## (id:##id##) ##author## [/small] ##note##', // modèle de mise en page. keywords+bbcode
            /* [st-main] Balise et style du bloc principal */
            'main-tag' => 'ul', // Balise pour bloc principal
            'style' => '', // classes et styles
            'id' => '', // identifiant
            /* [st-item] Balise pour les lignes */
            'item-tag' => 'li', // Balise pour blocs lignes
            /* [st-format] Format pour les mots-clés */
            'model-folder' => '/%s', // présentation pour ##folder##
            'model-version' => 'vers:%s', // présentation pour ##version##
            'model-note' => '[i class="t-blue"]%s[/i]', // présentation pour ##note##
            'state-list' => '[b style="color:red"]&#x2715 [/b]', // liste de choix : inactif, actif &#x1f534
            /* [st-css]  Style CSS */
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // ======> fusion et controle des options
        $options = $this->ctrl_options($options_def);
        $options['template'] = $this->get_bbcode($options['template'], false);
        $options['model-note'] = $this->get_bbcode($options['model-note'], false);
        // === Consolidation des options
        // balise HTML
        $options['main-tag'] = ($options['main-tag'] == '0') ? '' : $options['main-tag'];
        $options['item-tag'] = ($options['item-tag'] == '0') ? '' : $options['item-tag'];
        $state = explode(',', $this->get_bbcode($options['state-list']) . ',,'); // v31

        // SQL : type pour list
        foreach (explode(',', $options[__class__]) as $type) {
            if (trim($type) != '')
                $typelist[] = '\'' . trim($type) . '\'';
        }
        $type = trim(implode(',', $typelist));
        $exclude = ($options['type-exclude'] == '0') ? '' : 'NOT';
        // SQL : where sur client_id
        $client = $this->ctrl_argument($options['client'], ',0,1', false);
        // Critères de tri
        $sort_keys = explode(',', $options['sort']);

        // === RECUP EXTENSION
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from($db->quoteName('#__extensions'));
        if ($type) {
            $query->where($db->quoteName('type') . $exclude . ' IN (' . $type . ')');
        }
        if ($client != '')
            $query->where($db->quoteName('client_id') . '=' . $db->quote($client));
        $query->where($db->quoteName('extension_id') . '>' . $options['minimal-id']);
        $db->setQuery($query);
        if (isset($this->options_user['debug'])) {
            $debug = $query->__toString();
            // $debug .= '<br' . var_export($db->loadAssocList());
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

        // Mots à exclure du nom des extensions pour affichage
        $exclude_words = array();
        $exclude_words['package'] = array(
            'pkg_'
        );
        $exclude_words['component'] = array(
            'com_'
        );
        $exclude_words['module'] = array(
            'mod_'
        );
        $exclude_words['template'] = array();
        $tmp = 'plg_,actionlog_,sys_,system - ,system_,content_,editors_,editors-xtd_,quickicon_';
        $exclude_words['plugin'] = explode(',', $tmp);

        // == Consolider et trier le résultat
        foreach ($resbrut as $res) {
            if ($options['actif-only'] && $res['enabled'] == '0')
                continue;
            $res['client'] = ($res['client_id'] == '0') ? 'site' : 'admin';
            $res['id'] = $res['extension_id'];
            $infos = json_decode($res['manifest_cache']);
            if ($infos->author == $options['author-exclude'])
                continue;
            $res['author'] = (isset($infos->author)) ? $infos->author : '';
            $res['version'] = (isset($infos->version)) ? $infos->version : '';
            if (isset($infos->name)) {
                $res['name'] = $infos->name;
            }

            // nettoyage du nom
            $orig_name = $res['name'];
            $res['name'] = str_ireplace($exclude_words[$res['type']], '', $res['name']);
            // ajout des notes
            $res['note'] = '';
            $clean_name = str_replace($note_name_char_exclude, '', $res['name']);
            if (isset($notes[$clean_name])) {
                $res['note'] = $notes[$clean_name];
            }
            // on ajoute l'extension, sauf si noté 0 dans info.ini
            if ($res['note'] != '0') {
                $key = '';
                foreach ($sort_keys as $sort_key) {
                    $key .= $res[$sort_key] . chr(255);
                }
                $results[strtolower($key)] = $res;
            }
        }
        if (empty($results)) {
            return $this->msg_inline('no extension found with this options');
        }
        ksort($results);

        // === MISE EN FORME
        $attr_main['id'] = $options['id'];
        $this->get_attr_style($attr_main, $options['style']);

        foreach ($results as $res) {
            $out = $options['template'];
            $this->kw_replace($out, 'id', $res['id']);
            $this->kw_replace($out,'state', $state[$res['enabled']]);
            $this->kw_replace($out,'type', $res['type']);
            $str = ($res['folder'] == '') ? '' : sprintf($options['model-folder'], $res['folder']);
            $this->kw_replace($out, 'folder', $str);
            $this->kw_replace($out,'client', $res['client']);
            $this->kw_replace($out,'name', $res['name']);
            $this->kw_replace($out,'author', $res['author']);
            $str = ($res['version'] == '') ? '' : sprintf($options['model-version'], $res['version']);
            $this->kw_replace($out,'version', $str);
            $str = ($res['note'] == '') ? '' : sprintf($options['model-note'], $res['note']);
            $this->kw_replace($out,'note', $str);
            // ajout tag si demande
            if ($options['item-tag'])
                $out = $this->set_attr_tag($options['item-tag'], array(), $out);

            $html[] = $out;
        }
        if ($options['main-tag']) {
            return $this->set_attr_tag($options['main-tag'], $attr_main, implode(PHP_EOL, $html));
        } else {
            return implode(PHP_EOL, $html);
        }

        // run
        // Pass the array, followed by the column names and sort flags
        // $sorted = array_orderby($data, 'volume', SORT_DESC, 'edition', SORT_ASC);
        // https://www.php.net/manual/fr/function.array-multisort.php
        function array_orderby_orig()
        {
            $args = func_get_args();
            $data = array_shift($args);
            foreach ($args as $n => $field) {
                if (is_string($field)) {
                    $tmp = array();
                    foreach ($data as $key => $row)
                        $tmp[$key] = $row[$field];
                    $args[$n] = $tmp;
                }
            }
            $args[] = &$data;
            call_user_func_array('array_multisort', $args);
            return array_pop($args);
        }

        function array_orderby($data, $args)
        {
            foreach ($args as $n => $field) {
                if (is_string($field)) {
                    $tmp = array();
                    foreach ($data as $key => $row)
                        $tmp[$key] = $row[$field];
                    $args[$n] = $tmp;
                }
            }
            $args[] = &$data;
            call_user_func_array('array_multisort', $args);
            return array_pop($args);
        }
    }
}

// class




