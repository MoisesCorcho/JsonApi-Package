<?php

namespace JsonApi\JsonApi\Traits;

use Illuminate\Http\Request;
use JsonApi\JsonApi\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Trait para los Laravel Resources, en donde se hacen ciertas modificaciones para
 * que sea mas facil ajustar las respuestas a la especificacion JSON:API
 */
Trait JsonApiResource
{
    /**
     * Se especifican en un arreglo los atributos del recurso
     * que se quiere convertir en JSON.
     *
     * @return array
     */
    abstract public function toJsonApi(): array;

    /**
     * Funcion para crear el documento JSON:API para las rutas de self.
     * Ej. 'api/v1/appointments/{appointment}/relationships/category'
     *
     * @param Model $resource //El recurso asociado del cual se quieren retornar solo el 'id' y el 'type'
     * @return array
     */
    public static function identifier(Model $resource): array
    {
        return Document::type( $resource->getResourceType() )
            ->id( $resource->getRouteKey() )
            ->toArray();
    }

    /**
     * Funcion para crear el documento JSON:API para las rutas de self de varios modelos.
     * Ej. 'api/v1/appointments/{appointment}/relationships/comments'
     *
     * @param Collection $resources //Los recursos asociados de los cuales se quieren retornar solo el 'id' y el 'type'
     * @return array
     */
    public static function identifiers(Collection $resources)
    {
        // en caso de que la coleccion de recursos esté vacia se retorna
        // la llave 'data' asociada a un arary vacio.
        if ($resources->isEmpty($resources)) {
            return Document::empty();
        }

        return Document::type( $resources->first()->getResourceType() )
            ->ids( $resources )
            ->toArray();
    }

    /**
     * Transform the resource into an array.
     * Se usa el metodo get('data') para que no hayan errores tales
     * como que en algunas ocasiones se duplique la llave 'data',
     * la que se agrega por parte de la clase Document creada por
     * nosotros y la que se agrega automaticamente en
     * los LaravelResources
     *
     * @return array
     */
    public function toArray(Request $request): array
    {
        /** La llave include se agrega con el metodo $this->with()
         *  ya que debe estar al mismo nivel que la llave 'data' agregada
         *  de manera automatica por los LaravelResources.
         */
        if ( request()->filled('include') ) {

            // $resource->getIncludes() retorna un arreglo con las categorias de cada appointment.
            foreach( $this->getIncludes() as $include) {

                /** En caso de que se reciba la instancia de una Collection se
                 * va a recorrer dicha coleccion y se van a incluir sus elementos
                 * a la llave 'included', luego de esto, se debe hacer un 'continue'
                 * es decir, se va a pasar al siguiente elemento en el foreach(), esto
                 * para evitar que se siga ejecutando este loop y se añada a la
                 * llave contenido que no se quiere.
                */
                if ($include->resource instanceof Collection) {
                    foreach ($include->resource as $resource) {
                        $this->with['included'][] = $resource;
                    }

                    continue;
                }

                /** En caso de que se reciban objetos de relaciones que no se hayan
                 * especificado para precargar, se recibe una instancia de
                 * MissingValue, debido a la funcion whenLoaded().
                 */
                if( $include->resource instanceof MissingValue) {
                    continue;
                };

                /** Se colocan los corchetes luego de ['included] para
                 * que se agregue $include como nuevo elemento del
                 * arreglo ya que ['included'], va a ser un arreglo con
                 * todas las relaciones que se incluyan en el query
                 * parameter 'include'
                 */
                $this->with['included'][] = $include;
            }
        }

        /** Se crea el documento JSON:API a retornar con la clase
         * 'Document' creada manualmente. */
        return Document::type($this->resource->getResourceType())
            ->id($this->resource->getRouteKey())
            ->attributes($this->filterAttributes( $this->toJsonApi() ))
            ->relationshipLinks( $this->getRelationshipLinks() )
            ->links([
                'self' => route('api.v1.'.$this->resource->getResourceType().'.show', $this)
            ])
            ->get('data');
    }

    /**
     * Establecemos este metodo aqui en el Trait para hacer la inclusion
     * de este metodo opcional en la creacion del documento JSON:API.
     * Para darle valores lo definimos en el LaravelResource que se necesite.
     *
     * Se especifican los documentos que se quieran incluir dentro
     * del documento JSON:API.
     *
     * @return array
     */
    public function getIncludes(): array
    {
        return [];
    }

    /**
     * Establecemos este metodo aqui en el Trait para hacer la inclusion
     * de este metodo opcional en la creacion del documento JSON:API.
     * Para darle valores lo definimos en el LaravelResource que se necesite.
     *
     * Dentro del LaravelResource en este metodo se especifican las relaciones
     * de los links que se quieran generar.
     *
     * @return array
     */
    public function getRelationshipLinks(): array
    {
        return [];
    }

    /**
     * Customize the response for a request
     *
     * @param Request $request
     * @param JsonResponse $response
     * @return void
     */
    public function withResponse(Request $request, JsonResponse $response)
    {
        // Se debe mandar el header Location unicamente cuando se ha creado un recurso.
        if ($response->status() === 201) {
            $response->header(
                'Location',
                route('api.v1.'.$this->getResourceType().'.show', $this)
            );
        }
    }

    /**
     * Filtra los atributos para incluir en la representación del recurso.
     *
     * @param array $attributes Los atributos del recurso.
     * @return array El array de atributos filtrados.
     */
    public function filterAttributes(array $attributes): array
    {
        return array_filter($attributes, function($value) {

            // Verifica si no se han especificado campos específicos para la respuesta.
            if (request()->isNotFilled('fields')) {
                return true; // Si no se han especificado campos, se incluye el atributo.
            }

            // Obtiene los campos solicitados para este tipo de recurso.
            $fields = explode(',', request('fields.'.$this->getResourceType()));

            /**
             * Verifica si el valor actual es la clave de la ruta del recurso.
             * Debemos hacer esta verificacion ya que el identificador del recurso
             * es siempre incluido en App\JsonApi\JsonApiQueryBuilder::sparseFieldset()
             */
            if ($value === $this->getRouteKey()) {
                // Si es la clave de la ruta, verifica si la clave de la ruta está presente en los campos solicitados.
                return in_array($this->getRouteKeyName(), $fields);
            }

            // Si no es la clave de la ruta, se incluye el atributo sin filtrar.
            return $value;
        });
    }

    /**
     * Se reescribe el metodo collection para añadirle el atributo 'links'
     * a la respuesta, lo cual, nos ahorra el tener que crear un archivo
     * LaravelCollection solo para añadir dicho atributo.
     *
     * Se devuelve LengthAwarePaginator ya que se estan retornando los
     * resultados por defecto.
     * @param LengthAwarePaginator $resource
     *
     * @return AnonymousResourceCollection
     */
    public static function collection($resources): AnonymousResourceCollection
    {

        $collection = parent::collection($resources);

        /** La llave include se agrega con el metodo $this->with()
         *  ya que debe estar al mismo nivel que la llave 'data' agregada
         *  de manera automatica por los LaravelResources.
         */
        if (request()->filled('include')) {

            // $resources - debe retornar una coleccion de recursos.
            foreach ($collection->resource as $resource) {

                // $resource->getIncludes() retorna un arreglo con las categorias de cada appointment.
                // Es un metodo personalizado (No viene con Laravel).
                foreach( $resource->getIncludes() as $include) {

                    /** En caso de que se reciba la instancia de una Collection se
                     * va a recorrer dicha coleccion y se van a incluir sus elementos
                     * a la llave 'included', luego de esto, se debe hacer un 'continue'
                     * es decir, se va a pasar al siguiente elemento en el foreach(), esto
                     * para evitar que se siga ejecutando este loop y se añada a la
                     * llave contenido que no se quiere.
                    */
                    if ( $include->resource instanceof Collection ) {
                        foreach ($include->resource as $resource) {
                            $collection->with['included'][] = $resource;
                        }

                        continue;
                    }

                    /** En caso de que se reciban objetos de relaciones que no se hayan
                     * especificado para precargar, se recibe una instancia de
                     * MissingValue, debido a la funcion whenLoaded().
                     */
                    if( $include->resource instanceof MissingValue) {
                        continue;
                    };

                    /** Se colocan los corchetes luego de ['included] para
                     * que se agregue $include como nuevo elemento del
                     * arreglo ya que ['included'], va a ser un arreglo con
                     * todas las relaciones que se incluyan en el query
                     * parameter 'include'
                     */
                    $collection->with['included'][] = $include;
                }
            }
        }

        $collection->with['links'] = ['self' => request()->path()];

        return $collection;
    }

}
