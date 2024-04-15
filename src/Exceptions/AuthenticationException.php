<?php

namespace JsonApi\JsonApi\Exceptions;

use Exception;

class AuthenticationException extends Exception
{
    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request)
    {
        return response()->json([
            'errors' => [
                [
                    'title' => 'Unauthenticated',
                    'detail' => "This action requires authentication.",
                    'status' => '401'
                ]
            ]
        ], 401);
    }
}
