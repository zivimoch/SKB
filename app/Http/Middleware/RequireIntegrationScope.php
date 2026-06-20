<?php

namespace App\Http\Middleware;

use App\Support\ProblemDetails;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireIntegrationScope
{
    public function handle(Request $request, Closure $next, string $scope): Response
    {
        $client = $request->attributes->get('integration_client', []);
        $scopes = $client['scopes'] ?? [];

        if (! in_array('*', $scopes, true) && ! in_array($scope, $scopes, true)) {
            return ProblemDetails::response(
                $request,
                403,
                'Scope tidak mencukupi',
                "Integration client membutuhkan scope {$scope}.",
                'https://docs.skb.go.id/problems/insufficient-scope',
                ['required_scope' => $scope]
            );
        }

        return $next($request);
    }
}
