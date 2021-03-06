<?php

declare(strict_types=1);

namespace PhpCfdi\SatEstadoCfdi\Utils;

use PhpCfdi\SatEstadoCfdi\CfdiStatus;
use PhpCfdi\SatEstadoCfdi\Status\CancellableStatus;
use PhpCfdi\SatEstadoCfdi\Status\CancellationStatus;
use PhpCfdi\SatEstadoCfdi\Status\DocumentStatus;
use PhpCfdi\SatEstadoCfdi\Status\QueryStatus;

/**
 * Use this object to create a CfdiStatus from the raw string states from SAT webservice
 */
class CfdiStatusBuilder
{
    /** @var string */
    private $codigoEstatus;

    /** @var string */
    private $estado;

    /** @var string */
    private $esCancelable;

    /** @var string */
    private $estatusCancelacion;

    public function __construct(string $codigoEstatus, string $estado, string $esCancelable, string $estatusCancelacion)
    {
        $this->codigoEstatus = $codigoEstatus;
        $this->estado = $estado;
        $this->esCancelable = $esCancelable;
        $this->estatusCancelacion = $estatusCancelacion;
    }

    public function createQueryStatus(): QueryStatus
    {
        // S - Comprobante obtenido satisfactoriamente
        if (0 === strpos($this->codigoEstatus, 'S - ')) {
            return QueryStatus::found();
        }
        // N - 60? ...
        return QueryStatus::notFound();
    }

    public function createDocumentSatus(): DocumentStatus
    {
        if ('Vigente' === $this->estado) {
            return DocumentStatus::active();
        }
        if ('Cancelado' === $this->estado) {
            return DocumentStatus::cancelled();
        }
        // No encontrado
        return DocumentStatus::notFound();
    }

    public function createCancellableStatus(): CancellableStatus
    {
        if ('Cancelable sin aceptación' === $this->esCancelable) {
            return CancellableStatus::cancellableByDirectCall();
        }
        if ('Cancelable con aceptación' === $this->esCancelable) {
            return CancellableStatus::cancellableByApproval();
        }
        // No cancelable
        return CancellableStatus::notCancellable();
    }

    public function createCancellationStatus(): CancellationStatus
    {
        if ('Cancelado sin aceptación' === $this->estatusCancelacion) {
            return CancellationStatus::cancelledByDirectCall();
        }
        if ('Plazo vencido' === $this->estatusCancelacion) {
            return CancellationStatus::cancelledByExpiration();
        }
        if ('Cancelado con aceptación' === $this->estatusCancelacion) {
            return CancellationStatus::cancelledByApproval();
        }
        if ('En proceso' === $this->estatusCancelacion) {
            return CancellationStatus::pending();
        }
        if ('Solicitud rechazada' === $this->estatusCancelacion) {
            return CancellationStatus::disapproved();
        }
        // vacío
        return CancellationStatus::undefined();
    }

    public function create(): CfdiStatus
    {
        return new CfdiStatus(
            $this->createQueryStatus(),
            $this->createDocumentSatus(),
            $this->createCancellableStatus(),
            $this->createCancellationStatus()
        );
    }
}
