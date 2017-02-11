<?php

namespace Nova\Foundation;

use Nova\Filesystem\Filesystem;

class ProviderRepository
{
    /**
     * The filesystem instance.
     *
     * @var \Nova\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The path to the manifest.
     *
     * @var string
     */
    protected $manifestPath;


    /**
     * Create a new service repository instance.
     *
     * @param  \Nova\Filesystem\Filesystem  $files
     * @param  string  $manifestPath
     * @return void
     */
    public function __construct(Filesystem $files, $manifestPath)
    {
        $this->files = $files;

        $this->manifestPath = $manifestPath .DS .'services.php';
    }

    /**
     * Register the application service providers.
     *
     * @param  \Nova\Foundation\Application  $app
     * @param  array  $providers
     * @return void
     */
    public function load(Application $app, array $providers)
    {
        $manifest = $this->loadManifest();

        // First we will load the service manifest, which contains information on all
        // service providers registered with the application and which services it
        // provides. This is used to know which services are "deferred" loaders.
        if ($this->shouldRecompile($manifest, $providers)) {
            $manifest = $this->compileManifest($app, $providers);
        }

        // If the application is running in the console, we will not lazy load any of
        // the service providers. This is mainly because it's not as necessary for
        // performance and also so any provided Artisan commands get registered.
        if ($app->runningInConsole()) {
            $manifest['eager'] = $manifest['providers'];
        }

        // Next, we will register events to load the providers for each of the events
        // that it has requested. This allows the service provider to defer itself
        // while still getting automatically loaded when a certain event occurs.
        foreach ($manifest['when'] as $provider => $events) {
            $this->registerLoadEvents($app, $provider, $events);
        }

        // We will go ahead and register all of the eagerly loaded providers with the
        // application so their services can be registered with the application as
        // a provided service. Then we will set the deferred service list on it.
        foreach ($manifest['eager'] as $provider) {
            $app->register($this->createProvider($app, $provider));
        }

        $app->setDeferredServices($manifest['deferred']);
    }

    /**
     * Register the load events for the given provider.
     *
     * @param  \Nova\Foundation\Application  $app
     * @param  string  $provider
     * @param  array  $events
     * @return void
     */
    protected function registerLoadEvents(Application $app, $provider, array $events)
    {
        if (count($events) < 1) return;

        $app->make('events')->listen($events, function() use ($app, $provider)
        {
            $app->register($provider);
        });
    }

    /**
     * Compile the application manifest file.
     *
     * @param  \Nova\Foundation\Application  $app
     * @param  array  $providers
     * @return array
     */
    protected function compileManifest(Application $app, $providers)
    {
        // The service manifest should contain a list of all of the providers for
        // the application so we can compare it on each request to the service
        // and determine if the manifest should be recompiled or is current.
        $manifest = $this->freshManifest($providers);

        foreach ($providers as $provider)
        {
            $instance = $this->createProvider($app, $provider);

            // When recompiling the service manifest, we will spin through each of the
            // providers and check if it's a deferred provider or not. If so we'll
            // add it's provided services to the manifest and note the provider.
            if ($instance->isDeferred()) {
                foreach ($instance->provides() as $service) {
                    $manifest['deferred'][$service] = $provider;
                }

                $manifest['when'][$provider] = $instance->when();
            }

            // If the service providers are not deferred, we will simply add it to an
            // of eagerly loaded providers that will be registered with the app on
            // each request to the applications instead of being lazy loaded in.
            else {
                $manifest['eager'][] = $provider;
            }
        }

        return $this->writeManifest($manifest);
    }

    /**
     * Create a new provider instance.
     *
     * @param  \Nova\Foundation\Application  $app
     * @param  string  $provider
     * @return \Nova\Support\ServiceProvider
     */
    public function createProvider(Application $app, $provider)
    {
        return new $provider($app);
    }

    /**
     * Determine if the manifest should be compiled.
     *
     * @param  array  $manifest
     * @param  array  $providers
     * @return bool
     */
    public function shouldRecompile($manifest, $providers)
    {
        return is_null($manifest) || ($manifest['providers'] != $providers);
    }

    /**
     * Load the service provider manifest PHP file.
     *
     * @return array
     */
    public function loadManifest()
    {
        // The service manifest is a file containing a representation of every
        // service provided by the application and whether its provider is using
        // deferred loading or should be eagerly loaded on each request to us.
        if ($this->files->exists($this->manifestPath)) {
            $manifest = $this->files->getRequire($this->manifestPath);

            return array_merge(array('when' => array()), $manifest);
        }
    }

    /**
     * Write the service manifest file to disk.
     *
     * @param  array  $manifest
     * @return array
     */
    public function writeManifest($manifest)
    {
        $content = "<?php\n\nreturn " .var_export($manifest, true) .";\n";

        $this->files->put($this->manifestPath, $content);

        return array_merge(array('when' => array()), $manifest);
    }

    /**
     * Create a fresh manifest array.
     *
     * @param  array  $providers
     * @return array
     */
    protected function freshManifest(array $providers)
    {
        list($eager, $deferred) = array(array(), array());

        return compact('providers', 'eager', 'deferred');
    }

    /**
     * Get the filesystem instance.
     *
     * @return \Filesystem\Filesystem
     */
    public function getFilesystem()
    {
        return $this->files;
    }

}
