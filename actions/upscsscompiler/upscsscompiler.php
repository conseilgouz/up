<?php

/**
 * Compile tous les fichiers SCSS dans les dossiers des actions
 *
 * syntaxe:
 * {up upscsscompiler}  toutes les actions
 * {up upscsscompiler=action1, action2}  une ou plusieurs actions
 *
 * note: les fichiers SCSS & CSS sont en racine des dossiers actions
 *
 * @author   LOMART
 * @version  UP-1.2
 * @license   <a href="http://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GNU/GPLv3</a>
 * @credit   https://github.com/leafo/scssphp
 * @tags UP
 */
/*
 * v1.7 - update SCSSPHP version 0.8.4 de leafo
 * v1.8 - update SCSSPHP version 1.0.6
 * v2.5 - creation assets/colorname.ini
 * - option without-custom
 * - non exécution par upscsscompiler=0
 * v2.51 - fix export css - correction version zip
 * v2.9 - update SCSSPHP version 1.11.0
 * v5.2 - update SCSSPHP version 2.0.1
 *  - add option map
 */
defined('_JEXEC') or die();

if (! class_exists('ScssPhp\ScssPhp\Compiler')) {
    require 'vendor/autoload.php';
}

use ScssPhp\ScssPhp\Compiler;

class upscsscompiler extends upAction
{
    protected $options;

    protected $basePath;

    protected $formatter;

    public function init()
    {
        // charger les ressources communes à toutes les instances
        return true;
    }

    public function run()
    {
        $this->set_demopage();

        $options_def = array(
            /* [st-sel] Sélection des actions */
            'upscsscompiler' => '', // liste des actions à recompiler. toutes par défaut. 0 pour ne rien compiler
            'without-custom' => '0', // sans prise en compte des personnalisations. Usage interne pour créer le zip de UP
            'force' => '0', // force la compilation de tous les SCSS. Par défaut: les SCSS plus récents que leur CSS.
            'force-filter' => '', // oblige force si la condition est remplie
            /* [st-div] Divers */
            'mode' => 'Compressed', // Compressed, Expanded
            'map' => 0, // génère une sourceMap
            'info' => '0', // affiche rapport compilation
            'id' => '', // identifiant
        );

        // chemin pour sauver une copie des CSS si without-custom
        $upRootPath = JPATH_BASE . DIRECTORY_SEPARATOR . $this->upPath;
        $bakRootPath = JPATH_BASE . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'UP' . DIRECTORY_SEPARATOR . 'upcss' . DIRECTORY_SEPARATOR;
        if (!defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }

        // fusion et controle des options
        $this->options = $this->ctrl_options($options_def);
        // v2.5 - inactif si = 0 - permet de conserver le shortcode dans un article
        if ($this->options[__class__] === '0') {
            return '';
        }

        $this->msg_info("Your version is ".phpversion());
        if (phpversion() < '8.1.30') {
            $msg = "UpScssCompiler - PHP minimal version required is 8.1.30. ";
            $msg .= "Your version is ".phpversion();
            return $this->msg_inline($msg);
        }

        // Retirer les notices du rapport d'erreur
        $bak_error_reporting = error_reporting();
        error_reporting($bak_error_reporting ^ E_NOTICE);

        // vérfie et corrige le mode si erreur
        $this->options['mode'] = $this->ctrl_argument($this->options['mode'], 'Compressed, Expanded');
        $this->options['mode'] = trim(strtoupper($this->options['mode']));
        //
        // ==== Vérif filtrage
        $this->options['force'] = ($this->options['force'] || $this->options['without-custom'] || $this->filter_ok($this->options['force-filter'], false) === true);
        //
        // ==== Liste des actions
        if ($this->options[__class__] == '') {
            $actionsList = $this->up_actions_list();
        } else {
            // uniquement celles demandées
            $tmp = array_map('trim', explode(',', $this->options[__class__]));
            foreach ($tmp as $key) {
                if (array_key_exists($key, $this->dico)) {
                    $key = $this->dico[$key];
                }
                $actionsList[] = str_replace('-', '_', $key);
            }
        }

        // === COMPILATION DES FICHIERS SCSS ===

        // ==== compilation fichier principal de UP si toutes les actions demandées
        if ($this->options[__class__] == '' || $this->options['force']) {
            $this->basePath = $upRootPath . 'assets' . DIRECTORY_SEPARATOR;
            if ($this->options['without-custom']) {
                // inactiver up/assets/_variables.scss
                rename($this->basePath . 'custom/_variables.scss', $this->basePath . 'custom/_variables.scss.bak');
                copy($this->basePath . 'custom/_variables.scss.empty', $this->basePath . 'custom/_variables.scss.empty.bak');
                rename($this->basePath . 'custom/_variables.scss.empty', $this->basePath . 'custom/_variables.scss');
            }
            $fileScss = $this->basePath . 'up.scss';
            $this->scss_compile($fileScss);
            $this->make_colorname();

            if ($this->options['without-custom']) {
                // copie du fichier pour zip
                $this->saveCopy(str_replace('.scss', '.css', $fileScss), $bakRootPath . 'assets/up.css');
                $this->saveCopy($upRootPath . 'assets/colorname.ini', $bakRootPath . 'assets/colorname.ini');
                $this->saveCopy($upRootPath . 'assets/colorname-ref.ini', $bakRootPath . 'assets/colorname-ref.ini');
                // inactiver up/assets/_variables.scss
                rename($this->basePath . 'custom/_variables.scss.bak', $this->basePath . 'custom/_variables.scss');
                rename($this->basePath . 'custom/_variables.scss.empty.bak', $this->basePath . 'custom/_variables.scss.empty');
            }
        }
        // ==== compilation des SCSS des actions
        $this->basePath = $upRootPath . 'actions' . DIRECTORY_SEPARATOR;
        foreach ($actionsList as $action) {
            $actionPath = $this->basePath . $action . DIRECTORY_SEPARATOR;
            $listScss = $this->glob_recursive($actionPath . "[!_]*.scss");
            foreach ($listScss as $fileScss) {
                $this->scss_compile($fileScss);
                if ($this->options['without-custom']) {
                    $src = str_replace('.scss', '.css', $fileScss);
                    $dest = str_replace($upRootPath, $bakRootPath, $src);
                    $this->saveCopy($src, $dest);
                }
            }
        }

        if (!empty($this->options['without-custom'])) {
            $this->msg_info($this->trad_keyword('SAVE_COPY_OK', $bakRootPath));
        }
        // === CODE HTML EN RETOUR ===
        error_reporting($bak_error_reporting);
        return '';
    }

    // run

    /*
     * fait une copie de $source vers $dest
     * avec creation path si besoin
     */
    public function saveCopy($source, $dest)
    {
        $ok = true;
        if (! file_exists(dirname($dest))) {
            $ok = mkdir(dirname($dest), 0755, true);
        }
        $ok = $ok && copy($source, $dest);
        if (! $ok) {
            $this->msg_error($this->trad_keyword('EXPORT_ERR', $dest));
        }
    }

    /*
     * Compile et sauve le fichier SCSS
     */
    public function scss_compile($fileScss)
    {

        $fileCss = str_replace('.scss', '.css', $fileScss);
        $pathAbsolute = pathinfo($fileCss, PATHINFO_DIRNAME) . DS;
        $pathRelative = str_replace(JPATH_ROOT, '', $pathAbsolute);
        if ($this->options['force'] || ! file_exists($fileCss) || filemtime($fileScss) > filemtime($fileCss)) {
            $scss_compiler = new Compiler();
            // ==== FORMAT
            if ($this->options['mode'] == 'EXPANDED') {
                $scss_compiler->setOutputStyle(ScssPhp\ScssPhp\OutputStyle::EXPANDED);
            } else {
                $scss_compiler->setOutputStyle(ScssPhp\ScssPhp\OutputStyle::COMPRESSED);
            }
            // ==== MAP
            if ($this->options['map']) {
                $fileMap = $fileCss . '.map';
                $scss_compiler->setSourceMap(Compiler::SOURCE_MAP_FILE);
                $scss_compiler->setSourceMapOptions(array(
                    // relative or full url to the above .map file
                    'sourceMapURL'      => $pathRelative . $fileMap,
                    // partial path (server root) removed (normalized) to create a relative url
                    // difference between file & url locations, removed from ALL source files in .map
                    'sourceMapBasepath' => str_replace('\\', '/', JPATH_ROOT),
                    ));
            }

            //
            $scss_compiler->setImportPaths(pathinfo($fileScss, PATHINFO_DIRNAME));


            try {
                $string_sass = file_get_contents($fileScss);
                $result = $scss_compiler->compileString($string_sass);
                if ($result > '') {
                    file_put_contents($fileCss, $result->getCss());
                }
                if ($this->options['map']) {
                    file_put_contents($fileMap, $result->getSourceMap());
                }
                if ($this->options['info']) {
                    $msg = str_replace($this->basePath, '', $fileScss);
                    $msg .= ' -> ';
                    $msg .= str_replace($this->basePath, '', $fileCss);
                    $this->msg_info($msg, $this->trad_keyword('COMPIL_OK'));
                }
            } catch (Exception $e) {
                $msg = $this->trad_keyword('COMPIL_ERR');
                $msg .= str_replace($this->basePath, '', $fileScss);
                $this->msg_error($msg . '<br>' . $e->getmessage());
            }
        }
    }

    /*
     * Recherche récursive des fichiers
     */
    public function glob_recursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);

        foreach (glob(dirname($pattern) . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            if (stripos($dir, '_note') === false && (empty($this->options['without-custom']) || substr($dir, - 6, 6) !== 'custom')) {
                $files = array_merge($files, $this->glob_recursive($dir . DIRECTORY_SEPARATOR . basename($pattern), $flags));
            }
        }

        return $files;
    }

    /*
     * make_colorname
     * genere un fichier up/assets/colorname.ini
     * a partir du fichier up/assets/colorname.css
     * lui-meme genere par up/assets/colorname.scss
     * Ce fichier est utilisé par l'action color
     */
    public function make_colorname()
    {
        $base = $this->upPath . 'assets/';
        $filecss = $base . 'up.css';
        if (file_exists($filecss) === false) {
            $this->msg_error($filecss . ' not found');
        }
        $css = file_get_contents($filecss);

        $regex = '#t-hover-(.*):hover.*(?:t-hover-(.*):hover)?\{color\:(.*) \!important\}#Us';
        if (preg_match_all($regex, $css, $tmp)) {
            for ($i = 0; $i < count($tmp[0]); $i++) {
                $name1 = trim($tmp[1][$i]);
                $name2 = trim($tmp[2][$i]);
                $color = trim($tmp[3][$i]);
                $iniRef[] = $name1 . '="' . $color . '"';
                $ini[] = strtoupper($name1) . '="' . $color . '"';
                if ($name2) {
                    $iniRef[] = $name2 . '="' . $color . '"';
                    $ini[] = strtoupper($name2) . '="' . $color . '"';
                }
            }
            file_put_contents($base . 'colorname.ini', implode(PHP_EOL, $ini));
            file_put_contents($base . 'colorname-ref.ini', implode(PHP_EOL, $iniRef));
            $this->msg_info($this->trad_keyword('COLORNAME_INI_OK'));
        }
    }
}

// class
