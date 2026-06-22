<?php 
namespace App\Middleware; 
use Psr\Http\Message\ResponseInterface; 
use Psr\Http\Message\ServerRequestInterface; 
use Psr\Http\Server\MiddlewareInterface; 
use Psr\Http\Server\RequestHandlerInterface;

final class SecurityHeaders implements MiddlewareInterface { 
    public function process(ServerRequestInterface $req, RequestHandlerInterface $h): ResponseInterface { 
        return $h->handle($req) 
            ->withHeader('X-Content-Type-Options',     'nosniff') 
            ->withHeader('X-Frame-Options',            'DENY') 
            ->withHeader('Referrer-Policy',            'no-referrer-when-downgrade') 
            ->withHeader('Permissions-Policy',         'camera=(), microphone=()') 
            ->withHeader('Strict-Transport-Security',  'max-age=63072000; includeSubDomains') 
            ->withHeader('Content-Security-Policy',    "default-src 'self'; frame-ancestors 'none'"); 
    } 
} 