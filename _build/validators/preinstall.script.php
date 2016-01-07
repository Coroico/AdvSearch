<?php

/**
 * AdvSearch pre-install script
 *
 * Copyright 2012 Coroico <coroico@wangba.fr>
 * @author Coroico <coroico@wangba.fr>
 * @author goldsky <goldsky@virtudraft.com>
 * 07/1/2016
 *
 * Mycomponent is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option) any
 * later version.
 *
 * Mycomponent is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * Mycomponent; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package advsearch
 */
/**
 * Description: Example validator checks for existence of getResources
 * @package advsearch
 * @subpackage build
 */
/**
 * @package advsearch
 * Validators execute before the package is installed. If they return
 * false, the package install is aborted. This example checks for
 * the installation of getResources and aborts the install if
 * it is not found.
 */
/* The $modx object is not available here. In its place we
 * use $object->xpdo
 */
$modx = & $object->xpdo;


$modx->log(xPDO::LOG_LEVEL_INFO, 'Running PHP Validator.');
switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_INSTALL:

        $modx->log(xPDO::LOG_LEVEL_INFO, 'Checking for installed AdvSearch add-on ');
        $success = true;
        /* check for requirements */
        // Revo version >= 2.1
        $modx->log(xPDO::LOG_LEVEL_INFO, ' >>> Checking for Revo version compability ');
        $versionData = $modx->getVersionData();
        $version = (float) "{$versionData['version']}.{$versionData['major_version']}{$versionData['minor_version']}";
        if ($version < 2.08) {
            $modx->log(xPDO::LOG_LEVEL_ERROR, 'This package requires at least the version 2.0.8 of MODx Revo. Please upgrade your MODx install');
            $success = false;
        }
        // check that multibyte string option is on
        $modx->log(xPDO::LOG_LEVEL_INFO, ' >>> Checking for Multibyte string option ');
        $usemb = $modx->getOption('use_multibyte', null, false);
        if (!$usemb) {
            $modx->log(xPDO::LOG_LEVEL_ERROR, 'This package runs only with the multibyte extension on. See Lexicon and language system settings.');
            $success = false;
        }
        // check the existence of the Zend search library
//        $modx->log(xPDO::LOG_LEVEL_INFO, ' >>> Checking for the presence of the Zend Search library ');
//        if (!is_dir(MODX_CORE_PATH . 'components/advsearch/library/ZendSearch')) {
//            $modx->log(xPDO::LOG_LEVEL_INFO, 'Zend search library not found in ' . MODX_CORE_PATH . 'components/advsearch/library/ZendSearch - May be you have installed this library somewhere else. Install will continue');
//        }

        if ($success) {
            $modx->log(xPDO::LOG_LEVEL_INFO, 'End of validation. Install package successfull');
        } else {
            $modx->log(xPDO::LOG_LEVEL_ERROR, 'End of validation. Install package aborted');
        }
        break;

    /* These cases must return true or the upgrade/uninstall will be cancelled */
    case xPDOTransport::ACTION_UPGRADE:
        $success = true;
        break;

    case xPDOTransport::ACTION_UNINSTALL:
        $success = true;
        break;
}

return $success;
