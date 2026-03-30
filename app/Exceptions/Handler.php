<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Throwable;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    use ApiResponse;
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
    // protected function invalidJson($request, ValidationException $exception)
    // {
    //     return $this->apiResponse(null, $exception->errors(), 0, $exception->status);
    // }
    protected function invalidJson($request, ValidationException $exception)
    {

        foreach ($exception->errors() as $field => $message) {
            $firstErrorMessage = $message[0];
        }

        return $this->apiResponse(null, $firstErrorMessage, 0, $exception->status);
    }
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return $this->apiResponse(null, 'Unauthorized', 0, 401);
        }
        return redirect()->guest(route('login'));
    }
    protected function notFound($request, NotFoundHttpException $exception)
    {
        if ($request->expectsJson()) {
            return $this->apiResponse(null, 'Route not found', 0, 404);
        }

        abort(404, 'Route not found');
    }
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ModelNotFoundException && $request->wantsJson()) {
            return $this->apiResponse(null, 'Model not found', 0, 404);
        }
        if ($exception instanceof NotFoundHttpException && $request->wantsJson()) {
            return $this->apiResponse(null, 'Endpoint not exist', 0, 404);
        }

        return parent::render($request, $exception);
    }
}
