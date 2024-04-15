<?php

namespace JsonApi\JsonApi\Exceptions;

use Illuminate\Http\Request;

use Illuminate\Http\JsonResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use App\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JsonApi\JsonApi\Http\Responses\JsonApiValidationErrorResponse;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->renderable(function (HttpException $e, Request $request) {
            // En caso de que la peticion CONTENGA los headers JSON:API, se ejecutará el codigo de la derecha
            // Es decir, que se mostrará el error en formato JSON:API
            $request->isJsonApi() && throw new \JsonApi\JsonApi\Exceptions\HttpException($e);
        });

        $this->renderable(function (AuthenticationException $e, Request $request) {
            // En caso de que la peticion CONTENGA los headers JSON:API, se ejecutará el codigo de la derecha
            // Es decir, que se mostrará el error en formato JSON:API
            $request->isJsonApi() && throw new \JsonApi\JsonApi\Exceptions\AuthenticationException();
        });

        // Se llama al metodo register de la clase padre despues de haber capturado las excepciones.
        parent::register();
    }

    /**
     * Se sobreescribe esta funcion que es la que se ejecuta cuando
     * ocurren errores de validacion. Se sobreescribe para hacer que
     * los errores que salgan tengan la estructura necesaria en la
     * especificaion JSON:API.
     *
     * En resumen, se interceptan los mensajes de error de las
     * validaciones.
     *
     * @param [type] $request
     * @param ValidationException $exception
     * @return JsonApiValidationErrorResponse
     */
    protected function invalidJson($request, ValidationException $exception): JsonResponse
    {
        /** No se quiere formatear las respuestas de error para
         * autenticacion, con lo cual, cuando se estén enviando
         * los headers referentes a JSON:API se formatea con
         * especificacion JSON:API, en caso contrario, se retorna
         * la respuesta por defecto de Laravel.
         */
        if ( $request->isJsonApi() ) {
            return new JsonApiValidationErrorResponse($exception);
        }

        return parent::invalidJson($request, $exception);
    }
}
