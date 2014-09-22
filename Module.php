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
namespace LocaleManager;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ServiceProviderInterface;
use Zend\ModuleManager\Feature\InitProviderInterface;
use Zend\ModuleManager\ModuleManagerInterface;
use Zend\ModuleManager\ModuleEvent;
use Zend\Console\Console;
use Zend\ModuleManager\Feature\BootstrapListenerInterface;
use Zend\EventManager\EventInterface;
use Zend\ModuleManager\Feature\ViewHelperProviderInterface;

class Module implements
    AutoloaderProviderInterface,
    BootstrapListenerInterface,
    InitProviderInterface,
    ServiceProviderInterface, 
    ViewHelperProviderInterface
{
    
    public function init(ModuleManagerInterface $manager)
    {
        $events = $manager->getEventManager();
    
        // Registering a listener at default priority, 1, which will trigger
        // after the ConfigListener merges config.
        $events->attach(ModuleEvent::EVENT_MERGE_CONFIG, array($this, 'onMergeConfig'));
    }
    
    public function onBootstrap(EventInterface $e)
    {
        // Make sure the locale manager has been started
        $e->getApplication()->getServiceManager()->get('LocaleManager');
    }
    
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
    
    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'LocaleManager' => 'LocaleManager\Service\LocaleManagerFactory',
            )
        );
    }
    
    public function getViewHelperConfig()
    {
        return array(
            'factories' => array(
                'getLocale' => 'LocaleManager\View\Helper\Service\GetLocaleFactory',   		
           ),
        );
    }
    
    public function onMergeConfig(ModuleEvent $e)
    {
        $configListener = $e->getConfigListener();
        $config         = $configListener->getMergedConfig(false);
    
        // Make sure the router class is set to LocaleAwareTreeRouteStack
        if (!Console::isConsole()) {
            $config['router']['router_class'] = 'LocaleManager\Router\Http\LocaleTreeRouteStack';
        }
    
        // Pass the changed configuration back to the listener:
        $configListener->setMergedConfig($config);
    }
    
}