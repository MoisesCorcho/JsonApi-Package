<?php

namespace JsonApi\JsonApi\Http\Middleware;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ValidateJsonApiDocument
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('POST') || $request->isMethod('PATCH')) {
            $request->validate([
                'data' => ['required', 'array'],
                'data.type' => [
                    /** Solo será requerido este campo en caso de que el primer elemento (0) del array 'data'
                     * NO contenga la llave 'type', en caso que SI la contenga, no será requerido.
                     * Esta regla será de ayuda para los casos en donde se mandan varios elementos
                     * dentro de 'data', ya que en este caso, cada elemento irá dentro de su propio array*/
                    'required_without:data.0.type',
                    'string'
                ],
                'data.attributes' => [
                    Rule::requiredIf(
                        ! Str::of(request()->url())->contains('relationships')
                        /** Solo será requerida la llave 'attributes' dentro de 'data', en caso de que NO
                         * exista la llave 'type' dentro del primer elemento dentro de 'data'.
                         */
                        && request()->isNotFilled('data.0.type')
                    ),
                    'array'
                ]
            ]);
        }

        if ($request->isMethod('PATCH')) {
            $request->validate([
                'data.id' => [
                    /** Solo será requerido este campo en caso de que el primer elemento (0) del array 'data'
                     * NO contenga la llave 'id', en caso que SI la contenga, no será requerido.
                     * Esta regla será de ayuda para los casos en donde se mandan varios elementos
                     * dentro de 'data', ya que en este caso, cada elemento irá dentro de su propio array*/
                    'required_without:data.0.id',
                    'string'
                ]
            ]);
        }

        return $next($request);
    }
}
