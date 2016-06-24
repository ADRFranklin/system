<?php

namespace Nova\Language;

use Nova\Foundation\Application;
use Nova\Language\Language;


class LanguageManager
{
    /**
     * The Application instance.
     *
     * @var \Foundation\Application
     */
    protected $app;

    /**
     * The default locale being used by the translator.
     *
     * @var string
     */
    protected $locale;

    /**
     * The active Language instances.
     *
     * @var array
     */
    protected $instances = array();

    /**
     * Create new Language Manager instance.
     *
     * @param  \core\Application  $app
     * @return void
     */
    function __construct(Application $app, $locale)
    {
        $this->app = $app;

        $this->locale = $locale;
    }

    /**
     * Get instance of Language with domain and code (optional).
     * @param string $domain Optional custom domain
     * @param string $code Optional custom language code.
     * @return Language
     */
    public function instance($domain = 'app', $code = null)
    {
        $code = $code ?: $this->locale;

        $code = $this->getCurrentLanguage($code);

        // The ID code is something like: 'en/system', 'en/app' or 'en/file_manager'
        $id = $code .'/' .$domain;

        // Initialize the domain instance, if not already exists.
        if (! isset($this->instances[$id])) {
            $languages = $this->app['config']['languages'];

            $this->instances[$id] = new Language($languages, $domain, $code);
        }

        return $this->instances[$id];
    }

    /**
     * Get current Language
     * @return string
     */
    protected function getCurrentLanguage($code)
    {
        $locale = $this->app['config']['app.locale'];

        // Check if the end-user do not ask for a custom code.
        if ($code == $locale) {
            $session = $this->app['session.store'];

            return $session->get('language', $code);
        }

        return $code;
    }

    /**
     * Get the default locale being used.
     *
     * @return string
     */
    public function locale()
    {
        return $this->getLocale();
    }

    /**
     * Get the default locale being used.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set the default locale.
     *
     * @param  string  $locale
     * @return void
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Dynamically pass methods to the default instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array(array($this->instance(), $method), $parameters);
    }

}
