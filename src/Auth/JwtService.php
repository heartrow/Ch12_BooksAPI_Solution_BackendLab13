<?php
namespace App\Auth;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
final class JwtService {
 private string $secret;
 private string $algo = 'HS256';
 private int $ttl;
 private string $issuer;
 public function __construct() {
 $this->secret = $_ENV['JWT_SECRET'];
 $this->ttl = (int)($_ENV['JWT_TTL'] ?? 3600);
 $this->issuer = $_ENV['JWT_ISSUER'] ?? 'books-api';
 }
 public function issue(int $userId, array $extra = []): string {
 $now = time();
 $payload = array_merge([
 'iss' => $this->issuer, 'sub' => $userId,
 'iat' => $now, 'exp' => $now + $this->ttl,
 ], $extra);
 return JWT::encode($payload, $this->secret, $this->algo);
 }
 public function verify(string $token): array {
 return (array)JWT::decode($token, new Key($this->secret, $this->algo));
 }
 public function ttl(): int { return $this->ttl; }
}