<?php
namespace App\Middleware;
use App\Auth\JwtService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response as SlimResponse;
final class AuthMiddleware implements MiddlewareInterface {
 public function __construct(private JwtService $jwt) {}
 public function process(ServerRequestInterface $req, RequestHandlerInterface $h):
ResponseInterface {
 $hdr = $req->getHeaderLine('Authorization');
 if (!preg_match('/^Bearer\s+(.+)$/i', $hdr, $m)) {
 return $this->fail('Missing or malformed token');
 }
 try {
 $payload = $this->jwt->verify($m[1]);
 } catch (\Throwable $e) {
 error_log('[Auth] '.$e->getMessage());
 return $this->fail('Invalid or expired token');
 }
 $req = $req->withAttribute('auth', $payload);
 return $h->handle($req);
 }
 private function fail(string $msg): ResponseInterface {
 $r = new SlimResponse(401);
 $r->getBody()->write(json_encode(['error'=>$msg]));
 return $r->withHeader('Content-Type','application/json')
 ->withHeader('WWW-Authenticate','Bearer');
 }
}