<?php

/**
 * Affiche des informations sur l'article courant
 *
 * syntaxe 1 : {up jcontent-info=nom_info}
 * syntaxe 2 : {up jcontent_info}&lt;b&gt;une info :&lt;/b&gt; ##nom_info##{/up jcontent_info}
 * syntaxe 3 : {up jcontent_info | template=[b]une info :[/b] ##nom_info##}
 *
 * Les mots-clés :
 * ##id## ##title## ##subtitle##
 * ##image## ##image-src## ##image-alt## ##image-legend##
 * ##image-full## ##image-full-src## ##image-full-alt## ##image-full-legend##
 * ##date-crea## ##crea_by## ##date-modif## ##modif_by##
 * ##url-a## ##url-b## ##url-c##
 * ##date-publish## ##date-unpublish##
 * ##note## ##cat## ##catid## ##breadcrumbs##
 * ##featured## ##hits## ##tags## ##tags-link## ##author##
 * ##CF_id_or_name## : valeur brute du custom field
 *
 * @author   LOMART
 * @version  UP-2.5
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Joomla
 */
/*
 * v2.51 - ajout mot-clé ##cat-id## et utilisation dans modules
 * v2.52 - ajout ##navpath## et ##catpath##
 * v2.6 - ajout ##catid##
 * - prise en charge article courant (si dans module)
 * v2.9 - compatibilité PHP8 pour ##date-xxx##
 * - ajout motclé ##tags-link## pour récupérer les tags avec un lien vers la liste des articles avec le tag (Merci Deny)
 * - ajout motclé ##upnb## : nbre actions UP dans la page et ##uplist## : nbre par actions
 * v3.1 - ajout custom-field + nouvelle gestion keyword
 * 
 */
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Categories\Categories;
use Joomla\Database\DatabaseInterface;

class jcontent_info extends upAction
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
            __class__ => '', // le nom d'un élément ou rien
            /* [st-model] Modèle de présentation */
            'template' => '##content##', // modèle de mise en page. Si vide le modèle est le contenu. BBCode accepté
            /* [st-main] balise & style du bloc principal */
            'tag' => '_div', // balise pour le bloc d'un article. _div = div si class ou style, sinon rien. = 0=jamais
            'id' => '', // identifiant
            'style' => '', // classes et styles inline pour un article
            'class' => '', // classe(s) pour un article (obsoléte)
            /* [st-format] Format pour les mots-clés */
            'date-format' => '%e %B %Y', // format pour les dates
            'date-locale' => '', // localisation pour les dates. Par défaut, celle du navigateur client.
            'featured-html' => '[b class="t-gris"]&#x2605;[/b], [b class="t-jauneFonce"]&#x2605;[/b]', // présentation mise en vedette
            'tags-list-prefix' => '', // texte avant les autres eventuels tags
            'tags-list-style' => 'badge;margin-right:4px', // classe ou style affecte a une balise span par mot-cle
            'tags-list-separator' => ' ', // separateur entre mots-cles
            'path-separator' => '»', // caractère ou bbcode pour séparer les items menus ou les catégories
            'path-current-class' => 'b', // style de l'élement terminal d'un chemin
            'path-parent-class' => 'fs90', // style des élements parents
            'path-order' => 'asc', // asc: élément terminal à la fin, desc : au début
            'path-link' => '1', // affiche les liens sur les éléments.
            /* [st-css] Style CSS */
            'css-head' => '' // code CSS dans le head
        );

        // ======> fusion et controle des options
        $options = $this->ctrl_options($options_def);
        // ======> verif template (modèle de mise en page)
        // en priorité : le champ demande comme argument principal
        // en second : le template entre shortcode
        // l'option template
        if ($options[__class__]) {
            $tmpl = '##' . $options[__class__] . '##';
        } elseif ($this->content) {
            $tmpl = $this->content;
        } else {
            $tmpl = $options['template'];
        }
        if (! $tmpl) {
            $this->msg_error($this->trad_keyword('NO_TEMPLATE'));
            return false;
        }
        $tmpl = $this->get_bbcode($tmpl, '+hr|pre');
        if (isset($this->article->catid)) {
            $item = $this->article;
        } else {
            // appel d'un module : catégorie courante pour la page
            $app = Factory::getApplication();
            $artid = $app->getInput()->get('id', 0);
            // ---
            // $j = new JVersion();
            // $version = substr($j->getShortVersion(), 0, 1);
            // if ($version == "4") { // Joomla 4.0
            // $model = new ArticlesModel(array('ignore_request' => true));
            // } else { // Joomla 3.x
            JLoader::register('ContentModelArticles', JPATH_SITE . '/components/com_content/models/articles.php');
            $model = BaseDatabaseModel::getInstance('Articles', 'ContentModel', array(
                'ignore_request' => true
            ));
            // }
            // ---
            $appParams = $app->getParams();
            $model->setState('params', $appParams);

            $model->setState('filter.article_id', $artid);

            $items = $model->getItems();
            if (count($items) == 0)
                return '';
            $item = $items[0];
        }

        // v2.9 - pour eviter exe si clic sur un lien tag (bizarre)
        if (empty($item->title))
            return '';

        // ======> Style général et par article
        $main_attr['id'] = $options['id'];
        $this->get_attr_style($tmpl_attr, $options['class'], $options['style']);

        // css-head
        $this->load_css_head($options['css-head']);

        // ======> mise en forme résultat
        // --- le titre et sous titre
        $title = $item->title;
        $subtitle = '';
        if (stripos($tmpl, '##subtitle') !== false) {
            $title = strstr($item->title . '~', '~', true);
            $subtitle = trim(substr(strstr($item->title, '~'), 1));
        }

        // ==== les remplacements
        // === ID
        // $tmpl = str_ireplace('##id##', $item->id, $tmpl);
        $this->kw_replace($tmpl, 'id', $item->id);
        // === TITLE & SUBTITLE
        // $tmpl = str_ireplace('##title##', $title, $tmpl);
        // $tmpl = str_ireplace('##subtitle##', $subtitle, $tmpl);
        $this->kw_replace($tmpl, 'title', $title);
        $this->kw_replace($tmpl, 'subtitle', $subtitle);
        // === CAT
        // $tmpl = str_ireplace('##cat##', $item->category_title, $tmpl);
        // $tmpl = str_ireplace('##catid##', $item->catid, $tmpl);
        $this->kw_replace($tmpl, 'cat', $item->category_title);
        $this->kw_replace($tmpl, 'catid', $item->catid);
        if (stripos($tmpl, '##catpath') !== false) {
            $str = $this->get_catpath($item->catid, $options);
            // $tmpl = str_ireplace('##catpath##', $str, $tmpl);
            $this->kw_replace($tmpl, 'catpath', $str);
        }
        // le fil d'ariane (menu)
        if (stripos($tmpl, '##navpath') !== false) {
            $str = $this->get_navpath($options);
            // $tmpl = str_ireplace('##navpath##', $str, $tmpl);
            $this->kw_replace($tmpl, 'navpath', $str);
        }
        // === NOTE
        if (stripos($tmpl, '##note') !== false) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true);
            $query->select('note')
                ->from('#__content')
                ->where('id = ' . $item->id);
            $db->setQuery($query);
            $result = $db->loadResult();
            // $tmpl = str_ireplace('##note##', $result, $tmpl);
            $this->kw_replace($tmpl, 'note', $result);
        }
        // === DATE & AUTHOR
        $tmpl = str_ireplace('##date-crea##', $this->up_date_format($item->created, $options['date-format'], $options['date-locale']), $tmpl);
        $tmpl = str_ireplace('##crea_by##', Factory::getUser($item->created_by)->get('name'), $tmpl);
        $tmpl = str_ireplace('##date-modif##', $this->up_date_format($item->modified, $options['date-format'], $options['date-locale']), $tmpl);
        $tmpl = str_ireplace('##modif_by##', Factory::getUser($item->modified_by)->get('name'), $tmpl);
        $tmpl = str_ireplace('##author##', $item->author, $tmpl);
        // === DATE PUBLICATION
        $tmpl = str_ireplace('##date-publish##', $this->up_date_format($item->publish_up, $options['date-format'], $options['date-locale']), $tmpl);
        $tmpl = str_ireplace('##date-unpublish##', $this->up_date_format($item->publish_down, $options['date-format'], $options['date-locale']), $tmpl);
        // === TAGS
        // liste des tags sans lien
        if (stripos($tmpl, '##tags') !== false) {
            $this->get_attr_style($tags_list_attr, $options['tags-list-style']);
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true);
            $query->select('t.title')
                ->from('#__tags as t')
                ->innerJoin('#__contentitem_tag_map as m on t.id = m.tag_id')
                ->where('m.content_item_id = ' . $item->id . ' AND m.type_alias like "%article%"');
            $db->setQuery($query);
            $listTags = $db->loadObjectList();
            $tmpTags = (empty($listTags)) ? '' : $options['tags-list-prefix'];
            $tmpTag = array();
            foreach ($listTags as $tag) {
                if ($options['tags-list-style'] != '') {
                    $tmpTag[] = $this->set_attr_tag('span', $tags_list_attr, $tag->title);
                } else {
                    $tmpTag[] = $tag->title;
                }
            }
            // $tmpl = str_ireplace('##tags##', implode($options['tags-list-separator'], $tmpTag), $tmpl);
            $this->kw_replace($tmpl, 'tags', implode($options['tags-list-separator'], $tmpTag));
        }

        // liste des tags AVEC lien
        if (stripos($tmpl, '##tags-link') !== false) {
            $this->get_attr_style($tags_list_attr, $options['tags-list-style']);
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true);
            $query->select('t.title, t.alias, t.id, t.language')
                ->from('#__tags as t')
                ->innerJoin('#__contentitem_tag_map as m on t.id = m.tag_id')
                ->where('m.content_item_id = ' . $item->id . ' AND m.type_alias like "%article%"');
            $db->setQuery($query);
            $listTags = $db->loadObjectList();
            $tmpTags = (empty($listTags)) ? '' : $options['tags-list-prefix'];
            $tmpTag = array();
            foreach ($listTags as $tag) {
                $tags_list_attr['href'] = Route::_(RouteHelper::getComponentTagRoute($tag->id . ':' . $tag->alias, $tag->language));
                $tmpTag[] = $this->set_attr_tag('a', $tags_list_attr, $tag->title);
            }
            // $tmpl = str_ireplace('##tags-link##', implode($options['tags-list-separator'], $tmpTag), $tmpl);
            $this->kw_replace($tmpl, 'tags-link', implode($options['tags-list-separator'], $tmpTag));
        }

        // === en vedette
        if (stripos($tmpl, '##featured') !== false) {
            $tmp = $this->get_bbcode($options['featured-html']);
            $tmp = explode(',', $tmp);
            // $tmpl = str_ireplace('##featured##', $tmp[$item->featured], $tmpl);
            $this->kw_replace($tmpl, 'featured', $tmp[$item->featured]);
        }
        // === HITS
        // $tmpl = str_ireplace('##hits##', $item->hits, $tmpl);
        $this->kw_replace($tmpl, 'hits', $item->hits);

        // === IMAGES
        if (stripos($tmpl, '##image') !== false) {
            $images = json_decode($item->images, true);
            foreach (array(
                'image_intro' => 'image',
                'image_fulltext' => 'image-full'
            ) as $key => $keyword) {
                $img_src = $images[$key];
                $img_alt = $images[$key . '_alt'];
                if (empty($img_alt))
                    $img_alt = $this->link_humanize($img_src);
                $img_legend = $images[$key . '_caption'];
                $img = ($img_src) ? '<img src="' . $img_src . '" alt="' . $img_alt . '">' : '';
                // $tmpl = str_ireplace('##' . $keyword . '##', $img, $tmpl);
                // $tmpl = str_ireplace('##' . $keyword . '-src##', $img_src, $tmpl);
                // $tmpl = str_ireplace('##' . $keyword . '-alt##', $img_alt, $tmpl);
                // $tmpl = str_ireplace('##' . $keyword . '-legend##', $img_legend, $tmpl);
                $this->kw_replace($tmpl, $keyword, $img);
                $this->kw_replace($tmpl, $keyword . '-src', $img_src);
                $this->kw_replace($tmpl, $keyword . '-alt', $img_alt);
                $this->kw_replace($tmpl, $keyword . '-legend', $img_legend);
            }
        }

        // === URL
        if (stripos($tmpl, '##url-') !== false) {
            $urls = json_decode($item->urls, true);
            foreach (array(
                'a',
                'b',
                'c'
            ) as $key) {
                $attr = array();
                $url = '';
                $attr['href'] = $urls['url' . $key];
                $url_text = $urls['url' . $key . 'text'];
                if (empty($url_text))
                    $url_text = $attr['href'];
                if (! empty($attr['href'])) {
                    switch ($urls['target' . $key]) {
                        case 1: // new
                            $attr['target'] = '_blank';
                            $attr['rel'] = "nofollow noopener noreferrer";
                            break;
                        case 2: // popup
                            $attr['onclick'] = "window.open(this.href, 'targetWindow', 'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=600,height=600'); return false;";
                            $attr['rel'] = "noopener noreferrer";
                            break;
                        case 3: // modal
                            $attr['target'] = '_blank';
                            $attr['class'] = 'modal';
                            $attr['rel'] = "{handler: 'iframe', size: {x:600, y:600}} noopener noreferrer";
                            break;
                        default:
                            $attr['target'] = '_self';
                            $attr['rel'] = "nofollow";
                            break;
                    }
                    $url = $this->set_attr_tag('a', $attr, $url_text);
                }
                // $tmpl = str_ireplace('##url-' . $key . '##', $url, $tmpl);
                $this->kw_replace($tmpl, 'url-' . $keyword, $url);
            }
        }

        // === infos UP
        // {upnb} : nombre d'actions UP utilisées dans article
        if (stripos($tmpl, '##upnb') !== false) {
            $fulltext = (empty($item->fulltext)) ? $item->introtext : $item->fulltext;
            // $tmpl = str_ireplace('##upnb##', substr_count($fulltext, '{up '), $tmpl);
            $this->kw_replace($tmpl, 'upnb', substr_count($fulltext, '{up '));
        }

        // {uplist} : nombre d'occurence de chaque action
        $fulltext = (empty($item->fulltext)) ? $item->introtext : $item->fulltext;
        if (stripos($tmpl, '##uplist') !== false) {
            $tmp = '';
            if (preg_match_all('#\{up (.*)[ \=\}]+#Us', $fulltext, $upname) > 0) {
                $freqs = array_count_values($upname[1]);
                foreach ($freqs as $k => $v) {
                    $tmp .= $k . '&nbsp;(' . $v . ') ';
                }
            }
            // $tmpl = str_ireplace('##uplist##', $tmp, $tmpl);
            $this->kw_replace($tmpl, 'uplist', $tmp);
        }

        // les custom fields (v2.3)
        if (strpos($tmpl, '##') !== false) { // 3.1
            require_once ($this->upPath . '/assets/lib/kw_custom_field.php');
            kw_cf_replace($tmpl, $item);
        }

        // --- fin article
        $html = $this->set_attr_tag($options['tag'], $tmpl_attr, $tmpl);

        return $html;
    }

    // run

    /*
     * ==== get_catpath()
     * retourne les catégories ascendantes mises en forme
     *
     * path-separator => '»',
     * path-current-class => 'b',
     * path-parent-class => 'fs90',
     * path-order => 'asc',
     * path-link => '1',
     */
    function get_catpath($catid, $options)
    {
        $tag = ($options['path-link']) ? 'a' : 'span';
        $categories = Categories::getInstance('Content');
        $cat = $categories->get($catid);
        // mise en forme catégorie courante
        $this->get_attr_style($attr_current, $options['path-current-class']);
        if ($options['path-link'])
            $attr_current['href'] = Route::_('index.php?option=com_content&view=category&layout=blog&id=' . $cat->id);
        $out[] = $this->set_attr_tag($tag, $attr_current, $cat->title);
        $cat = $cat->getParent();
        // les catégories parentes
        $this->get_attr_style($attr_parent, $options['path-parent-class']);
        while ($cat->id !== 'root') {
            if ($options['path-link'])
                $attr_parent['href'] = 'index.php?option=com_content&view=category&layout=blog&id=' . $cat->id;
            $out[] = $this->set_attr_tag($tag, $attr_parent, $cat->title);
            $cat = $cat->getParent();
        }
        if (strtoupper($options['path-order']) == 'ASC')
            $out = array_reverse($out);

        $sep = $this->get_bbcode($options['path-separator']);
        return implode($sep, $out);
    }

    /*
     * ==== get_breadcrumbs()
     * retourne le fil d'ariane (menu) mis en forme
     *
     */
    function get_navpath($options)
    {
        $tag = ($options['path-link']) ? 'a' : 'span';
        // ==== Get the PathWay object from the application
        $app = Factory::getApplication();
        $pathway = $app->getPathway();
        $items = $pathway->getPathWay();
        $count = count($items);

        // ==== Mise en forme
        // mise en forme catégorie courante
        $this->get_attr_style($attr_current, $options['path-current-class']);
        if ($options['path-link'] && isset($items[0]->link))
            $attr_current['href'] = Route::_($items[0]->link);
        $out[] = $this->set_attr_tag($tag, $attr_current, $items[0]->name);

        // les menus parents
        $this->get_attr_style($attr_parent, $options['path-parent-class']);
        for ($i = 1; $i < $count; $i ++) {
            if ($options['path-link'])
                $attr_parent['href'] = Route::_($items[$i]->link);
            $out[] = $this->set_attr_tag($tag, $attr_parent, $items[$i]->name);
        }

        if (strtoupper($options['path-order']) == 'ASC')
            $out = array_reverse($out);

        $sep = $this->get_bbcode($options['path-separator']);
        return implode($sep, $out);
    }
}

// class





