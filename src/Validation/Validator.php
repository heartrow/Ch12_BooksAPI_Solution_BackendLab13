<?php
namespace App\Validation;
final class Validator
{
 private array $rules = []; private array $required = [];
 public function field(string $n, callable $r, string $err): self {
 $this->rules[$n] = ['rule'=>$r, 'error'=>$err]; return $this;
 }
 public function required(string ...$n): self {
 $this->required = array_merge($this->required, $n); return $this;
 }
 public function validate(array $b, bool $partial = false): array {
 $errs = [];
 if (!$partial) foreach ($this->required as $n)
 if (!array_key_exists($n, $b)) $errs[$n] = "$n is required";
 foreach ($this->rules as $n => $r) {
 if (!array_key_exists($n, $b)) continue;
 if (!$r['rule']($b[$n])) $errs[$n] = $r['error'];
 }
 return $errs;
 }
 public static function nonEmptyString(int $max = 255): callable {
 return fn($v) => is_string($v) && trim($v) !== '' && mb_strlen($v) <= $max;
 }
 public static function intRange(int $a, int $b): callable {
 return fn($v) => is_numeric($v) && (int)$v >= $a && (int)$v <= $b;
 }
 public static function email(): callable {
 return fn($v) => is_string($v) && filter_var($v, FILTER_VALIDATE_EMAIL) !== false;
 }
}