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
use Joomla\Database\DatabaseInterface;

class jcat_image extends upAction
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
          __class__ => '', // dossier/chemin/url vers une image si la catégorie n'en possède pas
          /* [st-css] Style CSS*/
            'id' => '',  // identifiant
          'class' => '', // classe(s) pour bloc
          'style' => '', // style inline pour bloc
          'css-head' => '' // style ajouté dans le HEAD de la page
        );

        // fusion et controle des options
        $options = $this->ctrl_options($options_def);

        // === CSS-HEAD
        $this->load_css_head($options['css-head']);

        // === récup ID article
        $artid = Factory::getApplication()->getInput()->get('id');

        // === récup image categorie
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery();
        $query
            ->select(array('cat.params', 'cat.id'))
            ->from($db->quoteName('#__content', 'art'))
            ->join('INNER', $db->quoteName('#__categories', 'cat') . ' ON ' . $db->quoteName('cat.id') . ' = ' . $db->quoteName('art.catid'))
            ->where($db->quoteName('art.id') . '=' . $db->quote($artid));
        $db->setQuery($query);
        $result = $db->loadRow();
        $catid = $result[1];
        $params = (array) json_decode($result[0]);

        $img['src'] = $params['image'];
        $img['alt'] = $params['image_alt'];

        // === si pas d'image, on utilise default
        if (empty($img['src'])) {
            $foldername = rtrim($options[__class__], '/') . '/';
            if (substr($foldername, -2) == '#/') {
                $foldername = str_replace('#/', $catid . '-*/', $foldername);
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
        $this->get_attr_style($img, $options['class'], $options['style']);
        if ($img['alt'] == '') {
            $img['alt'] = $this->link_humanize($img['src']);
        }

        // code en retour
        $out = (!empty($img['src'])) ? $this->set_attr_tag('img', $img) : '';
        return $out;
    }

    // run
}

// class
