<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as ReaderXlsx;

class FileController extends AbstractController
{
    #[Route('/upload', name: 'file_upload')]
    public function upload(Request $request)
    {
        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'No file uploaded'], 400);
        }
        return $this->loadFile($file);
    }

    protected function loadFile($file)
    {
        $extension = $file->getClientOriginalExtension();
        if ($extension === 'xlsx')
            $reader = new ReaderXlsx();
        else
            return $this->json(error_clear_last());

        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file);
        $sheetCount = $spreadsheet->getSheetCount();
        $refrigerantData = [];
        $lastValidZone = null;
        for ($i = 0; $i < $sheetCount; $i++) {
            $sheet = $spreadsheet->getSheet($i);
            $highestRow = $sheet->getHighestRow();
            for ($row = 3; $row <= $highestRow; $row++) {
                $refrigerantType = $sheet->getCell('I' . $row)->getValue();
                if (empty($refrigerantType)) {
                    // The empty refrigerants write as 'No especificado'
                    $refrigerantType = 'No especificado';
                }
                $zone = $sheet->getCell('A' . $row)->getValue();
                if (empty($zone) && $lastValidZone !== null) {
                    $zone = $lastValidZone;
                } elseif (!empty($zone)) {
                    $lastValidZone = $zone;
                }

                if (!isset($refrigerantData[$zone])) {
                    $refrigerantData[$zone] = [];
                }

                if (!isset($refrigerantData[$zone][$refrigerantType])) {
                    $refrigerantData[$zone][$refrigerantType] = 0;
                }

                $refrigerantData[$zone][$refrigerantType]++;
            }
        }

        $zones = [];
        $refrigerantTypes = [];
        $chartData = [];

        // Construir el array de zonas y tipos de refrigerantes
        foreach ($refrigerantData as $zone => $refrigerants) {
            $zones[] = $zone;
            foreach ($refrigerants as $refrigerantType => $count) {
                if (!in_array($refrigerantType, $refrigerantTypes)) {
                    $refrigerantTypes[] = $refrigerantType;
                }
            }
        }

        // Inicializar $chartData con ceros para el count 
        foreach ($refrigerantTypes as $refrigerantType) {
            $chartData[$refrigerantType] = array_fill(0, count($zones), 0);
        }

        // Rellenar $chartData con los recuentos
        foreach ($refrigerantData as $zone => $refrigerants) {
            $zoneIndex = array_search($zone, $zones);
            foreach ($refrigerants as $refrigerantType => $count) {
                $chartData[$refrigerantType][$zoneIndex] = $count;
            }
        }

        // // Construir el array final de datos para pasar a la plantilla Twig
        $chartDataFinal = [];
        foreach ($zones as $zoneIndex => $zone) {
            $seriesData = ['name' => $zone, 'data' => []];
            foreach ($refrigerantTypes as $refrigerantType) {
                if (isset($chartData[$refrigerantType][$zoneIndex]) && $chartData[$refrigerantType][$zoneIndex] > 0) {
                    $seriesData['data'][] = ['name' => $refrigerantType, 'valor' => $chartData[$refrigerantType][$zoneIndex]];
                }
            }
            $chartDataFinal[] = $seriesData;
        }

        // return $this->json($chartDataFinal);
        // return $this->json($refrigerantData);

        return $this->render(
            'refrigerant.html.twig',
            [
                'refrigerantData' => $refrigerantData,
                'chartData' => $chartDataFinal,
            ]
        );
    }
}
