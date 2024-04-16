<?php

namespace JsonApi\JsonApi\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeApiResource extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:apiResource {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new API resource';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Obtiene el nombre del recurso pasado como argumento al comando.
        $name = $this->argument('name');

        // Lee el contenido del archivo stub del recurso desde el directorio de comandos.
        $stub = File::get(dirname(__DIR__) . '/stubs/resource.stub');

        // Reemplaza la cadena '{{resource}}' en el stub con el nombre del recurso proporcionado.
        $stub = str_replace('{{resource}}', $name, $stub);

        // Guarda el contenido del stub modificado como un nuevo archivo en el directorio de recursos.
        File::put(app_path("Http/Resources/{$name}.php"), $stub);

        // Muestra un mensaje informativo en la consola indicando que el recurso se ha creado con éxito.
        $this->info('Resource created successfully.');
    }
}
