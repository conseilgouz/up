<?php

/**
 * Création des fichiers HTML pour le plugin editors-xtd
 *
 * syntaxe {up upbtn_makefile}
 *
 * @author   LOMART
 * @version  UP-2.1
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    UP
 * */

/*
 * v2.2 - export des fichiers vers un sous-dossier de tmp
 * v2.5 - renommage up/options.ini en up/upbtn-options.ini
 * - possibilité de surcharger up/upbtn-options.ini dans custom
 * v2.6 - ajout option without-custom pour création zip UP
 * v2.8 - on affiche les prefs user : options et prefsets
 * - ajout infos webmaster
 * v2.9 - prise en charge sous-titre dans l'aide intégrée
 * - ajout choix vide pour type [B]
 * v5.2 - prise en charge recherche et 2 boutons insérer
 */
defined('_JEXEC') or die();

class upbtn_makefile extends upAction
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
        /* [st-sel] Sélection des actions listées */
            __class__ => '', // Vide pour toutes les actions, sinon liste des actions à inclure ou exclure (list-exclude=1).
            'list-exclude' => '0', // 0:uniquement les actions indiquées, 1: toutes sauf les actions indiquées
            'without-custom' => '0', // 1 sans les infos dans prefs.ini (v2.6)
            /* [st-div] mode d'affichage */
            'top10' => '', // liste des actions à dupliquer dans un groupe au début de la liste
            'by-tags' => '1', // si 0, les actions sont dans l'ordre alpha sans notion de groupes (sauf top10)
            /* [st-export] Exportation des fichiers */
            'export-folder' => '' // sous-dossier de TMP pour sauver l'arborescence. ex : up-pref-foo
        );

        // ==== fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // controle options
        if (isset($this->options_user['export-folder'])) {
            $tmp = rtrim($this->options_user['export-folder'], '/') . '/';
            $tmp = preg_replace('#^/?tmp/?#i', '', $tmp);
            $tmp = 'tmp/UP/' . trim($tmp, '/') . '/';
            $this->options_user['export-folder'] = $tmp;
            $log_export = '';
        }
        $this->withoutCustom = (! empty($options['without-custom']));

        // === récupération liste actions dans la langue
        // liste des sous-dossiers du dossier actions (sauf _exemple)
        $actionsList = $this->up_actions_list();
        if ($options[__class__] == '') {
            // toutes les actions
            $actionsListSelect = $actionsList;
        } else {
            // uniquement (0) ou sauf (1) celles demandées
            // charger le dictionnaire
            $dico = file_get_contents($this->upPath . 'dico.json');
            $dico = json_decode($dico, true);

            $tmp = array_map('trim', explode(',', str_replace('-', '_', $options[__class__])));
            foreach ($actionsList as $key) {
                if (array_key_exists($key, $dico)) {
                    $key = $dico[$key];
                }
                if ($options['list-exclude'] xor in_array($key, $tmp)) {
                    $actionsListSelect[] = str_replace('-', '_', $key);
                }
            }
        }
        if (empty($actionsListSelect)) {
            return $this->msg_error('Aucune action demandée / ');
        }
        // ==== Controler la conformite des fichiers des actions
        // ==== Langues pour lesquelles créer les fichiers HTML
        // celles des fichiers dans le dossier UP de l'action
        $lang_list = glob($this->actionPath . 'up/??-??.ini');

        // ==== DEBUT BOUCLE CREATION FICHIERS
        // le principe pour tester le formulaire par upbtn.js :
        // les options sauf la principale (le nom de l'action) ont un name pour
        foreach ($lang_list as $lang) {
            $this->trad = array_change_key_case($this->load_inifile($lang), CASE_UPPER);
            $lang = pathinfo($lang, PATHINFO_FILENAME);
            $this->create_list_actions($actionsListSelect, $lang, $options);
            foreach ($actionsListSelect as $action) {
                $this->create_info_action($action, $lang);
            }
        }

        // FINI
        $msg = $this->msg_info($this->trad_keyword('MSG_RETURN'));
        if (isset($this->options_user['export-folder'])) {
            $msg = $this->msg_info($this->trad_keyword('EXPORT_FOLDER_OK', $this->options_user['export-folder']));
        }
        return $msg;
    }

    // run

    /*
     * création du fichier liste des actions
     * -------------------------------------
     * c'est le fichier principal de la popup
     * il appelle en ajax les infos de l'action sélectionnée
     *
     */
    public function create_list_actions($actionsList, $lang, $options)
    {
        $sort_list = array();
        $unsort_list = array();
        $top10_list = array();
        // --- le top10 des actions
        if ($options['top10']) {
            $top10_label = 'Top 10';
            $top10 = array_map('trim', explode(',', $options['top10']));
            foreach ($top10 as $actionName) {
                if (in_array($actionName, $actionsList)) {
                    $actinfos = $this->up_action_infos($actionName, $lang);
                    if (is_string($actinfos)) {
                        $this->msg_error($this->trad_keyword('UNKNOW_ACTION_TOP10', $actionName));
                    } else {
                        $top10_list[$top10_label][$actionName] = $actinfos['_shortdesc'];
                    }
                }
            }
        }
        // --- les actions
        if (! empty($options['by-tags'])) {
            $tags_prefs = $this->load_inifile($this->actionPath . 'custom/prefs.ini', true, false);
            // --- préparation liste actions classées par famille
            foreach ($actionsList as $actionName) {
                $notag_label = $this->trad('UP_NOTAG_LABEL');
                $actinfos = $this->up_action_infos($actionName, $lang);
                $str_tags = '';
                if (isset($actinfos['tags'])) {
                    $str_tags = $actinfos['tags'];
                }
                if (isset($tags_prefs['tags'][$actionName])) {
                    $str_tags = $tags_prefs['tags'][$actionName];
                }
                if ($str_tags) {
                    $tags = array_map('trim', explode(',', $str_tags));
                    foreach ($tags as $tag) {
                        $tag = (isset($this->trad[strtoupper($tag)])) ? $this->trad[strtoupper($tag)] : $tag;
                        $sort_list[strtolower($tag)][$actionName] = $actinfos['_shortdesc'];
                    }
                } else {
                    $unsort_list[$notag_label][$actionName] = $actinfos['_shortdesc'];
                }
            }
        } else {
            // toutes les actions sans tags
            foreach ($actionsList as $actionName) {
                $notag_label = 'Actions';
                $actinfos = $this->up_action_infos($actionName, $lang);
                $unsort_list[$notag_label][$actionName] = $actinfos['_shortdesc'];
            }
        }
        // Tri du tableau
        ksort($sort_list);
        $sortbytag = array_merge($top10_list, $sort_list, $unsort_list);

        // --- Création fichier
        // HEAD
        $html[] = '<!DOCTYPE html>';
        $html[] = '<html xmlns = "http://www.w3.org/1999/xhtml" xml:lang = "' . $lang . '" lang = "' . $lang . '">';
        $html[] = '<head>';
        $html[] = '<meta http-equiv = "content-type" content = "text/html; charset=utf-8" />';
        $html[] = '<title>UP actions list</title>';
        $html[] = '<meta http-equiv="Content-Security-Policy" content="default-src \'self\'; script-src \'self\'">'; // v5.2
        $html[] = '<link rel = "stylesheet" href = "upbtn.css" type = "text/css" />';
        $html[] = '<script type = "text/javascript" src = "upbtn.js"></script>';
        $html[] = '</head>';
        $html[] = '<body>';
        // v5.4.5 mini up : ajout d'un affichage d'attente pendant le chargement d'une action
        $html[] = '<div class="page-load-status" id="page-load-status">';
        $html[] = '<div class="loader-ellips infinite-scroll-request">';
        $html[] = '<span class="loader-ellips__dot"></span>';
        $html[] = '<span class="loader-ellips__dot"></span>';
        $html[] = '<span class="loader-ellips__dot"></span>';
        $html[] = '<span class="loader-ellips__dot"></span>';
        $html[] = '</div>';
        $html[] = '</div>';
        $html[] = '<div id="upbtns">';
        // filtrage v5.2
        $html[] = '<form id="upbtn-filter">';
        $html[] = '<label for="upbtn-filter">' . $this->trad('FILTER_LABEL') . '</label>';
        $html[] = '<input type="text" id="upbtn-filter-input" tabindex="1" placeholder="' . $this->trad('FILTER_PLACEHOLDER') . '">';
        $html[] = '<button id="upbtn-filter-reset">X</button>';
        $html[] = '</form>';

        // liste des actions
        $html[] = '<form id="upbtn-form">';
        $html[] = '<section class="upbtn-header">';

        $select[] = '<select id="upbtn-actionname">';
        $select[] = '<option value="">' . $this->trad('SELECT_ACTION') . '</option>';
        foreach ($sortbytag as $key => $tags) {
            $select[] = '<optgroup label="' . $key . '">';
            foreach ($tags as $key => $tag) {
                $select[] = '<option value="' . $key . '">' . str_replace('_', '-', $key) . ' - ' . $tag . '</option>';
            }
            $select[] = '</optgroup>';
        }
        $select[] = '</select>';
        $html = array_merge($html, $select);
        $html[] = '</section > <!--upbtn-header-->';
        // la partie remplie en ajax
        $html[] = '<section id="upbtn-options">';
        $html[] = '<div id="ajax-options"></div>';
        $html[] = '</section>';
        // options générales et validation
        $html[] = '<section id="upbtn-footer">';
        $html[] = '<hr>';
        $html[] = '<p>'; // v5.2
        $html[] = '<label for="upbtn-close">' . $this->trad('CLOSED_SHORTCODE') . ' </label> <input id="upbtn-close" type="checkbox">';
        $html[] = '<label for="upbtn-nbparts">' . $this->trad('NB_PARTS') . ' </label> <input id="upbtn-nbparts" type="number" value="1" min="1" max="12" size="3">';
        $html[] = '<label for="upbtn-debug">' . $this->trad('OPTION_DEBUG') . ' </label> <input id="upbtn-debug" type="checkbox">';
        $html[] = '<label for="upbtn-help">' . $this->trad('OPTION_HELP') . ' </label> <input id="upbtn-help" type="checkbox">';
        $html[] = '</p>';
        $html[] = '<p class="tc">';
        $html[] = '<button id="upbtn-submit" type="button">' . $this->trad('VALID') . '</button>';
        $html[] = '<button id="upbtn-submitAll" type="button">Insérer ouvrant+fermant</button>';
        $html[] = '</p>';
        $html[] = '<input type="hidden" id="upbtn-lang" value="' . $lang . '" />';
        $html[] = '</section>';
        $html[] = '</form>';
        $html[] = '</div>'; // 5.4.5
        $html[] = '</body>';
        $html[] = '</html>';

        // --- génére fichier
        $file = $this->upPath . 'upbtn/' . $lang . '.actions-list.html';
        file_put_contents($file, implode(PHP_EOL, $html));

        // EXPORT CUSTOM
        if (isset($this->options_user['export-folder'])) {
            $ok = true;
            $exportpath = $this->options_user['export-folder'] . 'upbtn';
            if (! file_exists($exportpath)) {
                $ok = mkdir($exportpath, 0755, true);
            }
            $ok = $ok && copy($file, $exportpath . '/' . $lang . '.actions-list.html');
            if (! $ok) {
                $this->msg_error($this->trad_keyword('EXPORT_CUSTOM_ERR', $exportpath));
            }
        }
    }

    /*
     * création des fichiers doc et options des actions
     * ------------------------------------------------
     * il s'agit des fichiers chargés en ajax par le fichier principal
     *
     */
    public function create_info_action($actionName, $lang)
    {
        // === récupération des infos et options
        $actinfos = $this->up_action_infos($actionName, $lang);
        // prevenir si l'action n'a pas de tags
        if (strpos($actinfos['_credit'], '@tags') === false) { // v2.8
            $this->msg_error($this->trad_keyword('TAG_NOT_FOUND', $actionName));
        }
        $actoptions = $this->up_action_options($actionName, true, $lang);
        $this->update_default($actionName, $actoptions);
        // --- fichier des types pour options
        $options_type = array();
        // v2.5 chgt nom options.ini et surcharge dans custom
        $optionsTypeFile = $this->upPath . 'actions/' . $actionName . '/custom/upbtn-options.ini';
        if (! file_exists($optionsTypeFile)) {
            $optionsTypeFile = $this->upPath . 'actions/' . $actionName . '/up/upbtn-options.ini';
        }
        if (file_exists($optionsTypeFile)) {
            $options_type = $this->load_inifile($optionsTypeFile);
        }
        if ($options_type === false) {
            return;
        }

        // ajout des options standards
        $options_type['id'] = (isset($options_type['id'])) ? $options_type['id'] : '[A]class:w10';
        // --- ajout des prefsets dans l'option principale
        $pref_user = $this->get_prefsets($actionName);
        if ($pref_user) {
            $prefset = $pref_user;
            // options n'est pas un prefset
            if (isset($prefset['options'])) {
                unset($prefset['options']);
            }
            $prefset_list = implode(',', array_keys($prefset));
            if (! empty($options_type[$actionName])) {
                $tmp = $this->get_argtype($options_type[$actionName], $actionName);
                if (! empty($tmp['list'])) {
                    $prefset_list .= ',' . implode(',', $tmp['list']);
                    $options_type[$actionName] = substr($options_type[$actionName], 0, strpos($options_type[$actionName], ']') + 1);
                }
                $options_type[$actionName] = str_replace(']', ']' . $prefset_list, $options_type[$actionName]);
                $options_type[$actionName] = str_replace($tmp['type'], 'COMBO', $options_type[$actionName]);
            } else {
                $options_type[$actionName] = '[COMBO]' . $prefset_list;
            }
        }
        // === création fichier html inséré en ajax
        unset($html);
        $html[] = '<!DOCTYPE html>';
        $html[] = '<html>';
        $html[] = '<head>';
        $html[] = '<title>' . $actionName . ' - editor button</title>';
        $html[] = '<meta charset = "UTF-8">';
        $html[] = '<meta name = "viewport" content = "width=device-width, initial-scale=1.0">';
        $html[] = '</head>';
        $html[] = '<body>';
        // --- lien vers demo
        if ($actinfos['_demopage'] != '') {
            $html[] = '<p class="upbtn-demo"><a href="' . $actinfos['_demopage'] . '" target="_blank">' . $this->trad('DEMO_SITE') . '</a></p>';
        }
        // --- documentation
        $html[] = '<div class="upbtn-doc">';
        $html[] = '<p>' . $actinfos['_shortdesc'] . '</p>';
        $html[] = '<p>' . $actinfos['_longdesc'] . '</p>';
        // --- documentation webmaster
        if ($this->withoutCustom === false) {
            $html[] = $this->up_help_txt($actionName);
            $html[] = $this->up_prefset_list($actionName);
        }
        $html[] = '</div >';

        // --- options
        foreach ($actoptions as $val) {

            if (substr($val['key'], 0, 6) == '>>ST>>') {
                $html[] = '<h4>' . $val['comment'] . '</h4>';
                continue;
            }
            // les options optionnelles sont identifiées par l'attribut NAME, les autres par ID
            // on remplace les undescores par des tirets pour le nom de l'option
            if ($val['key'] == $actionName) {
                $val['name'] = str_replace('_', '-', $val['key']);
                $idtag = ' id="' . $val['key'] . '"';
            } else {
                $val['name'] = $val['key'];
                $idtag = ' name="' . $val['key'] . '"';
            }

            // valeur par defaut dans titre
            $default_title = ' title="' . $val['val'] . '"';
            $placeholder = ' placeholder="' . $val['val'] . '"';
            $value = ' value="' . $val['val'] . '"';

            // --- le champ formulaire
            // type et argument de l'option
            $arg['type'] = 'A';
            $arg['attr'] = '';
            $arg['list'] = '';
            if (isset($options_type[$val['key']])) {
                $arg = $this->get_argtype($options_type[$val['key']], $actionName);
            }
            // ajout unit dans le label
            $unit = $this->preg_string('#unit="(.*)"#U', $arg['attr']);
            $unit = ($unit) ? ' (' . $unit . ')' : '';

            // trait de séparation si class hr dans options.ini
            $hr = (stripos($arg['attr'], 'hr-top') !== false) ? ' class="hr-top"' : '';

            // --- le label de l'option
            $html[] = '<p' . $hr . '><label for="' . $val['key'] . '" title="' . htmlentities($val['comment']) . '">' . $val['name'] . $unit . '</label>';

            switch ($arg['type']) {
                case 'N':
                    $html[] = '<input type = "number"' . $idtag . $arg['attr'] . $placeholder . '></p>';
                    break;
                case 'B':
                    $html[] = '<select' . $idtag . $arg['attr'] . $default_title . $placeholder . $value . '>';
                    $selected = ($val['val'] == '1') ? ' selected' : '';
                    $html[] = '<option value=""' . $selected . '>' . '' . '</option>';
                    $html[] = '<option value="1"' . $selected . '>' . $this->trad('UP_YES') . '</option>';
                    $selected = ($val['val'] == '0') ? ' selected' : '';
                    $html[] = '<option value="0"' . $selected . '>' . $this->trad('UP_NO') . '</option>';
                    $html[] = '</select></p>';
                    break;
                case 'LIST':
                    $html[] = '<select' . $idtag . $arg['attr'] . $default_title . $placeholder . $value . '>';
                    foreach ($arg['list'] as $e) {
                        $v = $e;
                        if (strpos($e, '::') !== false) {
                            list($v, $e) = explode('::', $e);
                        }
                        $selected = ($e == $val['val']) ? ' selected' : '';
                        $html[] = '<option value="' . $v . '"' . $selected . '>' . $e . '</option>';
                    }
                    $html[] = '</select></p>';
                    break;
                case 'COMBO':
                case 'FILES':
                case 'FILE':
                    $html[] = '<input type = "text"' . $idtag . $arg['attr'] . $default_title . $placeholder . ' list="' . 'combo_' . $val['key'] . '"><span class="combo-indicator">&#x25bc;</span>';
                    $html[] = '<datalist id="' . 'combo_' . $val['key'] . '">';
                    foreach ($arg['list'] as $e) {
                        $v = $e;
                        if (strpos($e, '::') !== false) {
                            list($v, $e) = explode('::', $e);
                        }
                        $html[] = '<option value="' . $v . '">' . $e . '</option>';
                    }
                    $html[] = '</datalist></p>';
                    break;
                case 'COLOR':
                    // attention : le navigateur transforme les couleurs en minuscules
                    $color = $this->color_normalize($val['val']);
                    $default_title = ' title="' . $color . '"';
                    $str = '<input type = "color"' . $idtag . $arg['attr'] . $default_title;
                    $str .= (empty($val['val'])) ? ' value="#feffff"' : ' value="' . $color . '"';
                    $str .= '></p>';
                    $html[] = $str;
                    break;
                default:
                    $html[] = '<input type = "text"' . $idtag . $arg['attr'] . $placeholder . '></p>';
                    break;
            }
        }

        // --- fin options
        $html[] = '</body>';
        $html[] = '</html >';

        // --- génére fichier (VOIR POUR GB)
        $filename = $lang . '.' . $actionName . '.html';
        $filepath = $this->upPath . 'actions/' . $actionName . '/up/' . $filename;
        file_put_contents($filepath, implode(PHP_EOL, $html));

        // EXPORT CUSTOM
        if (isset($this->options_user['export-folder'])) {
            $ok = true;
            $exportpath = $this->options_user['export-folder'] . '/actions/' . $actionName . '/up';
            if (! file_exists($exportpath)) {
                $ok = mkdir($exportpath, 0755, true);
            }
            $ok = $ok && copy($filepath, $exportpath . '/' . $filename);
            if (! $ok) {
                $this->msg_error($this->trad_keyword('EXPORT_CUSTOM_ERR', $exportpath));
            }
        }
    }

    /*
     * Récupération de la version de UP
     */
    public function get_up_version()
    {
        $vers = '?';
        $fic = $this->upPath . 'up.xml';
        if (file_exists($fic)) {
            $xml = simplexml_load_file($fic);
            $vers = $xml->version;
        }
        return $vers;
    }

    /*
     * conversion attributs dans options.ini
     * en chaine d'attributs pour input
     */
    public function attr_format($attr)
    {
        $out = '';
        if (! empty($attr)) {
            // par sécurité si utilisation du égal dans options.ini
            $attr = str_replace('=', ':', $attr);
            $arr = $this->strtoarray($attr, ',', ':', false);
            foreach ($arr as $k => $v) {
                $out .= ' ' . $k . '="' . $v . '"';
            }
        }
        return $out;
    }

    /*
     * retourne un tableau avec le nom des jeux d'options
     */
    public function get_prefsets($action_name)
    {
        $pref_user = array();
        if ($this->withoutCustom === false) {
            $pref_user_file = $this->upPath . 'actions/' . $action_name . '/custom/prefs.ini';
            if (file_exists($pref_user_file)) {
                $pref_user = $this->load_inifile($pref_user_file, true);
                // if (isset($pref_user['options']))
                // unset($pref_user['options']);
                if (isset($pref_user['tags'])) {
                    unset($pref_user['tags']);
                }
                // $out = implode(',', array_keys($pref_user));
            }
        }
        return $pref_user;
    }

    /*
     * Actualise les valeurs par défaut avec celles du prefs.ini
     */
    public function update_default($action_name, &$actoptions)
    {
        $pref_user_file = $this->upPath . 'actions/' . $action_name . '/custom/prefs.ini';
        if (file_exists($pref_user_file)) {
            $pref_user = $this->load_inifile($pref_user_file, true);
            if (isset($pref_user['options'])) {
                foreach ($actoptions as $k => $v) {
                    if (isset($pref_user['options'][$v['key']])) {
                        $actoptions[$k]['val'] = $pref_user['options'][$v['key']];
                    }
                }
            }
        }
    }

    /*
     * recoit une chaine de la forme [TYPE classe(s)]arguments
     * retourne un tableau avec le type (type), les attributs (attr) et le options d'une liste (list)
     */
    public function get_argtype($str, $action_name)
    {
        $out['type'] = '';
        $out['attr'] = '';
        $out['list'] = '';

        // v3.0 pour arg [lang dans list/combo
        if ($str[0] != '[' || strpos($str, ']') === false) {
            $this->msg_error('Syntax error in ' . $action_name . '\up\options.ini (' . $str . ')');
            return $out;
        }
        $matches = explode(']', ltrim($str, '['), 2);
        array_unshift($matches, $str);

        $arg = $matches[2];
        $tmp = explode(' ', $matches[1]);
        $out['type'] = strtoupper($tmp[0]);
        if (isset($tmp[1])) {
            $out['attr'] = 'class="' . substr($matches[1], strlen($out['type']) + 1) . '" ';
        }
        switch ($out['type']) {
            case 'N':
                $out['attr'] .= $this->attr_format($arg);
                break;
            case 'B':
                $out['list'] = array(
                    '',
                    $this->trad('UP_YES'),
                    $this->trad('UP_NO')
                );
                break;
            case 'COMBO':
            case 'LIST':
                $sep = (strpos($arg, '|') !== false) ? '|' : ',';
                $out['list'] = array_map('trim', explode($sep, $sep . $arg));
                break;
            case 'FILE':
            case 'FILES':
                if ($arg[0] != '/') {
                    $arg = $this->upPath . 'actions/' . $action_name . '/' . $arg;
                }
                $tmp = glob($arg);
                $filename = array();
                foreach ($tmp as $file) {
                    $filename[] = pathinfo($file, PATHINFO_FILENAME);
                }
                $out['list'] = $filename;

                break;
            case 'COLOR':
                break;
            default:
                $out['attr'] .= $this->attr_format($arg);
                break;
        }
        return $out;
    }

    public function trad($keyword)
    {
        return (isset($this->trad[$keyword])) ? $this->trad[$keyword] : $keyword;
    }

    // le cjhamp input accepte uniquement les couleurs RVB au format #rrggbb
    public function color_normalize($color)
    {
        $color = strtolower(ltrim($color, ' #'));
        switch (strlen($color)) {
            case 3:
            case 4: // on ne prend pas la transparence
                $color = '#' . $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
                break;
            case 6:
            case 8: // on ne prend pas la transparence
                $color = '#' . substr($color, 0, 6);
                break;
            default:
                $color = '';
        }
        return $color;
    }
}

// class
