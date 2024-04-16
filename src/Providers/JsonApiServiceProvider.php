<?php

namespace JsonApi\JsonApi\Providers;

use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder;
use JsonApi\JsonApi\Mixins\JsonApiRequest;
use JsonApi\JsonApi\Mixins\JsonApiQueryBuilder;
use JsonApi\JsonApi\Mixins\JsonApiTestResponse;

/**
 * JsonApiServiceProvider es responsable de registrar los mixins para proporcionar funcionalidades específicas para la API JSON.
 */
class JsonApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        /** Cuando se necesite una instancia de la clase '\App\Exceptions\Handler'
         * en su lugar va usar una instancia de '\App\JsonApi\Exceptions\Handler'.
         * Con el keyword 'singleton' se esta estableciendo que solo se UNA sola
         * instancia y que se use a traves de toda la aplicacion
        */
        $this->app->singleton(
            \App\Exceptions\Handler::class,
            \JsonApi\JsonApi\Exceptions\Handler::class,
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \JsonApi\JsonApi\Commands\GenerateJsonApiRoutes::class,
            ]);
        }

        // Agrega el mixin JsonApiQueryBuilder a la clase Builder de Eloquent para aplicar funcionalidades de construcción de consultas JSON API.
        Builder::mixin(new JsonApiQueryBuilder);

        // Agrega el mixin JsonApiTestResponse a la clase TestResponse para aplicar funcionalidades de respuesta de prueba JSON API.
        TestResponse::mixin(new JsonApiTestResponse);

        // Agrega el mixin JsonApiRequest a la clase TestResponse para agregar funciones nuevas que permitan trabajar con el documento JSON:API
        // mas facilmente.
        Request::mixin(new JsonApiRequest);

    }
}
