<?php

namespace Nova\Support\Contracts;


interface RenderableInterface
{
    /**
     * Get the evaluated contents of the object.
     *
     * @return string
     */
    public function fetch();

    /**
     * Show the evaluated contents of the object.
     *
     * @return string
     */
    public function render();
}
