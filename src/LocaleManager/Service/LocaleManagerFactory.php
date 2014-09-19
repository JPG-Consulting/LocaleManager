<?php
namespace LocaleManager\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use LocaleManager\LocaleManager;
use LocaleManager\LocaleManagerAwareInterface;
use Zend\I18n\Translator\TranslatorAwareInterface;
use Zend\Config\Processor\Translator;
use LocaleManager\Listener\LocaleManagerListener;

class LocaleManagerFactory implements FactoryInterface
{
	
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->has('Config') ? $serviceLocator->get('Config') : array();
        $config = isset($config['locale_manager']) ? $config['locale_manager'] : array();
        
        $localeManager = new LocaleManager( $config );
        $localeManager->setServiceManager( $serviceLocator );
        
        if ($serviceLocator->has('Application')) {
            $serviceLocator->get('Application')->getEventManager()->attach( new LocaleManagerListener() );
        }
        
        // --- Start Router Config ---
        $router = $serviceLocator->get('Router');
        if ($router instanceof LocaleManagerAwareInterface) {
            $router->setLocaleManager($localeManager);
             
            // TODO: Set the detection method for the router
        }
        
        if ($router instanceof TranslatorAwareInterface) {
            // Try setting a translator
            if (!$router->hasTranslator()) {
                // Inject the translator
                if ($serviceLocator->has('MvcTranslator')) {
                    $router->setTranslator( $serviceLocator->get('MvcTranslator') );
                } elseif ($serviceLocator->has('Zend\I18n\Translator\TranslatorInterface')) {
                    $router->setTranslator( $serviceLocator->get('Zend\I18n\Translator\TranslatorInterface') );
                } elseif ($serviceLocator->has('Translator')) {
                    $router->setTranslator( $serviceLocator->get('Translator') );
                } else if (!extension_loaded('intl')) {
                    $router->setTranslator( new MvcTranslator(new DummyTranslator()) );
                } else {
                    // For BC purposes (pre-2.3.0), use the I18n Translator
                    $router->setTranslator( new MvcTranslator(new Translator()) );
                }
            }
        
            // Set the router text domain
            $router->setTranslatorTextDomain('router');
        }
        
        // --- End Router Config ---
        
        // Return the instance
        return $localeManager;
    }
    
}