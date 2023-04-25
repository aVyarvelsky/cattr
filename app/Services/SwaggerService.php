<?php

namespace App\Services;

use App\Docs\RequestHeader;
use App\Docs\ResponseExampleFilter;
use App\Docs\ResponseHeader;
use App\Docs\UsesClass;
use App\Docs\UsesMethod;
use App\Helpers\Version;
use App\Http\Kernel;
use Arr;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Reflector;
use Symfony\Component\HttpFoundation\Response;

class SwaggerService
{
    private static array $data = [];
    private static bool $fired = false;

    private array $parameters = [];
    private array $headers = [];
    private array $exampleFilters = [];
    private array $responseSchema = [];

    public function __construct(private readonly Request $request, private readonly Response $response)
    {
        self::$fired = true;
    }

    public static function dumpData(): void
    {
        if (!self::$fired) {
            return;
        }

        file_put_contents(storage_path('/documentation.json'), json_encode(array_merge([
            'paths' => self::$data,
        ], self::getSwaggerHeader())));
    }

    private static function getSwaggerHeader(): array
    {
        return [
            'openapi' => '3.0.1',
            'info'    => [
                'title'   => 'Cattr API Documentation',
                'contact' => [
                    'name'  => 'Amazingcat LLC',
                    'email' => 'hi@cattr.app',
                ],
                'version' => (string)app(Version::class),
            ],
            'servers' => [
                [
                    'url'         => 'http://localhost:8000',
                    'description' => 'Local server served by Artisan',
                ],
                [
                    'url'         => 'https://demo.cattr.app/api',
                    'description' => 'Demo Cattr server',
                ],
            ],
        ];
    }

    final public function processData(): void
    {
        $this->processClassAttributes(Kernel::class);
        $this->processMiddlewares();
        $this->processHeaderExamples();
        $this->processClassAttributes($this->request->route()->getController());
        $this->processMethodAttributes(
            $this->request->route()->getController(),
            $this->request->route()->getActionMethod(),
        );

        if (!Arr::has(self::$data, $this->getCollectionPath())) {
            Arr::set(self::$data, $this->getCollectionPath(), [
            'deprecated'  => $this->request->route()->action['meta']['deprecated'] ?? false,
            'tags'  => $this->request->route()->action['meta']['tags'] ?? [],
            'operationId' => $this->request->route()->getName(),
            'responses'   => [],
            'parameters'  => $this->parameters,
            ]);
        }

        $currentResponsePath = sprintf(
            '%s.responses',
            $this->getCollectionPath(),
        );

        $responses = Arr::get(self::$data, $currentResponsePath);

        $responses[$this->response->getStatusCode()] = [
            'content' => [
                $this->response->headers->get('Content-Type') => [
                    'example' => $this->response->headers->get('Content-Type') === 'application/json' &&
                    $this->response->getContent() ?
                        $this->filterJsonContent($this->response->getContent()) :
                        $this->response->getContent(),
                    'schema' => $this->responseSchema,
                ],
            ],
            'headers' => $this->headers,
        ];

        Arr::set(self::$data, $currentResponsePath, $responses);
    }

    private function processClassAttributes(object|string $class): void
    {
        try {
            $reflection = new ReflectionClass($class);
        } catch (ReflectionException) {
            return;
        }

        $this->processAttributes($reflection);

        if ($parent = $reflection->getParentClass()) {
            $this->processClassAttributes($parent->getName());
        }
    }

    private function processAttributes(Reflector $reflection): void
    {
        $this->parameters = array_merge(
            $this->parameters,
            array_map(
                static fn($el) => $el->newInstance()->dump(),
                $reflection->getAttributes(RequestHeader::class),
            ),
        );

        $this->headers = array_merge(
            $this->headers,
            Arr::keyBy(
                array_map(
                    static fn($el) => $el->newInstance()->dump(),
                    $reflection->getAttributes(ResponseHeader::class),
                ),
                'name',
            ),
        );

        $this->exampleFilters = array_merge(
            $this->exampleFilters,
            array_map(
                static fn($el) => $el->newInstance()->attributePath,
                $reflection->getAttributes(ResponseExampleFilter::class),
            ),
        );

        $usesClasses = array_map(
            static fn($el) => $el->newInstance()->class,
            $reflection->getAttributes(UsesClass::class),
        );

        foreach ($usesClasses as $class) {
            $this->processClassAttributes($class);
        }

        $usesMethods = array_map(
            static fn($el) => [$el->newInstance()->class, $el->newInstance()->method],
            $reflection->getAttributes(UsesMethod::class),
        );

        foreach ($usesMethods as $method) {
            $this->processMethodAttributes($method[0], $method[1]);
        }
    }

    private function processMiddlewares(): void
    {
        $router = app(Router::class);

        $middlewares = array_unique(
            array_map(
                static fn($e) => explode(':', $e, 2)[0],
                array_merge(
                    app(Kernel::class)->getMiddleware(),
                    $router->gatherRouteMiddleware($this->request->route()),
                ),
            ),
        );

        foreach ($middlewares as $middleware) {
            $this->processClassAttributes($middleware);
        }
    }

    private function processHeaderExamples(): void
    {
        foreach ($this->request->headers as $header => $value) {
            $key = array_search($header, array_map('strtolower', array_column($this->parameters, 'name')));

            if ($key === false || $this->parameters[$key]['x-masked']) {
                return;
            }

            $this->parameters[$key]['example'] = $value;
        }

        foreach ($this->response->headers as $header => $value) {
            $key = array_search($header, array_map('strtolower', array_keys($this->parameters)));

            if ($key === false || $this->headers[$key]['x-masked']) {
                return;
            }

            $this->headers[$key]['example'] = $value;
        }
    }

    private function processMethodAttributes(object|string $object, string $method): void
    {
        try {
            $reflection = new ReflectionMethod($object, $method);
        } catch (ReflectionException) {
            return;
        }

        $this->processAttributes($reflection);
    }

    private function getCollectionPath(): string
    {
        return sprintf('/%s.%s', $this->request->route()?->uri(), strtolower($this->request->method()));
    }

    private function filterJsonContent(string $content): string
    {
        $result = json_decode($content, true);

        foreach ($this->exampleFilters as $filterPath) {
            if (Arr::has($result, sprintf('data.%s', $filterPath))) {
                data_set($result, sprintf('data.%s', $filterPath), '<masked>');
            }

            if (Arr::has($result, $filterPath)) {
                data_set($result, $filterPath, '<masked>');
            }
        }

        return json_encode($result);
    }
}
