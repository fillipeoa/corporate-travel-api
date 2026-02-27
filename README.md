# Corporate Travel API

Microsserviço em **Laravel 12** para gerenciamento de pedidos de viagem corporativa.

## Pré-requisitos

- [Docker](https://docs.docker.com/get-docker/) (v20+)
- [Docker Compose](https://docs.docker.com/compose/install/) (v2+)

## Instalação e Execução

### 1. Clonar o repositório

```bash
git clone https://github.com/seu-usuario/corporate-travel-api.git
cd corporate-travel-api
```

### 2. Configurar variáveis de ambiente

```bash
cp .env.example .env
```

> As variáveis de banco já estão configuradas para o ambiente Docker. Ajuste conforme necessário.

### 3. Subir os containers

```bash
docker compose up -d
```

Isso irá iniciar 3 serviços:

| Serviço | Container | Porta |
|---------|-----------|-------|
| PHP-FPM | `corporate-travel-app` | 9000 (interna) |
| Nginx | `corporate-travel-webserver` | **8080** → 80 |
| MySQL 8 | `corporate-travel-db` | **3307** → 3306 |

> **Nota:** Se a porta `8080` já estiver em uso, altere o mapeamento em `docker-compose.yml` (ex: `"8081:80"`).

### 4. Verificar se está funcionando

```bash
curl http://localhost:8080/up
```

Deve retornar **HTTP 200** confirmando que a aplicação está online.

## Variáveis de Ambiente

| Variável | Descrição | Valor Docker |
|----------|-----------|--------------|
| `DB_CONNECTION` | Driver do banco | `mysql` |
| `DB_HOST` | Host do banco | `db` (nome do serviço Docker) |
| `DB_PORT` | Porta do MySQL | `3306` |
| `DB_DATABASE` | Nome do banco | `corporate_travel_api` |
| `DB_USERNAME` | Usuário do banco | `laravel` |
| `DB_PASSWORD` | Senha do banco | `secret` |

