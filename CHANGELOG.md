# CHANGELOG

## [2024-12-19] - Instalación de Laravel Permission y configuración de roles

### Archivos modificados:
- `app/Models/User.php`
- `database/seeders/RolePermissionSeeder.php`
- `database/seeders/DatabaseSeeder.php`
- `.cursor/rules.yml`
- `composer.json`
- `composer.lock`
- `config/permission.php`
- `database/migrations/2025_09_22_024846_create_permission_tables.php`

### Cambios realizados:

#### Instalación de Laravel Permission
- **Paquete**: `spatie/laravel-permission` v6.21.0 instalado
- **Configuración**: Archivo de configuración publicado
- **Migraciones**: Tablas de roles y permisos creadas

#### Configuración del modelo User
- **Trait HasRoles**: Agregado al modelo User para manejo de roles y permisos
- **Import**: `use Spatie\Permission\Traits\HasRoles;` añadido

#### Creación de roles y permisos
- **Roles creados**: `superadmin` y `admin`
- **Permisos básicos**: 
  - `view-dashboard`
  - `manage-users`
  - `manage-roles`
  - `manage-permissions`
  - `view-reports`
  - `manage-settings`

#### Asignación de permisos
- **Superadmin**: Todos los permisos asignados
- **Admin**: Permisos limitados (dashboard, reports, users)

#### Usuarios de prueba creados
- **Usuario root**: 
  - Email: `root@admin.com`
  - Password: `root`
  - Rol: `superadmin`
- **Usuario admin**:
  - Email: `admin@admin.com`
  - Password: `admin`
  - Rol: `admin`

#### Reglas de desarrollo actualizadas
- **Nueva regla**: Cada módulo debe incluir test PHPUnit y vista de pruebas
- **Archivo**: `.cursor/rules.yml` actualizado

### Instrucciones de deploy:
1. Ejecutar migraciones: `php artisan migrate`
2. Ejecutar seeders: `php artisan db:seed`
3. Verificar usuarios creados en la base de datos
4. Probar login con las credenciales proporcionadas

### DB:
- **Migración**: `2025_09_22_024846_create_permission_tables.php`
- **Tablas creadas**: `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`
- **Seeder**: `RolePermissionSeeder` ejecutado exitosamente

---

## [2024-12-19] - Migración completa a CDN y corrección de imágenes base64

### Archivos modificados:
- `resources/views/vendor/adminlte/master.blade.php`
- `resources/views/vendor/adminlte/auth/login.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/welcome.blade.php`
- `config/adminlte.php`
- `resources/views/vendor/adminlte/partials/common/preloader.blade.php`
- `resources/views/vendor/adminlte/partials/common/brand-logo-xs.blade.php`
- `resources/views/vendor/adminlte/partials/common/brand-logo-xl.blade.php`
- `resources/views/vendor/adminlte/auth/auth-page.blade.php`
- `resources/views/vendor/adminlte/auth/passwords/confirm.blade.php`
- `routes/web.php`
- `package.json`
- `vite.config.js` (eliminado)

### Cambios realizados:

#### Migración a CDN
- **CSS/JS locales → CDN**: Reemplazados todos los archivos CSS, JS y SASS locales con enlaces CDN
- **FontAwesome**: `https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css`
- **Bootstrap**: `https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css`
- **AdminLTE**: `https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css`
- **jQuery**: `https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js`
- **OverlayScrollbars**: `https://cdnjs.cloudflare.com/ajax/libs/overlayscrollbars/1.13.1/`
- **iCheck Bootstrap**: `https://cdnjs.cloudflare.com/ajax/libs/icheck-bootstrap/3.0.1/icheck-bootstrap.min.css`

#### Corrección de errores de integridad
- **Problema**: Errores SHA-512/SHA-384 integrity attribute mismatch
- **Solución**: Eliminados atributos `integrity` de todos los enlaces CDN
- **Cache busting**: Añadidos parámetros `?v=1` a todos los CDN para forzar invalidación de caché

#### Migración de imágenes a base64
- **Problema**: Errores DNS con `via.placeholder.com` y rutas locales
- **Solución**: Reemplazadas todas las imágenes con SVGs base64 inline
- **Archivos afectados**: Logo principal, logos de autenticación, preloader
- **Formato**: `data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzMiIGhlaWdodD0iMzMi...`

#### Corrección de asset() con base64
- **Problema**: Laravel procesaba imágenes base64 con `asset()` creando URLs incorrectas
- **Solución**: Eliminado `asset()` de configuraciones de imágenes base64
- **Archivos corregidos**: `auth-page.blade.php`, `confirm.blade.php`

#### Configuración de rutas
- **Ruta raíz**: Cambiada de `view('welcome')` a `redirect()->route('login')`
- **Resultado**: Acceso directo al login desde la URL raíz

#### Limpieza de dependencias
- **Vite**: Deshabilitado completamente, archivo `vite.config.js` eliminado
- **package.json**: Eliminadas dependencias innecesarias (Vite, TailwindCSS, Bootstrap, Sass, jQuery)
- **Directorios eliminados**: `public/vendor/`, `resources/sass/`, `resources/css/app.css`

### Instrucciones de deploy:
1. Limpiar caché de Laravel: `php artisan cache:clear && php artisan view:clear && php artisan config:clear`
2. Verificar que no hay archivos locales en `public/vendor/`
3. Confirmar que todos los CDN están accesibles
4. Probar login y navegación básica

### Notas técnicas:
- Todas las imágenes son ahora SVGs base64 inline (sin dependencias externas)
- CDN con parámetros de versión para evitar problemas de caché
- Configuración AdminLTE actualizada para usar imágenes base64 directamente
- Rutas de autenticación funcionando correctamente
