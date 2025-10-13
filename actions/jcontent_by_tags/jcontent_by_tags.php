<?php

/**
 * liste des catégories d'un mot-clé (tag)
 *
 * syntaxe : {up jcontent-by-tags=id-ou-nom-tag}
 * syntaxe : {up jcontent-by-tags=id-ou-nom-tag}##title##{/up jcontent-by-tags}
 *
 * <b>Les mots-clés :</b>
 * ##title## ##title-link## ##subtitle## ##link##
 * ##intro## ##intro-text## ##intro-text,100## ##content##
 * ##image## ##image-link## ##image-src## ##image-alt##
 * ##date-crea## ##date-modif##  ##date-publish##
 * ##cat## ##new## ##hits## ##tags-list##
 * ##CF_id_or_name## : valeur brute du custom field
 *
 * @author   LOMART
 * @version  UP-1.7
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Joomla
 */
/*
 * v2.3 ajout motcles pour customFields : ##CF_id_or_name
 * v2.9 - compatibilité PHP8 pour ##date-xxx##
 * - le template peut être mis comme contenu
 * v3.0 bbcode possible sur template
 * v3.1 
 *   - ajout option 'content-plugin'
 *   - prise en charge des mots -clés pour les customs-fields
 */
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Database\DatabaseInterface;

class jcontent_by_tags extends upAction
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
            'maxi' => '', // Nombre maxi d'articles dans la liste
            'content-plugin' => 0, // prise en compte des plugins de contenu pour ##intro et ##content## 
            'no-published' => '0', // 1 pour obtenir les catégories non publiées
            'current' => '0', // 1 pour inclure l'article en cours
            'sort-by' => 'title', // tri: title, created, modified, publish, id, hits, ordering
            'sort-order' => 'asc', // ordre de tri : asc, desc
            /* [st-model] Modèles de présentation */
            'template' => '', // modèle de mise en page. Si vide le modèle est le contenu
            /* [st-main] balise & style pour le bloc principal */
            'main-tag' => 'div', // balise pour le bloc englobant tous les articles. 0 pour aucun
            'id' => '', // identifiant
            'main-style' => '', // classes et styles inline pour bloc principal
            'main-class' => '', // classe(s) pour bloc principal (obsolète)
            /* [st-item] balise & style pour un élément */
            'item-tag' => 'div', // balise pour un bloc article. 0 pour aucun
            'item-style' => '', // classes et styles inline pour bloc principal
            'item-class' => '', // classe(s) pour bloc principal (obsoléte)
            /* [st-img] Paramètre pour l'image */
            'image-src' => '//lorempixel.com/150/150', // image par défaut
            'image-alt' => 'news', // image, texte alternatif par défaut
            /* [st-format] Format pour les mots-clés */
            'date-format' => '%e %B %Y', // format pour les dates
            'date-locale' => '', // localisation pour les dates. Par défaut, celle du navigateur client.
            'new-days' => '30', // nombre de jours depuis la création de l'article
            'new-html' => '[span class="badge-red"]nouveau[/span]', // code HTML pour badge NEW
            'tags-list-prefix' => '', // texte avant les autres éventuels tags
            'tags-list-style' => '', // classe ou style pour les autres mots-clés
            /* [st-divers] Divers */
            'no-content-html' => 'aucune catégorie pour ce mot-clé', // retour si aucune catégorie trouvée
            'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // ======> fusion et controle des options
        $options = $this->ctrl_options($options_def);
        $options['template'] = $this->get_bbcode($options['template'], false);
        $options['new-html'] = $this->get_bbcode($options['new-html'], false);
        $options['no-content-html'] = $this->get_bbcode($options['no-content-html'], false);

        // ======> verif template (modèle de mise en page)
        // en priorité : le sontenu entre shortcode
        // en second : le model dans prefs.ini
        if ($this->content) {
            $tmpl = $this->get_bbcode($this->content); // v31
        } else {
            $tmpl = $options['template'];
        }
        if (empty($tmpl)) {
            $this->msg_error($this->trad_keyword('NO_TEMPLATE'));
            return false;
        }
        $tmpl = $this->get_bbcode($tmpl, '+hr|pre'); // v31
        
        // ==== ID du mot-clé
        if ((int) $options[__class__]) {
            $id = (int) $options[__class__];
        } else {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true);
            $query->select('t.id')
                ->from('#__tags as t')
                ->where('t.title like "' . $options[__class__] . '"');
            $db->setQuery($query);
            $id = (int) $db->loadResult();
            if ($id === 0) {
                $this->msg_error($this->trad_keyword('NO_TAG_NAME', $options[__class__]));
                return false;
            }
        }

        // =====> RECUP DES DONNEES
        // ---> contrôle clé de tri
        $list_sortkey = array(
            'title' => 'title',
            'ordering' => 'ordering',
            'created' => 'created',
            'modified' => 'modified',
            'publish' => 'publish_up',
            'id' => 'id',
            'hits' => 'hits'
        );
        if (isset($list_sortkey[$options['sort-by']])) {
            $sort_by = $list_sortkey[$options['sort-by']];
        } else {
            $this->msg_error($this->trad_keyword('SORT_NOT_FOUND', $options['sort-by'], implode(', ', array_keys($list_sortkey))));
            return false;
        }
        $sort_by = (isset($list_sortkey[$options['sort-by']])) ? $list_sortkey[$options['sort-by']] : 'title';

        // ---> creation requete

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select('c.id, c.title, c.introtext, c.fulltext, c.state, c.catid, c.created, c.modified, c.publish_up, c.images, c.hits, c.featured, c.alias, cat.alias as category_alias, cat.title as category_title')
            ->from('#__contentitem_tag_map as m ')
            ->innerJoin('#__content as c on c.id = m.content_item_id')
            ->innerJoin('#__tags as t on t.id = m.tag_id')
            ->innerJoin('#__categories as cat on c.catid = cat.id')
            ->order($db->quoteName('c.' . $sort_by) . ' ' . $options['sort-order']);

        if ($options['maxi'])
            $query->setLimit($options['maxi']);

        $query->where('t.id = ' . $id . ' AND m.type_alias like "%article%"');

        // -- ID de l'article à exclure - v3.0
        if ($options['current'] == '0') {
            if (isset($this->article->id)) {
                $artid = $this->article->id;
            } else {
                $app = Factory::getApplication();
                $artid = $app->getInput()->get('id', 0); // v2.5
            }
            $query->where('c.id<>' . $artid);
        }

        if ($options['no-published'] != '1') {
            $query->where('c.state=1');
        }

        $db->setQuery($query);
        $str = $db->replacePrefix((string) $query);

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
                            // --- lien vers l'article
            $url = '';
            $slug = ($item->alias) ? ($item->id . ':' . $item->alias) : $itemid;
            $catslug = ($item->category_alias) ? ($item->catid . ':' . $item->category_alias) : $item->catid;
            $route = RouteHelper::getArticleRoute($slug, $catslug);
            $url = Route::_($route);

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
            $this->kw_replace($sItem, 'title-link', '<a href="' . $url . '">' . $title . '</a>');
            // {title} : titre categorie sans lien
            $this->kw_replace($sItem, 'title', $title);
            // {subtitle} : sous-titre categorie (partie après tilde du titre)
            $this->kw_replace($sItem, 'subtitle', $subtitle);
            // {date-crea} : date de création
            $this->kw_replace($sItem, 'date-crea', $this->up_date_format($item->created, $options['date-format'], $options['date-locale']));
            // {date-modif} : date de création
            $this->kw_replace($sItem, 'date-modif', $this->up_date_format($item->modified, $options['date-format'], $options['date-locale']));
            // {date-publish} : date de création
            $this->kw_replace($sItem, 'date-publish', $this->up_date_format($item->publish_up, $options['date-format'], $options['date-locale']));

            // {content} : texte en HTML
            // prise en charge plugins contenu v31
            if ($options['content-plugin']) {
                if (stripos($sItem, '##intro') !== false)
                    $item->introtext = $this->import_content($item->introtext); // v31
                if (stripos($sItem, '##content##') !== false)
                    $item->fulltext = $this->import_content($item->fulltext);
            }

            $this->kw_replace($sItem, 'intro', $item->introtext);
            $this->kw_replace($sItem, 'content', $item->fulltext);
            // {cat} : nom catégorie
            $this->kw_replace($sItem, 'cat', $item->category_title);
            // {tags-list} : liste des tags
            if (stripos($sItem, '##tags-list##') !== false) {
                $db = Factory::getContainer()->get(DatabaseInterface::class);
                $query = $db->getQuery(true);
                $query->select('t.title')
                    ->from('#__tags as t')
                    ->innerJoin('#__contentitem_tag_map as m on t.id = m.tag_id')
                    ->where('m.content_item_id = ' . $item->id . ' AND m.type_alias like "%article%"');
                $db->setQuery($query);
                // $debug = $query->__toString();
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
                $new = ($item->created > $max) ? $options['new-html'] : '';
                $this->kw_replace($sItem, 'new', $new);
            }
            // {hits}
            $this->kw_replace($sItem, 'hits', $item->hits);
            // {image-xxx} : l'image d'intro, sinon celle dans l'introtext
            // {image} : la balise img complete
            // {image-src} et {image-alt} : uniquement src et alt d'une balise img existante
            if (stripos($sItem, '##image') !== false) {
                $params = json_decode($item->images);
                $img_src = $options['image-src'];
                $img_alt = $options['image-alt'];
                if ($params->image_intro) {
                    $img_src = $params->image_intro;
                    $img_alt = $params->image_intro_alt;
                } else {
                    $imgTag = $this->preg_string('#(\<img .*\>)#Ui', $item->fulltext);
                    if ($imgTag) {
                        $imgAttr = $this->get_attr_tag($imgTag, 'alt');
                        $img_src = $imgAttr['src'];
                        $img_alt = $imgAttr['alt'];
                    }
                }
                $imgtag = '<img src="' . $img_src . '" alt="' . $img_alt . '">';
                $this->kw_replace($sItem, 'image-link', '<a href="' . $url . '">' . $imgtag . '</a>');
                $this->kw_replace($sItem, 'image', $imgtag);
                $this->kw_replace($sItem, 'image-src', $img_src);
                $this->kw_replace($sItem, 'image-alt', $img_alt);
            }
            // --- tags avec param
            // ##intro-text,100## : les 100 premiers caractères de l'introduction en texte brut
            preg_match('#\#\#(intro-text\s*,?\s*([0-9]*)\s*)\#\##Ui', $sItem, $tag);
            if (isset($tag[1])) {
                $intro = ($item->introtext) ? $item->introtext : $item->fulltext;
                $intro = $this->import_content($intro);
                $intro = trim(strip_tags($intro));
                if (isset($tag[1]) && $tag[1]) {
                    $len = (int) $tag[2];
                    if (strlen($intro) > $len)
                        $intro = mb_substr($intro, 0, $len) . '...';
                }
                $this->kw_replace($sItem, $tag[1], $intro);
            }
            

            // les custom fields (v3.1)
            if (strpos($sItem, '##') !== false) { 
                require_once ($this->upPath . '/assets/lib/kw_custom_field.php');
                kw_cf_replace($sItem, $item);
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

