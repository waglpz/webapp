<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Security;

use Psr\Http\Message\ServerRequestInterface;
use Waglpz\Webapp\AuthStorage;

use function Waglpz\Webapp\sortLongestKeyFirst;

final class Firewall implements Firewalled
{
    /** @var array<string,array<string>> */
    private array $regeln;
    /** @var array<string> */
    private array $currentRollen;

    /**
     * @param array<string,array<string>> $regeln        The key is regex pattern of the route and value a list of
     *                                                   allowed roles
     * @param array<string>|null          $currentRollen Rollen of current user
     */
    public function __construct(array $regeln, ?array $currentRollen = null)
    {
        $this->regeln        = $regeln;
        $this->currentRollen = $currentRollen ?? (new AuthStorage())->roles;
    }

    public function checkRules(ServerRequestInterface $request): void
    {
        $uri = $request->getRequestTarget();

        sortLongestKeyFirst($this->regeln);

        foreach ($this->regeln as $routePattern => $rollenAllowed) {
            if ($uri === '/') {
                return;
            }

            if ($routePattern === '/') {
                continue;
            }

            $prefix = $_SESSION['hash_uri'][$uri] ?? '';

            if (\preg_match('#^' . $prefix . $routePattern . '#', $uri) === 1) {
                if ($rollenAllowed === [Rollen::UNBEKANNT]) {
                    return;
                }

                $matchedRollen = \array_intersect($rollenAllowed, $this->currentRollen);
                if (\count($matchedRollen) >= 1) {
                    return;
                }

                throw new Forbidden();
            }
        }

        throw new Forbidden();
    }
}
