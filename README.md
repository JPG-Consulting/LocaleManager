LocaleManager
=============

A locale manager for Zend Framework 2 which allows the creation, management and maintenance of multilingual sites.

## Locale Manager Configuration ##


We should first add the service factory to our service manager. This can be done by editing our configuration file and adding the service as follows:

    'service_manager' => array(
        ...
        factories' => array(
            'LocaleManager' => 'LocaleManager\Service\LocaleManagerFactory', 
            ...
        ),
        ...
    ),

### Basic Setup ###
    
    'locale_manager' => array(
        'locales' => array('en-US', 'es-ES'),
        'default_locale' => 'en-US',
    )

In the example above we are telling the locale manager we've got two locales 'en-US' and 'es-ES' and we set the default locale to 'en-US'.

## Router ##

The router allows for translatable routes and also detects the locale in the URL path. If an available locale is detected it sets the locale in the locale manager. The locale manager will set the default translators and trigger an event so you may set the custom translators according to the current locale.

### Router Configuration ###
    'router' => array(
        'router_class' => 'LocaleManager\Router\Http\LocaleTreeRouteStack',
        ...
    ),
