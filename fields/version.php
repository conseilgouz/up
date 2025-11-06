<?php
/**
 *
 * @package plg_UP for Joomla! 3.0+
 * @version $Id: up.php 2025-11-06 $
 * @author Lomart
 * @copyright (c) 2025 Lomart
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 *
 * */

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\String\StringHelper;

// Prevent direct access
defined('_JEXEC') || die;

class  JFormFieldVersion extends FormField
{
    /**
     * Element name
     *
     * @var   string
     */
    protected $_name = 'Version';

    public function getInput()
    {
        $return = '';
        // Load language
        $ext = $this->def('extension');
        $ext = explode('/', $ext);
        $type = "";
        $folder = "";
        if (count($ext) == 1) { // autoreadmore
            $extension = $ext[0];
        } elseif (count($ext) == 2) { // plugin /autoreadmore
            $type = $ext[0];
            $extension = $ext[1];
        } elseif (count($ext) == 3) { // plugin/content/autoreadmore
            $type = $ext[0];
            $folder = $ext[1];
            $extension = $ext[2];
        }
        $version = '';

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery();
        $query
            ->select($db->quoteName('manifest_cache'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . '=' . $db->Quote($extension));
        if ($type) {
            $query->where($db->quoteName('type') . '=' . $db->Quote($type));
        }
        if ($folder) {
            $query->where($db->quoteName('folder') . '=' . $db->Quote($folder));
        }
        $db->setQuery($query, 0, 1);
        $row = $db->loadAssoc();
        $tmp = json_decode($row['manifest_cache']);
        $version = $tmp->version;

        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $css = '';
        $css .= ".version {display:block;text-align:right;color:brown;font-size:12px;}";
        $css .= ".readonly.plg-desc {font-weight:normal;}";
        $css .= "fieldset.radio label {width:auto;}";
        $wa->addInlineStyle($css);
        $margintop = $this->def('margintop');
        $margintopstr = "";
        if (StringHelper::strlen($margintop)) {
            $margintopstr = "parent.style.marginTop = '".$margintop."';";
        }
        $marginright = $this->def('marginright');
        $marginrightstr = "";
        if (StringHelper::strlen($marginright)) {
            $marginrightstr = "parent.style.marginRight = '".$marginright."';";
        }
        $float = $this->def('float');
        $floatstr = "";
        if (StringHelper::strlen($float)) {
            $floatstr = "parent.style.float = '".$float."';";
        }
        if ($margintopstr || $marginrightstr || $floatstr) {
            $js = "document.addEventListener('DOMContentLoaded', function() {
			vers = document.querySelector('.version');
			parent = vers.parentElement.parentElement;
			".$margintopstr.";
			".$marginrightstr.";
            ".$floatstr. ";
			})";
            $wa->addInlineScript($js);
        }
        $return .= '<span class="version">' . Text::_('JVERSION') . ' ' . $version . "</span>";

        return $return;
    }
    public function def($val, $default = '')
    {
        return (isset($this->element[$val]) && (string) $this->element[$val] != '') ? (string) $this->element[$val] : $default;
    }

}
