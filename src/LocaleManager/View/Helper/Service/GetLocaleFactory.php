<?php
/**
 * LocaleManager
 *
 * Locale manager for ZF-2
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @author Juan Pedro Gonzalez Gutierrez
 * @copyright Copyright (c) 2013 Juan Pedro Gonzalez Gutierrez (http://www.jpg-consulting.com)
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2 License
 */

namespace LocaleManager\View\Helper\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use LocaleManager\View\Helper\GetLocale;
use Zend\View\HelperPluginManager;

class GetLocaleFactory implements FactoryInterface
{
	
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
    	$helper = new GetLocale();
    	
    	if ($serviceLocator instanceof HelperPluginManager) {
    	   $localeManager = $serviceLocator->getServiceLocator()->get('LocaleManager');	
    	} else {
    	    $localeManager = $serviceLocator->get('LocaleManager');
    	}
    	$helper->setLocaleManager( $localeManager );
    	
    	return $helper;
    }
    
}