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

namespace Lomart\Plugin\Content\Up\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;

// Prevent direct access
defined('_JEXEC') || die;

class ActionsField extends ListField
{
    /**
     * Element name
     *
     * @var   string
     */
    protected $_name = 'Actions';

    public function getOptions()
    {
        $return = '';

        $upPath = JPATH_ROOT . '/plugins/content/up'; // de base
        $file = $upPath.'/assets/UP-list-actions-version.txt';
        $actions = [];
        if (!is_file($file)) {
            return false;
        }
        $readBuffer = file($file, FILE_IGNORE_NEW_LINES);
        if (!$readBuffer) {// `file` couldn't read the htaccess we can't do anything at this point
            return '';
        }
        foreach ($readBuffer as $line) {
            $one = explode(':', $line);
            if (sizeof($one) > 1) {
                $actions[] = $one[0];
            }
        }
        $options = [];

        foreach ($actions as $action) {
            $options[] = HTMLHelper::_('select.option', $action, $action);
        }

        return array_merge(parent::getOptions(), array_values($options));

    }
    public function setup(\SimpleXMLElement $element, $value, $group = null)
    {
        if (\is_string($value)) {
            $value = explode(',', $value);
        }
        $return = parent::setup($element, $value, $group);

        return $return;
    }

}
