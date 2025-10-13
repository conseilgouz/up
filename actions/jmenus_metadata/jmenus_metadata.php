<?php

/**
 * liste des menus avec metadatas
 *
 * syntaxe : {up jmenus-metadata=menutype}
 *
 * MOTS-CLES MENUTYPE:
 * ##id## ##menutype## ##title## ##description##
 *
 * MOTS-CLES ITEM MENU:
 * ##id## ##title## ##title-link## ##menutype## ##note## ##access## ##language##
 * ##level## ##type## ##home##
 * ##image## icon ou class ?
 * ##publish_up## ##publish_down## ##index## ##follow##
 *
 * MOTS-CLES ARTICLES 
 * ##id##, ##title##, ##title-link', ##title-size##, ##alias##, ##state##, ##access##, ##featured##,
 * ##created##, ##modified##, ##publish_up##, ##publish_down##
 * ##catid##, ##catname##, ##language##, ##index##, ##follow##
 * ##featured_up## ##featured_down##
 * Les clés des champs JSON 
 * ##attribs.key## ##metada.key## ##images.key## ##urls.key##
 *
 * @author   LOMART
 * @version  UP-5.1
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Joomla
 */
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;

class jmenus_metadata extends upAction
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
            __class__ => '', // prefset, liste menutype (sep:virgule) ou vide pour tous
            'menutype-exclude' => '', // liste des menutypes non repris. (sep:virgule)
            'menuid' => '', // ID menu parent pour limiter à cette branche
            /* [st-main] Balise et style du bloc principal */
            'main-tag' => 'ul', // balise pour la liste des fichiers
            'style' => '', // classes et styles
            'id' => '',
            /* [st-model] Modèle de présentation */
            'public-only' => 0, // 1: visible par les robots, 0: visible par utilisateur courant
            'template-menutype' => '[h2]##title##[/h2]', // Modèle pour les lignes menutype
            'template-menu' => '##state[0:icon-unpublish t-rouge,1:,-2:icon-trash t-gris] # [span class="%%"] [/span] ####home[1:fa fa-home,0:] # [span]%%[/span] ####image##[b]##title-link##[/b] (id##id####language!* # - %%##) ##access!Accès public # [span class="bg-red t-white ph1"]%%[/span]####menutype## / ##type## ##publish_up>#now # [mark class="t-vert"] publié le %% [/mark]## ##publish_down>#now # [mark class="t-rouge"] dépublié le %% [/mark]####index=noindex # [span class="t-red"] %%[/span]## ##follow=nofollow # [span class="t-red"] %%[/span]####note # [div class="t-bleu i"]%%[/div]##', // Modèle pour les lignes menu
            'template-article' => '##featured[1:&#x2B50;,0:] # [span]%%[/span] ####title-link## (##id##) ##access!Accès public # [span class="bg-red t-white ph1"]%%[/span]## ##index=noindex # [span class="t-red"] %%[/span]####follow=nofollow # [span class="t-red"] %%[/span]##', // Modèle pour les lignes article
            /* [st-options] */
            'menu-state-show' => '1,0,2,-2', // liste des états affichés pour les menus : 0:inactif, 1:actif 2:archive, -2:poubelle
            'article-state-show' => '1,0,2,-2', // liste des états affichés pour les articles : 0:inactif, 1:actif 2:archive, -2:poubelle
            'article-sort-by' => 'title', // tri: title, ordering, created, modified, publish_up, id, hits, random
            'article-sort-order' => 'asc', // ordre de tri : asc, desc
            /* [st-format] Format pour les mots-clés */
            'date-format' => '%d/%m/%Y', // format pour les dates
            'date-locale' => '', // localisation pour les dates. Par défaut, celle du navigateur client.
            /* [st-css] Style CSS */
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // ======> fusion et controle des options
        $this->options = $this->ctrl_options($options_def);
        $this->options['template-menutype'] = $this->get_bbcode($this->options['template-menutype'], false);
        $this->options['template-menu'] = $this->get_bbcode($this->options['template-menu'], false);
        $this->options['template-article'] = $this->get_bbcode($this->options['template-article'], false);
        $isList = ($this->options['main-tag'] == 'ul');
        if (! $isList) {
            $this->options['template-menu'] = '<' . $this->options['main-tag'] . ' class="level_##level##">' . $this->options['template-menu'] . '</' . $this->options['main-tag'] . '>';
        }
        $this->options['article-sort-by'] = $this->ctrl_argument($this->options['article-sort-by'], 'title,ordering,created,modified,publish_up,id,hits,random');
        $this->options['article-sort-order'] = $this->ctrl_argument($this->options['article-sort-order'], 'asc,desc');

        $now = date('Y-m-d H:i:s');
        $this->options['template-menu'] = str_ireplace('#now', $now, $this->options['template-menu']);
        if (! empty($this->options['template-article']))
            $this->options['template-article'] = str_ireplace('#now', $now, $this->options['template-article']);

        // === CSS-HEAD
        $this->load_css_head($this->options['css-head']);

        // === RECUP NIVEAU ACCES
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from($db->quoteName('#__viewlevels'));
        $db->setQuery($query);
        $results = $db->loadObjectList();
        foreach ($results as $res)
            $this->nivacces[$res->id] = $res->title;

        // --- robots index - config par défaut
        $app = Factory::getApplication();
        $robots_config = $app->getCfg('robots', 'index, follow');

        // --- robots pour les catégories (si global, on prend $robots_config)
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select('id, metadata');
        $query->from($db->quoteName('#__categories'));
        $db->setQuery($query);
        $results = $db->loadObjectList();
        foreach ($results as $res) {
            $tmp = json_decode($res->metadata, true);
            $robots_category[$res->id] = (isset($tmp['robots'])) ? $tmp['robots'] : $robots_config;
        }

        // === liste des menutypes
        $all_menutype = $this->getMenutypes();

        // === MISE EN FORME
        $list = array();
        $menutype = '';
        $level = 0;
        $toplevel = 0;
        $attr_main['id'] = $this->options['id'];
        $this->get_attr_style($attr_main, $this->options['style']);

        // $menu = AbstractMenu::getInstance('site');

        $out = array(); // v2.8.1
        foreach ($all_menutype as $menutype) {
            if ($this->options['template-menutype'] != '0' && $isList)
                $out[] = $this->getLignMenutype($menutype);

            $all_menuitem = $this->getMenus($menutype);

            if (count($all_menuitem) > 0) {
                foreach ($all_menuitem as $menuItem) {
                    if ($menutype->menutype == $menuItem->menutype) {
                        if ($isList) {
                            if ($menuItem->level < $level) {
                                $out[] = '</li>' . str_repeat('</ul>', $level - $menuItem->level); // v5.1 merci Enfis
                            } elseif ($menuItem->level > $level) {
                                $out[] = '<ul>';
                            } else { // idem
                                $out[] = '</li>';
                            }
                        }
                        $params = json_decode($menuItem->params);
                        $robots_menu = $params->robots ?? '';
                        if (empty($robots_menu))
                            $robots_menu = $robots_config;

                        $str = $this->getLignMenu($menuItem, $robots_menu);
                        if ($isList) {
                            $out[] = '<li class="level_' . $menuItem->level . '">' . $str;
                        } else {
                            $offset = ($menuItem->level - $toplevel);
                            $out[] = $str;
                        }
                        // ---------------------------------
                        if ($this->options['template-article'] && str_contains($menuItem->link, 'com_content')) {
                            $articles = $this->getArticles($menuItem->link);
                            if (! empty($articles)) {
                                // $out[] = $art_list_tag;
                                // $out[] = $art_block_tag;
                                $out[] = '<ul class="list-article">';
                                foreach ($articles as $article) {
                                    // si robot categorie est global, on prend celui du menu et à défaut la config generale
                                    $robots_article = $robots_category[$article->catid];
                                    if (empty($robots_article))
                                        $robots_article = $robots_menu;
                                    if (empty($robots_article))
                                        $robots_article = $robots_config;
                                    $out[] = '<li class="list-article">' . $this->getLignArticle($article, $robots_article) . '</li>';
                                }
                                $out[] = '</ul>'; // $art_block_tag
                            }
                        }
                        // ---------------------------------
                        $level = (int) $menuItem->level;
                    }
                }
                for ($level; $level > 0; $level --)
                    if ($isList)
                        $out[] = ($isList) ? '</li></ul>' : '';
            } else {
                $out[] = 'No menu items.';
            }
        }

        return $this->set_attr_tag('div', $attr_main, implode(PHP_EOL, $out));
    }

    // run
    function getMenutypes()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from($db->quoteName('#__menu_types', 'mt'));
        $query->where($db->quoteName('mt.client_id') . '=0');

        if (! empty($this->options['menutype-exclude'])) {
            $exclus = array_map('trim', explode(',', $this->options['menutype-exclude']));
            $exclus = $db->quote($exclus);
            $exclus = implode(',', $exclus);
        }
        $foo = $this->options[__class__];
        if (! empty($this->options[__class__])) {
            $inclus = array_map('trim', explode(',', $this->options[__class__]));
            $inclus = $db->quote($inclus);
            $inclus = implode(',', $inclus);
        }
        if (isset($inclus))
            $query->where($db->quoteName('menutype') . ' IN (' . $inclus . ')');
        if (isset($exclus))
            $query->where($db->quoteName('menutype') . 'NOT IN (' . $exclus . ')');

        $db->setQuery($query);
        $menutypes = $db->loadObjectList();
        return $menutypes;
    }

    function getMenus($menutype)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select($db->quoteName(array(
            'id',
            'title',
            'menutype',
            'published',
            'type',
            'home',
            'link',
            'access',
            'language',
            'level',
            'path',
            'params',
            'note',
            'publish_up',
            'publish_down'
        )));
        $query->from($db->quoteName('#__menu'));
        // $query->where($db->quoteName('client_id') . '= 0');
        if (! empty($menutype))
            $query->where($db->quoteName('menutype') . '=' . $db->quote($menutype->menutype));
        $query->where($db->quoteName('published') . ' IN (' . $this->options['menu-state-show'] . ')');
        if ($this->options['public-only'])
            $query->where($db->quoteName('access') . '> 0');
        $query->order('lft ASC');
        $db->setQuery($query);
        $results = $db->loadObjectList();
        return $results;
    }

    // end getMenus
    function getArticles($menulink)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select($db->quoteName(array(
            'a.id',
            'a.title',
            'a.alias',
            'a.state',
            'a.access',
            'a.created',
            'a.modified',
            'a.publish_up',
            'a.publish_down',
            'a.metadata',
            'a.images',
            'a.urls',
            'a.attribs',
            'a.catid',
            'a.language',
            'a.featured',
        )));
        $query->select($db->quoteName('c.title', 'catname'));
        $query->from($db->quoteName('#__content', 'a'));
        $query->join('INNER', $db->quoteName('#__categories', 'c') . ' ON (' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid') . ')');
        if ($this->J4) {
            $query->select($db->quoteName('d.featured_up', 'featured_up'));
            $query->select($db->quoteName('d.featured_down', 'featured_down'));
            $query->join('LEFT', $db->quoteName('#__content_frontpage', 'd') . ' ON (' . $db->quoteName('d.content_id') . ' = ' . $db->quoteName('a.id') . ')');
        }
        if ($this->options['public-only'])
            $query->where($db->quoteName('a.access') . '=1');
        // ---- les critères spécifiques
        $link = parse_url($menulink);
        if (empty($link['query']))
            $foo = 'debug';
        parse_str($link['query'], $params);
        switch ($params['view']) {
            case 'article':
                // article : index.php?option=com_content&view=article&id=150
                $query->where($db->quoteName('a.id') . '=' . $params['id']);
                break;
            case 'archive':
                // articles archivés : index.php?option=com_content&view=archive&catid[0]=8&catid[1]=17
                $state = 2;
                $query->where($db->quoteName('a.catid') . ' IN (' . implode(',', $params['catid']) . ')');
                break;
            case 'featured':
                // articles épinglés : index.php?option=com_content&view=featured
                $query->where($db->quoteName('a.featured') . '=1');
                break;
            case 'category':
                // blog d'une catégorie : index.php?option=com_content&view=category&layout=blog&id=17
                // liste articles d'une catégorie : index.php?option=com_content&view=category&id=8
                $query->where($db->quoteName('a.catid') . '=' . $params['id']);
                break;
        }
        $query->where($db->quoteName('a.state') . ' IN (' . $this->options['article-state-show'] . ')');
        // ---- fin des critères spécifiques

        $query->order('a.' . $this->options['article-sort-by'] . ' ' . strtoupper($this->options['article-sort-order']));

        $db->setQuery($query);
        $results = $db->loadObjectList();

        return $results;
    }

    function getLignMenutype($menutype)
    {
        $tmpl = $this->options['template-menutype'];
        $this->kw_replace($tmpl, 'id', $menutype->id);
        $this->kw_replace($tmpl, 'title', $menutype->title);
        $this->kw_replace($tmpl, 'menutype', $menutype->menutype);
        $this->kw_replace($tmpl, 'description', $menutype->description);
        return $tmpl;
    }

    function getLignMenu($menu, $robots)
    {
        $tmpl = $this->options['template-menu'];

        $root = Uri::getInstance()->root();
        $url = $root . $this->get_db_value('path', 'menu', 'id=' . $menu->id);

        $this->kw_replace($tmpl, 'id', $menu->id);
        $this->kw_replace($tmpl, 'title', $menu->title);
        $this->kw_replace($tmpl, 'title-link', '<a href="' . $url . '">' . $menu->title . '</a>');
        $this->kw_replace($tmpl, 'menutype', $menu->menutype);
        $this->kw_replace($tmpl, 'home', $menu->home);
        $this->kw_replace($tmpl, 'access', $this->nivacces[$menu->access]);
        $this->kw_replace($tmpl, 'level', $menu->level);
        $this->kw_replace($tmpl, 'note', $menu->note);
        $this->kw_replace($tmpl, 'state', $menu->published);
        // date publish
        if (stripos($tmpl, '##publish_') !== false) {
            $this->kw_replace($tmpl, 'publish_up', $this->get_db_value('publish_up', 'menu', 'id=' . $menu->id));
            $this->kw_replace($tmpl, 'publish_down', $this->get_db_value('publish_down', 'menu', 'id=' . $menu->id));
        }
        //
        $robots = explode(',', $robots);
        $this->kw_replace($tmpl, 'index', trim($robots[0]));
        $this->kw_replace($tmpl, 'follow', trim($robots[1]));

        // ============================================================
        if (strpos($tmpl, '##image') !== false) {
            // $itemParams = json_decode($menu->params);
            $image = $this->get_image($menu->params);
            $this->kw_replace($tmpl, 'image', $image);
        }
        // language
        $str = ($menu->language == '*') ? '' : $menu->language;
        $this->kw_replace($tmpl, 'language', $str);

        // type
        if ($menu->type == 'component') {
            $link = array();
            $tmp = parse_url($menu->link);
            parse_str($tmp['query'], $link);
            $str_context = '';
            if (isset($link['layout']))
                $str_context .= $link['layout'] . ' ';
            if (isset($link['view']))
                $str_context .= $link['view'] . ' ';
            if (isset($link['id']))
                $str_context .= '#' . $link['id'];
            // ----
            if ($link['option'] == 'com_content') {
                $str = $str_context . ': ';
                switch ($link['view']) {
                    case 'article':
                        $str .= $this->get_db_value('title', 'content', 'id=' . $link['id']) . ')';
                        break;
                    case 'category':
                        $str .= $this->get_db_value('title', 'categories', 'id=' . $link['id']) . ')';
                        break;
                    default:
                        $str .= (isset($link['id'])) ? ' #' . $link['id'] : '';
                        break;
                }
            } else {
                $str = str_replace('com_', '', $link['option']) . ' ' . $str_context;
            }
        } else {
            switch ($menu->type) {
                case 'url':
                    $str = 'url : ' . $menu->link;
                    break;
                case 'alias':
                    $itemParams = json_decode($menu->params);
                    $idalias = $itemParams->aliasoptions;
                    $str = 'alias menu : ';
                    $str .= $this->get_db_value('menutype', 'menu', 'id=' . $idalias) . '/';
                    $str .= $this->get_db_value('title', 'menu', 'id=' . $idalias);
                    break;
                default:
                    $str = $menu->type;
            }
        }
        $this->kw_replace($tmpl, 'type', $str);

        return $tmpl;
    }

    function getLignArticle($article, $robots)
    {
        $tmpl = $this->options['template-article'];

        $root = Uri::getInstance()->toString(array(
            'scheme',
            'host',
            'port'
        ));
        $article->slug = $article->id . ':' . $article->alias;
        $route = Route::_(RouteHelper::getArticleRoute($article->slug, $article->catid, $article->language));
        $url = $root . Route::_($route);
        $metadata = json_decode($article->metadata, true);
        $robots = (empty($metadata['robots'])) ? $robots : $metadata['robots'];
        $robots = explode(',', $robots);

//         if ($article->id==290)
//             $debug=true;
        
        $this->kw_replace($tmpl, 'id', $article->id);
        $this->kw_replace($tmpl, 'title', $article->title);
        $this->kw_replace($tmpl, 'title-link', '<a href="' . $url . '">' . $article->title . '</a>');
        $this->kw_replace($tmpl, 'title-size', strlen($article->title));
        $this->kw_replace($tmpl, 'alias', $article->alias);
        $this->kw_replace($tmpl, 'state', $article->state);
        $this->kw_replace($tmpl, 'access', $this->nivacces[$article->access]);
        $this->kw_replace($tmpl, 'created', $this->up_date_format($article->created, $this->options['date-format'], $this->options['date-locale']));
        $this->kw_replace($tmpl, 'modified', $this->up_date_format($article->modified, $this->options['date-format'], $this->options['date-locale']));
        $this->kw_replace($tmpl, 'publish_up', $this->up_date_format($article->publish_up, $this->options['date-format'], $this->options['date-locale']));
        $this->kw_replace($tmpl, 'publish_down', $this->up_date_format($article->publish_down, $this->options['date-format'], $this->options['date-locale']));
        $this->kw_replace($tmpl, 'catid', $article->catid);
        $this->kw_replace($tmpl, 'catname', $article->catname);
        $this->kw_replace($tmpl, 'featured', $article->featured);
        $this->kw_replace($tmpl, 'featured_up', $this->up_date_format($article->featured_up, $this->options['date-format'], $this->options['date-locale']));
        $this->kw_replace($tmpl, 'featured_down', $this->up_date_format($article->featured_down, $this->options['date-format'], $this->options['date-locale']));
        $this->kw_replace($tmpl, 'language', $article->language);
        $this->kw_replace($tmpl, 'index', trim($robots[0]));
        $this->kw_replace($tmpl, 'follow', trim($robots[1]));


        // des mots-clés JSON
        $matches = array();
        $regex = '/\#\#(\w*)\.(\w*)\b.*\#\#/';
        while (preg_match_all($regex, $tmpl, $matches)) {
            $field = $matches[1][0];
            $key = $matches[2][0];
            $val = '';
            switch ($field) {
                case 'attribs':
                    $res = json_decode($article->attribs, true);
                    break;
                case 'metadata':
                    $res = json_decode($article->metadata, true);
                    break;
                case 'urls':
                    $res = json_decode($article->urls, true);
                    break;
                case 'images':
                    $res = json_decode($article->images, true);
                    break;
                default:
                    $res = array();
            }
            $val = $res[$key] ?? 'error';
            $this->kw_replace($tmpl, $matches[1][0] . '.' . $matches[2][0], $val);
        }
        
        return $tmpl;
    }

    // ===================================================================================
    //
    // Retourne une ligne formatée pour le menutype
    function get_lign_menutype($menutype)
    {
        $tmpl = $this->options['template-menutype'];
        $this->kw_replace($tmpl, 'id', $menutype['id']);
        $this->kw_replace($tmpl, 'menutype', $menutype['menutype']);
        $this->kw_replace($tmpl, 'title', $menutype['title']);
        $this->kw_replace($tmpl, 'description', $menutype['description']);
        return $tmpl;
    }

    function get_image($data)
    {
        $params = json_decode($data);
        if (! empty($params->menu_icon_css)) {
            // icone prioritaire
            $out = '<span class="' . $params->menu_icon_css . '" aria-hidden="true"></span>';
        } elseif ((! empty($params->menu_image))) {
            // image
            $css = (empty($params->menu_image_css)) ? '' : ' class="' . $params['menu_image'] . '"';
            $out = '<img src="' . $params->menu_image . '"' . $css . '>';
        } else {
            $out = '';
        }
        return $out;
    }
}


// class







