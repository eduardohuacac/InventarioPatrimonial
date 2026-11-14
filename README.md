# Sistema web de control y seguimiento de bienes patrimoniales

**I.E. Pública N.° 50023 «República de México» – Cusco**
Ingeniería de Software (SIS021) · Universidad Andina del Cusco · 2026-II

Herramienta **auxiliar de control interno**: registra e informa la ubicación, el responsable, el estado y la disponibilidad de cada bien, y conserva su historial completo. No reemplaza el registro oficial (SIGA – Módulo de Patrimonio, UGEL Cusco).

## Módulos

| Módulo | Requerimientos |
|---|---|
| Autenticación, perfiles y auditoría | RF01, RF02, RF13 |
| Registro y consulta de bienes | RF03, RF04, RF05, RF12 |
| Movimientos con sustento (asignación, préstamo, devolución, traslado, cambio de estado, verificación) | RF06 |
| Solicitudes de baja y correcciones con aprobación de la Dirección | RF07, RF08 |
| Importación de registros depurados (reglas R1–R5, bitácora, reconciliación) | RF09 |
| Reportes (PDF/Excel) y alertas | RF10, RF11 |

## Tecnología

- PHP 8.3+ · Laravel 13 · MySQL/MariaDB (XAMPP) · Blade + CSS propio (sin dependencias de Internet)
- barryvdh/laravel-dompdf (PDF) · phpoffice/phpspreadsheet (Excel/CSV)
- PHPUnit · Laravel Pint (PSR-12) · GitHub Actions

## Instalación (Windows + XAMPP)

```bash
# 1. Dependencias
composer install

# 2. Entorno: copie .env.example a .env y genere la clave
copy .env.example .env
php artisan key:generate

# 3. Cree la base «bienes_patrimoniales» (utf8mb4_unicode_ci) en phpMyAdmin, luego:
php artisan migrate --seed          # base + datos de demostración ficticios
# Uso real (sin demo):  php artisan migrate --seed --seeder=BaseSeeder

# 4. Ejecutar
php artisan serve                   # http://localhost:8000
```

### Usuarios de demostración (contraseña: `Patrimonio2026`)

| Perfil | Correo |
|---|---|
| Encargado de patrimonio | patrimonio@ie50023.edu.pe |
| Director(a) | director@ie50023.edu.pe |
| Administrador | admin@ie50023.edu.pe |
| Docente (consulta) | rquispe@ie50023.edu.pe |

> Los nombres de la demostración son ficticios (Ley N.° 29733). Cambie las contraseñas antes de usar datos reales.

Archivo de ejemplo para la importación: `docs/ejemplos/registros_depurados_ejemplo.xlsx`.

## Pruebas y calidad

```bash
php artisan test                              # 41 pruebas (unitarias, integración, aceptación CP01–CP08, regresión, rendimiento)
php artisan test --exclude-group=rendimiento  # sin la prueba de 5 000 bienes
vendor/bin/pint --test                        # convenciones de código
php tools/metricas.php                        # complejidad ciclomática y LOC (herramienta propia)
```

## Arquitectura (resumen)

```
app/
├── Domain/Movimientos/        Reglas del negocio puras
│   ├── Tipos/*Handler.php     Strategy: un manejador por tipo de movimiento
│   ├── FabricaHandlers.php    Factory
│   ├── ProyectorSituacion.php Recalcula la situación vigente desde el historial
│   └── SituacionBien.php      Objeto de valor inmutable
├── Services/                  Casos de uso (transacciones)
│   ├── RegistroBienService, RegistroMovimientoService, Alertas, Indicadores
│   ├── Importacion/           Lector, Diagnóstico + Reglas/R1..R5 (Strategy), Importador
│   └── Reportes/              Generador, exportador Excel
├── Http/Controllers, Middleware (perfiles, cabeceras de seguridad), Policies
└── Models/                    Eloquent; Movimiento, Aprobación, Auditoría y Bitácora son de solo inserción
```

## Flujo de trabajo con Git

- `main`: versión estable (entregas de cada unidad) · `develop`: integración · `feature/<modulo>`: trabajo de cada integrante.
- Commits con prefijo: `feat:`, `fix:`, `test:`, `docs:`, `refactor:`, `chore:`.
- Cada Pull Request a `develop` ejecuta la integración continua (`.github/workflows/ci.yml`).
