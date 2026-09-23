-- Database separato per i test automatici (PHPUnit). Eseguito solo alla prima creazione del volume.
-- Su un volume esistente: docker compose exec db mariadb -uroot -p < docker/mariadb/init/01-test-database.sql
CREATE DATABASE IF NOT EXISTS connectingpc_test CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci;
GRANT ALL PRIVILEGES ON connectingpc_test.* TO 'connectingpc'@'%';
FLUSH PRIVILEGES;
