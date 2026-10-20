-- O utilizador da aplicação cria uma base de dados por tenant (tenant_<uuid>).
CREATE USER IF NOT EXISTS 'app'@'%' IDENTIFIED BY 'secret';
GRANT ALL PRIVILEGES ON *.* TO 'app'@'%';
FLUSH PRIVILEGES;
