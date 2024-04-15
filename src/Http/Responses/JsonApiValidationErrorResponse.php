<?php

namespace JsonApi\JsonApi\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class JsonApiValidationErrorResponse extends JsonResponse
{

    public function __construct(ValidationException $exception, $status = 422)
    {
        $data = $this->formatJsonApiErrors($exception);

        $headers = [
            'content-type' => 'application/vnd.api+json'
        ];

        parent::__construct($data, $status, $headers);
    }

    /**
     * Darle formato a los errores de validacion de Laravel
     *
     * @param ValidationException $exception
     * @return Array
     */
    protected function formatJsonApiErrors(ValidationException $exception): Array
    {
        $title = $exception->getMessage();

        /*
        Esta es otra manera de hacer el proceso de darle formato a los errores segun json api specification
        $errors = [];
        foreach($exception->errors() as $field => $messages) {
            $pointer = "/".str_replace('.', '/', $field);

            $errors[] = [
                'title' => $title,
                'detail' => $messages[0],
                'source' => [
                    'pointer' => $pointer
                ]
            ];
        }*/

        return [
            'errors' => collect($exception->errors())
                ->map(function ($messages, $field) use ($title) {
                    return [
                        'title' => $title,
                        'detail' => $messages[0],
                        'source' => [
                            'pointer' => "/".str_replace('.', '/', $field)
                        ]
                    ];
                })->values()
        ];
    }

}
