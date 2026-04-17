<?php

namespace Raham\DDDCRUDGen;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;

class MakeDDDCRUD extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:ddd';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a DDD-structured CRUD with all necessary files';

    protected $stubs_path = __DIR__ . '/../stubs';

    protected function findPathFor($name, $model)
    {
        $viewName = Str::kebab(Str::plural(Str::lower($model)));

        return match ($name) {
            'actions' => app_path("Domains/$model/Actions"),
            'controllers' => app_path("Domains/$model/Controllers"),
            'dtos' => app_path("Domains/$model/DTO"),
            'models' => app_path("Domains/$model/Models"),
            'repository-contracts' => app_path("Domains/$model/Repositories/Contracts"),
            'repositories' => app_path("Domains/$model/Repositories"),
            'requests' => app_path("Domains/$model/Requests"),
            'services' => app_path("Domains/$model/Services"),
            'responder-contracts' => app_path("Domains/$model/Responders/Contracts"),
            'responders' => app_path("Responders"),
            'views' => resource_path("views/{$viewName}"),
        };
    }

    protected function scaffoldModel($model, $fields)
    {
        $stub = File::get($this->stubs_path . '/models/Model.stub');

        $modelContent = str_replace('{{ class }}', $model, $stub);
        $modelContent = str_replace('{{ namespace }}', "App\\Domains\\$model\\Models", $modelContent);

        $fillableKeys = array_keys($fields['fields'] ?? []);
        if (!empty($fillableKeys)) {
            $fillableString = "    protected \$fillable = ['" . implode("', '", $fillableKeys) . "'];\n";
            $modelContent = str_replace('{{ fillable }}', $fillableString, $modelContent);
        } else {
            $modelContent = str_replace("{{ fillable }}\n", "", $modelContent);
        }

        File::put(app_path("Domains/{$model}/Models/{$model}.php"), $modelContent);
    }

    protected function scaffoldMigrations($model, $fields)
    {
        $fields = config("ddd.domains")[$model]['fields'];

        $stub = File::get($this->stubs_path . '/migrations/migration.stub');

        $columns = collect($fields)
            ->map(fn($type, $name) => "\t\t\t\$table->{$type}('{$name}');")
            ->implode("\n");

        $table = Str::plural(Str::lower($model));

        $migrationName = "_create_{$table}_table.php";

        $filename = database_path('migrations/' . date('Y_m_d_His') . $migrationName);

        $migration = str_replace('{{ columns }}', $columns, $stub);
        $migration = str_replace('{{ table }}', $table, $migration);

        File::put($filename, $migration);
    }

    protected function scaffoldFactories($model)
    {
        $stub = File::get($this->stubs_path . '/factories/Factory.stub');

        $factory = config('ddd.domains')[$model]['factory'];

        $factoryColumns = collect($factory)
            ->map(
                fn($value, $key) =>
                "\t\t\t'$key' => fake()->$value(),"
            )
            ->implode("\n");

        $factory = str_replace('{{ model }}', $model, $stub);
        $factory = str_replace('{{ factory }}', $factoryColumns, $factory);

        File::put(database_path("factories/{$model}Factory.php"), $factory);
    }

    protected function scaffoldSeeder()
    {
        $domains = config("ddd.domains");

        $domainNames = collect($domains)->keys();

        foreach ($domainNames as $i => $domainName) {
            if ($i == 0) {
                $stub = File::get($this->stubs_path . '/seeders/Seeder.stub');
            }

            $path = database_path("seeders/DatabaseSeeder.php");

            $seedCount = config('ddd.domains')[$domainName]['seed'];

            $stub = str_replace('// append imports', "use App\\Domains\\$domainName\\Models\\$domainName;
// append imports", $stub);

            $stub = str_replace('// append factories', "\t\t$domainName::factory()->count($seedCount)->create();
// append factories", $stub);

            File::put($path, $stub);

            $stub = File::get($path);
        }
    }

    protected function addBinds()
    {
        $domains = config("ddd.domains");

        $domainNames = collect($domains)->keys();

        foreach ($domainNames as $i => $domainName) {
            if ($i == 0) {
                $stub = File::get($this->stubs_path . '/providers/Provider.stub');
            }

            $model = Str::studly($domainName);

            $path = app_path("Providers/RepositoryServiceProvider.php");

            $stub = str_replace('// append imports', "use App\\Domains\\$model\\Repositories\\Contracts\\{$model}RepositoryInterface;\nuse App\\Domains\\$model\\Repositories\\{$model}Repository;\n// append imports", $stub);

            $stub = str_replace('// append bindings', "\t\t\$this->app->bind({$model}RepositoryInterface::class, {$model}Repository::class);\n// append bindings", $stub);

            File::put($path, $stub);

            $stub = File::get($path);
        }
    }

    protected function scaffoldWebRoutes()
    {
        $stub = File::get($this->stubs_path . '/routes/web.stub');
        $path = base_path("routes/web.php");

        File::put($path, $stub);
    }

    protected function addRoutes()
    {
        $domains = config("ddd.domains");

        $domainNames = collect($domains)->keys();

        foreach ($domainNames as $i => $domainName) {
            
            if ($i == 0) {
                $stub = File::get($this->stubs_path . '/routes/web.stub');
            }

            $route = Str::plural(Str::lower($domainName));

            $model = Str::studly($domainName);

            $controller = "\App\Domains\\{$model}\Controllers\\{$model}Controller";

            $path = base_path("routes/web.php");

            $stub = str_replace('// append', "\nRoute::resource('{$route}', {$controller}::class);
// append", $stub);

            File::put($path, $stub);

            $stub = File::get($path);
        }
    }

    protected function scaffoldStoreRequest($model)
    {
        $rules = config('ddd.domains')[$model]['rules'];

        $stub = File::get($this->stubs_path . '/requests/Request.stub');

        $rules = collect($rules)
            ->map(fn($rules, $column) => "\t\t\t'$column' => '$rules'")
            ->implode(",\n");

        $rulesStub = str_replace('{{ namespace }}', "App\\Domains\\$model\\Requests", $stub);
        $rulesStub = str_replace('{{ class }}', "StoreRequest", $rulesStub);
        $rulesStub = str_replace('{{ rules }}', $rules, $rulesStub);

        File::put($this->findPathFor('requests', $model) . "/StoreRequest.php", $rulesStub);
    }

    protected function scaffoldUpdateRequest($model)
    {
        $rules = config('ddd.domains')[$model]['rules'];

        $stub = File::get($this->stubs_path . '/requests/Request.stub');

        $rules = collect($rules)
            ->map(fn($rules, $column) => "\t\t\t'$column' => '$rules'")
            ->implode(",\n");

        $rulesStub = str_replace('{{ namespace }}', "App\\Domains\\$model\\Requests", $stub);

        $rulesStub = str_replace('{{ class }}', "UpdateRequest", $rulesStub);

        $rulesStub = str_replace('{{ rules }}', $rules, $rulesStub);

        File::put($this->findPathFor('requests', $model) . "/UpdateRequest.php", $rulesStub);
    }

    protected function scaffoldActions($model)
    {
        $actions = [
            'Index',
            'Create',
            'Store',
            'Show',
            'Update',
            'Destroy'
        ];

        $fields = config('ddd.domains')[$model]['fields'];

        $columns = collect($fields)
                    ->keys()
                    ->map(fn($field) => "\t\t\t'{$field}' => \$dto->{$field}")
                    ->implode(",\n");

        foreach ($actions as $action) {
            $actionStub = File::get($this->stubs_path . '/actions/Action.stub');

            $actionStub = str_replace('{{ namespace }}', "App\\Domains\\{$model}\\Actions", $actionStub);
            $actionStub = str_replace('{{ serviceImport }}', "use App\\Domains\\{$model}\\Services\\{$model}Service;", $actionStub);
            $actionStub = str_replace('{{ class }}', "{$action}Action", $actionStub);
            $actionStub = str_replace('{{ service }}', "{$model}Service", $actionStub);
            $actionStub = str_replace('{{ serviceVar }}', Str::camel("{$model}Service"), $actionStub);
            $actionStub = str_replace('{{ method }}', Str::camel($action), $actionStub);
            $actionStub = str_replace('{{ columns }}', $columns, $actionStub);

            File::put("{$this->findPathFor('actions', $model)}/{$action}Action.php", $actionStub);
        }
    }

    protected function scaffoldResponders($model)
    {
        $stub = File::get($this->stubs_path . '/responders/Responder.stub');

        $responder = str_replace('{{ model }}', $model, $stub);

        File::put($this->findPathFor('responders', $model) . "/Responder.php", $responder);
    }

    protected function scaffoldViews($model)
    {
        $dir = Str::kebab(Str::plural(Str::lower($model)));

        $views = [
            'index',
            'create',
            'show',
            'edit',
        ];

        foreach ($views as $view) {
            File::put(resource_path("views/{$dir}/{$view}.blade.php"), "Hello from {$view}");
        }
    }

    protected function scaffoldRepositoryInterfaces($model)
    {
        $stub = File::get($this->stubs_path . '/repositories/contracts/RepositoryInterface.stub');

        $repositoryInterface = str_replace('{{ useInterface }}', "App\\Domains\\$model\\Repositories\\Contracts\\{$model}RepositoryInterface", $stub);
        $repositoryInterface = str_replace('{{ class }}', $model . 'RepositoryInterface', $stub);
        $repositoryInterface = str_replace('{{ namespace }}', "App\\Domains\\$model\\Repositories\\Contracts", $repositoryInterface);
        $repositoryInterface = str_replace('{{ useStoreRequest }}', "use App\\Domains\\$model\\Requests\\StoreRequest;", $repositoryInterface);
        $repositoryInterface = str_replace('{{ useUpdateRequest }}', "use App\\Domains\\$model\\Requests\\UpdateRequest;", $repositoryInterface);
        $repositoryInterface = str_replace('{{ Model }}', $model, $repositoryInterface);
        $repositoryInterface = str_replace('{{ modelVar }}', "$" . Str::camel($model), $repositoryInterface);
        $repositoryInterface = str_replace('{{ storeRequest }}', 'StoreRequest', $repositoryInterface);
        $repositoryInterface = str_replace('{{ updateRequest }}', 'UpdateRequest', $repositoryInterface);

        File::put(app_path("Domains/{$model}/Repositories/Contracts/{$model}RepositoryInterface.php"), $repositoryInterface);
    }

    protected function scaffoldRepositories($model)
    {
        $stub = File::get($this->stubs_path . '/repositories/Repository.stub');

        $repository = str_replace('{{ class }}', $model . 'Repository', $stub);
        $repository = str_replace('{{ namespace }}', "App\\Domains\\$model\\Repositories", $repository);
        $repository = str_replace('{{ interface }}', "{$model}RepositoryInterface", $repository);
        $repository = str_replace('{{ useInterface }}', "use App\\Domains\\$model\\Repositories\\Contracts\\{$model}RepositoryInterface", $repository);
        $repository = str_replace('{{ useModel }}', "use App\\Domains\\$model\\Models\\{$model}", $repository);
        $repository = str_replace('{{ useStoreRequest }}', "use App\\Domains\\$model\\Requests\\StoreRequest;", $repository);
        $repository = str_replace('{{ useUpdateRequest }}', "use App\\Domains\\$model\\Requests\\UpdateRequest;", $repository);
        $repository = str_replace('{{ model }}', $model, $repository);
        $repository = str_replace('{{ modelVar }}', "$" . Str::camel($model), $repository);
        $repository = str_replace('{{ storeRequest }}', 'StoreRequest', $repository);
        $repository = str_replace('{{ updateRequest }}', 'UpdateRequest', $repository);

        File::put(app_path("Domains/{$model}/Repositories/{$model}Repository.php"), $repository);
    }

    protected function scaffoldServices($model)
    {
        $stub = File::get($this->stubs_path . '/services/Service.stub');

        $service = str_replace('{{ class }}', $model . 'Service', $stub);
        $service = str_replace('{{ namespace }}', "App\\Domains\\$model\\Services", $service);
        $service = str_replace('{{ useRepositoryInterface }}', "use App\\Domains\\$model\\Repositories\\Contracts\\$model" . "RepositoryInterface;", $service);
        $service = str_replace('{{ repositoryInterface }}', "{$model}RepositoryInterface", $service);
        $service = str_replace('{{ repositoryVar }}', Str::camel($model) . "Repository", $service);
        $service = str_replace('{{ model }}', $model, $service);

        File::put(app_path("Domains/{$model}/Services/{$model}Service.php"), $service);
    }

    protected function scaffoldControllers($model)
    {
        $stub = File::get($this->stubs_path . '/controllers/Controller.stub');

        $modelVar = Str::camel("$$model");

        $controller = str_replace('{{ model }}', $model, $stub);
        $controller = str_replace('{{ modelVar }}', $modelVar, $controller);

        File::put(app_path("Domains/{$model}/Controllers/{$model}Controller.php"), $controller);
    }

    protected function makeDirectories($model)
    {
        $directories = [
            'actions',
            'controllers',
            'dtos',
            'models',
            'repositories',
            'repository-contracts',
            'requests',
            'services',
            'responders',
            'views',
        ];

        if (!File::exists(app_path("Domains"))) {
            File::makeDirectory(app_path("Domains"), 0755, true);
        }

        foreach ($directories as $dirName) {
            $path = $this->findPathFor($dirName, $model);
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }
        }
    }

    protected function scaffoldRepositoryServiceProvider($model)
    {
        $stub = File::get($this->stubs_path . '/providers/Provider.stub');

        File::put(app_path("Providers/RepositoryServiceProvider.php"), $stub);
    }

    protected function registerRepositoryServiceProvider()
    {
        $stub = File::get($this->stubs_path . '/providers/providers.stub');

        File::put(base_path("bootstrap/providers.php"), $stub);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $domains = config('ddd.domains', []);

        $this->scaffoldSeeder();
        $this->scaffoldWebRoutes();
        $this->addRoutes();

        foreach ($domains as $domain => $fields) {

            if ($fields['scaffold']) {

                $model = Str::studly($domain);

                $this->makeDirectories($model);

                $this->registerRepositoryServiceProvider();

                $this->addBinds();

                $this->scaffoldControllers($model);

                $this->scaffoldResponders($model);

                $this->scaffoldViews($model);

                $this->scaffoldFactories($model);

                $this->scaffoldModel($model, $fields);

                if ($fields['migrate']) {
                    $this->scaffoldMigrations($model, $fields);
                }

                $this->scaffoldStoreRequest($model);

                $this->scaffoldUpdateRequest($model);

                $this->scaffoldActions($model);

                $this->scaffoldRepositoryInterfaces($model);

                $this->scaffoldRepositories($model);

                $this->scaffoldServices($model);
            }
        }

        $this->info('DDD CRUD generation completed successfully!');
    }
}
