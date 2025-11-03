<?php

/**
 * pour contrôle des metadonnées
 *
 * liste des articles d'une ou plusieurs catégories avec mise en évidence des informations pour référencement
 *
 * syntaxe 1 : {up jcontent-metadata=id-categorie(s) | template=##title-link##}
 * syntaxe 2 : {up jcontent-metadata=id-categorie(s)}##title-link##{/up jcontent-metadata}
 *
 * --- Les mots-clés :
 * ##title## ##title-link## ##subtitle## ##maintitle## ##link## ##id##
 * ##intro## ##intro-text## ##intro-text,100## ##content##
 * ##image## ##image-src## ##image-alt##
 * ##date-crea## ##date-modif## ##nivaccess## ##id##
 * ##author## ##note## ##cat## ##cat-link## ##new## ##featured##  ##hits## ##tags-list##
 * ##upnb## : nbre actions UP dans la page - ##uplist## : nbre par actions
 * ##date-publish## ##date-publish-end## ##date-featured## ##date-featured-end##
 * ##meta-index## ##meta-follow## ##meta-title## ##meta-desc## ##meta-key##
 * ##robots-cfg## ##robots-cat## ##robots-art##
 * Les clés des champs JSON
 * ##attribs.key## ##metadata.key## ##images.key## ##urls.key##
 *
 * @author   LOMART
 * @version  UP-3.1
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags     Joomla
 */
/*
 * v5.3.3 - Joomla 6 : remplacement de getInstance
 *
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Database\DatabaseInterface;

class jcontent_metadata extends upAction
{
    public function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    public function run()
    {
        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // ID(s) catégorie(s) séparé(s) avec virgule, vide pour celle de l'article actuel ou 0 pour toutes
            'filter' => '', // conditions. Voir doc action filter
            'maxi' => '', // Nombre maxi d'articles dans la liste. Vide = tous
            'exclude' => '', // liste des id des catégories non reprises si option principale=0
            'current' => '1', // 1 pour inclure l'article en cours
            'nivaccess' => '1', // liste des groupes pour niveau d'accés. 1=public only, 0=tous, 1,9=public et guest
            'content-plugin' => 0, // prise en compte des plugins de contenu pour ##intro et ##content##
            'no-published' => '1', // Liste aussi les articles non publiés
            'author' => '', // filtre sur auteur: liste des id ou article, current
            'sort-by' => 'publish_up', // tri: title, ordering, created, modified, publish_up, id, hits, random
            'sort-order' => 'desc', // ordre de tri : asc, desc
            'no-content-html' => 'aucun article ne correspond aux critéres ...[br]no item matches the criteria ...', // texte si aucune correspondance. 0=aucun texte
            /* [st-model] Modéles de présentation */
            'template' => '', // modéle de mise en page. Si vide le modéle est le contenu
            /* [st-main] Balise et style pour le bloc principal */
            'main-tag' => 'div', // balise pour le bloc englobant tous les articles. 0 pour aucun
            'main-style' => '', // classes et styles inline pour bloc principal
            'main-class' => '', // classe(s) pour bloc principal (obsoléte)
            /* [st-item] Balise et style pour un article */
            'item-tag' => 'div', // balise pour le bloc d'un article. 0 pour aucun
            'item-style' => '', // classes et styles inline pour un article
            'item-class' => '', // classe(s) pour un article (obsoléte)
            /* [st-img] Paramétre pour l'image */
            'image-src' => '//lorempixel.com/300/300', // image par défaut
            'image-alt' => 'news', // image, texte alternatif par défaut
            /* [st-format] Format pour les mots-clés */
            'date-format' => '%d/%m/%Y', // format pour les dates
            'date-locale' => '', // localisation pour les dates. Par défaut, celle du navigateur client.
            'new-days' => '30', // nombre de jours depuis la création de l'article
            'new-html' => '[span class="badge bg-red t-white"]nouveau[/span]', // code HTML pour badge NEW
            'featured-html' => '&#x2B50 ', // code HTML pour article en vedette
            'tags-list-prefix' => '', // texte avant les autres éventuels tags
            'tags-list-style' => 'badge;margin-right:4px', // classe ou style affecté à une balise span par mot-clé
            'tags-list-separator' => ' ', // séparateur entre mots-clés
            /* [st-meta] spécifique jcontent-metadata */
            'meta-title-min' => 30, // nombre de caractéres minimum/raisonnable pour la balise title de la page. Nom du site inclus selon config
            'meta-title-max' => 65, // nombre de caractéres maximum pour la balise title de la page. Nom du site inclus selon config
            'meta-desc-min' => 0, // nombre de caractéres minimum/raisonnable pour la balise meta/description de la page
            'meta-desc-max' => 160, // nombre de caractéres maximum pour la balise meta/description de la page

            /* [st-css] Style CSS */
            'id' => '', // identifiant
            'css-head' => '' // code CSS dans le head
        );

        // si catégorie article non indiquée, c'est celle de l'article
        if ($this->options_user[__class__] === true) {
            if (isset($this->article->catid)) {
                // le shortcode est dans un article
                $this->options_user[__class__] = intval($this->article->catid);
            } else {
                // le shortcode est dans un module
                // TODO : récupérer le catid de l'article actuellement affiché
                return ''; // sortir tant que pas de CATID
            }
        }
        // ======> fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // === Filtrage
        if ($this->filter_ok($options['filter']) !== true) {
            return '';
        }

        $app = Factory::getApplication();
        $appParams = $app->getParams();

        $options['template'] = $this->get_bbcode($options['template'], false);
        $options['new-html'] = $this->get_bbcode($options['new-html'], false);
        $options['no-content-html'] = $this->get_bbcode($options['no-content-html'], false);
        // les niveaux d'accés autorisés. 0=tous
        $nivaccess = array();
        if (! empty($options['nivaccess'])) {
            $nivaccess = array_map('trim', explode(',', $options['nivaccess']));
        }

        // ======> verif template (modéle de mise en page)
        // en priorité : le sontenu entre shortcode
        // en second : le model dans prefs.ini
        if ($this->content) {
            $tmpl = $this->content;
        } else {
            $tmpl = $options['template'];
        }
        if (! $tmpl) {
            $this->msg_error(Text::_('Aucun contenu ni template'));
            return false;
        }

        // === configuration par défaut
        // pour calcul des longueurs titre et desc
        $nomSiteSize = ($app->getCfg('sitename_pagetitles', 0) == 1) ? strlen($app->getCfg('sitename', 0)) + 3 : 0;
        // robots
        if (stripos($tmpl, '##meta-index##') !== false || stripos($tmpl, '##meta-follow##') !== false || stripos($tmpl, '##robots-') !== false) {
            // configuration.php
            $robots_default[] = $app->getCfg('robots', 'index, follow');
            // les catégories
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->createQuery();
            $query->select('id, metadata');
            $query->from($db->quoteName('#__categories'));
            $db->setQuery($query);
            $results = $db->loadObjectList();
            foreach ($results as $res) {
                $tmp = json_decode($res->metadata, true);
                $robots_default[$res->id] = (isset($tmp['robots'])) ? $tmp['robots'] : '';
            }
        }

        // ======> date actuelle au format Joomla
        $now = date('Y-m-d H:i');

        // ======> controle clé de tri
        $list_sortkey = 'title, ordering, created, modified, publish_up, id, hits, random';
        $options['sort-by'] = $this->ctrl_argument($options['sort-by'], $list_sortkey);

        // === RECUP NIVEAU ACCES
        if (stripos($tmpl, '##nivaccess##') !== false) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->createQuery();
            $query->select('*');
            $query->from($db->quoteName('#__viewlevels'));
            $db->setQuery($query);
            $results = $db->loadObjectList();
            foreach ($results as $res) {
                $nivacces[$res->id] = $res->title;
            }
        }

        // =====> liste des catégories
        $catid = $options[__class__];
        $artid = '';
        if ($catid == '') {
            // la catégorie de l'article en cours
            if (isset($this->article->catid)) {
                // la catégorie de l'article en cours
                $catid = $this->article->catid;
                $artid = $this->article->id;
            } else {
                // appel d'un module : catégorie courante pour la page
                $app = Factory::getApplication();
                $artid = $app->getInput()->get('id', 0); // v2.5
                $view = $app->getInput()->get('view', 0);
                switch ($view) {
                    case 'article':
                        $database = Factory::getContainer()->get(DatabaseInterface::class);
                        $query = "SELECT catid FROM #__content WHERE id=" . $artid;
                        $database->setQuery($query);
                        $row = $database->loadObject();
                        $catid = ($row != null) ? $row->catid : '';
                        break;
                    case 'categories':
                    case 'category':
                        $catid = $artid;
                        break;
                    default:
                        $catid = '';
                        break;
                }
            }
        } elseif ($catid == '0') {
            // toutes les catégories = liste cat de niveau 1 - v2.6
            $catid = '';
            if ($this->J4) {
                $this->categories = Factory::getApplication()->bootComponent('com_content')->getCategory();
                $catniv1 = $this->categories->get()->getChildren();
                foreach ($catniv1 as $cat) {
                    $catid .= $cat->id . ',';
                }
            }
        }
        $catid = explode(',', $catid);

        // =====> RECUP DES DONNEES
        $model = Factory::getApplication()->bootComponent('com_content')->getMVCFactory()->createModel('Articles', '', array(
            'ignore_request' => true
        ));
        if (is_bool($model)) {
            return 'Aucune catégorie';
        }

        // Set application parameters in model
        $model->setState('params', $appParams);

        // Set the filters based on the module params
        // nombre d'article
        $model->setState('list.start', 0);
        if ($options['maxi'] != '') {
            $model->setState('list.limit', (int) $options['maxi']);
        }
        // etat publication
        if ($options['no-published'] !== true) {
            $model->setState('filter.published', 1);
        }

        // auteur(s)
        switch ($options['author']) {
            case 'article': // l'auteur de l'article en cours
                $model->setState('filter.author_id', $this->article->created_by);
                break;
            case 'current': // l'utilisateur connecté
                $userid = Factory::getApplication()->getIdentity()->id;
                $model->setState('filter.author_id', $userid);
                break;
            default:
                $model->setState('filter.author_id', explode(',', $options['author']));
                break;
        }

        // Category filter
        $model->setState('filter.category_id', $catid);
        if ($options['exclude']) {
            // Exclude
            $model->setState('filter.category_id.include', false);
        }

        // article en cours
        if ($artid != '' && $options['current'] != '1') {
            $model->setState('filter.article_id', $artid);
            $model->setState('filter.article_id.include', false); // Exclude
        }
        // Filter by language
        $model->setState('filter.language', $app->getLanguageFilter());

        // Ordering
        if ($options['sort-by'] == 'random') {
            $model->setState('list.ordering', 'rand()');
        } else {
            $model->setState('list.ordering', 'a.' . $options['sort-by']);
        }
        $model->setState('list.direction', $options['sort-order']);

        $items = $model->getItems();

        if (count($items) == 0) {
            return ($options['no-content-html'] == '0') ? '' : $options['no-content-html'];
        }

        // ======> Style général et par article
        $main_attr['id'] = $options['id'];
        $this->get_attr_style($main_attr, $options['main-class'], $options['main-style']);
        $this->get_attr_style($sItem_attr, $options['item-class'], $options['item-style']);
        $this->get_attr_style($tags_list_attr, $options['tags-list-style']);

        // css-head
        $this->load_css_head($options['css-head']);

        // ======> mise en forme résultat
        $html = array();
        $nblign = 0;
        if ($options['main-tag'] != '0') {
            $html[] = $this->set_attr_tag($options['main-tag'], $main_attr);
        }
        foreach ($items as $item) {
            if (! empty($nivaccess) && ! in_array($item->access, $nivaccess)) {
                continue;
            }
            $nblign++;
            // --- Bloc article
            if ($options['item-tag'] != '0') {
                $html[] = $this->set_attr_tag($options['item-tag'], $sItem_attr);
            }
            $sItem = $tmpl; // reinit pour nouvel article
            // --- lien vers l'article
            $url = '';
            $slug = ($item->alias) ? ($item->id . ':' . $item->alias) : $item->id;
            $catslug = ($item->category_alias) ? ($item->catid . ':' . $item->category_alias) : $item->catid;
            $route = RouteHelper::getArticleRoute($slug, $catslug);
            $url = Route::_($route);
            // --- le titre et sous titre
            $title = $item->title;
            $subtitle = '';
            $maintitle = strstr($item->title . '~', '~', true);
            if (stripos($sItem, '##subtitle##') !== false) {
                $title = $maintitle;
                $subtitle = trim(substr(strstr($item->title, '~'), 1));
            }
            // --- traitement $item->introtext et $item->fulltext
            // si pas d'introtext -> introtext=fulltext et fulltext=vide
            // ce n'est pas souhaitable car le contenu fulltext n'est pas prévu pour cela
            if ($item->fulltext == '') {
                $item->fulltext = $item->introtext;
                $item->introtext = '';
            }
            // prise en charge plugins contenu v31
            if ($options['content-plugin']) {
                $item->introtext = $this->import_content($item->introtext); // v31
                $item->fulltext = $this->import_content($item->fulltext);
            }
            // ==== les remplacements
            // {id} : ID de l'article
            $this->kw_replace($sItem, 'id', $item->id);
            // {link} : lien vers l'article - a mettre dans balise a
            $this->kw_replace($sItem, 'link', $url);
            // {title-link} : titre de l'article
            $this->kw_replace($sItem, 'title-link', '<a href="' . $url . '">' . $title . '</a>');
            // {title} : titre de l'article
            $this->kw_replace($sItem, 'title', $title);
            // {subtitle} : sous-titre de l'article (partie aprés tilde du titre)
            $this->kw_replace($sItem, 'subtitle', $subtitle);
            // {maintitle} : titre principal de l'article (avant tilde) v3.0
            $this->kw_replace($sItem, 'maintitle', $maintitle);
            // {alias} : alias de l'article v1.8
            $this->kw_replace($sItem, 'alias', $item->alias);
            // {date-crea} : date de création
            $this->kw_replace($sItem, 'date-crea', $this->up_date_format($item->created, $options['date-format'], $options['date-locale']));
            // {date-modif} : date de création
            $this->kw_replace($sItem, 'date-modif', $this->up_date_format($item->modified, $options['date-format'], $options['date-locale']));
            // {date-publish} : date de création
            $tmp = ($item->publish_up) ? $this->up_date_format($item->publish_up, $options['date-format'], $options['date-locale']) : '';
            $tmp = ($item->publish_up > $now) ? '<span class="t-rouge">' . $tmp . '</span>' : $tmp;
            $this->kw_replace($sItem, 'date-publish', $tmp);
            $tmp = ($item->publish_down) ? $this->up_date_format($item->publish_down, $options['date-format'], $options['date-locale']) : '';
            $tmp = ($item->publish_down < $now) ? '<span class="t-rouge">' . $tmp . '</span>' : $tmp;
            $this->kw_replace($sItem, 'date-publish-end', $tmp);
            // {author} : auteur
            $this->kw_replace($sItem, 'author', $item->author);
            // {intro} : texte d'introduction en HTML
            $this->kw_replace($sItem, 'intro', $item->introtext);
            $this->kw_replace($sItem, 'content', $item->fulltext);

            $lib_nivaccess = (isset($nivacces[$item->access]) ? $nivacces[$item->access] : 'N.A.');
            $this->kw_replace($sItem, 'nivaccess', $lib_nivaccess);

            // période de mise en vedette
            $tmp = ($item->featured_up) ? $this->up_date_format($item->featured_up, $options['date-format'], $options['date-locale']) : '';
            $tmp = ($item->featured_up > $now) ? '<span class="t-rouge">' . $tmp . '</span>' : $tmp;
            $this->kw_replace($sItem, 'date-featured', $tmp);
            $tmp = ($item->featured_down) ? $this->up_date_format($item->featured_down, $options['date-format'], $options['date-locale']) : '';
            $tmp = ($item->featured_down < $now) ? '<span class="t-rouge">' . $tmp . '</span>' : $tmp;
            $this->kw_replace($sItem, 'date-featured-end', $tmp);

            // --- robots

            if (stripos($sItem, '##meta-index##') !== false || stripos($sItem, '##meta-follow##') !== false) {
                $robots = ($metadata->robots ?? ''); // ceux de l'article en priorité
                if ($robots == '') {
                    $robots = $robots_default[$item->catid];
                } // ceux de la catégorie
                if ($robots == '') {
                    $robots = $robots_default[0];
                } // à défaut, ceux de configuration.php

                $tmp = (stripos($robots, 'noindex') !== false) ? '<span class="t-rouge">no-index</span>' : '<span class="t-vertFonce">index</span>';
                $this->kw_replace($sItem, 'meta-index', $tmp);
                $tmp = (stripos($robots, 'nofollow') !== false) ? '<span class="t-rouge">no-follow</span>' : '<span class="t-vertFonce">follow</span>';
                $this->kw_replace($sItem, 'meta-follow', $tmp);
            }

            if (stripos($sItem, '##robots') !== false) {
                $metadata = json_decode($item->metadata);

                $this->kw_replace($sItem, 'robots-cfg', $robots_default[0]);
                $this->kw_replace($sItem, 'robots-cat', $robots_default[$item->catid]);
                $this->kw_replace($sItem, 'robots-art', ($metadata->robots ?? ''));
            }
            // Ctrl taille du titre
            if (stripos($sItem, '##meta-title##') !== false) {
                $size = strlen($item->title) + $nomSiteSize;
                $style = ($size < $options['meta-title-min'] || $size > $options['meta-title-max']) ? ' class="b t-orange"' : '';
                $tmp = '<span' . $style . '>' . $size . '</span><span class="t-gris">/' . $options['meta-title-max'] . '</span>';
                $this->kw_replace($sItem, 'meta-title', $tmp);
            }

            // Ctrl taille description
            if (stripos($sItem, '##meta-desc##') !== false) {
                $size = strlen($item->metadesc);
                $style = ($size < $options['meta-desc-min'] || $size > $options['meta-desc-max']) ? ' class="b t-orange"' : '';
                $tmp = '<span' . $style . '>' . $size . '</span><span class="t-gris">/' . $options['meta-desc-max'] . '</span>';
                $this->kw_replace($sItem, 'meta-desc', '<span title="' . $item->metadesc . '">' . $tmp . '</span>');
            }

            // Ctrl taille métakey
            if (stripos($sItem, '##meta-keys##') !== false) {
                $this->kw_replace($sItem, 'meta-keys', $item->metakey);
            }
            // {note} : note sur l'article
            if (stripos($sItem, '##note##') !== false) {
                $db = Factory::getContainer()->get(DatabaseInterface::class);
                $query = $db->createQuery();
                $query->select('note')
                    ->from('#__content')
                    ->where('id = ' . $item->id);
                $db->setQuery($query);
                $result = $db->loadResult();
                $this->kw_replace($sItem, 'note', $result);
            }
            // {cat} : nom catégorie
            $this->kw_replace($sItem, 'cat', $item->category_title);
            //
            if (strpos($sItem, '##cat-link##')) {
                $caturl = Route::_(RouteHelper::getCategoryRoute($catslug));
                $this->kw_replace($sItem, 'cat-link', '<a href="' . $caturl . '">' . $item->category_title . '</a>');
            }
            // {tags-list} : liste des tags
            if (stripos($sItem, '##tags-list##') !== false) {
                $db = Factory::getContainer()->get(DatabaseInterface::class);
                $query = $db->createQuery();
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
                $this->kw_replace($sItem, 'tags-list', implode($options['tags-list-separator'], $tmpTag));
            }
            // {featured} : en vedette
            if (stripos($sItem, '##featured##') !== false) {
                $tmp = ($item->featured == '1') ? $options['featured-html'] : '';
                $this->kw_replace($sItem, 'featured', $tmp);
            }
            // {new} : badge
            if (stripos($sItem, '##new##') !== false) {
                $max = date('Y-m-d H:i:s', mktime(date("H"), date("i"), 0, date("m"), date("d") - intval($options['new-days']), date("Y")));
                $new = ($item->publish_up > $max) ? $options['new-html'] : '';
                $this->kw_replace($sItem, 'new', $new);
            }
            // {hits}
            $this->kw_replace($sItem, 'hits', $item->hits);
            // {image-xxx} : l'image d'intro, sinon celle dans l'introtext
            // {image} : la balise img complete
            // {image-src} et {image-alt} : uniquement src et alt d'une balise img existante
            if (stripos($sItem, '##image') !== false) {
                $images = json_decode($item->images);
                $img_src = $options['image-src'];
                $img_alt = $options['image-alt'];
                if ($images->image_intro) {
                    $img_src = $images->image_intro;
                    $img_alt = $images->image_intro_alt;
                } else {
                    $imgTag = $this->preg_string('#(\<img .*\>)#Ui', $item->introtext);
                    if ($imgTag) {
                        $imgAttr = $this->get_attr_tag($imgTag, 'alt');
                        $img_src = $imgAttr['src'];
                        $img_alt = $imgAttr['alt'];
                    }
                }
                $img_tag = '<img src="' . $img_src . '" alt="' . $img_alt . '">';
                $this->kw_replace($sItem, 'image', $img_tag);
                $this->kw_replace($sItem, 'image-src', $img_src);
                $this->kw_replace($sItem, 'image-alt', $img_alt);
            }

            // --- tags avec param
            // {intro-text,100} : les 100 premiers caractéres de l'introduction en texte brut
            preg_match('#\#\#(intro-text\s*,?\s*([0-9]*)\s*)\#\##Ui', $sItem, $tag);
            if (isset($tag[1])) {
                $intro = ($item->introtext) ? $item->introtext : $item->fulltext;
                $intro = trim(strip_tags($intro));
                if (isset($tag[1]) && $tag[1]) {
                    $len = (int) $tag[2];
                    if (strlen($intro) > $len) {
                        $intro = mb_substr($intro, 0, $len) . '...';
                    }
                }
                $this->kw_replace($sItem, $tag[1], $intro);
            }

            // {upnb} : nombre d'actions UP utilisées dans article
            $fulltext = (empty($item->fulltext)) ? $item->introtext : $item->fulltext;
            $this->kw_replace($sItem, 'upnb', substr_count($fulltext, '{up '));
            // {uplist} : nombre d'occurence de chaque action
            $fulltext = (empty($item->fulltext)) ? $item->introtext : $item->fulltext;
            if (stripos($sItem, '##uplist##') !== false) {
                $tmp = '';
                if (preg_match_all('#\{up (.*)[ \=\}]+#Us', $fulltext, $upname) > 0) {
                    $freqs = array_count_values($upname[1]);
                    foreach ($freqs as $k => $v) {
                        $tmp .= $k . '&nbsp;(' . $v . ') ';
                    }
                }
                $this->kw_replace($sItem, 'uplist', $tmp);
            }

            // des mots-clés JSON
            $matches = array();
            $regex = '/\#\#(\w*)\.(\w*)\b.*\#\#/';
            while (preg_match_all($regex, $sItem, $matches)) {
                $field = $matches[1][0];
                $key = $matches[2][0];
                $val = '';
                switch ($field) {
                    case 'attribs':
                        $res = json_decode($item->attribs, true);
                        break;
                    case 'metadata':
                        $res = json_decode($item->metadata, true);
                        break;
                    case 'urls':
                        $res = json_decode($item->urls, true);
                        break;
                    case 'images':
                        $res = json_decode($item->images, true);
                        break;
                    default:
                        $res = array();
                }
                $val = $res[$key] ?? 'error';
                $this->kw_replace($sItem, $matches[1][0] . '.' . $matches[2][0], $val);
            }

            // --- fin article
            $html[] = $sItem;
            if ($options['item-tag'] != '0') {
                $html[] = '</' . $options['item-tag'] . '>';
            }
        }
        if ($options['main-tag'] != '0') {
            $html[] = '</' . $options['main-tag'] . '>';
        }

        if ($nblign == 0) {
            return ($options['no-content-html'] == '0') ? '' : $options['no-content-html'];
        }

        return implode(PHP_EOL, $html);
    }

    // run
}

// class
