<?php

/**
 * Les articles par categories et sous-categories
 *
 * syntaxe 1 : {up jcontent-by-subcat=id-categorie}
 * liste des articles d'une catégorie et ses sous-catégories
 * syntaxe 2 : {up jcontent-by-subcat}
 * liste des articles de la catégorie en cours et ses sous-catégories
 * syntaxe 3 : {up jcontent-by-subcat=0}
 * liste des articles de toutes les catégories
 * syntaxe 4 : {up jcontent-by-subcat}##title##{/up jcontent-by-subcat}
 * variante : saisie template pour articles entre shortcodes
 *
 * <b>Les mots-clés article:</b>
 * ##title## ##title-link## ##subtitle## ##link##
 * ##intro## ##intro-text## ##intro-text,100## ##content##
 * ##image## ##image-src## ##image-alt##
 * ##date-crea## ##date-modif## ##date-publish##
 * ##author## ##note## ##cat## ##new## ##featured## ##hits## ##tags-list##
 * ##upnb## : nbre actions UP dans la page - ##uplist## : nbre par actions
 * ##CF_id_or_name## : valeur brute du custom field
 *
 * <b>Les mots-clés catégorie:</b>
 * ##catpath## : Chemin des categories depuis la categorie racine.
 * ex: si on demande la categorie 2 avec 3 niveaux (subcat=3): 2.1 > 2.1.1 > 2.1.1.1
 * ##title## ##title-link## ##link## : titre et lien de la categorie
 * ##alias## ##note## ##id## ##count## : nombre d'articles dans la catégorie
 *
 * @author LOMART
 * @version UP-2.5
 * @license <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags     Joomla
 */
/**
* v5.3.3 - Joomla 6 : remplacement de getInstance
*/
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Database\DatabaseInterface;

/*
 * VARIABLES GLOBALES
 * $this->catItems : tableau des catégories
 * $this->artid : ID de l'article courant (meme si appel d'un module)
 * $this->cat_model : model Joomla pour recup catégories
 * $this->art_model : model Joomla pour recup articles
 */

/*
 * v2.9 - compatibilité PHP8 pour ##date-xxx##
 * - ajout mots-clés ##upnb## et ##uplist##
 * v3.1
 * - ajout option 'content-plugin'
 * - prise en charge des mots -clés pour les customs-fields
 *
 */
class jcontent_by_subcat extends upAction
{
    public function init()
    {
        // charger les ressources communes a toutes les instances de l'action
        return true;
    }

    public function run()
    {
        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        $options_def = array(
            /* [st-selcat] Sélection des catégories */
            __class__ => '', // ID catégorie, vide pour celle de l'article actuel ou 0 pour toutes
            'exclude' => '', // liste des id des catégories non reprises si option principale=0
            'cat-level' => 99, // 0 a 99 - niveau maxi exploration des sous-catégories
            /* [st-selart] filtrage et tri des articles */
            'maxi' => '', // Nombre maxi d'articles dans chaque catégorie. Vide = tous
            'current' => '0', // 1 pour inclure l'article en cours
            'content-plugin' => 0, // prise en compte des plugins de contenu pour ##into et ##content##
            'no-published' => '0', // Liste aussi les articles non publies
            'author' => '', // filtre sur auteur: liste des id user ou article, current
            'sort-by' => 'title', // tri: title, ordering, created, modified, publish_up, id, hits
            'sort-order' => 'asc', // ordre de tri : asc, desc
            /* [st-cat] Paramètres d'affichage des catégories */
            'cat-template' => '[small]##catpath##[/small] [b]##title##[/b]', // modèle pour les lignes de catégories
            'cat-tag' => 'h5', // balise pour ligne catégorie. LI pour passer en format liste UL/LI
            'cat-class' => '', // classe(s) pour la ligne catégorie
            'cat-style' => '', // style pour la ligne catégorie
            'cat-separator' => '»', // pour séparer l'arborescence des catégories
            'cat-root-view' => 1, // afficher l'unique catégorie racine. root=jamais, plusieurs=toujours
            /* [st-art] Paramètres d'affichage des articles */
            'template' => '', // modele de mise en page. Si vide le modèle est le contenu
            'item-tag' => 'div', // balise pour le bloc d'un article.
            'item-style' => '', // classes et styles inline pour un article
            'item-class' => '', // classe(s) pour un article (obsolete)
            /* [st-main] style du bloc principal */
            'main-tag' => 'p', // balise pour le bloc englobant tous les articles.
            'id' => '', // identifiant pour main-tag
            'main-style' => '', // classes et styles inline pour bloc principal
            'main-class' => '', // classe(s) pour bloc principal (obsolete)
            /* [st-format] Format pour les mots-clés */
            'date-format' => '%e %B %Y', // format pour les dates
            'date-locale' => '', // localisation pour les dates. Par defaut, celle du navigateur client.
            'new-days' => '30', // nombre de jours depuis la création de l'article pour badge 'nouveau'
            'new-html' => '[span class="badge bg-red t-white"]nouveau[/span]', // code HTML pour badge NEW
            'featured-html' => '&#x2B50 ', // code HTML pour article en vedette
            'image-src' => '//lorempixel.com/300/300', // image par defaut
            'image-alt' => 'news', // image, texte alternatif par defaut
            'tags-list-prefix' => '', // texte avant les autres éventuels tags
            'tags-list-style' => 'badge;margin-right:4px', // classe ou style affecte a une balise span par mot-cle
            'tags-list-separator' => ' ', // séparateur entre mots-cles
            /* [st-divers] Divers */
            'no-content-html' => 'lang[en=No content found;fr=Aucun contenu trouvé', // texte si aucune correspondance. 0=aucun texte
            'css-head' => '' // code CSS dans le head
        );

        /*
         * ------------------------------
         * fusion et controle des options
         * ------------------------------
         */
        $this->options = $this->ctrl_options($options_def);
        $this->options['cat-template'] = $this->get_bbcode($this->options['cat-template'], false);
        $this->options['cat-separator'] = ' ' . $this->options['cat-separator'] . ' ';
        // $this->options['template'] = $this->get_bbcode($this->options['template'], false);
        $this->options['new-html'] = $this->get_bbcode($this->options['new-html'], false);
        $this->options['no-content-html'] = $this->get_bbcode($this->options['no-content-html'], false);
        $this->options['exclude'] = explode(',', $this->options['exclude']);
        // -- verif template (modele de mise en page)
        // en priorite : le contenu entre shortcode
        // en second : le model dans prefs.ini
        $this->options['template'] = (trim($this->content)) ? $this->content : $this->options['template'];
        if ($this->options['template'] == '') {
            $this->options['template'] = '##title-link##';
        } else {
            $this->options['template'] = $this->get_bbcode($this->options['template'], '+hr|pre');
        }
        // sortie sous forme LIST
        $isList = false;
        if (strtolower($this->options['cat-tag']) == 'li' || strtolower($this->options['cat-tag']) == 'ul') {
            $isList = true;
        }
        // -- controle cle de tri
        $list_sortkey = 'title, ordering, created, modified, publish_up, id, hits';
        $this->options['sort-by'] = $this->ctrl_argument($this->options['sort-by'], $list_sortkey);
        $this->options['sort-order'] = $this->ctrl_argument($this->options['sort-order'], 'asc,desc');

        /*
         * --------------------------------------------------
         * liste des catégories demandees et article en cours
         * --------------------------------------------------
         */
        // $this->artid = $this->article->id; // v2.9
        $this->artid = (isset($this->article->id)) ? $this->article->id : null;
        $this->catRootIDs = $this->get_cat_root();
        if (empty($this->catRootIDs)) {
            $this->msg_error($this->trad_keyword('CAT_NOT_EXIST', $this->options[__class__]));
            return;
        }

        /*
         * --------------------------------------------------
         * RECUP DES CATEGORIES dans $this->catItems
         * --------------------------------------------------
         */
        $this->cat_model = Factory::getApplication()->bootComponent('com_content')->getCategory();

        // recuperer les sous-catégories
        $this->catItems = array();
        foreach ($this->catRootIDs as $catRootID) {
            $catRootItem = $this->cat_model->get($catRootID);
            if ($catRootItem != null) {
                // niveau sous-catégorie relatif
                $max = $catRootItem->level + $this->options['cat-level'];
                // affiche t-on la racine
                $this->catItems[] = $catRootItem;
                // recup sous-cat
                $this->catItems = array_merge($this->catItems, $this->get_subcat($catRootItem, $max));
            }
        }
        // retour si aucune catégorie
        if (empty($this->catItems)) {
            return ($this->options['no-content-html'] == '0') ? '' : $this->options['no-content-html'];
        }

        /*
         * --------------------------------------------------
         * PREPARATION RECUP DES ARTICLES
         * --------------------------------------------------
         */

        // criteres communs
        $this->art_model = $this->set_art_common_params();
        // ======> Style general et par article
        $main_attr['id'] = $this->options['id'];
        $this->get_attr_style($main_attr, $this->options['main-class'], $this->options['main-style']);
        $this->get_attr_style($this->art_attr, $this->options['item-class'], $this->options['item-style']);
        $this->get_attr_style($this->tags_list_attr, $this->options['tags-list-style']);

        $this->get_attr_style($this->cat_attr, $this->options['cat-class'], $this->options['cat-style']);

        // css-head
        $this->load_css_head($this->options['css-head']);

        /*
         * --------------------------------------------------
         * MISE EN FORME RESULTAT
         * --------------------------------------------------
         */

        if ($isList) {
            $out = implode(PHP_EOL, $this->get_content_format_list());
        } else {
            $out = implode(PHP_EOL, $this->get_content_format_normal());
        }

        if ($this->options['main-tag'] != '0') {
            $out = $this->set_attr_tag($this->options['main-tag'], $main_attr, $out);
        }
        // file_put_contents(JPATH_ROOT . '\tmp\jcontent_by_subcat.html', $out);

        return $out;
    }

    // run

    /*
     * get_subcat
     * ----------
     * fonction recursive de recup des sous-catégories
     */
    public function get_subcat($item, $max)
    {
        $catlist = array();
        $category = $this->cat_model->get($item->id);
        if ($category != null && $category->level < $max) {
            $items = $category->getChildren();
            foreach ($items as $item) {
                if (! in_array($item->id, $this->options['exclude'])) {
                    $catlist[] = $item;
                    $catlist = array_merge($catlist, $this->get_subcat($item, $max));
                }
            }
        }
        return $catlist;
    }

    /*
     * get_cat_root
     * ------------
     * Retourne un tableau avec les catégories demandees
     * vide : ID de la catégorie pour l'article en cours
     * 0 : array('root') // toutes
     * array(x,y,z) : les catégories demandees sous forme tableau
     *
     * fixe $this->options['cat-root-view']
     */
    public function get_cat_root()
    {
        $app = Factory::getApplication();
        $catid = $this->options[__class__];
        if ($catid == '') {
            // la catégorie de l'article en cours
            if (isset($this->article->catid)) {
                // la catégorie de l'article en cours
                $catid = $this->article->catid;
                $this->artid = $this->article->id;
            } else {
                // appel d'un module : catégorie courante pour la page
                $this->artid = $app->getInput()->get('id', 0);
                $view = $app->getInput()->get('view', 0);
                switch ($view) {
                    case 'article':
                        $database = Factory::getContainer()->get(DatabaseInterface::class);
                        $query = "SELECT catid FROM #__content WHERE id=" . $this->artid;
                        $database->setQuery($query);
                        $row = $database->loadObject();
                        $catid = ($row != null) ? $row->catid : '';
                        break;
                    case 'categories':
                    case 'category':
                        $catid = $this->artid;
                        break;
                    default:
                        $catid = '';
                        break;
                }
            }
        } elseif ($catid == '0') {
            // toutes les catégories
            $catid = 'root';
        }
        // Affichage nom du dossier racine
        $catid = explode(',', $catid);

        // plusieurs racines, on les affiche toujours
        if (count($catid) > 1) {
            $this->options['cat-root-view'] = 1;
            if ($catid[0] == 'root') {
                $this->options['cat-root-view'] = 0;
            }
        }

        return $catid;
    }

    /*
     * set_art_common_params
     * ---------------------
     * application des filtres pour recup des articles
     */
    public function set_art_common_params()
    {
        $model = Factory::getApplication()->bootComponent('com_content')->getMVCFactory()->createModel('Articles');

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
        if ($this->options['maxi'] != '') {
            $model->setState('list.limit', (int) $this->options['maxi']);
        }
        // etat publication
        if ($this->options['no-published'] == '0') {
            $model->setState('filter.published', 1);
        }

        // auteur(s)
        switch ($this->options['author']) {
            case 'article': // l'auteur de l'article en cours
                $model->setState('filter.author_id', $this->article->created_by);
                break;
            case 'current': // l'utilisateur connecte
                $userid = Factory::getApplication()->getIdentity()->id;
                $model->setState('filter.author_id', $userid);
                break;
            default:
                $model->setState('filter.author_id', explode(',', $this->options['author']));
                break;
        }

        // Filter by language
        $model->setState('filter.language', $app->getLanguageFilter());

        // Ordering
        $model->setState('list.ordering', 'a.' . $this->options['sort-by']);
        $model->setState('list.direction', $this->options['sort-order']);

        // article en cours
        if ($this->artid && $this->options['current'] != '1') { // fix 3.1
            $model->setState('filter.article_id', $this->artid);
            $model->setState('filter.article_id.include', false); // Exclude
        }

        return $model;
    }

    /*
     * --------------------------------------------
     * LECTURE ARTICLES ET AFFICHAGE EN MODE NORMAL
     * --------------------------------------------
     */
    public function get_content_format_normal()
    {
        $rootCatLevel = $this->catItems[0]->level; // niveau de depart du TV
        $catPath = array();

        for ($i = 0; $i < count($this->catItems); $i++) {
            if ($this->catItems[$i]->id == 'root') {
                continue;
            }
            if (in_array($this->catItems[$i]->id, $this->catRootIDs)) {
                // RAZ pour les catid demandes qui peuvent etre de niveaux differents
                $rootCatLevel = $this->catItems[$i]->level; // niveau de depart du TV
                $catPath = array();
            }

            $currCatItem = $this->catItems[$i];
            $currCatLevel = $this->catItems[$i]->level;
            $nextCatLevel = (isset($this->catItems[$i + 1])) ? $this->catItems[$i + 1]->level : $rootCatLevel;
            // les articles de la catégorie
            $this->art_model->setState('filter.category_id', $currCatItem->id);
            $artItems = $this->art_model->getItems();
            // ligne catégorie
            if (! (! $this->options['cat-root-view'] && $i == 0)) {
                $catLign = $this->get_cat_lign($currCatItem, count($artItems), $this->options['cat-template'], $catPath);
                $html[] = $this->set_attr_tag($this->options['cat-tag'], $this->cat_attr, $catLign);
            }
            // ligne(s) article
            foreach ($artItems as $artItem) {
                $artLign = $this->get_art_lign($artItem, $this->options['template']);
                $html[] = $this->set_attr_tag($this->options['item-tag'], $this->art_attr, $artLign);
            }
            // --- $catPath
            if ($nextCatLevel > $currCatLevel) {
                $catPath[] = $currCatItem->title;
            }
            while ($currCatLevel > $nextCatLevel) {
                array_pop($catPath);
                $currCatLevel--;
            }
        }

        return $html;
    }

    /*
     * -------------------------------------------
     * LECTURE ARTICLES ET AFFICHAGE EN MODE LISTE
     * -------------------------------------------
     */
    public function get_content_format_list()
    {
        $rootCatLevel = $this->catItems[0]->level; // niveau de depart du TV
        $catPath = array();

        $html[] = '<ul>'; // liste externe

        for ($i = 0; $i < count($this->catItems); $i++) {
            if ($this->catItems[$i]->id == 'root') {
                continue;
            }
            $cattitle = $this->catItems[$i]->title;
            if ($i > 0 && in_array($this->catItems[$i]->id, $this->catRootIDs)) {
                // RAZ pour les catid demandes qui peuvent etre de niveaux differents
                $html[] = str_repeat('</ul></li>', $this->catItems[$i]->level - $rootCatLevel);
                $rootCatLevel = $this->catItems[$i]->level; // niveau de depart du TV
                $catPath = array();
            }
            $tmp_title = $this->catItems[$i]->title;
            $currCatItem = $this->catItems[$i];
            $currCatLevel = $this->catItems[$i]->level;
            $nextCatLevel = (isset($this->catItems[$i + 1])) ? $this->catItems[$i + 1]->level : $rootCatLevel;

            // les articles de la catégorie
            $this->art_model->setState('filter.category_id', $currCatItem->id);
            $artItems = $this->art_model->getItems();

            // --- la ligne catégorie
            $catLign = $this->get_cat_lign($currCatItem, count($artItems), $this->options['cat-template'], $catPath);
            $html[] = $this->set_attr_tag('li', $this->cat_attr) . $catLign;
            // --- nouvelle branche
            // if (!empty($artItems) || $nextCatLevel > $currCatLevel)
            $html[] = '<ul>';
            // --- le bloc article
            foreach ($artItems as $artItem) {
                // file_put_contents('tmp/error.log', $artItem->id .' - '. $artItem->catid. ' - '.$artItem->title."\n", FILE_APPEND);
                $artLign = $this->get_art_lign($artItem, $this->options['template']);
                $html[] = $this->set_attr_tag($this->options['item-tag'], $this->art_attr, $artLign);
            }

            // --- $catPath
            if ($nextCatLevel > $currCatLevel) {
                $catPath[] = $currCatItem->title;
            }

            // --- fermer les listes
            while ($currCatLevel > $nextCatLevel && $nextCatLevel) {
                $html[] = '</ul></li>';
                $currCatLevel--;
                array_pop($catPath);
            }
            if ($currCatLevel == $nextCatLevel) {
                $html[] = '</ul>';
                $html[] = '</li>';
            }
        }
        // Suppression niv0 si pas cat-root-view
        if (! $this->options['cat-root-view'] && $this->catItems[0]->id != 'root') {
            array_pop($html);
            array_pop($html);
            array_shift($html);
            array_shift($html);
        }
        // Fin du treeview
        $html[] = '</ul>'; // liste externe

        if ($this->catItems[0]->id == 'root') {
            for ($i = $currCatLevel; $i > 0; $i--) {
                $html[] = '</li></ul>'; // liste externe
            }
        }
        return $html;
    }

    /*
     * function get_cat_lign
     * ----------------------
     * retourne le contenu de la ligne catégorie sans la balise et ses attributs
     *
     */
    public function get_cat_lign($catItem, $nbArt, $tmpl, $catPath)
    {
        $url = Route::_(RouteHelper::getCategoryRoute($catItem->id));

        // les catégories parentes
        if (stripos($tmpl, '##catpath##') !== false) {
            // on affiche pas le 1er niveau si cat-root-view = 0
            if (! $this->options['cat-root-view']) {
                array_shift($catPath);
            }
            $str = (empty($catPath)) ? '' : implode($this->options['cat-separator'], $catPath) . $this->options['cat-separator'];
            $this->kw_replace($tmpl, 'catpath', $str);
        }
        // {id} : ID de l'article
        $this->kw_replace($tmpl, 'id', $catItem->id);
        // {link} : lien vers l'article - a mettre dans balise a
        $this->kw_replace($tmpl, 'link', $url);
        // {title-link} : titre de l'article
        $this->kw_replace($tmpl, 'title-link', '<a href="' . $url . '">' . $catItem->title . '</a>');
        // {title} : titre de l'article
        $this->kw_replace($tmpl, 'title', $catItem->title);
        // {alias} : alias de l'article
        $this->kw_replace($tmpl, 'alias', $catItem->alias);
        // {note} : alias de l'article
        $this->kw_replace($tmpl, 'note', $catItem->note);
        // {count} : nombre article dans catégorie
        $this->kw_replace($tmpl, 'count', $nbArt);

        return $tmpl;
    }

    /*
     * function get_art_lign
     * ----------------------
     * retourne le contenu de la ligne article sans la balise et ses attributs
     *
     */
    public function get_art_lign($artItem, $tmpl)
    {
        // --- lien vers l'article
        $url = '';
        $slug = $artItem->id; // v2.6
        $catslug = ($artItem->category_alias) ? ($artItem->catid . ':' . $artItem->category_alias) : $artItem->catid;
        $route = RouteHelper::getArticleRoute($slug, $catslug);
        $url = Route::_($route);
        // --- le titre et sous titre
        $title = $artItem->title;
        $subtitle = '';
        if (stripos($tmpl, '##subtitle##') !== false) {
            $title = strstr($artItem->title . '~', '~', true);
            $subtitle = trim(substr(strstr($artItem->title, '~'), 1));
        }
        // --- traitement $artItem->introtext et $artItem->fulltext
        // si pas d'introtext -> introtext=fulltext et fulltext=vide
        // ce n'est pas souhaitable car le contenu fulltext n'est pas prevu pour cela
        if ($artItem->fulltext == '') {
            $artItem->fulltext = $artItem->introtext;
            $artItem->introtext = '';
        }
        // prise en charge plugins contenu v31
        if ($this->options['content-plugin']) {
            if (stripos($artItem, '##intro') !== false) {
                $artItem->introtext = $this->import_content($artItem->introtext);
            } // v31
            if (stripos($artItem, '##content##') !== false) {
                $artItem->fulltext = $this->import_content($artItem->fulltext);
            }
        }

        // ==== les remplacements
        // {id} : ID de l'article
        $this->kw_replace($tmpl, 'id', $artItem->id);
        // {link} : lien vers l'article - a mettre dans balise a
        $this->kw_replace($tmpl, 'link', $url);
        // {title-link} : titre de l'article
        $this->kw_replace($tmpl, 'title-link', '<a href="' . $url . '">' . $title . '</a>');
        // {title} : titre de l'article
        $this->kw_replace($tmpl, 'title', $title);
        // {subtitle} : sous-titre article partie apres tilde du titre
        $this->kw_replace($tmpl, 'subtitle', $subtitle);
        // {alias} : alias de l'article v1.8
        $this->kw_replace($tmpl, 'alias', $artItem->alias);
        // {date-crea} : date de création
        $this->kw_replace($tmpl, 'date-crea', $this->up_date_format($artItem->created, $this->options['date-format'], $this->options['date-locale']));
        // {date-modif} : date de modif
        $this->kw_replace($tmpl, 'date-modif', $this->up_date_format($artItem->modified, $this->options['date-format'], $this->options['date-locale']));
        // {date-publish} : date de publication
        $this->kw_replace($tmpl, 'date-publish', $this->up_date_format($artItem->publish_up, $this->options['date-format'], $this->options['date-locale']));
        // {author} : auteur
        $this->kw_replace($tmpl, 'author', $artItem->author);
        // {intro} : texte d'introduction en HTML
        $this->kw_replace($tmpl, 'intro', $artItem->introtext);
        $this->kw_replace($tmpl, 'content', $artItem->fulltext);
        if (stripos($tmpl, '##note##') !== false) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true);
            $query->select('note')
                ->from('#__content')
                ->where('id = ' . $artItem->id);
            $db->setQuery($query);
            $result = $db->loadResult();
            $this->kw_replace($tmpl, 'note', $result);
        }
        // {cat} : nom catégorie
        $this->kw_replace($tmpl, 'cat', $artItem->category_title);

        // {tags-list} : liste des tags
        if (stripos($tmpl, '##tags-list##') !== false) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true);
            $query->select('t.title')
                ->from('#__tags as t')
                ->innerJoin('#__contentitem_tag_map as m on t.id = m.tag_id')
                ->where('m.content_item_id = ' . $artItem->id . ' AND m.type_alias like "%article%"');
            $db->setQuery($query);
            // $debug = $query->__toString();
            $listTags = $db->loadObjectList();
            $tmpTags = (empty($listTags)) ? '' : $this->options['tags-list-prefix'];
            $tmpTag = array();
            foreach ($listTags as $tag) {
                if ($this->options['tags-list-style'] != '') {
                    $tmpTag[] = $this->set_attr_tag('span', $this->tags_list_attr, $tag->title);
                } else {
                    $tmpTag[] = $tag->title;
                }
            }
            $this->kw_replace($tmpl, 'tags-list', implode($this->options['tags-list-separator'], $tmpTag));
        }
        // {unpublish} : en vedette
        if (stripos($tmpl, '##state##') !== false) {
            $state_info = array(
                '-2' => '&#x267b;',
                '0' => '&#x2716;',
                '1' => '&#x2714;',
                '2' => '&#x25A4;'
            );
            $tmp = $state_info[$artItem->state];
            $this->kw_replace($tmpl, 'state', $tmp);
        }
        // {featured} : en vedette
        if (stripos($tmpl, '##featured##') !== false) {
            $tmp = ($artItem->featured == '1') ? $this->options['featured-html'] : '';
            $this->kw_replace($tmpl, 'featured', $tmp);
        }
        // {new} : badge
        if (stripos($tmpl, '##new##') !== false) {
            $max = date('Y-m-d H:i:s', mktime(date("H"), date("i"), 0, date("m"), date("d") - intval($this->options['new-days']), date("Y")));
            $new = ($artItem->publish_up > $max) ? $this->options['new-html'] : '';
            $this->kw_replace($tmpl, 'new', $new);
        }
        // {hits}
        $this->kw_replace($tmpl, 'hits', $artItem->hits);
        // {image-xxx} : l'image d'intro, sinon celle dans l'introtext
        // {image} : la balise img complete
        // {image-src} et {image-alt} : uniquement src et alt d'une balise img existante
        if (stripos($tmpl, '##image') !== false) {
            $images = json_decode($artItem->images);
            $img_src = $this->options['image-src'];
            $img_alt = $this->options['image-alt'];
            if ($images->image_intro) {
                $img_src = $images->image_intro;
                $img_alt = $images->image_intro_alt;
            } else {
                $imgTag = $this->preg_string('#(\<img .*\>)#Ui', $artItem->introtext);
                if ($imgTag) {
                    $imgAttr = $this->get_attr_tag($imgTag, 'alt');
                    $img_src = $imgAttr['src'];
                    $img_alt = $imgAttr['alt'];
                }
            }
            $img_tag = '<img src="' . $img_src . '" alt="' . $img_alt . '">';
            $this->kw_replace($tmpl, 'image', $img_tag);
            $this->kw_replace($tmpl, 'image-src', $img_src);
            $this->kw_replace($tmpl, 'image-alt', $img_alt);
        }

        preg_match('#\#\#(intro-text\s*,?\s*([0-9]*)\s*)\#\##Ui', $tmpl, $tag);
        if (isset($tag[1])) {
            $intro = (! empty($tmpl->introtext)) ? $tmpl->introtext : ($tmpl->fulltext ?? '');
            if (isset($tag[1]) && $tag[1]) {
                $len = (int) $tag[2];
                if ($len && strlen($intro) > $len) {
                    $intro = $this->import_content($intro);
                    $intro = trim(strip_tags($intro));
                    $intro = mb_substr($intro, 0, $len) . '...';
                }
            }
            $this->kw_replace($tmpl, $tag[1], $intro);
        }

        // {upnb} : nombre d'actions UP utilisées dans article
        $fulltext = (empty($artItem->fulltext)) ? $artItem->introtext : $artItem->fulltext;
        $this->kw_replace($tmpl, 'upnb', substr_count($fulltext, '{up '));
        // {uplist} : nombre d'occurence de chaque action
        if (stripos($tmpl, '##uplist##') !== false) {
            $fulltext = (empty($artItem->fulltext)) ? $artItem->introtext : $artItem->fulltext;
            $tmp = '';
            $upname = array();
            if (preg_match_all('#\{up (.*)[ \=\}]+#Us', $fulltext, $upname) > 0) {
                $freqs = array_count_values($upname[1]);
                foreach ($freqs as $k => $v) {
                    $tmp .= $k . '&nbsp;(' . $v . ') ';
                }
            }
            $this->kw_replace($tmpl, 'uplist', $tmp);
        }

        // les custom fields (v2.3)
        if (strpos($tmpl, '##') !== false) { // 3.1
            require_once($this->upPath . '/assets/lib/kw_custom_field.php');
            kw_cf_replace($tmpl, $artItem);
        }

        return $tmpl;
    }
}

// class
