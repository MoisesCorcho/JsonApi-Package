<?php

namespace JsonApi\JsonApi\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateJsonApiRoutes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:jsonapi-routes-testpackage {--models=} {--belongsTo} {--hasMany}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $models = explode('-', $this->option('models'));

        if (count($models) != 2) {
            $this->error('You must specify at least two models.');
            return;
        }

        $modelOneLowercase = Str::plural(strtolower($models[0]));
        $parameter = strtolower($models[0]);
        $modelTwoLowercase = strtolower($models[1]);

        if ( $this->option('belongsTo') || $this->option('hasMany') ) {
            $modelTwoLowercase = Str::plural(strtolower($models[1]));
        }

        // Index Endpoint
        $firstPartIndexPatch = "'{$modelOneLowercase}/{{$parameter}}/relationships/{$modelTwoLowercase}'";

        $secondPartIndex = "[{$models[0]}{$models[1]}Controller::class, 'index']";

        $nameIndexPatch = "'{$modelOneLowercase}.relationships.{$modelTwoLowercase}'";

        
        $endpointIndex = "\n//Obtener el identificador del modelo {$models[0]} asociado al modelo {$models[1]}". 
            "\n" . "Route::get({$firstPartIndexPatch}, {$secondPartIndex})->name({$nameIndexPatch});" . "\n";

        //Patch Endpoint

        $secondPartPatch = "[{$models[0]}{$models[1]}Controller::class, 'update']";

        $endpointPatch = "\n//Actualizar el identificador del modelo {$models[0]} asociado al modelo {$models[1]}".
            "\n" . "Route::patch({$firstPartIndexPatch}, {$secondPartPatch})->name({$nameIndexPatch});" . "\n";

        // Show Endpoint
        $firstPartShow = "'{$modelOneLowercase}/{{$parameter}}/{$modelTwoLowercase}'";

        $secondPartShow = "[{$models[0]}{$models[1]}Controller::class, 'show']";

        $nameShow = "'{$modelOneLowercase}.{$modelTwoLowercase}'";


        $endpointShow = "\n//Obtener el recurso del modelo {$models[0]} asociado al modelo {$models[1]}".
            "\n". "Route::get({$firstPartShow}, {$secondPartShow})->name({$nameShow});" ."\n";

        File::append(base_path('routes/api.php'), $endpointIndex);
        File::append(base_path('routes/api.php'), $endpointPatch);
        File::append(base_path('routes/api.php'), $endpointShow);

        $this->info('Route generated successfully.');
    }
}
