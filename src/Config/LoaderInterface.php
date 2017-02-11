<?php
/**
 * Loader Interface
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 * @date April 12th, 2016
 */

namespace Nova\Config;


interface LoaderInterface
{
    /**
     * Load the given configuration group.
     *
     * @param  string  $environment
     * @param  string  $group
     * @return array
     */
    public function load($environment, $group);
}
