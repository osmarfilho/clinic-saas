# Clinic SaaS - Agent Instructions

## Projeto

Sistema SaaS para gestão de clínicas.

Stack:

- Laravel 12+
- Vue 3
- TypeScript
- PostgreSQL
- Docker
- Sanctum
- Spatie Permission

## Arquitetura

- Multi-tenant por clínica
- RBAC com Spatie Permission
- Auditoria de ações críticas
- API REST
- Frontend SPA

## Regras Gerais

- Nunca criar gambiarras.
- Corrigir a causa raiz.
- Não remover testes para fazer build passar.
- Não remover validações de segurança.
- Não usar hardcodes.
- Não expor credenciais.
- Sempre manter compatibilidade com multi-tenancy.
- Sempre validar ownership por clinic_id.

## Backend

- Utilizar Policies.
- Utilizar Form Requests.
- Utilizar Services para regras complexas.
- Controllers devem permanecer enxutos.
- Toda ação crítica deve gerar auditoria.

## Frontend

- Aplicação SPA.
- Nunca usar window.location.href.
- Sempre usar Vue Router.
- Sempre usar TypeScript.
- Logs e mensagens em português.
- Toasts e erros devem ser amigáveis.

## Testes

Toda alteração deve:

- Executar testes existentes.
- Criar testes quando necessário.
- Não reduzir cobertura.

## Segurança

- Validar autenticação.
- Validar autorização.
- Validar isolamento entre clínicas.
- Revisar rate limits.
- Revisar exposição de dados.

## Deploy

Antes de finalizar:

- php artisan test
- npm run type-check
- npm run build

Nenhuma tarefa é considerada concluída se algum desses passos falhar.