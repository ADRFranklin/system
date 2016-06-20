<?php
/**
 * DatabaseServiceProvider - Implements a Service Provider for Database.
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 */

namespace Nova\Database;

use Nova\Database\DatabaseManager;
use Nova\Database\ORM\Model;
use Nova\Database\Model as SimpleModel;
use Nova\Support\ServiceProvider;


class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the Application events.
     *
     * @return void
     */
    public function boot()
    {
        // Setup the (simple) Model.
        SimpleModel::setConnectionResolver($this->app['db']);

        // Setup the ORM Model.
        Model::setConnectionResolver($this->app['db']);

        Model::setEventDispatcher($this->app['events']);
    }

    /**
     * Register the Service Provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bindShared('db', function($app)
        {
            return new DatabaseManager($app);
        });
    }

}
