<?php

namespace Nova\Template;

use Nova\Config\Config;
use Nova\Language\LanguageManager;
use Nova\Support\Contracts\ArrayableInterface as Arrayable;
use Nova\Template\Template;
use Nova\View\Factory as ViewFactory;
use Nova\View\ViewFinderInterface;
use Nova\View\View;


class Factory
{
    /**
     * The View Factory instance.
     *
     * @var \Nova\View\Factory
     */
    protected $factory;

    /**
     * The view finder implementation.
     *
     * @var \Nova\View\ViewFinderInterface
     */
    protected $finder;

    /**
     * The Language Manager instance.
     *
     * @var \Nova\Language\LanguageManager
     */
    protected $languages;


    /**
     * Create new Template Factory instance.
     *
     * @param $factory The View Factory instance.
     * @return void
     */
    function __construct(ViewFactory $factory, ViewFinderInterface $finder, LanguageManager $languages)
    {
        $this->factory = $factory;

        $this->finder = $finder;

        $this->languages = $languages;
    }

    /**
     * Create a View instance
     *
     * @param string $view
     * @param array|string $data
     * @param string $custom
     * @return \Nova\View\View
     */
    public function make($view, $data = array(), $template = null)
    {
        if (is_string($data)) {
            if (! empty($data) && ($template === null)) {
                // The Module name given as second parameter; adjust the information.
                $template = $data;
            }

            $data = array();
        }

        // Get the View file path.
        $path = $this->find($view, $template);

        // Get the parsed data.
        $data = $this->parseData($data);

        return new Template($this->factory, $view, $path, $data);
    }

    /**
     * Check if the view file exists.
     *
     * @param    string     $view
     * @return    bool
     */
    public function exists($view, $template = null)
    {
        try {
            $this->find($view, $template);
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        return true;
    }

    /**
     * Parse the given data into a raw array.
     *
     * @param  mixed  $data
     * @return array
     */
    protected function parseData($data)
    {
        return ($data instanceof Arrayable) ? $data->toArray() : $data;
    }

    /**
     * Find the View file.
     *
     * @param    string     $view
     * @param    string     $template
     * @return    string
     */
    protected function find($view, $template = null)
    {
        // Calculate the current Template name.
        $template = $template ?: Config::get('app.template');

        // Calculate the search path.
        $path = sprintf('Templates/%s/%s', $template, $view);

        // Make the path absolute and adjust the directory separator.
        $path = str_replace('/', DS, APPDIR .$path);

        // Find the View file depending on the Language direction.
        $language = $this->getLanguage();

        if ($language->direction() == 'rtl') {
            // Search for the View file used on the RTL languages.
            $filePath = $this->finder->find($path .'-rtl');
        } else {
            $filePath = null;
        }

        if (is_null($filePath)) {
            $filePath = $this->finder->find($path);
        }

        if (! is_null($filePath)) return $filePath;

        throw new \InvalidArgumentException("Unable to load the view '" .$view ."' on template '" .$template ."'.", 1);
    }

    /**
     * Return the current Language instance.
     *
     * @return \Nova\Language\Language
     */
    protected function getLanguage()
    {
        return $this->languages->instance();
    }

}
