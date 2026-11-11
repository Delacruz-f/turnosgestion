<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Genera un QR en SVG en el servidor (sin servicios externos, sin GD).
 */
class QrCode
{
    /** Devuelve el marcado SVG listo para insertar en la vista. */
    public static function svg(string $payload, int $size = 160, int $margin = 1): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, $margin),
            new SvgImageBackEnd()
        );

        return (new Writer($renderer))->writeString($payload);
    }
}
