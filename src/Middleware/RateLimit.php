<?php
namespace App\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response as SlimResponse;

final class RateLimit implements MiddlewareInterface {
    public function __construct(
        private int $limit,
        private int $window,
        private string $bucket = 'default'
    ) {}

    public function process(ServerRequestInterface $req, RequestHandlerInterface $h):
    ResponseInterface {
        $ip = (string)($req->getServerParams()['REMOTE_ADDR'] ?? 'unknown');
        $file = sys_get_temp_dir() . '/books-api-rate-' . preg_replace('/\W+/','_',$this->bucket) . '.json';
        $now = time();
        $data = json_decode((string)@file_get_contents($file), true) ?: [];
        $b = $data[$ip] ?? ['count'=>0, 'reset'=>$now + $this->window];
        if ($b['reset'] <= $now) $b = ['count'=>0, 'reset'=>$now + $this->window];
        $b['count']++;
        $data[$ip] = $b;
        @file_put_contents($file, json_encode($data), LOCK_EX);
        if ($b['count'] > $this->limit) {
            $r = new SlimResponse(429);
            $r->getBody()->write(json_encode(['error'=>'Too many requests']));
            return $r->withHeader('Content-Type','application/json')
                ->withHeader('Retry-After', (string)max(1, $b['reset'] - $now));
        }
        return $h->handle($req);
    }
}