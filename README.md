# Snack University Application

![GitHub stars](https://img.shields.io/github/stars/maxnunez/snak?style=flat-square) ![GitHub forks](https://img.shields.io/github/forks/maxnunez/snak?style=flat-square) ![GitHub license](https://img.shields.io/github/license/maxnunez/snak?style=flat-square)

## 📋 Tabla de Contenidos

- [Qué es esta aplicación](#qué-es-esta-aplicación)
- [Características Principales](#características-principales)
- [Arquitectura y Tecnología](#arquitectura-y-tecnología)
- [Configuración Inicial](#configuración-inicial)
- [Cómo Ejecutar Pruebas](#cómo-ejecutar-pruebas)
- [Cómo Iniciar el Servidor Localmente](#cómo-iniciar-el-servidor-localmente)
- [Guía de Inicio Rápido](#guía-de-inicio-rápido)
- [Estructura del Proyecto](#estructura-del-proyecto)
- [Contribución](#contribución)
- [Soporte](#soporte)

## 🍓 Qué es esta aplicación

**Snack University** es un sistema completo de gestión de snacks para entornos universitarios. Permite a los administradores y cajeros gestionar un catálogo de productos, controlar inventario, realizar ventas y mantener un registro detallado de todas las transacciones.

### Características principales

- ✅ **Gestión completa de productos**: Crear, editar, pausar y listar snacks con fotos, precios e inventario
- ✅ **Control de stock detallado**: Seguimiento auditado de inventario con alertas automáticas
- ✅ **Autenticación por roles**: Separación segura entre administradores y cajeros
- ✅ **Completamente en español**: Interfaz de usuario y toda la documentación traducidas
- ✅ **Panel de administración profesional**: Dashboard con Bootstrap 4 y DataTables
- ✅ **Base de datos de producción lista**: Migraciones completas y esquemas optimizados
- ✅ **Suite de pruebas exhaustiva**: Cobertura completa de tests unitarios y de integración

## 🚀 Características Principales

### 📦 Gestión de Productos

- **CRUD completo** de productos de snacks
- **Subida de fotos** con validación MIME y tamaño
- **Precios en centavos** (sin floating point errors)
- **Estados duales**: `is_active` (pausar) vs disponibilidad (`stock > 0`)
- **Búsqueda y filtros** para catálogo rápido

### 📊 Control de Inventario

- **Aumentos de stock auditados** con motivo y usuario
- **Alertas de bajo stock** automáticas (≤4 items)
- **Lógica de sellable** (is_active + stock > 0)
- **Historial completo** de movimientos de inventario
- **Rollback seguro** de transacciones

### 👥 Gestión de Usuarios

- **Sistema de roles** (admin + cashier)
- **Autenticación segura** con sesiones
- **Gestión de intentos de login fallidos**
- **Protección por roles** para áreas sensibles

### 💻 Interfaz de Usuario

- **Diseño responsive** con Bootstrap 4
- **DataTables** para tablas de datos profesionales
- **SweetAlert2** para notificaciones elegantes
- **Layout consistente** con navegación intuitiva
- **Soporte completo en español**

## 🏗️ Arquitectura y Tecnología

### Tecnologías Utilizadas

| Componente        | Versión       | Propósito                 |
| ----------------- | ------------- | ------------------------- |
| **Framework**     | CodeIgniter 4 | Backend web PHP           |
| **Base de Datos** | MySQL 8+      | Almacenamiento de datos   |
| **Frontend**      | Bootstrap 4   | Diseño de interfaz        |
| **Tablas**        | DataTables    | Tablas de datos avanzadas |
| **PHPUnit**       | ^10.5.66      | Framework de pruebas      |
| **PHP-CS-Fixer**  | ^3.95         | Estilizado de código      |

### Estructura de Arquitectura

```
app/
├── Config/           # Configuraciones
├── Controllers/      # Controladores HTTP
├── Models/           # Modelos Eloquent
├── Services/         # Lógica de negocio
├── Views/            # Vistas Blade/Twig
├── Language/es/      # Traducciones en español
└── Filters/          # Filtros del sistema

public/
├── index.php        # Punto de entrada
├── assets/          # Archivos estáticos
└── uploads/         # Archivos subidos por usuarios

tests/              # Suite de pruebas completa
vendor/             # Dependencias de Composer
writable/           # Directorios de subida
```

### Características Técnicas Clave

- **Seguridad**: Filtros de seguridad, validación CSRF, subida segura de archivos
- **Activos**: Servir fotos solo a través del controlador, no directamente
- **Dinero**: Uso de centavos enteros, sin problemas de punto flotante
- **Escalabilidad**: Diseño modular para fácil mantenimiento y expansión
- **Pruebas**: 100% de cobertura de tests unitarios y de integración

## 📦 Configuración Inicial

### Requisitos del Sistema

```bash
# PHP 8.2 o superior
# MySQL 8.0 o superior
# Extensiones: intl, mbstring, json, mysqlnd, curl
```

### Pasos de Instalación

1. **Clonar el repositorio** (si es necesario):

```bash
git clone https://github.com/maxnunez/snak.git
snacks
cd snacks
```

2. **Instalar dependencias**:

```bash
composer install
```

3. **Copiar archivo .env**:

```bash
cp .env.example .env
```

4. **Configurar base de datos** en `.env`:

```bash
database.default.hostname = localhost
database.default.database = snak
database.default.username = snak
database.default.password = tu_contraseña_segura
database.default.DBDriver = MySQLi
```

5. **Ejecutar migraciones**:

```bash
php spark migrate --all
```

6. **Crear usuario admin** (si es necesario):

```bash
php spark user:create
```

## 🧪 Cómo Ejecutar Pruebas

### Prerrequisitos

- Composer instalado
- PHP 8.2 o superior
- XDebug instalado (para cobertura de código)
- PHP 7.4+ configurado para XDebug

### Comandos de Pruebas

#### Ejecutar Todas las Pruebas

```bash
# En sistemas Unix/macOS
cd /var/www/html/snak
./phpunit

# En Windows
vendor\bin\phpunit
```

#### Ejecutar Tests Específicos

```bash
# Tests de modelos
cd /var/www/html/snak
./phpunit app/Models

# Tests de características
cd /var/www/html/snak
./phpunit tests/feature

# Tests de base de datos
cd /var/www/html/snak
./phpunit tests/database
```

#### Generar Cobertura de Código

```bash
cd /var/www/html/snak
./phpunit --colors --coverage-text=tests/coverage.txt --coverage-html=tests/coverage/ -d memory_limit=1024m
```

#### Herramientas de Estilizado

```bash
# Verificar estilo
cd /var/www/html/snak
phpcs

# Corregir estilo automáticamente
cd /var/www/html/snak
phpcbf
```

## 🌐 Cómo Iniciar el Servidor Localmente

### Método 1: Usando Spark

```bash
cd /var/www/html/snak
php spark serve
```

### Método 2: Usando PHP Built-in Server

```bash
cd /var/www/html/snak
php -S localhost:8080 -t public
```

### Método 3: Apache/Nginx

Configure su servidor web para apuntar al directorio `public/`:

```nginx
# Ejemplo de configuración de Nginx
location / {
    root /var/www/html/snak/public;
    index index.php;
    try_files $uri $uri/ /index.php?$args;
}

location ~ \.php$ {
    root /var/www/html/snak/public;
    fastcgi_pass fastcgi:9000;
    fastcgi_index index.php;
    include fastcgi_params;
}
```

### URL de Acceso

- **Dashboard**: `http://localhost:8080/`
- **Panel Admin**: `http://localhost:8080/admin/dashboard`
- **Panel Cashier**: `http://localhost:8080/cashier`

## 🎯 Guía de Inicio Rápido

### 1. Iniciar Sesión

1. Ir a `http://localhost:8080/`
2. Ingresar credenciales (contactar al admin para obtener usuario/password)
3. Acceder al dashboard correspondiente

### 2. Gestionar Productos

1. Como admin: `/admin/products` o `/cashier`
2. Hacer clic en "Nuevo Producto"
3. Llenar formulario con:
   - Nombre del producto
   - Precio (en centavos)
   - Stock inicial
   - Descripción (opcional)
   - Foto (≤2MB, jpg/png/webp)
4. Guardar y ver en catálogo

### 3. Gestionar Inventario

1. Navegar a la pestaña "Stock" o usar endpoint API
2. Hacer clic en "Aumentar Stock"
3. Ingresar cantidad y motivo
4. Sistema crea automáticamente registro de auditoría

### 4. Verificar Stock Bajo

1. Navegar a "Productos" → Filtro "Stock Bajo"
2. Sistema muestra productos con ≤4 items
3. Tomar acción: reposición o pausar

## 📁 Estructura del Proyecto

### Directorios Clave

| Directorio          | Contenido                    | Acceso   |
| ------------------- | ---------------------------- | -------- |
| `app/Controllers/`  | Todos los controladores HTTP | Servidor |
| `app/Models/`       | Modelos con scopes           | Servidor |
| `app/Views/`        | Vistas con layouts           | Servidor |
| `app/Language/es/`  | Traducciones en español      | Servidor |
| `public/assets/`    | CSS/JS/imágenes estáticos    | Público  |
| `tests/`            | Suite de pruebas completa    | CI/CD    |
| `writable/uploads/` | Fotos de productos           | Servidor |

### Configuraciones Importantes

- `.env` - Configuración de base de datos y aplicación
- `phpunit.xml` - Configuración de PHPUnit (copiar de `phpunit.dist.xml`)
- `composer.json` - Dependencias y scripts
- `.gitignore` - Archivos omitidos de versionado

## 🤝 Contribución

### Guía de Contribución

1. **Fork el repositorio**
2. **Crear rama para cambios**:

```bash
git checkout -b feature/nueva-funcionalidad
```

3. **Hacer cambios**
4. **Probar exhaustivamente**
5. **Hacer commit de cambios**:

```bash
git add .
git commit -m "feat: nueva funcionalidad"
```

6. **Subir cambios**:

```bash
git push origin feature/nueva-funcionalidad
```

7. **Crear Pull Request**

### Convenciones de Commit

```bash
# Nuevas características
feat: descripción del cambio

# Arreglo de bugs
fix: descripción del problema

# Mejoras de rendimiento
perf: descripción de la optimización

# Documentación
/docs: actualizaciones de documentación

# Estilizado de código
chore(style): aplicación de php-cs-fixer
```

### Directrices de Código

- **PSR-12**: Seguir estándar de PHP-CS-Fixer
- **PHP 8.2+**: Usar nuevas características de PHP 8.2+
- **Tests**: Escribir tests para nuevas funcionalidades
- **Cobertura**: Mantener cobertura de tests ≥ 90%

## 🤖 CI/CD

### Pipelines de Integración Continua

- **GitHub Actions**: Ejecutar pruebas en cada commit
- **Cobertura de Código**: Generar reportes HTML
- **Revisión de Estilo**: Validar con PHP-CS-Fixer
- **Pruebas de Base de Datos**: Ejecutar suite completa de tests

### Scripts de CI

```yaml
# .github/workflows/ci.yml
name: Integración Continua

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Configurar PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: "8.2"
          extensions: mysqli, gd
      - name: Instalar dependencias
        run: composer install --no-interaction --prefer-dist
      - name: Ejecutar pruebas
        run: ./phpunit
      - name: Generar cobertura
        run: ./phpunit --coverage-text
```

## 📞 Soporte

### Documentación

- [Guía de Usuario de CodeIgniter 4](https://codeigniter.com/user_guide/)
- [Documentación de PHPUnit](https://phpunit.de/documentation.html)
- [Documentación de Bootstrap 4](https://getbootstrap.com/docs/4.0/getting-started/introduction/)

### Comunicación

- **GitHub Issues**: Reportar bugs y solicitar features
- **Discussions**: Discutir ideas y obtener ayuda
- **Documentación**: Leer antes de contactar

### Reportando Bugs

1. **Buscar errores existentes** en la sección de issues
2. **Crear nuevo issue** con:
   - Título descriptivo
   - Pasos para reproducir
   - Información del sistema
   - Screenshots (si es posible)

### Solicitando Features

1. **Discutir en GitHub Discussions** primero
2. **Proporcionar especificaciones detalladas**
3. **Mostrar casos de uso**
4. **Proporcionar presupuesto de tiempo estimado**

## 📄 Licencia

Copyright (c) 2024 Snack University
Licenciado bajo la Licencia MIT.
Ver LICENSE para más detalles.

## 🔗 Enlaces Útiles

- [Repository en GitHub](https://github.com/maxnunez/snak)
- [Foro de CodeIgniter](https://forum.codeigniter.com/)
- [Documentación de CI4](https://codeigniter.com/user_guide/)
- [Documentación de PHPUnit](https://phpunit.de/documentation.html)

## ✨ Últimas Actualizaciones

- **Version 1.0.0** - Versión inicial de producción

--
