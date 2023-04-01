<?php

namespace App\Services;

use App\Helpers\Version;
use Arr;
use Illuminate\Http\Request;
use Str;
use Symfony\Component\HttpFoundation\Response;

class SwaggerService
{
    private static array $data = [];
    private static bool $fired = false;

    public function __construct(private readonly Request $request, private readonly Response $response)
    {
        self::$fired = true;
    }

    public function processData(): void
    {
        Arr::add(self::$data, $this->getCollectionPath(), [
            'deprecated' => $this->request->route()->action['meta']['deprecated'] ?? false,
            'responses' => [],
        ]);

        $currentResponsePath = sprintf(
            '%s.responses.%s',
            $this->getCollectionPath(),
            $this->response->getStatusCode(),
        );

        $response = Arr::exists(self::$data, $currentResponsePath) ? Arr::get(self::$data, $currentResponsePath) :[];

        $response['headers'] = array_merge(
            $response['headers'] ?? [],
            Arr::except(
                $this->response->headers->all(),
                ['date', 'content-type']
            ),
        );

        $contentType = $this->response->headers->get('Content-Type');

        if (isset($response['content'][$contentType]['examples'])) {
            $response['content'][$contentType]['examples'][Str::uuid()->getUrn()] = [
                'value' => $this->response->getContent()
            ];
        } elseif (!isset($response['content'][$contentType])) {
            $response['content'][$contentType] = ['example' => ['value' =>$this->response->getContent()]];
        } else {
            $response['content'][$contentType]['examples'][Str::uuid()->getUrn()] = [
                'value' => $this->response->getContent()
            ];

            unset($response['content'][$contentType]['example']);
        }

        Arr::set(self::$data, $currentResponsePath, $response);
    }

    public static function dumpData(): void
    {
        if (!self::$fired) {
            return;
        }

        file_put_contents(storage_path('/documentation.json'), json_encode(array_merge([
            'paths' => self::$data
        ], self::getSwaggerHeader())));
    }

    private static function getSwaggerHeader(): array
    {
        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Cattr API Documentation',
                'contact' => [
                    'name' => 'Amazingcat LLC',
                    'email' => 'hi@cattr.app',
                ],
                'version' => (string) app(Version::class),
            ],
        ];
    }

    private function getCollectionPath(): string
    {
        return sprintf('/%s.%s', $this->request->route()?->uri(), strtolower($this->request->method()));
    }
}
