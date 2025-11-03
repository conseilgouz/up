<?php

/**
 * Recherche une action dans le contenu des articles et/ou modules (params)
 *
 * syntaxe {up upsearch=action1,action2 | regex=... | module}
 *
 * Mots-clés pour template
 * ##id##  ##title##  ##title-link## ##subtitle##  ##cat## ##date-crea## ##date-modif##
 * ##text## : résultat recherche
 *
 * @version  UP-2.5
 * @author   Lomart
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    UP
 *
 */

/*
 * v2.6 - fix toutes les catégories pour J4
 * v2.9 - compatibilité PHP8 pour ##date-xxx##
 * v5.2 - neutralisation caractères spéciaux dans le texte
 */

defined('_JEXEC') or die();

use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Database\DatabaseInterface;

require_once('sort_text.php');

class upsearch extends upAction
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
        /* [st-sel]  Critères de recherche */
            __class__ => '', // liste des actions (ou synonyme) séparées par des virgules
            'regex' => '', // motif de recherche dans le shortcode ou dans tout le contenu si shortcode vide
            'cat' => '', // liste id des categories d'articles, séparateur:virgule. vide = toutes
            'module' => '', // pour recherche dans le champ 'params'. vide = tous, partie du nom du module
            'no-published' => 0, // 1 recherche dans tous les articles archivé, non publié, a la corbeille
            /* [st-form] Mise en forme du résultat*/
            'sort-by' => 'title', // tri pour article: title, ordering, created, modified, publish_up, id, ... ou text pour contenu recherche
            'sort-order' => 'asc', // ordre de tri : asc, desc
            'only-one' => 0, // 1 : afficher un seul resultat par article
            'maxlen' => '', // nombre de caractères maxi pour le resultat
            'template' => '[p]##id## ; ##title-link## ; "##text##"[/p]', // modèle pour retour. titre article et texte trouve pour tableau
            'date-format' => '%Y-%m-%d', // format pour les dates
            'target' => '_blank', // pour le lien sur article
            /* [st-css] Style CSS*/
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // variables generales
        $state_info = array(
            '-2' => '&#x1F5D1;',
            '0' => '&#x2716;',
            '1' => '',
            '2' => '&#x25A4;'
        );

        // consolidation des options pour affichage par debug
        $str = trim(@$this->options_user['regex'] ?? '');
        if ($str) {
            if (strpos('#/@~$', $str[0]) === false) {
                $str = '#' . $str . '#i';
            }
            if (strpos(substr($str, - 4, 4), $str[0]) === false) {
                $str = $str . $str[0];
            }
            $this->options_user['regex'] = $str;
        }

        // fusion et controle des options
        $this->options = $this->ctrl_options($options_def);

        // controle cle de tri
        $list_sortkey = 'title,ordering, created,modified,publish_up, id, hits, text';
        $this->options['sort-by'] = $this->ctrl_argument($this->options['sort-by'], $list_sortkey);
        $this->options['sort-order'] = $this->ctrl_argument($this->options['sort-order'], 'asc,desc');
        // bbcode template
        $this->options['template'] = $this->get_bbcode($this->options['template']);

        // === CSS-HEAD
        $this->load_css_head($this->options['css-head']);

        // === LE TYPE DE RECHERCHE
        if (empty($this->options[__class__])) {
            // -- recherche regex sur totalite contenu
            $regex = $this->options['regex'];
            $regex_2 = '';
        } else {
            foreach (array_map('trim', explode(',', $this->options[__class__])) as $action_name) {
                $regex = (empty($regex)) ? '' : $regex . '|';
                // recherche du nom original de l'action
                $action_name = str_replace('-', '_', $action_name);
                if (isset($this->dico[$action_name])) {
                    $action_name = $this->dico[$action_name];
                }
                // on accepte tiret et underscore
                $regex .= str_replace('_', '[_-]', $action_name);
                // recherche synonyme
                $synonym = $this->get_dico_synonym($action_name);
                if ($synonym) {
                    $regex .= '|' . str_replace(',', '|', $synonym);
                }
            }
            // seconde recherche dans les options du shortcode
            $regex_2 = $this->options['regex'];
            // uniquement le shortcode ouvrant
            $regex = '#\{up (?:' . $regex . ').*\}#Uis';
        }

        // ================================================= LES ARTICLES
        // --- les categories recherchees
        if (empty($this->options['cat'])) {
            // toutes les catégories = liste cat de niveau 1 - v2.6
            if ($this->J4) {
                $this->categories =  Factory::getApplication()->bootComponent('com_content')->getCategory();
                $catniv1 = $this->categories->get()->getChildren();
                foreach ($catniv1 as $cat) {
                    $this->options['cat'] .= $cat->id . ',';
                }
            }
        }
        $catid = explode(',', $this->options['cat']);

        // --- les articles
        $model = new Joomla\Component\Content\Site\Model\ArticlesModel(array('ignore_request' => true));
        if (is_bool($model)) {
            return 'Aucune catégorie';
        }

        // Set application parameters in model
        $app = Factory::getApplication();
        $appParams = $app->getParams();
        $model->setState('params', $appParams);

        // Category filter
        $model->setState('filter.category_id', $catid);

        // Ordering
        if ($this->options['sort-by'] != 'text') {
            $model->setState('list.ordering', 'a.' . $this->options['sort-by']);
            $model->setState('list.direction', $this->options['sort-order']);
        }
        // published
        if (empty($this->options['no-published'])) {
            $model->setState('filter.published', 1);
        }

        $items = $model->getItems();

        // === RECHERCHE ARTICLES
        foreach ($items as $item) {
            if (preg_match_all($regex, $item->introtext . $item->fulltext, $tmp_all)) {
                foreach ($tmp_all[0] as $tmp) {
                    if ($regex_2 && preg_match($regex_2, $tmp) !== 1) {
                        continue;
                    }
                    // tableau resultat
                    $result = array();
                    $result['id'] = $item->id;
                    $result['cat'] = $item->category_title;
                    $result['state'] = $state_info[$item->state];
                    list($result['title'], $result['subtitle']) = explode('~', $item->title . '~');
                    $result['title'] = $result['state'] . $result['title'];
                    if (stripos($this->options['template'], '##title-link##') !== false) {
                        // --- lien vers l'article
                        $url = '';
                        $slug = ($item->alias) ? ($item->id . ':' . $item->alias) : $item;
                        $catslug = ($item->category_alias) ? ($item->catid . ':' . $item->category_alias) : $item->catid;
                        $route = Route::_(RouteHelper::getArticleRoute($slug, $catslug, $item->language));
                        $url = Route::_($route);
                        $result['title-link'] = '<a href="' . $url . '" target="' . $this->options['target'] . '">' . $result['title'] . '</a>';
                    }
                    $result['date-crea'] = $this->up_date_format($item->created, $this->options['date-format']);
                    $result['date-modif'] = $this->up_date_format($item->modified, $this->options['date-format']);

                    // mise en forme resultat
                    if ($this->options['maxlen'] && strlen($tmp) > (int) $this->options['maxlen']) {
                        $tmp = substr($tmp, 0, (int) $this->options['maxlen']) . ' ...';
                    }
                    // on neutralise les shortcodes
                    $result['text'] = str_replace(array(
                        '{',
                        '}',
                        '[',
                        ']'
                    ), array(
                        '<span>{</span>',
                        '<span>}</span>',
                        '<span>[</span>',
                        '<span>]</span>'
                    ), $tmp);
                    // on ajoute au tableau global
                    $results[] = $result;
                    // une seule occurrence par article
                    if ($this->options['only-one']) {
                        break;
                    }
                }
            }
        }
        if (empty($results)) { // v2.9
            return '';
        }

        // tri resultats
        /*
         * dans sort_text.php
         * function text_sort_asc($a, $b) {
         * return $a["text"] > $b["text"];
         * }
         * function text_sort_desc($a, $b) {
         * return $a["text"] < $b["text"];
         * }
         */
        if ($this->options['sort-by'] == 'text') {
            usort($results, 'text_sort_' . $this->options['sort-order']);
        }

        // --- préparation résultats
        foreach ($results as $result) {
            $tmpl = $this->options['template'];
            foreach ($result as $k => $v) {
                if ($k == 'text') {
                    $v =  htmlspecialchars($v);
                    $v = nl2br($v);
                }
                $tmpl = str_ireplace('##' . $k . '##', $v, $tmpl);
            }
            $html[] = $tmpl;
        }

        // ================================================= LES MODULES
        if ($this->options['module']) {
            // --- recup
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->createQuery();
            $query->select('*');
            $query->from($db->quoteName('#__modules'));
            // uniquement module site
            $query->where($db->quoteName('client_id') . '=0');
            if ($this->options['module'] != '1') {
                $query->where($db->quoteName('module') . ' LIKE ' . $db->quote('%' . $this->options['module'] . '%'));
            }
            if (empty($this->options['no-published'])) {
                $query->where($db->quoteName('published') . '=1');
            }

            $db->setQuery($query);
            $debug = $query->__toString();
            if (isset($this->options_user['debug'])) {
                $debug = $query->__toString();
                $this->msg_info(htmlentities($debug), 'Requete SQL');
            }
            $items = $db->loadObjectList();
            // --- RESULTATS MODULES
            foreach ($items as $item) {
                $params = json_decode($item->params, true); // mod_lmcustom
                $params[] = $item->content; // mod_custom
                foreach ($params as $key => $val) {
                    if (is_string($val) && strlen($val) > 15) {
                        if (preg_match($regex, $val, $tmp)) {
                            $out = $tmp[0];
                            if ($regex_2 && preg_match($regex_2, $out) !== 1) {
                                continue;
                            }
                            // mise en forme resultat
                            if ($this->options['maxlen'] && strlen($out) > (int) $this->options['maxlen']) {
                                $out = substr($out, 0, (int) $this->options['maxlen']) . ' ...';
                            }
                            // on neutralise les shortcodes
                            $out = str_replace(array(
                                '{',
                                '}',
                                '[',
                                ']'
                            ), array(
                                '<span>{</span>',
                                '<span>}</span>',
                                '<span>[</span>',
                                '<span>]</span>'
                            ), $out);

                            $tmpl = $this->options['template'];
                            $tmpl = str_ireplace('##id##', $item->id, $tmpl);
                            $tmpl = str_ireplace('##title##', $state_info[(int) $item->published] . '<b>Module : </b>' . $item->title, $tmpl);
                            $tmpl = str_ireplace('##title-link##', $state_info[(int) $item->published] . '<b>Module </b>: ' . $item->title, $tmpl);
                            $tmpl = str_ireplace('##subtitle##', '', $tmpl);
                            $tmpl = str_ireplace('##date-crea##', 'n.c.', $tmpl);
                            $tmpl = str_ireplace('##date-modif##', 'n.c.', $tmpl);
                            $tmpl = str_ireplace('##cat##', '**MODULE**', $tmpl);
                            if (strpos($tmpl, '##text##') !== false) {
                                $out =  htmlspecialchars($v);
                                $out = nl2br($out);
                                if (empty($key)) {
                                    $tmpl = str_ireplace('##text##', '<b>content: </b>' . $out, $tmpl);
                                } else {
                                    $tmpl = str_ireplace('##text##', '<b>params[' . $key . ']: </b>' . $out, $tmpl);
                                }
                            }
                            $html[] = $tmpl;
                            // une seule occurrence par article
                            if ($this->options['only-one']) {
                                break;
                            }
                        }
                    }
                }
            }
        }
        // === RETOUR
        // attributs du bloc principal
        $attr_main = array();
        $attr_main['id'] = $this->options['id'];
        $this->get_attr_style($attr_main, $this->options['class'], $this->options['style']);

        // code en retour
        $out = (empty($html)) ? '' : implode(PHP_EOL, $html);
        $out = $this->set_attr_tag('div', $attr_main, $out);

        return $out;
    }

    // run
}

// class
