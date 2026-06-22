USE books_api;
ALTER TABLE books ADD COLUMN created_by INT NULL AFTER genre,
 ADD CONSTRAINT fk_books_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET
NULL;
UPDATE books SET created_by = 1 WHERE id IN (1, 3);
UPDATE books SET created_by = 2 WHERE id = 2;
CREATE TABLE IF NOT EXISTS audit_log (
 id BIGINT AUTO_INCREMENT PRIMARY KEY,
 occurred_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 actor_id INT NULL,
 action VARCHAR(50) NOT NULL,
 target VARCHAR(80) NULL,
 ip_address VARCHAR(45) NULL,
 detail VARCHAR(500) NULL,
 INDEX idx_action (action),
 INDEX idx_actor (actor_id)
) ENGINE=InnoDB;