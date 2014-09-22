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

use Zend\Mvc\Router\Http\TreeRouteStack;
use Zend\I18n\Translator\TranslatorAwareInterface;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\Stdlib\RequestInterface as Request;
use Zend\Uri\Http as HttpUri;
use LocaleManager\Router\Exception;
use LocaleManager\LocaleManagerAwareInterface;
use LocaleManager\LocaleManager;
use Zend\Mvc\Router\Http\RouteMatch;

class LocaleTreeRouteStack extends TreeRouteStack implements 
    LocaleManagerAwareInterface, 
    TranslatorAwareInterface
{
    
    /**
     * Translator used for translatable segments.
     * 
     * @var TranslatorInterface
     */
    protected $translator = null;
    
    /**
     * Translator text domain to use.
     *
     * @var string
     */
    protected $textDomain = 'default';
    
    /**
     * Whether the translator is enabled.
     *
     * @var bool
     */
    protected $translatorEnabled = true;
    
    /**
     * Detection method for locales.
     * 
     * @var string
     */
    protected $detectionMethod = 'path';
    
    /**
     * 
     * @var LocaleManager
     */
    protected $localeManager = null;
    
    /**
     * Sets translator to use in helper
     *
     * @param  TranslatorInterface $translator  [optional] translator.
     *                                           Default is null, which sets no translator.
     * @param  string              $textDomain  [optional] text domain
     *                                           Default is null, which skips setTranslatorTextDomain
     * @return TranslatorAwareInterface
     */
    public function setTranslator(TranslatorInterface $translator = null, $textDomain = null)
    {
        $this->translator = $translator;
        
        if ($textDomain !== null) {
            $this->setTranslatorTextDomain( $textDomain );
        }    	
    }
    
    /**
     * Returns translator used in object
     *
     * @return TranslatorInterface|null
     */
    public function getTranslator() 
    {
    	return $this->translator;
    }
    
    /**
     * Checks if the object has a translator
     *
     * @return bool
    */
    public function hasTranslator()
    {
        return $this->translator !== null;
    }
    
    /**
     * Sets whether translator is enabled and should be used
     *
     * @param  bool $enabled [optional] whether translator should be used.
     *                       Default is true.
     * @return TranslatorAwareInterface
    */
    public function setTranslatorEnabled($enabled = true)
    {
        if (!is_bool($enabled)) {
            throw new Exception\InvalidArgumentException(sprintf(
                    '%s expects a bool as an argument; %s provided'
                    ,__METHOD__, gettype($enabled)
            ));
        }
        
    	$this->translatorEnabled = $enabled;
    	return $this;
    }
    
    /**
     * Returns whether translator is enabled and should be used
     *
     * @return bool
    */
    public function isTranslatorEnabled()
    {
        if ($this->translator === null) {
        	return false;
        }
        
    	return $this->translatorEnabled;
    }
    
    /**
     * Set translation text domain
     *
     * @param  string $textDomain
     * @return TranslatorAwareInterface
    */
    public function setTranslatorTextDomain($textDomain = 'default')
    {
        if (!is_string($textDomain)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects a string as an argument; %s provided'
                ,__METHOD__, gettype($textDomain)
            ));
        }
        
        $this->textDomain = $textDomain;
        
        return $this;
    }
    
    /**
     * Return the translation text domain
     *
     * @return string
    */
    public function getTranslatorTextDomain()
    {
    	return $this->textDomain;
    }
    
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
     * Get the base URL.
     *
     * @return string
     */
    public function getBaseUrlWithLocale( $locale = null)
    {
        // Inject the locale into the base url if necesary
        if ((strcasecmp($this->detectionMethod, 'path') === 0 ) && ($this->localeManager !== null) ) {
            if ($locale === null) {
                $locale = $this->localeManager->getLocale();
            }
            $locale = str_replace('_', '-', $locale);
            
            $default = str_replace('_', '-', $this->localeManager->getDefaultLocale());
            
            if ((!empty($locale)) && (strcasecmp($locale, $default) !== 0)) {
                return $this->baseUrl . '/' . str_replace('_', '-', $locale);
            }	
        }
        
        return $this->baseUrl;
    }
    
    
    protected function setLocale( $locale )
    {
        // Make the locale ZF2 compatible
        $locale = str_replace('-', '_', $locale);
        
        // Set the locale in the route translator
        if ($this->hasTranslator() && $this->isTranslatorEnabled()) {
            $this->getTranslator()->setLocale( $locale );
        }
        
        // Set the locale in the locale manager
        $this->localeManager->setLocale( $locale );	
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
        
        // Translator options
        if ($this->hasTranslator() && $this->isTranslatorEnabled()) {
            $options['translator']  = isset($options['translator']) ? $options['translator'] : $this->getTranslator();
            $options['text_domain'] = isset($options['text_domain']) ? $options['text_domain'] : $this->getTranslatorTextDomain();
        }
    
        if ($this->baseUrl === null && method_exists($request, 'getBaseUrl')) {
            $this->setBaseUrl($request->getBaseUrl());
        }
        
        $uri           = $request->getUri();
        $baseUrlLength = strlen($this->baseUrl) ?: null;
        
        // Start locale detection
        if ((strcasecmp($this->detectionMethod, 'path') === 0 ) && ($pathOffset === null)) {
            $relativePath = substr($uri->getPath(), strlen($this->baseUrl));
            $relativePath = ltrim($relativePath, '/');
            if (!empty($relativePath)) {
                $relativePath = explode('/', $relativePath, 2);
                
                $locale = $relativePath[0];
                if ( $this->localeManager->has( $locale ) ) {
                    if (!isset($relativePath[1])) {
                        // Permanent redirect
                        header('Location: ' . $uri . '/', true, 301);
                        exit();
                    }
                    
                    if ($this->hasTranslator() && $this->isTranslatorEnabled() && !isset($options['locale'])) {
                        $options['locale'] = str_replace('-', '_', $locale);
                        $this->setLocale( $locale );
                    }
                    
                    $pathOffset = strlen($locale) + 1;
                }
            }
            
            if ($this->hasTranslator() && $this->isTranslatorEnabled() && !isset($options['locale'])) {
                $options['locale'] = str_replace('-', '_', $this->localeManager->getDefaultLocale());
                $this->setLocale( $options['locale'] );
            }
        }
        // End locale detection
        
        if ($pathOffset !== null) {
            $baseUrlLength += $pathOffset;
        }
        
        if ($this->requestUri === null) {
            $this->setRequestUri($uri);
        }
        
        if ($baseUrlLength !== null) {
            $pathLength = strlen($uri->getPath()) - $baseUrlLength;
        } else {
            $pathLength = null;
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
        if (!isset($options['name'])) {
            throw new Exception\InvalidArgumentException('Missing "name" option');
        }
        
        $names = explode('/', $options['name'], 2);
        $route = $this->routes->get($names[0]);
        
        if (!$route) {
            throw new Exception\RuntimeException(sprintf('Route with name "%s" not found', $names[0]));
        }
        
        if (isset($names[1])) {
            if (!$route instanceof TreeRouteStack) {
                //die("DEBUG: route is " . gettype($route));
                throw new Exception\RuntimeException(sprintf('Route with name "%s" does not have child routes', $names[0]));
            }
            $options['name'] = $names[1];
        } else {
            unset($options['name']);
        }
        
        // Translator options
        if ($this->hasTranslator() && $this->isTranslatorEnabled()) {
            $options['translator']  = isset($options['translator']) ? $options['translator'] : $this->getTranslator();
            $options['text_domain'] = isset($options['text_domain']) ? $options['text_domain'] : $this->getTranslatorTextDomain();
            $options['locale']      = isset($options['locale']) ? $options['locale'] : str_replace('-', '_', $this->localeManager->getLocale() );
        }
        
        if (isset($options['only_return_path']) && $options['only_return_path']) {
            return $this->getBaseUrlWithLocale( $options['locale'] ) . $route->assemble(array_merge($this->defaultParams, $params), $options);
        }

        if (!isset($options['uri'])) {
            $uri = new HttpUri();

            if (isset($options['force_canonical']) && $options['force_canonical']) {
                if ($this->requestUri === null) {
                    throw new Exception\RuntimeException('Request URI has not been set');
                }

                $uri->setScheme($this->requestUri->getScheme())
                    ->setHost($this->requestUri->getHost())
                    ->setPort($this->requestUri->getPort());
            }

            $options['uri'] = $uri;
        } else {
            $uri = $options['uri'];
        }

        $path = $this->getBaseUrlWithLocale( $options['locale'] ) . $route->assemble(array_merge($this->defaultParams, $params), $options);

        if (isset($options['query'])) {
            $uri->setQuery($options['query']);
        }

        if (isset($options['fragment'])) {
            $uri->setFragment($options['fragment']);
        }

        if ((isset($options['force_canonical']) && $options['force_canonical']) || $uri->getHost() !== null || $uri->getScheme() !== null) {
            if (($uri->getHost() === null || $uri->getScheme() === null) && $this->requestUri === null) {
                throw new Exception\RuntimeException('Request URI has not been set');
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