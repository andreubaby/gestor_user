# Swagger / OpenAPI - Uso rapido

## Archivo fuente

- `docs/openapi.yaml`

## Opcion integrada en la app (recomendada)

Rutas disponibles (requieren sesion autenticada):

- `GET /api/docs` (Swagger UI)
- `GET /api/docs/openapi.yaml` (spec servida por Laravel)

## Opcion 1: Swagger Editor (online)

1. Abre https://editor.swagger.io/
2. Copia y pega el contenido de `docs/openapi.yaml`
3. Revisa endpoints y schemas

## Opcion 2: Swagger UI en local con Docker

```bash
docker run --rm -p 8081:8080 -e SWAGGER_JSON=/foo/openapi.yaml -v "${PWD}/docs:/foo" swaggerapi/swagger-ui
```

Despues abre:
- http://localhost:8081

## Opcion 3: Redoc local rapido

```bash
npx redoc-cli serve docs/openapi.yaml
```

## Notas

- La especificacion cubre `routes/api.php` actual.
- Si agregas rutas nuevas, actualiza `docs/openapi.yaml`.


