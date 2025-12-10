<?php

/**
 * liste des menus
 *
 * syntaxe : {up jmenus-list=menutype}
 *
 * MOTS-CLES MENUTYPE:
 * ##id## ##menutype## ##title## ##description##

 * MOTS-CLES ITEM MENU:
 * ##id## ##title## ##link## ##title-link## ##note## ##access## ##language## ##component##
 * ##level## ##image## / v51
 * ##hidden## / 5.3
 * @author   LOMART
 * @version  UP-1.8
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Joomla
 */
/*
 * v31 : fix route pour component
 * v51 : add ##level## ##image##
 *       fix main-tag, ajout classe level_x aux items de menus 
 * v53 : new parameter nohidden : ignore hidden menus, add ##hidden##
 */
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Menu\MenuFactoryInterface;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Lomart\Plugin\Content\Up\Helper\UpHelper;

class jmenus_list extends Lomart\Plugin\Content\Up\Extension\Up
{

    function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    function run()
    {
        // lien vers la page de demo (vide=page sur le site de UP)
        UpHelper::set_demopage($this);

        $options_def = array(
            __class__ => '', // prefset, nom menutype ou vide pour tous
            'menuid' => '', // ID menu parent pour limiter à cette branche
            /* [st-main] Balise et style du bloc principal */
            'main-tag' => 'ul', // balise pour la liste des fichiers
            'style' => '', // classes et styles
            'id' => '',
            'nohidden' => '', // 5.3 : ignore hidden menus
            /* [st-model] Modèle de présentation */
            'template-menutype' => '[h5]##title## (id:##id##)[/h5] ##description## / ##menutype##', // modèle pour menutype. keywords + bbcode
            'template-menu' => '##title-link##[small] (id:##id##) ##access## - ##component## ##language##[/small] ##note##', // modèle item menu. keywords + bbcode
            'model-note' => '[i class="t-blue"] %s[/i]', // modèle pour ##note## keywords + bbcode
            /* [st-css] Style CSS */
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // ======> fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);
        $options['template-menutype'] = UpHelper::get_bbcode($this,$options['template-menutype'], false);
        $options['template-menu'] = UpHelper::get_bbcode($this,$options['template-menu'], false);
        $options['model-note'] = UpHelper::get_bbcode($this,$options['model-note'], false);
        $isList = ($options['main-tag'] == 'ul');
//         if (!$isList) {
//             $options['template-menu'] = '<'.$options['main-tag'] .' class="level_##level##">'.$options['template-menu'].'</'.$options['main-tag'].'>';
//         }

        // === CSS-HEAD
        UpHelper::load_css_head($this,$options['css-head']);

        // === RECUP NIVEAU ACCES
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery();
        $query->select('*');
        $query->from($db->quoteName('#__viewlevels'));
        $db->setQuery($query);
        $results = $db->loadObjectList();
        foreach ($results as $res) {
            $nivacces[$res->id] = $res->title;
        }
        
        // === liste des menutypes
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery();
        $query->select('*');
        $query->from($db->quoteName('#__menu_types'));
        $db->setQuery($query);
        $results = $db->loadObjectList();

        foreach ($results as $res) {
            $all_menutype[$res->menutype]['menutype'] = $res->menutype;
            $all_menutype[$res->menutype]['id'] = $res->id;
            $all_menutype[$res->menutype]['title'] = $res->title;
            $all_menutype[$res->menutype]['description'] = $res->description;
        }
        // === Quel menu ?
        if ($options[__class__] == '') {
            $sel_menutype = array_keys($all_menutype); // tous
        } else {
            $sel_menutype = array_map('trim', explode(',', $options[__class__]));
        }

        // === MISE EN FORME
        $list = array();
        $menutype = '';
        $level = 0;
        $toplevel = 0;
        $attr_main['id'] = $options['id'];
        UpHelper::get_attr_style($this,$attr_main, $options['style']);

        $menu = Factory::getContainer()->get(MenuFactoryInterface::class)->createMenu('site'); // Joomla 6.0
        $allMenuItems = $menu->getItems($attributes = array(), $values = array());
        // -- extraire une branche
        if ($options['menuid']) {
            $ok = 0; // flag
            $count = count($allMenuItems);
            for ($i = 0; $i < $count; $i++) {
                if ($allMenuItems[$i]->id == $options['menuid']) {
                    $ok = $allMenuItems[$i]->level;
                    $toplevel = $allMenuItems[$i]->level + 1;
                    unset($allMenuItems[$i]);
                } elseif ($allMenuItems[$i]->level <= $ok) {
                    $ok = 0;
                }
                if ($ok == 0) {
                    unset($allMenuItems[$i]);
                }
            }
        }
        // ---
        $out = array(); // v2.8.1
        if (count($allMenuItems) > 0) {
            foreach ($sel_menutype as $menutype) {
                if ($options['template-menutype'] != '0' && $isList) {
                    $out[] = $this->get_lign_menutype($all_menutype[$menutype], $options);
                }
                foreach ($allMenuItems as $menuItem) {
                    if ($menutype == $menuItem->menutype) {
                        $params = $menuItem->getParams();
                        if ($options['nohidden']) { // ignore hidden menu ?
                            if (!is_null($params->get('menu_show')) && ($params->get('menu_show') == 0)) {
                                continue; // ignore
                            }
                        }
                        if ($isList) {
                            if ($menuItem->level < $level) {
                                $out[] = '</li>' . str_repeat('</ul>', $level - $menuItem->level); //v5.1 merci Enfis
                            } elseif ($menuItem->level > $level) {
                                $out[] = '<ul>';
                            } else { // idem
                                $out[] = '</li>';
                            }
                        }

                        $foo = $menuItem->title;
                        $str = $this->get_lign_menu($menuItem, $options, $nivacces, $params);
                        if ($isList) {
                            $out[] = '<li class="level_'.$menuItem->level.'">' . $str;
                        } else {
                            $offset = ($menuItem->level - $toplevel);
                            // $out[] = ($offset) ? str_pad('-', ($offset * 3)) . '-' . $str : $str;
                            $out[] = $str;
                        }
                        $level = (int) $menuItem->level;
                    }
                }
                for ($level; $level > 0; $level--) {
                    if ($isList) {
                        $out[] = ($isList) ? '</li></ul>' : '';
                    }
                }
            } // menutype
        } else {
            $out[] = 'No menu items.';
        }

        return UpHelper::set_attr_tag($this,'div', $attr_main, implode(PHP_EOL, $out));
    }

    // run
    //
    // Retourne une ligne formatée pour le menutype
    function get_lign_menutype($data, $options)
    {
        $out = $options['template-menutype'];
        UpHelper::kw_replace($this,$out, 'id', $data['id']);
        UpHelper::kw_replace($this,$out, 'menutype', $data['menutype']);
        UpHelper::kw_replace($this,$out, 'title', $data['title']);
        UpHelper::kw_replace($this,$out, 'description', $data['description']);
        return $out;
    }

    // Retourne une ligne formatée pour un item menu
    function get_lign_menu($data, $options, $nivacces, $params)
    {
        $out = $options['template-menu'];
        if (strpos($out, '##image') !== false) {
            $image = $this->get_image($params);
            UpHelper::kw_replace($this,$out, 'image', $image);
        }
        UpHelper::kw_replace($this,$out, 'level', $data->level); // v5.1
        UpHelper::kw_replace($this,$out, 'id', $data->id);
        UpHelper::kw_replace($this,$out, 'title', $data->title);
        // note
        $str = ($data->note == '') ? '' : sprintf($options['model-note'], $data->note);
        UpHelper::kw_replace($this,$out, 'note', $str);
        // niveau accés
        UpHelper::kw_replace($this,$out, 'access', $nivacces[$data->access]);
        // language
        $str = ($data->language == '*') ? '' : $data->language;
        UpHelper::kw_replace($this,$out, 'language', $str);
        // component
        $str = (isset($data->query['view'])) ? '/' . $data->query['view'] : '';
        UpHelper::kw_replace($this,$out, 'component', str_replace('com_', '', ($data->component ?? '')) . $str);
        // lien
        if (strpos($out, '##link') !== false || strpos($out, '##title-link') !== false) { // v31
            $itemParams = $data->getParams();
            $data->flink = $data->link;
            switch ($data->type) {
                case 'separator':
                    break;

                case 'heading':
                    // No further action needed.
                    break;

                case 'url':
                    if ((strpos($data->link, 'index.php?') === 0) && (strpos($data->link, 'Itemid=') === false)) {
                        // If this is an internal Joomla link, ensure the Itemid is set.
                        $data->flink = $data->link . '&Itemid=' . $data->id;
                    }
                    break;

                case 'alias':
                    $data->flink = 'index.php?Itemid=' . $itemParams->get('aliasoptions');

                    // Get the language of the target menu item when site is multilingual
                    if (Multilanguage::isEnabled()) {
                        $newItem = Factory::getApplication()->getMenu()->getItem((int) $itemParams->get('aliasoptions'));

                        // Use language code if not set to ALL
                        if ($newItem != null && $newItem->language && $newItem->language !== '*') {
                            $data->flink .= '&lang=' . $newItem->language;
                        }
                    }
                    break;

                default:
                    $data->flink = 'index.php?Itemid=' . $data->id;
                    break;
            }

            if ((strpos($data->flink, 'index.php?') !== false) && strcasecmp(substr($data->flink, 0, 4), 'http')) {
                $data->flink = Route::_($data->flink, true, $itemParams->get('secure'));
            } else {
                $data->flink = Route::_($data->flink);
            }
            UpHelper::kw_replace($this,$out, 'link', $data->flink);
            UpHelper::kw_replace($this,$out, 'title-link', '<a href="' . $data->flink . '">' . $data->title . '</a>');
            $text = "";
            if (!is_null($params->get('menu_show')) && ($params->get('menu_show') == 0)) {
                $text = UpHelper::kw_replace($this,$out, 'hidden', Text::_('MENUS_HIDDEN'));
            }
            UpHelper::kw_replace($this,$out, 'hidden', $text);
        }

        return $out;
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
