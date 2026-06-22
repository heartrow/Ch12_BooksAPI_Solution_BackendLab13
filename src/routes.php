<?php
declare(strict_types=1);

use App\Auth\JwtService;
use App\Controllers\AuthController;
use App\Controllers\BookController;
use App\Database;
use App\Middleware\RateLimit;
use App\Middleware\AuthMiddleware;
use App\Repositories\BookRepository;
use App\Repositories\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app): void {

    $pdo  = Database::get();
    $jwt  = new JwtService();

    $bookCtrl = new BookController(new BookRepository($pdo));
    $authCtrl = new AuthController(new UserRepository($pdo), $jwt);
    $auth     = new AuthMiddleware($jwt);

    // Public — no token required.
    $app->get('/', function (Request $r, Response $s) {
        $s->getBody()->write(json_encode([
            'name'    => 'Books REST API',
            'version' => '3.0.0 (JWT auth)',
            'endpoints' => [
                'public' => [
                    'POST /auth/register',
                    'POST /auth/login',
                    'GET  /api/books',
                    'GET  /api/books/{id}',
                ],
                'protected' => [
                    'GET    /auth/me',
                    'POST   /api/books',
                    'PUT    /api/books/{id}',
                    'DELETE /api/books/{id}   (admin only)',
                ],
            ],
        ]));
        return $s->withHeader('Content-Type', 'application/json');
    });

    // -- Auth routes -------------------------------------------------
    $loginMw = new RateLimit( 
        (int)($_ENV['LOGIN_RATE_LIMIT']     ?? 5), 
        (int)($_ENV['LOGIN_WINDOW_SECONDS'] ?? 60), 
        'login' 
    ); 

    $app->post('/auth/register', [$authCtrl, 'register']);
    $app->post('/auth/login', [$authCtrl, 'login'])->add($loginMw); 

    // /auth/me requires a valid JWT.
    $app->get('/auth/me', [$authCtrl, 'me'])->add($auth);

    // -- Books routes ------------------------------------------------
    // Read endpoints stay public (anyone can browse the catalogue).
    $app->get('/api/books',       [$bookCtrl, 'index']);
    $app->get('/api/books/{id}',  [$bookCtrl, 'show']);

    // Write endpoints require a JWT.
    $app->group('/api/books', function ($g) use ($bookCtrl) {
        $g->post  ('',        [$bookCtrl, 'create']);
        $g->put   ('/{id}',   [$bookCtrl, 'update']);
        $g->delete('/{id}',   [$bookCtrl, 'delete']);   // controller also enforces role=admin
    })->add($auth);

    // CORS pre-flight catch-all.
    $app->options('/{routes:.+}', fn(Request $r, Response $s) => $s);
};
