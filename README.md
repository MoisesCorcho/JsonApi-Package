
# JsonApi-Package

# Laravel JsonApi Mixins

Este paquete proporciona una serie de macros que simplifican el manejo de consultas JSON:API en Laravel Eloquent, así como macros adicionales para las clases Request y TestResponse. Estas macros están diseñadas para extender las capacidades de Laravel en el manejo de solicitudes y respuestas que siguen la especificación JSON:API, lo que facilita tanto la manipulación de datos como la escritura y ejecución de pruebas unitarias y funcionales para las API. Las macros disponibles incluyen:

**Para Laravel Eloquent: *JsonApiQueryBuilder:***  Proporciona macros para facilitar la manipulación de consultas de base de datos siguiendo los estándares JSON:API. Estas macros simplifican la aplicación de clasificación, filtros, inclusión de relaciones, selección de campos y paginación en las consultas de Eloquent.

**Para Illuminate\Http\Request: *JsonApiRequest:*** Ofrece macros que extienden la clase Request de Laravel para facilitar la identificación y manipulación de solicitudes que siguen la especificación JSON:API. Estas macros permiten determinar si una solicitud es una solicitud JSON:API, extraer los datos validados de la solicitud, obtener atributos de datos, obtener el ID de una relación especificada y verificar la presencia de relaciones en los datos de la solicitud.

**Para Illuminate\Testing\TestResponse: *JsonApiTestResponse:***
Presenta macros que extienden la clase TestResponse de Laravel para facilitar la verificación de respuestas que siguen la especificación JSON:API. Estas macros permiten verificar la presencia y el formato correcto de errores JSON:API, errores de validación, recursos JSON:API, colecciones de recursos JSON:API y enlaces de relaciones en las respuestas.

El paquete abarca varios aspectos clave para cumplir con la especificación JSON:API:

1: **Creación de respuestas JSON:API coherentes**: El Trait `JsonApiResource` facilita la transformación de modelos y colecciones de Eloquent en respuestas JSON:API válidas, incluyendo la estructura correcta de los recursos, los enlaces de relaciones y los encabezados necesarios.

2: **Validación de solicitudes y respuestas**: El paquete incluye middleware y clases de respuesta para validar los documentos y encabezados JSON:API en las solicitudes HTTP, así como para manejar errores de validación de manera coherente según la especificación JSON:API.

3: **Facilita la escritura de pruebas**: Los mixins para las clases Request y TestResponse simplifican la escritura y ejecución de pruebas unitarias y funcionales para las API que siguen la especificación JSON:API, permitiendo verificar fácilmente la estructura y el formato de las respuestas, así como validar los datos de entrada. 

En resumen, este paquete proporciona herramientas integrales para establecer una estructura consistente y compatible con la especificación JSON:API en las APIs desarrolladas con Laravel, abordando aspectos como la manipulación de datos, la validación de solicitudes y respuestas, y la escritura de pruebas.

# Estructura
```
📦src
 ┣ 📂Exceptions
 ┃ ┣ 📜AuthenticationException.php
 ┃ ┣ 📜Handler.php
 ┃ ┗ 📜HttpException.php
 ┣ 📂Http
 ┃ ┣ 📂Middleware
 ┃ ┃ ┣ 📜ValidateJsonApiDocument.php
 ┃ ┃ ┗ 📜ValidateJsonApiHeaders.php
 ┃ ┗ 📂Responses
 ┃ ┃ ┗ 📜JsonApiValidationErrorResponse.php
 ┣ 📂Mixins
 ┃ ┣ 📜JsonApiQueryBuilder.php
 ┃ ┣ 📜JsonApiRequest.php
 ┃ ┗ 📜JsonApiTestResponse.php
 ┣ 📂Providers
 ┃ ┗ 📜JsonApiServiceProvider.php
 ┣ 📂Traits
 ┃ ┣ 📜HasModelsRelationship.php
 ┃ ┗ 📜JsonApiResource.php
 ┗ 📜Document.php
```
# Uso

# Traits

El Trait `JsonApiResource` se utiliza dentro de los Laravel Resources para facilitar la creación de respuestas que cumplen con la especificación JSON:API. Proporciona una serie de métodos y funcionalidades que permiten ajustar la salida de los recursos a los estándares JSON:API de manera más sencilla y consistente.

Aquí hay una descripción de las principales funcionalidades proporcionadas por este Trait:

1: **`toJsonApi()`:** Este método abstracto debe ser implementado en las clases que utilicen este Trait. Se utiliza para especificar los atributos del recurso que se desean incluir en la respuesta JSON:API.

2: **`identifier(Model $resource)`:** Un método estático que genera un documento JSON:API para un recurso individual, utilizando solo su `id` y `type`.

3: **`identifiers(Collection $resources)`:** Similar al método anterior, pero para una colección de recursos.

4: **`toArray(Request $request)`:** Transforma el recurso en un array siguiendo la estructura de un documento JSON:API. Este método ajusta automáticamente la salida para incluir los atributos, relaciones y enlaces correctos.

5: **`getIncludes()`:** Método opcional que se puede definir en la clase que utiliza este Trait para especificar las relaciones que se desean incluir en la respuesta JSON:API.
Ejemplo:

```php
public function getIncludes(): array
    {
        return [
            CategoryResource::make($this->whenLoaded('category')),
            AuthorResource::make($this->whenLoaded('author')),
            // Se llama al metodo collection ya que es una relacion de uno a muchos.
            CommentResource::collection($this->whenLoaded('comments'))
        ];
    }
```

6: **`getRelationshipLinks()`:** Otro método opcional para definir los enlaces de las relaciones que se desean generar en el documento JSON:API.

Ejemplo:

```php
public function getRelationshipLinks(): array
{
    return ['category', 'author'];
}
```
7: **`withResponse(Request $request, JsonResponse $response)`:** Permite personalizar la respuesta JSON:API antes de enviarla al cliente. Por ejemplo, puede establecer el encabezado `Location` en caso de que se haya creado un nuevo recurso.

8: **`filterAttributes(array $attributes)`:** Filtra los atributos del recurso para incluir solo aquellos especificados en los campos solicitados, si se ha especificado alguno.

9: **`collection($resources)`:** Sobrescribe el método `collection` para añadir atributos adicionales, como los enlaces, a la respuesta de una colección de recursos.

En resumen, el Trait `JsonApiResource` simplifica y estandariza la creación de respuestas JSON:API dentro de los Laravel Resources, facilitando el cumplimiento de los estándares y la generación de respuestas coherentes y estructuradas según la especificación JSON:API.


# Macros - Mixins

Para usar estos mixins, simplemente registra el proveedor de servicios `JsonApiServiceProvider` en tu archivo `config/app.php` y aplica las macros a tus consultas Eloquent.

```php
    'providers' => ServiceProvider::defaultProviders()->merge([
        /*
         * Package Service Providers...
         */

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
        // Here
        App\Providers\JsonApiServiceProvider::class,
    ])->toArray(),
```

# Macros Disponibles

## Builder

### allowedSorts

**Firma:** `allowedSorts(array $allowedSorts): Closure`  

**Descripción:** Aplica clasificación (ordenamiento) a una consulta de base de datos según los campos permitidos.  
**Parámetros:**  

- `$allowedSorts`: Los campos de clasificación permitidos.

### allowedFilters

**Firma:** `allowedFilters(array $allowedFilters): Closure`  

**Descripción:** Aplica filtros a una consulta de base de datos según los campos permitidos.  

**Parámetros:**  
- `$allowedFilters`: Los campos de filtro permitidos.

### allowedIncludes

**Firma:** `allowedIncludes(array $allowedIncludes): Closure`  

**Descripción:** Precarga relaciones en una consulta de base de datos según las relaciones permitidas.  
**Parámetros:**  

- `$allowedIncludes`: Las relaciones permitidas.

**Notas:** Para el correcto uso de este Macro se debe implementar el Trait "HasModelsRelationship", el cual, obliga a la implementacion de la funcion "getModelRelationships" en la cual se debe retornar un arreglo de strings con los nombres de las relaciones establecidas en el modelo.

### sparseFieldset

**Firma:** `sparseFieldset(): Closure`  

**Descripción:** Selecciona un subconjunto de campos de la consulta.  

**Parámetros:**  
- Ninguno.

### jsonPaginate

**Firma:** `jsonPaginate(): Closure`  

**Descripción:** Pagina los resultados de la consulta.  

**Parámetros:**  
- Ninguno.

### getResourceType

**Firma:** `getResourceType(): Closure`  

**Descripción:** Obtiene el tipo de recurso de una consulta.  

**Parámetros:**  
- Ninguno.


## Ejemplo de uso

```php
use App\Models\Post;
use Illuminate\Http\Request;

$posts = Post::query()
    ->allowedSorts(['title', 'created_at'])
    ->allowedFilters(['author', 'created_at'])
    ->allowedIncludes(['comments', 'author'])
    ->sparseFieldset()
    ->jsonPaginate();
```

## Request

### isJsonApi

**Firma:** `isJsonApi(): Closure`
**Descripción:** Determina si la solicitud actual es una solicitud JSON API.
**Parámetros:** 
- Ninguno.

### validatedData

**Firma:** `validatedData(): Closure`
**Descripción:** Retorna los datos validados de la solicitud.
**Parámetros:** 
- Ninguno.

### getAttributes

**Firma:** `getAttributes(): Closure`

**Descripción:** Retorna los atributos de los datos validados de la solicitud.
**Parámetros:** 
- Ninguno.

### getRelationshipId

**Firma:** `getRelationshipId(string $relation): Closure`

**Descripción:** Retorna el ID de una relación especificada.

**Parámetros:**
- `$relation`: El nombre de la relación.

### hasRelationships

**Firma:** `hasRelationships(): Closure`

**Descripción:** Verifica si la solicitud tiene relaciones.

**Parámetros:** 
- Ninguno.

### hasRelationship

**Firma:** `hasRelationship($relation): Closure`

**Descripción:** Verifica si una relación específica está presente en los datos validados de la solicitud.

**Parámetros:**
- `$relation`: El nombre de la relación.

## Ejemplo de uso

```php
public function store(AppointmentRequest $request): AppointmentResource
{
    $request->validatedData();
    $appointmentData = $request->getAttributes();

    $appointmentData['category_id'] = $request->getRelationshipId('category');
    $appointmentData['user_id'] = $request->getRelationshipId('author');

    $appointment = Appointment::create($appointmentData);

    return AppointmentResource::make($appointment);
}

```


## TestResponse

### assertJsonApiError

**Firma:** `assertJsonApiError(string $title = null, string $detail = null, string $status = null): Closure`

**Descripción:** Verifica si se devuelven errores HTTP en formato JSON:API.

**Parámetros:**
- `$title` (Opcional): El título del error.
- `$detail` (Opcional): El detalle del error.
- `$status` (Opcional): El estado del error.

### assertJsonApiValidationErrors

**Firma:** `assertJsonApiValidationErrors(string $attribute): Closure`

**Descripción:** Verifica si se devuelven errores de validación en formato JSON:API.

**Parámetros:**
- `$attribute`: El atributo para el cual se están verificando los errores de validación en formato JSON:API.

### assertJsonApiResource

**Firma:** `assertJsonApiResource(Model $model, array $attributes): Closure`

**Descripción:** Verifica si se devuelve un recurso en formato JSON:API.

**Parámetros:**
- `$model`: El modelo del recurso que se espera recibir en la respuesta JSON:API.
- `$attributes`: Los atributos que se esperan para el recurso en la respuesta JSON:API.

### assertJsonApiResourceCollection

**Firma:** `assertJsonApiResourceCollection(Collection $models, array $attributesKeys): Closure`

**Descripción:** Verifica si se devuelve una colección de recursos en formato JSON:API.

**Parámetros:**
- `$models`: La colección de modelos de recursos que se espera recibir en la respuesta JSON:API.
- `$attributesKeys`: Las claves de los atributos que se esperan para cada recurso en la respuesta JSON:API.

### assertJsonApiRelationshipLinks

**Firma:** `assertJsonApiRelationshipLinks(Model $model, array $relations): Closure`

**Descripción:** Verifica si se están retornando los enlaces de relaciones en las respuestas JSON:API.

**Parámetros:**
- `$model`: El modelo del recurso del cual se están verificando los enlaces de relaciones.
- `$relations`: Las relaciones para las cuales se están verificando los enlaces de relaciones en la respuesta JSON:API.


```php
/** @test */
public function guests_cannot_create_appointments()
{
    $this->postJson(route('api.v1.appointments.store'))
        ->assertJsonApiError(
            title: 'Unauthenticated',
            detail: 'This action requires authentication.',
            status: '401'
        );

    $this->assertDatabaseCount('appointments', 0);
}


/** @test */
public function date_is_required()
{
    /** Cualquier usuario que se cree tendrá los permisos necesarios
        * para la autenticacion de Sanctum
        */
    Sanctum::actingAs(User::factory()->create());

    $response = $this->postJson(route('api.v1.appointments.store'), [
        'data' => [
            'type' => 'appointments',
            'attributes' => [
                'start_time' => '11:00',
                'email' => 'falseemail@gmail.com'
            ],
        ]
    ]);

    $response->assertJsonApiValidationErrors('date');
}


/** @test */
public function can_fetch_a_single_appointment(): void
{
    $this->withoutExceptionHandling();

    $appointment = Appointment::factory()->create();

    $response = $this->getJson(route('api.v1.appointments.show', $appointment));

    $response->assertJsonApiResource($appointment, [
        'date' => $appointment->date,
        'start-time' => $appointment->start_time,
        'email' => $appointment->email
    ])->assertJsonApiRelationshipLinks($appointment, ['category', 'author']);
}


/** @test */
public function can_fetch_all_appointments()
{
    $this->withoutExceptionHandling();

    $appointments = Appointment::factory()->count(3)->create();

    $response = $this->getJson(route('api.v1.appointments.index'));

    $response->assertJsonApiResourceCollection($appointments, [
        'date', 'start-time', 'email'
    ]);
}

```

## Ejemplos de establecimiento de rutas 

```php
Route::apiResource('appointments', AppointmentController::class);

Route::apiResource('categories', CategoryController::class)
    ->only('index', 'show');

Route::apiResource('authors', AuthorController::class)
    ->only('index', 'show');

Route::apiResource('comments', CommentController::class);

// Todas estas rutas comparten el mismo inicio de URL asi que se agrupan para colocarle un prefijo
// y que de esta manera la ruta sea mas corta.
Route::prefix('appointments/{appointment}')->group(function () {

    // Son rutas necesarias para generar los links de las relaciones (self y related) de Category
    // (Las categorias de los Appointments)
    Route::controller(AppointmentCategoryController::class)->group(function () {

        // Obtener el identificador de la Categoria asociada al Appointment.
        Route::get('relationships/category', 'index')
            ->name('appointments.relationships.category');

        // Actualizar Categoria relacionada al Appointment.
        Route::patch('relationships/category', 'update')
            ->name('appointments.relationships.category');

        // Obtener la Categoria asociada al Appointment.
        Route::get('category', 'show')
            ->name('appointments.category');
    });

    // Son rutas necesarias para generar los links de las relaciones (self y related) de Author
    // (Los autores de los Appointemnts)
    Route::controller(AppointmentAuthorController::class)->group(function () {

        // Obtener el identificador del Autor relacionado al Appointment.
        Route::get('relationships/author', 'index')
            ->name('appointments.relationships.author');

        // Actualizar el Autor relacionado al Appointment.
        Route::patch('relationships/author', 'update')
            ->name('appointments.relationships.author');

        // Obtener el Autor relacionado al Appointment.
        Route::get('author', 'show')
            ->name('appointments.author');
    });

    // Son rutas necesarias para generar los links de las relaciones (self y related) de Comment
    // (Los comentarios de los Appointemnts)
    Route::controller(AppointmentCommentController::class)->group(function () {

        // Obtener los identificadores de los Comentarios relacionados al Appointment.
        Route::get('relationships/comments', 'index')
            ->name('appointments.relationships.comments');

        // Actualizar los Comentarios relacionados al Appointment.
        Route::patch('relationships/comments', 'update')
            ->name('appointments.relationships.comments');

        // Obtener los Comentarios relacionados al Appointment.
        Route::get('relationships', 'show')
            ->name('appointments.comments');
    });
});

```