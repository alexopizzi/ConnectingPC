<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Definizione di una rotta. I parametri del percorso usano {nome} oppure {nome:regex};
 * alcuni nomi hanno un vincolo predefinito (locale, id, token).
 */
final class Route
{
    private const DEFAULT_PATTERNS = [
        'locale' => '[a-z]{2,3}(?:-[A-Za-z0-9]{2,8})?',
        'id' => '[1-9][0-9]{0,9}',
        'slug' => '[a-z0-9-]+',
        'token' => '[A-Za-z0-9_-]{16,128}',
    ];

    public ?string $name = null;

    /** @var list<string> */
    public array $middleware = [];

    public ?string $area = null;

    public readonly string $regex;

    /** @var list<string> */
    public readonly array $parameters;

    /**
     * @param list<string> $methods
     * @param array{0: class-string, 1: string}|callable $handler
     */
    public function __construct(
        public readonly array $methods,
        public readonly string $path,
        public readonly mixed $handler,
    ) {
        $parameters = [];
        // La regex inline può contenere quantificatori con graffe: {id:[0-9]{1,5}}
        $regex = preg_replace_callback(
            '/\{([a-z_]+)(?::((?:[^{}]|\{[^{}]*\})+))?\}/',
            static function (array $m) use (&$parameters): string {
                $parameters[] = $m[1];
                $pattern = $m[2] ?? (self::DEFAULT_PATTERNS[$m[1]] ?? '[^/]+');

                return '(?P<' . $m[1] . '>' . $pattern . ')';
            },
            $path,
        );
        $this->regex = '#^' . $regex . '$#';
        $this->parameters = $parameters;
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function middleware(string ...$middleware): self
    {
        $this->middleware = [...$this->middleware, ...$middleware];

        return $this;
    }

    /** @return array<string, string>|null */
    public function match(string $path): ?array
    {
        if (!preg_match($this->regex, $path, $matches)) {
            return null;
        }
        $params = [];
        foreach ($this->parameters as $parameter) {
            $params[$parameter] = $matches[$parameter];
        }

        return $params;
    }
}
