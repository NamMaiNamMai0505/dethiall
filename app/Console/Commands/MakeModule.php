<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeModule extends Command
{
    protected $signature = 'module:scaffold {name}';
    protected $description = 'Tạo module theo convention dev cũ (controllers, models, requests, providers, routes, views)';

    public function handle()
    {
        $name = $this->argument('name');
        $basePath = base_path("modules/{$name}");

        // Các thư mục con cần tạo
        $dirs = [
            'Controllers',
            'Models',
            'Requests',
            'Routes',
            'Views',
            'Providers',
        ];

        foreach ($dirs as $dir) {
            $path = "{$basePath}/{$dir}";
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
                $this->info("Đã tạo thư mục: {$path}");
            }
        }

        // Routes/web.php
        $routeFile = "{$basePath}/Routes/web.php";
        if (!File::exists($routeFile)) {
            File::put($routeFile, "<?php\n\nuse Illuminate\Support\Facades\Route;\n\n// Routes cho module {$name}\n");
            $this->info("Đã tạo file: {$routeFile}");
        }

        // Models/{name}.php
        $modelFile = "{$basePath}/Models/{$name}.php";
        if (!File::exists($modelFile)) {
            $modelStub = <<<PHP
<?php

namespace Modules\\{$name}\\Models;

use Illuminate\\Database\\Eloquent\\Model;

class {$name} extends Model
{
    protected \$guarded = [];
}
PHP;
            File::put($modelFile, $modelStub);
            $this->info("Đã tạo file: {$modelFile}");
        }

        // Controllers/{name}Controller.php
        $controllerFile = "{$basePath}/Controllers/{$name}Controller.php";
        if (!File::exists($controllerFile)) {
            $controllerStub = <<<PHP
<?php

namespace Modules\\{$name}\\Controllers;

use App\\Http\\Controllers\\Controller;

class {$name}Controller extends Controller
{
    public function index()
    {
        return view(strtolower('{$name}') . '::index');
    }
}
PHP;
            File::put($controllerFile, $controllerStub);
            $this->info("Đã tạo file: {$controllerFile}");
        }

        // Providers/{name}ServiceProvider.php
        $providerFile = "{$basePath}/Providers/{$name}ServiceProvider.php";
        if (!File::exists($providerFile)) {
            $providerStub = <<<PHP
<?php

namespace Modules\\{$name}\\Providers;

use Illuminate\\Support\\ServiceProvider;

class {$name}ServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Load routes và views cho module
        \$this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        \$this->loadViewsFrom(__DIR__ . '/../Views', strtolower('{$name}'));
    }
}
PHP;
            File::put($providerFile, $providerStub);
            $this->info("Đã tạo file: {$providerFile}");
        }

        // Requests/Create{name}Request.php
        $createRequestFile = "{$basePath}/Requests/Create{$name}Request.php";
        if (!File::exists($createRequestFile)) {
            $createStub = <<<PHP
<?php

namespace Modules\\{$name}\\Requests;

use Illuminate\\Foundation\\Http\\FormRequest;

class Create{$name}Request extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // TODO: define rules
        ];
    }
}
PHP;
            File::put($createRequestFile, $createStub);
            $this->info("Đã tạo file: {$createRequestFile}");
        }

        // Requests/Update{name}Request.php
        $updateRequestFile = "{$basePath}/Requests/Update{$name}Request.php";
        if (!File::exists($updateRequestFile)) {
            $updateStub = <<<PHP
<?php

namespace Modules\\{$name}\\Requests;

use Illuminate\\Foundation\\Http\\FormRequest;

class Update{$name}Request extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // TODO: define rules
        ];
    }
}
PHP;
            File::put($updateRequestFile, $updateStub);
            $this->info("Đã tạo file: {$updateRequestFile}");
        }

        // Views/index.blade.php
        $viewFile = "{$basePath}/Views/index.blade.php";
        if (!File::exists($viewFile)) {
            File::put($viewFile, "<h1>Module {$name} - Index</h1>");
            $this->info("Đã tạo file: {$viewFile}");
        }
        // --- Thêm provider vào config/app.php ---
        $configFile = config_path('app.php');
        $providerClass = "Modules\\{$name}\\Providers\\{$name}ServiceProvider::class";

        $appConfig = File::get($configFile);

        if (strpos($appConfig, $providerClass) === false) {
            // tìm đoạn 'providers' => [
            $newConfig = preg_replace(
                "/('providers'\s*=>\s*\[)/",
                "$1\n        {$providerClass},",
                $appConfig
            );

            File::put($configFile, $newConfig);
            $this->info("✅ Đã đăng ký ServiceProvider cho module {$name} vào config/app.php");
        } else {
            $this->info("ℹ️ ServiceProvider cho module {$name} đã tồn tại trong config/app.php");
        }
        $this->info("✅ Module {$name} scaffolded thành công!");
    }
}
