<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeDomain extends Command
{
    protected $signature = 'make:domain {domain} {name} {--force}';
    protected $description = 'Create Request, DTO, Action and Controller for a domain use case';

    public function handle(): int
    {
        $domain = Str::studly($this->argument('domain'));
        $name = Str::studly($this->argument('name'));
        $force = (bool) $this->option('force');

        $files = [
            $this->requestPath($domain, $name) => $this->requestStub($domain, $name),
            $this->dtoPath($domain, $name) => $this->dtoStub($domain, $name),
            $this->actionPath($domain, $name) => $this->actionStub($domain, $name),
            $this->controllerPath($domain, $name) => $this->controllerStub($domain, $name),
        ];

        $existingFiles = array_filter(
            array_keys($files),
            static fn (string $path): bool => File::exists($path)
        );

        if (! empty($existingFiles) && ! $force) {
            $this->error('Some files already exist. Use --force to overwrite them.');

            foreach ($existingFiles as $existingFile) {
                $this->line($this->relativePath($existingFile));
            }

            return self::FAILURE;
        }

        foreach ($files as $path => $content) {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $content);
            $this->info('Created: ' . $this->relativePath($path));
        }

        $this->newLine();
        $this->info("Domain scaffolding created for {$domain}/{$name}");

        return self::SUCCESS;
    }

    private function requestPath(string $domain, string $name): string
    {
        return app_path("Http/Requests/Api/V1/{$domain}/{$name}Request.php");
    }

    private function dtoPath(string $domain, string $name): string
    {
        return app_path("Domain/{$domain}/DTOs/{$name}Data.php");
    }

    private function actionPath(string $domain, string $name): string
    {
        return app_path("Domain/{$domain}/Actions/{$name}Action.php");
    }

    private function controllerPath(string $domain, string $name): string
    {
        return app_path("Http/Controllers/Api/V1/{$domain}/{$name}Controller.php");
    }

    private function requestStub(string $domain, string $name): string
    {
        return <<<PHP
<?php

namespace App\Http\Requests\Api\V1\\{$domain};

use Illuminate\Foundation\Http\FormRequest;

class {$name}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            //
        ];
    }
}

PHP;
    }

    private function dtoStub(string $domain, string $name): string
    {
        return <<<PHP
<?php

namespace Domain\\{$domain}\DTOs;

use App\Http\Requests\Api\V1\\{$domain}\\{$name}Request;

class {$name}Data
{
    public function __construct(
        //
    ) {
    }

    public static function fromRequest({$name}Request \$request): self
    {
        \$validated = \$request->validated();

        return new self(
            //
        );
    }
}

PHP;
    }

    private function actionStub(string $domain, string $name): string
    {
        return <<<PHP
<?php

namespace Domain\\{$domain}\Actions;

use Domain\\{$domain}\DTOs\\{$name}Data;

class {$name}Action
{
    public function execute({$name}Data \$data): mixed
    {
        return null;
    }
}

PHP;
    }

    private function controllerStub(string $domain, string $name): string
    {
        return <<<PHP
<?php

namespace App\Http\Controllers\Api\V1\\{$domain};

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\\{$domain}\\{$name}Request;
use App\Support\Responses\ApiResponse;
use Domain\\{$domain}\Actions\\{$name}Action;
use Domain\\{$domain}\DTOs\\{$name}Data;
use Illuminate\Http\JsonResponse;

class {$name}Controller extends Controller
{
    public function __invoke(
        {$name}Request \$request,
        {$name}Action \$action
    ): JsonResponse {
        \$data = {$name}Data::fromRequest(\$request);

        \$result = \$action->execute(\$data);

        return ApiResponse::success(\$result);
    }
}

PHP;
    }

    private function relativePath(string $absolutePath): string
    {
        $normalizedAppPath = str_replace('\\', '/', app_path());
        $normalizedAbsolutePath = str_replace('\\', '/', $absolutePath);

        if (str_starts_with($normalizedAbsolutePath, $normalizedAppPath . '/')) {
            return 'app/' . substr($normalizedAbsolutePath, strlen($normalizedAppPath) + 1);
        }

        return $normalizedAbsolutePath;
    }
}
