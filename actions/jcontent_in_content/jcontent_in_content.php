<?php

/**
 * Affiche le contenu d'un article dans un autre
 *
 * syntaxe 1 : {up jcontent-in-content=id_article}
 * syntaxe 2 : {up jcontent_in_content=145}template{/up jcontent_in_content}
 *
 * Les mots-clés :
 * ##id## ##title## ##title-link## ##subtitle## ##link##
 * ##intro## ##intro-text## ##intro-text,100## ##content##
 * ##image## ##image-src## ##image-alt##
 * ##date-crea## ##date-modif## ##date-publish##
 * ##author## ##note## ##cat## ##new## ##featured## ##hits##
 * ##CF_id_or_name## : valeur brute du custom field

 * @author   LOMART
 * @version  UP-1.7
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags    Joomla
 */

/*
 * v2.9 compatibilité PHP8 pour ##date-xxx##
 * v3.1
 * - ajout option 'content-plugin'
 * - prise en charge des mots -clés pour les customs-fields
 * v5.3.3 - Joomla 6 : remplacement de getInstance
 */
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\CMS\HTML\HTMLHelper;

class jcontent_in_content extends upAction
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
            __class__ => '', // ID de l'article
            'no-published' => '1', // Liste aussi les articles non publiés
            'content-plugin' => 0, // prise en compte des plugins de contenu pour ##into et ##content##
            /* [st-model] Modèle de présentation */
            'template' => '##content##', // modèle de mise en page. Si vide le modèle est le contenu. BBCode accepté
            /* [st-main] Balise & style pour le bloc parent */
            'main-tag' => 'div', // balise pour le bloc d'un article. 0 pour aucun
            'id' => '', // identifiant
            'main-style' => '', // classes et styles inline pour un article
            'main-class' => '', // classe(s) pour un article (obsoléte)
            /* [st-format] Format pour les mots-clés */
            'date-format' => '%e %B %Y', // format pour les dates
            'date-locale' => '', // localisation pour les dates. Par défaut, celle du navigateur client.
            /* [st-CSS] Style CSS */
            'css-head' => '' // code CSS dans le head
        );

        $sItemid = (int) $this->options_user[__class__];

        // ======> fusion et controle des options
        $options = $this->ctrl_options($options_def);
        // ======> verif template (modèle de mise en page)
        // en priorité : le contenu entre shortcode
        // en second : le model dans prefs.ini
        if ($this->content) {
            $tmpl = $this->content;
        } else {
            $tmpl = $options['template'];
        }
        if (! $tmpl) {
            $this->msg_error($this->trad_keyword('NO_TEMPLATE'));
            return false;
        }
        $tmpl = $this->get_bbcode($tmpl, '+hr|pre');
        // =====> RECUP DES DONNEES
        // JLoader::register('ContentModelArticles', JPATH_SITE . '/components/com_content/models/articles.php');
        $model = Factory::getApplication()->bootComponent('com_content')->getMVCFactory()->createModel('Articles');
        if (is_bool($model)) {
            return 'Aucune catégorie';
        }

        // Set application parameters in model
        $app = Factory::getApplication();
        $appParams = $app->getParams();
        $model->setState('params', $appParams);

        // Set the filters based on the module params
        // etat publication
        if ($options['no-published'] !== true) {
            $model->setState('filter.published', 1);
        }

        // Access filter
        $access = ! ComponentHelper::getParams('com_content')->get('show_noauth');
        $authorised = Access::getAuthorisedViewLevels(Factory::getApplication()->getIdentity()->id);
        $model->setState('filter.access', $access);
        // Article filter
        $model->setState('filter.article_id', $sItemid);

        $items = $model->getItems();
        if (count($items) == 0) {
            return '<span class="b t-red">' . $sItemid . ' : ID article non trouvé / not found ...</span>';
        }
        $item = $items[0];

        // ======> Style général et par article
        $main_attr['id'] = $options['id'];
        $this->get_attr_style($sItem_attr, $options['main-class'], $options['main-style']);

        // css-head
        $this->load_css_head($options['css-head']);

        // ======> mise en forme résultat
        // --- Bloc article
        if ($options['main-tag'] != '0') {
            $html[] = $this->set_attr_tag($options['main-tag'], $sItem_attr);
        }
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
        // {id} : ID de l'article
        $this->kw_replace($sItem, 'id', $item->id);
        // {link} : lien vers l'article - a mettre dans balise a
        $this->kw_replace($sItem, 'link', $url);
        // {title} : titre de l'article
        $this->kw_replace($sItem, 'title-link', '<a href="' . $url . '">' . $title . '</a>');
        // {title-no-link} : titre de l'article
        $this->kw_replace($sItem, 'title', $title);
        // {subtitle} : sous-titre de l'article (partie après tilde du titre)
        $this->kw_replace($sItem, 'subtitle', $subtitle);
        // {cat} : catégorie de l'article
        $this->kw_replace($sItem, 'cat', $item->category_title);
        // {date-crea} : date de création
        $this->kw_replace($sItem, 'date-crea', $this->up_date_format($item->created, $options['date-format'], $options['date-locale']));
        // {date-modif} : date de modification
        $this->kw_replace($sItem, 'date-modif', $this->up_date_format($item->modified, $options['date-format'], $options['date-locale']));
        // {date-publish} : date de publication
        $this->kw_replace($sItem, 'date-publish', $this->up_date_format($item->publish_up, $options['date-format'], $options['date-locale']));
        // {author} : auteur
        $this->kw_replace($sItem, 'author', $item->author);

        // --- traitement $item->introtext et $item->fulltext
        // si pas d'introtext -> introtext=fulltext et fulltext=vide
        // ce n'est pas souhaitable car le contenu fulltext n'est pas prévu pour cela
        if ($item->fulltext == '') {
            $item->fulltext = $item->introtext;
            $item->introtext = '';
        }
        // prise en charge plugins contenu v31
        if ($options['content-plugin']) {
            if (stripos($sItem, '##intro') !== false) {
                $item->introtext = $this->import_content($item->introtext);
            } // v31
            if (stripos($sItem, '##content##') !== false) {
                $item->fulltext = $this->import_content($item->fulltext);
            }
        }

        // {intro} : texte d'introduction en HTML
        PluginHelper::importPlugin('content');
        $content = HTMLHelper::_('content.prepare', $item->introtext);
        $this->kw_replace($sItem, 'intro', $content);
        // {content} : contenu de l'article en HTML
        PluginHelper::importPlugin('content');
        $content = ($item->fulltext == '') ? ($item->introtext) : ($item->fulltext);
        $content = HTMLHelper::_('content.prepare', $content);
        $this->kw_replace($sItem, 'content', $content);
        // {cat} : nom catégorie
        $this->kw_replace($sItem, 'cat', $item->category_title);
        // {tags} : liste des tags
        if (stripos($sItem, '##tag##') !== false) {
        }
        // {featured} : en vedette
        if (stripos($sItem, '##featured##') !== false) {
            $tmp = ($item->featured == '1') ? $options['featured-html'] : '';
            $this->kw_replace($sItem, 'featured', $tmp);
        }
        // {hit}
        $this->kw_replace($sItem, 'hits', $item->hits);

        // {image-xxx} : l'image d'intro, sinon celle dans l'introtext
        // {image} : la balise img complete
        // {image-src} et {image-alt} : uniquement src et alt d'une balise img existante
        if (stripos($sItem, '##image') !== false) {
            $images = json_decode($item->images);
            $img_src = '';
            if ($images->image_intro) {
                $img_src = $images->image_intro;
                $img_alt = $images->image_intro_alt;
            } else {
                $imgTag = $this->preg_string('#(\<img .*\>)#Ui', $item->introtext);
                $imgAttr = $this->get_attr_tag($imgTag, 'src,alt');
                $img_src = (isset($imgAttr['src'])) ? $imgAttr['src'] : '';
                $img_alt = (isset($imgAttr['alt'])) ? $imgAttr['alt'] : '';
            }
            $this->kw_replace($sItem, 'image', '<img src="' . $img_src . '" alt="' . $img_alt . '">');
            $this->kw_replace($sItem, 'image-src', $img_src);
            $this->kw_replace($sItem, 'image-alt', $img_alt);
        }

        // --- tags avec param
        // {intro-text,100} : les 100 premiers caractères de l'introduction en texte brut
        preg_match('#\#\#(intro-text\s*,?\s*([0-9]*)\s*)\#\##Ui', $sItem, $tag);
        if (! empty($tag[1])) {
            $intro = ($item->introtext) ? $item->introtext : $item->fulltext;
            $intro = trim(strip_tags($intro));
            if (isset($tag[1]) && $tag[1]) {
                $len = (int) $tag[2];
                if (strlen($intro) > $len) {
                    $intro = mb_substr($intro, 0, $len) . '...';
                }
            }
            $this->kw_replace($sItem, $tag[1], $intro);
        }

        // les custom fields (v3.1)
        if (strpos($sItem, '##') !== false) { // 3.1
            require_once($this->upPath . '/assets/lib/kw_custom_field.php');
            kw_cf_replace($sItem, $item);
        }

        // --- fin article
        $html[] = $sItem;
        if ($options['main-tag'] != '0') {
            $html[] = '</' . $options['main-tag'] . '>';
        }

        return implode(PHP_EOL, $html);
    }

    // run
}

// class
