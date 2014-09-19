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
namespace LocaleManager\Listener;

use Zend\EventManager\ListenerAggregateInterface;
use Zend\Mvc\MvcEvent;
use Zend\EventManager\EventManagerInterface;

class LocaleManagerListener implements 
    ListenerAggregateInterface
{
    /**
     * @var array
     */
    protected $callbacks = array();
	    
    /**
     * Attach one or more listeners
     *
     * Implementors may add an optional $priority argument; the EventManager
     * implementation will pass this to the aggregate.
     *
     * @param EventManagerInterface $events
     *
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $this->callbacks[] = $events->attach(
                MvcEvent::EVENT_RENDER,
                array($this, 'onRenderEvent'),
                0
        );
    
        $this->callbacks[] = $events->attach(
                MvcEvent::EVENT_FINISH,
                array($this, 'onFinishEvent'),
                0
        );
    }
    
    /**
     * Detach all previously attached listeners
     *
     * @param EventManagerInterface $events
     *
     * @return void
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->callbacks as $index => $callback) {
            if ($events->detach($callback)) {
                unset($this->callbacks[$index]);
            }
        }
    }
    
    public function onRenderEvent(MvcEvent $e)
    {
        $routeMatch = $e->getRouteMatch();
         
        if (!$routeMatch) {
            // Nothing to do if thereis not a match!
            return;
        }
        
        $router         = $e->getRouter();
        $serviceManager = $e->getApplication()->getServiceManager();
        $localeManager  = $serviceManager->get('LocaleManager');
        $headLink       = $serviceManager->get('viewhelpermanager')->get('headLink');
        $locales        = $serviceManager->get('LocaleManager')->getAvailableLocales();
        
        foreach( $locales as $locale) {
            if (strcasecmp($locale, $localeManager->getLocale()) !== 0) {
                $params = $routeMatch->getParams();
                $params['locale'] = $locale;
                 
                $url = $router->assemble(
                        $params,
                        array(
                            'name' => $routeMatch->getMatchedRouteName(),
                            'locale' => $locale,
                        )
                );
                 
                $headLink->appendAlternate($url, 'text/html', null, array('hreflang' => $locale));
            }
        }
    }
    
    public function onFinishEvent(MvcEvent $e)
    {
        $serviceManager = $e->getApplication()->getServiceManager();
        $localeManager  = $serviceManager->get('LocaleManager');
        
        $response = $e->getResponse();
        $response->getHeaders()->addHeaderLine('Content-Language', $localeManager->getLocale());
    }
}

