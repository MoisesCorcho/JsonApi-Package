<?php

namespace JsonApi\JsonApi\Traits;

trait HasModelsRelationship
{
    /**
     * Si se quiere usar el Macro 'allowedIncludes' es necesario
     * que se use este Trait en el modelo para obligar a la
     * implementacion de esta funcion y asi asegurar el correcto
     * funcionamiento del Macro.
     *
     * @return array
     */
    abstract public function getModelRelationships(): array;
}
