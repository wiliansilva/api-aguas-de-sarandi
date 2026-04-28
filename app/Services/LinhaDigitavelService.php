<?php

namespace App\Services;

use App\Helpers\Modulo10;
use App\Repositories\LinhaDigitavelRepository;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Exceptions\HttpResponseException;

class LinhaDigitavelService
{
    public function __construct(
        private readonly LinhaDigitavelRepository $repository
    ) {}

    /**
     * Gera a linha digitável a partir dos dados do documento.
     *
     * Padrão FEBRABAN – Produto 8 (Arrecadação / Saneamento):
     *   Código de barras (44 dígitos):
     *     Pos  1-3 : Identificação (826 = produto/segmento/valor real)
     *     Pos  4   : DV geral (Módulo 10 da sequência de 43 dígitos sem DV)
     *     Pos  5-15: Valor (11 dígitos, 9 inteiros + 2 decimais)
     *     Pos 16-44: Campo livre (29 dígitos, definido pela empresa)
     *
     *   Linha digitável: 4 blocos de 11 dígitos extraídos do barcode de 44 dígitos,
     *   cada bloco seguido de seu próprio DV Módulo 10.
     *
     * @param  string  $ligacao
     * @param  string  $referencia  Formato: MMAA (ex: 0326)
     * @param  string  $vencimento  Formato: YYYY-MM-DD
     * @param  float   $valor
     * @return array{linhaDigitavel: string, qrCodeImagem: ?string}
     */
    public function gerar(string $ligacao, string $referencia, string $vencimento, float $valor): array
    {
        $documento = $this->repository->findDocumento($ligacao, $referencia, $vencimento, $valor);

        if (!$documento) {
            throw new HttpResponseException(
                response()->json([
                    'success' => false,
                    'error'   => 'Documento não encontrado.',
                    'detail'  => 'Nenhum documento em aberto corresponde aos dados informados.',
                ], 404)
            );
        }

        $valorFormatado      = $this->formatarValor($valor);                              // 11 dígitos
        $referenciaFormatada = $this->formatarReferencia($referencia);                    //  4 dígitos
        $vencimentoFormatado = $this->formatarVencimento($vencimento);                    //  8 dígitos
        $ligacaoFormatada    = str_pad($ligacao, 6, '0', STR_PAD_LEFT);                  //  6 dígitos

        // Sequência sem DV – 43 dígitos:
        // 826(3) + Valor(11) + Empresa(8) + Ligacao(6) + Seq(2) + Referencia(4) + Vencimento(8) + Flag(1)
        $sequenciaSemDv =
            '826'
            . $valorFormatado
            . '06350000'
            . $ligacaoFormatada
            . '02'
            . $referenciaFormatada
            . $vencimentoFormatado
            . '0';

         $dv = Modulo10::calcular($sequenciaSemDv);

        // FEBRABAN Produto 8: barcode de 44 dígitos dividido em 4 blocos de 11.
        // O DV geral ocupa a posição 4 do barcode (entre "826" e o valor),
        // por isso é inserido no campo1 após os 3 primeiros dígitos.
        $campo1 = substr($sequenciaSemDv, 0, 3) . $dv . substr($sequenciaSemDv, 3, 7);
        $campo2 = substr($sequenciaSemDv, 10, 11);
        $campo3 = substr($sequenciaSemDv, 21, 11);
        $campo4 = substr($sequenciaSemDv, 32, 11);

        $dv1 = Modulo10::calcular($campo1);
        $dv2 = Modulo10::calcular($campo2);
        $dv3 = Modulo10::calcular($campo3);
        $dv4 = Modulo10::calcular($campo4);

        return [
            'linhaDigitavel' => "{$campo1}-{$dv1} {$campo2}-{$dv2} {$campo3}-{$dv3} {$campo4}-{$dv4}",
            'qrCodeImagem'   => $this->gerarQrCodeBase64($documento->qrCode ?? null),
        ];
    }

    /**
     * Retorna a linha digitável sem formatação (apenas dígitos e DVs).
     */
    public function gerarSemFormatacao(string $ligacao, string $referencia, string $vencimento, float $valor): string
    {
        $dados = $this->gerar($ligacao, $referencia, $vencimento, $valor);

        // Remove espaços e hífens
        return str_replace([' ', '-'], '', $dados['linhaDigitavel']);
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────

    private function gerarQrCodeBase64(?string $conteudo): ?string
    {
        if (empty($conteudo)) {
            return null;
        }

        $writer = new PngWriter();
        $qrCode = new QrCode($conteudo);
        $result = $writer->write($qrCode);

        return 'data:image/png;base64,' . base64_encode($result->getString());
    }

    /**
     * Formata o valor com 11 posições (FEBRABAN Produto 8, pos 5-15).
     * Ex: 50.27 → 00000005027
     */
    private function formatarValor(float $valor): string
    {
        $valorFormatado = number_format($valor, 2, '', '');
        return str_pad((string) $valorFormatado, 11, '0', STR_PAD_LEFT);
    }

    /**
     * Remove a "/" da referência.
     * Ex: 03/26 → 0326  (se vier formatado do banco)
     * ou já vem como 0326 do request.
     */
    private function formatarReferencia(string $referencia): string
    {
        return str_replace('/', '', $referencia);
    }

    /**
     * Remove os "-" da data de vencimento.
     * Ex: 2026-04-15 → 20260415
     */
    private function formatarVencimento(string $vencimento): string
    {
        return str_replace('-', '', $vencimento);
    }
}