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

defined('_JEXEC') or die();

use Joomla\Registry\Registry;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Layout\FileLayout;

class CompileField extends FormField
{
    protected $type = 'compile';

    public function renderField($options = array())
    {
        $layout = new FileLayout('compile_scss', JPATH_ROOT . '/plugins/content/up/layouts');
        return $layout->render();
    }
}
