<?php

declare(strict_types=1);

namespace App\Modules\CMS\Infrastructure\Database\Seeders;

use App\Modules\CMS\Domain\Models\AllowedFileType;
use Illuminate\Database\Seeder;

/**
 * Seeder Maestro de tipos de archivo. Cubre los formatos web relevantes
 * agrupados por categoría; el administrador solo debe prender/apagar
 * (`is_active`) desde el panel — nunca editar allow-lists en código.
 *
 * Activos por defecto: formatos seguros y de uso cotidiano (imágenes raster,
 * PDF, video/audio web). Apagados por defecto: formatos con superficie de
 * riesgo (SVG puede embeber scripts) o de uso excepcional (comprimidos,
 * fuentes, datos).
 */
class AllowedFileTypesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->definitions() as $definition) {
            AllowedFileType::query()->updateOrCreate(
                ['category' => $definition['category'], 'name' => $definition['name']],
                $definition,
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function definitions(): array
    {
        return [
            // --- Imágenes -----------------------------------------------------
            [
                'category' => 'Imágenes',
                'name' => 'JPEG',
                'description' => 'Fotografías y imágenes rasterizadas con compresión con pérdida. Formato universal para fotos de producto.',
                'mime_types' => ['image/jpeg', 'image/pjpeg'],
                'extensions' => ['jpg', 'jpeg'],
                'icon_reference' => 'pi pi-image',
                'is_active' => true,
            ],
            [
                'category' => 'Imágenes',
                'name' => 'PNG',
                'description' => 'Imágenes sin pérdida con soporte de transparencia. Ideal para logos rasterizados e interfaces.',
                'mime_types' => ['image/png'],
                'extensions' => ['png'],
                'icon_reference' => 'pi pi-image',
                'is_active' => true,
            ],
            [
                'category' => 'Imágenes',
                'name' => 'WebP',
                'description' => 'Formato moderno de Google con mejor compresión que JPEG/PNG. Recomendado para todo el sitio.',
                'mime_types' => ['image/webp'],
                'extensions' => ['webp'],
                'icon_reference' => 'pi pi-image',
                'is_active' => true,
            ],
            [
                'category' => 'Imágenes',
                'name' => 'AVIF',
                'description' => 'Formato de nueva generación (AV1) con compresión superior a WebP. Soporte de navegador amplio desde 2024.',
                'mime_types' => ['image/avif'],
                'extensions' => ['avif'],
                'icon_reference' => 'pi pi-image',
                'is_active' => true,
            ],
            [
                'category' => 'Imágenes',
                'name' => 'GIF',
                'description' => 'Imágenes animadas simples. Considerar video MP4/WebM para animaciones largas (pesa menos).',
                'mime_types' => ['image/gif'],
                'extensions' => ['gif'],
                'icon_reference' => 'pi pi-image',
                'is_active' => true,
            ],
            [
                'category' => 'Imágenes',
                'name' => 'SVG',
                'description' => 'Gráficos vectoriales escalables (logos, iconos). ADVERTENCIA: puede embeber JavaScript — activar solo si los archivos provienen de fuentes confiables.',
                'mime_types' => ['image/svg+xml'],
                'extensions' => ['svg'],
                'icon_reference' => 'pi pi-star',
                'is_active' => false,
            ],
            [
                'category' => 'Imágenes',
                'name' => 'ICO',
                'description' => 'Iconos de navegador (favicon) en formato clásico de Windows.',
                'mime_types' => ['image/x-icon', 'image/vnd.microsoft.icon'],
                'extensions' => ['ico'],
                'icon_reference' => 'pi pi-bookmark',
                'is_active' => true,
            ],

            // --- Documentos ---------------------------------------------------
            [
                'category' => 'Documentos',
                'name' => 'PDF',
                'description' => 'Documento portable: menús, catálogos, cotizaciones, facturas.',
                'mime_types' => ['application/pdf'],
                'extensions' => ['pdf'],
                'icon_reference' => 'pi pi-file-pdf',
                'is_active' => true,
            ],
            [
                'category' => 'Documentos',
                'name' => 'Word (DOCX)',
                'description' => 'Documentos de Microsoft Word (formato moderno OOXML y legado .doc).',
                'mime_types' => [
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/msword',
                ],
                'extensions' => ['docx', 'doc'],
                'icon_reference' => 'pi pi-file-word',
                'is_active' => true,
            ],
            [
                'category' => 'Documentos',
                'name' => 'Excel (XLSX)',
                'description' => 'Hojas de cálculo de Microsoft Excel (OOXML y legado .xls).',
                'mime_types' => [
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.ms-excel',
                ],
                'extensions' => ['xlsx', 'xls'],
                'icon_reference' => 'pi pi-file-excel',
                'is_active' => false,
            ],
            [
                'category' => 'Documentos',
                'name' => 'PowerPoint (PPTX)',
                'description' => 'Presentaciones de Microsoft PowerPoint.',
                'mime_types' => [
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'application/vnd.ms-powerpoint',
                ],
                'extensions' => ['pptx', 'ppt'],
                'icon_reference' => 'pi pi-desktop',
                'is_active' => false,
            ],
            [
                'category' => 'Documentos',
                'name' => 'Texto plano',
                'description' => 'Archivos de texto sin formato (.txt).',
                'mime_types' => ['text/plain'],
                'extensions' => ['txt'],
                'icon_reference' => 'pi pi-align-left',
                'is_active' => false,
            ],

            // --- Video --------------------------------------------------------
            [
                'category' => 'Video',
                'name' => 'MP4 (H.264/H.265)',
                'description' => 'Video estándar web con máxima compatibilidad. Recomendado para banners y hero videos.',
                'mime_types' => ['video/mp4'],
                'extensions' => ['mp4', 'm4v'],
                'icon_reference' => 'pi pi-video',
                'is_active' => true,
            ],
            [
                'category' => 'Video',
                'name' => 'WebM',
                'description' => 'Video abierto (VP9/AV1) con excelente compresión. Alternativa moderna a MP4.',
                'mime_types' => ['video/webm'],
                'extensions' => ['webm'],
                'icon_reference' => 'pi pi-video',
                'is_active' => true,
            ],
            [
                'category' => 'Video',
                'name' => 'QuickTime (MOV)',
                'description' => 'Video de Apple. Común al exportar desde iPhone/Final Cut; convertir a MP4 para publicar.',
                'mime_types' => ['video/quicktime'],
                'extensions' => ['mov'],
                'icon_reference' => 'pi pi-video',
                'is_active' => false,
            ],

            // --- Audio --------------------------------------------------------
            [
                'category' => 'Audio',
                'name' => 'MP3',
                'description' => 'Audio comprimido universal.',
                'mime_types' => ['audio/mpeg', 'audio/mp3'],
                'extensions' => ['mp3'],
                'icon_reference' => 'pi pi-volume-up',
                'is_active' => false,
            ],
            [
                'category' => 'Audio',
                'name' => 'WAV',
                'description' => 'Audio sin compresión (archivos grandes).',
                'mime_types' => ['audio/wav', 'audio/x-wav'],
                'extensions' => ['wav'],
                'icon_reference' => 'pi pi-volume-up',
                'is_active' => false,
            ],
            [
                'category' => 'Audio',
                'name' => 'OGG',
                'description' => 'Audio abierto Vorbis/Opus.',
                'mime_types' => ['audio/ogg'],
                'extensions' => ['ogg', 'oga'],
                'icon_reference' => 'pi pi-volume-up',
                'is_active' => false,
            ],

            // --- Comprimidos --------------------------------------------------
            [
                'category' => 'Comprimidos',
                'name' => 'ZIP',
                'description' => 'Archivo comprimido. Activar solo si se necesita intercambiar paquetes de assets.',
                'mime_types' => ['application/zip', 'application/x-zip-compressed'],
                'extensions' => ['zip'],
                'icon_reference' => 'pi pi-box',
                'is_active' => false,
            ],
            [
                'category' => 'Comprimidos',
                'name' => 'RAR',
                'description' => 'Archivo comprimido propietario.',
                'mime_types' => ['application/vnd.rar', 'application/x-rar-compressed'],
                'extensions' => ['rar'],
                'icon_reference' => 'pi pi-box',
                'is_active' => false,
            ],
            [
                'category' => 'Comprimidos',
                'name' => '7-Zip',
                'description' => 'Archivo comprimido de alta compresión.',
                'mime_types' => ['application/x-7z-compressed'],
                'extensions' => ['7z'],
                'icon_reference' => 'pi pi-box',
                'is_active' => false,
            ],

            // --- Fuentes ------------------------------------------------------
            [
                'category' => 'Fuentes',
                'name' => 'WOFF2',
                'description' => 'Fuente web moderna (recomendada). Para tipografías propias del Theme Builder.',
                'mime_types' => ['font/woff2'],
                'extensions' => ['woff2'],
                'icon_reference' => 'pi pi-language',
                'is_active' => false,
            ],
            [
                'category' => 'Fuentes',
                'name' => 'WOFF',
                'description' => 'Fuente web (formato previo a WOFF2).',
                'mime_types' => ['font/woff', 'application/font-woff'],
                'extensions' => ['woff'],
                'icon_reference' => 'pi pi-language',
                'is_active' => false,
            ],
            [
                'category' => 'Fuentes',
                'name' => 'TrueType / OpenType',
                'description' => 'Fuentes de escritorio (TTF/OTF). Preferir WOFF2 para la web.',
                'mime_types' => ['font/ttf', 'font/otf', 'application/x-font-ttf'],
                'extensions' => ['ttf', 'otf'],
                'icon_reference' => 'pi pi-language',
                'is_active' => false,
            ],

            // --- Datos --------------------------------------------------------
            [
                'category' => 'Datos',
                'name' => 'CSV',
                'description' => 'Datos tabulares separados por comas (importaciones/exportaciones).',
                'mime_types' => ['text/csv', 'application/csv'],
                'extensions' => ['csv'],
                'icon_reference' => 'pi pi-table',
                'is_active' => false,
            ],
            [
                'category' => 'Datos',
                'name' => 'JSON',
                'description' => 'Datos estructurados JSON (configuraciones, exportaciones).',
                'mime_types' => ['application/json'],
                'extensions' => ['json'],
                'icon_reference' => 'pi pi-code',
                'is_active' => false,
            ],
        ];
    }
}
