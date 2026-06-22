<?php
namespace App\Controllers; 
use App\Validation\Validator;
use App\Repositories\BookRepository; 
use Psr\Http\Message\ResponseInterface as Response; 
use Psr\Http\Message\ServerRequestInterface as Request; 
  
final class BookController { 
    public function __construct(private BookRepository $books) {} 
  
    public function index(Request $r, Response $s): Response { 
        $p   = $r->getQueryParams(); 
        $rows = $this->books->all((string)($p['q'] ?? ''), (int)($p['limit'] ?? 0)); 
        return $this->json($s, ['count'=>count($rows), 'data'=>$rows]); 
    } 
    public function show(Request $r, Response $s, array $a): Response { 
        $book = $this->books->find((int)$a['id']); 
        return $book ? $this->json($s, $book) 
                     : $this->json($s, ['error'=>'not found'], 404); 
    } 
    public function create(Request $r, Response $s): Response { 

        $body = (array)$r->getParsedBody(); 
        $auth    = (array)$r->getAttribute('auth', []); 
        $errors = (new Validator()) 
            ->required('title', 'author', 'year') 
            ->field('title',  Validator::nonEmptyString(200), 'title must be 1-200 chars') 
            ->field('author', Validator::nonEmptyString(150), 'author must be 1-150 chars') 
            ->field('year',   Validator::intRange(1000, (int)date('Y')), 'year must be 1000..now') 
            ->field('genre',  Validator::nonEmptyString(80),  'genre must be ≤ 80 chars') 
            ->validate($body); 
        if ($errors) return $this->json($s, ['errors'=>$errors], 400); 
        $id = $this->books->create($body, $auth['sub']); 
        return $this->json($s, ['message'=>'Book created', 'data'=>$this->books->find($id)], 201) 
                    ->withHeader('Location', '/api/books/' . $id); 
    }



     /* update() and delete() follow the same pattern — see solution */ 
    /** PUT /api/books/{id} — full or partial update */ 
  public function update(Request $req, Response $res, array $args): Response { 
        $id   = (int)$args['id']; 
        $book = $this->books->find($id); 
        if (!$book) return $this->json($res, ['error'=>'Not found'], 404); 
    
        $auth    = (array)$req->getAttribute('auth', []); 
        $isOwner = (int)$book['created_by'] === (int)($auth['sub'] ?? 0); 
        $isAdmin = ($auth['role'] ?? 'member') === 'admin'; 
        if (!$isOwner && !$isAdmin) return $this->json($res, ['error'=>'Forbidden'], 403); 
  
        $body = (array)($req->getParsedBody() ?? []); 
        $errors = $this->validate($body, false); // requireAll: false for partial updates
        if (!empty($errors)) {
            return $this->json($res, ['errors' => $errors], 400); 
        }
  
        // 2. Pass the validated data to the Repository to update MySQL
        // (Assuming your repository has an update method that takes the ID and the data array)
        $this->books->update($id, $body);  
        
        // 3. Fetch the fresh data to return to the user
        $updatedBook = $this->books->find($id);
        
        return $this->json($res, ['message' => 'Book updated', 'data' => $updatedBook]); 
    } 

    /** DELETE /api/books/{id} */ 
    public function delete(Request $req, Response $res, array $args): Response { 
        $auth = (array)$req->getAttribute('auth', []); 
        if (($auth['role'] ?? 'member') !== 'admin') { 
            return $this->json($res, ['error' => 'Admins only'], 403); 
        } 

        $id = (int)($args['id'] ?? 0); 
        
        // 1. Fetch it first so we can return the deleted data in the response
        $deletedBook = $this->books->find($id);
        if (!$deletedBook) {
            return $this->json($res, ['error' => "Book {$id} not found"], 404); 
        }
        
        // 2. Tell the Repository to delete it from MySQL
        $this->books->delete($id);  
        
        return $this->json($res, ['message' => 'Book deleted', 'data' => $deletedBook]); 
    }

    private function validate(array $body, bool $requireAll): array { 
        $errors = []; 
        $rules = [ 
          'title'  => fn($v) => is_string($v) && trim($v) !== '', 
          'author' => fn($v) => is_string($v) && trim($v) !== '', 
          'year'   => fn($v) => is_numeric($v) && (int)$v >= 1000 && (int)$v <= (int)date('Y'), 
        ]; 
        foreach ($rules as $f => $check) { 
            if ($requireAll && !array_key_exists($f, $body)) { $errors[$f]="$f is required"; 
                continue; 
                } 
            if (array_key_exists($f, $body) && !$check($body[$f])) $errors[$f] = "$f is invalid"; 
        } 
        return $errors; 
    } 
    
    private function json(Response $r, $data, int $status = 200): Response { 
        $r->getBody()->write(json_encode( 
            $data, 
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE 
            | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT 
        )); 
        return $r->withHeader('Content-Type','application/json; charset=utf-8') 
                ->withStatus($status); 
    } 
} 


