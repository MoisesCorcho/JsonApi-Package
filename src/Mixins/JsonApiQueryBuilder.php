<?php

namespace JsonApi\JsonApi\Mixins;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Clase llamada a traves del metodo mixin de Builder
 * cada una de estas funciones es un Macro, la cual es una
 * funcion extendida de la propia clase Builder
 * es decir, que se puede usar como una funcion mas de la
 * clase
 */
class JsonApiQueryBuilder
{

    /**
     * Esta función (Macro) devuelve una función de cierre que se encarga de aplicar la
     * clasificación (ordenamiento) a una consulta de la base de datos. Toma un array
     * $allowedSorts como parámetro, que contiene los campos permitidos para ordenar.
     *
     * @param array $allowedSorts Los campos de clasificación permitidos (Se recibe en el Closure).
     * @return Closure
     */
    public function allowedSorts(): Closure
    {
        return function($allowedSorts) {

            /** @var Builder $this */

            if (request()->filled('sort')) {

                $sortFields = explode(',', request()->input('sort'));

                // Se obtienen en un array los campos de la tabla relacionada al modelo.
                $columnList = Schema::getColumnListing($this->getResourceType());

                foreach ($sortFields as $sortField) {

                    $sortDirection = Str::of($sortField)->startsWith('-') ? 'desc' : 'asc';

                    $sortField = ltrim($sortField, '-');

                    if ( !in_array($sortField, $allowedSorts) ) {
                        throw new BadRequestHttpException("The sort field '{$sortField}' is not allowed in the '{$this->getResourceType()}' resource.");
                    }

                    /** La convencion para pasar campos al ordenar es con guiones medios
                     * con lo cual, aquellos campos que tengan guiones bajos, son mandados
                     * con guiones medios, pero como en la base de datos el campos está
                     * escrito con guion medio, se debe hacer el cambio aqui.
                     */
                    $sortField = str($sortField)->replace('-', '_');


                    /** En caso de que uno de los campos mandados en el query parameter
                     * no exista como uno de los atributos de la tabla en base de datos
                     * relacionada al modelo, se manda una Excepcion.
                     */
                    if ( !in_array($sortField, $columnList) ) {
                        throw new BadRequestHttpException("The '{$sortField}' field does not exist");
                    }

                    $this->orderBy($sortField, $sortDirection);
                }
            }

            return $this;
        };
    }

    /**
     * Esta función (Macro) devuelve una función de cierre que se encarga de aplicar filtros a una
     * consulta de la base de datos. Toma un array $allowedFilters como parámetro, que
     * contiene los campos permitidos para filtrar.
     *
     * @param array $allowedFilters Los campos de filtro permitidos (Se recibe en el Closure).
     * @return Closure
     */
    public function allowedFilters(): Closure
    {
        return function($allowedFilters) {
            /** @var Builder $this */

            // Se obtienen en un array los campos de la tabla relacionada al modelo.
            $columnList = Schema::getColumnListing($this->getResourceType());

            // Se establece un array vacio como segundo parametro por defecto.
            foreach (request('filter', []) as $filter => $value) {

                if ( !in_array($filter, $allowedFilters) ) {
                    throw new BadRequestHttpException("The filter '{$filter}' is not allowed in the '{$this->getResourceType()}' resource.");
                }

                /** La convencion para pasar campos al ordenar es con guiones medios
                 * con lo cual, aquellos campos que tengan guiones bajos, son mandados
                 * con guiones medios, pero como en la base de datos el campos está
                 * escrito con guion medio, se debe hacer el cambio aqui.
                 */
                $filter = str($filter)->replace('-', '_')->toString();

                /** En caso de que uno de los campos mandados en el query parameter
                 * no exista como uno de los atributos de la tabla en base de datos
                 * relacionada al modelo, o sea un Scope se manda una Excepcion.
                 */
                if ( !$this->hasNamedScope($filter) && !in_array($filter, $columnList)) {
                    throw new BadRequestHttpException("The '{$filter}' field does not exist");
                }

                /** El metodo hasNamedScope es util para verificar la existencias
                 * de un Scope en el modelo.
                 */
                $this->hasNamedScope($filter)
                    ? $this->{$filter}($value)
                        // Este es el filtro por defecto en caso de que no se encuentren Scopes.
                    : $this->where($filter, 'LIKE', '%'.$value.'%');

            }

            return $this;
        };
    }

    /**
     * Esta funcion (Macro) hace la precarga de relaciones en caso de que
     * sea un include (relacion) permitido.
     *
     * Se debe recordar, que se deben añadir las relaciones que se quieran
     * incluir en el metodo 'getIncludes' dentro del LaravelResource, el cual
     * a su vez, debe estar usando el Trait 'JsonApiResource'.
     *
     * Ej. CategoryResource::make($this->whenLoaded('category'))
     *
     * Para el correcto funcionamiento de este Macro se debe establecer en el
     * modelo la funcion 'getModelRelationships()' en donde se retorne un
     * Array con los nombres de las relaciones definidas en el modelo.
     *
     * @param array $allowedIncludes Los includes permitidos. (Se recibe en el Closure).
     *
     * @return Closure
     */
    public function allowedIncludes(): Closure
    {
        return function ($allowedIncludes) {
            /** @var Builder $this */

            /**
             * En caso de que no se envie el query parameter 'include' simplemente
             * se retorna $this para que se puedan seguir encadenando metodos.
             */
            if (request()->isNotFilled('include')) {
                return $this;
            }

            // Convertimos el String recibido en un array.
            $includes = explode(',', request()->input('include'));

            // Se recorre el array de includes.
            foreach ($includes as $include) {

                if ( !in_array($include, $allowedIncludes) ) {
                    throw new BadRequestHttpException("The include relationship '{$include}' is not allowed in the '{$this->getResourceType()}' resource.");
                }

                // Se llamara al metodo 'getModelRelationships' solo en caso de que se haya
                // implementado dentro del modelo.
                if ( method_exists($this->model, 'getModelRelationships') ) {
                    if ( !in_array($include, $this->model->getModelRelationships()) ) {
                        throw new BadRequestHttpException("The '{$include}' relationship does not exist");
                    }
                }

                // Añadimos el include para la precarga.
                $this->with($include);
            }

            return $this;
        };
    }

    /**
     * Esta funcion (Macro) Retorna una función de cierre que selecciona
     * un subconjunto de campos de la consulta.
     *
     * @return Closure Una función de cierre para seleccionar campos específicos.
     */
    public function sparseFieldset(): Closure
    {
        return function () {
            /** @var Builder $this */

            /**
             * En caso de que no se envie el query parameter 'fields' simplemente
             * se retorna $this para que se puedan seguir encadenando metodos.
             */
            if (request()->isNotFilled('fields')) {
                return $this;
            }

            /**
             * Esta línea de código está extrayendo los campos de una solicitud HTTP.
             * La solicitud debe contener un parámetro llamado 'fields', que se espera
             * que sea una lista separada por comas de los campos que se desean recuperar
             * para un recurso específico. El método request() de Laravel se utiliza para
             * obtener los datos de la solicitud, y se espera que getResourceType()
             * proporcione el tipo de recurso actual
             */
            $fields = explode(',', request('fields.'.$this->getResourceType()));

            /**
             * Aquí se obtiene el nombre de la clave de ruta del modelo asociado con el
             * controlador. En Laravel, el nombre de la clave de ruta se utiliza para
             * identificar un modelo específico en las rutas con parámetros.
             */
            $getRouteKeyName = $this->model->getRouteKeyName();

            /**
             * Esta línea de código verifica si el nombre de la clave de ruta del modelo
             * está presente en la lista de campos extraídos de la solicitud. Si no está
             * presente, se agrega a la lista de campos. Esto garantiza que el nombre de
             * la clave de ruta esté incluido en los campos solicitados, lo que es
             * necesario cuando se recuperan recursos individualmente mediante su clave
             * de ruta. Si no se manda el identificador habran errores al encontrar
             * el recurso.
             */
            if ( !in_array($getRouteKeyName, $fields) ) {
                $fields[] = $getRouteKeyName;
            }

            // Se obtienen en un array los campos de la tabla relacionada al modelo.
            $columnList = Schema::getColumnListing($this->getResourceType());

            /** La convencion para pasar campos al ordenar es con guiones medios
             * con lo cual, aquellos campos que tengan guiones bajos, son mandados
             * con guiones medios, pero como en la base de datos el campos está
             * escrito con guion medio, se debe hacer el cambio aqui.
             */
            $fields = array_map(function ($field) use ($columnList) {

                $fieldReplaced = str($field)->replace('-', '_');

                /** En caso de que uno de los campos mandados en el query parameter
                 * no exista como uno de los atributos de la tabla en base de datos
                 * relacionada al modelo, se manda una Excepcion.
                 */
                if ( !in_array($fieldReplaced, $columnList) ) {
                    throw new BadRequestHttpException("The '{$field}' field does not exist");
                }

                return $fieldReplaced;
            }, $fields);

            return $this->addSelect($fields);
        };
    }

    /**
     * Esta funcion (Macro) Retorna una función de cierre que paginará
     * los resultados de la consulta.
     *
     * @return Closure Una función de cierre para paginar los resultados.
     */
    public function jsonPaginate(): Closure
    {
        return function () {
            /** @var Builder $this */

            return $this->paginate(
                $perPage = request('page.size', 15),
                $columns = ['*'],
                $pageName = 'page[number]',
                $page = request('page.number', 1)
            )->appends(request()->only('sort', 'filter', 'page.size'));
        };
    }

    /**
     * Esta funcion (Macro) Obtiene el tipo de recurso ya sea del nombre
     * de la tabla en la base de datos o en la propiedad
     * resourceType en el modelo creada por nosotros.
     *
     * @return Closure
     */
    public function getResourceType(): Closure
    {
        return function() {

            /** @var Builder $this */
            if (property_exists($this->model, 'resourceType')) {
                return $this->model->resourceType;
            }

            return $this->model->getTable();
        };
    }

}
