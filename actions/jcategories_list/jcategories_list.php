<?php

/**
 * liste des catégories descendantes
 * la catégorie parente n'est pas affichée
 *
 * syntaxe : {up jcategories-list=id}
 *
 * MOTS-CLES ITEM MENU:
 * ##id## ##access## ##title-link## ##title## ##note## ##extension## ##language##
 *
 * @author   LOMART
 * @version  UP-1.8
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Joomla
 */
/*
 * https://docs.joomla.org/Categories_and_CategoryNodes_API_Guide
 */
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Categories\Categories;
use Joomla\Database\DatabaseInterface;
use Lomart\Plugin\Content\Up\Helper\UpHelper;

class jcategories_list extends Lomart\Plugin\Content\Up\Extension\Up
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
            __class__ => '', // id catégorie ou vide pour toutes
            'component' => 'Content', // nom de l'extension pour laquelle récupérer les catégories
            'level' => '', // nombre de niveaux. O = tous
            /* [st-model] Modèles de présentation */
            'template' => '##title-link##[small] (id:##id##) ##access## ##extension## ##language##[/small] ##note##', // modèle de mise en page. keywords + bbcode
            'model-note' => '[i class="t-blue"]%s[/i]', // présentation pour ##note##
            /* [st-main] Balise  et style du bloc principal */
            'main-tag' => 'ul', // balise pour bloc parent
            'style' => '', // classes et styles
            'id' => '', // identifiant
            /* [st-item] Balise pour les lignes */
            'item-tag' => 'li', // balise pour blocs enfants (lignes)
            /* [st-css] Style CSS */
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // ======> fusion et controle des options
        $options = UpHelper::ctrl_options($this,$options_def);
        $options['template'] = UpHelper::get_bbcode($this,$options['template'], false);
        $options['model-note'] = UpHelper::get_bbcode($this,$options['model-note'], false);

        $options['main-tag'] = ($options['main-tag'] == '0') ? '' : $options['main-tag'];
        $options['item-tag'] = ($options['item-tag'] == '0') ? '' : $options['item-tag'];

        $options['level'] = ((int) $options['level'] > 0) ? (int) $options['level'] : 99;

        // === RECUP NIVEAU ACCES
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery();
        $query->select('*');
        $query->from($db->quoteName('#__viewlevels'));
        $db->setQuery($query);
        $results = $db->loadObjectList();
        foreach ($results as $res)
            $this->nivacces[$res->id] = $res->title;

        // css-head
        UpHelper::load_css_head($this,$options['css-head']);

        // Variables globales pour l'appel récursif
        $this->out = array();
        $this->level = 0;
        $this->categories = Factory::getApplication()->bootComponent($options['component'])->getCategory();

        // === MISE EN FORME
        $attr_main['id'] = $options['id'];
        UpHelper::get_attr_style($this,$attr_main, $options['style']);

        // contruction de l'arbre des catégorie par aooel récursif
        $this->get_children($options[__class__], $options);

        return UpHelper::set_attr_tag($this,'div', $attr_main, implode(PHP_EOL, $this->out));
    }

    // run
    //
    function get_children($id, $options)
    {
        if ($this->level >= $options['level'])
            return;
        $this->level ++;
        $cat = $this->categories->get($id);
        $children = $cat->getChildren();
        if (count($children) > 0) {
            if ($options['main-tag'])
                $this->out[] = '<' . $options['main-tag'] . '>';
            foreach ($children as $child) {
                if ($options['item-tag'])
                    $this->out[] = '<' . $options['item-tag'] . '>';
                $this->out[] = $this->get_lign_cat($child, $options);
                $this->get_children($child->id, $options);
                if ($options['item-tag'])
                    $this->out[] = '</' . $options['item-tag'] . '>';
            }
            if ($options['main-tag'])
                $this->out[] = '</' . $options['main-tag'] . '>';
        }
        $this->level --;
    }

    // Retourne une ligne formatée pour un item menu
    function get_lign_cat($data, $options)
    {
        $url = 'index.php?option=com_content&view=category&layout=blog&id=' . $data->id;
        $out = $options['template'];
//         $out = str_ireplace('##id##', $data->id, $out);
        UpHelper::kw_replace($this,$out, 'id', $data->id);
//         $out = str_ireplace('##title##', $data->title, $out);
        UpHelper::kw_replace($this,$out, 'title', $data->title);
//         $out = str_ireplace('##title-link##', '<a href="' . $url . '">' . $data->title . '</a>', $out);
        UpHelper::kw_replace($this,$out, 'title-link', '<a href="' . $url . '">' . $data->title . '</a>');
        // note
        $str = ($data->note == '') ? '' : sprintf($options['model-note'], $data->note);
//         $out = str_ireplace('##note##', $str, $out);
        UpHelper::kw_replace($this,$out, 'note', $str);
        // niveau accés
        $str = ($data->access > 1) ? $this->nivacces[$data->access] : '';
//         $out = str_ireplace('##access##', $str, $out);
        UpHelper::kw_replace($this,$out, 'access', $str);
        // language
        $str = ($data->language == '*') ? '' : $data->language;
//         $out = str_ireplace('##language##', $str, $out);
        UpHelper::kw_replace($this,$out, 'language', $str);
        // component
//         $out = str_ireplace('##extension##', str_replace('com_', '', $data->extension) . $str, $out);
        UpHelper::kw_replace($this,$out, 'extension', str_replace('com_', '', $data->extension) . $str);

        return $out;
    }
}

// class



