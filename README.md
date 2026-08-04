# Beleza CRM

CRM SaaS multi-tenant para profissionais autônomos e pequenas equipes do setor de beleza. Reúne clientes, catálogo, agenda sem conflitos e financeiro operacional em uma interface mobile-first.

## Stack

- PHP 8.4, Laravel 13, Livewire 4, Blade e Flux UI
- Tailwind CSS 4 e Vite
- PostgreSQL 18 e Redis 8 em produção
- PHPUnit 12, Larastan/PHPStan e Laravel Pint

## Desenvolvimento local

Requisitos: PHP 8.4 com `curl`, `fileinfo`, `intl`, `mbstring`, `openssl`, `pdo_pgsql`, `pdo_sqlite`, `sodium` e `zip`; Composer 2; Node 24; Docker opcional para PostgreSQL/Redis.

```bash
docker compose up -d
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
composer dev
```

Neste workspace Windows também existe um runtime portátil ignorado pelo Git:

```powershell
.\.tools\php\php.exe artisan migrate:fresh --seed
npm run build
.\.tools\php\php.exe artisan serve
```

Usuário demonstrativo criado pelo seeder: `demo@belezacrm.test` / `password`.

### Supabase

Em ambientes conectados ao Supabase, use o Session Pooler exibido em **Connect** no painel do projeto e mantenha as tabelas da aplicação no schema privado `crm`:

```dotenv
DB_CONNECTION=pgsql
DB_URL=postgresql://postgres.PROJECT_REF:SENHA@REGIAO.pooler.supabase.com:5432/postgres
DB_SCHEMA=crm,public,extensions
DB_SSLMODE=require
```

O host direto `db.PROJECT_REF.supabase.co` exige conectividade IPv6 ou o add-on IPv4. O Session Pooler é a opção recomendada para o backend Laravel em redes IPv4.

## Qualidade

```bash
composer lint:check
composer types:check
php artisan test
```

Os testes padrão usam SQLite em memória. A migration PostgreSQL adiciona `btree_gist` e a exclusion constraint `appointments_no_overlap`; o pipeline de homologação deve executar também a suíte contra PostgreSQL para validar concorrência no banco real.

## Regras arquiteturais

- `Team` é a implementação concreta do estabelecimento/tenant do Starter Kit.
- Entidades do CRM recebem `tenant_id`, escopo global, Policies e FKs compostas.
- Casos de uso ficam em `app/Domain/*/Actions`; telas e futura API apenas os orquestram.
- Concluir um atendimento dispara `AppointmentCompleted` após commit e cria uma única conta a receber.
- Valores são inteiros em centavos e datas de agenda são persistidas em UTC.
- A API `/api/v1` está reservada, mas não expõe tokens ou recursos até a ativação do aplicativo móvel.
