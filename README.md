# Corporate Travel API

Microsserviço em **Laravel 12** para gerenciamento de pedidos de viagem corporativa.

## Decisões Técnicas

- **Service Layer** — lógica de negócio isolada em `TravelOrderService`.
- **Events + Listeners** — notificações desacopladas via `TravelOrderStatusUpdated` event (padrão Observer).
- **Laravel Sanctum** para autenticação via tokens Bearer — solução oficial, leve e sem dependências externas.
- **Enum PHP 8** (`TravelOrderStatus`) para status do pedido — type-safety e validação nativa.
- **Form Requests** para validação — desacoplamento do controller, reutilização e mensagens customizadas.
- **Policies** para autorização — controle granular por recurso (view, create, updateStatus, cancel).
- **API Resources** para serialização — formato de resposta padronizado e consistente.
- **Custom Exceptions** — `TravelOrderAlreadyApprovedException` para regras de negócio claras.
- **DB Transactions** — atomicidade nas operações de mudança de status.
- **Rate Limiting** — rotas de autenticação protegidas contra brute-force (`throttle:5,1`).
- **PHPStan (Larastan)** — análise estática nível 6 para type-safety.
- **GitHub Actions CI** — pipeline com Pint (lint), PHPStan (análise) e PHPUnit (testes).
- **Docker** (PHP 8.4-FPM + Nginx + MySQL 8) — ambiente reproduzível.

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

### 4. Instalar dependências e configurar a aplicação

```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

### 5. (Opcional) Popular banco com dados de exemplo

```bash
docker compose exec app php artisan db:seed --class=TravelOrderSeeder
```

Cria um usuário regular (`user@example.com`), um admin (`admin@example.com`) e pedidos de viagem em diferentes status. A senha padrão é `password`.

### 6. Verificar se está funcionando

```bash
curl http://localhost:8080/up
```

## Endpoints da API

Base URL: `http://localhost:8080/api/v1`

### Autenticação

| Método | Rota | Descrição | Auth |
|--------|------|-----------|------|
| `POST` | `/auth/register` | Registrar novo usuário | Não |
| `POST` | `/auth/login` | Login (retorna token) | Não |
| `POST` | `/auth/logout` | Logout (revoga token) | Sim |

### Pedidos de Viagem

| Método | Rota | Descrição | Auth |
|--------|------|-----------|------|
| `GET` | `/travel-orders` | Listar pedidos (com filtros) | Sim |
| `POST` | `/travel-orders` | Criar novo pedido | Sim |
| `GET` | `/travel-orders/{id}` | Consultar pedido | Sim |
| `PATCH` | `/travel-orders/{id}/status` | Atualizar status | Sim (admin) |
| `PATCH` | `/travel-orders/{id}/cancel` | Cancelar pedido | Sim (solicitante) |

### Autenticação nas requisições

Inclua o token no header `Authorization`:

```
Authorization: Bearer {seu-token}
```

### Exemplos de Uso

**Registrar usuário:**

```bash
curl -s -X POST http://localhost:8080/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"João Silva","email":"joao@example.com","password":"secret123","password_confirmation":"secret123"}'
```

```json
{"user":{"name":"João Silva","email":"joao@example.com","id":1},"token":"1|abc..."}
```

**Criar pedido de viagem:**

```bash
curl -s -X POST http://localhost:8080/api/v1/travel-orders \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"destination":"São Paulo","departure_date":"2026-04-01","return_date":"2026-04-05"}'
```

```json
{
  "data": {
    "id": 1,
    "requester": {"id": 1, "name": "João Silva"},
    "destination": "São Paulo",
    "departure_date": "2026-04-01",
    "return_date": "2026-04-05",
    "status": "requested",
    "created_at": "2026-02-27T21:00:00.000000Z",
    "updated_at": "2026-02-27T21:00:00.000000Z"
  }
}
```

**Aprovar pedido (admin):**

```bash
curl -s -X PATCH http://localhost:8080/api/v1/travel-orders/1/status \
  -H "Authorization: Bearer {admin-token}" \
  -H "Content-Type: application/json" \
  -d '{"status":"approved"}'
```

### Filtros disponíveis (GET /travel-orders)

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `status` | string | `requested`, `approved` ou `cancelled` |
| `destination` | string | Busca parcial por destino |
| `departure_from` | date | Data mínima de ida (YYYY-MM-DD) |
| `departure_to` | date | Data máxima de ida (YYYY-MM-DD) |
| `return_from` | date | Data mínima de volta (YYYY-MM-DD) |
| `return_to` | date | Data máxima de volta (YYYY-MM-DD) |
| `created_from` | date | Data mínima de criação do pedido (YYYY-MM-DD) |
| `created_to` | date | Data máxima de criação do pedido (YYYY-MM-DD) |

### Regras de Negócio

- O status de um novo pedido é sempre `requested`.
- O **solicitante** pode cancelar seu próprio pedido, desde que ainda esteja em `requested`.
- Apenas um **administrador** pode aprovar ou cancelar pedidos de outros usuários.
- O administrador **não pode** alterar o status de seus próprios pedidos.
- Não é possível **cancelar** um pedido que já foi **aprovado**.
- Cada usuário visualiza apenas seus **próprios pedidos** (admin vê todos).
- O solicitante recebe uma **notificação** (email + banco) quando seu pedido é aprovado ou cancelado por um admin.

## Variáveis de Ambiente

| Variável | Descrição | Valor Docker |
|----------|-----------|--------------|
| `DB_CONNECTION` | Driver do banco | `mysql` |
| `DB_HOST` | Host do banco | `db` (nome do serviço Docker) |
| `DB_PORT` | Porta do MySQL | `3306` |
| `DB_DATABASE` | Nome do banco | `corporate_travel_api` |
| `DB_USERNAME` | Usuário do banco | `laravel` |
| `DB_PASSWORD` | Senha do banco | `secret` |

## Executar os Testes

Os testes utilizam **SQLite em memória**, sem depender do MySQL:

```bash
# Rodar todos os testes
docker compose exec app php artisan test

# Rodar testes de um diretório específico
docker compose exec app php artisan test tests/Feature/Auth/
docker compose exec app php artisan test tests/Feature/TravelOrder/
```

## Qualidade de Código

```bash
# Análise estática (PHPStan nível 6)
docker compose exec app vendor/bin/phpstan analyse

# Formatação (Laravel Pint)
docker compose exec app vendor/bin/pint
```

## Estrutura do Projeto

```
├── app/
│   ├── Enums/                  # TravelOrderStatus (requested, approved, cancelled)
│   ├── Events/                 # TravelOrderStatusUpdated
│   ├── Exceptions/             # TravelOrderAlreadyApprovedException
│   ├── Http/
│   │   ├── Controllers/Api/V1/ # AuthController, TravelOrderController
│   │   ├── Requests/           # Form Requests (Auth, TravelOrder)
│   │   └── Resources/          # TravelOrderResource
│   ├── Listeners/              # SendTravelOrderNotification
│   ├── Models/                 # User, TravelOrder
│   ├── Notifications/          # TravelOrderStatusChanged
│   ├── Policies/               # TravelOrderPolicy
│   └── Services/               # TravelOrderService
├── database/
│   ├── factories/              # UserFactory, TravelOrderFactory
│   ├── migrations/             # Todas as migrations
│   └── seeders/                # TravelOrderSeeder
├── docker/
│   └── nginx/                  # Configuração do Nginx
├── routes/
│   └── api.php                 # Rotas da API (v1)
├── tests/Feature/
│   ├── Auth/                   # RegisterTest, LoginTest
│   └── TravelOrder/            # Create, Show, Index, UpdateStatus, Cancel, Notification
├── .github/workflows/ci.yml   # CI: lint + phpstan + testes
├── phpstan.neon                # Configuração do PHPStan (nível 6)
├── Dockerfile
├── docker-compose.yml
└── README.md
```

## Comandos Úteis

```bash
# Ver logs da aplicação
docker compose logs -f app

# Acessar o container PHP
docker compose exec app bash

# Parar todos os containers
docker compose down

# Parar e remover volumes (reset do banco)
docker compose down -v
```
