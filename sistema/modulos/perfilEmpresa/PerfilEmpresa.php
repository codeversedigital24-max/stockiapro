<?php
/**
 * sistema/modulos/PerfilEmpresa.php
 * Lectura y actualización del perfil de la empresa.
 * Todo se limita a la empresa de la sesión; nunca a un ID del cliente.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';

final class PerfilEmpresa
{
    private PDO $pdo;

    /** Campos editables de configuracion_empresa. */
    private const CAMPOS_CONFIG = [
        'nombre_comercial', 'razon_social', 'identificacion_fiscal',
        'email_contacto', 'telefono', 'descripcion_comercial',
        'direccion', 'ciudad', 'pais', 'sitio_web', 'whatsapp',
        'moneda_principal', 'codigo_moneda', 'simbolo_moneda',
        'decimales', 'separador_decimal', 'separador_miles',
        'impuesto_nombre', 'impuesto_porcentaje', 'precios_con_impuesto',
        'comprobante_tipo', 'datos_facturacion',
        'tienda_nombre', 'tienda_slug', 'tienda_contacto',
        'tienda_color_primario', 'tienda_color_secundario',
        'tienda_descripcion', 'tienda_publicada',
        'zona_horaria', 'idioma', 'formato_fecha', 'formato_hora', 'estado',
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Devuelve el perfil completo de la empresa. */
    public function obtener(int $empresaId): array
    {
        if ($empresaId <= 0) {
            return $this->vacio();
        }

        $row = Database::one(
            "SELECT e.id, e.nombre_comercial, e.razon_social, e.identificacion_fiscal,
                    e.email_contacto, e.telefono, e.estado AS empresa_estado,
                    e.tipo_licencia, e.fecha_vencimiento_licencia,
                    e.created_at AS empresa_creada,
                    c.logo_url, c.favicon_url, c.descripcion_comercial, c.direccion,
                    c.ciudad, c.pais, c.sitio_web, c.whatsapp,
                    c.moneda_principal, c.codigo_moneda, c.simbolo_moneda,
                    c.decimales, c.separador_decimal, c.separador_miles,
                    c.impuesto_nombre, c.impuesto_porcentaje, c.precios_con_impuesto,
                    c.comprobante_tipo, c.datos_facturacion,
                    c.tienda_nombre, c.tienda_slug, c.tienda_contacto,
                    c.tienda_color_primario, c.tienda_color_secundario,
                    c.tienda_descripcion, c.tienda_publicada,
                    c.zona_horaria, c.idioma, c.formato_fecha, c.formato_hora,
                    c.estado AS perfil_estado, c.updated_at AS perfil_actualizado
             FROM empresas e
             LEFT JOIN configuracion_empresa c ON c.empresa_id = e.id
             WHERE e.id = :eid AND e.deleted_at IS NULL
             LIMIT 1",
            [':eid' => $empresaId]
        );

        if ($row === null) {
            return $this->vacio();
        }

        $base = $this->vacio();

        return array_merge($base, [
            'id'                    => (int) $row['id'],
            'nombre_comercial'      => (string) ($row['nombre_comercial'] ?? ''),
            'razon_social'          => (string) ($row['razon_social'] ?? ''),
            'identificacion_fiscal' => (string) ($row['identificacion_fiscal'] ?? ''),
            'email_contacto'        => (string) ($row['email_contacto'] ?? ''),
            'telefono'              => (string) ($row['telefono'] ?? ''),
            'descripcion_comercial' => (string) ($row['descripcion_comercial'] ?? ''),
            'direccion'             => (string) ($row['direccion'] ?? ''),
            'ciudad'                => (string) ($row['ciudad'] ?? ''),
            'pais'                  => (string) ($row['pais'] ?? ''),
            'sitio_web'             => (string) ($row['sitio_web'] ?? ''),
            'whatsapp'              => (string) ($row['whatsapp'] ?? ''),
            'moneda_principal'      => (string) ($row['moneda_principal'] ?? $base['moneda_principal']),
            'codigo_moneda'         => (string) ($row['codigo_moneda'] ?? 'USD'),
            'simbolo_moneda'        => (string) ($row['simbolo_moneda'] ?? '$'),
            'decimales'             => (int) ($row['decimales'] ?? 2),
            'separador_decimal'     => (string) ($row['separador_decimal'] ?? '.'),
            'separador_miles'       => (string) ($row['separador_miles'] ?? ','),
            'impuesto_nombre'       => (string) ($row['impuesto_nombre'] ?? 'IVA'),
            'impuesto_porcentaje'   => (float) ($row['impuesto_porcentaje'] ?? 0),
            'precios_con_impuesto'  => (int) ($row['precios_con_impuesto'] ?? 0),
            'comprobante_tipo'      => (string) ($row['comprobante_tipo'] ?? ''),
            'datos_facturacion'     => (string) ($row['datos_facturacion'] ?? ''),
            'tienda_nombre'         => (string) ($row['tienda_nombre'] ?? ''),
            'tienda_slug'           => (string) ($row['tienda_slug'] ?? ''),
            'tienda_contacto'       => (string) ($row['tienda_contacto'] ?? ''),
            'tienda_color_primario' => (string) ($row['tienda_color_primario'] ?? '#2563eb'),
            'tienda_color_secundario' => (string) ($row['tienda_color_secundario'] ?? '#6c757d'),
            'tienda_descripcion'    => (string) ($row['tienda_descripcion'] ?? ''),
            'tienda_publicada'      => (int) ($row['tienda_publicada'] ?? 0),
            'zona_horaria'          => (string) ($row['zona_horaria'] ?? $base['zona_horaria']),
            'idioma'                => (string) ($row['idioma'] ?? 'es'),
            'formato_fecha'         => (string) ($row['formato_fecha'] ?? 'd/m/Y'),
            'formato_hora'          => (string) ($row['formato_hora'] ?? 'H:i'),
            'estado'                => (string) ($row['perfil_estado'] ?? 'activa'),
            'logo'                  => (string) ($row['logo_url'] ?? ''),
            'favicon'               => (string) ($row['favicon_url'] ?? ''),
            'empresa_estado'        => (string) ($row['empresa_estado'] ?? 'activa'),
            'tipo_licencia'         => (string) ($row['tipo_licencia'] ?? ''),
            'fecha_vencimiento'     => $row['fecha_vencimiento_licencia'] ?? null,
            'actualizado'           => (string) ($row['perfil_actualizado'] ?? $row['empresa_creada'] ?? ''),
        ]);
    }

    /**
     * Guarda el perfil. Devuelve ['ok'=>bool, 'mensaje'=>string, 'data'=>array].
     *
     * @param array<string,mixed> $in
     * @param array<string,mixed> $files
     */
    public function guardar(int $empresaId, array $in, array $files = []): array
    {
        if ($empresaId <= 0) {
            return $this->error('Empresa no válida.');
        }

        $datos = $this->normalizar($in);
        $error = $this->validar($datos);

        if ($error !== null) {
            return $this->error($error);
        }

        try {
            $this->pdo->beginTransaction();

            $logoAnterior = '';

            if (!empty($files['logo']['name'])) {
                $logoAnterior = (string) Database::value(
                    'SELECT logo_url FROM configuracion_empresa WHERE empresa_id = :eid LIMIT 1',
                    [':eid' => $empresaId]
                );

                $subida = $this->guardarLogo($empresaId, $files['logo']);

                if (empty($subida['ok'])) {
                    $this->pdo->rollBack();
                    return $this->error((string) ($subida['error'] ?? 'No se pudo subir el logo.'));
                }

                $datos['logo_url'] = $subida['path'];
            }

            $empresas = [
                ':nombre_comercial'      => $datos['nombre_comercial'],
                ':razon_social'          => $datos['razon_social'] !== '' ? $datos['razon_social'] : null,
                ':identificacion_fiscal' => $datos['identificacion_fiscal'] !== '' ? $datos['identificacion_fiscal'] : null,
                ':email_contacto'        => $datos['email_contacto'],
                ':telefono'              => $datos['telefono'] !== '' ? $datos['telefono'] : null,
                ':eid'                   => $empresaId,
            ];

            Database::run(
                "UPDATE empresas
                 SET nombre_comercial = :nombre_comercial,
                     razon_social = :razon_social,
                     identificacion_fiscal = :identificacion_fiscal,
                     email_contacto = :email_contacto,
                     telefono = :telefono,
                     updated_at = NOW()
                 WHERE id = :eid AND deleted_at IS NULL",
                $empresas
            );

            $existe = (int) Database::value(
                'SELECT COUNT(*) FROM configuracion_empresa WHERE empresa_id = :eid',
                [':eid' => $empresaId]
            );

            if ($existe === 0) {
                Database::run(
                    'INSERT INTO configuracion_empresa (empresa_id) VALUES (:eid)',
                    [':eid' => $empresaId]
                );
            }

            $sets = [];
            $params = [':eid' => $empresaId];

            foreach (self::CAMPOS_CONFIG as $campo) {
                $sets[] = "`{$campo}` = :{$campo}";
                $params[":{$campo}"] = $datos[$campo] ?? null;
            }

            if (isset($datos['logo_url'])) {
                $sets[] = 'logo_url = :logo_url';
                $params[':logo_url'] = $datos['logo_url'];
            }

            $sets[] = 'updated_at = NOW()';

            Database::run(
                'UPDATE configuracion_empresa SET ' . implode(', ', $sets) . ' WHERE empresa_id = :eid',
                $params
            );

            $this->pdo->commit();

            // Limpieza del logo anterior, fuera de la transacción.
            if (!empty($datos['logo_url']) && $logoAnterior !== '') {
                $this->borrarLogoAnterior($empresaId, $logoAnterior);
            }

            return [
                'ok'      => true,
                'mensaje' => 'Perfil actualizado correctamente.',
                'data'    => $this->obtener($empresaId),
            ];
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log('[PerfilEmpresa::guardar] ' . $e->getMessage());

            if ($e->getCode() === '23000') {
                return $this->error('El correo de contacto o el identificador de tienda ya están en uso.');
            }

            return $this->error('No se pudo guardar el perfil de la empresa.');
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log('[PerfilEmpresa::guardar] ' . $e->getMessage());

            return $this->error('No se pudo guardar el perfil de la empresa.');
        }
    }

    /* ------------------------------------------------------------------
     | Internos
     * ------------------------------------------------------------------ */

    /** Normaliza y recorta todos los campos editables. */
    private function normalizar(array $in): array
    {
        $corto = fn(string $clave, int $max): string =>
            $this->limpiar((string) ($in[$clave] ?? ''), $max);

        $largo = fn(string $clave, int $max): string =>
            $this->limpiarLargo((string) ($in[$clave] ?? ''), $max);

        $decimales = (int) ($in['decimales'] ?? 2);
        $decimales = max(0, min(4, $decimales));

        $impuesto = (float) str_replace(',', '.', (string) ($in['impuesto_porcentaje'] ?? 0));
        $impuesto = max(0, min(100, $impuesto));

        $sepDecimal = substr(trim((string) ($in['separador_decimal'] ?? '.')), 0, 1);
        if (!in_array($sepDecimal, ['.', ','], true)) {
            $sepDecimal = '.';
        }

        $sepMiles = substr(trim((string) ($in['separador_miles'] ?? ',')), 0, 1);
        if (!in_array($sepMiles, ['.', ','], true)) {
            $sepMiles = ',';
        }

        $slug = mb_strtolower($corto('tienda_slug', 100));
        $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug) ?? '';
        $slug = trim(preg_replace('/-+/', '-', $slug) ?? '', '-');
        $slug = $slug !== '' ? $slug : null;

        $zona = (string) ($in['zona_horaria'] ?? 'America/Mexico_City');
        if (!in_array($zona, DateTimeZone::listIdentifiers(), true)) {
            $zona = 'America/Mexico_City';
        }

        $idioma = (string) ($in['idioma'] ?? 'es');
        if (!in_array($idioma, ['es', 'en', 'pt'], true)) {
            $idioma = 'es';
        }

        $formatoFecha = (string) ($in['formato_fecha'] ?? 'd/m/Y');
        if (!in_array($formatoFecha, ['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y'], true)) {
            $formatoFecha = 'd/m/Y';
        }

        $formatoHora = (string) ($in['formato_hora'] ?? 'H:i');
        if (!in_array($formatoHora, ['H:i', 'h:i A'], true)) {
            $formatoHora = 'H:i';
        }

        $estado = (string) ($in['estado'] ?? 'activa');
        if (!in_array($estado, ['activa', 'inactiva'], true)) {
            $estado = 'activa';
        }

        $colorPrimario = $this->color((string) ($in['tienda_color_primario'] ?? ''), '#2563eb');
        $colorSecundario = $this->color((string) ($in['tienda_color_secundario'] ?? ''), '#6c757d');

        return [
            'nombre_comercial'       => $corto('nombre_comercial', 150),
            'razon_social'           => $corto('razon_social', 200),
            'identificacion_fiscal'  => $corto('identificacion_fiscal', 50),
            'email_contacto'         => mb_strtolower($corto('email_contacto', 150)),
            'telefono'               => $corto('telefono', 30),
            'descripcion_comercial' => $largo('descripcion_comercial', 2000),
            'direccion'              => $corto('direccion', 200),
            'ciudad'                 => $corto('ciudad', 80),
            'pais'                   => $corto('pais', 80),
            'sitio_web'              => $corto('sitio_web', 150),
            'whatsapp'               => $corto('whatsapp', 30),
            'moneda_principal'       => $corto('moneda_principal', 50) !== ''
                ? $corto('moneda_principal', 50)
                : 'Dólar estadounidense',
            'codigo_moneda'          => mb_strtoupper($corto('codigo_moneda', 3)),
            'simbolo_moneda'         => $corto('simbolo_moneda', 5),
            'decimales'              => $decimales,
            'separador_decimal'      => $sepDecimal,
            'separador_miles'        => $sepMiles,
            'impuesto_nombre'        => $corto('impuesto_nombre', 50) !== ''
                ? $corto('impuesto_nombre', 50)
                : 'IVA',
            'impuesto_porcentaje'    => round($impuesto, 2),
            'precios_con_impuesto'   => ((int) ($in['precios_con_impuesto'] ?? 0)) === 1 ? 1 : 0,
            'comprobante_tipo'       => $corto('comprobante_tipo', 50),
            'datos_facturacion'      => $largo('datos_facturacion', 3000),
            'tienda_nombre'          => $corto('tienda_nombre', 150),
            'tienda_slug'            => $slug,
            'tienda_contacto'        => $corto('tienda_contacto', 255),
            'tienda_color_primario'  => $colorPrimario,
            'tienda_color_secundario' => $colorSecundario,
            'tienda_descripcion'     => $largo('tienda_descripcion', 2000),
            'tienda_publicada'       => ((int) ($in['tienda_publicada'] ?? 0)) === 1 ? 1 : 0,
            'zona_horaria'           => $zona,
            'idioma'                 => $idioma,
            'formato_fecha'          => $formatoFecha,
            'formato_hora'           => $formatoHora,
            'estado'                 => $estado,
        ];
    }

    private function validar(array $d): ?string
    {
        if ($d['nombre_comercial'] === '') {
            return 'El nombre comercial es obligatorio.';
        }

        if (!filter_var($d['email_contacto'], FILTER_VALIDATE_EMAIL)) {
            return 'El correo de contacto no es válido.';
        }

        if (preg_match('/^[A-Z]{3}$/', $d['codigo_moneda']) !== 1) {
            return 'El código de moneda debe tener 3 letras (ej. USD).';
        }

        if ($d['simbolo_moneda'] === '') {
            return 'El símbolo monetario es obligatorio.';
        }

        if ($d['separador_decimal'] === $d['separador_miles']) {
            return 'El separador decimal y el de miles deben ser distintos.';
        }

        if ($d['sitio_web'] !== '') {
            if (!filter_var($d['sitio_web'], FILTER_VALIDATE_URL)) {
                return 'El sitio web no es una URL válida.';
            }

            if (preg_match('#^https?://#i', $d['sitio_web']) !== 1) {
                return 'El sitio web debe iniciar con http:// o https://.';
            }
        }

        if ($d['tienda_slug'] !== null && preg_match('/^[a-z0-9-]{3,100}$/', $d['tienda_slug']) !== 1) {
            return 'El identificador de tienda solo admite minúsculas, números y guiones (3 a 100).';
        }

        return null;
    }

    /** Valida y mueve el logo a sistema/uploads/empresa/{id}/. */
    private function guardarLogo(int $empresaId, array $file): array
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'No se pudo recibir el logo.'];
        }

        if ((int) ($file['size'] ?? 0) > 2 * 1024 * 1024) {
            return ['ok' => false, 'error' => 'El logo supera el límite de 2 MB.'];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');

        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['ok' => false, 'error' => 'El archivo recibido no es válido.'];
        }

        $info = @getimagesize($tmp);

        if ($info === false) {
            return ['ok' => false, 'error' => 'El archivo no es una imagen válida.'];
        }

        if ((int) ($info[0] ?? 0) > 5000 || (int) ($info[1] ?? 0) > 5000) {
            return ['ok' => false, 'error' => 'La imagen supera las dimensiones permitidas.'];
        }

        $mime = (string) ($info['mime'] ?? '');

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeReal = $finfo ? finfo_file($finfo, $tmp) : false;

            if ($finfo) {
                finfo_close($finfo);
            }

            if ($mimeReal !== false && $mimeReal !== $mime) {
                return ['ok' => false, 'error' => 'El contenido del archivo no coincide con una imagen permitida.'];
            }
        }

        $extensiones = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($extensiones[$mime])) {
            return ['ok' => false, 'error' => 'Formato no permitido. Usa JPG, PNG o WebP.'];
        }

        $directorio = APP_ROOT . '/sistema/uploads/empresa/' . $empresaId;

        if (!is_dir($directorio) && !mkdir($directorio, 0775, true) && !is_dir($directorio)) {
            return ['ok' => false, 'error' => 'No se pudo crear la carpeta de logos.'];
        }

        $nombre = 'logo_' . bin2hex(random_bytes(8)) . '.' . $extensiones[$mime];
        $destino = $directorio . '/' . $nombre;

        if (!move_uploaded_file($tmp, $destino)) {
            return ['ok' => false, 'error' => 'No se pudo guardar el logo.'];
        }

        return [
            'ok'   => true,
            'path' => 'sistema/uploads/empresa/' . $empresaId . '/' . $nombre,
        ];
    }

    private function borrarLogoAnterior(int $empresaId, string $ruta): void
    {
        $prefijo = 'sistema/uploads/empresa/' . $empresaId . '/';

        if (!str_starts_with($ruta, $prefijo)) {
            return;
        }

        $archivo = APP_ROOT . '/' . ltrim($ruta, '/');

        if (is_file($archivo)) {
            @unlink($archivo);
        }
    }

    private function color(string $valor, string $defecto): string
    {
        $valor = trim($valor);

        return preg_match('/^#[0-9a-fA-F]{6}$/', $valor) === 1 ? strtolower($valor) : $defecto;
    }

    private function limpiar(string $valor, int $max): string
    {
        $valor = strip_tags(trim($valor));
        $valor = preg_replace('/[\x00-\x1F\x7F]/u', '', $valor) ?? '';

        return mb_substr(trim($valor), 0, $max);
    }

    private function limpiarLargo(string $valor, int $max): string
    {
        $valor = strip_tags(trim($valor));
        $valor = str_replace(["\r\n", "\r"], "\n", $valor);
        $valor = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $valor) ?? '';

        return mb_substr(trim($valor), 0, $max);
    }

    private function vacio(): array
    {
        return [
            'id' => 0,
            'nombre_comercial' => '',
            'razon_social' => '',
            'identificacion_fiscal' => '',
            'email_contacto' => '',
            'telefono' => '',
            'descripcion_comercial' => '',
            'direccion' => '',
            'ciudad' => '',
            'pais' => '',
            'sitio_web' => '',
            'whatsapp' => '',
            'moneda_principal' => 'Dólar estadounidense',
            'codigo_moneda' => 'USD',
            'simbolo_moneda' => '$',
            'decimales' => 2,
            'separador_decimal' => '.',
            'separador_miles' => ',',
            'impuesto_nombre' => 'IVA',
            'impuesto_porcentaje' => 0.0,
            'precios_con_impuesto' => 0,
            'comprobante_tipo' => '',
            'datos_facturacion' => '',
            'tienda_nombre' => '',
            'tienda_slug' => '',
            'tienda_contacto' => '',
            'tienda_color_primario' => '#2563eb',
            'tienda_color_secundario' => '#6c757d',
            'tienda_descripcion' => '',
            'tienda_publicada' => 0,
            'zona_horaria' => 'America/Mexico_City',
            'idioma' => 'es',
            'formato_fecha' => 'd/m/Y',
            'formato_hora' => 'H:i',
            'estado' => 'activa',
            'logo' => '',
            'favicon' => '',
            'empresa_estado' => 'activa',
            'tipo_licencia' => '',
            'fecha_vencimiento' => null,
            'actualizado' => '',
        ];
    }

    private function error(string $mensaje): array
    {
        return ['ok' => false, 'mensaje' => $mensaje, 'data' => []];
    }
}
