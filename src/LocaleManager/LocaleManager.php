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

use LocaleManager\Exception;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\I18n\Translator\TranslatorAwareInterface;

class LocaleManager implements ServiceManagerAwareInterface
{
    /**
     * The current runtime locale.
     * 
     * @var string
     */
    protected $locale;
	
    /**
     * The default locale.
     * 
     * @var string
     */
    protected $default;
    
    /**
     * Service manager.
     * 
     * @var ServiceManager
     */
    protected $serviceManager;
    
    /**
     * Stored options.
     * 
     * @var array
     */
    protected $options;
    
    /**
     * The adapter to access locales.
     * 
     * @var unknown
     */
    protected $adapter;
    
    /**
     * Constructor.
     * 
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this->options = $options;
    }
    
    protected function initAdapter()
    {
        
        if (!$this->adapter) {
            // Get the configuration options for the adapter
        	if (isset($this->options['storage'])) {
        	    $config = $this->options['storage'];
        	    // Free some memory as we no longer need this
        		unset($this->options['storage']);
        	} else {
        		$config = array();
        	}
        	
        	$config['locales'] = isset($this->options['locales']) ? $this->options['locales'] : array(); 
        	
        	$adapterClass = isset($config['type']) ? $config['type'] : '\LocaleManager\Storage\DefaultStorage';
        	if (!class_exists($adapterClass)) {
        	    switch ( strtolower($adapterClass) )
        	    {
        	    	//case 'db':
        	    	//    $adapterClass = '\LocaleManager\Storage\DbStorage';
        	    	//    break;
        	    	case 'default':
        	    	    $adapterClass = '\LocaleManager\Storage\DefaultStorage';
        	    	    break;
        	    	//case 'doctrine':
        	    	//    $adapterClass = '\LocaleManager\Storage\DoctrineStorage';
        	    	//    break;
        	    	default:
        	    	    // TODO: Throw exception
        	    }
        	}
        	
        	// Obtain an instance
        	$factory = sprintf('%s::factory', $adapterClass);
        	$this->adapter = call_user_func($factory, $config);        	
        }     	
    }
    
    /**
     * Set service manager
     *
     * @param ServiceManager $serviceManager
     */
    public function setServiceManager(ServiceManager $serviceManager)
    {
    	$this->serviceManager = $serviceManager;
    	return $this;
    }
    
    /**
     * Get the current runtime locale.
     * 
     * @return string
     */
    public function getLocale()
    {
        if (!$this->locale) {
            // Try to fetch the locale from Zend's translator
            if ($this->serviceManager->has('MvcTranslator')) {
            	$this->setLocale( $this->serviceManager->get('MvcTranslator')->getLocale() );
            } elseif ($this->serviceManager->has('Zend\I18n\Translator\TranslatorInterface')) {
            	$this->setLocale( $this->serviceManager->get('Zend\I18n\Translator\TranslatorInterface')->getLocale() );
            } elseif ($this->serviceManager->has('Translator')) {
            	$this->setLocale( $this->serviceManager->get('Translator')->getLocale() );
            } else {
                $this->setLocale( $this->getDefaultLocale() );
            }
        }
        return $this->locale;
    }
    
    /**
     * Set the current runtime locale.
     * 
     * @param string $locale
     * @return \LocaleManager\LocaleManager
     */
    public function setLocale( $locale )
    {
        // ISO locale format
        $this->locale = str_replace('_', '-', $locale);
        
        // ZF2 styled locale
        $locale = str_replace('-', '_', $locale);
        
        // Set locale on known translators
        if ($this->serviceManager->has('MvcTranslator')) {
            $this->serviceManager->get('MvcTranslator')->setLocale( $locale );
        }
        if ($this->serviceManager->has('Zend\I18n\Translator\TranslatorInterface')) {
            $this->serviceManager->get('Zend\I18n\Translator\TranslatorInterface')->setLocale( $locale );
        } 
        if ($this->serviceManager->has('Translator')) {
            $this->serviceManager->get('Translator')->setLocale( $locale );
        }
        
        $router = $this->serviceManager->get('Router');
        if ($router instanceof TranslatorAwareInterface) {
        	$router->getTranslator()->setLocale( $locale );
        }
            
        
        // TODO: Trigger an event to alert of the locale change.
        
        return $this;
    }
    
    /**
     * Get the default locale.
     * 
     * @param string $locale
     */
    public function getDefaultLocale()
    {
        if (!$this->default) {
            // Try to fetch the locale from Zend's translator
            if ($this->serviceManager->has('MvcTranslator')) {
            	$this->setDefaultLocale( $this->serviceManager->get('MvcTranslator')->getFallbackLocale() ); 
            } elseif ($this->serviceManager->has('Zend\I18n\Translator\TranslatorInterface')) {
            	$this->setDefaultLocale( $this->serviceManager->get('Zend\I18n\Translator\TranslatorInterface')->getFallbackLocale() );
            } elseif ($this->serviceManager->has('Translator')) {
            	$this->setDefaultLocale( $this->serviceManager->get('Translator')->getFallbackLocale() );
            } 

            if (!$this->default) {
                if (!extension_loaded('intl')) {
                    throw new Exception\ExtensionNotLoadedException(sprintf(
                        '%s component requires the intl PHP extension',
                        __NAMESPACE__
                    ));
                }
                
                $this->setDefaultLocale( \Locale::getDefault() );
            }
            
            
        }
        return $this->default;	
    }
	
    /**
     * Set the default locale.
     * 
     * @param string $locale
     * @return \LocaleManager\LocaleManager
     */
    public function setDefaultLocale( $locale )
    {
        $this->default = str_replace('_', '-', $locale);
        return $this;
    }
    
    /**
     * Check if the locale is available.
     * 
     * @param string $locale
     */
    public function has( $locale )
    {
    	$this->initAdapter();
    	
    	// Normalize locale
    	$locale = str_replace('_', '-', $locale);
    	
    	if ($this->adapter->has( $locale )) {
    	    return true;
    	}
    	
    	// Default locale is always allowed
    	if (strcasecmp($locale, $this->getDefaultLocale()) === 0) {
    		return true;
    	}
    	
    	return false;
    }
}