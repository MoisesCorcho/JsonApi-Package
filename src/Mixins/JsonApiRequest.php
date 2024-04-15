<?php

namespace JsonApi\JsonApi\Mixins;

use Closure;

/**
 * Esta clase proporciona un conjunto de macros (cierres) que se pueden
 * adjuntar a la clase Request de Laravel para facilitar el manejo de
 * solicitudes que siguen la especificación JSON API.
 */
class JsonApiRequest
{
    /**
     * Determina si la solicitud actual es una solicitud JSON API.
     *
     * Esta función determina si la solicitud actual es una solicitud JSON API.
     * Verifica si la cabecera de aceptación (accept) o el tipo de contenido
     * (content-type) es application/vnd.api+json. Devuelve true si la solicitud
     * es una solicitud JSON API, de lo contrario, devuelve false.
     *
     * @return \Closure
     */
    public function isJsonApi(): Closure
    {
        return function() {
            /** @var Request $this */

            // Si el path NO empieza con el prefijo 'api' entonces NO se considera como
            // una peticion JSON:API
            if ( ! str($this->path())->startsWith('api') ) {
                return false;
            }

            if ($this->header('accept') === 'application/vnd.api+json') {
                return true;
            }

            return $this->header('content-type') === 'application/vnd.api+json';
        };
    }

    /**
     * Retorna los datos validados de la solicitud.
     *
     * Esta función devuelve los datos validados de la solicitud. Se asume que
     * la solicitud ha sido validada previamente. Retorna los datos bajo la
     * clave 'data'.
     *
     * @return \Closure
     */
    public function validatedData(): Closure
    {
        return function() {
            /** @var Request $this */

            return $this->validated()['data'];
        };

        return $this;
    }

    /**
     * Retorna los atributos de los datos validados de la solicitud.
     *
     * Esta función devuelve los atributos de los datos validados de la solicitud.
     * Se asume que los datos han sido validados y que contienen una clave
     * 'attributes' que contiene los atributos asociados al recurso.
     *
     * @return \Closure
     */
    public function getAttributes(): Closure
    {
        return function() {
            /** @var Request $this */

            return $this->validatedData()['attributes'];
        };

        return $this;
    }

    /**
     * Retorna el ID de una relación especificada.
     *
     * Esta función devuelve el ID de una relación especificada. Toma el nombre
     * de la relación como parámetro y retorna el ID asociado a esa relación
     * dentro de los datos validados de la solicitud.
     *
     * @param string $relation
     * @return \Closure
     */
    public function getRelationshipId(): Closure
    {
        return function(string $relation) {
            /** @var Request $this */

            return $this->validatedData()['relationships'][$relation]['data']['id'];
        };

        return $this;
    }

    /**
     * Verifica si la solicitud tiene relaciones.
     *
     * Esta función verifica si la solicitud tiene relaciones. Comprueba si la
     * clave 'relationships' está presente en los datos validados de la solicitud.
     *
     * @return \Closure
     */
    public function hasRelationships(): Closure
    {
        return function() {
            /** @var Request $this */

            return isset($this->validatedData()['relationships']);
        };

        return $this;
    }

    /**
     * Verifica si una relación específica está presente en los datos validados de la solicitud.
     *
     * Esta función verifica si una relación específica está presente en los
     * datos validados de la solicitud. Toma el nombre de la relación como
     * parámetro y verifica si existe dentro de las relaciones presentes en
     * los datos validados de la solicitud.
     *
     * @param string $relation
     * @return \Closure
     */
    public function hasRelationship(): Closure
    {
        return function($relation) {
            /** @var Request $this */

            return $this->hasRelationships() && isset($this->validatedData()['relationships'][$relation]);
        };

        return $this;
    }

}
