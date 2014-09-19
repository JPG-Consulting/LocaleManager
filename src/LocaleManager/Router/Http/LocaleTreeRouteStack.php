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
namespace LocaleManager\Router\Http;

use Zend\Mvc\Router\Http\TranslatorAwareTreeRouteStack;
use LocaleManager\LocaleManagerAwareInterface;
use LocaleManager\LocaleManager;
use Zend\Stdlib\ArrayUtils;
use LocaleManager\Router\Exception;
use Zend\Stdlib\RequestInterface as Request;
use Zend\Uri\Http as HttpUri;
use Zend\Mvc\Router\Http\RouteMatch;

class LocaleTreeRouteStack extends TranslatorAwareTreeRouteStack implements LocaleManagerAwareInterface
{
    /**
     * 
     * @var \LocaleManager\LocaleManager;
     */
	protected $localeManager;
	
	/**
	 * Method used to detect locales
	 * 
	 * @var string
	 */
	protected $localeMethod = 'path';
	
	/**
	 * Set locale manager
	 *
	 * @param LocaleManager $serviceManager
	 */
	public function setLocaleManager(LocaleManager $localeManager)
	{
		$this->localeManager = $localeManager;
		return $this;
	}
	
	/**
	 * Set the locale detection method.
	 * 
	 * @param string $method
	 * @return \LocaleManager\Router\Http\LocaleTreeRouteStack
	 */
	public function setMethod( $method )
	{
		$this->localeMethod = $method;
		return $this;
	}
	
	/**
	 * Get the locale detection method.
	 * 
	 * @return string
	 */
	public function getMethod()
	{
		return $this->localeMethod;
	}
	
	/**
	 * factory(): defined by RouteInterface interface.
	 *
	 * @see    \Zend\Mvc\Router\RouteInterface::factory()
	 * @param  array|Traversable $options
	 * @return SimpleRouteStack
	 * @throws Exception\InvalidArgumentException
	 */
	public static function factory($options = array())
	{
	    if ($options instanceof \Traversable) {
	        $options = ArrayUtils::iteratorToArray($options);
	    } elseif (!is_array($options)) {
	        throw new Exception\InvalidArgumentException(__METHOD__ . ' expects an array or Traversable set of options');
	    }
	
	    $instance = parent::factory($options);
	
	    if (isset($options['method'])) {
	        $instance->setMethod($options['method']);
	    }
	
	    return $instance;
	}
	
	/**
	 * match(): defined by \Zend\Mvc\Router\RouteInterface
	 *
	 * @see    \Zend\Mvc\Router\RouteInterface::match()
	 * @param  Request      $request
	 * @param  integer|null $pathOffset
	 * @param  array        $options
	 * @return RouteMatch|null
	 */
	public function match(Request $request, $pathOffset = null, array $options = array())
	{
	    if (!method_exists($request, 'getUri')) {
	        return null;
	    }
	
	    if ($this->baseUrl === null && method_exists($request, 'getBaseUrl')) {
	        $this->setBaseUrl($request->getBaseUrl());
	    }
	
	    $uri             = $request->getUri();
	    $baseUrlLength   = strlen($this->baseUrl) ?: null;
	
	    if ($pathOffset !== null) {
	        $baseUrlLength += $pathOffset;
	    }
	
	    // Path detection method could change the baseUrlLength
	    if (strcasecmp($this->localeMethod, 'path') === 0) {
	        $relativeUri = substr($uri->getPath(), $baseUrlLength);
	        if ($relativeUri[0] === '/') {
	            $relativeUri = explode('/', $relativeUri, 3);
	            $locale = $relativeUri[1];
 
	            if ($locale) {
	                // Check if it really is a locale
	                if ($this->localeManager->has($locale)) {
	                    $this->localeManager->setLocale($locale);
	                    $baseUrlLength += strlen($locale) + 1;
	                    
	                    if ($baseUrlLength >= strlen($uri->getPath()) ) {
	                    	// Make a permanent redirect with trailing slash
	                    	$uri = $uri . '/';
	                        header('Location: ' . $uri, true, 301);
	                        exit();
	                    }
	                }
	            }
	        }
	    }

	    // End path detection of locale
	
	    if ($this->requestUri === null) {
	        $this->setRequestUri($uri);
	    }
	
	    if ($baseUrlLength !== null) {
	        $pathLength = strlen($uri->getPath()) - $baseUrlLength;
	    } else {
	        $pathLength = null;
	    }
	
	    // TranslatorAwareTreeRouteStack
	    if ($this->hasTranslator() && $this->isTranslatorEnabled() && !isset($options['translator'])) {
	        $options['translator'] = $this->getTranslator();
	    }
	
	    if (!isset($options['text_domain'])) {
	        $options['text_domain'] = $this->getTranslatorTextDomain();
	    }
	    
	    foreach ($this->routes as $name => $route) {
	        if (
	        ($match = $route->match($request, $baseUrlLength, $options)) instanceof RouteMatch
	        && ($pathLength === null || $match->getLength() === $pathLength)
	        ) {
	            $match->setMatchedRouteName($name);
	
	            foreach ($this->defaultParams as $paramName => $value) {
	                if ($match->getParam($paramName) === null) {
	                    $match->setParam($paramName, $value);
	                }
	            }
	
	            return $match;
	        }
	    }
	
	    return null;
	}
	
	/**
	 * assemble(): defined by \Zend\Mvc\Router\RouteInterface interface.
	 *
	 * @see    \Zend\Mvc\Router\RouteInterface::assemble()
	 * @param  array $params
	 * @param  array $options
	 * @return mixed
	 * @throws Exception\InvalidArgumentException
	 * @throws Exception\RuntimeException
	 */
	public function assemble(array $params = array(), array $options = array())
	{
	    if ($this->hasTranslator() && $this->isTranslatorEnabled() && !isset($options['translator'])) {
	        $options['translator'] = $this->getTranslator();
	    }
	
	    if (!isset($options['text_domain'])) {
	        $options['text_domain'] = $this->getTranslatorTextDomain();
	    }
	     
	    if (!isset($options['locale'])) {
	        $options['locale'] = $this->localeManager->getLocale();
	    }
	
	    if (!isset($options['name'])) {
	        throw new \Zend\Mvc\Router\Exception\Exception\InvalidArgumentException('Missing "name" option');
	    }
	     
	    $names = explode('/', $options['name'], 2);
	    $route = $this->routes->get($names[0]);
	     
	    if (!$route) {
	        throw new \Zend\Mvc\Router\Exception\Exception\RuntimeException(sprintf('Route with name "%s" not found', $names[0]));
	    }
	     
	    if (isset($names[1])) {
	        if (!$route instanceof TreeRouteStack) {
	            //throw new Exception\RuntimeException(sprintf('Route with name "%s" does not have child routes', $names[0]));
	            throw new \Zend\Mvc\Router\Exception\RuntimeException(sprintf('Route with name "%s" does not have child routes', $names[0]));
	        }
	        $options['name'] = $names[1];
	    } else {
	        unset($options['name']);
	    }
	     
	    if (isset($options['only_return_path']) && $options['only_return_path']) {
	        return $this->baseUrl . $route->assemble(array_merge($this->defaultParams, $params), $options);
	    }
	     
	    if (!isset($options['uri'])) {
	        $uri = new HttpUri();
	         
	        if (isset($options['force_canonical']) && $options['force_canonical']) {
	            if ($this->requestUri === null) {
	                throw new \Zend\Mvc\Router\Exception\Exception\RuntimeException('Request URI has not been set');
	            }
	             
	            $uri->setScheme($this->requestUri->getScheme())
	            ->setHost($this->requestUri->getHost())
	            ->setPort($this->requestUri->getPort());
	        }
	         
	        $options['uri'] = $uri;
	    } else {
	        $uri = $options['uri'];
	    }
	     
	    if (strcasecmp($this->localeMethod, 'path') === 0) {
	        $locale         = str_replace('_', '-', $options['locale']);
	        $defaultLocale  = $this->localeManager->getDefaultLocale();
	        $defaultLocale  = str_replace('_', '-', $defaultLocale);
	
	        if (strcasecmp($locale, $defaultLocale) === 0) {
	            $path = $this->baseUrl . $route->assemble(array_merge($this->defaultParams, $params), $options);
	        } else {
	            $path = $this->baseUrl . '/' . $locale . $route->assemble(array_merge($this->defaultParams, $params), $options);
	        }
	    } else {
	        $path = $this->baseUrl . $route->assemble(array_merge($this->defaultParams, $params), $options);
	    }
	     
	    if (isset($options['query'])) {
	        $uri->setQuery($options['query']);
	    }
	     
	    if (isset($options['fragment'])) {
	        $uri->setFragment($options['fragment']);
	    }
	     
	    if ((isset($options['force_canonical']) && $options['force_canonical']) || $uri->getHost() !== null || $uri->getScheme() !== null) {
	        if (($uri->getHost() === null || $uri->getScheme() === null) && $this->requestUri === null) {
	            throw new \Zend\Mvc\Router\Exception\Exception\RuntimeException('Request URI has not been set');
	        }
	         
	        if ($uri->getHost() === null) {
	            $uri->setHost($this->requestUri->getHost());
	        }
	         
	        if ($uri->getScheme() === null) {
	            $uri->setScheme($this->requestUri->getScheme());
	        }
	         
	        return $uri->setPath($path)->normalize()->toString();
	    } elseif (!$uri->isAbsolute() && $uri->isValidRelative()) {
	        return $uri->setPath($path)->normalize()->toString();
	    }
	     
	    return $path;
	}
	
}