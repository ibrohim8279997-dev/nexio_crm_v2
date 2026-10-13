<?php
use App\Support\ApiException;
use App\Support\H;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Resolve session → tenant context, same-origin check, auth rate limit, portal scope.
        $middleware->api(prepend: [
            \App\Http\Middleware\ApiContext::class,
        ]);
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ApiException $e, $request) {
            $payload = ['code' => $e->errorCode, 'message' => $e->getMessage()];
            if ($e->details !== null) $payload['details'] = $e->details;
            return H::json(['error' => $payload], $e->status);
        });

        // API surface never leaks stack traces.
        $exceptions->render(function (Throwable $e, $request) {
            if (!$request->is('api/*')) return null;
            if ($e instanceof ApiException) return null;
            if ($e instanceof \Illuminate\Validation\ValidationException || $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) return null;
            report($e);
            return H::json(['error' => ['code' => 'internal', 'message' => 'Внутренняя ошибка сервера. Попробуйте позже.']], 500);
        });
    })->create();
