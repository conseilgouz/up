<?php

/**
 * liste des articles d'une catégorie
 *
 * syntaxe : {up jcontent-list=id-catégorie(s)}
 *
 * Une action très pratique pour lister les articles de la catégorie en cours, il suffit de taper {up jcontent-list}
 *
 * @author   LOMART
 * @version  UP-0.9
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Joomla
 */

/*
 * v2.6 -  fix toutes les catégories pour J4
 * v5.3.3 - Joomla 6 : remplacement de getInstance
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Access\Access;
use Joomla\Database\DatabaseInterface;

class jcontent_list extends upAction
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
            __class__ => '', // ID de la catégorie ou vide pour celle de l'article actuel
            'maxi' => '', // Nombre maxi d'article dans la liste
            'no-published' => '1', // Liste aussi les articles non publiés
            'author' => '', // filtre sur auteur: liste des id ou article, current
            'sort-by' => 'title', // tri: title, ordering, created, modified, publish_up, id, hits
            'sort-order' => 'asc', // ordre de tri : asc, desc
            /* [st-main] Style du bloc principal */
            'id' => '', // identifiant
            'main-class' => '', // classe(s) pour bloc principal (obsoléte)
            'main-style' => '', // classes et styles inline pour bloc principal
            'class' => '', // idem main-class. Conservé pour compatibilité descendante
            'style' => '', // idem main-style. Conservé pour compatibilité descendante
            /* [st-title] Titre : balise et style */
            'title' => '', // titre HTML si article trouvé.
            'title-tag' => 'h3', // niveau du titre
            'title-style' => '', // classes et styles inline pour le titre
            'title-class' => '', // classe(s) pour le titre (obsoléte)
            /* [st-list] Style de la liste*/
            'list-style' => '', // classes et styles inline pour la liste
            'list-class' => '', // classe(s) pour la liste (obsoléte)
            /* [st-css] Style CSS */
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
        // compatibilité v1.6.3
        $old_options = array(
            'sort_by' => 'sort-by',
            'sort_order' => 'sort-order',
            'no_published' => 'no-published-by'
        );
        foreach ($old_options as $old => $new) {
            if (isset($this->options_user[$old])) {
                $this->options_user[$new] = $this->options_user[$old];
                unset($this->options_user[$old]);
            }
        }

        // ======> fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // ======> contrôle clé de tri
        $list_sortkey = 'title, ordering, created, modified, publish_up, id, hits';
        $options['sort-by'] = $this->ctrl_argument($options['sort-by'], $list_sortkey);

        $catid = $options[__class__];
        if ($catid == '') {
            // la catégorie de l'article en cours
            if (isset($this->article->id)) {
                // la catégorie de l'article en cours
                $catid = $this->article->catid;
            } else {
                // appel d'un module : catégorie courante pour la page
                $app = Factory::getApplication();
                $artid_current = $app->getInput()->get('id', 0);
                $view = $app->getInput()->get('view', 0);
                switch ($view) {
                    case 'article':
                        $database = Factory::getContainer()->get(DatabaseInterface::class);
                        $query = "SELECT catid FROM #__content WHERE id=" . $artid_current;
                        $database->setQuery($query);
                        $row = $database->loadObject();
                        $catid = ($row != null) ? $row->catid : '';
                        break;
                    case 'categories':
                    case 'category':
                        $catid = $artid_current;
                        break;
                    default:
                        $catid = '';
                        break;
                }
            }
        } elseif ($catid == 0) {
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
        // $model->setState('filter.category_id', array($catid));
        if ($catid !== 'all') {
            $model->setState('filter.category_id', $catid);
        }

        // Filter by language
        $model->setState('filter.language', $app->getLanguageFilter());

        // Ordering
        $model->setState('list.ordering', 'a.' . $options['sort-by']);
        $model->setState('list.direction', $options['sort-order']);

        $items = $model->getItems();

        if (count($items) == 0) {
            return '';
        }

        // css-head
        $this->load_css_head($options['css-head']);

        // ======> mise en forme résultat
        $artlist = array();
        if (count($items)) {
            foreach ($items as $item) {
                $url = '';
                $slug = ($item->alias) ? ($item->id . ':' . $item->alias) : $item->id;
                $catslug = ($item->category_alias) ? ($item->catid . ':' . $item->category_alias) : $item->catid;
                $route = RouteHelper::getArticleRoute($slug, $catslug);
                $url = Route::_($route);

                $artlist[] = '<a href="' . $url . '">' . $item->title . '</a>';
            }
        }

        // attributs du bloc principal
        $this->get_attr_style($attr_main, $options['main-class'], $options['main-style']);
        // pour compatibilité v1.6
        $this->get_attr_style($attr_main, $options['class'], $options['style']);

        // attributs du bloc titre
        $this->get_attr_style($attr_title, $options['title-class'], $options['title-style']);

        // attributs du bloc list
        $this->get_attr_style($attr_list, $options['list-class'], $options['list-style']);

        // ======> code en retour
        $out = $this->set_attr_tag('div', $attr_main);
        if ($options['title']) {
            $out .= $this->set_attr_tag($options['title-tag'], $attr_title, $options['title']);
        }
        $out .= $this->set_attr_tag('ul', $attr_list);
        foreach ($artlist as $lign) {
            $out .= '<li>' . $lign . '</li>';
        }
        $out .= '</ul>';
        $out .= '</div>';

        return $out;
    }

    // run
}

// class
