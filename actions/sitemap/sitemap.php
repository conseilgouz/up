<?php

/**
 * Création d'un fichier 'sitemap.xml' en racine du site
 *
 * syntaxe {up sitemap}
 *
 * @author  LOMART
 * @version  UP-2.7
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags   Expert
 *
 */
/*
 * 3.0 modif entete urlset + url pour menu
 * 3.1 reprise complete. Liens sur les menus, puis sur les articles ciblés par le menu
 */
defined('_JEXEC') or die();

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Database\DatabaseInterface;

class sitemap extends upAction
{

    function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    function run()
    {

        // lien vers la page de demo
        // - 0 pour cacher le lien vers demo car inexistante
        $this->set_demopage(0);

        $options_def = array(
            __class__ => '', // nom menutype exclus. séparateur: virgule
            'cron' => '+1 semaine', // délai entre 2 générations automatiques
            'frequency' => '', // fréquence : always, hourly, daily, weekly, monthly, yearly, never
            'priority' => '', // priority de 0.1 à 1
            'menutype-exclude' => '', // nom menutype à exclure (idem option principale)
            'info' => '0', // afficher le nombre de liens et la liste des pages non indexées
            'id' => '' // identifiant
        );

        // fusion et controle des options
        $this->options = $this->ctrl_options($options_def);

        // === CRON : exécution périodique
        if ($this->cron_ok($this->options['cron']) !== true) {
            return '';
        }

        // ==============================================================
        // CONTROLES ET PREPARATION DES OPTIONS
        // ==============================================================

        $this->frequency = ($this->options['frequency']) ? '<changefreq>' . $this->options['frequency'] . '</changefreq>' : '';
        $this->priority = ($this->options['priority']) ? '<priority>' . $this->options['priority'] . '</priority>' : '';

        // ==== l'option principale peut contenir les menutypes a exclure
        $this->options['menutype-exclude'] = $this->str_append($this->options['menutype-exclude'], $this->options[__class__], ',');
        $menus = array_map('trim', explode(',', $this->options['menutype-exclude']));
        $menutypeExclus = '';
        foreach ($menus as $menu) {
            $this->add_str($menutypeExclus, $menu, ',', '"', '"');
        }

        // ==== Variables globales
        $this->info = ''; // le compte-rendu si option info=1
        $this->link = array(); // les liens valides
        $this->invalid = array(); // les liens invalides
        $this->today = date('Y-m-d H:i:s', time());

        // ==== robots index - config par défaut
        $app = Factory::getApplication();
        $tmp = $app->getCfg('robots', 'index, follow');
        $okk = stripos($app->getCfg('robots', 'index, follow'), 'noindex');
        $cfgIndex = (stripos($app->getCfg('robots', 'index, follow'), 'noindex') === false);
        if ($this->options['info'])
            $this->info .= '<br>' . $this->trad_keyword('VAL_CONFIG_INDEX', (($cfgIndex) ? 'index' : 'no-index'));
        // ==== les catégories
        // $this->catIndex[catid] = true si robots index pour la catégorie
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery();
        $query->select('id, metadata, title');
        $query->from($db->quoteName('#__categories'));
        // $foo = $query->__toString();
        $db->setQuery($query);
        $results = $db->loadObjectList();
        foreach ($results as $res) {
            $meta = json_decode($res->metadata, true);
            if (empty($meta['robots'])) {
                $this->catIndex[$res->id] = $cfgIndex;
            } else {
                $this->catIndex[$res->id] = (stripos($meta['robots'], 'noindex') === false);
                if (! $this->catIndex[$res->id] && $this->options['info'])
                    $this->info .= '<br>' . $this->trad_keyword('VAL_CAT_INDEX', $res->id, $res->title);
            }
        }

        // ==============================================================
        // RECUPERATION DES LIENS DES MENUS
        // ==============================================================

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery();

        $query->select($db->quoteName(array(
            'id',
            'menutype',
            'title',
            'link',
            'path',
            'params',
            'publish_up',
            'publish_down'
        )));
        $query->from($db->quoteName('#__menu'));
        $query->where($db->quoteName('client_id') . '= 0');
        if ($menutypeExclus)
            $query->where($db->quoteName('menutype') . 'NOT IN (' . $menutypeExclus . ')');
        $query->where($db->quoteName('published') . '= 1');
        $query->where($db->quoteName('access') . '= 1');

        // $foo = $query->__toString();
        $db->setQuery($query);
        $results = $db->loadObjectList();

        // ==== mise en forme XML des liens retenus
        $uri = Uri::getInstance();
        $root = $uri->root();

        foreach ($results as $link) {
            $url = $root . $link->path;
            $metadata = json_decode($link->params);
            if (! isset($metadata->robots)) {
                $this->invalid[] = '<p>Menu <a href="' . $url . '">' . $link->title . '</a> (' . $link->menutype . '/' . $link->id . ') external URL, alias, separator, ...</p>';
            } elseif (str_contains($metadata->robots, 'noindex')) {
                $this->invalid[] = '<p>Menu <a href="' . $url . '">' . $link->title . '</a> (' . $link->menutype . '/' . $link->id . ') menu robots=noindex.</p>';
            } elseif (! ((is_null($link->publish_up) || $link->publish_up <= $this->today) && (is_null($link->publish_down) || $link->publish_down >= $this->today))) {
                $this->invalid[] = '<p>Menu <a href="' . $url . '">' . $link->title . '</a> (' . $link->menutype . '/' . $link->id . ') menu published between ' . $link->publish_up . ' and ' . $link->publish_down . '</p>';
            } else {
                $this->link[$url] = '';
                // ==== Les liens vers les articles accessibles par les menus
                if (str_contains($link->link, 'com_content')) {
                    $this->get_article_links($link->link);
                }
            }
        }

        // pour info
        if ($this->options['info']) {
            $this->info .= '<h2>' . count($this->link) . ' liens ajoutés dans le sitemap</h2>';
            $this->info .= '<h2>Lien(s) non repris dans le sitemap</h2>';
            sort($this->invalid);
            $this->info .= implode(PHP_EOL, $this->invalid);
        }

        // --- debut sitemap.xml
        /*
         * $xml = <<<urlset
         * <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
         * xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         * xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
         * http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
         * urlset;
         * $xml .= PHP_EOL . '<?xml version="1.0" encoding="UTF-8" ?>';
         */

        // on tri les liens
        ksort($this->link);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($this->link as $url => $lastmod) {
            $str = '<url>';
            $str .= '<loc>' . $url . '</loc>';
            $str .= $lastmod;
            $str .= $this->frequency;
            $str .= $this->priority;
            $str .= '</url>';
            $xml .= PHP_EOL . $str;
        }
        $xml .= PHP_EOL . '</urlset>';
        file_put_contents('sitemap.xml', $xml);

        return $this->info;
    }

    // run

    /*
     * ajoute la liste des liens à indexer
     */
    function get_article_links($link)
    {

        // ==== TOUS LES ARTICLES PUBLIQUEs AVEC ROBOTS:INDEX
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery();

        $query->select($db->quoteName(array(
            'a.id',
            'a.title',
            'a.alias',
            'a.state',
            'a.access',
            'a.modified',
            'a.publish_up',
            'a.publish_down',
            'a.metadata',
            'a.catid',
            'a.language',
            'a.featured'
        )));
        $query->from($db->quoteName('#__content', 'a'));
        $query->where($db->quoteName('a.access') . '=1');

        // ---- les critères spécifiques
        $state = 1;
        $link = parse_url($link);
        parse_str($link['query'], $params);
        switch ($params['view']) {
            case 'article':
                // article : index.php?option=com_content&view=article&id=150
                $query->where($db->quoteName('a.ID') . '=' . $params['id']);
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
        $query->where($db->quoteName('a.state') . '=' . $state);
        // ---- fin des critères spécifiques

        $query->order('a.modified DESC');
        // $foo = $query->__toString();
        $db->setQuery($query);
        $results = $db->loadObjectList();

        // === MISE EN FORME
        $uri = Uri::getInstance();
        // $root = $uri->root();
        $root = $uri->toString(array(
            'scheme',
            'host',
            'port'
        ));

        foreach ($results as $link) {
            // le lien
            $link->slug = $link->id . ':' . $link->alias;
            $route = Route::_(RouteHelper::getArticleRoute($link->slug, $link->catid, $link->language));
            $url = $root . Route::_($route);

            // index or noindex de l'article ?
            $metadata = json_decode($link->metadata);
            if (empty($metadata->robots)) {
                $index = $this->catIndex[$link->catid];
                $index_info = ($this->catIndex[$link->catid]) ? ' cat-index' : 'cat-noindex';
            } else {
                $index = (stripos($metadata->robots, 'noindex') === false);
                $index_info = ' art:' . $metadata->robots;
            }
            if (! $index) {
                $this->invalid[] = '<p>Article <a href="' . $url . '">' . $link->title . '</a> (id:' . $link->id . ' | catid:' . $link->catid . ') modif: ' . $link->modified . '</p>';
                continue;
            }
            // dans la période de publication ?
            if (! ((empty($link->publish_up) || $link->publish_up <= $this->today) && ((int) $link->publish_down == 0 || $link->publish_down >= $this->today))) {
                $this->invalid[] = '<p>Article OUT OF PUBLISH: ' . $link->publish_up . ' TO ' . $link->publish_down . ': <a href="' . $url . '">' . $link->title . '</a> (' . $link->id . ') ' . $link->modified . ' --' . $index_info . '</p>';
                continue;
            }
            // c'est ok, on ajoute au sitemap
            $this->link[$url] = '<lastmod>' . date('Y-m-d\TH:i:sP', strtotime($link->modified)) . '</lastmod>';
        }
    }

    /*
     * Test si l'action péridique doit-être faite
     * $interval : delai en secondes ou + 1 jour 1 heure
     * $datetime_first : dateheure (YYYYMMDDHHMM) première exécution (non utilisé ici)
     */
    function cron_ok($interval, $datetime_first = null)
    {
        if (empty($interval))
            return true; // toujours

        date_default_timezone_set('Europe/Paris');

        // première exécution
        if (! is_null($datetime_first = null)) {
            if ((date('YmdHi') <= $datetime_first))
                return false;
        }

        // lire fichier
        $filename = 'tmp/up-' . $this->name . '-' . $this->options['id'] . '.cron';
        if (file_exists($filename)) {
            $lastdate = file_get_contents($filename);
            $date = date('YmdHis');
            if (date('YmdHis') < $lastdate)
                return false; // l'heure n'est pas arrivée
        }
        // MAJ fichier
        if ($interval[0] == '+') {
            $term_fr = array(
                'année',
                'an',
                'mois',
                'jour',
                'semaine',
                'heure',
                'seconde'
            );
            $term_en = array(
                'year',
                'year',
                'month',
                'day',
                'week',
                'hour',
                'second'
            );
            $interval = str_ireplace($term_fr, $term_en, $interval);
        } else {
            $interval = '+' . $interval . ' second';
        }

        $lastdate = date('YmdHis', strtotime($interval));
        file_put_contents($filename, $lastdate);

        return true; // on exécute
    }
}

// class
