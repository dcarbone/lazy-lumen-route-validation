<?php declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Class FakeParameter
 * @package App\Console\Commands
 */
class FakeParameter {
    /** @var int */
    public $position;
    /** @var string */
    public $name;

    /**
     * FakeParameter constructor.
     * @param int $pos
     * @param string $name
     */
    public function __construct(int $pos, string $name) {
        $this->position = $pos;
        $this->name = $name;
    }
}

/**
 * Class FakeRoute
 * @package App\Console\Commands
 */
class FakeRoute {
    /** @var string */
    public $method;
    /** @var string */
    public $rawRoute;
    /** @var bool */
    public $isAdmin;
    /** @var string */
    public $controller;
    /** @var string */
    public $action;

    /** @var array */
    public $errors = [];

    /** @var bool */
    public $usesClosure = false;

    /** @var string */
    public $filename;

    /** @var \App\Console\Commands\CheckRoutesCommand */
    private $cmd;

    /** @var \App\Console\Commands\FakeParameter[] */
    private $routeParams = [];
    /** @var array */
    private $middlewares = [];

    /**
     * FakeRoute constructor.
     * @param \App\Console\Commands\CheckRoutesCommand $cmd
     * @param string $file
     * @param string $method
     * @param string $rawRoute
     * @param $action
     */
    public function __construct(CheckRoutesCommand $cmd, string $file, string $method, string $rawRoute, $action) {
        $this->cmd = $cmd;
        $this->filename = $file;
        $this->method = $method;
        $this->rawRoute = $rawRoute;
        $this->isAdmin = false !== strpos($rawRoute, 'admin/');
        if ($this->isAdmin && false === strrpos($file, '_admin.php')) {
            $this->errors[] = sprintf(
                'Route "%s" in file "%s" is an admin route but is in a non-admin file',
                $this->rawRoute,
                $this->filename
            );
        }
        $this->parseRouteParams();
        $this->parseAction($action);
    }

    /**
     * @return \App\Console\Commands\FakeParameter[]
     */
    public function parameters(): array {
        return $this->routeParams;
    }

    /**
     * @return array
     */
    public function middlewares(): array {
        return $this->middlewares;
    }

    private function parseRouteParams(): void {
        if (false !== strpos($this->rawRoute, '{')) {
            preg_match_all('/(?:{)([a-zA-Z0-9]+)(?:})/', $this->rawRoute, $matches);
            if (2 === count($matches)) {
                foreach ($matches[1] as $i => $match) {
                    $this->routeParams[] = new FakeParameter($i, $match);
                }
            } else {
                $this->errors[] = sprintf(
                    'Route "%s" in file "%s" has parameters, but our regex failed...',
                    $this->rawRoute,
                    $this->filename
                );
            }
        }
    }

    /**
     * @param string $stmt
     */
    private function parseActionUsesStatement(string $stmt): void {
        if (false === ($pos = strpos($stmt, '@'))) {
            $this->errors[] = sprintf(
                'Route "%s" in file "%s" does not have a proper "uses" statement.  Expected "Controller@action", got %s',
                $this->rawRoute,
                $this->filename,
                $stmt
            );
        } else {
            [$this->controller, $this->action] = explode('@', $stmt, 2);
        }
    }

    /**
     * @param array $action
     */
    private function parseActionArray(array $action): void {
        if (isset($action['middleware'])) {
            $this->middlewares = $action['middleware'];
        }
        if (isset($action['uses'])) {
            $this->parseActionUsesStatement($action['uses']);
        }
    }

    /**
     * @param array|\Closure $action
     */
    private function parseAction($action): void {
        if (is_array($action)) {
            $this->parseActionArray($action);
        } else if (is_callable($action)) {
            $this->usesClosure = true;
        }
    }
}

/**
 * Class FakeRouter
 * @package App\Console\Commands
 */
class FakeRouter implements \Iterator, \Countable {
    private const GET     = 'get';
    private const POST    = 'post';
    private const PUT     = 'put';
    private const PATCH   = 'patch';
    private const DELETE  = 'delete';
    private const HEAD    = 'head';
    private const OPTIONS = 'options';

    /** @var int */
    public $getCount = 0;
    /** @var int */
    public $postCount = 0;
    /** @var int */
    public $putCount = 0;
    /** @var int */
    public $patchCount = 0;
    /** @var int */
    public $deleteCount = 0;
    /** @var int */
    public $headCount = 0;
    /** @var int */
    public $optionsCount = 0;

    /** @var \App\Console\Commands\CheckRoutesCommand */
    private $cmd;
    /** @var string */
    private $filename;

    /** @var \App\Console\Commands\FakeRoute[] */
    private $routes = [];

    /**
     * FakeRouter constructor.
     * @param \App\Console\Commands\CheckRoutesCommand $cmd
     */
    public function __construct(CheckRoutesCommand $cmd) {
        $this->cmd = $cmd;
    }

    /**
     * @param string $filename
     */
    public function setCurrentFile(string $filename): void {
        $this->filename = $filename;
    }

    /**
     * @param string $route
     * @param $action
     */
    public function get(string $route, $action): void {
        $this->routes[] = new FakeRoute($this->cmd, $this->filename, self::GET, $route, $action);
        $this->getCount++;
    }

    /**
     * @param string $route
     * @param $action
     */
    public function post(string $route, $action): void {
        $this->routes[] = new FakeRoute($this->cmd, $this->filename, self::POST, $route, $action);
        $this->postCount++;
    }

    /**
     * @param string $route
     * @param $action
     */
    public function put(string $route, $action): void {
        $this->routes[] = new FakeRoute($this->cmd, $this->filename, self::PUT, $route, $action);
        $this->putCount++;
    }

    /**
     * @param string $route
     * @param $action
     */
    public function patch(string $route, $action): void {
        $this->routes[] = new FakeRoute($this->cmd, $this->filename, self::PATCH, $route, $action);
        $this->patchCount++;
    }

    /**
     * @param string $route
     * @param $action
     */
    public function delete(string $route, $action): void {
        $this->routes[] = new FakeRoute($this->cmd, $this->filename, self::DELETE, $route, $action);
        $this->deleteCount++;
    }

    /**
     * @param string $route
     * @param $action
     */
    public function head(string $route, $action): void {
        $this->routes[] = new FakeRoute($this->cmd, $this->filename, self::HEAD, $route, $action);
        $this->headCount++;
    }

    /**
     * @param string $route
     * @param $action
     */
    public function options(string $route, $action): void {
        $this->routes[] = new FakeRoute($this->cmd, $this->filename, self::OPTIONS, $route, $action);
        $this->optionsCount++;
    }

    /**
     * @return \App\Console\Commands\FakeRoute|false
     */
    public function current() {
        return current($this->routes);
    }

    public function next() {
        next($this->routes);
    }

    /**
     * @return int|null
     */
    public function key() {
        return key($this->routes);
    }

    /**
     * @return bool
     */
    public function valid() {
        return null !== key($this->routes);
    }

    public function rewind() {
        reset($this->routes);
    }

    /**
     * @return int
     */
    public function count() {
        return count($this->routes);
    }
}

/**
 * Class CheckRoutesCommand
 * @package App\Console\Commands
 */
class CheckRoutesCommand extends Command {
    private const ROUTES_DIR      = __DIR__.'/../../../routes';
    private const CONTROLLERS_DIR = __DIR__.'/../../../app/Http/Controllers';

    /** @var string */
    protected $name = 'compute:check-routes';
    /** @var string */
    protected $description = 'This command will endeavour to ensure sanity between the route definition files, the controllers, and the swagger doc route definition';

    /**
     * @param string $fname
     * @return bool
     */
    protected function isAdmin(string $fname): bool {
        return false !== strrpos($fname, '_admin.php');
    }

    public function handle() {
        $routeDir = new \DirectoryIterator(self::ROUTES_DIR);
        $router = new FakeRouter($this);
        $fileCount = 0;
        foreach ($routeDir as $routeFile) {
            if (!$routeFile->isFile() || $routeFile->isDot()) {
                // skip non-files and dot files
                continue;
            }
            $name = $routeFile->getBasename();
            if (0 !== strpos($name, 'api_')) {
                // skip non-api files
                continue;
            }
            $fileCount++;
            $router->setCurrentFile($routeFile->getFilename());
            require $routeFile->getPathname();
        }

        $errorCount = 0;
        $adminCount = 0;
        $parameterCount = 0;
        foreach ($router as $route) {
            if ($route->usesClosure) {
                $this->warn(sprintf('Skipping %s as it uses a closure...', $route->rawRoute));
                continue;
            }
            try {
                // TODO: super inefficient...
                $reflClass = new \ReflectionClass("\\App\\Http\\Controllers\\{$route->controller}");
                if (!$reflClass->hasMethod($route->action)) {
                    $route->errors[] = sprintf('Route "%s" in file "%s" expects controller "%s" to have action "%s", but it does not.',
                        $route->rawRoute,
                        $route->filename,
                        $route->controller,
                        $route->action
                    );
                }
            } catch (\ReflectionException $e) {
                $route->errors[] = $e->getMessage();
            }
            if (0 !== ($cnt = count($route->errors))) {
                $errorCount += $cnt;
            }
            if ($route->isAdmin) {
                $adminCount++;
            }
            $parameterCount += count($route->parameters());

        }

        $this->info(sprintf('%d routes parsed from %d files.  There were:', count($router), $fileCount));
        if (0 === $errorCount) {
            $this->info('    0 definition errors!');
        } else {
            $this->warn(sprintf('    %d definition errors :(', $errorCount));
        }
        $this->info(<<<STRING
Across:
    {$router->getCount} GET routes
    {$router->postCount} POST routes
    {$router->putCount} PUT routes
    {$router->patchCount} PATCH routes
    {$router->deleteCount} DELETE routes
    {$router->headCount} HEAD routes
    {$router->optionsCount} OPTIONS routes
With:
    {$parameterCount} Route Parameters
STRING
        );

        if (0 !== $errorCount) {
            $this->info('Error List:');
            foreach ($router as $route) {
                if (0 < count($route->errors)) {
                    $this->warn(sprintf('    File: %s; Route: %s;', $route->filename, $route->rawRoute));
                    foreach ($route->errors as $i => $error) {
                        $this->warn("        {$i}: {$error}");
                    }
                }
            }
            return 1;
        }

        $this->info('Validating route Action definitions...');

        return 0;
    }
}
