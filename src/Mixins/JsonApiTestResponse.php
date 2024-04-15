<?php

namespace JsonApi\JsonApi\Mixins;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Assert as PHPUnit;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Clase llamada a traves del metodo mixin de TestResponse
 * cada una de estas funciones es un Macro, la cual es una
 * funcion extendida de la propia clase TestResponse
 * es decir, que se puede usar como una funcion mas de la
 * clase
 */
class JsonApiTestResponse
{
    /**
     * Macro para verificar si se devuelven errores HTTP en formato JSON:API.
     *
     * Todos los parametros son opcionales, para esto, al momento de usar esta
     * funcion, se deben mandar 'named arguments' en lugar de 'positional arguments'.
     *
     * @param string $title // Titulo del error
     * @param string $detail // detalle del error
     * @param string $status // estado del error
     *
     * @return Closure
     */
    public function assertJsonApiError(): Closure
    {
        return function($title = null, $detail = null, $status = null) {
            /** @var TestResponse $this */

            try {

                $this->assertJsonStructure([
                    'errors' => [
                        '*' => ['title', 'detail']
                    ]
                ]);
            } catch (ExpectationFailedException $th) {
                PHPUnit::fail(
                    "Error objects MUST be returned as an array keyed by errors in the top level of a JSON:API document."
                    .PHP_EOL.PHP_EOL.
                    $th->getMessage()
                );
            }

            // Se valida que el codigo de la izquierda no sea null, en caso de que no lo sea, se ejecuta el codigo de la derecha.
            $title  && $this->assertJsonFragment(['title' => $title]);
            $detail && $this->assertJsonFragment(['detail' => $detail]);
            $status && $this->assertJsonFragment(['status' => $status])->assertStatus((int) $status);

            return $this;
        };
    }

    /**
     * Macro para verificar si se devuelven errores de validación en formato JSON:API.
     *
     * Este método macro se utiliza para verificar si se devuelven errores de validación
     * en un formato específico JSON:API. Verifica si los errores de validación para un
     * atributo específico tienen el formato correcto y si la respuesta tiene el encabezado
     * content-type establecido en application/vnd.api+json. Además, verifica si el código
     * de estado de la respuesta es 422 (Unprocessable Entity).
     *
     * @param string $attribute // El atributo para el cual se están verificando los errores
     * de validación en formato JSON:API. Parametro del Closure interno.
     *
     * @return Closure
     */
    public function assertJsonApiValidationErrors(): Closure
    {
        return function ($attribute) {
            /** @var TestResponse $this  */

            $pointer = "/data/attributes/{$attribute}";

            if ( Str::of($attribute)->startsWith('data') ) {
                $pointer = "/".str_replace('.', '/', $attribute);
            } elseif (Str::of($attribute)->startsWith('relationships')) {
                $pointer = "/data/".str_replace('.', '/', $attribute)."/data/id";
            }

            try {
                $this->assertJsonFragment([
                    'source' => ['pointer' => $pointer]
                ]);
            } catch (ExpectationFailedException $e) {
                PHPUnit::fail(
                    "Failed to find a JSON:API validation error for key: '{$attribute}'"
                    .PHP_EOL.PHP_EOL.
                    $e->getMessage()
                );
            }

            try {
                $this->assertJsonStructure([
                    'errors' => [
                        ['title', 'detail', 'source' => ['pointer']]
                    ]
                ]);
            } catch (ExpectationFailedException $e) {
                PHPUnit::fail(
                    "Failed to find a valid JSON:API error response"
                    .PHP_EOL.PHP_EOL.
                    $e->getMessage()
                );
            }

            return $this->assertHeader(
                'content-type', 'application/vnd.api+json'
            )->assertStatus(422);
        };
    }

    /**
     * Macro para verificar si se devuelve un recurso en formato JSON:API.
     *
     * Este método macro se utiliza para verificar si se devuelve un recurso en un formato
     * específico JSON:API. Verifica si la respuesta contiene un objeto de datos con el tipo
     * correcto, el ID correcto, los atributos esperados y los enlaces de self correctos.
     * También verifica el encabezado Location de la respuesta.
     *
     * @param \Illuminate\Database\Eloquent\Model $model // El modelo del recurso que se
     * espera recibir en la respuesta JSON:API. Parametro del Closure interno.
     *
     * @param array $attributes // Los atributos que se esperan para el recurso en la
     * respuesta JSON:API. Parametro del Closure interno.
     *
     * @return Closure
     */
    public function assertJsonApiResource(): Closure
    {
        return function ($model, $attributes) {

            /** @var TestResponse $this */
            $this->assertJson([
                'data' => [
                    'type' => $model->getResourceType(),
                    'id' => (string) $model->getRouteKey(),
                    'attributes' => $attributes,
                    'links' => [
                        'self' => route('api.v1.'.$model->getResourceType().'.show', $model)
                    ]
                ]
            ]);

            // Se debe verificar el header Location solo cuando se haya creado un nuevo recurso.
            if ($this->status() === 201) {
                $this->assertHeader(
                    'Location',
                    route('api.v1.'.$model->getResourceType().'.show', $model)
                );
            }

            return $this;
        };
    }

    /**
     * Macro para verificar si se devuelve una colección de recursos en formato JSON:API.
     *
     * Este método macro se utiliza para verificar si se devuelve una colección de recursos
     * en un formato específico JSON:API. Verifica si la respuesta contiene una estructura
     * de datos que representa una colección de recursos, y verifica cada recurso individual
     * dentro de la colección para asegurarse de que tenga el formato correcto
     * de tipo, ID y enlaces de self.
     *
     * @param \Illuminate\Database\Eloquent\Collection $models // La colección de modelos de
     * recursos que se espera recibir en la respuesta JSON:API. Parametro del Closure interno.
     *
     * @param array $attributesKeys // Las claves de los atributos que se esperan para cada
     * recurso en la respuesta JSON:API. Parametro del Closure interno.
     *
     * @return Closure
     */
    public function assertJsonApiResourceCollection(): Closure
    {
        return function ($models, $attributesKeys) {
            /** @var TestResponse $this */

            $this->assertJsonStructure([
                'data' => [
                    '*' => [
                        'attributes' => $attributesKeys
                    ]
                ]
            ]);

            foreach ($models as $model) {
                $this->assertJsonFragment([
                    'type' => $model->getResourceType(),
                    'id' => (string) $model->getRouteKey(),
                    'links' => [
                        'self' => route('api.v1.'.$model->getResourceType().'.show', $model)
                    ]
                ]);
            }

            return $this;
        };
    }

    /**
     * Macro para verificar si se estan retornando los relationships links
     * en las respuestas.
     *
     * @param Model $model
     * @param array $relationships
     *
     * @return Closure
     */
    public function assertJsonApiRelationshipLinks(): Closure
    {
        return function ($model, $relations) {
            /** @var TestResponse $this */

            foreach ($relations as $relation) {
                $this->assertJson([
                    'data' => [
                        'relationships' => [
                            $relation => [
                                'links' => [
                                    'self' => route("api.v1.{$model->getResourceType()}.relationships.{$relation}", $model),
                                    'related' => route("api.v1.{$model->getResourceType()}.{$relation}", $model)
                                ]
                            ]
                        ]
                    ]
                ]);
            }

            return $this;
        };
    }

}
