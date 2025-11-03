<?php

/**
 * liste des catégories d'un mot-clé (tag)
 *
 * syntaxe : {up jcategories-by-tags=id-ou-nom-tag}
 *
 * MOTS-CLES :
 * ##title## ##title-link## ##subtitle## ##link##
 * ##intro## ##content## : la description de la catégorie en HTML 
 * ##intro-text## ##intro-text,100## : la description de la catégorie en TEXT
 * ##image## ##image-link## ##image-src## ##image-alt##
 * ##date-crea## ##date-modif## ##new## ##count## ##hits## ##tags-list##
 *
 * @author   LOMART
 * @version  UP-1.7
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags     Joomla
 */

/*
 * v1.8 - valeur alt par défaut = src humanize
 * v2.9 - date pour php8
 * v3.1 ajout option 'content-plugin'
 */
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;

class jcategories_by_tags extends upAction
{

    function init()
    {
        // charger les ressources communes à toutes les instances de l'action
        return true;
    }

    function run()
    {
        // lien vers la page de demo (vide=page sur le site de UP)
        $this->set_demopage();

        $options_def = array(
            __class__ => '', // ID ou nom du tag
            'maxi' => '', // Nombre maxi d'article dans la liste
            'no-published' => '0', // 1 pour obtenir les catégories non publiées
            'content-plugin' => 0, // prise en compte des plugins de contenu pour ##into et ##content##
            'sort-by' => 'title', // tri: title, ordering, created, modified, id, hits
            'sort-order' => 'asc', // ordre de tri : asc, desc
            'no-content-html' => '[p]aucune catégorie pour ce mot-clé[/p]', // retour si aucune catégorie trouvée
            /* [st-tmpl] Modèle pour affichage résultat */
            'template' => '', // modèle de mise en page. Si vide le modèle est défini par le contenu
            /* [st-main] Style du bloc principal */
            'main-tag' => 'div', // balise pour le bloc englobant tous les articles. 0 pour aucun
            'main-style' => '', // classes et styles inline pour bloc principal
            'main-class' => '', // classe(s) pour bloc principal (obsoléte)
            /* [st-item] Style d'une ligne résultat */
            'item-tag' => 'div', // balise pour le bloc. 0 pour aucun
            'item-style' => '', // classes et styles inline
            'item-class' => '', // classe(s)
            /* [st-img] Paramètres pour images */
            'image-src' => '//lorempixel.com/150/150', // image par défaut
            'image-alt' => 'news', // image, texte alternatif par défaut
            /* [st-format] Formats pour les mots-clés */
            'date-format' => '%e %B %Y', // format pour les dates
            'date-locale' => '', // localisation pour les dates. Par défaut, celle du navigateur client.
            'new-days' => '30', // nombre de jours depuis la création de l'article
            'new-html' => '[span class="badge-red"]nouveau[/span]', // code HTML pour badge NEW
            'tags-list-prefix' => '', // texte avant les autres eventuels tags
            'tags-list-style' => '', // classe ou style pour les autres mots-clés
            /* [st-css] Style CSS */
            'id' => '', // identifiant
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // ======> fusion et controle des options
        $options = $this->ctrl_options($options_def);
        $options['new-html'] = $this->get_bbcode($options['new-html'], false);
        $options['no-content-html'] = $this->get_bbcode($options['no-content-html'], false);
        $options['template'] = $this->get_bbcode($options['template'], false);

        // ======> verif template (modèle de mise en page)
        // en priorité : le sontenu entre shortcode
        // en second : le model dans prefs.ini
        if ($this->content) {
            $tmpl = $this->content;
        } else {
            $tmpl = $options['template'];
        }
        if (! $tmpl) {
            $this->msg_error($this->trad_keyword('NO_CONTENT'));
            return false;
        }

        // ====
        if ((int) $options[__class__]) {
            $id = (int) $options[__class__];
        } else {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->createQuery();
            $query->select('t.id')
                ->from('#__tags as t')
                ->where('t.title like "' . $options[__class__] . '"');
            $db->setQuery($query);
            $id = (int) $db->loadResult();
            if ($id === 0) {
                $this->msg_error($this->trad_keyword('NO_CATID',$options[__class__] ));
                return false;
            }
        }

        // =====> RECUP DES DONNEES
        // ---> contrôle clé de tri
        $list_sortkey = array(
            'title' => 'title',
            'ordering' => 'lft',
            'created' => 'created_time',
            'modified' => 'modified_time',
            'id' => 'id',
            'hits' => 'hits'
        );
        if (isset($list_sortkey[$options['sort-by']])) {
            $sort_by = $list_sortkey[$options['sort-by']];
        } else {
//             $this->msg_error(Text::_('Error <b>sort_by=' . $options['sort-by'] . '</b> not found. Correct is : ' . implode(', ', array_keys($list_sortkey))));
            $this->msg_error($this->trad_keyword('ERR_SORTBY', $options['sort-by'], implode(', ', array_keys($list_sortkey))));
            return false;
        }
        $sort_by = (isset($list_sortkey[$options['sort-by']])) ? $list_sortkey[$options['sort-by']] : 'title';

        // ---> creation requete

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery();
        $query->select('c.title, c.id, c.alias, c.description, c.path, c.params, c.created_time, c.modified_time, c.hits')
            ->from('#__contentitem_tag_map as m ')
            ->innerJoin('#__categories as c on c.id = m.content_item_id')
            ->innerJoin('#__tags as t on t.id = m.tag_id')
            ->order($db->quoteName('c.' . $sort_by) . ' ' . $options['sort-order']);

        if ($options['maxi'])
            $query->setLimit($options['maxi']);

        $query->where('t.id = ' . $id . ' AND m.type_alias like "%category%"');
        if ($options['no-published'] != '1') {
            $query->where('c.published=1');
        }

        $db->setQuery($query);

        $items = $db->loadObjectList();

        if (count($items) == 0)
            return $options['no-content-html'];

        // ======> Style général et par catégorie
        $main_attr['id'] = $options['id'];
        $this->get_attr_style($main_attr, $options['main-class'], $options['main-style']);
        $this->get_attr_style($item_attr, $options['item-class'], $options['item-style']);
        $this->get_attr_style($tags_list_attr, $options['tags-list-style']);

        // css-head
        $this->load_css_head($options['css-head']);

        // ======> SORTIE HTML

        if ($options['main-tag'] != '0')
            $html[] = $this->set_attr_tag($options['main-tag'], $main_attr);

        foreach ($items as $item) {
            // --- Bloc catégorie
            if ($options['item-tag'] != '0')
                $html[] = $this->set_attr_tag($options['item-tag'], $item_attr);
            $sItem = $tmpl; // reinit pour nouvel article
                            // --- lien vers le blog de la catégorie
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->createQuery();
            $query->select(array(
                'COUNT(*)'
            ))
                ->from($db->quoteName('#__content'))
                ->where($db->quoteName('catid') . "=" . $item->id)
                ->where($db->quoteName('state') . '=1');
            $db->setQuery($query);
            $count = $db->loadResult();
            $url = ($count == 0) ? '#' : 'index.php?option=com_content&view=category&layout=blog&id=' . $item->id;

            // --- le titre et sous titre
            $title = $item->title;
            $subtitle = '';
            if (stripos($sItem, '##subtitle##') !== false) {
                $title = strstr($item->title . '~', '~', true);
                $subtitle = trim(substr(strstr($item->title, '~'), 1));
            }

            // ==== les remplacements
            // {id} : ID de la categorie
            $this->kw_replace($sItem, 'id', $item->id);
            // {link} : lien vers le blog categorie - a mettre dans balise a
            $this->kw_replace($sItem, 'link', $url);
            // {title-link} : titre categorie avec lien
            if ($count > 0) {
                $this->kw_replace($sItem, 'title-link', '<a href="' . $url . '">' . $title . '</a>');
            } else {
                $this->kw_replace($sItem, 'title-link', $title);
            }
            // {title} : titre categorie sans lien
            $this->kw_replace($sItem, 'title', $title);
            // {subtitle} : sous-titre categorie (partie après tilde du titre)
            $this->kw_replace($sItem, 'subtitle', $subtitle);
            // {date-crea} : date de création v2.9
            $this->kw_replace($sItem, 'date-crea', $this->up_date_format($item->created_time, $options['date-format'], $options['date-locale']));
            // {date-modif} : date de création v2.9
            $this->kw_replace($sItem, 'date-modif', $this->up_date_format($item->modified_time, $options['date-format'], $options['date-locale']));
            // {description} : texte en HTML
            // prise en charge plugins contenu v31
            if ($options['content-plugin']) {
                if (stripos($sItem, '##intro') !== false || stripos($sItem, '##content##') !== false)
                    $item->description = $this->import_content($item->description); // v31
            }
            
            $this->kw_replace($sItem, 'intro', $item->description);
            $this->kw_replace($sItem, 'content', $item->description);
            // {tags-list} : liste des tags
            if (stripos($sItem, '##tags-list##') !== false) {
                // ##### TODO #####
                $db = Factory::getContainer()->get(DatabaseInterface::class);
                $query = $db->createQuery();
                $query->select('t.title')
                    ->from('#__contentitem_tag_map as m ')
                    ->innerJoin('#__categories as c on c.id = m.content_item_id')
                    ->innerJoin('#__tags as t on t.id = m.tag_id')
                    ->where('c.id = ' . $item->id . ' AND m.type_alias like "%category%"')
                    ->where('t.id <>' . $id);
                $db->setQuery($query);
                $listTags = $db->loadObjectList();
                $tmpTags = (empty($listTags)) ? '' : $options['tags-list-prefix'];
                foreach ($listTags as $tag) {
                    $tmpTags .= $this->set_attr_tag('span', $tags_list_attr, $tag->title);
                }
                $this->kw_replace($sItem, 'tags-list', '<span>' . $tmpTags . '</span>');
            }
            // {new} : badge
            if (stripos($sItem, '##new##') !== false) {
                $max = date('Y-m-d H:i:s', mktime(date("H"), date("i"), 0, date("m"), date("d") - intval($options['new-days']), date("Y")));
                $new = ($item->created_time > $max) ? $options['new-html'] : '';
                $this->kw_replace($sItem, 'new', $new);
            }
            // {count}
            $this->kw_replace($sItem, 'count', $count);
            // {hits}
            $this->kw_replace($sItem, 'hits', $item->hits);
            // {image-xxx} : l'image d'intro, sinon celle dans l'introtext
            // {image} : la balise img complete
            // {image-src} et {image-alt} : uniquement src et alt d'une balise img existante
            if (stripos($sItem, '##image') !== false) {
                $params = json_decode($item->params);
                $img_src = $options['image-src'];
                $img_alt = $options['image-alt'];
                if ($params->image) {
                    $img_src = $params->image;
                    $img_alt = $params->image_alt;
                } else {
                    $imgTag = $this->preg_string('#(\<img .*\>)#Ui', $item->description);
                    if ($imgTag) {
                        $imgAttr = $this->get_attr_tag($imgTag, 'alt');
                        $img_src = $imgAttr['src'];
                        $img_alt = $imgAttr['alt'];
                    }
                }
                // alt par défaut (v1.8)
                if ($img_alt == '')
                    $img_alt = $this->link_humanize($img_src);
                $imgtag = '<img src="' . $img_src . '" alt="' . $img_alt . '">';
                if ($count > 0) {
                    $this->kw_replace($sItem, 'image-link', '<a href="' . $url . '">' . $imgtag . '</a>');
                } else {
                    $this->kw_replace($sItem, 'image-link', $imgtag);
                }
                $this->kw_replace($sItem, 'image', $imgtag);
                $this->kw_replace($sItem, 'image-src', $img_src);
                $this->kw_replace($sItem, 'image-alt', $img_alt);
            }

            // --- keyword avec param
           
            // ##intro-text,100## : les 100 premiers caracteres de l'introduction en texte brut
            preg_match('#\#\#(intro-text\s*,?\s*([0-9]*)\s*)\#\##Ui', $tmpl, $tag);
            if (isset($tag[1])) {
                $intro = trim(strip_tags($item->description));
                if (isset($tag[1]) && $tag[1]) {
                    $len = (int) $tag[2];
                    if (strlen($intro) > $len)
                        $intro = mb_substr($intro, 0, $len) . '...';
                }
                $this->kw_replace($sItem, $tag[1],$intro);
            }
            
            // --- fin article
            $html[] = $sItem;
            if ($options['item-tag'] != '0')
                $html[] = '</' . $options['item-tag'] . '><!--item-->';
        }
        if ($options['main-tag'] != '0')
            $html[] = '</' . $options['main-tag'] . '><!--main-->';

        return implode(PHP_EOL, $html);
    }

    // run
}

// class

