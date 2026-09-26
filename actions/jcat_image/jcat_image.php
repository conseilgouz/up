<?php

/**
 * Affiche une image en rapport avec la catégorie de l'article courant
 *
 * syntaxe {up jcat_image}
 *
 * @author   LOMART
 * @version  UP-1.95
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @tags Joomla
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Component\Content\Site\Model\ArticleModel;
use Joomla\Component\Categories\Administrator\Model\CategoryModel;
use Lomart\Plugin\Content\Up\Helper\UpHelper;

class jcat_image extends Lomart\Plugin\Content\Up\Extension\Up
{
    public function init()
    {
        return true;
    }

    public function run()
    {

        // lien vers la page de demo
        UpHelper::set_demopage($this);

        $options_def = array(
          __class__ => '', // dossier/chemin/url vers une image si la catégorie n'en possède pas
          /* [st-css] Style CSS*/
            'id' => '',  // identifiant
          'class' => '', // classe(s) pour bloc
          'style' => '', // style inline pour bloc
          'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // fusion et controle des options
        $options = UpHelper::ctrl_options($this, $options_def);

        // === CSS-HEAD
        UpHelper::load_css_head($this, $options['css-head']);

        // === récup ID article
        $artid = (int)Factory::getApplication()->getInput()->get('id');

        // === récup image categorie
        $model     = new ArticleModel(array('ignore_request' => true));
        $app       = Factory::getApplication();
        $appParams = $app->getParams();
        $params = $appParams;
        $model->setState('params', $appParams);
        $model->setState('list.start', 0);
        $model->setState('list.limit', 1);
        $item = $model->getItem($artid);
        $catid = ($item != null) ? $item->catid : '';
        $model     = new CategoryModel(array('ignore_request' => true));
        $category = $model->getItem($catid);
        $img['src'] = $category->params['image'];
        $img['alt'] = $category->params['image_alt'];

        // === si pas d'image, on utilise default
        if (empty($img['src'])) {
            $foldername = rtrim($options[__class__], '/') . '/';
            if (strpos($foldername, '..') !== false) {
                return "Erreur : le répertoire ".$foldername." contient des caractères interdits";
            }
            if (substr($foldername, -2) == '#/') {
                $foldername = str_replace('#/', $catid . '-*/', $foldername);
            }
            if (!UpHelper::on_server($this, rtrim($foldername, '/'))) {
                return "Erreur : le répertoire ".$foldername." n'est pas sur ce serveur";
            }
            if (!is_dir(rtrim($foldername, '/'))) {
                return "Erreur : le répertoire ".$foldername." n'existe";
            }
            $imgList = glob($foldername . '*.{jpg,JPG,jpeg,JPEG,png,PNG,webp,WEBP}', GLOB_BRACE | GLOB_NOSORT); // v2.5 pascal
            if (!empty($imgList)) {
                $num = rand(0, count($imgList) - 1);
                $img['src'] = $imgList[$num];
            }
        }
        if (empty($img['src'])) {
            $img['src'] = $options[__class__];
        }

        // attributs du bloc principal
        $img['id'] = $options['id'];
        UpHelper::get_attr_style($this, $img, $options['class'], $options['style']);
        if ($img['alt'] == '') {
            $img['alt'] = UpHelper::link_humanize($this, $img['src']);
        }

        // code en retour
        $out = (!empty($img['src'])) ? UpHelper::set_attr_tag($this, 'img', $img) : '';
        return $out;
    }

    // run
}

// class
