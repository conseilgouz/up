<?php

/**
 * comptabilise le nombre d'appel de cette action et affiche le nombre de visite
 *
 * syntaxe :
 * {up site-stats} : incrémente le fichier dir-logs/id-alias_article.log
 * {up site-stats=*} : Calcule et affiche les stats pour TOUS les fichiers
 * {up site-stats=0} : Calcule et affiche les stats pour l'article courant
 * {up site-stats=12} : Calcule et affiche les stats pour l'article d'ID=12
 * {up site-stats=mask} : affiche les stats des fichiers correspondant au mask
 *
 * L'affichage est réalisé à l'aide de templates et de mots clés :
 * --- tmpl-lign : résultat pour un fichier log (un article)
 * ##count##  : nombre total de visites uniques pour un article
 * ##id##, ##alias##, ##title##, ##created##, ##updated## : données de l'article
 * ##catid##, ##catalias## : id et alias de la catégorie de l'article
 * ##detail## : affiche le détail des visites par année, mois, langue
 * --- tmpl-detail-period : modèle pour une période de ##detail##
 * Les mots clés pour les sous-templates de ##detail##
 * Détail par articles : ##PERIOD##, ##TOTAL##, ##LANG##
 * Motif répété pour ##LANG## : ##LANG##, ##COUNT##
 * Détail par nombre de pges vues par un visteur : ##PERIOD##, ##TOTAL##, ##PAGES-VISITORS##
 * Motif répété pour ##PAGES-VISITORS## : ##NBPAGES##, ##NBVISITORS## :
 *
 * @version  UP-3.0
 * @author   Lomart
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit    <a href="" target"_blank">script xxx de xxx</a>
 * @tags    Expert
 *
 */

/*
 * =============================== TODO ===============================
 * - ne pas effacer et recreer stats jour dans stats_compacte
 */
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

class site_stat extends upAction
{
    public function init()
    {
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        $this->set_demopage();

        $options_def = array(
             /*[st-main]Options pour enregistrement du suivi */
            __class__ => '', // vide : enregistre l'accès, sinon masque= *:tous, 1:courant, ID article ou masque fichiers (id-alias)
            'catid-include' => '', // liste des id catégories à inclure, séparateur virgule
            'catid-exclude' => '', // liste des id catégories à exclure, séparateur virgule
            'usergroup-list' => '', // liste des groupes d'utilisateurs à exclure, séparateur virgule
            'ip-list' => '127.0.0.8, localhost', // liste des IP à ignorer. les botnets sont ignorés, séparateur virgule
            'bots-list' => 'bot,spider,crawler,libwww,search,archive,slurp,teoma,facebook,twitter', // liste de bots exclus

            /* [ST-RESULT] Options pour affichage résultats */
            'view-catid-include' => '', // liste des catégories prise en compte
            'detail-max-month' => 0, // Nombre de mois affichés. 0: tous
            'tmpl-lign' => '##ALIAS## ##CATALIAS## ##DETAIL##', // modele d'affichage pour un article ou total
            'tmpl-detail-period' => '[b class="t-blue bg-grisClair ph1"]##PERIOD##[/b] ([b]##TOTAL##[/b] - ##LANG##) ', // template pour une période
            'tmpl-detail-period-lang' => '[i]##LANG##[/i]:[b]##COUNT##[/b] ', // Sous-template pour un langage
            'tmpl-total-detail-period' => '[b class="t-blanc bg-brun ph1"]##PERIOD##[/b] ([b]##TOTAL##[/b] - ##PAGES-VISITORS##)', // template pour le nombre de pages vues par les visiteurs
            'tmpl-total-detail-PV' => '[i]##NBPAGES##p[/i]:##COUNT##', // Sous-template groupe de nombres de pages par visiteurs
            'date-format' => 'lang[en=%B %se, %Y; fr=%e %B %Y]', // format pour la date
            'no-content-html' => 'lang[en=No statistical data; fr=Aucune donnée statistique]', // message affiché si aucun résultat pour la sélection

            /* [st-cumul] Paramètres consolidation pour prefs.ini */
            'keep-days' => 3, // nombre de jours non compactés en mois. Aujourd'hui non inclus. Ces jours ne sont pas comptabilisé dans leur mois
            'keep-months' => 12, // nombre minimum de mois non compactés en années. (0=année courante, 12=année courante et précédente)
            'delay-unique' => 300, // delai (en secondes) entre 2 visites d'une page par une IP pour la considerer comme unique
            'bots-nbpages' => 200, // nb pages/jour pour suspision de robots

            /* [ST-CSS-TOTAL] Classe(s) et style(s) pour la ligne total */
            'total-style' => 'bg-grisClair', // style pour la dernière ligne
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
            'dir-logs' => 'up/site-stat' // dossier pour logs
        );

        // fusion et controle des options
        $this->options = $this->ctrl_options($options_def);

        $this->dirLogs = JPATH_ROOT . '/' . trim($this->options['dir-logs'], '/') . '/';
        if (! is_dir($this->dirLogs)) {
            mkdir($this->dirLogs, 0777, true);
        }

        // =====================================================
        // TEMPORAIRE : conversion fichiers log de site-visit vers site-stats
        if ($this->options[__class__] == 'convert') {
            $this->consolidation();
            return 'Convert : consolidation OK';
        }
        // =====================================================

        if ($this->options[__class__] == '') {
            // =======> on incremente le fichier log
            $this->incremente();
            return ''; // aucun retour
        } else {
            // =======> on affiche
            // date minimum pour compacter les données
            $this->compact_min_days = date('Y-m-d', strtotime('-' . $this->options['keep-days'] . ' days'));
            $this->compact_min_months = date('Y-01', strtotime('-' . $this->options['keep-months'] . ' months'));
            $this->today = date('Y-m-d');
            // date minimum pour afficher
            $this->report_datemin = '2000-01'; // toutes les stats
            if ($this->options['detail-max-month']) {
                $this->report_datemin = date('Y-m', strtotime('-' . ($this->options['detail-max-month'] - 1) . ' month'));
            }
            // les templates
            $this->tmpl_lign = $this->get_bbcode($this->options['tmpl-lign']);
            $this->tmpl_detail_period = $this->get_bbcode($this->options['tmpl-detail-period']);
            $this->tmpl_detail_period_lang = $this->get_bbcode($this->options['tmpl-detail-period-lang']);
            $this->tmpl_total_detail_period = $this->get_bbcode($this->options['tmpl-total-detail-period']);
            $this->tmpl_total_detail_PV = $this->get_bbcode($this->options['tmpl-total-detail-PV']);
            // les cumuls
            $this->pv_days = array();
            $this->total_pages = 0;
            // attributs d'une ligne
            $this->attr_item = array();
            $this->get_attr_style($this->attr_item, $this->options['item-class'], $this->options['item-style']);

            /* === liste des fichiers log pour les pages demandées */
            $filelist = $this->log_filelist();
            foreach ($filelist as $file) {
                $this->logs_today = ''; // logs du jour au format txt
                // compteur pour la ligne d'une page
                $data = $this->stats_get_data($file); // get data & filtre categories
                if ($data === false) {
                    continue;
                }
                // lecture log
                $logs = trim(file_get_contents($file));
                $logs = str_ireplace('##', "#xx#", $logs); // provisoire c'est corrigé dans incremente
                $logs = explode("\n", $logs);
                if (empty($logs)) {
                    continue;
                }

                $stats = array();
                $this->stats_par_jours($logs, $stats); // etape 1 : compacter par jour
                $this->stats_compacte($stats); // etape 2 : compacter par année et mois
                ksort($stats);
                if ($this->options[__class__] == '*' && empty($this->options['view-catid-include']) && empty($this->options['detail-max-month'])) {
                    $this->log_save($file, $stats);
                }

                // preparer la sortie
                $html[] = $this->stats_report($stats, $data);
            }
            // sortie si aucune ligne
            if (empty($html)) {
                return $this->options['no-content-html'];
            }

            // tri sur la 1ere colonne
            sort($html);

            // === La ligne total en fin avec nbpages-visitors
            if (count($filelist) > 1) {
                $this->get_attr_style($attr_total, $this->options['total-style']);
                $out = $this->stats_report_total();
                $html[] = $this->set_attr_tag($this->options['item-tag'], $attr_total, $out);
            }
        }

        // === CSS-HEAD
        $this->load_css_head($this->options['css-head']);
        // attributs du bloc principal
        $attr_main = array();
        $attr_main['id'] = $this->options['id'];
        $this->get_attr_style($attr_main, $this->options['class'], $this->options['style']);

        // code en retour
        $out = implode(PHP_EOL, $html);
        return $this->set_attr_tag($this->options['main-tag'], $attr_main, $out);
    }

    // run

    /*
     * ------------------------------------------------------------------------
     * function incremente()
     * met à jour le fichier log lors de l'affichage par Joomla d'un articles
     * id-alias.log : AAAA-MM-JJ-HH:MM#LNG#IP (LNG=xx si vide)
     * --------------------------------------------------------------------------
     */
    public function incremente()
    {
        if (isset($this->article->alias)) {
            // le shortcode UP est dans un article
            $id = $this->article->id;
            $row['alias'] = $this->article->alias;
            $row['catid'] = $this->article->catid;
        } elseif (Factory::getApplication()->getInput()->get('view', 0) == 'article') {
            // le shortcode UP est dans un module et le contenu est un article
            $id = (int) Factory::getApplication()->getInput()->get('id', 0);
            $database = Factory::getContainer()->get(DatabaseInterface::class);
            $query = " SELECT a.alias, a.catid";
            $query .= " FROM #__content AS a";
            $query .= " WHERE a.id=" . $id;
            $database->setQuery($query);
            $row = $database->loadAssoc();
        }

        // ce n'est pas un article
        if (empty($row)) {
            return;
        }

        // --- Exclure des catégories
        if (! empty($this->options['catid-exclude'])) {
            if (in_array($row['catid'], explode(',', $this->options['catid-exclude']))) {
                return;
            }
        }

        // --- uniquement certaines catégories
        if (! empty($this->options['catid-include'])) {
            if (! in_array($row['catid'], explode(',', $this->options['catid-include']))) {
                return;
            }
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

        // -----------------------------
        // ---- ON AJOUTE AUX FICHIERS
        // -----------------------------

        // ===> log : liste des visites avec date, ip, lang
        $fileLogs = $this->dirLogs . $id . '-' . $row['alias'] . '.log';
        $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        $lang = locale_accept_from_http($lang);
        $lang = ($lang) ? $lang : 'xx';
        $content = date('Y-m-d-H:i') . '#' . $lang . '#' . $ip;
        file_put_contents($fileLogs, $content . "\n", FILE_APPEND | LOCK_EX);
    }

    // fin incremente

    /*
     * log_filelist
     * ------------
     * retourne la liste des fichiers .log
     */
    public function log_filelist()
    {
        // le masque de sélection : vide = article courant
        $mask = $this->options[__class__];

        if ($mask == 0) {
            // récupération id de l'article courant
            if (isset($this->article->alias)) {
                // le shortcode UP est dans un article
                $mask = $this->article->id . '-' . $this->article->alias . '*';
            } elseif (Factory::getApplication()->getInput()->get('view', 0) == 'article') {
                // le shortcode UP est dans un module et le contenu est un article
                $id = (int) Factory::getApplication()->getInput()->get('id', 0);
                $database = Factory::getContainer()->get(DatabaseInterface::class);
                $query = " SELECT alias FROM #__content";
                $query .= " WHERE id=" . $id;
                $database->setQuery($query);
                $mask = $id . '-' . $database->loadResult();
            }
        } else {
            // plusieurs cas
            if ((int) $mask != 0) {
                // 1- uniquement l'ID (12)
                $mask = (int) $mask . '-*';
            } else {
                // il faut un joker au début pour inclure l'id
                $mask = '*' . ltrim($mask, ' *');
            }
        }
        return glob($this->dirLogs . $mask . '.log');
    }

    /*
     * stats_get_data
     * --------------
     * récupération des données pour l'article
     * false si pas de la catégorie définie par l'option view-catid-include
     */
    public function stats_get_data($filename)
    {
        list($id) = explode('-', basename($filename), 2);
        $catid_list = explode(',', $this->options['view-catid-include']);
        $database = Factory::getContainer()->get(DatabaseInterface::class);
        $query = " SELECT a.id, a.alias, a.title, a.catid, c.alias AS catalias, a.created, a.modified";
        $query .= " FROM #__content AS a";
        $query .= " INNER JOIN #__categories AS c";
        $query .= " WHERE a.id=" . $id;
        $query .= " AND c.id=a.catid";
        $database->setQuery($query);
        $row = $database->loadAssoc();
        // Ajout dans le tableau des fichiers
        if (empty($catid_list[0]) || in_array($row['catid'], $catid_list)) {
            return $row;
        } else {
            return false;
        }
    }

    // fin stats_get_data

    /*
     * stats_par_jours
     * ---------------
     * Création du tableau $stats avec le nombre de visites uniques par IP
     * Conversion des lignes ajoutées par la fonction incremente
     * AAAA-MM-JJ-HH:MM#LN#121.0.0.1 devient AAAA-MM-JJ#LN#121.0.0.1 = nb visites uniques
     */
    public function stats_par_jours(&$logs, &$stats)
    {
        $ctrl_datetime = array();
        foreach ($logs as $k => $log) {
            if (empty($log)) {
                continue;
            }
            $key = $log;
            $nb = 0;
            if (strpos($log, '=')) {
                list($key, $nb) = explode('=', $log);
            }
            if (empty($nb)) { // stats non consolidés
                // on garde une copie des logs du jour pour les sauver en sortie
                if ($key >= $this->today) {
                    $this->logs_today .= $key . "\n";
                }
                // date-heure du dernier accès à la page par une IP
                $datetime = strtotime(substr($key, 0, 16));
                $key = substr($key, 0, 10) . substr($key, 16);
                if (isset($stats[$key])) {
                    // si le delai entre 2 visites est respecté
                    if ($datetime > ($ctrl_datetime[$key] + ($this->options['delay-unique']))) {
                        $stats[$key]++;
                    }
                } else {
                    $stats[$key] = 1;
                }
                $ctrl_datetime[$key] = $datetime;
                // Tableau global de toutes les pages vues par une IP
                $this->pv_days[$key] = (isset($this->pv_days[$key])) ? $this->pv_days[$key] + 1 : 1;
            } else {
                $stats[$key] = $nb;
            }
        }
    }

    // fin stats_par_jours

    /*
     * stats_compacte
     * --------------
     * Cumule les stats selon les options keep-days & keep-months
     */
    public function stats_compacte(&$stats)
    {
        foreach ($stats as $key => $nb) {
            list($period, $lng) = explode('#', $key);
            if ($period < $this->compact_min_months) { // cumul mois en année
                $k = substr($period, 0, 4) . '#' . $lng;
            } elseif ($period < $this->compact_min_days) { // cumul en jour en mois
                $k = substr($period, 0, 7) . '#' . $lng;
            } else { // sinon jour consolide
                $k = substr($period, 0, 10) . '#' . $lng;
            }
            unset($stats[$key]);
            if (isset($stats[$k])) {
                $stats[$k] += $nb;
            } else {
                $stats[$k] = $nb;
            }
        }
    }

    // fin stats_compacte

    /*
     * stats_report
     * ------------
     * calcul et prépare l'affichage d'UNE ligne de stat
     */
    public function stats_report(&$stats, &$data)
    {
        // la ligne d'un item
        $out_lign = $this->tmpl_lign;
        $out_lign = str_ireplace('##id##', $data['id'], $out_lign);
        $out_lign = str_ireplace('##alias##', $data['alias'], $out_lign);
        $out_lign = str_ireplace('##title##', $data['title'], $out_lign);
        $out_lign = str_ireplace('##catid##', $data['catid'], $out_lign);
        $out_lign = str_ireplace('##catalias##', $data['catalias'], $out_lign);
        $out_lign = str_ireplace('##created##', $this->up_date_format($data['created'], $this->options['date-format']), $out_lign);
        $out_lign = str_ireplace('##modified##', $this->up_date_format($data['modified'], $this->options['date-format']), $out_lign);

        // --- totaux pour une ligne
        $curr_period = '';
        $cumul_period = 0;
        $cumul_item = 0;
        $out_period = '';
        $out_period_all = '';
        $out_lang_all = '';

        foreach ($stats as $key => $nb) {
            if ($key < $this->report_datemin) {
                continue;
            }
            list($period, $lang) = explode('#', $key);
            if ($period != $curr_period) {
                // finaliser periode precedente
                if (! empty($out_period)) {
                    $out_period = str_ireplace('##TOTAL##', $cumul_period, $out_period);
                    $out_period = str_ireplace('##LANG##', $out_lang_all, $out_period);
                    $out_period_all .= $out_period . ' ';
                    $cumul_item += $cumul_period;
                    $cumul_period = 0;
                }
                // init nouvelle periode
                $out_lang = '';
                $curr_period = $period;
                $out_period = str_ireplace('##PERIOD##', $curr_period, $this->tmpl_detail_period);
                // nouvelle periode
                $out_lang = str_ireplace('##LANG##', $lang, $this->tmpl_detail_period_lang);
                $out_lang = str_ireplace('##COUNT##', $nb, $out_lang);
                $out_lang_all = $out_lang;
                $cumul_period = $nb;
            } else {
                $out_lang = str_ireplace('##LANG##', $lang, $this->tmpl_detail_period_lang);
                $out_lang = str_ireplace('##COUNT##', $nb, $out_lang);
                $out_lang_all .= ' ' . $out_lang;
                $cumul_period += $nb;
            }
        }

        if (! empty($out_period)) {
            $out_period = str_ireplace('##TOTAL##', $cumul_period, $out_period);
            $out_period = str_ireplace('##LANG##', $out_lang_all, $out_period);
            $out_period_all .= $out_period;
            $cumul_item += $cumul_period;
            $cumul_period = 0;
        }

        // count de l'item
        if (empty($cumul_item)) {
            $cumul_item = array_sum($stats);
        }
        $out_lign = str_ireplace('##count##', $cumul_item, $out_lign);
        $out_lign = str_ireplace('##detail##', $out_period_all, $out_lign);

        $this->total_pages += $cumul_item;
        return $this->set_attr_tag($this->options['item-tag'], $this->attr_item, $out_lign);
    }

    // fin stats_report

    /*
     * stats_report_total
     * ------------------
     * Retourne une ligne avec le total des pages vues pour la sélection de fichiers (si pas *)
     */
    public function stats_report_total()
    {
        $out_lign = $this->tmpl_lign;
        $out_lign = str_ireplace('##count##', $this->total_pages, $out_lign);
        $out_lign = str_ireplace('##id##', '', $out_lign);
        $out_lign = str_ireplace('##alias##', 'TOTAL', $out_lign);
        $out_lign = str_ireplace('##title##', '', $out_lign);
        $out_lign = str_ireplace('##catid##', '', $out_lign);
        $out_lign = str_ireplace('##catalias##', '', $out_lign);
        $out_lign = str_ireplace('##created##', '', $out_lign);
        $out_lign = str_ireplace('##modified##', '', $out_lign);
        if (stripos($out_lign, '##detail##') != false) {
            if ($this->options[__CLASS__] == '*') {
                $out_lign = str_ireplace('##detail##', $this->detail_pages_visitors(), $out_lign);
            } else {
                $out_lign = str_ireplace('##detail##', $this->trad_keyword('NO_STAT_VISITORS'), $out_lign);
            }
        }
        return $out_lign;
    }

    // stats_report_total

    /*
     * detail_pages_visitors
     * ---------------------------
     * Si affichage de tous les fichiers (*), retourne une ligne avec le total des pages vues
     * et si ##detail##, la ventilation du nombre de visiteurs par nombre de pages vues dans une journée
     */
    public function detail_pages_visitors()
    {
        $pvfile = $this->dirLogs . '_pages-visitors.txt';
        $botCtrlFile = $this->dirLogs . '_robot-control.txt';

        // ==== lecture du fichier
        if (file_exists($pvfile)) {
            $logs = explode("\n", trim(file_get_contents($pvfile)));
            foreach ($logs as $log) {
                list($key, $nb_visitors) = explode('=', $log);
                $pv[$key] = $nb_visitors;
            }
        }
        // === ajout des nouveau logs
        // de AAAA-MM-JJ#LN#IP=nbpages vers AAAA-MM-JJ#000x = +1
        foreach ($this->pv_days as $key => $nbpages) {
            $nb = pow(2, strlen(decbin($nbpages)) - 1);
            $nb = str_pad($nb, 4, '0', STR_PAD_LEFT);
            $k = substr($key, 0, 10) . '#' . $nb;
            $pv[$k] = (isset($pv[$k])) ? $pv[$k]++ : 1;
            // ---- Ctrl des gros visiteurs (robot?)
            if ($nbpages > $this->options['bots-nbpages']) {
                $url = (substr($key, strrpos($key, '#') + 1));
                $date = (substr($key, 0, strpos($key, '#')));
                file_put_contents($botCtrlFile, $url . ' # ' . $date . ';' . $nbpages."\n", FILE_APPEND);
            }
        }
        // === on compacte
        foreach ($pv as $key => $nbvisitors) {
            list($period, $nb) = explode('#', $key);
            if ($period < $this->compact_min_months) { // cumul mois en année
                $k = substr($period, 0, 4) . '#' . $nb;
            } elseif ($period < $this->compact_min_days) { // cumul en jour en mois
                $k = substr($period, 0, 7) . '#' . $nb;
            } else { // jour consolide
                $k = substr($period, 0, 10) . '#' . $nb;
            }
            unset($pv[$key]);
            if (isset($pv[$k])) {
                $pv[$k] += $nbvisitors;
            } else {
                $pv[$k] = $nbvisitors;
            }
        }
        // === on sauve
        ksort($pv);
        $logs = '';
        foreach ($pv as $k => $v) {
            $logs .= $k . '=' . $v . "\n";
        }
        file_put_contents($pvfile, $logs);

        // === on prépare la chaine pour retour
        $out_all = '';
        $out_period = '';
        $out_period_all = '';
        $curr_period = '';
        $cumul_period = 0;
        foreach ($pv as $key => $nbvisitors) {
            list($period, $nbpages) = explode('#', $key);
            if ($period < $this->report_datemin) {
                continue;
            }
            // périodes antérieures
            if ($period != $curr_period) {
                if (! empty($out_period)) {
                    $out_period = str_ireplace('##TOTAL##', $cumul_period, $out_period);
                    $out_period = str_ireplace('##PAGES-VISITORS##', $out_all, $out_period);
                    $out_period_all .= $out_period . ' ';
                    $cumul_period = 0;
                }
                // init nouvelle periode
                $curr_period = $period;
                $out_period = str_ireplace('##PERIOD##', $curr_period, $this->tmpl_total_detail_period);
                // nouvelle periode
                $out_one = str_ireplace('##NBPAGES##', intval($nbpages), $this->tmpl_total_detail_PV);
                $out_one = str_ireplace('##COUNT##', $nbvisitors, $out_one);
                $out_all = $out_one;
                $cumul_period = $nbvisitors;
            } else {
                $out_one = str_ireplace('##NBPAGES##', intval($nbpages), $this->tmpl_total_detail_PV);
                $out_one = str_ireplace('##COUNT##', $nbvisitors, $out_one);
                $out_all .= ' ' . $out_one;
                $cumul_period += $nbvisitors;
            }
        }
        if (! empty($out_period)) {
            $out_period = str_ireplace('##TOTAL##', $cumul_period, $out_period);
            $out_period = str_ireplace('##PAGES-VISITORS##', $out_all, $out_period);
            $out_period_all .= $out_period;
        }

        return $out_period_all;
    }

    // stats_detail_pages_visitors

    /*
     * log_save
     * --------
     * Sauve la version actualisée/compactée du fichier log
     */
    public function log_save($file, &$stats)
    {
        unlink($file);
        $logs = '';
        foreach ($stats as $key => $nb) {
            if ($key < $this->today) {
                $logs .= $key . '=' . $nb . "\n";
            }
        }
        $logs .= $this->logs_today;
        file_put_contents($file, $logs, FILE_APPEND | LOCK_EX);
    }

    // fin log_save

    /*
     * consolidation
     * -------------
     * Modifie les anciens fichiers de site-visit vers site-stats
     * - id au début nom fichier
     * - ajout tiret entre date et heure
     */
    public function consolidation()
    {
        foreach (glob($this->dirLogs . '*.log') as $file) {
            if (preg_match('#([0-9]*)(.*).log#', basename($file), $match)) {
                if (empty($match[1])) {
                    $alias = $match[2];
                    $id = $this->get_db_value('id', 'content', 'alias=' . $alias);
                    // $database = Factory::getContainer()->get(DatabaseInterface::class);
                    // $query = " SELECT id";
                    // $query .= " FROM #__content";
                    // $query .= " WHERE alias='" . $alias . "'";
                    // $database->setQuery($query);
                    // $id = $database->loadResult();
                    if (empty($id)) {
                        $this->msg_error('consolidation: alias fichier non trouvé. renommé en .error');
                        $newfile = $this->dirLogs . $alias . '.error';
                        rename($file, $newfile);
                    } else {
                        $newfile = $this->dirLogs . $id . '-' . $alias . '.log';
                        rename($file, $newfile);

                        $str = file_get_contents($newfile);
                        $str = str_ireplace(' ', '-', trim($str));
                        $str = str_ireplace('##', '#xx#', trim($str));
                        $str = file_put_contents($newfile, $str);
                    }
                }
            }
        }
    } // fin consolidation
}

// class
