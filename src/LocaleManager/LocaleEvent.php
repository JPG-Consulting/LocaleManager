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

use Zend\EventManager\Event;

class LocaleEvent extends Event
{
    /**
     * Module events triggered by eventmanager
     */
    const EVENT_LOCALE_CHANGE        = 'localeChange';
    
	/**
	 * Locale.
	 * 
	 * @var string
	 */
    protected $locale;
    
    /**
     * Default locale.
     * 
     * @var string
     */
    protected $default;
    
    /**
     * Get the current runtime locale.
     *
     * @return string
     */
    public function getLocale()
    {
    	return $this->locale;
    }
    
    /**
     * Set the current runtime locale.
     * 
     * @param string $locale
     * @return \LocaleManager\LocaleEvent
     */
    public function setLocale( $locale )
    {
    	$this->locale = $locale;
    	return $this;
    }
    
    /**
     * Get the default locale.
     * 
     * @return string
     */
    public function getDefaultLocale()
    {
    	return $this->default;
    }
    
    /**
     * Set the default locale.
     *
     * @param string $locale
     * @return \LocaleManager\LocaleEvent
     */
    public function setDefaultLocale( $locale )
    {
    	$this->default = $locale;
    	return $this;
    }
}