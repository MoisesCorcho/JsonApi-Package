<?php

namespace JsonApi\JsonApi\Exceptions;

use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException as BaseHttpException;

/**
 * Esta clase sirve para centralizar la captura de las excepciones HTTP
 * y de esta manera, en el caso que se quiera sobreescribir un detalle
 * de error, se puede crear un nuevo metodo para ello.
 *
 * Ej. get404Detail o get403Detail
 */
class HttpException extends BaseHttpException
{
    public function __construct(BaseHttpException $e)
    {
        parent::__construct($e->getStatusCode(), $e->getMessage());
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request): JsonResponse
    {
        /**
         * En la clase 'Illuminate\Http\Response' que viene con Laravel, se puede ver que
         * extiende de 'SymfonyResponse' (Symfony\Component\HttpFoundation\Response), dentro
         * de esta, se puede ver que hay constantes asignadas a los numeros de error HTTP, y
         * mas abajo se pueden ver los '$statusText' que es un arreglo con los numeros de las
         * excepciones HTTP asociados a sus respectivos nombres.
         */
        $title = Response::$statusTexts[$this->getStatusCode()];

        /**
         * Codigo de estado, es obtenido de esta misma clase, gracias a que llamamos al
         * constructor padre dentro del constructor mandandole el mensaje y codigo de
         * error que recibimos de la excepcion capturada.
         */
        $statusCode = $this->getStatusCode();

        /**
         * Detalle de error, es obtenido de esta misma clase, gracias a que llamamos al
         * constructor padre dentro del constructor mandandole el mensaje y codigo de
         * error que recibimos de la excepcion capturada.
         */
        $detail = $this->getMessage();

        /**
         * El metodo con el codigo HTTP a buscar. se hace de esta manera para que
         * sea dinamico.
         */
        $method = "get{$statusCode}Detail";

        /**
         * Se verifica si dicho metodo existe dentro de esta clase, en caso de que
         * asi sea, se llama para sobreescribir el detalle del error con su contenido.
         */
        if (method_exists($this, $method)) {
            $detail = $this->$method();
        }

        return response()->json([
            'errors' => [
                [
                    'title' => $title,
                    'detail' => $detail,
                    'status' => (string) $this->getStatusCode()
                ]
            ]
        ], $this->getStatusCode());
    }

    /**
     * Se obtiene el detalle para los errores 404.
     *
     * @return string
     */
    protected function get404Detail(): string
    {
        if ( str($this->getMessage())->startsWith('No query results for model') ) {
            return "No records found with that id.";
        }

        return $this->getMessage();
    }
}
