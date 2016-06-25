<?php
/**
 * Translator - Class to handle a Laravel-esque style Translations.
 *
 * NOTE: The real translation are made via the new Language API.
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 */

namespace Nova\Validation;


class Translator
{
    /**
     * The Language lines used by the Translator.
     *
     * @var array
     */
    protected $messages = array();

    /**
     * Create a new Translator instance.
     */
    public function __construct()
    {
        $lines = require __DIR__ .DS .'messages.php';

        $this->messages = array('validation' => $lines);
    }

    /**
     * Get the translation for a given key.
     *
     * @param  string $id
     * @param  array  $params
     * @param  string $domain
     * @param  string $locale
     * @return string
     */
    public function trans($id, array $params = array(), $domain = 'messages', $locale = null)
    {
        $line = array_get($this->messages, $id);

        if (! is_null($line)) {
            return $this->makeReplacements($line, $params);
        }

        return $id;
    }

    /**
     * Make the place-holder replacements on a line.
     *
     * @param  string $line
     * @param  array  $replaces
     * @return string
     */
    protected function makeReplacements($line, array $replaces)
    {
        foreach ($replaces as $key => $value) {
            $line = str_replace(':' .$key, $value, $line);
        }

        return $line;
    }
}
