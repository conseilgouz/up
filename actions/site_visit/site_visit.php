<?php

/**
 * comptabilise le nombre d'appel de cette action et affiche le nombre de visite
 *
 * syntaxe : 
 * {up site-visit} : incrémente le fichier log-path/alias_article.stat
 * {up site-visit=nom} : incrémente le fichier log-path/nom.stat
 * {up site-visit | info} : liste le contenu de tous les fichiers .stat dans log-path
 * {up site-visit | info=xxx} : liste le contenu de tous les fichiers action-xxx.stat dans log-path
 *
 * Les données sauvées dans xxx.stat sont ##alias##  : le nom du fichier, puis dans l'ordre :
 * ##counter##  : le cumul des visites
 * ##lastdate## : la date dernère consultation
 * ##id##, ##alias##, ##title##, ##created##, ##updated## : données de l'article
 * ##catid##, ##catalias## : id et alias de la catégorie de l'article
 * ##detail## : affiche le détail des visites par année, mois, langue
 * 
 *  
 * @version  UP-2.9
 * @author lomart
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Expert
 *
 */
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

class site_visit extends upAction
{

    function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
            /*[st-main]Options principales*/
            __class__ => '', // nom du compteur, si vide = alias article (si info=0)
            'catid-include' => '', // liste des id catégories à inclure, séparateur virgule
            'catid-exclude' => '', // liste des id catégories à exclure, séparateur virgule
            'usergroup-list' => '', // liste des groupes d'utilisateurs à exclure, séparateur virgule
            'ip-list' => '127.0.0.0, localhost', // liste des IP à ignorer. les botnets sont ignorés, séparateur virgule
            'bots-list' => 'bot,spider,crawler,libwww,search,archive,slurp,teoma,facebook,twitter', // liste de bots exclus
            /* [ST-RESULT] Options pour affichage résultats */
            'info' => '', // masque des fichiers stat et log dont le contenu est listé. vide = article courant, * = tous ou masque fichier
            'info-template' => '##counter## visites au ##lastdate##', // modele d'affichage
            'info-catid-include' => '', // liste des catégories prise en compte
            'info-sort' => '', // tri de la liste. Par défaut ##alias##. Tous les mots sont utilisables
            'info-sort-order' => 'asc', // sens de tri sur l'ensemble des mots-clé de info-sort
            'detail-period-style' => '', // style ajouté pour la période de la liste détaillée
            'use-bbcode' => 0, // utilise le format bbcode dans le résultat. A utiliser pour un export en CSV 
            'date-format' => 'lang[en=%B %se, %Y;fr=%e %B %Y]', // format pour la date
            'no-content-html' => "lang[en=No statistical data;fr=Aucune donnée statistique]", // message affiché si aucun résultat pour la sélection
            /* [ST-CSS-LIGN] Gestion style d'une ligne */
            'item-tag' => '', // balise pour les lignes de la liste
            'item-class' => '', // classe pour les lignes de la liste
            'item-style' => '', // style pour les lignes de la liste
            /* [ST-CSS-MAIN] Gestion style du bloc principal */
            'main-tag' => '', // balise pour la liste
            'id' => '', // identifiant
            'class' => '', // classe(s) pour bloc
            'style' => '', // style inline pour bloc
            'css-head' => '', // style ajouté dans le HEAD de la page
            /* [ST-EXPERT] Pour webmaster - à ajouter dans le fichier prefs.ini */
            'log' => 1, // argumente un fichier nom_compteur.log avec IP, langage et date
            'dir-logs' => 'up/site-visit', // dossier pour logs
        );

        // fusion et controle des options
        $this->options = $this->ctrl_options($options_def);

        // === actualiser ou compte-rendu ?
        if (empty($this->options['info'])) {
            // =======> on incremente les fichiers stat et log
            $this->incremente();
            return ''; // aucun retour
        } else {
            // =======> info : on affiche les compteurs stat et log
            // Recuperation contenu
            $content = $this->report(! empty($this->options['info-detailXXXXXXXXXXXXXXXXX']));

            // CSS-HEAD
            $this->load_css_head($this->options['css-head']);

            // attributs du bloc principal
            $attr_main = array();
            $attr_main['id'] = $this->options['id'];
            $this->get_attr_style($attr_main, $this->options['class'], $this->options['style']);

            // code en retour

            return $this->set_attr_tag($this->options['main-tag'], $attr_main, $content);
        }
    }

    // run

    /*
     * ------------------------------------------------------------------------
     * function incremente()
     * met à jour les fichiers stat et log
     * --------------------------------------------------------------------------
     */
    function incremente()
    {
        // --- uniquement pour les articles
        if (isset($this->article->alias)) {
            // le shortcode UP est dans un article
            $id = $this->article->id;
            $row['alias'] = $this->article->alias;
            $row['title'] = $this->article->title;
            $row['catid'] = $this->article->catid;
            $row['category_alias'] = $this->article->category_alias;
            $row['created'] = $this->article->created;
            $row['modified'] = $this->article->modified;
        } elseif (Factory::getApplication()->getInput()->get('view', 0) == 'article') {
            // le shortcode UP est dans un module et le contenu est un article
            $id = (int) Factory::getApplication()->getInput()->get('id', 0);
            $database = Factory::getContainer()->get(DatabaseInterface::class);
            $query = " SELECT a.alias, a.title,a.catid, c.alias AS category_alias, a.created, a.modified";
            $query .= " FROM #__content AS a";
            $query .= " INNER JOIN #__categories AS c";
            $query .= " WHERE a.id=" . $id;
            $query .= " AND c.id=a.catid";
            $database->setQuery($query);
            $row = $database->loadAssoc();
        }

        // ce n'est pas un article
        if (empty($row))
            return;

        // --- Exclure des catégories
        if (! empty($this->options['catid-exclude'])) {
            if (in_array($row['catid'], explode(',', $this->options['catid-exclude'])))
                return;
        }

        // --- uniquement certaines catégories
        if (! empty($this->options['catid-include'])) {
            if (! in_array($row['catid'], explode(',', $this->options['catid-include'])))
                return;
        }

        // --- Exclure les robots
        if (! empty($this->options['bots-list'])) {
            $agent = $_SERVER['HTTP_USER_AGENT'];
            if (! empty($agent)) {
                $botsList = array_map('trim', explode(',', $this->options['bots-list']));
                foreach ($botsList as $bot) {
                    if (stripos($agent, $bot) !== false) {
                        return;
                    }
                }
            }
        }

        // --- Exclure des IP client
        if (! empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // Filtrage sur IP
        if (! empty($this->options['ip-list'])) {
            $ipList = array_map('trim', explode(',', $this->options['ip-list']));
            if (in_array($ip, $ipList)) {
                return;
            }
        }
        // --- Exclure les membres d'un usergroup
        $user = Factory::getApplication()->getIdentity();
        if (! empty($this->options['usergroup-list']) && $user->groups) {
            $usergroup = $user->groups;
            $exclude = explode(',', $this->options['usergroup-list']);
            if (! empty(array_intersect($usergroup, $exclude))) {
                return;
            }
        }

        // ============================
        // ===== ON AJOUTE AUX FICHIERS
        // ============================

        $dirLogs = JPATH_ROOT . '/' . trim($this->options['dir-logs'], '/') . '/';
        if (! is_dir($dirLogs))
            mkdir($dirLogs, 0777, true);

        // ===> fichier stat : cumul, date derniere vue et id, alias, title, catid, category_alias, created, modified
        $fileStat = $dirLogs . $row['alias'] . '.stat';
        $nb = 0;
        if (file_exists($fileStat)) {
            list ($nb, $time) = explode('||', file_get_contents($fileStat));
            $nb = intval($nb);
        }
        $nb ++;
        file_put_contents($fileStat, $nb . '||' . date('Y-m-d H:i') . '||' . $id . '||' . implode('||', $row) . PHP_EOL, LOCK_EX);

        // ===> log : liste des visites avec date, ip, lang
        if ($this->options['log']) {
            $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            $lang = locale_accept_from_http($lang);
            $content = date('Y-m-d H:i') . '#' . $lang . '#' . $ip;
            file_put_contents($dirLogs . $row['alias'] . '.log', $content . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }

    /*
     * ------------------------------------------------------------------------
     * function report()
     * affiche la liste des fichiers stat avec nombre et date dernière visite
     * --------------------------------------------------------------------------
     */
    function report()
    {
        $dirLogs = JPATH_ROOT . '/' . trim($this->options['dir-logs'], '/') . '/';
        $stat_tmpl = $this->get_bbcode($this->options['info-template']);
        // le style
        $this->get_attr_style($attr_item, $this->options['item-class'], $this->options['item-style']);
        // on consolide les critères de tri. Il faut obligatoirement l'alias
        if (strpos($this->options['info-sort'], '##') !== false) {
            $sort_tmpl = $this->options['info-sort'];
            $sort_tmpl .= (strpos($sort_tmpl, '##alias##') === false) ? '##alias##' : '';
        }
        // le masque de sélection : vide = article courant
        $mask = $this->options['info'];
        if ($mask==1) {
            // l'article courant
            if (isset($this->article->alias)) {
                // le shortcode UP est dans un article
                $mask = $this->article->alias;
            } elseif (Factory::getApplication()->getInput()->get('view', 0) == 'article') {
                // le shortcode UP est dans un module et le contenu est un article
                $id = (int) Factory::getApplication()->getInput()->get('id', 0);
                $database = Factory::getContainer()->get(DatabaseInterface::class);
                $query = " SELECT alias FROM #__content";
                $query .= " WHERE id=" . $id;
                $database->setQuery($query);
                $mask = $database->loadResult();
            }
        } 

        $html = array();
        
        foreach (glob($dirLogs . $mask . '.stat') as $file) {
            $row = explode('||', file_get_contents($file));
            if ($this->options['info-catid-include']) {
                if (! in_array($row[5], explode(',', $this->options['info-catid-include'])))
                    continue;
            }
            $str = $stat_tmpl;
            $str = str_replace('##counter##', $row[0], $str);
            $str = str_replace('##lastdate##', $this->up_date_format($row[1], $this->options['date-format']), $str);
            $str = str_replace('##id##', $row[2], $str);
            $str = str_replace('##alias##', $row[3], $str);
            $str = str_replace('##title##', $row[4], $str);
            $str = str_replace('##catid##', $row[5], $str);
            $str = str_replace('##catalias##', $row[6], $str);
            $str = str_replace('##created##', $this->up_date_format($row[7], $this->options['date-format']), $str);
            $str = str_replace('##modified##', $this->up_date_format($row[8], $this->options['date-format']), $str);
            if (strpos($str, '##detail##') != false) {
                $str = str_replace('##detail##', $this->synthese($dirLogs . $row[3] . '.log'), $str);
            }
            // --- critère de tri
            if (isset($sort_tmpl)) {
                $sort = $sort_tmpl;
                $sort = str_replace('##counter##', sprintf('%08d', $row[0]), $sort);
                $sort = str_replace('##lastdate##', $row[1], $sort);
                $sort = str_replace('##id##', sprintf('%08d', $row[2]), $sort);
                $sort = str_replace('##alias##', $row[3], $sort);
                $sort = str_replace('##title##', $row[4], $sort);
                $sort = str_replace('##catid##', sprintf('%08d', $row[5]), $sort);
                $sort = str_replace('##catalias##', $row[6], $sort);
                $sort = str_replace('##created##', $row[7], $sort);
                $sort = str_replace('##modified##', $row[8], $sort);
                if (strpos($sort, '##') !== false)
                    $sort = $row[3]; // alias

                $html[$sort] = $this->set_attr_tag($this->options['item-tag'], $attr_item, $str);
            } else {
                $html[] = $this->set_attr_tag($this->options['item-tag'], $attr_item, $str);
            }
        }

        if (empty($html))
            return  $this->options['no-content-html'];

        if ($this->options['info-sort-order'] == 'desc') {
            krsort($html);
        } else {
            ksort($html);
        }
        return implode(PHP_EOL, $html);
    }

    /*
     * ------------------------------------------------------------------------
     * function synthese()
     * calcule une synthèse mensuelle des logs
     *
     * contenu fichier .log dans l'ordre d'apparition
     * log année precédente ------ : AAAA#LN || total || unique
     * log mois année en cours --- : AAAA-MM#LN || total || unique
     * log d'un jour consolidé --- : AAAA-MM-JJ#LN#121.0.0.1 || total || unique
     * log d'un jour non consolidé : AAAA-MM-JJ HH:MM#LN#121.0.0.1
     * ---------------------------------------------------------------------------
     */
    function synthese($logfile)
    {
        // récupération données
        $logs = explode(PHP_EOL, trim(file_get_contents($logfile)));
        if (empty($logs))
            return '';

        $thisday = date('Y-m-d');
        $thisyear = date('Y');
        $stats = array(); // array pour réecrire le fichier .stat

        /*
         *
         * lecture et ventilation du fichier $logs dans $stats avec cumul journalier
         *
         */
        foreach ($logs as $k => $log) {
            $log_array = explode('||', $log);
            $key = $log_array[0];
            if (count($log_array) == 1) {
                // stats non consolidés
                // AAAA-MM-JJ HH:MM#LN#121.0.0.1 devient AAAA-MM-JJ#LN#121.0.0.1
                $key = substr($key, 0, 10) . substr($key, 16);
                if (isset($stats[$key])) {
                    $stats[$key]['total'] ++;
                } else {
                    $stats[$key]['total'] = 1;
                    $stats[$key]['unique'] = 1;
                }
                // on supprime le log, il est cumulé dans $stats
                unset($logs[$k]);
            } else {
                $stats[$key]['total'] = $log_array[1];
                $stats[$key]['unique'] = $log_array[2];
            }
        }
        /*
         *
         * Ventilation avec cumul annuel et mensuel
         * on conserve les cumuls journaliers avec IP
         *
         */
        foreach ($stats as $key => $val) {
            if (strlen($key) > 10) {
                if ($key < $thisday) {
                    if ($key < $thisyear) { // année precedente
                        $k = substr($key, 0, 4) . substr($key, 10, 3);
                    } elseif ($key < $thisday) { // mois courant et precedent
                        $k = substr($key, 0, 7) . substr($key, 10, 3);
                    } // sinon today (meme key)
                    if (isset($stats[$key])) {
                        $stats[$k]['total'] += $stats[$key]['total'];
                        $stats[$k]['unique'] += $stats[$key]['unique'];
                    } else {
                        $stats[$k]['total'] = $stats[$key]['total'];
                        $stats[$k]['unique'] = $stats[$key]['unique'];
                    }
                    unset($stats[$key]);
                }
            }
        }
        /*
         * sauvegarder
         */
        unlink($logfile);
        foreach ($stats as $key => $val) {
            file_put_contents($logfile, $key . '||' . implode('||', $val) . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
        /*
         *
         * préparation affichage résultat pour retour au format :
         * 2021 (fr:200/150) 2022-01 (fr:60/40 gb:10/55) today (fr:60/40 gb:10/55)
         *
         */
        $out = '';
        $curr_period = '';
        $today = array();
        $attr_period = array();
        $this->get_attr_style($attr_period, $this->options['detail-period-style']);
        foreach ($stats as $key => $val) {
            list ($period, $lang) = explode('#', $key);
            if (strlen($key) > 10) {
                // cumul des valeurs du jour
                if (isset($today[$lang])) {
                    $today[$lang]['total'] += $val['total'];
                    $today[$lang]['unique'] ++;
                } else {
                    $today[$lang]['total'] = $val['total'];
                    $today[$lang]['unique'] = 1;
                }
            } else {
                // périodes antérieures
                if ($period != $curr_period) {
                    if ($out)
                        $out .= ') ';
                        $out .= $this->set_attr_tag('span', $attr_period, $period, true, $this->options['use-bbcode']) . ' (' . $lang . ':' . $val['total'] . '/' . $val['unique'];
                    $curr_period = $period;
                } else {
                    $out .= ' ' . $lang . ':' . $val['total'] . '/' . $val['unique'];
                }
            }
        }
        if ($out)
            $out .= ') ';
        // Ajout visites du jour
        if (! empty($today)) {
            $out .= $this->set_attr_tag('span', $attr_period, date('Y-m-d'), true, $this->options['use-bbcode']) . ' (';
            foreach ($today as $lang => $val) {
                $out .= $lang . ':' . $val['total'] . '/' . $val['unique'];
            }
            $out .= ') ';
        }

        return $out;
    }
}
        
// end class
