# theme_cpi — Tema oficial CPI Virtual

Tema hijo de **Boost** para Moodle 5.2+. Implementa la identidad visual de CPI: paleta navy/gold y tipografía Poppins/Roboto.

## Paleta y tipografía

| Token | Valor |
|---|---|
| `$cpi-navy` | `#182b53` (primario, navbar, botones) |
| `$cpi-navy-dark` | `#0e1d38` (hover) |
| `$cpi-gold` | `#c9a227` (secundario, acento) |
| `$cpi-gold-light` | `#e4be50` (hover gold) |
| Fuente principal | Poppins 400/500/600/700 |
| Fuente de apoyo | Roboto 400/500/700 |

Las fuentes se inyectan vía `<link>` en `<head>` (hook `before_standard_head_html_generation`), nunca con `@import` en SCSS, para compatibilidad con scssphp.

## Estructura

```
theme/cpi/
├── version.php                  — versión y dependencias del plugin
├── config.php                   — configuración del tema (parent: boost)
├── lib.php                      — callbacks SCSS (pre / main / post)
├── scss/
│   ├── pre.scss                 — tokens de marca (compila antes de Boost)
│   └── post.scss                — overrides visuales (compila después de Boost)
├── db/hooks.php                 — registro del hook de <head>
├── classes/local/hooks/output/
│   └── before_standard_head_html_generation.php  — inyecta Google Fonts
└── lang/
    ├── en/theme_cpi.php
    └── es/theme_cpi.php
```

## Instalación

1. Clonar este repo en `moodle/public/theme/cpi/`:
   ```bash
   git clone <url> moodle/public/theme/cpi
   sudo chown -R 33:33 moodle/public/theme/cpi
   ```
2. Correr el upgrade de Moodle:
   ```bash
   docker compose exec -u www-data php php /var/www/html/admin/cli/upgrade.php --non-interactive
   ```
3. Activar el tema:
   ```bash
   docker compose exec -u www-data php php /var/www/html/admin/cli/cfg.php --name=theme --set=cpi
   docker compose exec -u www-data php php /var/www/html/admin/cli/purge_caches.php
   ```

## Logo e imágenes

El logotipo y la imagen de fondo del login **no van en este repo** — se suben desde la UI de Moodle:
*Administración del sitio › Apariencia › Logos*

Esto evita versionar binarios y permite cambiarlos sin tocar el código.

## Actualizar el tema

Edita `scss/pre.scss` o `scss/post.scss`, haz commit y push, luego en el servidor:
```bash
git pull
docker compose exec -u www-data php php /var/www/html/admin/cli/purge_caches.php
```
Moodle recompila el SCSS en la siguiente carga de página.
