<?php

/**
 * liste des articles d'une catégorie
 *
 * syntaxe 1 : {up jcontent-by-categories=id-catégorie(s) | template=##title-link##}
 * syntaxe 2 : {up jcontent-by-categories=id-catégorie(s)}##title-link##{/up jcontent-by-categories}
 *
 * Une action très pratique pour lister les articles de la catégorie en cours, il suffit de taper {up article-category}
 *
 * Les mots-clés :
 * ##title## ##title-link## ##subtitle## ##maintitle## ##link## ##id##
 * ##intro## ##intro-text## ##intro-text,100## ##content##
 * ##image## ##image-src## ##image-alt##
 * ##date-crea## ##date-modif## ##date-publish## ##date-max##
 * ##author## ##note## ##cat## ##cat-link## ##new## ##featured##  ##hits## ##tags-list##
 * ##CF_id_or_name## : valeur brute du custom field
 * ##upnb## : nbre actions UP dans la page - ##uplist## : nbre par actions
 *
 * @author   LOMART
 * @version  UP-1.7
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags     Joomla
 */

/**
 * v2.4 - ajout mot-clé ##content##
 * v2.6 - fix toutes les catégories
 * v2.8 - ajout mot-clé ##cat-link##
 * v2.9 - compatibilité PHP8 pour ##date-xxx##
 * - ajout mots-clés ##upnb## et ##uplist##
 * v2.9.1 - ajout sort-by=random (merci manuelvoileux)
 * v30 - ajout mot-clé maintitle
 * v3.1
 * - ajout option 'content-plugin'
 * - prise en charge des mots -clés pour les customs-fields
 * v5.1 - ajout option featured
 * v5.2 - ajout motclé ##date-max## et options new-date
 * v5.3.3 - Joomla 6 : remplacement de getInstance
 */
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Database\DatabaseInterface;

class jcontent_by_categories extends upAction
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
            'current' => '0', // 1 pour inclure l'article en cours
            'no-published' => '1', // Liste aussi les articles non publiés
            'featured' => 'show', // show : tous les articles, 0 ou hide : sauf les articles en vedette, 1 ou only : uniquement les articles en vedette
            'content-plugin' => 0, // prise en compte des plugins de contenu pour ##into et ##content##
            'author' => '', // filtre sur auteur: liste des id ou article, current
            'sort-by' => 'publish_up', // tri: title, ordering, created, modified, publish_up, id, hits, random
            'sort-order' => 'desc', // ordre de tri : asc, desc
            'no-content-html' => 'Pas de nouvelles, bonnes nouvelles ...[br]No news, good news ...', // texte si aucune correspondance. 0=aucun texte
            /* [st-model] Modèles de présentation */
            'template' => '', // modèle de mise en page. Si vide le modèle est le contenu
            /* [st-main] Balise et style pour le bloc principal */
            'main-tag' => 'div', // balise pour le bloc englobant tous les articles. 0 pour aucun
            'main-style' => '', // classes et styles inline pour bloc principal
            'main-class' => '', // classe(s) pour bloc principal (obsolète)
            /* [st-item] Balise et style pour un article */
            'item-tag' => 'div', // balise pour le bloc d'un article. 0 pour aucun
            'item-style' => '', // classes et styles inline pour un article
            'item-class' => '', // classe(s) pour un article (obsolète)
            /* [st-img] Paramètre pour l'image */
            'image-src' => '//lorempixel.com/300/300', // image par défaut
            'image-alt' => 'news', // image, texte alternatif par défaut
            /* [st-format] Format pour les mots-clés */
            'date-format' => '%e %B %Y', // format pour les dates
            'date-locale' => '', // localisation pour les dates. Par défaut, celle du navigateur client.
            'new-days' => '30', // nombre de jours depuis la création de l'article
            'new-date' => 'featured', // date utilisée pour le calcul de new-day : featured, created, modified, max (la plus récente)
            'new-html' => '[span class="badge bg-red t-white"]nouveau[/span]', // code HTML pour badge NEW
            'featured-html' => '&#x2B50 ', // code HTML pour article en vedette
            'tags-list-prefix' => '', // texte avant les autres éventuels tags
            'tags-list-style' => 'badge;margin-right:4px', // classe ou style affecté à une balise span par mot-clé
            'tags-list-separator' => ' ', // séparateur entre mots-clés
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

        $options['template'] = $this->get_bbcode($options['template'], '+hr|pre');
        $options['new-html'] = $this->get_bbcode($options['new-html']);
        $options['no-content-html'] = $this->get_bbcode($options['no-content-html']);
        // ======> verif template (modèle de mise en page)
        // en priorité : le sontenu entre shortcode
        // en second : le model dans prefs.ini
        if ($this->content) {
            $tmpl = $this->get_bbcode($this->content, '+hr|pre');
        } else {
            $tmpl = $options['template'];
        }
        if (! $tmpl) {
            $this->msg_error($this->trad_keyword('NO_CONTENT'));
            return false;
        }

        // ======> contrôle clé de tri
        $list_sortkey = 'title, ordering, created, modified, publish_up, id, hits, random';
        $options['sort-by'] = $this->ctrl_argument($options['sort-by'], $list_sortkey);

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
        } else {
            $artid = $this->article->id ?? ''; // v5.1
        }
        $catid = explode(',', $catid);

        // =====> RECUP DES DONNEES
        // Get an instance of the generic articles model
        $model = Factory::getApplication()->bootComponent('com_content')->getMVCFactory()->createModel('Articles', '', array(
            'ignore_request' => true
        ));
        if (is_bool($model)) {
            return 'Aucune catégorie';
        }

        // Set application parameters in model
        $app = Factory::getApplication();
        $appParams = $app->getParams();
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

        // etat en vedette
        $options['featured'] = strtolower($options['featured']);
        if ($options['featured'] === '1') {
            $options['featured'] = 'only';
        }
        if ($options['featured'] === '0') {
            $options['featured'] = 'hide';
        }
        $model->setState('filter.featured', $options['featured']);

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

        // Access filter
        $access = ! ComponentHelper::getParams('com_content')->get('show_noauth');
        $authorised = Access::getAuthorisedViewLevels(Factory::getApplication()->getIdentity()->id);
        $model->setState('filter.access', $access);

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
        $sItem_attr = array();
        $tags_list_attr = array();
        $this->get_attr_style($main_attr, $options['main-class'], $options['main-style']);
        $this->get_attr_style($sItem_attr, $options['item-class'], $options['item-style']);
        $this->get_attr_style($tags_list_attr, $options['tags-list-style']);

        // css-head
        $this->load_css_head($options['css-head']);

        // ======> mise en forme résultat
        if ($options['main-tag'] != '0') {
            $html[] = $this->set_attr_tag($options['main-tag'], $main_attr);
        }
        foreach ($items as $item) {
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
                if (stripos($sItem, '##intro') !== false) {
                    $item->introtext = $this->import_content($item->introtext);
                } // v31
                if (stripos($sItem, '##content##') !== false) {
                    $item->fulltext = $this->import_content($item->fulltext);
                }
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
            // {subtitle} : sous-titre de l'article (partie après tilde du titre)
            $this->kw_replace($sItem, 'subtitle', $subtitle);
            // {maintitle} : titre principal de l'article (avant tilde) v3.0
            $this->kw_replace($sItem, 'maintitle', $maintitle);
            // {alias} : alias de l'article v1.8
            $this->kw_replace($sItem, 'alias', $item->alias);
            // {date-crea} : date de création
            $this->kw_replace($sItem, 'date-crea', $this->up_date_format($item->created, $options['date-format'], $options['date-locale']));
            // {date-modif} : date de modification
            $this->kw_replace($sItem, 'date-modif', $this->up_date_format($item->modified, $options['date-format'], $options['date-locale']));
            // {date-publish} : date de publication
            $this->kw_replace($sItem, 'date-publish', $this->up_date_format($item->publish_up, $options['date-format'], $options['date-locale']));
            // date significative. Dans l'ordre modif, publication, création
            if (stripos($sItem, '##date-max') !== false) {
                $datemax = max($item->publish_up, $item->modified, $item->created);
                $datemax = $this->up_date_format($datemax, $options['date-format'], $options['date-locale']);
                $this->kw_replace($sItem, 'date-max', $datemax);
            }
            // {author} : auteur
            $this->kw_replace($sItem, 'author', $item->author);
            // {intro} : texte d'introduction en HTML
            $this->kw_replace($sItem, 'intro', $item->introtext);
            $this->kw_replace($sItem, 'content', $item->fulltext);
            if (stripos($sItem, '##note##') !== false) {
                $db = Factory::getContainer()->get(DatabaseInterface::class);
                $query = $db->getQuery(true);
                $query->select('note')
                    ->from('#__content')
                    ->where('id = ' . $item->id);
                $db->setQuery($query);
                $result = $db->loadResult();
                //$sItem = str_ireplace('##note##', $result, $sItem); // v51
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
                switch ($options['new-date']) {
                    case 'max':
                        $datemax = max($item->created, $item->modified, $item->publish_up);
                        $new = ($datemax > $max) ? $options['new-html'] : '';
                        break;
                    case 'created':
                        $new = ($item->created > $max) ? $options['new-html'] : '';
                        break;
                    case 'modified':
                        $new = ($item->modified > $max) ? $options['new-html'] : '';
                        break;
                    default:
                        $new = ($item->publish_up > $max) ? $options['new-html'] : '';
                }

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
                if (! empty($images->image_intro)) { // v31
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
            // {intro-text,100} : les 100 premiers caractères de l'introduction en texte brut
            preg_match('#\#\#(intro-text\s*,?\s*([0-9]*)\s*)\#\##Ui', $sItem, $tag);
            if (isset($tag[1])) {
                $intro = ($item->introtext) ? $item->introtext : $item->fulltext;
                $intro = $this->import_content($intro);
                $intro = trim(strip_tags($intro));
                $intro = str_replace(PHP_EOL, ' ', $intro); // v51 ote les saut de ligne pour affcihage dans tableau
                if (isset($tag[1]) && $tag[1]) {
                    $len = (int) $tag[2];
                    if ($len > 0 && strlen($intro) > $len) { // fix 5.2
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
                $upname = array();
                if (preg_match_all('#\{up (.*)[ \=\}]+#Us', $fulltext, $upname) > 0) {
                    $freqs = array_count_values($upname[1]);
                    foreach ($freqs as $k => $v) {
                        $tmp .= $k . '&nbsp;(' . $v . ') ';
                    }
                }
                $this->kw_replace($sItem, 'uplist', $tmp);
            }

            // les custom fields (v3.1)
            if (strpos($sItem, '##') !== false) {
                require_once($this->upPath . '/assets/lib/kw_custom_field.php');
                kw_cf_replace($sItem, $item);
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

        return implode(PHP_EOL, $html);
    }

    // run
}

// class
