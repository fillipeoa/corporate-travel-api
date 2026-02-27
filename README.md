# Corporate Travel API

Microsserviço em **Laravel 12** para gerenciamento de pedidos de viagem corporativa.

## Decisões Técnicas

- **Laravel Sanctum** para autenticação via tokens Bearer — solução oficial do ecossistema Laravel, leve e sem dependências externas.
- **Enum PHP 8** (`TravelOrderStatus`) para status do pedido — type-safety e validação nativa.
- **Form Requests** para validação — desacoplamento do controller, reutilização e mensagens customizadas.
- **Policies** para autorização — controle granular por recurso (view, create, updateStatus).
- **API Resources** para serialização — formato de resposta padronizado e consistente.
- **Notifications (mail + database)** — o solicitante é notificado automaticamente quando seu pedido é aprovado ou cancelado.
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

> **Nota:** Se a porta `8080` já estiver em uso, altere o mapeamento em `docker-compose.yml` (ex: `"8081:80"`).

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

Deve retornar **HTTP 200** confirmando que a aplicação está online.

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

# Filtrar por nome do teste
docker compose exec app php artisan test --filter=test_user_can_register_with_valid_data
```

## Estrutura do Projeto

```
├── app/
│   ├── Enums/                  # TravelOrderStatus (requested, approved, cancelled)
│   ├── Http/
│   │   ├── Controllers/Api/V1/ # AuthController, TravelOrderController
│   │   ├── Requests/           # Form Requests (Auth, TravelOrder)
│   │   └── Resources/          # TravelOrderResource
│   ├── Models/                 # User, TravelOrder
│   ├── Notifications/          # TravelOrderStatusChanged
│   └── Policies/               # TravelOrderPolicy
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
│   └── TravelOrder/            # Create, Show, Index, UpdateStatus, Notification
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

# Formatar código com Laravel Pint
docker compose exec app vendor/bin/pint
```
